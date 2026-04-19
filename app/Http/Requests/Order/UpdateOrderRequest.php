<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use App\DTOs\Order\UpdateOrderDTO;

class UpdateOrderRequest extends FormRequest
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
            'crm_id' => 'sometimes|string|max:255',
            'erp_id' => 'sometimes|string|max:255',
            'customer_name' => 'sometimes|string|max:255',
            'customer_email' => 'sometimes|email|max:255',
            'customer_phone' => 'sometimes|string|max:255',
            'total_amount' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|string|in:new,pending,completed,cancelled',
            'items' => 'sometimes|array',
            'items.*.product_id' => 'required|uuid',
            'items.*.quantity'   => 'required_with:items|integer|min:1',
            'items.*.price'      => 'required_with:items|numeric|min:0',
        ];
    }

    public function toDTO(): UpdateOrderDTO
    {
        $data = $this->validated();
        return UpdateOrderDTO::fromArray($data);
    }
}
