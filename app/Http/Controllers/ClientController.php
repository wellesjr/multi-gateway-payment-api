<?php

namespace App\Http\Controllers;

use App\Services\ClientService;
use App\Http\Resources\ClientResource;
use App\Support\ApiResponse;

class ClientController extends Controller
{
    public function __construct(
        private ClientService $clientService
    ) {}

    public function index()
    {
        $clients = $this->clientService->list();

        return ApiResponse::success(
            message: 'Clientes listados com sucesso.',
            data: ClientResource::collection($clients),
            meta: [
                'current_page' => $clients->currentPage(),
                'last_page' => $clients->lastPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
            ],
        );
    }

    public function show(int $id)
    {
        $client = $this->clientService->findWithTransactions($id);

        if (!$client) {
            return ApiResponse::error('Cliente não encontrado.', 404);
        }

        return ApiResponse::success(
            message: 'Cliente encontrado com sucesso.',
            data: new ClientResource($client),
        );
    }
}
