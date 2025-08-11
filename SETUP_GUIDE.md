# User Role and Address Setup Guide

## Overview
This guide covers the complete setup for user roles (admin, seller, customer) and address management using Spatie Laravel Permission.

## Changes Made

### 1. Database Migration
- **File**: `database/migrations/0001_01_01_000000_create_users_table.php`
- **Changes**: Added address fields to users table:
  - `zip_code` (string, nullable)
  - `city` (string, nullable)
  - `state` (string, nullable)

### 2. User Model Updates
- **File**: `app/Models/User.php`
- **Changes**:
  - Added Spatie's `HasRoles` trait
  - Added address fields to `$fillable`
  - Configured for role-based permissions

### 3. Permission System
- **Package**: `spatie/laravel-permission`
- **Roles Created**:
  - `admin` - Full system access
  - `seller` - Product management and seller dashboard
  - `customer` - Shopping and order management

### 4. Registration Updates
- **File**: `app/Actions/Fortify/CreateNewUser.php`
- **Changes**:
  - Added validation for role selection
  - Added address fields validation
  - Automatic role assignment on registration

## Setup Instructions

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Run Seeders
```bash
php artisan db:seed
```

### 3. Usage Examples

#### Check User Role
```php
$user = auth()->user();
if ($user->hasRole('admin')) {
    // Admin specific logic
}
```

#### Check Permission
```php
if ($user->can('manage products')) {
    // Show product management interface
}
```

#### Middleware Usage
```php
// In routes/web.php
Route::group(['middleware' => ['role:admin']], function () {
    // Admin routes
});

Route::group(['middleware' => ['role:seller']], function () {
    // Seller routes
});
```

## Next Steps

1. **Update Registration Form**: Add role selection and address fields to the registration view
2. **Update Profile Forms**: Add address fields to profile update forms
3. **Create Role-Specific Views**: Design different dashboards for each role
4. **Add Middleware**: Protect routes based on roles and permissions

## Available Roles and Permissions

### Roles
- `admin` - Full system access
- `seller` - Can manage products and view seller dashboard
- `customer` - Can browse products and place orders

### Key Permissions
- `view dashboard`
- `manage products`
- `manage orders`
- `manage users`
- `view seller dashboard`
- `view customer dashboard`
