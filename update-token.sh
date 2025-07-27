#!/bin/bash

# New token for verified admin user (ID: 39)
NEW_TOKEN="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6IjM5Iiwibmlja25hbWUiOiJBZG1pbiBVc2VyIiwiaWF0IjoxNzUzNTY2NjAzLCJleHAiOjE3NTQxNzE0MDN9.30bhNaD41edLiQJvQ5C6r-9-uAY-v3mdVM02a14ps4U"

echo "Updating EXPRESS_API_TOKEN in .env file..."

# Update the EXPRESS_API_TOKEN in .env file
sed -i.bak "s|EXPRESS_API_TOKEN=.*|EXPRESS_API_TOKEN=$NEW_TOKEN|g" .env

echo "✅ Updated EXPRESS_API_TOKEN with verified admin user token"
echo "Clearing Laravel config cache..."

# Clear Laravel config cache
./vendor/bin/sail artisan config:clear

echo "✅ Configuration updated successfully!"
echo ""
echo "You can now test the notification system with:"
echo "./vendor/bin/sail artisan notifications:test" 