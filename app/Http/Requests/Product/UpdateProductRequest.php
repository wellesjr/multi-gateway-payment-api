<?php

namespace App\Http\Requests\Product;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProductRequest extends FormRequest
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
        return [
            'name'   => ['sometimes', 'string', 'max:255'],
            'amount' => ['sometimes', 'decimal:0,2', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max'       => 'O nome não pode ter mais de 255 caracteres.',
            'amount.decimal' => 'O valor deve ser um número decimal com até 2 casas decimais.',
            'amount.min'     => 'O valor não pode ser negativo.',
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
                'success' => false,
                'message' => 'Você não tem permissão para atualizar este produto.',
            ], 403)
        );
    }
}
