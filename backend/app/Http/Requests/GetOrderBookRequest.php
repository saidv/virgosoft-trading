<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetOrderBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Since this is a public endpoint, we can return true.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'symbol' => 'required|string|in:BTC,ETH',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'symbol.in' => 'Symbol must be BTC or ETH',
        ];
    }
}
