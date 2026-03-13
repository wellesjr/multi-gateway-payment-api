<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,
            'transactions' => $this->whenLoaded('transactions', function () {
                return $this->transactions->map(fn($transaction) => [
                    'id' => $transaction->id,
                    'status' => $transaction->status?->value ?? $transaction->status,
                    'amount' => $transaction->amount,
                    'external_id' => $transaction->external_id,
                    'created_at' => $transaction->created_at,
                ])->all();
            }),
        ];
    }
}
