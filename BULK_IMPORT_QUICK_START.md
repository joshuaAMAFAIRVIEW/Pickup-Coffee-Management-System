# Bulk Import Quick Setup

## What You Need

1. **Import Spreadsheet** (you already have this):
   - ID: `1PxmVtCPTl82B2UQw72oD7rhMTef0Lpr1gyp47Uk1gzQ`
   - Must have "anyone with link can view" access
   - Format: Row 1 = Headers, Data starts at Row 2
   - Column A = Category (must match database categories, case-insensitive)

2. **Apps Script for Deleting Rows** (needs setup):
   - See `BULK_IMPORT_APPS_SCRIPT.md` for full instructions

## Quick Setup (5 minutes)

### 1. Deploy Apps Script
```
1. Open your import spreadsheet
2. Extensions > Apps Script
3. Copy code from BULK_IMPORT_APPS_SCRIPT.md
4. Deploy > New deployment > Web app
5. Execute as: Me, Who has access: Anyone
6. Copy the Web App URL
```

### 2. Update PHP Config
Open `bulk_import_items.php`, find line 17 and paste your URL:
```php
$appsScriptUrl = 'https://script.google.com/macros/s/YOUR_SCRIPT_ID/exec';
```

### 3. Test It!
1. Add test items to spreadsheet (rows 2, 3, 4)
2. Go to Add Item page
3. Click 📊 Bulk Import
4. Successfully imported items will be deleted from spreadsheet
5. Failed/skipped items will remain

## Features

✅ **Duplicate Detection** - Items with same S/N are skipped (not imported, not deleted)
✅ **Category Validation** - Invalid categories are skipped (not imported, not deleted)
✅ **Smart Deletion** - Only successfully imported rows are deleted
✅ **Case-Insensitive** - "LAPTOP" matches "laptop" in database
✅ **Detailed Results** - Shows which rows succeeded/failed and why

## Spreadsheet Format Example

| CATEGORY | S/N    | MODEL  | IP        | MAC | RAM | CPU  | STORAGE |
|----------|--------|--------|-----------|-----|-----|------|---------|
| LAPTOP   | L001   | XPS 15 | 10.0.0.1  | ... | 16GB| i7   | 512GB   |
| LAPTOP   | L002   | XPS 13 | 10.0.0.2  | ... | 8GB | i5   | 256GB   |
| Desktop  | D001   | OptiPlex| 10.0.0.3 | ... | 32GB| i9   | 1TB     |

## What Happens During Import

1. **Row 2**: Category "LAPTOP" exists ✓ → Import success → **Row deleted**
2. **Row 3**: Category "LAPTOP" exists ✓, S/N unique ✓ → Import success → **Row deleted**
3. **Row 4**: Category "Desktop" not found ✗ → Skipped → **Row kept**

## Need Help?

- Full documentation: `BULK_IMPORT_APPS_SCRIPT.md`
- Google Sheets logging: `GOOGLE_SHEETS_SETUP.md`
- Check Apache logs: `c:\xampp\apache\logs\error.log`
