<?php

namespace App\Enums;

enum IdempotencyScope: string
{
    case Purchase = 'purchase';
    case Refund = 'refund';
}
