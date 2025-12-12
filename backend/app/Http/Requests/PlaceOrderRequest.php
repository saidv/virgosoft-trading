<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Auth handled by Sanctum middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'symbol' => 'required|string|in:BTC,ETH',
            'side' => 'required|string|in:buy,sell',
            'price' => 'required|numeric|gt:0|regex:/^\d+(\.\d{1,8})?$/',
            'amount' => 'required|numeric|gt:0|regex:/^\d+(\.\d{1,8})?$/',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'symbol.in' => 'Symbol must be BTC or ETH',
            'side.in' => 'Side must be buy or sell',
            'price.gt' => 'Price must be greater than 0',
            'price.regex' => 'Price can have maximum 8 decimal places',
            'amount.gt' => 'Amount must be greater than 0',
            'amount.regex' => 'Amount can have maximum 8 decimal places',
        ];
    }
}
