<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        return in_array($user->role, [UserRole::Admin, UserRole::Manager], true);
    }

    public function rules(): array
    {
        $roleValues = array_column(UserRole::cases(), 'value');

        $rules = [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email:rfc,dns', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role'     => ['sometimes', Rule::in($roleValues)],
        ];

        if ($this->user()->role === UserRole::Manager) {
            $rules['role'][] = 'not_in:ADMIN';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'O nome é obrigatório.',
            'name.max'           => 'O nome não pode ter mais de 255 caracteres.',
            'email.required'     => 'O e-mail é obrigatório.',
            'email.email'        => 'O e-mail deve ser válido.',
            'email.unique'       => 'Este e-mail já está em uso.',
            'password.required'  => 'A senha é obrigatória.',
            'password.min'       => 'A senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'A confirmação de senha não confere.',
            'role.not_in'        => 'Você não tem permissão para atribuir este role.',
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
                'message' => 'Você não tem permissão para criar usuários.',
            ], 403)
        );
    }
}
