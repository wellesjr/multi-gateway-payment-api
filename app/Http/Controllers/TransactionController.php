<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {}

    public function index(): JsonResponse
    {
        $transactions = $this->transactionService->list();

        return ApiResponse::success(
            message: 'Transações listadas com sucesso.',
            data: TransactionResource::collection($transactions),
            meta: [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        );
    }

    public function show(Transaction $transaction): JsonResponse
    {
        $loadedTransaction = $this->transactionService->findById($transaction->id);

        if (!$loadedTransaction) {
            return ApiResponse::error('Transação não encontrada.', 404);
        }

        return ApiResponse::success(
            message: 'Transação encontrada com sucesso.',
            data: new TransactionResource($loadedTransaction),
        );
    }

    public function refund(Transaction $transaction): JsonResponse
    {
        try {
            $refunded = $this->transactionService->refund(
                $transaction,
                request()->header('Idempotency-Key'),
            );
        } catch (\DomainException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success(
            message: 'Reembolso realizado com sucesso.',
            data: new TransactionResource($refunded),
        );
    }
}
