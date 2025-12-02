# Bulk Import Apps Script Setup

## Purpose
This Apps Script deletes rows from your import spreadsheet after items are successfully imported to the system.

## Setup Instructions

### 1. Open Your Import Spreadsheet
Open the spreadsheet you use for bulk importing items:
https://docs.google.com/spreadsheets/d/1PxmVtCPTl82B2UQw72oD7rhMTef0Lpr1gyp47Uk1gzQ/edit

### 2. Create Apps Script
1. In your spreadsheet: **Extensions > Apps Script**
2. Create a new file or replace the default code
3. Paste the code below
4. Click **Deploy > New deployment**
5. Type: **Web app**
6. Settings:
   - Execute as: **Me**
   - Who has access: **Anyone**
7. Click **Deploy**
8. **Copy the Web App URL**

### 3. Configure PHP
Open `bulk_import_items.php` and replace line 17:
```php
$appsScriptUrl = 'PASTE_YOUR_WEB_APP_URL_HERE';
```

---

## Apps Script Code

```javascript
/**
 * Google Apps Script - Delete Imported Rows
 * Deletes rows from spreadsheet after successful import
 */

function doPost(e) {
  try {
    const data = JSON.parse(e.postData.contents);
    const action = data.action;
    
    if (action !== 'deleteRows') {
      throw new Error('Invalid action');
    }
    
    const spreadsheetId = data.spreadsheetId;
    const sheetName = data.sheetName;
    const rowNumbers = data.rows; // Array of row numbers to delete (e.g., [2, 3, 5])
    
    if (!rowNumbers || rowNumbers.length === 0) {
      return ContentService.createTextOutput(JSON.stringify({
        success: true,
        message: 'No rows to delete'
      })).setMimeType(ContentService.MimeType.JSON);
    }
    
    // Open the spreadsheet
    const ss = SpreadsheetApp.openById(spreadsheetId);
    const sheet = ss.getSheetByName(sheetName);
    
    if (!sheet) {
      throw new Error('Sheet "' + sheetName + '" not found');
    }
    
    // Sort row numbers in descending order (delete from bottom to top)
    // This prevents row number shifts during deletion
    const sortedRows = rowNumbers.sort((a, b) => b - a);
    
    let deletedCount = 0;
    
    // Delete each row
    for (let i = 0; i < sortedRows.length; i++) {
      const rowNum = sortedRows[i];
      
      // Validate row number (must be >= 2, row 1 is headers)
      if (rowNum >= 2 && rowNum <= sheet.getLastRow()) {
        sheet.deleteRow(rowNum);
        deletedCount++;
      }
    }
    
    // Return success response
    return ContentService.createTextOutput(JSON.stringify({
      success: true,
      message: 'Deleted ' + deletedCount + ' rows successfully',
      deletedCount: deletedCount
    })).setMimeType(ContentService.MimeType.JSON);
    
  } catch (error) {
    // Return error response
    return ContentService.createTextOutput(JSON.stringify({
      success: false,
      error: error.message
    })).setMimeType(ContentService.MimeType.JSON);
  }
}

/**
 * Test function (optional - for debugging)
 */
function testDeleteRows() {
  const testData = {
    action: 'deleteRows',
    spreadsheetId: '1PxmVtCPTl82B2UQw72oD7rhMTef0Lpr1gyp47Uk1gzQ',
    sheetName: 'Sheet1',
    rows: [3, 2] // Delete rows 2 and 3
  };
  
  const e = {
    postData: {
      contents: JSON.stringify(testData)
    }
  };
  
  const result = doPost(e);
  Logger.log(result.getContent());
}
```

---

## How It Works

1. **After successful import**, the PHP system sends a request to this Apps Script with row numbers
2. **Rows are sorted in descending order** (bottom to top) to prevent row shifting
3. **Each row is deleted** if it exists and is >= row 2 (preserving headers)
4. **Returns confirmation** of how many rows were deleted

## Important Notes

- ✅ Row 1 (headers) is **never deleted**
- ✅ Only **successfully imported** rows are deleted
- ✅ Rows with errors or skipped items **remain in the spreadsheet**
- ✅ Duplicate S/N entries are skipped and **not deleted**
- ✅ Invalid categories are skipped and **not deleted**

## Testing

1. Add some test data to your spreadsheet (rows 2, 3, 4)
2. Run the bulk import from the system
3. Check the spreadsheet - successfully imported rows should be gone
4. Failed/skipped rows should still be there

## Troubleshooting

- **"Sheet not found"** - Make sure `$sheetName` in `bulk_import_items.php` matches your sheet name exactly
- **Nothing deleted** - Check that the Apps Script URL is correctly set in `bulk_import_items.php`
- **Permission denied** - Ensure the Apps Script is deployed with "Execute as: Me" and "Anyone" access
- **Rows not deleting** - Check Apps Script execution logs: **View > Executions**

## Security

- The spreadsheet ID is hardcoded in PHP (safe)
- Only row numbers are sent to the script (no sensitive data)
- Apps Script validates row numbers (must be >= 2)
- Consider adding authentication tokens for production use
