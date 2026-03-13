<?php

namespace App\Http\Requests\Gateway;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Gate;

class UpdateGatewayStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user ? Gate::forUser($user)->allows('gateways.manage') : false;
    }

    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
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
            ApiResponse::error('Você não tem permissão para atualizar este gateway.', 403)
        );
    }
}
