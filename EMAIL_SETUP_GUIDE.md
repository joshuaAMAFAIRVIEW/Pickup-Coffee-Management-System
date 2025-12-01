# Email Setup Guide - Pickup Coffee Management System

## Overview
Your system is now configured to send emails to newly created users with their login credentials. Follow this guide to complete the email setup.

## Quick Start

### Option 1: Gmail SMTP (Recommended - Easiest)

#### Step 1: Create Gmail App Password
1. Go to your Google Account: https://myaccount.google.com/
2. Click on **Security** in the left sidebar
3. Under "How you sign in to Google", enable **2-Step Verification** (if not already enabled)
4. After enabling 2-Step Verification, go back to Security
5. Under "How you sign in to Google", click **App passwords**
6. Select app: **Mail**
7. Select device: **Windows Computer** (or Other)
8. Click **Generate**
9. **Copy the 16-character password** (it will look like: `abcd efgh ijkl mnop`)

#### Step 2: Configure SMTP Settings
1. Open the file: `smtp_config.php`
2. Find the Gmail configuration section
3. Update these values:
   ```php
   // === GMAIL CONFIGURATION ===
   define('SMTP_ENABLED', true);              // Change false to true
   define('SMTP_HOST', 'smtp.gmail.com');
   define('SMTP_PORT', 587);
   define('SMTP_USER', 'your-email@gmail.com');        // ← YOUR GMAIL ADDRESS
   define('SMTP_PASS', 'your-app-password');           // ← THE 16-CHAR PASSWORD FROM STEP 1
   define('SMTP_FROM_EMAIL', 'your-email@gmail.com');  // ← YOUR GMAIL ADDRESS
   define('SMTP_FROM_NAME', 'Pickup Coffee');
   ```

4. **Example:**
   ```php
   define('SMTP_ENABLED', true);
   define('SMTP_HOST', 'smtp.gmail.com');
   define('SMTP_PORT', 587);
   define('SMTP_USER', 'admin@pickupcoffee.com');
   define('SMTP_PASS', 'abcd efgh ijkl mnop');  // The app password (without spaces)
   define('SMTP_FROM_EMAIL', 'admin@pickupcoffee.com');
   define('SMTP_FROM_NAME', 'Pickup Coffee');
   ```

5. **Remove spaces from the app password!** It should be 16 characters like: `abcdefghijklmnop`

6. Save the file

#### Step 3: Test
1. Go to Users page in your system
2. Click "Add New User"
3. Fill in the details with a real email address
4. Click "Create User"
5. Check if the success message says "email has been sent" instead of "saved to email_logs folder"

---

### Option 2: Office 365 / Outlook.com SMTP

#### Step 1: Enable SMTP Authentication
1. Log in to your Office 365 account
2. Go to your account settings
3. Ensure SMTP authentication is enabled

#### Step 2: Configure SMTP Settings
1. Open `smtp_config.php`
2. Find the Office 365 configuration section:
   ```php
   // === OFFICE 365 CONFIGURATION ===
   define('SMTP_ENABLED', true);
   define('SMTP_HOST', 'smtp.office365.com');
   define('SMTP_PORT', 587);
   define('SMTP_USER', 'your-email@yourdomain.com');
   define('SMTP_PASS', 'your-password');
   define('SMTP_FROM_EMAIL', 'your-email@yourdomain.com');
   define('SMTP_FROM_NAME', 'Pickup Coffee');
   ```

3. Replace with your actual Office 365 credentials
4. Save the file

---

## Troubleshooting

### Email not sending?

1. **Check smtp_config.php**
   - Make sure `SMTP_ENABLED` is set to `true`
   - Verify all settings have actual values (no placeholders)
   - Remove any spaces from the Gmail App Password

2. **Gmail App Password not working?**
   - Make sure 2-Step Verification is enabled
   - The app password should be 16 characters (remove spaces)
   - Try generating a new app password

3. **Port blocked?**
   - Some networks block port 587
   - Try changing `SMTP_PORT` to `465` (and you may need to modify SimpleMailer.php)
   - Contact your IT department

4. **Check email_logs folder**
   - If email sending fails, a copy is saved to `email_logs/`
   - You can manually forward these emails to users
   - Files are named: `user_[employee_number]_[timestamp].html`

### Still not working?

1. Check XAMPP error logs: `C:\xampp\apache\logs\error.log`
2. Make sure your antivirus/firewall isn't blocking SMTP connections
3. Try accessing the system from `http://localhost` instead of `127.0.0.1`

---

## How It Works

### When you create a new user:

1. System creates the user account in the database
2. System generates a beautiful HTML email with:
   - Pickup Coffee branding
   - User's credentials (username & password)
   - Employee number and details
   - Direct login link
3. If SMTP is configured correctly:
   - Email is sent immediately to the user's email address
   - Success message: "Onboarding email has been sent"
4. If SMTP fails:
   - Email is saved to `email_logs/` folder
   - You can open the `.html` file and forward it manually
   - Text file with credentials is also saved

---

## Security Notes

⚠️ **Important Security Information:**

1. **Never commit smtp_config.php to Git/GitHub**
   - It contains your email password
   - Already added to `.gitignore`

2. **Use App Passwords (not your actual Gmail password)**
   - App passwords are safer
   - Can be revoked individually
   - Don't give access to your full account

3. **Limit file permissions on smtp_config.php**
   - Only admin should be able to read this file

4. **Regular password changes**
   - Change your app passwords periodically
   - Revoke unused app passwords

---

## Quick Reference

### Files Created
- `smtp_config.php` - SMTP configuration (you need to edit this)
- `SimpleMailer.php` - Email sending class (don't edit)
- `EMAIL_SETUP_GUIDE.md` - This guide
- `email_logs/` - Folder where emails are saved if sending fails

### What to Edit
Only edit `smtp_config.php` - just add your Gmail address and app password!

### What NOT to Edit
- `SimpleMailer.php` - Email sending engine
- `create_user.php` - Already configured to use SMTP

---

## Support

If you need help:
1. Check the troubleshooting section above
2. Review the email_logs folder for saved emails
3. Check XAMPP Apache error logs
4. Make sure smtp_config.php has valid credentials

---

**Last Updated:** <?= date('Y-m-d') ?>
