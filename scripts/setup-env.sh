#!/bin/bash

# ===========================================
# ACS Environment Setup Script
# ===========================================
# This script helps configure environment variables
# for the ACS (Auto Configuration Server) system

set -e

echo "======================================"
echo "  ACS Environment Setup"
echo "======================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if .env exists
if [ -f .env ]; then
    echo -e "${YELLOW}Warning: .env file already exists${NC}"
    read -p "Do you want to backup and overwrite it? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
        echo -e "${GREEN}Backed up to .env.backup.$(date +%Y%m%d_%H%M%S)${NC}"
    else
        echo "Exiting without changes"
        exit 0
    fi
fi

# Copy template
echo "Copying .env.example to .env..."
cp .env.example .env

# Generate app key
echo "Generating application key..."
php artisan key:generate --ansi

echo ""
echo "======================================"
echo "  Configuration Wizard"
echo "======================================"
echo ""

# Environment selection
echo "Select environment:"
echo "1) Development (local)"
echo "2) Production"
read -p "Choice [1]: " env_choice
env_choice=${env_choice:-1}

if [ "$env_choice" = "2" ]; then
    sed -i 's/APP_ENV=local/APP_ENV=production/' .env
    sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env
    sed -i 's/LOG_LEVEL=debug/LOG_LEVEL=warning/' .env
    echo -e "${GREEN}Set to production mode${NC}"
fi

# API Key
echo ""
echo -e "${YELLOW}ACS API Key Configuration${NC}"
read -p "Enter API key (leave empty for random): " api_key
if [ -z "$api_key" ]; then
    api_key="acs-$(openssl rand -hex 16)"
    echo -e "${GREEN}Generated API key: $api_key${NC}"
fi

# Add or update API key
if grep -q "^ACS_API_KEY=" .env; then
    sed -i "s|^ACS_API_KEY=.*|ACS_API_KEY=$api_key|" .env
else
    echo "" >> .env
    echo "# ACS API Configuration" >> .env
    echo "ACS_API_KEY=$api_key" >> .env
fi

# Database configuration
echo ""
echo -e "${YELLOW}Database Configuration${NC}"
echo "Select database:"
echo "1) PostgreSQL (recommended for production)"
echo "2) SQLite (development only)"
read -p "Choice [1]: " db_choice
db_choice=${db_choice:-1}

if [ "$db_choice" = "1" ]; then
    read -p "Database host [127.0.0.1]: " db_host
    db_host=${db_host:-127.0.0.1}
    
    read -p "Database port [5432]: " db_port
    db_port=${db_port:-5432}
    
    read -p "Database name [acs_database]: " db_name
    db_name=${db_name:-acs_database}
    
    read -p "Database username [acs_user]: " db_user
    db_user=${db_user:-acs_user}
    
    read -sp "Database password: " db_pass
    echo
    
    sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=pgsql|" .env
    sed -i "s|^DB_HOST=.*|DB_HOST=$db_host|" .env
    sed -i "s|^DB_PORT=.*|DB_PORT=$db_port|" .env
    sed -i "s|^DB_DATABASE=.*|DB_DATABASE=$db_name|" .env
    sed -i "s|^DB_USERNAME=.*|DB_USERNAME=$db_user|" .env
    sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$db_pass|" .env
    
    echo -e "${GREEN}PostgreSQL configured${NC}"
else
    sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=sqlite|" .env
    echo -e "${GREEN}SQLite configured (development mode)${NC}"
fi

# MQTT Configuration
echo ""
echo -e "${YELLOW}MQTT Broker Configuration${NC}"
echo "1) Use test.mosquitto.org (public test broker)"
echo "2) Configure custom broker"
read -p "Choice [1]: " mqtt_choice
mqtt_choice=${mqtt_choice:-1}

if [ "$mqtt_choice" = "2" ]; then
    read -p "MQTT Host: " mqtt_host
    read -p "MQTT Port [1883]: " mqtt_port
    mqtt_port=${mqtt_port:-1883}
    read -p "MQTT Username (optional): " mqtt_user
    read -sp "MQTT Password (optional): " mqtt_pass
    echo
    
    # Add MQTT config if not exists
    if ! grep -q "^MQTT_HOST=" .env; then
        echo "" >> .env
        echo "# MQTT Configuration" >> .env
    fi
    
    sed -i "s|^MQTT_HOST=.*|MQTT_HOST=$mqtt_host|" .env
    sed -i "s|^MQTT_PORT=.*|MQTT_PORT=$mqtt_port|" .env
    
    if [ -n "$mqtt_user" ]; then
        if grep -q "^MQTT_USERNAME=" .env; then
            sed -i "s|^MQTT_USERNAME=.*|MQTT_USERNAME=$mqtt_user|" .env
        else
            echo "MQTT_USERNAME=$mqtt_user" >> .env
        fi
    fi
    
    if [ -n "$mqtt_pass" ]; then
        if grep -q "^MQTT_PASSWORD=" .env; then
            sed -i "s|^MQTT_PASSWORD=.*|MQTT_PASSWORD=$mqtt_pass|" .env
        else
            echo "MQTT_PASSWORD=$mqtt_pass" >> .env
        fi
    fi
    
    echo -e "${GREEN}Custom MQTT broker configured${NC}"
fi

# WebSocket Configuration
echo ""
echo -e "${YELLOW}WebSocket Server Configuration${NC}"
read -p "WebSocket port [9000]: " ws_port
ws_port=${ws_port:-9000}

if ! grep -q "^USP_WEBSOCKET_PORT=" .env; then
    echo "" >> .env
    echo "# USP WebSocket Configuration" >> .env
    echo "USP_WEBSOCKET_ENABLED=true" >> .env
    echo "USP_WEBSOCKET_HOST=0.0.0.0" >> .env
    echo "USP_WEBSOCKET_PORT=$ws_port" >> .env
    echo "USP_WEBSOCKET_PING_INTERVAL=30" >> .env
else
    sed -i "s|^USP_WEBSOCKET_PORT=.*|USP_WEBSOCKET_PORT=$ws_port|" .env
fi

echo -e "${GREEN}WebSocket configured on port $ws_port${NC}"

# Redis/Queue Configuration
echo ""
echo -e "${YELLOW}Queue Configuration${NC}"
echo "1) Database queue (simple, development)"
echo "2) Redis queue (recommended for production)"
read -p "Choice [1]: " queue_choice
queue_choice=${queue_choice:-1}

if [ "$queue_choice" = "2" ]; then
    read -p "Redis host [127.0.0.1]: " redis_host
    redis_host=${redis_host:-127.0.0.1}
    
    read -p "Redis port [6379]: " redis_port
    redis_port=${redis_port:-6379}
    
    sed -i "s|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=redis|" .env
    sed -i "s|^REDIS_HOST=.*|REDIS_HOST=$redis_host|" .env
    sed -i "s|^REDIS_PORT=.*|REDIS_PORT=$redis_port|" .env
    
    echo -e "${GREEN}Redis queue configured${NC}"
else
    sed -i "s|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=database|" .env
    echo -e "${GREEN}Database queue configured${NC}"
fi

echo ""
echo "======================================"
echo -e "${GREEN}  Environment Setup Complete!${NC}"
echo "======================================"
echo ""
echo "Next steps:"
echo "1. Review .env file: nano .env"
echo "2. Run migrations: php artisan migrate"
echo "3. Start server: php artisan serve --host=0.0.0.0 --port=5000"
echo ""
echo "Optional background services:"
echo "- MQTT subscriber: php artisan usp:mqtt-subscribe"
echo "- WebSocket server: php artisan usp:websocket-server"
echo "- Queue worker: php artisan horizon"
echo ""
echo -e "${YELLOW}Your API Key: $api_key${NC}"
echo "Save this key - you'll need it for API authentication!"
echo ""
