<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\DTOs\Product\UpdateProductDTO;
use App\Models\Product;

class UpdateProductRequest extends FormRequest
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
        $product = $this->route('product');
        $productId = $product instanceof Product ? $product->getKey() : $product;

        return [
            'sku' => [
                'sometimes', 
                'string', 
                'max:255', 
                Rule::unique('products', 'sku')->ignore($productId)
            ],
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'status' => 'sometimes|string|in:available,unavailable',
        ];
    }

    public function toUpdateDTO(): UpdateProductDTO
    {
        $data = $this->validated();
        return UpdateProductDTO::fromArray($data);
    }
}
