<?php

namespace App\Http\Controllers;

use App\Dtos\CreateUserDto;
use App\Dtos\UpdateUserDto;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::paginate(15);

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
        $user = User::create($dto->toArray());

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
        $dto = UpdateUserDto::fromRequest($request);
        $user->update($dto->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Usuário atualizado com sucesso.',
            'data'    => new UserResource($user->fresh()),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $authUser = request()->user();

        if ($authUser->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Você não pode excluir sua própria conta.',
            ], 403);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuário excluído com sucesso.',
        ]);
    }
}
