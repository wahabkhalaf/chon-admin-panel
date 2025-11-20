# CHON Admin Panel - Docker Setup

This directory contains Docker configuration for the Laravel Filament admin panel.

## ⚠️ Important: Environment Variables

Before deploying, create a `.env` file in `/opt/chon-admin-panel/infra/admin/`:

```bash
# Generate with: php artisan key:generate --show (or use existing from .env)
ADMIN_APP_KEY=base64:LksPFZWbKLAh+JTwhZuP/P7si4Qp8S89KqPleGGt45Q=

# API Key for communicating with CHON API (optional)
ADMIN_API_KEY=your_api_key_here
```

## 📁 Structure

```
infra/admin/
├── Dockerfile                  # Laravel + PHP-FPM + Nginx image
├── docker-compose.admin.yml    # Admin panel service definition
├── nginx-default.conf          # Nginx configuration for Laravel
├── supervisord.conf            # Supervisor config for PHP-FPM + Nginx
├── deploy-admin.sh             # Deployment script
└── README.md                   # This file
```

## 🚀 Deployment Steps

### 1. Clone Admin Panel Repository

```bash
cd /opt
git clone <your-laravel-repo-url> chon-admin-panel
cd chon-admin-panel
```

### 2. Create Environment File

```bash
cp .env.example .env
nano .env
```

Required environment variables:
```env
APP_NAME="CHON Admin"
APP_ENV=production
APP_KEY=base64:...  # Generate with: php artisan key:generate --show
APP_DEBUG=false
APP_URL=http://admin.chonapp.net

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=chon
DB_USERNAME=chon
DB_PASSWORD=your_postgres_password

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=redis
REDIS_PORT=6379

CHON_API_URL=http://api:3000
CHON_API_KEY=your_api_key_here
```

### 3. Run Deployment Script

```bash
cd /opt/chon/infra/admin
chmod +x deploy-admin.sh
./deploy-admin.sh
```

### 4. Access Admin Panel

- URL: `http://167.71.138.109:8080`
- Or: `http://admin.chonapp.net` (requires DNS)

## 🔧 Manual Commands

### Build Image
```bash
cd /opt/chon/infra/admin
docker compose -f docker-compose.admin.yml build
```

### Start Service
```bash
docker compose -f docker-compose.admin.yml up -d
```

### Stop Service
```bash
docker compose -f docker-compose.admin.yml down
```

### View Logs
```bash
docker logs chon_admin_panel -f
```

### Run Artisan Commands
```bash
docker exec chon_admin_panel php artisan migrate
docker exec chon_admin_panel php artisan config:cache
docker exec chon_admin_panel php artisan make:filament-user
```

### Access Container
```bash
docker exec -it chon_admin_panel sh
```

## 📝 Notes

- Admin panel runs on port **8080**
- Shares PostgreSQL and Redis with main CHON API
- Uses same Docker network: `chon_chon_network`
- Logs stored in Docker volume: `chon_admin_logs`
- Storage persisted in: `chon_admin_storage`

## 🔐 First Time Setup

After deployment, create your first admin user:

```bash
docker exec -it chon_admin_panel php artisan make:filament-user
```

Follow the prompts to set:
- Name
- Email
- Password

## 🌐 Nginx Reverse Proxy (Optional)

To expose admin panel through main Nginx, add to `infra/nginx/conf.d/`:

```nginx
# admin.conf
server {
    listen 80;
    server_name admin.chonapp.net;
    
    location / {
        proxy_pass http://admin:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

Then reload Nginx:
```bash
docker exec chon_nginx_prod nginx -s reload
```
