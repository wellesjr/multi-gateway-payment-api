<?php

namespace App\Repositories;

use App\Models\IdempotencyKey;
use App\Repositories\Interfaces\IdempotencyKeyRepositoryInterface;

class IdempotencyKeyRepository implements IdempotencyKeyRepositoryInterface
{
    public function __construct(
        private readonly IdempotencyKey $model,
    ) {}

    public function findByScopeAndKeyForUpdate(string $scope, string $idempotencyKey): ?IdempotencyKey
    {
        return $this->model->newQuery()
            ->where('scope', $scope)
            ->where('idempotency_key', $idempotencyKey)
            ->lockForUpdate()
            ->first();
    }

    public function create(string $scope, string $idempotencyKey, string $requestFingerprint): IdempotencyKey
    {
        return $this->model->newQuery()->create([
            'scope' => $scope,
            'idempotency_key' => $idempotencyKey,
            'request_fingerprint' => $requestFingerprint,
            'transaction_id' => null,
        ]);
    }

    public function attachTransaction(IdempotencyKey $idempotencyKey, int $transactionId): IdempotencyKey
    {
        $idempotencyKey->transaction_id = $transactionId;
        $idempotencyKey->save();

        return $idempotencyKey->fresh();
    }
}