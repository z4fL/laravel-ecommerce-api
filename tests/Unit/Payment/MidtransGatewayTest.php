<?php

use App\Enum\PaymentOutcome;
use App\PaymentGateways\MidtransGateway;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tests\TestCase;

uses(TestCase::class);

it('maps supported Midtrans statuses to payment outcomes', function (string $status, PaymentOutcome $outcome) {
    expect((new MidtransGateway)->determineOutcome($status))->toBe($outcome);
})->with([
    ['pending', PaymentOutcome::PENDING],
    ['capture', PaymentOutcome::SUCCESS],
    ['settlement', PaymentOutcome::SUCCESS],
    ['deny', PaymentOutcome::FAILED],
    ['failure', PaymentOutcome::FAILED],
    ['expire', PaymentOutcome::EXPIRED],
    ['cancel', PaymentOutcome::CANCELLED],
]);

it('rejects an unsupported Midtrans status', function () {
    expect(fn () => (new MidtransGateway)->determineOutcome('unknown'))
        ->toThrow(InvalidArgumentException::class);
});

it('accepts a valid webhook signature', function () {
    config(['payment.midtrans.server_key' => 'test-server-key']);

    $payload = [
        'order_id' => 'payment-order',
        'status_code' => '200',
        'gross_amount' => '100000',
    ];
    $payload['signature_key'] = hash(
        'sha512',
        $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'test-server-key'
    );

    (new MidtransGateway)->verify(new Request($payload));

    expect(true)->toBeTrue();
});

it('rejects an invalid or incomplete webhook signature', function (array $payload) {
    config(['payment.midtrans.server_key' => 'test-server-key']);

    expect(fn () => (new MidtransGateway)->verify(new Request($payload)))
        ->toThrow(UnauthorizedHttpException::class);
})->with([
    [['order_id' => 'payment-order', 'status_code' => '200', 'gross_amount' => '100000']],
    [['order_id' => 'payment-order', 'status_code' => '200', 'gross_amount' => '100000', 'signature_key' => 'invalid']],
]);
