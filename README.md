# Test Case — eCommerce Data Sync

Laravel 11 · PostgreSQL · Redis · RabbitMQ · Elasticsearch 8

---

## Stack

| | |
|---|---|
| PHP | 8.2 |
| Framework | Laravel 11 |
| Database | PostgreSQL 16 |
| Cache | Redis |
| Queue | RabbitMQ 3 |
| Search | Elasticsearch 8.13 |

---

## Запуск

```bash
docker compose up -d --build

docker compose exec test.laravel php artisan migrate
docker compose exec test.laravel php artisan db:seed
```

Локальна розробка:

```bash
composer run dev
# запускає: serve + queue worker + pail
```

---

## Змінні оточення

```dotenv
QUEUE_CONNECTION=rabbitmq
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest

ELASTICSEARCH_ENABLED=true
ELASTICSEARCH_HOST=http://elasticsearch:9200
ELASTICSEARCH_TIMEOUT=2
ELASTICSEARCH_INDEX_PRODUCTS=products_v1
ELASTICSEARCH_INDEX_ORDERS=orders_v1

CACHE_STORE=redis
REDIS_HOST=test-redis

QUEUE_WORKER_SLEEP=3
QUEUE_WORKER_PRODUCTS_TIMEOUT=60
QUEUE_WORKER_ORDERS_TIMEOUT=60
QUEUE_WORKER_REINDEX_TIMEOUT=120
QUEUE_WORKER_MAX_TIME=3600
```

---

## API

```
GET    /api/products
POST   /api/products
GET    /api/products/{id}
PUT    /api/products/{id}
DELETE /api/products/{id}

GET    /api/orders
POST   /api/orders
GET    /api/orders/{id}
PUT    /api/orders/{id}
DELETE /api/orders/{id}
```

Параметри для списків: `?limit=20&page=1&q=пошуковий запит`

---

## Черги

Три domain-черги, кожна зі своїм воркером:

| Черга | Призначення |
|---|---|
| `products` | IndexProductJob, DeleteProductJob |
| `orders` | IndexOrderJob, DeleteOrderJob |
| `reindex` | ReindexJob + bulk-джоби переіндексації |

---

## Команди

```bash
# Повна переіндексація в Elasticsearch
php artisan es:reindex --model=all
php artisan es:reindex --model=products
php artisan es:reindex --model=orders
```
