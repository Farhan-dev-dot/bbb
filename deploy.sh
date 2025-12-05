#!/bin/bash

# Docker Deployment Script for BBB Laravel Application

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== BBB Laravel Docker Deployment ===${NC}"

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    print_error "Docker is not running. Please start Docker and try again."
    exit 1
fi

# Check if docker-compose is available
if ! command -v docker-compose &> /dev/null; then
    print_error "docker-compose is not installed. Please install it and try again."
    exit 1
fi

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    print_warning ".env file not found. Creating from .env.example"
    if [ -f .env.example ]; then
        cp .env.example .env
        print_status ".env file created from .env.example"
    else
        print_error ".env.example not found. Please create .env file manually."
        exit 1
    fi
fi

# Stop any running containers
print_status "Stopping existing containers..."
docker-compose down --remove-orphans

# Build and start containers
print_status "Building and starting containers..."
docker-compose up --build -d

# Wait for database to be ready
print_status "Waiting for database to be ready..."
sleep 30

# Run database migrations
print_status "Running database migrations..."
docker-compose exec -T app php artisan migrate --force

# Seed database (optional)
read -p "Do you want to run database seeders? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    print_status "Running database seeders..."
    docker-compose exec -T app php artisan db:seed --force
fi

# Clear and cache configurations
print_status "Optimizing application..."
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache

# Set proper permissions
print_status "Setting proper permissions..."
docker-compose exec -T app chown -R www-data:www-data /var/www/html/storage
docker-compose exec -T app chown -R www-data:www-data /var/www/html/bootstrap/cache

print_status "Deployment completed successfully!"

echo
echo -e "${GREEN}=== Services Status ===${NC}"
docker-compose ps

echo
echo -e "${GREEN}=== Access URLs ===${NC}"
echo -e "Application: ${BLUE}http://localhost:8000${NC}"
echo -e "PHPMyAdmin: ${BLUE}http://localhost:8080${NC}"
echo -e "Redis Commander: ${BLUE}http://localhost:8081${NC}"
echo -e "Nginx (Production): ${BLUE}http://localhost${NC}"

echo
echo -e "${GREEN}=== Useful Commands ===${NC}"
echo "View logs: docker-compose logs -f"
echo "Access app container: docker-compose exec app bash"
echo "Access database: docker-compose exec db mysql -u root -p"
echo "Stop services: docker-compose down"
echo "Restart services: docker-compose restart"

echo
print_status "Deployment script finished!"