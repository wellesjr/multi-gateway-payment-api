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
            'status' => $this->status,
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
