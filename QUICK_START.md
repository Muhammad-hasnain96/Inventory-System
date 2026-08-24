# Quick Start Guide

Get the Inventory Management System running in 10 minutes.

## Prerequisites

Before starting, ensure you have:
- **PHP 8.1+**: Check with `php -v`
- **MySQL 8.0+**: Running and accessible
- **Node.js 16+**: Check with `node -v`
- **Composer**: Check with `composer -v`
- **Git** (optional): For cloning

## Step 1: Database Setup (2 min)

### Option A: MySQL Command Line

```bash
# Open MySQL
mysql -u root -p

# Create database and user
CREATE DATABASE inventory_system;
CREATE USER 'inventory_user'@'localhost' IDENTIFIED BY 'password123';
GRANT ALL PRIVILEGES ON inventory_system.* TO 'inventory_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Option B: MySQL GUI (phpMyAdmin, Workbench)
- Create database named: `inventory_system`
- Note connection details

## Step 2: Backend Setup (3 min)

```bash
# Navigate to project directory
cd Inventory-system

# Copy environment file
cp .env.example .env

# Edit .env with your database details
# Update these lines:
# DB_DATABASE=inventory_system
# DB_USERNAME=inventory_user (or root)
# DB_PASSWORD=password123 (or your password)

# Install PHP dependencies
composer install

# Generate application key
php artisan key:generate

# Run migrations (creates all tables)
php artisan migrate --seed

# The --seed flag runs DatabaseSeeder which:
# - Creates roles (super_admin, branch_manager, sales_user)
# - Creates 3 branches (Downtown, Uptown, West Mall)
# - Creates 6 users (with different roles)
# - Creates 8 sample products
# - Creates initial inventory for each product in each branch
```

**You should see:**
```
Migration table created successfully.
Migrating: <timestamps> create_roles_table
Migrating: <timestamps> create_branches_table
...
Database seeded successfully!

Sample Credentials:
  Super Admin: admin@inventory.local / password123
  Branch Manager (Downtown): john@inventory.local / password123
  Sales User (Downtown): alice@inventory.local / password123
```

## Step 3: Start Laravel Backend (1 min)

```bash
# In the project directory, start Laravel development server
php artisan serve

# Output should show:
# INFO  Server running on [http://127.0.0.1:8000].
```

**Keep this terminal open.** The backend runs on `http://localhost:8000`

## Step 4: Frontend Setup (2 min)

Open a **new terminal** in the project directory:

```bash
# Install JavaScript dependencies
npm install

# Start Vite development server
npm run dev

# Output should show something like:
#   VITE v4.4.0  ready in 234 ms
#   ➜  Local:   http://localhost:5173/
#   ➜  press h to show help
```

**Keep this terminal open too.** The frontend runs on `http://localhost:5173`

## Step 5: Open Application (1 min)

Open your browser and go to:
```
http://localhost:5173
```

You'll see the login page. Use any of these credentials:

**Super Admin** (full access)
```
Email: admin@inventory.local
Password: password123
```

**Branch Manager** (manage one branch)
```
Email: john@inventory.local
Password: password123
```

**Sales User** (create orders only)
```
Email: alice@inventory.local
Password: password123
```

## Verification Checklist

After login, verify these features work:

- [ ] **Dashboard**: Sees sales, orders, low stock items
- [ ] **Products**: Can view products and search
- [ ] **Inventory**: Can see branch stock levels
- [ ] **Orders**: Can create orders with multiple items
- [ ] **Low Stock Alert**: Dashboard shows items below threshold

## Test Race Condition Prevention

After setup, test the concurrency handling:

```bash
# 1. Get an inventory record with low stock
# Go to Inventory page
# Find an item with quantity < 5
# Note the product ID

# 2. Create 2-3 orders very quickly
# Try to order more than available
# Expected: First order succeeds, others fail with stock error
# Never: Negative inventory
```

## Troubleshooting

### "Connection refused" on Laravel startup
```
Solution: 
- Ensure MySQL is running
- Check DB credentials in .env
- Try: php artisan migrate (should work without errors)
```

### "npm not found"
```
Solution:
- Install Node.js from nodejs.org
- Restart terminal
- Try: node -v and npm -v
```

### "Column not found" error
```
Solution:
- Run: php artisan migrate --fresh --seed
- This deletes all tables and recreates them
```

### Frontend can't reach backend
```
Solution:
- Check vite.config.js proxy is configured
- Ensure Laravel runs on http://localhost:8000
- Check CORS if different domains
```

### Login fails with valid credentials
```
Solution:
- Clear browser cache (Ctrl+Shift+Delete)
- Check database seeded: php artisan tinker -> User::count()
- Try: php artisan db:seed --class=DatabaseSeeder
```

## What's Running

After step-by-step completion, you should have:

**Terminal 1** (Laravel Backend)
```
Running on http://localhost:8000
✓ API endpoints available
✓ Database connected
```

**Terminal 2** (Vue Frontend)  
```
Running on http://localhost:5173
✓ Vue application loaded
✓ API communication working
```

**Database**
```
Database name: inventory_system
Tables created: 8
Sample data populated: Yes
Ready for use
```

## Next Steps

### Explore Features
1. Create a product
2. Add stock to a branch
3. Create a multi-item order
4. Check dashboard metrics
5. View stock movement history

### Test Concurrency (Optional)
See the README.md file for load testing instructions.

### Customize
- Edit products in `database/seeders/DatabaseSeeder.php`
- Add new branches
- Create additional users

### Deploy to Production
See "Deployment" section in README.md

---

**Everything set up? Happy inventory managing! 📦**

For detailed documentation, see: **README.md**
