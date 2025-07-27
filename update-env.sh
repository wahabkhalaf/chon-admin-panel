#!/bin/bash

# Get the host machine's IP address
HOST_IP=$(ifconfig | grep "inet " | grep -v 127.0.0.1 | head -1 | awk '{print $2}')

echo "Host IP detected: $HOST_IP"
echo "Updating .env file..."

# Update the EXPRESS_API_BASE_URL in .env file
sed -i.bak "s|EXPRESS_API_BASE_URL=http://localhost:3001|EXPRESS_API_BASE_URL=http://$HOST_IP:3001|g" .env

echo "✅ Updated .env file with host IP: $HOST_IP"
echo "Clearing Laravel config cache..."

# Clear Laravel config cache
./vendor/bin/sail artisan config:clear

echo "✅ Configuration updated successfully!"
echo ""
echo "You can now test the notification system with:"
echo "./vendor/bin/sail artisan notifications:test" 