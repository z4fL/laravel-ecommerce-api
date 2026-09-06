<h1>Invoice {{ $order->order_number }}</h1>

<p>Thank you for your purchase, {{ $order->user->name }}.</p>

<p>Total: {{ $order->total }}</p>