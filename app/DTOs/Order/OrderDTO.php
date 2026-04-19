<?php

namespace App\DTOs\Order;

use App\DTOs\Order\OrderItemDTO;

class OrderDTO
{
    public function __construct(
        public string $crmId,
        public string $erpId,
        public string $customerName,
        public string $customerEmail,
        public string $customerPhone,
        public float $totalAmount,
        public string $status,
        public array $items,
    ) {}

    public static function fromArray(array $data): self
    {
        $items = array_map(fn($item) => OrderItemDTO::fromArray($item), $data['items'] ?? []);

        return new self(
            crmId: $data['crm_id'],
            erpId: $data['erp_id'],
            customerName: $data['customer_name'],
            customerEmail: $data['customer_email'],
            customerPhone: $data['customer_phone'],
            totalAmount: $data['total_amount'],
            status: $data['status'],
            items: $items,
        );
    }

    public function toArrayOnlyOrder(): array
    {
        return [
            'crm_id' => $this->crmId,
            'erp_id' => $this->erpId,
            'customer_name' => $this->customerName,
            'customer_email' => $this->customerEmail,
            'customer_phone' => $this->customerPhone,
            'total_amount' => $this->totalAmount,
            'status' => $this->status,
        ];
    }

    public function toArray(): array
    {
        return [
            'crm_id' => $this->crmId,
            'erp_id' => $this->erpId,
            'customer_name' => $this->customerName,
            'customer_email' => $this->customerEmail,
            'customer_phone' => $this->customerPhone,
            'total_amount' => $this->totalAmount,
            'status' => $this->status,
            'items' => array_map(fn($item) => $item->toArray(), $this->items),
        ];
    }
}