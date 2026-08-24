# Multi-Branch Smart Inventory & Order Management System

A production-grade inventory and order management system built with Laravel, MySQL, and Vue 3, designed for multi-branch retail operations with robust concurrency handling and RBAC.

## 📋 Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Tech Stack](#tech-stack)
- [Database Schema](#database-schema)
- [Concurrency Handling (CRITICAL)](#concurrency-handling-critical)
- [Installation & Setup](#installation--setup)
- [API Documentation](#api-documentation)
- [Features](#features)
- [Security](#security)
- [Testing](#testing)

---

## 🎯 Overview

This system provides complete inventory management across multiple branches with:
- **Real-time stock tracking** with per-branch inventory
- **Race condition prevention** using row-level database locking
- **Role-Based Access Control** (Super Admin, Branch Manager, Sales User)
- **Order processing** with atomic transactions
- **Cross-branch order placement** - users can place orders from any branch
- **Stock movement history** for complete audit trails
- **Dashboard reporting** with key metrics and low stock alerts

### Key Design Principles
- **Clean Architecture**: Service layer pattern with thin controllers
- **SOLID Principles**: Single responsibility, dependency injection
- **Database Integrity**: Transactions, foreign keys, constraints
- **Concurrency Safety**: Row-level locking, atomic operations
- **Audit Trail**: Complete history of all inventory movements

---

## 🏗️ Architecture

### Folder Structure

```
/app
  ├── /Models              # Eloquent models with relationships
  ├── /Services           # Business logic (ProductService, InventoryService, OrderService)
  ├── /Http
  │   ├── /Controllers/Api   # Thin REST API controllers
  │   ├── /Requests         # Form request validation
  │   └── /Middleware       # Authentication & role checking
  ├── /Policies           # Authorization policies
  └── /Events             # Event listeners (optional)

/database
  ├── /migrations         # Database schema
  └── /seeders           # Sample data

/routes
  └── /api.php           # API endpoints

/resources/js
  ├── /pages             # Vue page components
  ├── /components        # Reusable Vue components
  ├── /services          # API client service
  ├── /router            # Vue router configuration
  └── main.js            # Vue app entry point
```

### Layer Responsibilities

**Controllers** (Thin)
- Request validation delegation to Form Requests
- Authorization checks via policies
- Service method calls
- Response formatting

**Services** (Business Logic)
- All business logic lives here
- Database transactions management
- Concurrency-safe operations
- Complex workflows

**Models** (Data Layer)
- Eloquent relationships
- Database interactions
- Simple queries
- NO business logic

**Form Requests** (Validation)
- Input validation rules
- Authorization checks
- Normalization

### Example Flow

```
Vue Component
    ↓
POST /api/orders (Axios)
    ↓
OrderController (receives request)
    ↓
CreateOrderRequest (validates)
    ↓
authorize() (checks policy)
    ↓
OrderService::createOrder() (CRITICAL CONCURRENCY LOGIC)
    ↓
Database Transaction with Row-Level Locking
    ↓
Response JSON back to Vue
    ↓
Vue Update & Display
```

---

## 💾 Database Schema

### Tables Overview

#### users
- `id`, `name`, `email`, `password`
- `role_id` (FK to roles)
- `branch_id` (FK to branches) - each user assigned to one branch
- `status` (active/inactive)

#### roles
- `id`, `name` (super_admin, branch_manager, sales_user)
- `display_name`, `description`

#### branches
- `id`, `name`, `code` (DT, UP, WM etc - unique)
- `address`, `phone`, `email`, `status`

#### products
- `id`, `name`, `sku` (unique - CRITICAL)
- `cost_price`, `sale_price`, `tax_percentage`
- `description`, `status`
- `deleted_at` (soft deletes)

#### inventories ⭐
- `id`, `product_id` (FK), `branch_id` (FK)
- `quantity` (current stock - NEVER goes negative)
- `low_stock_threshold`
- **UNIQUE constraint**: (product_id, branch_id) - one record per product per branch

#### orders
- `id`, `order_number` (unique)
- `branch_id`, `created_by` (FK to users)
- `subtotal`, `tax_amount`, `total_amount`
- `status` (pending, confirmed, completed, cancelled)

#### order_items
- `id`, `order_id` (FK), `product_id` (FK)
- `quantity`, `unit_price`, `tax_percentage`, `line_total`

#### stock_movements ⭐
- `id`, `inventory_id` (FK)
- `type` (add, adjust, transfer_out, transfer_in, order_deduction)
- `quantity`, `quantity_before`, `quantity_after`
- Complete audit trail of every stock change

#### stock_transfers
- `id`, `product_id`, `from_branch_id`, `to_branch_id`
- `quantity`, `status` (pending, completed, cancelled)

### Indexes
- All foreign keys are indexed
- `products.sku` is indexed
- `inventories.(product_id, branch_id)` is a unique key
- `orders.order_number` is indexed
- `stock_movements.inventory_id`, `type`, `created_at` are indexed

### Constraints
- Foreign keys with ON DELETE RESTRICT (prevent deletion of referenced data)
- Unique constraints on SKU and order numbers
- NOT NULL constraints on critical fields

---

## 🔒 Concurrency Handling (CRITICAL)

This is the most important aspect of the system. Race conditions can cause inventory overselling.

### The Problem

Imagine two requests trying to sell the last laptop:

```
Time  Request 1                    Request 2
----  -------------------------    -------------------------
T1    Check: stock = 1
T2    Check: stock = 1
T3    Deduct stock 1                
T4                                  Deduct stock 1
      ❌ Result: stock = -1 (OVERSELLING!)
```

### Our Solution: Row-Level Locking

We use **SELECT FOR UPDATE** (row-level database locking) to prevent concurrent access.

```php
// OrderService::createOrder()
return DB::transaction(function () use ($branch, $user, $items) {
    // ... validation ...
    
    // CRITICAL: Lock inventory rows by product_id
    // This prevents other requests from accessing these rows until transaction commits
    $inventories = DB::table('inventories')
        ->whereIn('product_id', $productIds)
        ->where('branch_id', $branch->id)
        ->lockForUpdate()  // ← CRITICAL LINE
        ->get();

    // After acquiring lock, verify stock AGAIN (double-check)
    foreach ($validatedItems as $item) {
        $inventory = $inventoryMap->get($item['product']->id);
        if ($inventory->quantity < $item['quantity']) {
            throw new \Exception("Insufficient stock");
        }
    }

    // Now deduct stock safely - no other request can interfere
    foreach ($validatedItems as $item) {
        $this->inventoryService->deductStockForOrder(...);
    }

    return $order->refresh();
}, attempts: 3); // Retry on deadlock
```

### Timeline with Locking

```
Time  Request 1                           Request 2
----  -----------------------------------  -----------------------------------
T1    BEGIN TRANSACTION
T2    LOCK inventory rows                 BEGIN TRANSACTION
T3    Verify stock = 1 ✓                  WAIT for lock... (blocked)
T4    Deduct stock = 0
T5    CREATE order
T6    CREATE order_items
T7    COMMIT (releases lock)
T8                                        Gets lock, verifies stock = 0
T9                                        Throws: "Insufficient stock"
      ✓ Result: stock = 0 (correct!)
```

### Key Points

1. **Database Transaction**: Everything is atomic - all or nothing
2. **Row-Level Lock** (`lockForUpdate()`): Prevents concurrent reads/writes
3. **Double-Check Pattern**: Verify stock after acquiring lock
4. **Atomic Stock Deduction**: Done within locked transaction
5. **Ordered Locking**: Lock products in consistent order (by ID) to prevent deadlocks
6. **Retry Logic**: Automatic retry on deadlock (up to 3 attempts)

### Race Conditions Prevented

✓ **Overselling**: Impossible - stock verified under lock
✓ **Negative Inventory**: Impossible - decrementStock throws exception
✓ **Lost Updates**: Impossible - each operation is atomic
✓ **Dirty Reads**: Impossible - locked rows not readable by others
✓ **Phantom Reads**: Impossible - lock prevents new rows

### Performance Considerations

- Lock is held for milliseconds while processing order
- Most requests don't conflict (different products/branches)
- Lock escalation to table level only if conflicts are high
- Connection pool ensures enough database connections

### Testing Concurrency

```bash
# Simulate 10 concurrent requests buying the last item
ab -n 10 -c 10 -T application/json \
  -H "Authorization: Bearer TOKEN" \
  -d '{"items":[{"product_id":1,"quantity":1}]}' \
  http://localhost:8000/api/orders

# Result should show:
# - 1 successful order
# - 9 failed orders with "Insufficient stock" error
# - Final inventory = 0 (not negative!)
```

---

## 🛠️ Installation & Setup

### Prerequisites
- PHP 8.1+
- MySQL 8.0+
- Node.js 16+
- Composer
- Git

### Backend Setup

```bash
# 1. Clone and setup
cd Inventory-system
cp .env.example .env
composer install

# 2. Configure database in .env
DB_DATABASE=inventory_system
DB_USERNAME=root
DB_PASSWORD=yourpassword

# 3. Generate app key
php artisan key:generate

# 4. Run migrations (creates schema)
php artisan migrate

# 5. Seed sample data
php artisan db:seed

# 6. Start Laravel development server
php artisan serve
# Runs on http://localhost:8000
```

### Frontend Setup

```bash
# 1. Install dependencies
npm install

# 2. Build assets (development)
npm run dev

# OR for production
npm run build

# 3. The Vue app runs on http://localhost:5173 (or via Laravel)
```

### Database Setup (Alternative MySQL)

```bash
# If using MySQL GUI:
1. Create database: CREATE DATABASE inventory_system;
2. Use the created database
3. Run: php artisan migrate --seed
```

### Verify Installation

```bash
# 1. Backend runs
curl http://localhost:8000/api/login

# 2. Database connected
php artisan tinker
>>> User::count()
# Should show: 8 (from seed)

# 3. API working
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@inventory.local","password":"password123"}'
# Should return token
```

---

## 👥 Roles & Permissions

### Super Admin
- ✅ View all data across all branches
- ✅ Create/edit/delete products
- ✅ Delete any order
- ✅ Manage all branches
- ✅ View all reports

### Branch Manager
- ✅ Manage own branch only
- ✅ Add/adjust/transfer stock
- ✅ Create & cancel orders
- ✅ View branch reports
- ❌ Create products
- ❌ Delete products
- ❌ View other branches

### Sales User
- ✅ Create orders only
- ✅ View products
- ✅ View own branch inventory
- ❌ Modify stock
- ❌ Delete orders
- ❌ Transfer between branches

### Authorization Implementation

```php
// Policies (app/Policies/)
- OrderPolicy: controls who can view/create/cancel orders
- InventoryPolicy: controls who can add/adjust/transfer stock
- ProductPolicy: controls who can create/edit/delete products

// Middleware (app/Http/Middleware/)
- EnsureSuperAdmin: /super-admin/* routes
- EnsureBranchManager: /manager/* routes

// Usage in Controllers
$this->authorize('create', Order::class);
$this->authorize('addStock', $inventory);
```

---

## 📡 API Documentation

### Authentication

```
POST /api/login
{
  "email": "john@inventory.local",
  "password": "password123"
}

Response:
{
  "success": true,
  "data": {
    "user": { "id": 1, "name": "John Manager", "role": {...}, "branch": {...} },
    "token": "eyJ0eXAi..."
  }
}

# Use token in Authorization header
Authorization: Bearer eyJ0eXAi...
```

### Products

```
GET    /api/products              # List all
GET    /api/products?q=laptop     # Search
GET    /api/products/{id}         # Single product
POST   /api/products              # Create (requires branch_manager+)
PUT    /api/products/{id}         # Update
DELETE /api/products/{id}         # Delete (requires super_admin)
PATCH  /api/products/{id}/status  # Toggle active/inactive
GET    /api/products/low-stock    # Low stock items
```

### Inventory

```
GET    /api/inventory                              # Branch inventory
GET    /api/inventory/low-stock                    # Low stock alerts
POST   /api/inventory/products/{id}/add-stock      # Add stock
POST   /api/inventory/products/{id}/adjust-stock   # Adjust stock
POST   /api/inventory/products/{id}/transfer       # Transfer between branches
GET    /api/inventory/products/{id}/history        # Stock movement history
```

### Orders (CRITICAL FOR CONCURRENCY)

```
GET    /api/orders              # List orders
GET    /api/orders/{id}         # Single order details
POST   /api/orders              # Create order (concurrency-safe!)
POST   /api/orders/{id}/cancel  # Cancel order
```

**Create Order Example:**
```json
POST /api/orders
{
  "branch_id": 1,
  "items": [
    {"product_id": 1, "quantity": 2},
    {"product_id": 3, "quantity": 1}
  ],
  "notes": "VIP customer order"
}

Response:
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "id": 5,
    "order_number": "DT-20240315-00005",
    "branch_id": 1,
    "subtotal": 1399.98,
    "tax_amount": 139.99,
    "total_amount": 1539.97,
    "status": "confirmed",
    "items": [...]
  }
}
```

### Dashboard

```
GET /api/dashboard                    # All metrics
GET /api/dashboard/sales-report       # Sales data
GET /api/dashboard/inventory-report   # Inventory data
```

---

## ✨ Features

### Product Management
- ✓ CRUD operations with SKU uniqueness validation
- ✓ Soft deletes (can restore)
- ✓ Search by name or SKU
- ✓ Status management (active/inactive)
- ✓ Tax percentage per product

### Inventory Management
- ✓ Per-branch stock tracking
- ✓ Add/adjust stock with audit trail
- ✓ Stock transfers between branches
- ✓ Low stock alerts
- ✓ Complete movement history
- ✓ Never allows negative stock

### Order Processing
- ✓ Multi-item orders
- ✓ Auto-calculation (subtotal, tax, total)
- ✓ **Cross-branch order placement** - select any branch for order fulfillment
- ✓ **Race condition prevention** (row-level locking)
- ✓ Atomic transactions
- ✓ Order cancellation with stock restoration
- ✓ Unique order numbers per branch per day

### Reporting
- ✓ Today's sales
- ✓ Monthly sales breakdown
- ✓ Total orders count
- ✓ Top 5 products by sales
- ✓ Low stock alerts

### Security
- ✓ Sanctum API authentication
- ✓ Role-based access control
- ✓ Form request validation
- ✓ Policy-based authorization
- ✓ Soft deletes (restore capability)
- ✓ Audit trail via stock_movements

---

## 🔐 Security

### Input Validation
- All inputs validated via Form Requests
- Type coercion (numeric values, etc.)
- Max/min constraints
- Mandatory field validation

### Authorization
- Policies check user permissions before actions
- Middleware ensures user is authenticated
- Super Admin can bypass some restrictions

### Database Security
- SQL injection prevention (Eloquent ORM)
- Prepared statements
- Foreign key constraints
- Unique constraints

### API Security
- Bearer token authentication (Sanctum)
- CORS if needed
- Rate limiting (can be added)
- Proper HTTP status codes

### Audit Trail
- Every stock movement logged with:
  - Quantity before/after
  - Type of movement
  - User who made the change
  - Timestamp

---

## 📊 Sample Data

**Demo Users** (from seeder):
```
Super Admin:
  Email: admin@inventory.local
  Password: password123

Branch Managers:
  Downtown: john@inventory.local / password123
  East Side: manager@eastside.local / password123
  North Mall: manager@northmall.local / password123

Sales User:
  Email: alice@inventory.local
  Password: password123
```

**Demo Branches:**
- Downtown (DT)
- Uptown (UP)
- West Mall (WM)
- East Side (ES)
- North Mall (NM)

Sales User (Downtown):
  Email: alice@inventory.local
  Password: password123
```

**Sample Products:**
- Laptop Pro 15 (SKU: LP-PRO-15)
- Wireless Mouse (SKU: MOUSE-WL)
- USB-C Cable (SKU: CABLE-USB-C)
- Monitor 4K 27" (SKU: MON-4K-27)
- Mechanical Keyboard (SKU: KB-MECH)

**Sample Branches:**
- Downtown (DT)
- Uptown (UP)
- West Mall (WM)

---

## 🧪 Testing Concurrency

### Load Test: 100 Concurrent Orders

```bash
# Use Apache Bench to simulate 100 concurrent requests
ab -n 100 -c 100 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -p order.json \
  http://localhost:8000/api/orders

# order.json:
{
  "items": [{"product_id": 1, "quantity": 1}],
  "notes": "Test order"
}

# Expected: Some succeed, some fail with "Insufficient stock"
# Never: Negative inventory
```

### Manual Race Condition Test

```php
// Laravel Tinker
php artisan tinker

// Get a product with low stock
$inventory = Inventory::find(1);
$inventory->quantity = 1;
$inventory->save();

// Simulate two users trying to buy it
// Request 1: Create order with 1 qty
// Request 2: Create order with 1 qty (simultaneously)

// Result: One succeeds, one gets "Insufficient stock" error
```

---

## 📈 Performance Considerations

- Database connections pooled
- Indexes on frequently queried columns
- Pagination for large result sets (default 15 items)
- Transactions kept short
- No N+1 queries (use `with()` for eager loading)

---

## 🚀 Deployment

### Production Checklist

```bash
# 1. Environment
APP_ENV=production
APP_DEBUG=false
APP_KEY=<generated key>

# 2. Database
DB_HOST=prod-database.com
DB_USERNAME=prod_user
DB_PASSWORD=strong_password

# 3. Cache
CACHE_DRIVER=redis
SESSION_DRIVER=redis

# 4. Migrations
php artisan migrate --force

# 5. Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Seed if needed
php artisan db:seed --class=DatabaseSeeder

# 7. Build Vue assets
npm run build

# 8. Set proper permissions
chmod -R 755 storage bootstrap/cache

# 9. Use production web server (Nginx/Apache)
# 10. Enable HTTPS
# 11. Set up monitoring/logging
```

---

## 📞 Support & Troubleshooting

### Common Issues

**1. Token not persisting**
```
Solution: Ensure localStorage is enabled
Check browser DevTools > Application > Local Storage
```

**2. CORS errors**
```
Solution: Configure CORS in config/cors.php
Allow your frontend domain
```

**3. Database connection failed**
```
Solution: 
- Verify DB credentials in .env
- Ensure MySQL is running
- Run: php artisan migrate
```

**4. Orders processing slowly**
```
Solution:
- Check database connections limit
- Monitor lock contention
- Scale database connections
```

---

## 📝 License

This project is provided as-is for educational and production purposes.

---

## 🎓 Learning Resources

- **Clean Architecture**: Check Service classes structure
- **Concurrency**: See OrderService::createOrder() for row-level locking
- **RBAC**: Check Policies and Middleware
- **Vue 3 Composition API**: See page components
- **Laravel Best Practices**: Check Model relationships and validation

---

**Built with ❤️ for production-grade inventory management**
