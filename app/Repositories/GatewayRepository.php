<?php

namespace App\Repositories;

use App\Models\Gateway;
use App\Repositories\Interfaces\GatewayRepositoryInterface;
use Illuminate\Support\Collection;

class GatewayRepository implements GatewayRepositoryInterface
{
    public function __construct(
        private readonly Gateway $model,
    ) {}

    public function allOrderedByPriority(): Collection
    {
        return $this->model->newQuery()
            ->orderBy('priority')
            ->get();
    }

    public function activeOrderedByPriority(): Collection
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();
    }

    public function update(Gateway $gateway, array $data): Gateway
    {
        $gateway->fill($data);
        $gateway->updated_at = now();
        $gateway->save();

        return $gateway->fresh();
    }
}
