<?php

namespace App\Repositories\Interfaces;

use App\Models\Gateway;
use Illuminate\Support\Collection;

interface GatewayRepositoryInterface
{
    public function allOrderedByPriority(): Collection;

    public function activeOrderedByPriority(): Collection;

    public function update(Gateway $gateway, array $data): Gateway;
}
