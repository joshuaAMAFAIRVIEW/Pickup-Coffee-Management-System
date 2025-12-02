# Return Equipment with Incident Report Feature

## Overview
Enhanced the return equipment process to properly handle condition tracking with dedicated input fields and photo upload for damaged equipment incidents.

## Changes Made

### 1. Fixed Modal Z-Index Issue
**Problem:** Return equipment modal was being blocked/hidden by the user detail modal.

**Solution:**
- Added inline styles to `users.php` to set return modal z-index to 1060
- Added `data-bs-backdrop="true"` and `style="z-index: 1060;"` to return modal
- Changed modal initialization to use `backdrop: 'static'` option

**Result:** Return modal now properly appears on top of user detail modal.

---

### 2. Added Condition-Specific Input Fields

#### Perfectly Working
- No additional input required

#### Minor Dent/Minor Problem
- **New:** Text area for issue details (required)
- Field ID: `returnMinorIssueDetails`
- Container ID: `minorIssueContainer`

#### Damaged
- **Existing:** Text area for damage details (required)
- **New:** File upload for incident report photo
- Field ID: `incidentReportPhoto`
- Container ID: `damageDetailsContainer`
- **New:** Photo preview with thumbnail

---

### 3. Incident Report Photo Upload

#### Frontend (users.php)
- Added file input with `accept="image/*"`
- Added photo preview functionality with thumbnail display
- Updated `submitReturnEquipment()` to use FormData for file upload
- Added client-side validation

#### Backend (return_equipment.php)
- Handles multipart/form-data file uploads
- Validates file types (JPG, JPEG, PNG, GIF only)
- Creates unique filenames: `incident_{assignment_id}_{timestamp}.{ext}`
- Stores photos in: `/uploads/incident_reports/`
- Saves relative path to database

#### Database
- **New Column:** `item_assignments.incident_photo_path` (VARCHAR 500)
- Stores relative file path for incident photos
- NULL for returns without photos

#### SQL Migration
```sql
ALTER TABLE `item_assignments` 
ADD COLUMN `incident_photo_path` VARCHAR(500) NULL DEFAULT NULL 
COMMENT 'Path to incident report photo for damaged returns' 
AFTER `damage_details`;
```

---

### 4. Google Sheets Integration
Updated `return_equipment.php` to log incident photo URL to Google Sheets:
- New field: `incident_photo_url`
- Format: `{hostname}/{photo_path}`
- Logged when equipment is returned

---

## File Structure

### Modified Files
1. **users.php** - Frontend UI and JavaScript
2. **return_equipment.php** - Backend processing and file upload

### New Files
1. **add_incident_photo_column.sql** - Database migration
2. **uploads/incident_reports/README.md** - Directory documentation

### New Directories
- `uploads/incident_reports/` - Stores incident report photos

---

## Usage Flow

### For Users Returning Equipment

1. **Navigate to Users page** (`users.php`)
2. **Click on a user** to open user detail modal
3. **View current equipment** in the modal
4. **Click "Return" button** for an item
5. **Return modal opens** (on top of user detail modal)
6. **Select condition:**
   - **Perfectly Working** → Just confirm
   - **Minor Dent/Minor Problem** → Fill issue details
   - **Damaged** → Fill damage details + optionally upload photo
7. **Submit** - Equipment is returned and logged

### For Damaged Equipment

When "Damaged" is selected:
1. Damage details text area appears (required)
2. Photo upload field appears below
3. Click "Choose File" to select incident report photo
4. Photo preview shows selected image
5. Click "Confirm Return"
6. Photo is uploaded to server
7. Photo path saved to database
8. Photo URL logged to Google Sheets

---

## Technical Details

### File Upload Settings
- **Max Size:** Controlled by PHP settings (default: 2MB)
- **Allowed Types:** JPG, JPEG, PNG, GIF
- **Directory:** `uploads/incident_reports/`
- **Permissions:** 0755 (created automatically)

### Security Considerations
- File type validation (extension whitelist)
- Unique filename generation (prevents overwriting)
- Directory created with proper permissions
- Upload errors handled gracefully

### Data Storage
```
item_assignments table:
- return_condition: 'perfectly-working' | 'minor-issue' | 'damaged'
- damage_details: Text description (used for both minor-issue and damaged)
- incident_photo_path: 'uploads/incident_reports/incident_123_1234567890.jpg'
```

### Google Sheets Fields
```javascript
{
  'item_name': 'Laptop Dell Latitude 5420',
  'category': 'Laptop',
  'return_condition': 'damaged',
  'damage_details': 'Screen cracked on left corner',
  'incident_photo_url': 'example.com/uploads/incident_reports/incident_123_1234567890.jpg',
  // ... other fields
}
```

---

## Testing Checklist

- [x] Modal z-index fixed - return modal appears above user detail modal
- [x] Perfectly Working condition - no extra fields shown
- [x] Minor Issue condition - shows issue details text area
- [x] Damaged condition - shows damage details + photo upload
- [x] Photo preview works when file selected
- [x] File upload validates file types
- [x] Photos saved to correct directory
- [x] Photo path saved to database
- [x] Return process works without photo (optional)
- [x] Return process works with photo
- [x] Google Sheets receives photo URL
- [x] Database migration completed

---

## Future Enhancements

Potential improvements for future versions:

1. **Photo Gallery** - View all incident photos for an item
2. **Photo Compression** - Automatically compress large images
3. **Multiple Photos** - Allow multiple photos per incident
4. **Photo Viewer** - Lightbox/modal to view full-size photos
5. **Export to PDF** - Generate incident report PDFs with photos
6. **Cloud Storage** - Store photos in cloud (AWS S3, Google Cloud Storage)
7. **Photo Management** - Admin page to view/delete all incident photos
8. **Notification System** - Email alerts when damaged equipment returned

---

## Troubleshooting

### Modal Not Appearing
- Check browser console for JavaScript errors
- Ensure Bootstrap 5.3.0 is loaded
- Verify modal z-index CSS is applied

### File Upload Failing
- Check PHP upload settings in `php.ini`:
  - `upload_max_filesize`
  - `post_max_size`
  - `file_uploads = On`
- Verify `uploads/incident_reports/` directory exists and is writable
- Check server error logs

### Photo Not Saving to Database
- Run the SQL migration: `add_incident_photo_column.sql`
- Verify `incident_photo_path` column exists in `item_assignments` table
- Check PHP error logs for database errors

---

## Support

For issues or questions, check:
1. Browser console (F12) for JavaScript errors
2. PHP error logs (`xampp/php/logs/php_error_log`)
3. Apache error logs (`xampp/apache/logs/error.log`)
4. Database connection and column existence
