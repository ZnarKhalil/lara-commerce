# Laravel E-commerce API

A robust RESTful API for e-commerce applications built with Laravel. This API provides all the necessary endpoints to manage products, categories, orders, and user authentication.



## Installation

1. Clone the repository
```bash
git clone https://github.com/ZnarKhalil/lara-commerce.git
```

2. Install dependencies
```bash
composer install
```

3. Copy environment file
```bash
cp .env.example .env
```

4. Generate application key
```bash
php artisan key:generate
```

5. Configure database in .env file
```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

6. Run migrations
```bash
php artisan migrate
```

7. (Optional) Seed the database
```bash
php artisan db:seed
```


## Features

### Authentication
- User registration and login
- Token-based authentication using Laravel Sanctum
- Role-based authorization (Admin/User)
- Protected routes
- User profile management

### Products
- CRUD operations for products
- Product categorization
- Product search functionality
- Related products
- Stock management
- SKU tracking
- Product status (active/inactive)

### Categories
- CRUD operations for categories
- Category-based product filtering
- Category status management
- Products listing by category

### Orders
- Order creation and management
- Order status tracking
- Order cancellation (for pending orders)
- Order items management
- Stock validation and automatic adjustment
- Order history

### Authorization
- Admin-only routes for product and category management
- User-specific order management
- Role-based access control

### Code
- Request validation using Form Request classes
- Resource classes for consistent API responses
- Database transactions for data integrity
- Proper error handling
- Comprehensive test coverage
- Pagination support
- Search functionality
- Relationship loading optimization

## API Endpoints

### Authentication
- POST /api/auth/register - Register a new user
- POST /api/auth/login - Login user
- POST /api/auth/logout - Logout user
- GET /api/auth/me - Get authenticated user profile

### Products
- GET    /api/products              - List all products
- GET    /api/products/{id}         - Get single product
- POST   /api/products              - Create product (Admin only)
- PUT    /api/products/{id}         - Update product (Admin only)
- DELETE /api/products/{id}         - Delete product (Admin only)
- GET    /api/products/search/{query} - Search products
- GET    /api/products/{id}/related - Get related products

### Categories
- GET    /api/categories              - List all categories
- GET    /api/categories/{id}         - Get single category
- POST   /api/categories             - Create category (Admin only)
- PUT    /api/categories/{id}         - Update category (Admin only)
- DELETE /api/categories/{id}         - Delete category (Admin only)
- GET    /api/categories/{id}/products - Get products in category

### Orders
- GET    /api/orders              - List user's orders
- GET    /api/orders/{id}         - Get single order
- POST   /api/orders             - Create new order
- POST   /api/orders/{id}/cancel - Cancel order (if pending)
- PATCH  /api/orders/{id}/status - Update order status (Admin only)

## Technical Details

### Enums
- UserRole (USER, ADMIN)
- OrderStatus (PENDING, PROCESSING, COMPLETED, CANCELLED, REFUNDED)
- PaymentStatus (PENDING, PAID, FAILED, REFUNDED)
- PaymentMethod (CREDIT_CARD, PAYPAL, BANK_TRANSFER, CASH_ON_DELIVERY)

## Testing

Run the test suite:
```bash
php artisan test
```

## Future Enhancements
- Product variations (size, color, etc.)
- Image handling
- Coupon system
- Wishlist functionality
- Review/Rating system
- Payment gateway integration
- Email notifications
- Shopping cart for guest users
