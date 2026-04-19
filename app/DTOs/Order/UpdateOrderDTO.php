<?php

namespace App\DTOs\Order;

class UpdateOrderDTO
{
    public function __construct(
        public ?string $crmId = null,
        public ?string $erpId = null,
        public ?string $customerName = null,
        public ?string $customerEmail = null,
        public ?string $customerPhone = null,
        public ?float $totalAmount = null,
        public ?string $status = null,  
        public ?array $items = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            crmId: $data['crm_id'] ?? null,
            erpId: $data['erp_id'] ?? null,
            customerName: isset($data['customer_name']) ? (string) ($data['customer_name']) : null,
            customerEmail: isset($data['customer_email']) ? (string) ($data['customer_email']) : null,
            customerPhone: isset($data['customer_phone']) ? (string) ($data['customer_phone']) : null,
            totalAmount: isset($data['total_amount']) ? (float) ($data['total_amount']) : null,
            status: isset($data['status']) ? (string) ($data['status']) : null,
            items: isset($data['items']) 
            ? array_map(fn($item) => OrderItemDTO::fromArray($item), $data['items']) 
            : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'crm_id' => $this->crmId,
            'erp_id' => $this->erpId,
            'customer_name' => $this->customerName,
            'customer_email' => $this->customerEmail,
            'customer_phone' => $this->customerPhone,
            'total_amount' => $this->totalAmount,
            'status' => $this->status,
            'items' => array_map(fn($item) => $item->toArray(), $this->items),
        ], fn($value) => !is_null($value));
    }
}