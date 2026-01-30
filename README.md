# 🚀 Run Orbit (Laravel --- No Docker)

Easily run the **Laravel Orbit** application locally using PHP, MySQL,
and Node --- no Docker required.

Follow this step-by-step guide to get your development environment up
and running smoothly.

------------------------------------------------------------------------

## 📋 Prerequisites

Make sure you have the following installed:

-   PHP 8.2+\
-   Composer\
-   Node.js & npm\
-   MySQL or MariaDB\
-   Git

------------------------------------------------------------------------

## 🎯 Quick Start

### Step 1: Clone the Repository

``` bash
git clone https://github.com/KemuelJoshua/dost.git orbit
cd orbit
```

------------------------------------------------------------------------

### Step 2: Install Backend Dependencies

``` bash
composer install
```

------------------------------------------------------------------------

### Step 3: Setup Environment

``` bash
cp .env.example .env
php artisan key:generate
```

------------------------------------------------------------------------

### Step 4: Configure Database

Edit `.env`:

``` env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=orbit
DB_USERNAME=root
DB_PASSWORD=
```

Create DB:

``` bash
mysql -u root -p
```

``` sql
CREATE DATABASE orbit;
EXIT;
```

------------------------------------------------------------------------

### Step 5: Install Frontend & Build

``` bash
npm install
npm run build
```

For dev mode:

``` bash
npm run dev
```

------------------------------------------------------------------------

### Step 6: Fix Permissions (Linux/macOS)

``` bash
chmod -R 775 storage bootstrap/cache
sudo chown -R $USER:www-data storage bootstrap/cache
```

Windows usually needs no changes.

------------------------------------------------------------------------

### Step 7: Run Migrations & Seed

``` bash
php artisan migrate
php artisan db:seed
```

------------------------------------------------------------------------

### Step 8: Serve App

``` bash
php artisan serve
```

Visit:

http://127.0.0.1:8000

Custom port:

``` bash
php artisan serve --port=8001
```

------------------------------------------------------------------------

## 🛠 Useful Commands

``` bash
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

Logs:

``` bash
tail -f storage/logs/laravel.log
```

Queues:

``` bash
php artisan queue:work
```

------------------------------------------------------------------------

## ⚠️ Troubleshooting

### Storage Permission Error

``` bash
chmod -R 775 storage bootstrap/cache
```

### App Key Missing

``` bash
php artisan key:generate
```

### Blank Page / 500 Error

``` bash
php artisan optimize:clear
```

Check logs:

``` bash
storage/logs/laravel.log
```

------------------------------------------------------------------------

## 🚧 Common Errors

  Issue                   Fix
  ----------------------- ----------------------------------
  DB connection refused   Check MySQL running & env config
  Permission denied       Fix storage permissions
  Missing vendor          Run composer install
  Vite not loading        Run npm install & npm run dev

------------------------------------------------------------------------

## 🌐 Production Tips

-   Use Nginx or Apache instead of `php artisan serve`
-   Set `.env`:

``` env
APP_ENV=production
APP_DEBUG=false
```

-   Run:

``` bash
php artisan config:cache
php artisan route:cache
```

-   Setup Supervisor for queues

------------------------------------------------------------------------

## ✅ Done!

Orbit is now running locally without Docker 🚀\
Happy coding!
