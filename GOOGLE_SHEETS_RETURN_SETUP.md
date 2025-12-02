# Google Sheets Return Tab Setup Guide

## Overview
The Return tab is organized into **3 sections** to categorize returned equipment by condition:
1. **Perfectly Working** - Equipment returned in perfect condition
2. **Minor Dent/Problem** - Equipment with minor issues
3. **Damaged** - Equipment with significant damage

## Initial Setup

### Step 1: Open Your Google Sheet
Open the "Equipment Management Log" spreadsheet with the Release and Return tabs.

### Step 2: Set Up Return Tab Structure

Go to the **Return** tab and set up the following structure:

---

#### SECTION 1: PERFECTLY WORKING
**Row 1 - Headers:**
```
Timestamp | Item Name | Category | User Name | Username | Department | Region | Assigned At | Returned At | Serial Number | Other Attributes | Incident Photo
```

Leave this section ready - items will be added below the header automatically.

---

**Row X (leave 2-3 empty rows after last perfectly working item)**

#### SECTION 2: MINOR DENT/PROBLEM
**Header Row:**
```
=== MINOR DENT/PROBLEM ===
```

**Next Row - Headers:**
```
Timestamp | Item Name | Category | User Name | Username | Department | Region | Assigned At | Returned At | Issue Details | Serial Number | Other Attributes
```

---

**Row Y (leave 2-3 empty rows after last minor issue item)**

#### SECTION 3: DAMAGED
**Header Row:**
```
=== DAMAGED ===
```

**Next Row - Headers:**
```
Timestamp | Item Name | Category | User Name | Username | Department | Region | Assigned At | Returned At | Damage Details | Serial Number | Other Attributes | Incident Photo
```

---

## How It Works

### When Equipment is Returned:

1. **Perfectly Working Condition**
   - Item logged to Section 1
   - No damage details needed
   - No incident photo

2. **Minor Dent/Problem Condition**
   - Item logged to Section 2
   - Issue details included
   - No incident photo required

3. **Damaged Condition**
   - Item logged to Section 3
   - Damage details included
   - Incident photo URL included (if uploaded)

## Apps Script Behavior

The Apps Script will:
- Automatically detect the return condition
- Insert the row in the correct section
- Create section headers if they don't exist
- Maintain proper spacing between sections
- Include appropriate fields based on condition

## Manual Setup (Alternative)

If you prefer to manually create the structure before any returns:

1. **Row 1:** Section 1 headers (Perfectly Working)
2. **Row 2-10:** Leave empty for perfectly working items
3. **Row 11:** Type: `=== MINOR DENT/PROBLEM ===`
4. **Row 12:** Section 2 headers
5. **Row 13-20:** Leave empty for minor issue items
6. **Row 21:** Type: `=== DAMAGED ===`
7. **Row 22:** Section 3 headers
8. **Row 23+:** Leave empty for damaged items

## Example Layout

```
Row 1:  [Timestamp] [Item Name] [Category] ... [Incident Photo]
Row 2:  2025-12-02  Laptop XPS   LAPTOP    ... (empty)
Row 3:  2025-12-02  iPhone 13    PHONE     ... (empty)
Row 4:  (empty)
Row 5:  (empty)
Row 6:  === MINOR DENT/PROBLEM ===
Row 7:  [Timestamp] [Item Name] [Category] ... [Issue Details] ...
Row 8:  2025-12-02  iPad Pro     TABLET    ... Small dent on corner
Row 9:  (empty)
Row 10: (empty)
Row 11: === DAMAGED ===
Row 12: [Timestamp] [Item Name] [Category] ... [Damage Details] ... [Incident Photo]
Row 13: 2025-12-02  MacBook Pro  LAPTOP    ... Screen cracked ... https://...
```

## Benefits

✅ **Organized** - Easy to see equipment condition at a glance
✅ **Filtered** - Can quickly find items needing repair
✅ **Tracked** - Incident photos linked for damaged items
✅ **Automated** - Script handles organization automatically
✅ **Flexible** - Can manually adjust sections if needed

## Redeploy Apps Script

After updating the Apps Script code in GOOGLE_SHEETS_SETUP.md:

1. Go to your Google Sheet
2. Extensions > Apps Script
3. Replace the code with the updated version
4. Click **Deploy > Manage deployments**
5. Click the edit icon (pencil)
6. Change **Version** to "New version"
7. Add description: "Added 3-section return organization"
8. Click **Deploy**

The Web App URL remains the same - no need to update `google_sheets_logger.php`

## Testing

Test each condition:

1. **Test Perfectly Working:**
   - Return an item with "Perfectly Working" condition
   - Check Return tab Section 1
   - Verify row appears with correct data

2. **Test Minor Issue:**
   - Return an item with "Minor Dent/Minor Problem" condition
   - Add issue details
   - Check Return tab Section 2
   - Verify issue details appear

3. **Test Damaged:**
   - Return an item with "Damaged" condition
   - Add damage details
   - Upload incident photo
   - Check Return tab Section 3
   - Verify damage details and photo URL appear

## Troubleshooting

**Items not appearing in correct section:**
- Check that section headers exist in Return tab
- Verify Apps Script is deployed with latest code
- Check Apache logs for Google Sheets API errors

**Section headers not being created:**
- Manually create the structure following the guide above
- Ensure sheet name is exactly "Return"

**Photo URLs not appearing:**
- Verify file upload is working (check uploads/incident_reports/ folder)
- Check that $_SERVER['HTTP_HOST'] is set correctly
- Ensure incident_photo_url is being sent in the API call
