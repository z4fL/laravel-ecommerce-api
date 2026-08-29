<?php

namespace App\Enum;

enum InventoryHistoryType: string
{
    case DECREASE = 'decrease';
    case INCREASE = 'increase';
}
