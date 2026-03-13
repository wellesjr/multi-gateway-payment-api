<?php

namespace App\Http\Controllers;

use App\Dtos\User\CreateUserDto;
use App\Dtos\User\UpdateUserDto;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function index(): JsonResponse
    {
        $users = $this->userService->list();

        return ApiResponse::success(
            message: 'Usuários listados com sucesso.',
            data: UserResource::collection($users),
            meta: [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $dto = CreateUserDto::fromRequest($request);
        $user = $this->userService->create($dto);

        return ApiResponse::success(
            message: 'Usuário criado com sucesso.',
            data: new UserResource($user),
            status: 201,
        );
    }

    public function show(User $user): JsonResponse
    {
        $resource = new UserResource($user);

        return ApiResponse::success(
            message: 'Usuário encontrado com sucesso.',
            data: $resource->toArray(request()),
        );
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse|Response
    {
        $dto = UpdateUserDto::fromRequest($request);

        if (empty($dto->toArray())) {
            return response()->noContent();
        }

        $updatedUser = $this->userService->update($user, $dto);
        $resource = new UserResource($updatedUser);

        return ApiResponse::success(
            message: 'Usuário atualizado com sucesso.',
            data: $resource->toArrayUpdate(request()),
        );
    }

    public function destroy(User $user): JsonResponse|Response
    {
        try {
            $this->userService->delete($user, request()->user());
        } catch (\DomainException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        return ApiResponse::success('Usuário excluído com sucesso.');
    }
}