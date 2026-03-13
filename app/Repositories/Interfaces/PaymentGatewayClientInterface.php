<?php

namespace App\Repositories\Interfaces;

use App\Dtos\Payment\ChargePayloadDto;
use App\Dtos\Payment\GatewayChargeResultDto;



interface PaymentGatewayClientInterface
{
    public function gatewayName(): string;

    public function charge(ChargePayloadDto $payload): GatewayChargeResultDto;

    public function refund(string $externalId): bool;
}
