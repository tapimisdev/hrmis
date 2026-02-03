# 🚀 Run Orbit (Laravel + Docker)

Easily spin up your **Laravel Orbit** application locally using Docker
and Docker Compose.

This guide walks you through setting up a fully functional development
environment in just a few simple steps.

------------------------------------------------------------------------

## 📋 Prerequisites

Before you begin, make sure you have the following installed:

-   Docker --- Container platform
-   Docker Compose --- Multi-container orchestration
-   Git --- Version control

------------------------------------------------------------------------

## 🎯 Quick Start

### Step 1: Clone the Repository

``` bash
git clone https://github.com/KemuelJoshua/dost.git orbit
cd orbit
```

------------------------------------------------------------------------

### Step 2: Build and Start Containers

``` bash
docker-compose up -d --build
```

------------------------------------------------------------------------

### Step 3: Enter the Application Container

``` bash
docker exec -it orbit bash
```

------------------------------------------------------------------------

### Step 4: Install Dependencies

``` bash
composer install
npm install
npm run build
```

------------------------------------------------------------------------

### Step 5: Storage & Cache Permissions

``` bash
docker exec -it orbit chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker exec -it orbit chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
```

------------------------------------------------------------------------

### Step 6: Reload & Restart Supervisor

``` bash
docker exec -it orbit supervisorctl reread
docker exec -it orbit supervisorctl update
docker exec -it orbit supervisorctl restart all
```

------------------------------------------------------------------------

### Step 7: Run Migrations & Seeders

``` bash
docker exec -it orbit php artisan migrate --force
docker exec -it orbit php artisan db:seed --force
```

------------------------------------------------------------------------

### Step 8: Access the Application

http://localhost:8001

------------------------------------------------------------------------

## ⚠️ Troubleshooting

### Containers not starting

``` bash
docker-compose down
docker-compose up -d --build
```

### Permission denied

``` bash
docker exec -it orbit chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
```

### 500 Error

``` bash
docker exec -it orbit php artisan optimize:clear
```

------------------------------------------------------------------------

## 🚧 Common Errors

  Error              Fix
  ------------------ ------------------------
  Port conflict      Change port in compose
  No vendor          Run composer install
  Assets missing     Run npm install
  Permission issue   Fix chmod

------------------------------------------------------------------------

## 🌐 Production Setup

-   Use volumes for storage
-   Disable debug mode
-   Setup HTTPS proxy
-   Use Supervisor for workers

------------------------------------------------------------------------

## ✅ Done!

Orbit is now running with Docker 🚀
