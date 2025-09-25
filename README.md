# 🛒 E-commerce Order Management API

A modern Laravel 12 backend API with **standardized responses**, **comprehensive testing**, and **role-based authentication**.

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.4+-blue.svg)](https://php.net)
[![Tests](https://img.shields.io/badge/Tests-42%20passing-green.svg)](#testing)
[![Coverage](https://img.shields.io/badge/Coverage-97%25-brightgreen.svg)](#testing)

## ✨ Features

- 🔐 **Sanctum Authentication** with role-based access control
- 🛍️ **Complete E-commerce Flow** (Products → Cart → Orders → Payments)
- 📊 **Standardized API Responses** with consistent JSON structure
- 🧪 **Comprehensive Testing** (42 tests, 97%+ coverage)
- ⚡ **Performance Optimized** with caching and queues
- 🎯 **Laravel 12 & PHP 8.4** features throughout

## 🚀 Quick Start

```bash
# Clone and setup
git clone <repository-url>
cd ecommerceOrderManagmentSystem

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Database setup (SQLite)
touch database/database.sqlite
php artisan migrate --seed

# Start development server
composer run dev
```

**API Base URL:** `http://localhost:8000/api`

## 📋 API Overview

### 🔑 Authentication
```bash
POST /register    # Register user
POST /login       # Login user  
POST /logout      # Logout (protected)
GET  /me          # Get profile (protected)
```

### 🏷️ Categories & Products
```bash
GET    /categories           # List categories
POST   /categories           # Create (admin)
PUT    /categories/{id}      # Update (admin)
DELETE /categories/{id}      # Delete (admin)

GET    /products             # List products (with filters)
POST   /products             # Create (admin)
PUT    /products/{id}        # Update (admin)
DELETE /products/{id}        # Delete (admin)
```

### 🛒 Cart & Orders
```bash
GET    /cart                 # Get cart (customer)
POST   /cart                 # Add item (customer)
PUT    /cart/{id}            # Update item (customer)
DELETE /cart/{id}            # Remove item (customer)

GET    /orders               # List orders (protected)
POST   /orders               # Create order (protected)
PUT    /orders/{id}          # Update status (admin)
```

### 💳 Payments
```bash
GET    /payments             # List payments (protected)
POST   /orders/{id}/payment  # Process payment (protected)
```

## 📝 Response Format

All responses follow a consistent structure:

### ✅ Success Response
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": { ... },
  "meta": {
    "timestamp": "2025-09-25T13:20:08.819360Z",
    "version": "1.0"
  }
}
```

### ❌ Error Response
```json
{
  "success": false,
  "message": "Error description",
  "errors": { ... },
  "meta": {
    "timestamp": "2025-09-25T13:20:08.819360Z",
    "version": "1.0"
  }
}
```

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test suite
php artisan test --filter AuthTest
```

**Test Coverage:**
- ✅ **42 Tests Passing**
- ✅ **Feature Tests**: Auth, Products, Cart, Orders
- ✅ **Unit Tests**: OrderService business logic
- ✅ **Standardized Response Tests**: All error scenarios

## 👥 User Roles

| Role | Permissions |
|------|-------------|
| **Admin** | Manage products/categories, Update order status |
| **Customer** | Browse products, Manage cart, Place orders |

## 🔧 Development

```bash
# Start development server
composer run dev

# Code formatting
./vendor/bin/pint
npm run format

# Database operations
php artisan migrate
php artisan db:seed
php artisan db:wipe

# Queue processing
php artisan queue:work
```

## 📦 Postman Collection

Import `postman_collection.json` for complete API testing with:
- ✅ **All endpoints** with proper headers
- ✅ **Dynamic variables** for IDs and tokens
- ✅ **Sample requests** with realistic data
- ✅ **Environment setup** instructions

## 🏗️ Architecture

```
app/
├── Http/Controllers/Api/    # API controllers
├── Http/Resources/          # Response transformers
├── Http/Requests/           # Validation classes
├── Http/Middleware/         # Custom middleware
├── Models/                  # Eloquent models
├── Services/                # Business logic
└── Traits/                  # Reusable code

tests/
├── Feature/                 # API integration tests
└── Unit/                    # Unit tests
```

## 🔒 Security Features

- **Sanctum Authentication** with token-based API access
- **Role-based Middleware** for admin/customer separation
- **Form Request Validation** with comprehensive rules
- **SQL Injection Prevention** via Eloquent ORM
- **Input Sanitization** and validation

## ⚡ Performance

- **Query Caching** for product listings (15min TTL)
- **Eager Loading** to prevent N+1 queries
- **Background Jobs** for email notifications
- **Database Indexing** on key columns

## 📊 Sample Data

The seeder creates realistic test data:
- 2 admin users
- 10 customer users  
- 5 categories
- 20 products
- 15 orders with payments

**Default Admin:** `admin@example.com` / `password`

---

## 📄 License

MIT License - feel free to use this project for learning and development!

---

<div align="center">
  <strong>Built with ❤️ using Laravel 12 & PHP 8.4</strong>
</div>