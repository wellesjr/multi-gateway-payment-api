<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    protected $fillable = [
        'scope',
        'idempotency_key',
        'request_fingerprint',
        'transaction_id',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
