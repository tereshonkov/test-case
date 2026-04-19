<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Elastic\Elasticsearch\Client;
use Illuminate\Support\Arr;

class ElasticsearchService
{
    public function __construct(
        protected Client $client
    ) {}

    public function searchProducts(string $q, int $limit, int $page): array
    {
        return $this->performSearch(
            index: config('elasticsearch.indices.products'),
            query: $q,
            limit: $limit,
            page: $page,
            fields: ['name^3', 'description', 'sku.text^2', 'sku']
        );
    }

    public function searchOrders(string $q, int $limit, int $page): array
    {
        return $this->performSearch(
            index: config('elasticsearch.indices.orders'),
            query: $q,
            limit: $limit,
            page: $page,
            fields: [
                'crm_id^3',
                'erp_id^3',
                'customer_name^3',
                'customer_email^2',
                'customer_phone^2',
                'items.product_id',
            ]
        );
    }

    public function indexProduct(Product $product): void
    {
        $this->client->index([
            'index' => config('elasticsearch.indices.products'),
            'id' => (string) $product->id,
            'body' => [
                'id' => (string) $product->id,
                'sku' => (string) $product->sku,
                'name' => (string) $product->name,
                'description' => (string) ($product->description ?? ''),
                'status' => (string) $product->status,
                'price' => (float) $product->price,
                'stock' => (int) $product->stock,
                'reserved_stock' => (int) $product->reserved_stock,
                'created_at' => optional($product->created_at)?->toAtomString(),
            ],
            'refresh' => false,
        ]);
    }

    public function deleteProduct(string $id): void
    {
        try {
            $this->client->delete([
                'index' => config('elasticsearch.indices.products'),
                'id' => $id,
                'refresh' => false,
            ]);
        } catch (\Throwable) {
            // noop: id may be absent in index
        }
    }

    public function indexOrder(Order $order): void
    {
        $order->loadMissing('items');

        $this->client->index([
            'index' => config('elasticsearch.indices.orders'),
            'id' => (string) $order->id,
            'body' => [
                'id' => (string) $order->id,
                'crm_id' => (string) ($order->crm_id ?? ''),
                'erp_id' => (string) ($order->erp_id ?? ''),
                'customer_name' => (string) $order->customer_name,
                'customer_email' => (string) ($order->customer_email ?? ''),
                'customer_phone' => (string) ($order->customer_phone ?? ''),
                'status' => (string) $order->status,
                'total_amount' => (float) $order->total_amount,
                'items' => $order->items->map(fn ($item) => [
                    'product_id' => (string) $item->product_id,
                    'quantity' => (int) $item->quantity,
                    'price' => (float) $item->price,
                ])->values()->all(),
                'created_at' => optional($order->created_at)?->toAtomString(),
            ],
            'refresh' => false,
        ]);
    }

    public function deleteOrder(string $id): void
    {
        try {
            $this->client->delete([
                'index' => config('elasticsearch.indices.orders'),
                'id' => $id,
                'refresh' => false,
            ]);
        } catch (\Throwable) {
            // noop
        }
    }

    public function ensureIndices(): void
    {
        $indices = [
            config('elasticsearch.indices.products') => [
                'mappings' => [
                    'properties' => [
                        'id' => ['type' => 'keyword'],
                        'sku' => ['type' => 'keyword', 'fields' => ['text' => ['type' => 'text']]],
                        'name' => ['type' => 'text'],
                        'description' => ['type' => 'text'],
                        'status' => ['type' => 'keyword'],
                        'price' => ['type' => 'double'],
                        'stock' => ['type' => 'integer'],
                        'reserved_stock' => ['type' => 'integer'],
                        'created_at' => ['type' => 'date'],
                    ],
                ],
            ],
            config('elasticsearch.indices.orders') => [
                'mappings' => [
                    'properties' => [
                        'id' => ['type' => 'keyword'],
                        'crm_id' => ['type' => 'keyword'],
                        'erp_id' => ['type' => 'keyword'],
                        'customer_name' => ['type' => 'text', 'fields' => ['keyword' => ['type' => 'keyword']]],
                        'customer_email' => ['type' => 'text', 'fields' => ['keyword' => ['type' => 'keyword']]],
                        'customer_phone' => ['type' => 'text', 'fields' => ['keyword' => ['type' => 'keyword']]],
                        'status' => ['type' => 'keyword'],
                        'total_amount' => ['type' => 'double'],
                        'items' => [
                            'type' => 'nested',
                            'properties' => [
                                'product_id' => ['type' => 'keyword'],
                                'quantity' => ['type' => 'integer'],
                                'price' => ['type' => 'double'],
                            ],
                        ],
                        'created_at' => ['type' => 'date'],
                    ],
                ],
            ],
        ];

        foreach ($indices as $index => $body) {
            if (!$this->client->indices()->exists(['index' => $index])->asBool()) {
                $this->client->indices()->create([
                    'index' => $index,
                    'body' => $body,
                ]);
            }
        }
    }

    protected function performSearch(
        string $index,
        string $query,
        int $limit,
        int $page,
        array $fields
    ): array {
        $response = $this->client->search([
            'index' => $index,
            'from' => max(0, ($page - 1) * $limit),
            'size' => $limit,
            'body' => [
                'query' => [
                    'bool' => [
                        'should' => [
                            [
                                'multi_match' => [
                                    'query' => $query,
                                    'fields' => $fields,
                                    'type' => 'best_fields',
                                    'fuzziness' => 'AUTO',
                                ],
                            ],
                            [
                                'simple_query_string' => [
                                    'query' => $query,
                                    'fields' => $fields,
                                    'default_operator' => 'and',
                                ],
                            ],
                        ],
                        'minimum_should_match' => 1,
                    ],
                ],
            ],
        ])->asArray();

        $hits = Arr::get($response, 'hits.hits', []);
        $total = (int) Arr::get($response, 'hits.total.value', 0);

        return [
            'ids' => array_map(
                static fn (array $hit): string => (string) $hit['_id'],
                $hits
            ),
            'total' => $total,
        ];
    }
}