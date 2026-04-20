<?php

namespace Database\Seeders;

use App\Enums\ReindexTarget;
use App\Jobs\ReindexJob;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::withoutEvents(
            fn() => Product::factory()->count(10)->create()
        );

        if (config('elasticsearch.enabled')) {
            dispatch(new ReindexJob(ReindexTarget::Products));
        }
    }
}
