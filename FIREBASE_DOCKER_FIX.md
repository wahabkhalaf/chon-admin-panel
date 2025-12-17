# Firebase OpenSSL Error - Docker Configuration Fix

## Problem
When seeding notifications, you're getting this error:
```json
{
    "success": false,
    "error": "Topic Error: OpenSSL unable to validate key",
    "status_code": 500
}
```

## Root Cause
The Docker container is using a **placeholder** Firebase service account key instead of your real credentials. The placeholder has an invalid private key that OpenSSL cannot validate.

The entrypoint script was creating this placeholder when the credentials file didn't exist in the container:
```bash
echo '{"type":"service_account",...,"private_key":"-----BEGIN PRIVATE KEY-----\nplaceholder\n-----END PRIVATE KEY-----\n"}'
```

## Solution

### Step 1: Copy Firebase Credentials to Server
On your DigitalOcean server (`134.209.27.138`), copy the Firebase service account JSON file:

```bash
# SSH into your server
ssh root@134.209.27.138

# Create the directory
mkdir -p /opt/chon

# Copy the Firebase credentials file to the server
# From your local machine, run:
scp /Users/zeyadkhudeeda/Documents/programming_projects/chon/chon-admin-panel/storage/app/firebase/firebase-service-account.json root@134.209.27.138:/opt/chon/
```

### Step 2: Update docker-compose.yml on Server
On your server at `/opt/chon/docker-compose.yml`, add the Firebase credentials volume mount:

```yaml
services:
  admin:
    image: ${REGISTRY:-registry.digitalocean.com/chon}/admin:${IMAGE_TAG:-latest}
    container_name: chon-admin
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    env_file:
      - .env
    
    networks:
      - chon_network
    
    volumes:
      - admin_storage:/var/www/html/storage
      - admin_logs:/var/www/html/storage/logs
      - /opt/chon/ssl:/etc/nginx/ssl:ro
      - /opt/chon/nginx:/etc/nginx/http.d:ro
      - /opt/chon/firebase-service-account.json:/var/www/html/storage/app/firebase/firebase-service-account.json:ro  # ADD THIS LINE
    
    # ... rest of the configuration
```

The key line to add is:
```yaml
- /opt/chon/firebase-service-account.json:/var/www/html/storage/app/firebase/firebase-service-account.json:ro
```

### Step 3: Verify Permissions
Make sure the Firebase credentials file has correct permissions:

```bash
# On the server
chmod 644 /opt/chon/firebase-service-account.json
```

### Step 4: Restart Container
```bash
# On the server
cd /opt/chon
docker-compose down
docker-compose up -d
```

### Step 5: Verify Firebase Connection
```bash
# In the container
docker exec chon-admin php artisan firebase:test
# or
docker exec chon-admin php artisan notifications:test
```

## Docker Changes Made Locally
The entrypoint script has been updated to:
1. ✅ Not create placeholder credentials anymore
2. ✅ Verify credentials are mounted properly
3. ✅ Warn if credentials are missing with clear instructions
4. ✅ Validate the credentials file contains valid private key

## Testing After Fix

### Test 1: Direct Firebase Connection
```bash
docker exec chon-admin php artisan firebase:test
```

### Test 2: Seed Notifications
```bash
docker exec chon-admin php artisan db:seed --class=NotificationSeeder
# or
docker exec chon-admin php artisan db:seed --class=NotificationDemoSeeder
```

### Test 3: Send Test Notification
```bash
docker exec chon-admin php artisan fcm:test --title="Test Notification" --message="Firebase is now working!"
```

## Environment Variables Check
Ensure your `.env` file on the server has these Firebase settings:
```bash
FIREBASE_PROJECT=chon-1114a
```

The credentials file location is configured in `config/firebase.php`:
```php
'credentials' => storage_path('app/firebase/firebase-service-account.json'),
```

## Troubleshooting

### If you still get OpenSSL errors:
1. Verify the file is mounted: `docker exec chon-admin ls -la /var/www/html/storage/app/firebase/`
2. Check file contents: `docker exec chon-admin cat /var/www/html/storage/app/firebase/firebase-service-account.json | head -20`
3. Check container logs: `docker logs chon-admin | grep -i firebase`

### If notifications still fail to send:
1. Verify the private key format starts with `-----BEGIN PRIVATE KEY-----`
2. Ensure no newline characters are corrupted during file transfer
3. Check that the file is readable by the www-data user inside the container

## Build Process Update
The Dockerfile now handles missing credentials gracefully. The actual credentials must be provided via the volume mount in docker-compose.yml.

## Migration Path
For existing deployments:
1. Backup current `/opt/chon/docker-compose.yml`
2. Add the Firebase volume mount
3. Copy credentials file
4. Restart container
5. Run migrations if needed
6. Seed data with notifications

