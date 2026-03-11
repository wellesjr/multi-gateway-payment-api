<?php

namespace App\Http\Controllers;

use App\Dtos\CreateUserDto;
use App\Dtos\UpdateUserDto;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function index(): JsonResponse
    {
        $users = $this->userService->list();

        return response()->json([
            'success' => true,
            'data'    => UserResource::collection($users),
            'meta'    => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $dto  = CreateUserDto::fromRequest($request);
        $user = $this->userService->create($dto);

        return response()->json([
            'success' => true,
            'message' => 'Usuário criado com sucesso.',
            'data'    => new UserResource($user),
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new UserResource($user),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $dto         = UpdateUserDto::fromRequest($request);
        $updatedUser = $this->userService->update($user, $dto);

        return response()->json([
            'success' => true,
            'message' => 'Usuário atualizado com sucesso.',
            'data'    => new UserResource($updatedUser),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        try {
            $this->userService->delete($user, request()->user());
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuário excluído com sucesso.',
        ]);
    }
}
