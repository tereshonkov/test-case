<?php

namespace App\DTOs\Product;

class UpdateProductDTO
{
    public function __construct(
        public ?string $sku = null,
        public ?string $name = null,
        public ?string $description = null,
        public ?float $price = null,
        public int|null $stock = null,
        public ?string $status = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            sku: $data['sku'] ?? null,
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            price: isset($data['price']) ? (float) ($data['price']) : null,
            stock: isset($data['stock']) ? (int) ($data['stock']) : null,
            status: $data['status'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock,
            'status' => $this->status,
        ], fn($value) => !is_null($value));
    }
}