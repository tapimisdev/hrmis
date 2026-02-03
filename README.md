🚀 How to Run Orbit (Laravel + Docker)

Easily spin up your Laravel Orbit application locally using Docker and
Docker Compose. This guide walks you through setting up a fully
functional development environment in just a few steps.

📋 Prerequisites

Before you begin, make sure you have the following installed:

-   Docker — Container platform
-   Docker Compose — Multi-container orchestration
-   Git — Version control

🎯 Quick Start

Step 1: Clone the Repository

    git clone https://github.com/KemuelJoshua/dost.git orbit

    cd orbit

Step 2: Build and Start Containers

    docker-compose up -d --build

Step 3: Fix Entrypoint Script (Linux Users)

    sed -i 's/\r$//' docker/php-apache/entrypoint.sh

    chmod +x docker/php-apache/entrypoint.sh

Step 4: Enter the Application Container

    docker exec -it orbit bash

Step 5: Install Dependencies

    composer install

    npm install

    npm run build

Step 6: Storage & Cache Permissions

    docker exec -it orbit chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

    docker exec -it orbit chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

Step 7: Run Migrations & Seed

    docker exec -it orbit php artisan migrate --force

    docker exec -it orbit php artisan db:seed --force

Step 8: Access the Application

http://localhost:8001

🔧 Useful Docker Commands

    docker-compose ps
    docker-compose logs -f orbit
    docker-compose down
    docker-compose up -d --build
    docker exec -it orbit bash
