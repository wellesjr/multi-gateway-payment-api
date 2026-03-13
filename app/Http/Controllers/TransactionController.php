<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {}

    public function index(): JsonResponse
    {
        $transactions = $this->transactionService->list();

        return response()->json([
            'success' => true,
            'data' => TransactionResource::collection($transactions),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    public function show(Transaction $transaction): JsonResponse
    {
        $loadedTransaction = $this->transactionService->findById($transaction->id);

        if (!$loadedTransaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transação não encontrada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new TransactionResource($loadedTransaction),
        ]);
    }

    public function refund(Transaction $transaction): JsonResponse
    {
        try {
            $refunded = $this->transactionService->refund($transaction);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reembolso realizado com sucesso.',
            'data' => new TransactionResource($refunded),
        ]);
    }
}
