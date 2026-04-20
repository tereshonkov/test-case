<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Client;
use App\Services\ElasticsearchService;
use Illuminate\Support\Facades\Log;
use App\Observers\ProductObserver;
use App\Observers\OrderObserver;
use App\Models\Product;
use App\Models\Order;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Client::class, function () {
            return ClientBuilder::create()
                ->setHosts([config('elasticsearch.host')])
                ->setRetries(2)
                ->setHttpClientOptions([
                    'timeout' => (int) config('elasticsearch.timeout', 2),
                ])
                ->build();
        });

        $this->app->singleton(ElasticsearchService::class, function ($app) {
            return new ElasticsearchService($app->make(Client::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(ElasticsearchService $elasticsearchService): void
    {
        Product::observe(ProductObserver::class);
        Order::observe(OrderObserver::class);
        
        if (!config('elasticsearch.enabled')) {
            return;
        }
        try {
            $elasticsearchService->ensureIndices(); 
        } catch (\Throwable $e) {
            Log::warning('elasticsearch.ensure_indices_failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
