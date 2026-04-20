<?php

namespace App\Jobs;

use App\Enums\ReindexTarget;
use App\Jobs\Order\IndexOrderJob;
use App\Jobs\Product\IndexProductJob;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReindexJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function __construct(public readonly ReindexTarget $target)
    {
        $this->onQueue('reindex');
    }

    public function handle(): void
    {
        match ($this->target) {
            ReindexTarget::Products => Product::query()
                ->select('id')
                ->chunkById(500, function ($chunk) {
                    foreach ($chunk as $product) {
                        dispatch(new IndexProductJob((string) $product->id))
                            ->onQueue('reindex');
                    }
                }),
            ReindexTarget::Orders => Order::query()
                ->select('id')
                ->chunkById(500, function ($chunk) {
                    foreach ($chunk as $order) {
                        dispatch(new IndexOrderJob((string) $order->id))
                            ->onQueue('reindex');
                    }
                }),
        };
    }
}
