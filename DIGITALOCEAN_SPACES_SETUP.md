# DigitalOcean Spaces Setup for Advertising Images

## Overview
The advertising images are now configured to use DigitalOcean Spaces instead of local storage. This provides better scalability, CDN delivery, and eliminates storage issues.

## Configuration

### 1. DigitalOcean Spaces Details
- **Space Name**: `chon`
- **Region**: `lon1` (London)
- **Endpoint**: `https://lon1.digitaloceanspaces.com`
- **CDN Endpoint**: `https://chon.lon1.cdn.digitaloceanspaces.com`

### 2. Required Package
Install the AWS S3 Flysystem adapter (required for DigitalOcean Spaces):

```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

Or if using Docker:
```bash
docker-compose exec app composer require league/flysystem-aws-s3-v3 "^3.0"
```

### 3. Environment Variables
Add your DigitalOcean Spaces credentials to the `.env` file:

```env
# DigitalOcean Spaces Configuration
DO_SPACES_KEY=YOUR_DO_SPACES_ACCESS_KEY
DO_SPACES_SECRET=YOUR_DO_SPACES_SECRET_KEY
DO_SPACES_REGION=lon1
DO_SPACES_BUCKET=chon
DO_SPACES_ENDPOINT=https://lon1.digitaloceanspaces.com
DO_SPACES_CDN_ENDPOINT=https://chon.lon1.cdn.digitaloceanspaces.com
```

#### How to Get Your Access Keys:
1. Log in to your DigitalOcean account
2. Go to **API** → **Spaces Keys**
3. Click **Generate New Key**
4. Copy the **Access Key** and **Secret Key**
5. Replace `YOUR_DO_SPACES_ACCESS_KEY` and `YOUR_DO_SPACES_SECRET_KEY` in `.env`

### 4. Space Permissions
Ensure your DigitalOcean Space has:
- **Public Read Access** enabled (for CDN delivery)
- **CORS Configuration** (if needed for direct uploads)

To enable public read:
1. Go to your Space settings
2. Navigate to **Settings**
3. Under **File Listing**, enable "Public"

### 5. Files Modified

#### config/filesystems.php
Added new `spaces` disk configuration that uses S3-compatible driver with DigitalOcean endpoints.

#### app/Filament/Resources/AdvertisingResource.php
- Changed `disk('public')` to `disk('spaces')`
- Updated image column to use new URL accessor

#### app/Models/Advertising.php
- Simplified `getImageUrlAttribute()` to return CDN URLs directly
- Removed environment-specific URL logic
- All images now served through DigitalOcean CDN

## Usage

### Creating New Advertisements
When creating or editing advertisements through the Filament admin panel:
1. Upload images as usual
2. Images are automatically stored in DigitalOcean Spaces
3. CDN URLs are generated automatically
4. No changes needed in your workflow

### API Response
The API will return advertisement images with CDN URLs:
```json
{
  "id": 1,
  "company_name": "Example Company",
  "phone_number": "+1234567890",
  "image": "https://chon.lon1.cdn.digitaloceanspaces.com/advertisements/image.jpg",
  "is_active": true
}
```

## Benefits

1. **CDN Delivery**: Fast image loading worldwide through DigitalOcean's CDN
2. **Scalability**: No storage limits on your application server
3. **Reliability**: Images hosted on DigitalOcean's infrastructure
4. **Cost-Effective**: Pay only for storage and bandwidth used
5. **No Migration Needed**: New uploads go to Spaces; existing local images can be migrated gradually

## Migrating Existing Images (Optional)

If you have existing images in local storage, you can migrate them:

```php
// Run this in tinker or create a command
use App\Models\Advertising;
use Illuminate\Support\Facades\Storage;

Advertising::all()->each(function ($ad) {
    if ($ad->image && !filter_var($ad->image, FILTER_VALIDATE_URL)) {
        $oldPath = storage_path('app/public/' . $ad->image);
        
        if (file_exists($oldPath)) {
            $contents = file_get_contents($oldPath);
            Storage::disk('spaces')->put($ad->image, $contents);
            echo "Migrated: {$ad->image}\n";
        }
    }
});
```

## Troubleshooting

### Images Not Displaying
1. Verify `.env` credentials are correct
2. Check Space has public read access enabled
3. Verify CDN endpoint is correct
4. Clear Laravel cache: `php artisan config:clear`

### Upload Failures
1. Check Space permissions
2. Verify access keys have write permissions
3. Check file size limits (current: 26MB)
4. Review Laravel logs: `storage/logs/laravel.log`

### CORS Issues (if uploading from frontend)
Add CORS configuration to your Space:
```json
[
  {
    "AllowedOrigins": ["*"],
    "AllowedMethods": ["GET", "PUT", "POST", "DELETE"],
    "AllowedHeaders": ["*"],
    "MaxAgeSeconds": 3000
  }
]
```

## Testing

Test the configuration:
```bash
php artisan tinker
```

```php
// Test upload
Storage::disk('spaces')->put('test.txt', 'Hello World');

// Test read
Storage::disk('spaces')->get('test.txt');

// Get URL
Storage::disk('spaces')->url('test.txt');
```

## Support

For issues with:
- **DigitalOcean Spaces**: [DigitalOcean Documentation](https://docs.digitalocean.com/products/spaces/)
- **Laravel Filesystem**: [Laravel Docs](https://laravel.com/docs/filesystem)
- **Filament Upload**: [Filament Docs](https://filamentphp.com/docs/forms/fields/file-upload)
