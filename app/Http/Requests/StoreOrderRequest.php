<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'     => 'required|exists:users,id',
            'description' => 'required|string|max:255',
            'value'       => 'required|numeric|min:0.01',
            'currency'    => 'required|in:BRL,USD',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'O campo usuário é obrigatório.',
            'user_id.exists'   => 'O usuário informado não existe.',

            'description.required' => 'O campo descrição é obrigatório.',
            'description.string'   => 'O campo descrição deve ser uma string.',
            'description.max'      => 'O campo descrição deve ter no máximo 255 caracteres.',

            'value.required' => 'O campo valor é obrigatório.',
            'value.numeric'  => 'O campo valor deve ser numérico.',
            'value.min'      => 'O campo valor deve ser no mínimo 0,01.',

            'currency.required' => 'O campo moeda é obrigatório.',
            'currency.in'       => 'O campo moeda deve ser BRL ou USD.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'error' => 'Dados inválidos.',
            'details' => $validator->errors(),
        ], 422);

        throw new HttpResponseException($response);
    }
}
