-- Generates fake workload: reads (indexed + seq scans), writes, aggregations,
-- and a few intentionally heavier queries to populate pg_stat_statements.
-- Run repeatedly: docker exec -i jmonitor-pg-demo psql -U jmonitor -d shop < docker/pg-demo/activity.sql

-- Indexed lookups
SELECT * FROM orders WHERE customer_id = (1 + floor(random() * 2000)::int) LIMIT 20;
SELECT o.*, c.email FROM orders o JOIN customers c ON c.id = o.customer_id
  WHERE c.country = 'FR' LIMIT 50;

-- Sequential scan (orders.status is not indexed)
SELECT status, count(*), sum(total_cents) FROM orders GROUP BY status;

-- Heavier aggregation / join (slow-ish)
SELECT p.category, count(*) AS items, sum(oi.quantity) AS qty
FROM order_items oi
JOIN products p ON p.id = oi.product_id
JOIN orders o ON o.id = oi.order_id
WHERE o.status IN ('paid','shipped')
GROUP BY p.category
ORDER BY qty DESC;

-- A deliberately slow query
SELECT c.country, count(DISTINCT o.id) AS orders, count(oi.id) AS lines
FROM customers c
LEFT JOIN orders o ON o.customer_id = c.id
LEFT JOIN order_items oi ON oi.order_id = o.id
GROUP BY c.country
ORDER BY orders DESC;

-- Writes (tuple insert/update/delete activity)
INSERT INTO orders (customer_id, status, total_cents)
SELECT 1 + floor(random() * 2000)::int, 'pending', 1000 + floor(random() * 5000)::int
FROM generate_series(1, 50);

UPDATE orders SET status = 'shipped'
WHERE id IN (SELECT id FROM orders WHERE status = 'paid' ORDER BY random() LIMIT 30);

DELETE FROM orders WHERE status = 'cancelled' AND id IN
  (SELECT id FROM orders WHERE status = 'cancelled' ORDER BY random() LIMIT 10);

-- A rolled-back transaction (rollback ratio)
BEGIN;
UPDATE products SET stock = stock - 1 WHERE id = 1 + floor(random() * 500)::int;
ROLLBACK;
