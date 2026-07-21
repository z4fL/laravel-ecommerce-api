<?php

namespace App\Enum;

enum OrderStatus: string
{
    case PENDING_PAYMENT = 'pending_payment';

    case PAID = 'paid';

    case PROCESSING = 'processing';

    case SHIPPED = 'shipped';

    case DELIVERED = 'delivered';

    case COMPLETED = 'completed';

    case CANCELLED = 'cancelled';

    case FAILED  = 'failed';
}
