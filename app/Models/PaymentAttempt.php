<?php

namespace App\Models;

use App\Enums\PaymentAttemptStatus;
use Illuminate\Database\Eloquent\Model;

class PaymentAttempt extends Model
{
    protected $fillable = [
        'transaction_id',
        'gateway_id',
        'status',
        'external_id',
        'error_message',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentAttemptStatus::class,
            'attempted_at' => 'datetime',
        ];
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function gateway()
    {
        return $this->belongsTo(Gateway::class);
    }
}