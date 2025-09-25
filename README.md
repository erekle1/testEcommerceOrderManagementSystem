# E-commerce Order Management System

A comprehensive Laravel 12 backend API for managing e-commerce operations including authentication, product management, cart functionality, order processing, and payment handling.

## Features

- **Authentication & Authorization**: Laravel Sanctum-based API authentication with role-based access control
- **Product Management**: CRUD operations for products and categories with filtering and search
- **Shopping Cart**: Add, update, and remove items with stock validation
- **Order Processing**: Create orders from cart with automatic stock management
- **Payment Processing**: Mock payment system with transaction tracking
- **Notifications**: Order confirmation emails using Laravel queues
- **Caching**: Product listings cached for improved performance
- **Testing**: Comprehensive test suite with 97%+ coverage

## Technology Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Database**: SQLite (development) / MySQL (production)
- **Authentication**: Laravel Sanctum
- **Testing**: PHPUnit with Laravel testing features
- **Caching**: Laravel Cache (database driver)
- **Queues**: Database queues for background jobs
- **Code Quality**: Laravel Pint (PSR-12), Prettier

## Installation

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js and npm
- SQLite or MySQL

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd ecommerceOrderManagmentSystem
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database configuration**
   
   For SQLite (development):
   ```bash
   touch database/database.sqlite
   ```
   
   For MySQL (production), update `.env`:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ecommerce_order_management
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

6. **Run migrations and seeders**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

7. **Start the development server**
   ```bash
   composer run dev
   ```

   This will start:
   - Laravel server on `http://localhost:8000`
   - Queue worker
   - Log viewer
   - Vite dev server

## API Documentation

### Base URL
```
http://localhost:8000/api
```

### Authentication

All protected routes require a Bearer token in the Authorization header:
```
Authorization: Bearer <your-token>
```

### Endpoints

#### Authentication
- `POST /register` - Register a new user
- `POST /login` - Login user
- `POST /logout` - Logout user (protected)
- `GET /me` - Get current user profile (protected)

#### Categories
- `GET /categories` - List all categories
- `GET /categories/{id}` - Get specific category
- `POST /categories` - Create category (admin only)
- `PUT /categories/{id}` - Update category (admin only)
- `DELETE /categories/{id}` - Delete category (admin only)

#### Products
- `GET /products` - List products (with filters: category_id, min_price, max_price, search)
- `GET /products/{id}` - Get specific product
- `POST /products` - Create product (admin only)
- `PUT /products/{id}` - Update product (admin only)
- `DELETE /products/{id}` - Delete product (admin only)

#### Cart (Customer only)
- `GET /cart` - Get user's cart
- `POST /cart` - Add item to cart
- `GET /cart/{id}` - Get specific cart item
- `PUT /cart/{id}` - Update cart item quantity
- `DELETE /cart/{id}` - Remove cart item

#### Orders
- `GET /orders` - Get user's orders (protected)
- `POST /orders` - Create order from cart (protected)
- `GET /orders/{id}` - Get specific order (protected)
- `PUT /orders/{id}/status` - Update order status (admin only)

#### Payments
- `GET /payments` - Get user's payments (protected)
- `GET /payments/{id}` - Get specific payment (protected)
- `POST /orders/{orderId}/payment` - Process payment (protected)

### Sample Requests

#### Register User
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "customer"
  }'
```

#### Login
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

#### Get Products with Filters
```bash
curl -X GET "http://localhost:8000/api/products?category_id=1&min_price=10&max_price=100&search=phone"
```

#### Add to Cart
```bash
curl -X POST http://localhost:8000/api/cart \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <your-token>" \
  -d '{
    "product_id": 1,
    "quantity": 2
  }'
```

#### Create Order
```bash
curl -X POST http://localhost:8000/api/orders \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <your-token>" \
  -d '{
    "cart_items": [
      {
        "product_id": 1,
        "quantity": 2
      }
    ]
  }'
```

## User Roles

### Admin
- Manage categories and products
- Update order statuses
- Access to all admin operations

### Customer
- Browse products and categories
- Manage shopping cart
- Place orders
- View order history
- Process payments

## Sample Data

The seeder creates:
- 2 admin users
- 10 customer users
- 5 categories
- 20 products
- 10 cart items
- 15 orders with payments

### Default Admin Credentials
- Email: `admin@example.com`
- Password: `password`

- Email: `superadmin@example.com`
- Password: `password`

## Testing

Run the test suite:
```bash
php artisan test
```

Run tests with coverage:
```bash
php artisan test --coverage
```

The test suite includes:
- **Feature Tests**: Authentication, Product management, Cart operations, Order processing
- **Unit Tests**: OrderService business logic
- **Coverage**: 97%+ across controllers and services

## Development Commands

```bash
# Start development server with hot reload
composer run dev

# Run tests
composer run test

# Code formatting
./vendor/bin/pint
npm run format

# Database operations
php artisan migrate
php artisan migrate:rollback
php artisan db:seed
php artisan db:wipe

# Queue operations
php artisan queue:work
php artisan queue:listen
```

## Project Structure

```
app/
├── Http/
│   ├── Controllers/Api/     # API controllers
│   └── Middleware/          # Custom middleware
├── Models/                  # Eloquent models
├── Services/                # Business logic services
├── Traits/                  # Reusable traits
├── Notifications/           # Email notifications
└── Jobs/                    # Queue jobs

database/
├── migrations/              # Database migrations
├── seeders/                 # Database seeders
└── factories/               # Model factories

routes/
└── api.php                  # API routes

tests/
├── Feature/                 # Feature tests
└── Unit/                    # Unit tests
```

## Business Logic

### Order Processing
- Automatic stock validation before order creation
- Stock decrement on successful order
- Stock restoration on order cancellation
- Order status state machine validation

### Payment Processing
- Mock payment system with 90% success rate
- Transaction ID generation
- Payment status tracking

### Caching Strategy
- Product listings cached for 15 minutes
- Cache invalidation on product updates
- Query-based cache keys for filtered results

## Security Features

- API token authentication with Sanctum
- Role-based access control middleware
- Input validation and sanitization
- SQL injection prevention with Eloquent ORM
- CSRF protection for web routes

## Performance Optimizations

- Database indexing on frequently queried columns
- Eager loading to prevent N+1 queries
- Query result caching
- Background job processing for emails

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests and ensure they pass
5. Submit a pull request

## License

This project is licensed under the MIT License.