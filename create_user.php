<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: users.php'); exit; }

$username = trim($_POST['username'] ?? '');
$employee_number = trim($_POST['employee_number'] ?? '');
$password = $_POST['password'] ?? '';
$email = trim($_POST['email'] ?? '');
$role = $_POST['role'] ?? 'staff';
$first = trim($_POST['first_name'] ?? '');
$middle = trim($_POST['middle_name'] ?? '');
$last = trim($_POST['last_name'] ?? '');
$position = trim($_POST['position'] ?? '');
$department = trim($_POST['department'] ?? '');
$store = trim($_POST['store'] ?? '');
$region = trim($_POST['region'] ?? '');

if ($username === '' || $employee_number === '' || $password === '' || $email === '') {
  $_SESSION['flash_error'] = 'Username, employee number, password and email are required';
  header('Location: users.php'); exit;
}

$pdo = $GLOBALS['pdo'];
try {
  // Check if employee number already exists
  $checkStmt = $pdo->prepare('SELECT id FROM users WHERE employee_number = ?');
  $checkStmt->execute([$employee_number]);
  if ($checkStmt->fetch()) {
    $_SESSION['flash_error'] = 'Employee number already exists';
    header('Location: users.php'); exit;
  }

  $hash = password_hash($password, PASSWORD_DEFAULT);
  $ins = $pdo->prepare('INSERT INTO users (username, employee_number, password, role, first_name, middle_name, last_name, position, department, store, email, region) VALUES (:u,:empnum,:p,:r,:fn,:mn,:ln,:pos,:dept,:store,:email,:region)');
  $ins->execute([':u'=>$username,':empnum'=>$employee_number,':p'=>$hash,':r'=>$role,':fn'=>$first,':mn'=>$middle,':ln'=>$last,':pos'=>$position,':dept'=>$department,':store'=>$store,':email'=>$email,':region'=>$region]);

  // Prepare and send onboarding email
  $emailSent = false;
  $emailError = '';
  
  try {
    // Load SMTP configuration
    if (file_exists(__DIR__ . '/smtp_config.php')) {
      require_once __DIR__ . '/smtp_config.php';
    }
    
    // Check if SMTP is configured
    $smtpConfigured = defined('SMTP_ENABLED') && SMTP_ENABLED === true 
                      && defined('SMTP_HOST') && SMTP_HOST !== ''
                      && defined('SMTP_USER') && SMTP_USER !== ''
                      && defined('SMTP_PASS') && SMTP_PASS !== '';
    
    $subject = 'Welcome to Pickup Coffee - Account Created';
    $fullName = trim($first . ' ' . $last) ?: $username;
    
    // Get the full URL for the logo
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    $logoUrl = $protocol . '://' . $host . $path . '/assets/img/PICKUP-Horizontal-White.png';
    $loginUrl = $protocol . '://' . $host . $path . '/login.php';
    
    $body = "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <link href='https://fonts.googleapis.com/css2?family=Jost:wght@300;400;600;700&display=swap' rel='stylesheet'>
    <style>
        body { 
            margin: 0; 
            padding: 0; 
            font-family: 'Jost', sans-serif; 
            background-color: #f4f4f4;
            line-height: 1.6;
        }
        .email-container { 
            max-width: 600px; 
            margin: 20px auto; 
            background: #ffffff; 
            border-radius: 8px; 
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header { 
            background: linear-gradient(135deg, #A9C27F 0%, #8BA862 100%); 
            padding: 40px 20px; 
            text-align: center;
        }
        .header img {
            max-width: 250px;
            height: auto;
        }
        .content { 
            padding: 40px 30px;
            color: #333;
        }
        .greeting {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        .credentials-box {
            background: #f9f9f9;
            border: 2px solid #A9C27F;
            border-radius: 8px;
            padding: 25px;
            margin: 30px 0;
        }
        .credentials-box h3 {
            color: #A9C27F;
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 18px;
        }
        .credential-item {
            background: white;
            padding: 12px 15px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid #A9C27F;
        }
        .credential-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .credential-value {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-top: 5px;
        }
        .highlight-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .highlight-box strong {
            color: #A9C27F;
        }
        .btn-login {
            display: inline-block;
            background: linear-gradient(135deg, #A9C27F 0%, #8BA862 100%);
            color: white;
            text-decoration: none;
            padding: 14px 35px;
            border-radius: 25px;
            font-weight: 600;
            margin: 20px 0;
            box-shadow: 0 4px 6px rgba(169, 194, 127, 0.3);
        }
        .footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            color: #666;
            font-size: 13px;
            border-top: 1px solid #e0e0e0;
        }
        .footer-logo {
            color: #A9C27F;
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 10px;
        }
        .divider {
            height: 1px;
            background: #e0e0e0;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class='email-container'>
        <!-- Header with Logo -->
        <div class='header'>
            <img src='" . $logoUrl . "' alt='Pickup Coffee' />
        </div>
        
        <!-- Email Content -->
        <div class='content'>
            <div class='greeting'>Hello " . htmlspecialchars($fullName) . ",</div>
            
            <p>Welcome to <strong>Pickup Coffee Inventory Management System</strong>!</p>
            
            <p>Your account has been successfully created. Below are your account details:</p>
            
            <!-- User Details -->
            <div class='credentials-box'>
                <h3>📋 Your Account Information</h3>
                
                <div class='credential-item'>
                    <div class='credential-label'>Employee Number</div>
                    <div class='credential-value'>" . htmlspecialchars($employee_number) . "</div>
                </div>
                
                <div class='credential-item'>
                    <div class='credential-label'>Full Name</div>
                    <div class='credential-value'>" . htmlspecialchars($fullName) . "</div>
                </div>
                
                <div class='credential-item'>
                    <div class='credential-label'>Email Address</div>
                    <div class='credential-value'>" . htmlspecialchars($email) . "</div>
                </div>
                
                <div class='credential-item'>
                    <div class='credential-label'>Department</div>
                    <div class='credential-value'>" . htmlspecialchars($department ?: 'Not specified') . "</div>
                </div>
                
                <div class='credential-item'>
                    <div class='credential-label'>Role</div>
                    <div class='credential-value'>" . htmlspecialchars(ucfirst($role)) . "</div>
                </div>
            </div>
            
            <!-- Login Credentials Highlight -->
            <div class='highlight-box'>
                <p style='margin: 0 0 15px 0;'><strong>🔑 Your Login Credentials:</strong></p>
                <p style='margin: 8px 0;'><strong>Username:</strong> <span style='background: #fff; padding: 4px 10px; border-radius: 4px; font-family: monospace; font-size: 16px;'>" . htmlspecialchars($username) . "</span></p>
                <p style='margin: 8px 0;'><strong>Password:</strong> <span style='background: #fff; padding: 4px 10px; border-radius: 4px; font-family: monospace; font-size: 16px;'>" . htmlspecialchars($password) . "</span></p>
            </div>
            
            <p style='margin: 25px 0;'><strong>Important Notes:</strong></p>
            <ul style='color: #666; line-height: 1.8;'>
                <li>Please keep your login credentials secure and confidential</li>
                <li>Do not share your password with anyone</li>
                <li>Use these credentials to access the inventory system</li>
            </ul>
            
            <div class='divider'></div>
            
            <div style='text-align: center;'>
                <p>Ready to get started?</p>
                <a href='" . $loginUrl . "' class='btn-login'>Login to Your Account</a>
            </div>
        </div>
        
        <!-- Footer -->
        <div class='footer'>
            <div class='footer-logo'>PICKUP COFFEE</div>
            <p>Inventory Management System</p>
            <div class='divider'></div>
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>If you have any questions or need assistance, please contact your system administrator.</p>
            <p style='margin-top: 20px; font-size: 11px; color: #999;'>© " . date('Y') . " Pickup Coffee. All rights reserved.</p>
        </div>
    </div>
</body>
</html>";
    
    // Try to send email using SMTP if configured
    if ($smtpConfigured) {
      require_once __DIR__ . '/SimpleMailer.php';
      
      $mailer = new SimpleMailer(
        SMTP_HOST,
        SMTP_PORT,
        SMTP_USER,
        SMTP_PASS,
        SMTP_FROM_EMAIL,
        SMTP_FROM_NAME
      );
      
      $emailSent = $mailer->send($email, $subject, $body);
      
      if (!$emailSent) {
        $emailError = 'SMTP sending failed - check your SMTP configuration';
      }
    } else {
      $emailError = 'SMTP not configured - please edit smtp_config.php';
    }
    
    // If mail fails, save email to file for manual review
    if (!$emailSent) {
      // Create emails directory if it doesn't exist
      $emailDir = __DIR__ . '/email_logs';
      if (!file_exists($emailDir)) {
        mkdir($emailDir, 0755, true);
      }
      
      // Save email to file
      $filename = $emailDir . '/user_' . $employee_number . '_' . date('Y-m-d_His') . '.html';
      file_put_contents($filename, $body);
      
      // Also create a simple text version with credentials
      $textFile = $emailDir . '/user_' . $employee_number . '_credentials.txt';
      $textContent = "PICKUP COFFEE - New User Account\n";
      $textContent .= "================================\n\n";
      $textContent .= "Employee Number: " . $employee_number . "\n";
      $textContent .= "Full Name: " . $fullName . "\n";
      $textContent .= "Username: " . $username . "\n";
      $textContent .= "Password: " . $password . "\n";
      $textContent .= "Email: " . $email . "\n";
      $textContent .= "Department: " . ($department ?: 'Not specified') . "\n";
      $textContent .= "Role: " . ucfirst($role) . "\n\n";
      $textContent .= "Login URL: " . $loginUrl . "\n";
      file_put_contents($textFile, $textContent);
    }
  } catch (Exception $e) {
    // Email failed but user still created
    $emailSent = false;
    $emailError = $e->getMessage();
  }

  if ($emailSent) {
    $_SESSION['flash_success'] = 'User created successfully! Onboarding email has been sent to ' . htmlspecialchars($email);
  } else {
    $message = 'User created successfully! ';
    if ($emailError) {
      $message .= '(Email not sent: ' . htmlspecialchars($emailError) . ' - ';
    } else {
      $message .= '(';
    }
    $message .= 'Credentials saved to email_logs folder - please send manually)';
    $_SESSION['flash_success'] = $message;
  }
} catch (Exception $e) {
  $_SESSION['flash_error'] = 'Error creating user: ' . $e->getMessage();
}
header('Location: users.php'); exit;
