<?php

namespace App\Http\Controllers;

use App\Http\Requests\Gateway\UpdateGatewayPriorityRequest;
use App\Http\Requests\Gateway\UpdateGatewayStatusRequest;
use App\Http\Resources\GatewayResource;
use App\Models\Gateway;
use App\Services\GatewayService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class GatewayController extends Controller
{
    public function __construct(
        private readonly GatewayService $gatewayService,
    ) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success(
            message: 'Gateways listados com sucesso.',
            data: GatewayResource::collection($this->gatewayService->listAll()),
        );
    }

    public function updateStatus(UpdateGatewayStatusRequest $request, Gateway $gateway): JsonResponse
    {
        $updated = $this->gatewayService->updateStatus($gateway, (bool) $request->validated('is_active'));

        return ApiResponse::success(
            message: 'Status do gateway atualizado com sucesso.',
            data: new GatewayResource($updated),
        );
    }

    public function updatePriority(UpdateGatewayPriorityRequest $request, Gateway $gateway): JsonResponse
    {
        $updated = $this->gatewayService->updatePriority($gateway, (int) $request->validated('priority'));

        return ApiResponse::success(
            message: 'Prioridade do gateway atualizada com sucesso.',
            data: new GatewayResource($updated),
        );
    }
}
