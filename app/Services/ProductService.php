<?php

namespace App\Services;

use App\Models\Product;
use App\DTOs\Product\ProductDTO;
use App\DTOs\Product\UpdateProductDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    public function __construct(
        private RememberHelper $rememberHelper,
        private ElasticsearchService $elasticsearchService
    ) {}

    public function getProducts(int $limit = 10, int $page = 1, ?string $q = null): LengthAwarePaginator
    {
        $search = trim((string) $q);
        $searchPart = $search !== '' ? ':q:' . md5($search) : ':q:none';
        $key = "products:list:v2:limit:{$limit}:page:{$page}{$searchPart}";
        $ttl = (int) config('domain-cache.products.list_ttl', 60);

        return $this->rememberHelper->rememberTagged(
            domain: 'products',
            key: $key,
            ttl: $ttl,
            tags: ['products'],
            resolver: function () use ($limit, $page, $search): LengthAwarePaginator {
                if ($search === '' || !config('elasticsearch.enabled')) {
                    return Product::query()
                        ->latest()
                        ->paginate($limit, ['*'], 'page', $page);
                }

                $result = $this->elasticsearchService->searchProducts($search, $limit, $page);
                $ids = $result['ids'];
                $total = $result['total'];

                if ($ids === []) {
                    return new LengthAwarePaginator([], $total, $limit, $page);
                }

                $items = Product::query()
                    ->whereIn('id', $ids)
                    ->get()
                    ->sortBy(fn(Product $product) => array_search($product->id, $ids, true))
                    ->values();

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

    public function getProductById(string $id): Product
    {
        $key = "products:item:v1:{$id}";
        $ttl = (int) config('domain-cache.products.item_ttl', 300);

        return $this->rememberHelper->rememberTagged(
            domain: 'products',
            key: $key,
            ttl: $ttl,
            tags: ['products'],
            resolver: fn() => Product::query()->findOrFail($id)
        );
    }

    public function createProduct(ProductDTO $dto): Product
    {
        $product = Product::create($dto->toArray());

        Cache::tags(['products'])->flush();

        return $product;
    }

    public function updateProduct(Product $product, UpdateProductDTO $dto): Product
    {
        $product->update($dto->toArray());
        $fresh = $product->fresh();

        Cache::tags(['products'])->flush();

        return $fresh;
    }

    public function deleteProduct(string $id): bool
    {
        $deleted = (bool) Product::findOrFail($id)->delete();

        Cache::tags(['products'])->flush();

        return $deleted;
    }
}
