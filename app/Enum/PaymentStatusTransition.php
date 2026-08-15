<?php

namespace App\Enum;

enum PaymentStatusTransition: string
{
    case TRANSITIONED = 'transitioned';
    case IDEMPOTENT = 'idempotent';
    case CONFLICT = 'conflict';
}
