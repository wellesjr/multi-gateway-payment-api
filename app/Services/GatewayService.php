<?php

namespace App\Services;

use App\Models\Gateway;
use Illuminate\Support\Collection;

class GatewayService
{
    /**
     * @return Collection<int, Gateway>
     */
    public function listAll(): Collection
    {
        return Gateway::query()->orderBy('priority')->get();
    }

    public function updateStatus(Gateway $gateway, bool $isActive): Gateway
    {
        $gateway->update(['is_active' => $isActive]);

        return $gateway->fresh();
    }

    public function updatePriority(Gateway $gateway, int $priority): Gateway
    {
        $gateway->update(['priority' => $priority]);

        return $gateway->fresh();
    }
}
