# E-commerce API

A robust RESTful API for e-commerce applications built with Laravel. This API provides comprehensive endpoints for managing products, orders, users, and all core e-commerce functionality.

## 🚀 Features

- **User Authentication & Authorization** - Laravel Sanctum token-based authentication with role management
- **Product Management** - CRUD operations for products with categories and inventory tracking
- **Shopping Cart** - Add, update, and remove items from cart
- **Order Management** - Complete order lifecycle from creation to fulfillment
- **Payment Integration** - Support for multiple payment gateways
- **User Profiles** - Customer account management and order history
- **Search & Filtering** - Advanced product search and filtering capabilities
- **Notification System** (Events, Listeners, and Laravel Notifications)

## ⚙️ Installation

### 1. Clone the repository

```bash
git clone https://github.com/mahmoud4524354/Ecommerce-Api.git
cd Ecommerce-Api
```

### 2. Install dependencies

```bash
composer install
```

### 3. Environment setup

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure your database

Edit `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 5. Run migrations and seeders

```bash
php artisan migrate
php artisan db:seed
```

### 6. Start the development server

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`
