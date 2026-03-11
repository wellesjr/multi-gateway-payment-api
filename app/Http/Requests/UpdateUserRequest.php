<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $authUser   = $this->user();
        $targetUser = $this->route('user');

        if (!$authUser) {
            return false;
        }

        // ADMIN pode editar qualquer um
        if ($authUser->role === User::ROLE_ADMIN) {
            return true;
        }

        // MANAGER pode editar qualquer um exceto ADMIN
        if ($authUser->role === User::ROLE_MANAGER) {
            return $targetUser->role !== User::ROLE_ADMIN;
        }

        // Demais roles só podem editar o próprio perfil
        return $authUser->id === $targetUser->id;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        $rules = [
            'name'     => ['sometimes', 'string', 'max:255'],
            'email'    => ['sometimes', 'email', "unique:users,email,{$userId}"],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
            'role'     => ['sometimes', Rule::in(User::ROLES)],
        ];

        $authRole = $this->user()->role;

        // MANAGER não pode atribuir role ADMIN
        if ($authRole === User::ROLE_MANAGER) {
            $rules['role'][] = 'not_in:ADMIN';
        }

        // FINANCE e USER não podem alterar role
        if (in_array($authRole, [User::ROLE_FINANCE, User::ROLE_USER], true)) {
            $rules['role'] = ['prohibited'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.max'           => 'O nome não pode ter mais de 255 caracteres.',
            'email.email'        => 'O e-mail deve ser válido.',
            'email.unique'       => 'Este e-mail já está em uso.',
            'password.min'       => 'A senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'A confirmação de senha não confere.',
            'role.not_in'        => 'Você não tem permissão para atribuir este role.',
            'role.prohibited'    => 'Você não tem permissão para alterar o role.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Erro de validação, por favor verifique os dados informados',
                'errors'  => $validator->errors()->messages(),
            ], 422)
        );
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Você não tem permissão para atualizar este usuário.',
            ], 403)
        );
    }
}
