# Advertising System

## Overview
The advertising system allows administrators to manage company advertisements with images, company names, and phone numbers.

## Features
- **Company Information**: Store company name and phone number
- **Image Management**: Upload and manage advertisement images
- **Status Control**: Activate/deactivate advertisements
- **File Support**: Accepts JPEG, JPG, and PNG formats
- **Image Optimization**: Automatic resizing and cropping

## Database Structure
The `advertisements` table contains:
- `id` - Primary key
- `company_name` - Company name (required)
- `phone_number` - Contact phone number (required)
- `image` - Image file path (required)
- `is_active` - Status flag (default: true)
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

## Admin Panel Access
Navigate to `/admin/advertisings` in the admin panel to:
- View all advertisements
- Create new advertisements
- Edit existing advertisements
- Delete advertisements
- Toggle advertisement status

## Image Requirements
- **Formats**: JPEG, JPG, PNG
- **Max Size**: 5MB
- **Aspect Ratio**: 16:9 (automatically enforced)
- **Target Dimensions**: 800x450 pixels
- **Storage**: Public disk in `storage/app/public/advertisements/`

## Usage Examples

### Creating an Advertisement
1. Go to `/admin/advertisings/create`
2. Fill in company name and phone number
3. Upload an image file
4. Set active status
5. Click "Create"

### Editing an Advertisement
1. Go to `/admin/advertisings`
2. Click the edit button for the desired advertisement
3. Modify fields as needed
4. Click "Save changes"

### Managing Status
- Use the toggle switch to activate/deactivate advertisements
- Active advertisements are marked with a green "Active" badge
- Inactive advertisements are marked with a red "Inactive" badge

## API Access
The system stores image URLs that can be accessed via:
```
/storage/advertisements/{filename}
```

## File Management
- Images are stored in the public storage disk
- Automatic cleanup when advertisements are deleted
- Image optimization and resizing handled automatically
- Support for image editing within the admin panel

## Security Features
- File type validation
- File size limits
- Secure file storage
- Admin-only access control
