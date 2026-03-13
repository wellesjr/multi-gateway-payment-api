<?php

namespace App\Models;

use App\Enums\ReconciliationStatus;
use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'client_id',
        'gateway_id',
        'external_id',
        'amount',
        'status',
        'reconciliation_status',
        'card_last_digits',
        'reconciled_at',
        'reconciliation_error',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => TransactionStatus::class,
            'reconciliation_status' => ReconciliationStatus::class,
            'reconciled_at' => 'datetime',
        ];
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'transaction_products')
            ->withPivot('quantity');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function gateway()
    {
        return $this->belongsTo(Gateway::class);
    }

    public function paymentAttempts()
    {
        return $this->hasMany(PaymentAttempt::class);
    }
}
