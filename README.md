🚀 Run Laravel App Locally with Docker
Easily spin up your Laravel application locally using Docker and Docker Compose. This guide walks you through setting up a fully functional development environment in just a few steps.

📋 Prerequisites
Before you begin, ensure you have the following installed on your machine:

Docker — Container platform

Docker Compose — Multi-container orchestration

Git — Version control

🎯 Quick Start
Step 1: Clone the Repository
Start by cloning your Laravel project:

Bash
git clone https://github.com/KemuelJoshua/dost.git orbit
cd orbit
Step 2: Build and Start Containers
Build and run your Docker containers in detached mode:

Bash
docker-compose up -d --build
This command will download necessary images and start all services defined in your docker-compose.yml.

Step 3: Fix Entrypoint Script (Linux Users)
This prevents execution errors caused by Windows line endings (CRLF) and ensures the script is executable:

Bash
sed -i 's/\r$//' docker/php-apache/entrypoint.sh
chmod +x docker/php-apache/entrypoint.sh
Step 4: Storage & Cache Setup
The entrypoint script usually handles permissions, but you can manually ensure the web server has access:

Bash
docker exec -it orbit chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker exec -it orbit chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
Step 5: Run Migrations & Seed Database
Initialize your database with tables and sample data:

Bash
docker exec -it orbit php artisan migrate --force
docker exec -it orbit php artisan db:seed --force
Step 6: Install Node Dependencies & Build Assets
Compile your front-end assets if they weren't bundled during the image build:

Bash
docker exec -it orbit npm install
docker exec -it orbit npm run build
Step 7: Access Your Application
Open your browser and navigate to:

Plaintext
http://localhost:8001
Your Laravel application is now running! 🎉

🔗 Useful Commands
Here are some handy Docker commands for development:

Bash
# View running containers
docker-compose ps

# View container logs
docker-compose logs -f orbit

# Stop containers
docker-compose down

# Rebuild containers
docker-compose up -d --build

# Access the application container shell
docker exec -it orbit bash
Would you like me to help you generate a docker-compose.yml file or a Dockerfile to match this specific setup?