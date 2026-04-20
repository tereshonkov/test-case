<?php

namespace App\Jobs\Product;

use App\Models\Product;
use App\Services\ElasticsearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 20, 30];

    public function __construct(public readonly string $productId)
    {
        $this->onQueue('products');
    }

    public function handle(ElasticsearchService $es): void
    {
        $es->deleteProduct($this->productId);
    }
}