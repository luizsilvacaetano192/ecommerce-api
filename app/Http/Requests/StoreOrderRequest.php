<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'    => 'required|exists:users,id',
            'description'=> 'required|string|max:255',
            'value'     => 'required|numeric|min:0.01',
            'currency'   => 'required|in:BRL,USD',
        ];
    }
}
