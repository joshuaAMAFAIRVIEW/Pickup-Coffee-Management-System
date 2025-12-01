# Email Configuration for Pickup Coffee Management System

## Current Solution

Since XAMPP doesn't have mail configured by default, the system now saves email content to the `email_logs/` folder when a user is created.

### How it works:
1. When you create a new user, the system attempts to send an email
2. If email sending fails (which it will by default), the system saves:
   - **HTML email preview**: `email_logs/user_[employee-number]_[timestamp].html`
   - **Text credentials file**: `email_logs/user_[employee-number]_credentials.txt`

### To send credentials to users:
1. Check the `email_logs/` folder after creating a user
2. Open the HTML file in a browser to see the formatted email
3. Copy the credentials from the text file
4. Send them manually via your email client

---

## Option 1: Enable Email Sending with Gmail SMTP (Recommended)

To actually send emails automatically, you'll need to configure PHP to use an SMTP server.

### Step 1: Install PHP Mailer via Composer

1. Download Composer from https://getcomposer.org/download/
2. Open terminal in your project folder:
   ```powershell
   cd C:\xampp\htdocs\Pickup-Coffee-Management-System\Pickup-Coffee-Management-System
   ```
3. Install PHPMailer:
   ```powershell
   composer require phpmailer/phpmailer
   ```

### Step 2: Create SMTP Configuration

Create a file called `smtp_config.php` with your email settings:

```php
<?php
// Gmail SMTP Settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPT', 'tls');
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password'); // Use App Password, not regular password
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');
define('SMTP_FROM_NAME', 'Pickup Coffee');
?>
```

### Step 3: Get Gmail App Password

1. Go to https://myaccount.google.com/apppasswords
2. Select "Mail" and "Windows Computer"
3. Generate a password
4. Copy the 16-character password (remove spaces)
5. Use this in `SMTP_PASS` above

### Step 4: Update create_user.php

The code is already prepared for PHPMailer. It will automatically use it if available.

---

## Option 2: Use XAMPP's Built-in Sendmail

### Step 1: Configure php.ini

1. Open `C:\xampp\php\php.ini`
2. Find and update these lines:
   ```ini
   [mail function]
   SMTP=smtp.gmail.com
   smtp_port=587
   sendmail_from=your-email@gmail.com
   sendmail_path="\"C:\xampp\sendmail\sendmail.exe\" -t"
   ```

### Step 2: Configure sendmail.ini

1. Open `C:\xampp\sendmail\sendmail.ini`
2. Update these settings:
   ```ini
   smtp_server=smtp.gmail.com
   smtp_port=587
   auth_username=your-email@gmail.com
   auth_password=your-app-password
   force_sender=your-email@gmail.com
   ```

### Step 3: Restart Apache

Restart Apache in XAMPP Control Panel for changes to take effect.

---

## Option 3: Use Office 365 / Outlook SMTP

If you have a company Office 365 account:

```php
define('SMTP_HOST', 'smtp.office365.com');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPT', 'tls');
define('SMTP_USER', 'your-email@company.com');
define('SMTP_PASS', 'your-password');
define('SMTP_FROM_EMAIL', 'your-email@company.com');
define('SMTP_FROM_NAME', 'Pickup Coffee');
```

---

## Testing

After configuration, create a test user and check if:
1. Email is received
2. OR email files are saved in `email_logs/` folder

---

## Security Note

**Never commit smtp_config.php to GitHub!** Add it to `.gitignore`:

```
smtp_config.php
email_logs/
```

---

## Current Behavior

✅ **User is always created** - even if email fails
✅ **Email content is saved** - you can manually send credentials
✅ **Success message shows** - whether email sent or saved to file
✅ **Credentials are logged** - easy to copy and send manually

The system works perfectly without email configuration - you just need to manually send the credentials from the saved files.
