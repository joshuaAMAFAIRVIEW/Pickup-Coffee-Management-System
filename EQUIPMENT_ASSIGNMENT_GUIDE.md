# Equipment Release/Assignment Feature - User Guide

## Overview
You can now manage equipment assignments directly from the **Users** page. This allows you to easily track which equipment each employee has borrowed and assign new equipment to them.

---

## How to Use

### 1. View User Equipment History

**From the Users page:**
- **Click on any username** (it's now clickable/blue)
- A modal will open showing:
  - **User Details** (Employee #, Name, Department, Role)
  - **Currently Borrowed Equipment** (active assignments)
  - **Assignment History** (past borrowed items with dates)

**Current Borrowed Equipment shows:**
- Equipment name
- Category
- Details (S/N, MODEL, etc.)
- When it was assigned
- Notes
- **Return button** to mark equipment as returned

**Assignment History shows:**
- Equipment name
- Category  
- Assigned date
- Returned date
- Duration (how many days it was borrowed)
- Notes

---

### 2. Assign Equipment to a User (Release Equipment)

**From the Users page:**
- Find the user you want to assign equipment to
- Click the **📦 box icon** button in the Actions column
- A modal opens showing user details

**Assignment Process:**
1. **Select Category** (dropdown)
   - Choose the equipment category (Laptop, Monitor, Phone, etc.)

2. **Select Equipment** (searchable dropdown)
   - After selecting category, available equipment appears
   - **Only shows equipment with "available" status**
   - **Only shows equipment not currently assigned to anyone**
   - Type to search/filter equipment

3. **View Equipment Details** (auto-displays)
   - When you select equipment, its details appear
   - Shows S/N, MODEL, IP, MAC, etc. (depending on category modifiers)

4. **Add Notes** (optional)
   - Add any notes about this assignment
   - Example: "For remote work setup", "Temporary replacement"

5. **Click "Assign Equipment"**
   - Creates the assignment record
   - Changes equipment status from "available" to "borrowed"
   - Starts tracking the assignment

---

### 3. Return Equipment

**Two ways to return equipment:**

**Option A: From User Detail Modal**
- Click the username to open their detail modal
- Find the equipment in "Currently Borrowed Equipment" section
- Click the **Return** button next to the equipment
- Confirm the return
- Equipment status changes back to "available"

**Option B: (Recommended workflow)**
- When a staff member returns equipment, search for their account
- Click their username to see what they borrowed
- Click Return for the specific equipment

---

## Workflow Example

### Scenario: Staff Joe needs a laptop

1. **Find Joe's account:**
   - Go to Users page
   - Search for "staff_joe" in the search bar

2. **Assign laptop to Joe:**
   - Click the 📦 button next to his name
   - Select Category: "Laptop"
   - Select Equipment: "Dell Latitude 5420" (S/N: DL123456)
   - Add notes: "For remote work - Project Alpha"
   - Click "Assign Equipment"

3. **Tracking begins:**
   - Laptop status changes to "borrowed"
   - Assignment is recorded with timestamp
   - Joe's profile now shows this laptop in "Currently Borrowed"
   - Laptop won't appear in "available" lists anymore

4. **When Joe returns the laptop:**
   - Click on "staff_joe" username
   - In the modal, click "Return" next to the Dell laptop
   - Laptop status changes back to "available"
   - Assignment is closed with return timestamp
   - Assignment moves to "History" section

---

## Features

### ✅ Benefits
- **Easy tracking** - Know who has what equipment at all times
- **History logging** - Keep records of all past assignments
- **Status automation** - Equipment status updates automatically
- **Search functionality** - Quickly find equipment by typing
- **Notes system** - Document assignment purposes or conditions
- **Prevents double assignment** - Can't assign already-borrowed equipment

### 🎯 Key Points
- Only **available** equipment can be assigned
- Equipment must be **returned** before assigning to someone else
- All assignments are **timestamped** automatically
- Admins can see **full history** of who used what equipment

---

## Database Changes

The system uses the `item_assignments` table with these fields:
- `id` - Assignment ID
- `user_id` - Who borrowed the equipment
- `item_id` - Which equipment was borrowed
- `assigned_at` - When it was assigned
- `returned_at` - When it was returned (NULL = still borrowed)
- `notes` - Optional notes about the assignment

When equipment is assigned:
- Record created in `item_assignments`
- `items.status` changes to "borrowed"

When equipment is returned:
- `returned_at` is set to current timestamp
- `items.status` changes back to "available"

---

## UI Location

**Users Page (`users.php`):**
- Username column: **Clickable** (blue text) → Opens user detail modal
- Actions column: **📦 box icon** → Opens assignment modal

**Action Buttons Order:**
1. 📦 Release Equipment (green)
2. ✏️ Edit User (blue)
3. 🔑 Reset Password (yellow)
4. 🗑️ Delete User (red)

---

## Tips

1. **Quick Assignment:** Use the search bar to find users quickly, then click 📦
2. **Check Before Assigning:** Click the username first to see if they already have equipment
3. **Use Notes:** Add context like "Replacement for damaged unit" or "Temporary loan"
4. **Monitor History:** Click usernames periodically to audit equipment usage
5. **Searchable Dropdowns:** Type in the equipment dropdown to filter results quickly

---

**Last Updated:** December 1, 2025
