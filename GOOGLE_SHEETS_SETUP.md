# Google Sheets Apps Script Setup

## Step 1: Create Google Spreadsheet

1. Go to Google Sheets and create a new spreadsheet
2. Name it "Equipment Management Log" (or any name you prefer)
3. Create two tabs:
   - **Release** (for equipment assignments/releases)
   - **Return** (for equipment returns)

## Step 2: Set Up Sheet Headers

### Release Tab Headers (Row 1):
| Timestamp | Item Name | Category | User Name | Username | Department | Region | Assigned At | Item Condition | Notes | Serial Number | Other Attributes |

### Return Tab Headers (Row 1):
| Timestamp | Item Name | Category | User Name | Username | Department | Region | Assigned At | Returned At | Return Condition | Damage Details | Serial Number | Other Attributes |

## Step 3: Deploy Apps Script

1. In your Google Sheet, go to **Extensions > Apps Script**
2. Delete any default code
3. Copy and paste the code below
4. Click **Deploy > New deployment**
5. Choose type: **Web app**
6. Settings:
   - Execute as: **Me**
   - Who has access: **Anyone**
7. Click **Deploy**
8. Copy the **Web app URL**
9. Paste the URL in `google_sheets_logger.php` replacing `YOUR_GOOGLE_APPS_SCRIPT_WEB_APP_URL_HERE`

## Apps Script Code

```javascript
// Google Apps Script for Equipment Management Logging

function doPost(e) {
  try {
    // Parse incoming JSON data
    const data = JSON.parse(e.postData.contents);
    const type = data.type; // 'release' or 'return'
    const logData = data.data;
    
    // Get the active spreadsheet
    const ss = SpreadsheetApp.getActiveSpreadsheet();
    
    // Get the appropriate sheet based on type
    let sheet;
    if (type === 'release') {
      sheet = ss.getSheetByName('Release');
    } else if (type === 'return') {
      sheet = ss.getSheetByName('Return');
    } else {
      throw new Error('Invalid type. Must be "release" or "return"');
    }
    
    if (!sheet) {
      throw new Error(`Sheet "${type}" not found`);
    }
    
    // Parse attributes JSON
    let attributes = {};
    try {
      attributes = JSON.parse(logData.attributes || '{}');
    } catch (err) {
      attributes = {};
    }
    
    // Extract serial number from attributes
    const serialNumber = attributes.S_N || attributes.s_n || 
                        attributes.SERIAL_NUMBER || attributes.serial_number || 
                        attributes.SN || attributes.sn || 'N/A';
    
    // Format other attributes (exclude serial number)
    const otherAttrs = Object.entries(attributes)
      .filter(([key]) => !['S_N', 's_n', 'SERIAL_NUMBER', 'serial_number', 'SN', 'sn'].includes(key))
      .map(([key, value]) => `${key}: ${value}`)
      .join(', ');
    
    // Prepare row data based on type
    let rowData;
    const timestamp = new Date();
    
    if (type === 'release') {
      rowData = [
        timestamp,
        logData.item_name || '',
        logData.category || '',
        logData.user_name || '',
        logData.username || '',
        logData.department || '',
        logData.region || '',
        logData.assigned_at || '',
        logData.item_condition || '',
        logData.notes || '',
        serialNumber,
        otherAttrs
      ];
    } else { // return
      rowData = [
        timestamp,
        logData.item_name || '',
        logData.category || '',
        logData.user_name || '',
        logData.username || '',
        logData.department || '',
        logData.region || '',
        logData.assigned_at || '',
        logData.returned_at || '',
        logData.return_condition || '',
        logData.damage_details || '',
        serialNumber,
        otherAttrs
      ];
    }
    
    // Append the row to the sheet
    sheet.appendRow(rowData);
    
    // Return success response
    return ContentService.createTextOutput(JSON.stringify({
      success: true,
      message: `Data logged to ${type} sheet successfully`
    })).setMimeType(ContentService.MimeType.JSON);
    
  } catch (error) {
    // Return error response
    return ContentService.createTextOutput(JSON.stringify({
      success: false,
      error: error.message
    })).setMimeType(ContentService.MimeType.JSON);
  }
}

// Test function (optional - for debugging)
function testPost() {
  const testData = {
    type: 'release',
    data: {
      item_name: 'Test Laptop',
      category: 'LAPTOP',
      user_name: 'John Doe',
      username: 'jdoe',
      department: 'IT',
      region: 'NCR',
      assigned_at: '2025-12-01 10:00:00',
      item_condition: 'Brand New',
      notes: 'Test note',
      attributes: '{"S_N":"TEST123","Brand":"Dell","Model":"XPS 15"}'
    }
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

## Step 4: Configure PHP

1. Open `google_sheets_logger.php`
2. Replace `YOUR_GOOGLE_APPS_SCRIPT_WEB_APP_URL_HERE` with your actual Web App URL from Step 3

Example:
```php
$webAppUrl = 'https://script.google.com/macros/s/AKfycbx.../exec';
```

## Step 5: Test

1. Assign an equipment to a user
2. Check the **Release** tab in your Google Sheet
3. Return the equipment with condition details
4. Check the **Return** tab in your Google Sheet

## Troubleshooting

- If data doesn't appear, check Apache error logs: `c:\xampp\apache\logs\error.log`
- Make sure the sheet names are exactly "Release" and "Return"
- Verify the Web App is deployed with "Anyone" access
- Check that headers are in Row 1 of both sheets

## Security Notes

- The Web App URL should be kept secure
- Consider adding authentication tokens for production use
- Monitor the Google Apps Script execution logs for any issues
