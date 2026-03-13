<?php

namespace App\Enums;

enum ReconciliationStatus: string
{
    case Pending = 'pending';
    case Reconciled = 'reconciled';
    case Failed = 'failed';
}
