#!/bin/bash
set -e

echo "🚀 Deploying CHON Admin Panel..."

# Navigate to admin panel directory
cd /opt/chon-admin-panel

# Pull latest changes
echo "📥 Pulling latest code..."
git pull origin main

# Copy Docker files if not exist
if [ ! -d "docker" ]; then
  echo "📁 Creating docker directory..."
  mkdir -p docker/nginx docker/supervisor
fi

cp /opt/chon-admin-panel/infra/admin/nginx-default.conf docker/nginx/default.conf
cp /opt/chon-admin-panel/infra/admin/supervisord.conf docker/supervisor/supervisord.conf
cp /opt/chon-admin-panel/infra/admin/Dockerfile Dockerfile

# Build and start
echo "🏗️  Building Docker image..."
cd /opt/chon-admin-panel/infra/admin
docker compose -f docker-compose.admin.yml build

echo "🚀 Starting admin panel..."
docker compose -f docker-compose.admin.yml up -d

echo "⏳ Waiting for container to initialize..."
sleep 10

echo "📋 Checking container logs..."
docker logs --tail 50 chon_admin_panel

echo ""
echo "✅ Deployment complete!"
echo "🌐 Access at: http://167.71.138.109:8080"
echo ""
echo "💡 Useful commands:"
echo "   View logs: docker logs -f chon_admin_panel"
echo "   Laravel logs: docker exec chon_admin_panel tail -f storage/logs/laravel.log"
echo "   Shell access: docker exec -it chon_admin_panel sh"
