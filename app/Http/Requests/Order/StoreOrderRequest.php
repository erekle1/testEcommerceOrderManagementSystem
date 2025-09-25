<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'cart_items' => ['required', 'array', 'min:1'],
            'cart_items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'cart_items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cart_items.required' => 'Cart items are required.',
            'cart_items.min' => 'At least one cart item is required.',
            'cart_items.*.product_id.required' => 'Product ID is required for each item.',
            'cart_items.*.product_id.exists' => 'The selected product does not exist.',
            'cart_items.*.quantity.required' => 'Quantity is required for each item.',
            'cart_items.*.quantity.min' => 'Quantity must be at least 1 for each item.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'cart_items' => 'cart items',
            'cart_items.*.product_id' => 'product',
            'cart_items.*.quantity' => 'quantity',
        ];
    }
}