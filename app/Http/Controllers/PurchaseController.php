<?php

namespace App\Http\Controllers;

use App\Dtos\Purchase\PurchaseDto;
use App\Http\Requests\Purchase\StorePurchaseRequest;
use App\Http\Resources\TransactionResource;
use App\Services\PurchaseService;
use App\Support\ApiResponse;
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
            return ApiResponse::error($e->getMessage(), 422);
        }

        if (!$result->success) {
            return ApiResponse::error(
                message: $result->message,
                status: 422,
                data: new TransactionResource($result->transaction),
            );
        }

        return ApiResponse::success(
            message: $result->message,
            data: new TransactionResource($result->transaction),
            status: 201,
        );
    }
}
