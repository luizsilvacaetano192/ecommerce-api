<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'description' => ['sometimes', 'required', 'string', 'max:255'],
            'value' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'currency' => ['sometimes', 'required', 'string', 'in:BRL,USD'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'O campo usuário é obrigatório quando fornecido.',
            'user_id.integer' => 'O campo usuário deve ser um número inteiro.',
            'user_id.exists' => 'O usuário informado não existe.',

            'description.required' => 'O campo descrição é obrigatório quando fornecido.',
            'description.string' => 'O campo descrição deve ser uma string.',
            'description.max' => 'O campo descrição deve ter no máximo 255 caracteres.',

            'value.required' => 'O campo valor é obrigatório quando fornecido.',
            'value.numeric' => 'O campo valor deve ser numérico.',
            'value.min' => 'O campo valor deve ser no mínimo 0,01.',

            'currency.required' => 'O campo moeda é obrigatório quando fornecido.',
            'currency.string' => 'O campo moeda deve ser uma string.',
            'currency.in' => 'O campo moeda deve ser BRL ou USD.',
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
