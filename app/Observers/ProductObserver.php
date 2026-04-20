<?php

namespace App\Observers;

use App\Jobs\Product\IndexProductJob;
use App\Jobs\Product\DeleteProductJob;
use App\Models\Product;

class ProductObserver
{
    public bool $afterCommit = true;

    /**
     * Handle the Product "created and updated" event.
     */
    public function saved(Product $product): void
    {
        dispatch(new IndexProductJob((string) $product->id));
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        dispatch(new DeleteProductJob((string) $product->id));
    }
}
