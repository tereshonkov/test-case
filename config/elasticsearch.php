<?php

return [
    'enabled' => env('ELASTICSEARCH_ENABLED', false),
    'host'    => env('ELASTICSEARCH_HOST', 'localhost:9200'),
    'timeout' => (int) env('ELASTICSEARCH_TIMEOUT', 2),

    'indices' => [
        'products' => env('ELASTICSEARCH_INDEX_PRODUCTS', 'products_v1'),
        'orders'   => env('ELASTICSEARCH_INDEX_ORDERS', 'orders_v1'),
    ],
];