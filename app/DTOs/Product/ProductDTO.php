<?php

namespace App\DTOs\Product;

class ProductDTO
{
    public function __construct(
        public string $sku,
        public string $name,
        public string $description,
        public float $price,
        public int $stock,
        public string $status,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            sku: $data['sku'],
            name: $data['name'],
            description: $data['description'],
            price: $data['price'],
            stock: $data['stock'],
            status: $data['status'],
        );
    }

    public function toArray(): array
    {
        return [
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock,
            'status' => $this->status,
        ];
    }
}