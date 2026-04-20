<?php

namespace App\Observers;

use App\Models\Order;
use App\Jobs\Order\IndexOrderJob;
use App\Jobs\Order\DeleteOrderJob;

class OrderObserver
{
    public bool $afterCommit = true;

    /**
     * Handle the Order "created and updated" event.
     */
    
    public function saved(Order $order): void
    {
        dispatch(new IndexOrderJob((string) $order->id));
    }
    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        dispatch(new DeleteOrderJob((string) $order->id));
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        dispatch(new IndexOrderJob((string) $order->id));
    }
}