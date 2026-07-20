-- Init script for the Jmonitor PostgreSQL demo instance.
-- Runs once on first container start (empty data dir).

CREATE EXTENSION IF NOT EXISTS pg_stat_statements;

-- A small e-commerce schema --------------------------------------------------

CREATE TABLE customers (
    id          serial PRIMARY KEY,
    email       text NOT NULL,
    country     text NOT NULL,
    created_at  timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE products (
    id          serial PRIMARY KEY,
    name        text NOT NULL,
    category    text NOT NULL,
    price_cents integer NOT NULL,
    stock       integer NOT NULL DEFAULT 0
);

CREATE TABLE orders (
    id           serial PRIMARY KEY,
    customer_id  integer NOT NULL REFERENCES customers(id),
    status       text NOT NULL DEFAULT 'pending',
    total_cents  integer NOT NULL DEFAULT 0,
    created_at   timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE order_items (
    id          serial PRIMARY KEY,
    order_id    integer NOT NULL REFERENCES orders(id),
    product_id  integer NOT NULL REFERENCES products(id),
    quantity    integer NOT NULL
);

-- Index so some queries use index scans; orders.status is deliberately left
-- unindexed so status filters do sequential scans (visible in index usage ratio).
CREATE INDEX idx_orders_customer ON orders(customer_id);
CREATE INDEX idx_order_items_order ON order_items(order_id);

-- Seed data ------------------------------------------------------------------

INSERT INTO customers (email, country)
SELECT 'user' || g || '@example.com',
       (ARRAY['FR','DE','US','ES','IT'])[1 + (g % 5)]
FROM generate_series(1, 2000) g;

INSERT INTO products (name, category, price_cents, stock)
SELECT 'Product ' || g,
       (ARRAY['books','toys','tools','food','tech'])[1 + (g % 5)],
       500 + (g % 50) * 100,
       (g % 200)
FROM generate_series(1, 500) g;

INSERT INTO orders (customer_id, status, total_cents, created_at)
SELECT 1 + (g % 2000),
       (ARRAY['pending','paid','shipped','cancelled'])[1 + (g % 4)],
       1000 + (g % 100) * 50,
       now() - (g || ' minutes')::interval
FROM generate_series(1, 20000) g;

INSERT INTO order_items (order_id, product_id, quantity)
SELECT 1 + (g % 20000),
       1 + (g % 500),
       1 + (g % 5)
FROM generate_series(1, 60000) g;

-- Create some dead tuples (bloat) so dead_tuple_ratio is non-zero.
UPDATE orders SET status = 'paid' WHERE status = 'pending' AND id % 3 = 0;
DELETE FROM order_items WHERE id % 17 = 0;

ANALYZE;
