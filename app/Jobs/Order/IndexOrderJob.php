<?php

namespace App\Jobs\Order;

use App\Models\Order;
use App\Services\ElasticsearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IndexOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 20, 30];

    public function __construct(public readonly string $orderId)
    {
        $this->onQueue('orders');
    }

    public function handle(ElasticsearchService $es): void
    {
        $order = Order::findOrFail($this->orderId)->load('items');
        $es->indexOrder($order);
    }
}