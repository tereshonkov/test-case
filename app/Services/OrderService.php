<?php

namespace App\Services;

use App\Models\Order;
use App\DTOs\Order\OrderDTO;
use App\DTOs\Order\UpdateOrderDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class OrderService
{
    public function __construct(
        private RememberHelper $rememberHelper,
        private ElasticsearchService $elasticsearchService
    ) {}

    public function getOrders(int $limit = 10, int $page = 1, ?string $q = null): LengthAwarePaginator
    {
        $search = trim((string) $q);
        $searchPart = $search !== '' ? ':q:' . md5($search) : ':q:none';
        $key = "orders:list:v2:limit:{$limit}:page:{$page}{$searchPart}";
        $ttl = (int) config('domain-cache.orders.list_ttl', 30);

        return $this->rememberHelper->rememberTagged(
            domain: 'orders',
            key: $key,
            ttl: $ttl,
            tags: ['orders'],
            resolver: function () use ($limit, $page, $search): LengthAwarePaginator {
                if ($search === '' || !config('elasticsearch.enabled')) {
                    return Order::query()
                        ->with('items')
                        ->latest()
                        ->paginate($limit, ['*'], 'page', $page);
                }

                $result = $this->elasticsearchService->searchOrders($search, $limit, $page);
                $ids = $result['ids'];
                $total = $result['total'];

                if ($ids === []) {
                    return new LengthAwarePaginator([], $total, $limit, $page);
                }


                $items = Order::query()
                    ->with('items')
                    ->whereIn('id', $ids)
                    ->get()
                    ->sortBy(fn(Order $order) => array_search($order->id, $ids, true))->values();

                return new LengthAwarePaginator(
                    items: $items,
                    total: $total,
                    perPage: $limit,
                    currentPage: $page,
                    options: [
                        'path' => request()->url(),
                        'query' => request()->query(),
                    ]
                );
            }
        );
    }

    public function getOrderById(string $id): Order
    {
        $key = "orders:item:v1:{$id}";
        $ttl = (int) config('domain-cache.orders.item_ttl', 120);

        return $this->rememberHelper->rememberTagged(
            domain: 'orders',
            key: $key,
            ttl: $ttl,
            tags: ['orders'],
            resolver: fn() => Order::query()->with('items')->findOrFail($id)
        );
    }

    public function createOrder(OrderDTO $dto): Order
    {
        $order = DB::transaction(function () use ($dto) {
            $order = Order::create($dto->toArrayOnlyOrder());

            foreach ($dto->items as $item) {
                $order->items()->create($item->toArray());
            }
            return $order->fresh(['items']);
        });

        $this->elasticsearchService->indexOrder($order);
        Cache::tags(['orders'])->flush();

        return $order;
    }

    public function updateOrder(Order $order, UpdateOrderDTO $dto): Order
    {
        $updatedOrder = DB::transaction(function () use ($order, $dto) {
            $data = $dto->toArray();

            if (isset($data['items'])) {
                unset($data['items']);
            }

            $order->update($data);

            if ($dto->items !== null) {
                $order->items()->delete();

                foreach ($dto->items as $itemDTO) {
                    $order->items()->create($itemDTO->toArray());
                }
            }

            return $order->fresh(['items']);
        });

        $this->elasticsearchService->indexOrder($updatedOrder);
        Cache::tags(['orders'])->flush();

        return $updatedOrder;
    }

    public function deleteOrder(string $id): bool
    {
        $deleted = (bool) Order::findOrFail($id)->delete();

        $this->elasticsearchService->deleteOrder($id);
        Cache::tags(['orders'])->flush();

        return $deleted;
    }
}
