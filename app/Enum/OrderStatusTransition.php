<?php

namespace App\Enum;

enum OrderStatusTransition: string
{
    case TRANSITIONED = 'transitioned';
    case IDEMPOTENT = 'idempotent';
    case CONFLICT = 'conflict';
}
