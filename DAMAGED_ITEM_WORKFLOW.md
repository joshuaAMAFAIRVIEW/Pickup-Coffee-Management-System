# Damaged Item Management Workflow

## Overview
This document describes the complete workflow for managing damaged items in the inventory system, including status changes and repair history tracking.

## Item Statuses
The system supports the following item statuses:
- **available**: Item is ready to be assigned
- **borrowed**: Item is currently assigned to a user
- **damaged**: Item is damaged and cannot be assigned
- **to be repair**: Item is scheduled for repair

## Workflow Steps

### 1. Item Returned as Damaged
When a user returns an item with condition "damaged":
- **File**: `return_equipment.php`
- **Process**:
  1. Damage details and photo are captured
  2. Photo is stored as BLOB in database (`incident_photo`, `incident_photo_mime`)
  3. Item status is automatically set to `damaged`
  4. Return is logged to Google Sheets "Return" tab
  5. Photo URL is included in Google Sheets as HYPERLINK formula

### 2. Damaged Item Prevention
Damaged items cannot be assigned:
- **File**: `assign_equipment.php`
- **Validation**: Checks if `status='damaged'` OR `status='to be repair'`
- **Error Message**: "Equipment is damaged and cannot be assigned. Please repair it first."

### 3. Status Change Management
Admin/Manager can change item status in inventory:
- **File**: `inventory.php`
- **UI**: Two new buttons appear for damaged/to be repair items:
  - 🔧 **Wrench Icon**: Change Status button
  - 🕒 **History Icon**: Repair History button

#### Change Status Modal
- Shows current status with item name
- Dropdown with status options:
  - Damaged
  - To Be Repair
  - Available
- Notes field for repair details
- Submits to `change_item_status.php`

### 4. Status Change API
- **File**: `change_item_status.php`
- **Method**: POST
- **Parameters**:
  - `item_id`: ID of the item
  - `new_status`: New status (damaged/to be repair/available)
  - `notes`: Optional notes about the change
- **Process**:
  1. Validates status value
  2. Gets current status from database
  3. Updates `items.status`
  4. Logs change to `item_repair_history` table
  5. Records changed_by (session user_id)
- **Response**: JSON with success/error

### 5. Repair History Tracking
- **Table**: `item_repair_history`
- **Columns**:
  - `id`: Auto-increment primary key
  - `item_id`: Item being tracked (indexed)
  - `old_status`: Previous status
  - `new_status`: New status
  - `notes`: Details about the change
  - `changed_by`: User ID who made the change
  - `changed_at`: Timestamp of change

### 6. Repair History API
- **File**: `get_repair_history.php`
- **Method**: GET
- **Parameters**: `item_id`
- **Query**: JOINs `item_repair_history` with `users` table
- **Returns**: Array of history records with:
  - Status change details (old → new)
  - Notes
  - Timestamp
  - User who made the change (username, first_name, last_name)
- **Order**: DESC by changed_at (newest first)

### 7. Repair History Modal
Shows complete timeline of status changes:
- Status badges showing old → new status
- Notes for each change
- User who made the change
- Timestamp
- Timeline format with cards

## Database Schema

### item_assignments Table (additions)
```sql
ALTER TABLE item_assignments 
ADD COLUMN returned_at DATETIME NULL,
ADD COLUMN return_condition VARCHAR(50) NULL,
ADD COLUMN damage_details TEXT NULL,
ADD COLUMN incident_photo LONGBLOB NULL,
ADD COLUMN incident_photo_mime VARCHAR(50) NULL;
```

### item_repair_history Table (new)
```sql
CREATE TABLE item_repair_history (
  id INT PRIMARY KEY AUTO_INCREMENT,
  item_id INT NOT NULL,
  old_status VARCHAR(20) NOT NULL,
  new_status VARCHAR(20) NOT NULL,
  notes TEXT,
  changed_by INT,
  changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_item_id (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## UI Components

### Inventory Page Buttons
Only visible when `status='damaged'` OR `status='to be repair'`:
- **Change Status Button**: Blue wrench icon, opens change status modal
- **Repair History Button**: Gray history icon, opens repair history modal

### Badge Colors
Status badges use Bootstrap classes:
- `badge-available`: Green (available)
- `badge-borrowed`: Yellow (borrowed)
- `badge-damaged`: Red (damaged)
- `badge-to be repair`: Orange (to be repair)

## Typical Repair Workflow Example

1. **Item Returned as Damaged**
   - User returns laptop as "damaged"
   - Takes photo of broken screen
   - Status: `borrowed` → `damaged`

2. **Sent for Repair**
   - Admin clicks "Change Status" button
   - Selects "To Be Repair"
   - Notes: "Sent to repair shop on 2024-01-15"
   - Status: `damaged` → `to be repair`

3. **Repair Completed**
   - Admin clicks "Change Status" button
   - Selects "Available"
   - Notes: "Screen replaced, fully functional"
   - Status: `to be repair` → `available`

4. **View History**
   - Click "Repair History" button
   - See complete timeline:
     - borrowed → damaged (User returned damaged)
     - damaged → to be repair (Sent to repair shop)
     - to be repair → available (Repair completed)

## Integration with Google Sheets

### Return Sheet
When item returned as damaged:
- Logged to "Return" sheet under "=== DAMAGED ===" section
- Photo included as HYPERLINK formula
- Format: `=HYPERLINK("https://domain.com/serve_photo.php?assignment_id=123", "View Photo")`

### Photo Storage
- **Database**: Photos stored as LONGBLOB in `item_assignments.incident_photo`
- **MIME Type**: Stored in `item_assignments.incident_photo_mime`
- **Serving**: `serve_photo.php?assignment_id=X` retrieves and displays photo
- **Headers**: Proper Content-Type and Content-Length set

## Security
- All APIs require active session (`session_start()`)
- SQL queries use prepared statements with bound parameters
- File uploads validated (size limit: 5MB)
- Only JPEG/PNG images allowed for incident photos
- XSS protection with `htmlspecialchars()` on output

## Error Handling
- Database transactions with rollback on error
- JSON error responses with descriptive messages
- Frontend alerts for user feedback
- Page reload on successful status change

## Files Modified/Created

### Modified Files
1. `inventory.php` - Added status change and repair history buttons + modals
2. `assign_equipment.php` - Added damaged item validation
3. `return_equipment.php` - Added status change logic for damaged returns

### Created Files
1. `change_item_status.php` - API for changing item status
2. `get_repair_history.php` - API for retrieving repair history
3. `serve_photo.php` - Endpoint for serving incident photos from database

### Database Changes
1. Added columns to `item_assignments` table
2. Created `item_repair_history` table

## Testing Checklist
- [ ] Return item as damaged with photo
- [ ] Verify status set to 'damaged' automatically
- [ ] Try to assign damaged item (should fail)
- [ ] Change status from 'damaged' to 'to be repair'
- [ ] View repair history (should show one entry)
- [ ] Change status from 'to be repair' to 'available'
- [ ] View repair history (should show two entries)
- [ ] Assign now-available item (should succeed)
- [ ] Check Google Sheets for photo HYPERLINK
- [ ] Click photo link in spreadsheet (should open photo)
