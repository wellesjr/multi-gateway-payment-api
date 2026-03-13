<?php

namespace App\Http\Requests\Product;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Gate;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        return Gate::forUser($user)->allows('products.manage');
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
            ApiResponse::error('Você não tem permissão para atualizar este produto.', 403)
        );
    }
}
