<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status?->value ?? $this->status,
            'reconciliation_status' => $this->reconciliation_status?->value ?? $this->reconciliation_status,
            'reconciled_at' => $this->reconciled_at,
            'reconciliation_error' => $this->reconciliation_error,
            'amount' => $this->amount,
            'external_id' => $this->external_id,
            'card_last_digits' => $this->card_last_digits,
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client?->id,
                    'name' => $this->client?->name,
                    'email' => $this->client?->email,
                ];
            }),
            'gateway' => $this->whenLoaded('gateway', function () {
                if (!$this->gateway) {
                    return null;
                }

                return [
                    'id' => $this->gateway->id,
                    'name' => $this->gateway->name,
                    'priority' => $this->gateway->priority,
                ];
            }),
            'products' => $this->whenLoaded('products', function () {
                return $this->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'amount' => $product->amount,
                        'quantity' => (int) $product->pivot?->quantity,
                    ];
                })->all();
            }),
            'payment_attempts' => $this->whenLoaded('paymentAttempts', function () {
                return $this->paymentAttempts->map(function ($attempt) {
                    return [
                        'id' => $attempt->id,
                        'status' => $attempt->status?->value ?? $attempt->status,
                        'external_id' => $attempt->external_id,
                        'error_message' => $attempt->error_message,
                        'attempted_at' => $attempt->attempted_at,
                        'gateway' => $attempt->relationLoaded('gateway') && $attempt->gateway
                            ? [
                                'id' => $attempt->gateway->id,
                                'name' => $attempt->gateway->name,
                            ]
                            : null,
                    ];
                })->all();
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
