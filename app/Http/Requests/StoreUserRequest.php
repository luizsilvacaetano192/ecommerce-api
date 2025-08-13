<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'O campo nome é obrigatório.',
            'name.string' => 'O campo nome deve ser uma string.',
            'name.max' => 'O campo nome deve ter no máximo 255 caracteres.',

            'email.required' => 'O campo email é obrigatório.',
            'email.email' => 'O campo email deve ser um endereço válido.',
            'email.unique' => 'O email informado já está em uso.',

            'password.required' => 'O campo senha é obrigatório.',
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
