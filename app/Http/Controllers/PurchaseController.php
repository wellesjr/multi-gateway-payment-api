<?php

namespace App\Http\Controllers;

use App\Dtos\Purchase\PurchaseDto;
use App\Http\Requests\Purchase\StorePurchaseRequest;
use App\Http\Resources\TransactionResource;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseService $purchaseService,
    ) {}

    public function store(StorePurchaseRequest $request): JsonResponse
    {
        try {
            $result = $this->purchaseService->purchase(PurchaseDto::fromArray($request->validated()));
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'data' => new TransactionResource($result['transaction']),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => new TransactionResource($result['transaction']),
        ], 201);
    }
}
