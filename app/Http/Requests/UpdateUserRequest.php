<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $userId = $this->route('user');

        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('users')->ignore($userId),
            ],
            'password' => 'nullable|string|min:6',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'O campo nome é obrigatório quando fornecido.',
            'name.string' => 'O campo nome deve ser uma string.',
            'name.max' => 'O campo nome deve ter no máximo 255 caracteres.',

            'email.required' => 'O campo email é obrigatório quando fornecido.',
            'email.email' => 'O campo email deve ser um endereço válido.',
            'email.unique' => 'O email informado já está em uso.',

            'password.string' => 'O campo senha deve ser uma string.',
            'password.min' => 'O campo senha deve ter no mínimo 6 caracteres.',
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
