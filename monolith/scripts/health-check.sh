#!/bin/bash
# Health check dan monitoring

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "========================================="
echo "Application Health Check"
echo "========================================="
echo ""

# Check Docker
echo "Checking Docker containers..."
docker-compose ps

echo ""
echo "Container Status:"
APP_STATUS=$(docker-compose ps app | grep -c "Up" || echo "0")
NGINX_STATUS=$(docker-compose ps nginx | grep -c "Up" || echo "0")
MYSQL_STATUS=$(docker-compose ps mysql | grep -c "Up" || echo "0")

if [ $APP_STATUS -eq 1 ]; then
    echo -e "${GREEN}✓ App container is UP${NC}"
else
    echo -e "${RED}✗ App container is DOWN${NC}"
fi

if [ $NGINX_STATUS -eq 1 ]; then
    echo -e "${GREEN}✓ Nginx container is UP${NC}"
else
    echo -e "${RED}✗ Nginx container is DOWN${NC}"
fi

if [ $MYSQL_STATUS -eq 1 ]; then
    echo -e "${GREEN}✓ MySQL container is UP${NC}"
else
    echo -e "${RED}✗ MySQL container is DOWN${NC}"
fi

echo ""
echo "Disk Usage:"
df -h | grep -E '^/dev/' | awk '{print $5, $6}'

echo ""
echo "Recent Errors (last 20 lines):"
docker-compose logs app 2>&1 | tail -20 | grep -i error || echo "No errors found"

echo ""
echo "========================================="
