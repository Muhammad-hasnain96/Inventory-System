#!/bin/bash
for i in {1..30}; do
  mysql -uroot -psecret inventory_system << EOF
INSERT INTO orders (order_number, created_by, branch_id, status, subtotal, tax_amount, total_amount, notes, created_at, updated_at) 
VALUES ('ORD-202604$(printf %02d $i)-$(printf %04d $i)', 1, $((1 + RANDOM % 3)), 'confirmed', $((1000 + RANDOM % 5000)), $((100 + RANDOM % 500)), $((1100 + RANDOM % 5500)), 'Sample order', NOW() - INTERVAL $i DAY, NOW());
EOF
done
echo "✓ Inserted 30 sample orders"
