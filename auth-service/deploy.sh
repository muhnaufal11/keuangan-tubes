#!/bin/bash
# Script untuk setup & deploy aplikasi ke CasaOS

echo "========================================="
echo "Keuangan App - CasaOS Deployment Script"
echo "========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo -e "${RED}Docker is not installed. Installing Docker...${NC}"
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}Docker Compose is not installed. Installing Docker Compose...${NC}"
    sudo apt-get update
    sudo apt-get install -y docker-compose
fi

echo -e "${GREEN}Docker & Docker Compose are ready${NC}"
echo ""

# Create docker directory if not exists
mkdir -p docker/ssl

# Build Docker images
echo -e "${YELLOW}Building Docker images...${NC}"
docker-compose build

echo ""
echo -e "${YELLOW}Starting containers...${NC}"
docker-compose up -d

# Wait for MySQL to be ready
echo -e "${YELLOW}Waiting for database to be ready...${NC}"
sleep 10

# Run migrations
echo -e "${YELLOW}Running database migrations...${NC}"
docker-compose exec -T app php artisan migrate --force

# Create storage link
echo -e "${YELLOW}Creating storage link...${NC}"
docker-compose exec -T app php artisan storage:link

# Set proper permissions
echo -e "${YELLOW}Setting file permissions...${NC}"
docker-compose exec -T app chown -R www-data:www-data storage
docker-compose exec -T app chown -R www-data:www-data bootstrap/cache

echo ""
echo -e "${GREEN}=========================================${NC}"
echo -e "${GREEN}Deployment Complete!${NC}"
echo -e "${GREEN}=========================================${NC}"
echo ""
echo "Application URL: http://localhost"
echo ""
echo "Available commands:"
echo "  docker-compose ps              - Check running containers"
echo "  docker-compose logs -f         - View logs"
echo "  docker-compose exec app bash   - Connect to app container"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Configure .env file with your domain and database credentials"
echo "2. Setup SSL certificate if needed"
echo "3. Configure your domain DNS"
echo ""
