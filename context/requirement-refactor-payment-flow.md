# Requirement File untuk Review Refactoring Payment Flow

Checklist source code yang dibutuhkan untuk melakukan review refactoring pada Milestone 4 (Payment & Order Management), project `laravel-ecommerce-api`.

Tempelkan isi tiap file di bawah section yang sesuai. Kalau file tidak ada / tidak relevan, tulis "N/A" saja.

---

## 1. Controller Layer

- `app/Http/Controllers/PaymentController.php`

```php
class PaymentController extends Controller
{

    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    public function store(Order $order)
    {
        $payment = $this->paymentService->create($order);

        return $this->created('Payment', new PaymentResource($payment));
    }
}
```

- `app/Http/Controllers/WebhookController.php`

```php
class WebhookController extends Controller
{
    public function handle(
        PaymentWebhookRequest $request,
        PaymentWebhookGatewayResolver $resolver,
        PaymentEventProcessor $processor,
        PaymentStatusService $statusService,
    ) {
        $gateway = $request->route('gateway');

        $webhookGateway = $resolver->resolve($gateway);

        $webhookGateway->verify($request);

        $normalized = $gateway->normalize($request);

        Log::info('Payment webhook received', [
            'gateway' => $gateway,
            'gateway_order_id' => $normalized['order_id'] ?? null,
            'gateway_transaction_id' => $normalized['transaction_id'] ?? null,
            'status' => $normalized['status'] ?? null,
        ]);

        $result = $processor->process(normalized);

        Log::info('Payment event processed', [
            'payment_id' => $result->paymentId,
            'outcome' => $result->outcome->value,
        ]);

        $transition = $statusService->update($result);

        return $this->success([
            'payment_id' => $result->paymentId,
            'outcome' => $result->outcome->value,
            'transition' => $transition->value,
        ]);
    }
}
```

## 2. Service Layer

- `app/Services/Payment/PaymentService.php`

```php
class PaymentService
{

    public function __construct(
        private readonly PaymentGatewayInterface $paymentGateway,
    ) {}

    public function create(Order $order): Payment
    {
        if ($order->status !== OrderStatus::PENDING_PAYMENT) {
            throw ValidationException::withMessages([
                'payment' => 'Order is not eligible for payment.',
            ]);
        }

        return DB::transaction(function () use ($order) {
            $payment = Payment::create([
                'order_id' => $order->id,
                'gateway' => config('payment.default'),
                'gateway_order_id' => (string) Str::ulid(),
                'status' => PaymentStatus::PENDING,
                'amount' => $order->total,
            ]);

            Log::info('Payment gateway request sent', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'gateway' => $payment->gateway,
                'gateway_order_id' => $payment->gateway_order_id,
                'amount' => $payment->amount,
            ]);

            $response = $this->paymentGateway->createTransaction(
                $payment->load(['order.orderItems', 'order.user'])
            );

            $errorMessages = data_get($response, 'error_messages');

            if (! empty($errorMessages)) {
                Log::error('Payment gateway request failed', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'gateway' => $payment->gateway,
                    'error' => is_array($errorMessages)
                        ? implode(' ', $errorMessages)
                        : (string) $errorMessages,
                ]);

                throw ValidationException::withMessages([
                    'payment' => is_array($errorMessages)
                        ? implode(' ', $errorMessages)
                        : (string) $errorMessages,
                ]);
            }

            $payment->update([
                'gateway_transaction_id' => $response['transaction_id'],
                'payment_url' => $response['redirect_url'],
                'expired_at' => now()->addMinutes(29),
                'metadata' => [
                    'snap_token' => $response['token']
                ]
            ]);

            return $payment;
        });
    }
}
```

- `app/Services/Payment/PaymentStatusService.php`

```php
class PaymentStatusService
{
    public function __construct(
        private readonly OrderStatusService $orderStatusService,
    ) {}
    private const ALLOWED_TRANSITIONS = [
        PaymentStatus::PENDING->value => [
            PaymentStatus::PAID,
            PaymentStatus::FAILED,
            PaymentStatus::EXPIRED,
            PaymentStatus::CANCELLED,
        ],
    ];

    public function update(PaymentEventResult $result): PaymentStatusTransition
    {
        return DB::transaction(function () use ($result) {
            $payment = Payment::query()
                ->with('order')
                ->findOrFail($result->paymentId);

            $targetStatus = $this->determineTargetStatus($result->outcome);

            $currentStatus = $payment->status;

            $transition = $this->determineTransition(
                $payment,
                $currentStatus,
                $targetStatus,
            );

            if ($transition !== PaymentStatusTransition::TRANSITIONED) {
                return $transition;
            }

            $previousStatus = $payment->status;

            $this->persistTransition($payment, $targetStatus);

            $this->logTransition(
                'Payment status transitioned',
                [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'from' => $previousStatus->value,
                    'to' => $targetStatus->value,
                ],
                'info',
            );

            if ($targetStatus === PaymentStatus::PAID) {
                $this->updateOrderStatus($payment);
            }

            return $transition;
        });
    }

    private function updateOrderStatus(Payment $payment): void
    {
        $transition = $this->orderStatusService->update(
            $payment->order,
            OrderStatus::PAID,
        );

        if ($transition === OrderStatusTransition::CONFLICT) {
            throw ValidationException::withMessages([
                'order' => 'Order status cannot be updated to paid.',
            ]);
        }
    }

    private function determineTargetStatus(PaymentOutcome $outcome): PaymentStatus
    {
        return match ($outcome) {
            PaymentOutcome::PENDING => PaymentStatus::PENDING,
            PaymentOutcome::SUCCESS => PaymentStatus::PAID,
            PaymentOutcome::FAILED => PaymentStatus::FAILED,
            PaymentOutcome::EXPIRED => PaymentStatus::EXPIRED,
            PaymentOutcome::CANCELLED => PaymentStatus::CANCELLED,
        };
    }

    private function determineTransition(
        Payment $payment,
        PaymentStatus $current,
        PaymentStatus $target,
    ): PaymentStatusTransition {
        if ($current === $target) {
            $this->logTransition(
                'Payment status transition idempotent',
                [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'status' => $current->value,
                ],
                'info',
            );

            return PaymentStatusTransition::IDEMPOTENT;
        }

        if ($this->canTransition($current, $target)) {
            return PaymentStatusTransition::TRANSITIONED;
        }

        $this->logTransition(
            'Payment status transition conflict',
            [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'current_status' => $current->value,
                'requested_status' => $target->value,
            ],
            'warning',
        );

        return PaymentStatusTransition::CONFLICT;
    }

    private function canTransition(PaymentStatus $current, PaymentStatus $target): bool
    {
        return in_array(
            $target,
            self::ALLOWED_TRANSITIONS[$current->value] ?? [],
            true,
        );
    }

    private function persistTransition(Payment $payment, PaymentStatus $targetStatus): void
    {
        $payment->status = $targetStatus;

        if ($targetStatus === PaymentStatus::PAID) {
            $payment->paid_at = now();
        }

        $payment->save();
    }

    private function logTransition(string $message, array $context, string $level): void
    {
        match ($level) {
            'info' => Log::info($message, $context),
            'warning' => Log::warning($message, $context),
            'error' => Log::error($message, $context),
            default => Log::info($message, $context),
        };
    }
}
```

- `app/Services/Order/OrderStatusService.php`

```php
class OrderStatusService
{
    public function update(Order $order, OrderStatus $targetStatus): OrderStatusTransition
    {
        $currentStatus = $order->status;

        $transition = $this->determineTransition(
            $currentStatus,
            $targetStatus,
        );

        if ($transition === OrderStatusTransition::IDEMPOTENT) {
            $this->logTransition(
                'Order status transition idempotent',
                [
                    'order_id' => $order->id,
                    'status' => $currentStatus->value,
                ],
                'info',
            );

            return $transition;
        }

        if ($transition === OrderStatusTransition::CONFLICT) {
            $this->logTransition(
                'Order status transition conflict',
                [
                    'order_id' => $order->id,
                    'current_status' => $currentStatus->value,
                    'requested_status' => $targetStatus->value,
                ],
                'warning',
            );

            return $transition;
        }

        $previousStatus = $order->status;

        $this->persistTransition($order, $targetStatus);

        $this->logTransition(
            'Order status transitioned',
            [
                'order_id' => $order->id,
                'from' => $previousStatus->value,
                'to' => $targetStatus->value,
            ],
            'info',
        );

        return $transition;
    }

    private function determineTransition(
        OrderStatus $current,
        OrderStatus $target,
    ): OrderStatusTransition {
        if ($current === $target) {
            return OrderStatusTransition::IDEMPOTENT;
        }

        if ($current->canTransitionTo($target)) {
            return OrderStatusTransition::TRANSITIONED;
        }

        return OrderStatusTransition::CONFLICT;
    }

    private function persistTransition(
        Order $order,
        OrderStatus $targetStatus,
    ): void {
        $order->status = $targetStatus;
        $order->save();
    }

    private function logTransition(string $message, array $context, string $level): void
    {
        match ($level) {
            'info' => Log::info($message, $context),
            'warning' => Log::warning($message, $context),
            'error' => Log::error($message, $context),
            default => Log::info($message, $context),
        };
    }
}
```

- `app/Services/Payment/PaymentEventProcessor.php`

```php
class PaymentEventProcessor
{
    public function __construct(
        private readonly PaymentWebhookInterface $paymentWebhook,
    ) {}

    public function process(array $event): PaymentEventResult
    {
        $payment = Payment::query()
            ->where('gateway', $event['gateway'])
            ->where('gateway_transaction_id', $event['transaction_id'])
            ->first();

        if (! $payment) {
            throw ValidationException::withMessages([
                'payment' => 'Payment transaction could not be resolved.',
            ]);
        }

        if ($payment->gateway_order_id !== $event['order_id']) {
            throw ValidationException::withMessages([
                'payment' => 'Payment gateway order ID does not match.',
            ]);
        }

        if ((int) $payment->amount !== (int) $event['gross_amount']) {
            throw ValidationException::withMessages([
                'payment' => 'Payment amount does not match.',
            ]);
        }

        $outcome = $this->paymentWebhook->determineOutcome(
            $event['status']
        );

        return new PaymentEventResult(
            paymentId: $payment->id,
            outcome: $outcome,
        );
    }
}
```

## 3. Gateway & Webhook Abstraction

- `app/Contracts/PaymentGatewayInterface.php`

```php
interface PaymentGatewayInterface
{
    public function createTransaction(Payment $payment): array;
    public function getTransaction(string $transactionId): array;
    public function cancelTransaction(string $transactionId): array;
}
```

- `app/Contracts/PaymentWebhookInterface.php`

```php
interface PaymentWebhookInterface
{
    public function verify(Request $request): void;
    public function normalize(Request $request): array;
    public function determineOutcome(string $status): PaymentOutcome;
}
```

- `app/Services/PaymentGateways/MidtransGateway.php`

```php
class MidtransGateway implements PaymentGatewayInterface, PaymentWebhookInterface
{
    public function __construct()
    {
        Config::$serverKey = config('payment.midtrans.server_key');
        Config::$clientKey = config('payment.midtrans.client_key');
        Config::$isProduction = config('payment.midtrans.is_production');
        Config::$isSanitized = config('payment.midtrans.is_sanitized');
        Config::$is3ds = config('payment.midtrans.is_3ds');
    }

    private function splitName(?string $fullName): array
    {
        $name = trim($fullName ?? '');

        if (empty($name)) {
            return ['first_name' => '', 'last_name' => ''];
        }

        // Split by any amount of whitespace
        $parts = preg_split('/\s+/', $name);
        $lastName = array_pop($parts);
        $firstName = !empty($parts) ? implode(' ', $parts) : $lastName;

        return [$firstName, $lastName];
    }

    public function createTransaction(Payment $payment): array
    {
        [$firstName, $lastName] = $this->splitName($payment->order?->recipient_name);

        $payload = [
            'transaction_details' => [
                'order_id' => $payment->gateway_order_id,
                'gross_amount' => $payment->amount
            ],
            'customer_details' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $payment->order->user->email,
                'phone' => $payment->order->phone,
                'shipping_address' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $payment->order->phone,
                    'address' => $payment->order->address,
                    'city' => $payment->order->city,
                    'postal_code' => $payment->order->postal_code,
                    'country_code' => 'IDN'
                ]
            ],
            'expiry' => [
                "unit" => "minutes",
                "duration" => 30
            ]
        ];

        $response = Snap::createTransaction($payload);

        if (is_object($response) && ! empty($response->error_messages)) {
            return [
                'error_messages' => (array) $response->error_messages,
            ];
        }

        if (is_array($response) && ! empty($response['error_messages'])) {
            return [
                'error_messages' => (array) $response['error_messages'],
            ];
        }

        return [
            'redirect_url' => data_get($response, 'redirect_url'),
            'token' => data_get($response, 'token'),
        ];
    }

    public function getTransaction(string $transactionId): array
    {
        throw new BadMethodCallException(
            'getTransaction is not implemented yet.'
        );
    }

    public function cancelTransaction(string $transactionId): array
    {
        throw new BadMethodCallException(
            'cancelTransaction is not implemented yet.'
        );
    }

    public function verify(Request $request): void
    {
        $payload = $request->all();

        $orderId = $payload['order_id'] ?? null;
        $statusCode = $payload['status_code'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? null;
        $signatureKey = $payload['signature_key'] ?? null;

        if (
            $orderId === null ||
            $statusCode === null ||
            $grossAmount === null ||
            $signatureKey === null
        ) {
            throw new UnauthorizedHttpException(
                'Bearer',
                'Invalid webhook signature.'
            );
        }

        $expectedSignature = hash(
            'sha512',
            $orderId
                . $statusCode
                . $grossAmount
                . config('payment.midtrans.server_key')
        );

        if (! hash_equals($expectedSignature, $signatureKey)) {
            throw new UnauthorizedHttpException(
                'Bearer',
                'Invalid webhook signature.'
            );
        }
    }

    public function normalize(Request $request): array
    {
        $payload = $request->all();

        return [
            'gateway' => 'midtrans',
            'transaction_id' => $payload['transaction_id'],
            'order_id' => $payload['order_id'],
            'status' => $payload['transaction_status'],
            'payment_type' => $payload['payment_type'] ?? null,
            'gross_amount' => $payload['gross_amount'],
            'currency' => $payload['currency'] ?? null,
            'raw_payload' => $payload,
        ];
    }

    public function determineOutcome(string $status): PaymentOutcome
    {
        return match ($status) {
            'pending' => PaymentOutcome::PENDING,

            'capture',
            'settlement' => PaymentOutcome::SUCCESS,

            'deny',
            'failure' => PaymentOutcome::FAILED,

            'expire' => PaymentOutcome::EXPIRED,

            'cancel' => PaymentOutcome::CANCELLED,

            default => throw new InvalidArgumentException(
                "Unsupported Midtrans transaction status: {$status}"
            ),
        };
    }
}
```

- `app/Services/PaymentWebhookGatewayResolver.php`

```php
class PaymentWebhookGatewayResolver
{
    public function resolve(string $gateway): PaymentWebhookInterface
    {
        return match ($gateway) {
            'midtrans' => app(MidtransGateway::class),

            default => throw new InvalidArgumentException(
                'Unsupported webhook gateway.'
            ),
        };
    }
}
```

## 4. DTO & Enum

- `app/DataTransferObjects/PaymentEventResult.php` (class/DTO)

```php
class PaymentEventResult
{
    public function __construct(
        public int $paymentId,
        public PaymentOutcome $outcome,
    ) {}
}
```

- `PaymentOutcome` (enum)

```php
enum PaymentOutcome: string
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
}
```

- `PaymentStatus` (enum)

```php
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
}
```

- `PaymentStatusTransition` (enum)

```php
enum PaymentStatusTransition: string
{
    case TRANSITIONED = 'transitioned';
    case IDEMPOTENT = 'idempotent';
    case CONFLICT = 'conflict';
}

```

- `OrderStatus` (enum)

```php
enum OrderStatus: string
{
    case PENDING_PAYMENT = 'pending_payment';
    case PAID = 'paid';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function canTransitionTo(self $status): bool
    {
        $allowed = match ($this) {
            self::PENDING_PAYMENT => [
                self::PAID,
                self::CANCELLED,
            ],

            self::PAID => [
                self::PROCESSING,
                self::CANCELLED,
            ],

            self::PROCESSING => [
                self::SHIPPED,
            ],

            self::SHIPPED => [
                self::COMPLETED,
            ],

            self::COMPLETED,
            self::CANCELLED => [],
        };

        return in_array($status, $allowed, true);
    }

    public function isFinal(): bool
    {
        return match ($this) {
            self::COMPLETED,
            self::CANCELLED => true,

            default => false,
        };
    }
}

```

- `OrderStatusTransition` (enum)

```php
enum OrderStatusTransition: string
{
    case TRANSITIONED = 'transitioned';
    case IDEMPOTENT = 'idempotent';
    case CONFLICT = 'conflict';
}
```

## 5. Model

- `app/Models/Payment.php`

```php
#[Fillable([
    'order_id',

    'gateway',
    'gateway_order_id',
    'gateway_transaction_id',
    'payment_method',
    'status',

    'amount',

    'payment_url',
    'expired_at',
    'paid_at',

    'metadata',
])]
class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

```

- `app/Models/Order.php`

```php
#[Fillable([
    'order_number',
    'user_id',
    'status',

    'recipient_name',
    'phone',
    'province',
    'city',
    'district',
    'postal_code',
    'address',

    'subtotal',
    'total',
])]
class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
```

- (opsional) relasi/scope penting yang dipakai di query payment/order

## 6. Route

- `routes/api.php` (khusus bagian route payment & webhook)

```php
Route::post('/orders/{order}/payment', [PaymentController::class, 'store']);
Route::post('/webhook/payment/{gateway}', [WebhookController::class, 'handle']);
```

## 7. Test (untuk memastikan behavior yang harus tetap terkunci)

untuk test bisa diskip saja.

## 8. Opsional tapi membantu

- Migration table `payments` dan `orders`

```php
Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained()->cascadeOnDelete();

    $table->string('gateway');
    $table->string('gateway_order_id')->nullable()->unique();
    $table->string('gateway_transaction_id')->nullable()->unique();
    $table->string('payment_method')->nullable();
    $table->string('status');

    $table->unsignedBigInteger('amount');

    $table->string('payment_url')->nullable();
    $table->timestamp('expired_at')->nullable();
    $table->timestamp('paid_at')->nullable();

    $table->json('metadata')->nullable();

    $table->timestamps();
});

Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->string('order_number')->unique();
    $table->foreignId('user_id');
    $table->string('status');

    $table->string('recipient_name');
    $table->string('phone', 20);
    $table->string('province');
    $table->string('city');
    $table->string('district');
    $table->string('postal_code');
    $table->string('address');

    $table->unsignedBigInteger('subtotal');
    $table->unsignedBigInteger('total');

    $table->timestamps();
});
```

- `FormRequest` yang dipakai di endpoint payment (kalau ada validasi khusus) -> "N/A"
- Middleware auth/JWT yang menyelimuti route payment (relevan buat cek security)

untuk middleware memakai Route::middleware('auth:api') dari jwt, aman
