<?php

namespace App\Enums;

enum PaymentAttemptStatus: string
{
    case Success = 'success';
    case Failed = 'failed';
    case Exception = 'exception';
}
