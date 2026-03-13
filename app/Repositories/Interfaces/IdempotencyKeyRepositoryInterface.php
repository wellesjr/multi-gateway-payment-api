<?php

namespace App\Repositories\Interfaces;

use App\Models\IdempotencyKey;

interface IdempotencyKeyRepositoryInterface
{
    public function findByScopeAndKeyForUpdate(string $scope, string $idempotencyKey): ?IdempotencyKey;

    public function create(string $scope, string $idempotencyKey, string $requestFingerprint): IdempotencyKey;

    public function attachTransaction(IdempotencyKey $idempotencyKey, int $transactionId): IdempotencyKey;
}
