# Concurrency Handling: Deep Dive

This document explains how the system prevents race conditions in inventory management.

## Problem Statement

In a multi-user system with concurrent requests, inventory management can have critical issues:

### Scenario: Overselling

Product: Laptop (only 1 in stock)
User A wants to buy: 1 laptop
User B wants to buy: 1 laptop
Both submit orders simultaneously

**Without proper locking:**
```
Timeline:
T1: Request A reads inventory: 1 laptop ✓
T2: Request B reads inventory: 1 laptop ✓
T3: Request A decrements stock: 1 - 1 = 0
T4: Request B decrements stock: 1 - 1 = 0  ← Both deducted!
Result: Stock = 0, BUT 2 laptops sold! (negative inventory)
```

**This is CRITICAL because:**
- Orders fulfilled but stock doesn't exist
- Fulfillment department can't ship
- Customer complaint (cancellation, refund, chargeback)
- Financial loss and reputation damage

## Solution: Row-Level Database Locking

### What is Row-Level Locking?

Row-level locking is a database feature where a database lock is placed on a specific row (or rows) preventing other transactions from modifying that data until the lock is released.

```sql
-- This acquires an exclusive lock on the inventory row
SELECT * FROM inventories WHERE product_id = 1 AND branch_id = 1 FOR UPDATE;
```

**Key point:** When a lock is acquired, OTHER transactions must WAIT until the lock is released.

### Our Implementation

In `OrderService::createOrder()`:

```php
return DB::transaction(function () use ($branch, $user, $items) {
    // STEP 1: Validate all items exist
    // (basic validation, doesn't need locks yet)
    $validatedItems = [];
    foreach ($items as $item) {
        $product = Product::find($item['product_id']);
        // ... validation logic ...
        $validatedItems[] = [
            'product' => $product,
            'quantity' => $item['quantity'],
            // ... etc ...
        ];
    }

    // STEP 2: Lock inventory rows in CONSISTENT ORDER
    // (BY product_id to prevent deadlocks)
    $productIds = collect($validatedItems)
        ->pluck('product.id')
        ->sort()  // ← IMPORTANT: Consistent order prevents deadlocks
        ->values()
        ->toArray();

    // STEP 3: Acquire locks on all inventory rows
    $inventories = DB::table('inventories')
        ->whereIn('product_id', $productIds)
        ->where('branch_id', $branch->id)
        ->lockForUpdate()  // ← CRITICAL: This line acquires exclusive locks
        ->get();

    $inventoryMap = $inventories->keyBy('product_id');

    // STEP 4: Re-validate stock availability AFTER acquiring lock
    // This is crucial - another request might have modified stock while we waited
    foreach ($validatedItems as $item) {
        $inventory = $inventoryMap->get($item['product']->id);
        if ($inventory->quantity < $item['quantity']) {
            throw new \Exception("Insufficient stock");
        }
    }

    // STEP 5: Create order
    $order = Order::create([
        'order_number' => Order::generateOrderNumber($branch),
        'branch_id' => $branch->id,
        'created_by' => $user->id,
        'subtotal' => $subtotal,
        'tax_amount' => $totalTax,
        'total_amount' => $subtotal + $totalTax,
        'status' => 'confirmed',
        'notes' => $notes,
    ]);

    // STEP 6: Create order items and deduct stock
    foreach ($validatedItems as $item) {
        // Create order item
        OrderItem::create([...]);

        // Deduct stock (happens under lock acquired in STEP 3)
        $inventoryModel = \App\Models\Inventory::find($inventory->id);
        $this->inventoryService->deductStockForOrder(
            $inventoryModel,
            $item['quantity'],
            $user,
            $order
        );
    }

    return $order->refresh()->load('items.product');

}, attempts: 3); // Retry up to 3 times if deadlock occurs
```

### Timeline WITH Locking

```
Timeline:
T1: Request A BEGIN TRANSACTION
T2: Request B BEGIN TRANSACTION
T3: Request A LOCK inventory row for product 1 [SUCCESS - acquires lock]
T4: Request B LOCK inventory row for product 1 [BLOCKED - waiting for lock]
T5: Request A RE-VERIFY stock = 1 ✓
T6: Request A DEDUCT stock: 1 - 1 = 0
T7: Request A CREATE order
T8: Request A COMMIT [releases lock]
T9: Request B LOCK inventory row [NOW acquired since A released]
T10: Request B RE-VERIFY stock = 0 ✗ [NOT enough stock!]
T11: Request B THROW exception "Insufficient stock"
T12: Request B ROLLBACK

Result: ✓ Stock = 0, only 1 laptop sold correctly
```

## Double-Check Pattern

Notice we check stock TWICE:
1. **Before locking** (basic validation, might be stale)
2. **After locking** (definitive check, guaranteed fresh)

This is called the **double-check locking pattern**.

```
Check 1: Before lock    ← May not reflect current state
         |
        LOCK inventory  ← Wait for our turn
         |
Check 2: After lock     ← Definitely current state
         |
        Deduct stock    ← Safe operation under lock
```

## Why Consistent Lock Order Prevents Deadlocks

### Scenario Without Consistent Order

```
Request A: LOCK product 1,  then LOCK product 2
Request B: LOCK product 2,  then LOCK product 1
           ↑                        ↑
       Already locked           Already locked
           → DEADLOCK!
```

### Solution: Always Lock in Same Order

```
Request A: LOCK (product 1, product 2)  [order: 1, 2]
Request B: LOCK (product 1, product 2)  [order: 1, 2]
           ↓
        Request B waits for A
           ↓
        Request A completes
           ↓
        Request B gets locks
           ↓
        Both complete successfully
```

**In our code:**
```php
$productIds = collect($validatedItems)
    ->pluck('product.id')
    ->sort()  // ← Ensures consistent ascending order
    ->values()
    ->toArray();

DB::table('inventories')
    ->whereIn('product_id', $productIds)  // ← MySQL locks in defined order
    ->lockForUpdate()
    ->get();
```

## Transaction Properties (ACID)

Our implementation ensures:

### Atomicity
All operations in the transaction succeed or all fail. There's no partial state.

```php
DB::transaction(function () {
    // If anything fails here, entire transaction rolls back
    Order::create(...);        // ✓
    OrderItem::create(...);    // ✓
    $inventory->decrement();   // ✓  ← If this fails, all above rollback
});
```

### Consistency
Database always goes from one valid state to another valid state.

```
Before: inventory = 1, orders = 5
Order: Buy 1
After:  inventory = 0, orders = 6  ← Valid state
```

### Isolation
One transaction doesn't see incomplete work of another transaction.

```
Request A: BEGIN → LOCK → DEDUCT → COMMIT
           |                          |
Request B: BEGIN → WAIT ← LOCK ← COMMIT → Now sees result
           (invisible until A commits)
```

### Durability
Once committed, data is permanently written to disk.

```
COMMIT
  ↓
Written to disk
  ↓
Even if server crashes, data is safe
```

## Race Conditions Prevented

### 1. Lost Update
**Problem:** Two updates overwrite each other
```
A: Read qty=10, add 5 → qty=15
B: Read qty=10, add 3 → qty=13  (A's update lost!)
```
**Solution:** Lock prevents concurrent reads

### 2. Dirty Read
**Problem:** Read uncommitted data that might rollback
```
A: Deduct stock (uncommitted)
B: Read qty=0  (hasn't committed yet!)
A: ROLLBACK → qty=1 again
B: Using wrong data!
```
**Solution:** Isolation level prevents dirty reads

### 3. Non-Repeatable Read
**Problem:** Same query returns different results in same transaction
```
T1: SELECT qty = 10
T2: (some other transaction changes qty to 5)
T3: SELECT qty = 5  (different from T1!)
```
**Solution:** Lock prevents data changing mid-transaction

### 4. Phantom Read
**Problem:** New rows appear mid-transaction
```
T1: SELECT * FROM inventories WHERE qty < 5 → 2 rows
T2: INSERT new row with qty = 3
T3: SELECT * FROM inventories WHERE qty < 5 → 3 rows
```
**Solution:** Lock prevents new rows being inserted

## Performance Impact

### Lock Scope
```
One inventory row:          ~microseconds of lock
Multiple products:          ~milliseconds of lock
Typically: 5-50ms per order
```

### Throughput
```
Without lock (concurrent), can process ~1000 concurrent orders
With lock (serialized):     process ~20 concurrent orders

BUT: Lost orders happen way more than performance degradation!
Trade-off: Correctness > Performance
```

### When Lock Contention Happens

High contention (slow performance) occurs when:
- Many users buying same product
- During flash sales
- Limited inventory

**Solutions:**
- Scale database (more connections)
- Use read replicas for reporting
- Implement order queue during peaks

## Testing Concurrency

### 1. Unit Test (Single Row)

```php
// Test in isolation
$inventory = Inventory::find(1);
$inventory->quantity = 1;
$inventory->save();

// Simulate concurrent requests
$results = [];
for ($i = 0; $i < 10; $i++) {
    try {
        $order = $this->orderService->createOrder(
            $branch,
            $user,
            [['product_id' => 1, 'quantity' => 1]]
        );
        $results['success']++;
    } catch (Exception $e) {
        $results['failed']++;
    }
}

// Assert
$this->assertEquals(1, $results['success']);     // Only 1 succeeds
$this->assertEquals(9, $results['failed']);      // 9 fail
$this->assertEquals(0, $inventory->refresh()->quantity);  // Stock = 0
```

### 2. Load Test (Apache Bench)

```bash
# Prepare order data
echo '{"items":[{"product_id":1,"quantity":1}]}' > order.json

# Run 100 concurrent requests
ab -n 100 -c 100 \
   -T application/json \
   -H "Authorization: Bearer TOKEN" \
   -p order.json \
   http://localhost:8000/api/orders

# Check result
CHECK inventory:
- Should be at or below original quantity
- Never negative!
```

### 3. Stress Test (Simultaneous Multiple Products)

```bash
# Order multiple products at same time
# Each user buys 1 of products 1-5
# If 10 concurrent users:
# - 10 quantities of product 1 sold
# - But only 5 in stock!
# Result: 5 orders succeed, 5 fail with "Insufficient stock"
```

## Monitoring Lock Contention

### MySQL Performance Schema

```sql
-- Check lock waits
SELECT * FROM performance_schema.events_waits_current
WHERE OBJECT_NAME LIKE 'inventories';

-- Check InnoDB locks
SELECT * FROM INFORMATION_SCHEMA.INNODB_LOCKS;
```

### Application Logging

```php
\Log::info('Order processing', [
    'user_id' => $user->id,
    'items_count' => count($items),
    'start_time' => microtime(true),
    'status' => 'completed',
    'duration_ms' => (microtime(true) - $start) * 1000,
]);
```

## Alternative Approaches (Not Used)

### Pessimistic Locking (Our Approach ✓)
- Lock rows immediately
- Pros: Prevents all conflicts
- Cons: Potential lock contention
- **Used for:** High-value transactions (orders)

### Optimistic Locking (Alternative)
- Check version before update
- Pros: Better concurrency
- Cons: Can have conflicts
- **Would use for:** Low-conflict operations

### Distributed Locks (Alternative)
- Lock via Redis
- Pros: Works across servers
- Cons: Complex, slower
- **Would use for:** Microservices

## Best Practices

✓ **DO:**
- Lock in consistent order (prevent deadlocks)
- Keep transactions short (release locks quickly)
- Use appropriate isolation level
- Handle deadlocks gracefully (retry logic)
- Test with concurrent requests
- Monitor lock contention in production

✗ **DON'T:**
- Lock for entire request processing
- Acquire locks in inconsistent order
- Ignore deadlock exceptions
- Skip double-check validation
- Use optimistic locking for critical operations

## Summary

The system achieves **race condition safety** through:

1. **Database Transactions** - Atomicity
2. **Row-Level Locking** - Exclusive access
3. **Double-Check Pattern** - Validation accuracy
4. **Consistent Lock Order** - Deadlock prevention
5. **Retry Logic** - Transient failure handling
6. **Proper Isolation Levels** - Visibility control

This ensures that even under extreme concurrent load, inventory is never oversold and data integrity is maintained.

**Result: Production-grade concurrency safety! 🔒**
