<?php

namespace App\Services;

use App\Models\Gateway;
use App\Repositories\Interfaces\GatewayRepositoryInterface;
use Illuminate\Support\Collection;

class GatewayService
{
    public function __construct(
        private readonly GatewayRepositoryInterface $gatewayRepository,
    ) {}

    public function listAll(): Collection
    {
        return $this->gatewayRepository->allOrderedByPriority();
    }

    public function updateStatus(Gateway $gateway, bool $isActive): Gateway
    {
        return $this->gatewayRepository->update($gateway, ['is_active' => $isActive]);
    }

    public function updatePriority(Gateway $gateway, int $priority): Gateway
    {
        return $this->gatewayRepository->update($gateway, ['priority' => $priority]);
    }
}
