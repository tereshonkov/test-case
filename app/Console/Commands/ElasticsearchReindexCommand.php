<?php

namespace App\Console\Commands;

use App\Enums\ReindexTarget;
use App\Jobs\ReindexJob;
use Illuminate\Console\Command;

class ElasticsearchReindexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:reindex {--model=all : products|orders|all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reindex data into Elasticsearch';

    /**
     * Execute the console command.
     */
    public function handle(): void  
    {
        $model = $this->option('model');

        if (in_array($model, ['all', 'products'])) {
            $this->info('Queued: products reindex');
            dispatch(new ReindexJob(ReindexTarget::Products));
        }
        if (in_array($model, ['all', 'orders'])) {
            $this->info('Queued: orders reindex');
            dispatch(new ReindexJob(ReindexTarget::Orders));
        }
        $this->info('Done.');
    }
}
