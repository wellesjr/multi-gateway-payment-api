<?php

namespace App\Http\Controllers;

use App\Services\ClientService;
use App\Http\Resources\ClientResource;

class ClientController extends Controller
{
    public function __construct(
        private ClientService $clientService
    ) {}

    public function index()
    {
        $clients = $this->clientService->list();

        return response()->json([
            'success' => true,
            'data' => ClientResource::collection($clients),
            'meta' => [
                'current_page' => $clients->currentPage(),
                'last_page' => $clients->lastPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
            ],
        ]);

    }

    public function show(int $id)
    {
        $client = $this->clientService->find($id);

        return new ClientResource($client);
    }
}