<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use App\Support\ApiResponse;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Gate;
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

        return Gate::forUser($authUser)->allows('users.update-target', $targetUser);
    }

    public function rules(): array
    {
        $userId     = $this->route('user')->id;
        $roleValues = array_column(UserRole::cases(), 'value');

        $rules = [
            'name'     => ['sometimes', 'string', 'max:255'],
            'email'    => ['sometimes', 'email:rfc,dns', "unique:users,email,{$userId}"],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
            'role'     => ['sometimes', Rule::in($roleValues)],
        ];

        $authRole = $this->user()->role;

        if ($authRole === UserRole::Manager) {
            $rules['role'][] = 'not_in:ADMIN';
        }

        if (in_array($authRole, [UserRole::Finance, UserRole::User], true)) {
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
            ApiResponse::error(
                message: 'Erro de validação, por favor verifique os dados informados',
                status: 422,
                errors: $validator->errors()->messages(),
            )
        );
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            ApiResponse::error('Você não tem permissão para atualizar este usuário.', 403)
        );
    }
}
