<?php

namespace App\DTOs\Order;

class OrderItemDTO
{
    public function __construct(
        public string $productId,
        public int $quantity,
        public float $price,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productId: $data['product_id'],
            quantity: $data['quantity'],
            price: $data['price'],
        );
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'price' => $this->price,
        ];
    }
}