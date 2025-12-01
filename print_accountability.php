<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';

$assignmentId = (int)($_GET['assignment_id'] ?? 0);

if ($assignmentId <= 0) {
    die('Invalid assignment ID');
}

// Get assignment details
$sql = "SELECT 
    ia.id as assignment_id,
    ia.assigned_at,
    ia.unassigned_at,
    ia.notes,
    i.id as item_id,
    i.display_name as item_name,
    i.attributes,
    i.item_condition,
    c.name as category_name,
    u.id as user_id,
    u.username,
    u.employee_number,
    u.first_name,
    u.last_name,
    u.department
FROM item_assignments ia
JOIN items i ON ia.item_id = i.id
JOIN categories c ON i.category_id = c.id
JOIN users u ON ia.user_id = u.id
WHERE ia.id = :assignment_id";

$stmt = $pdo->prepare($sql);
$stmt->execute([':assignment_id' => $assignmentId]);
$assignment = $stmt->fetch();

if (!$assignment) {
    die('Assignment not found');
}

// Parse attributes JSON
$attributes = [];
if ($assignment['attributes']) {
    $decoded = json_decode($assignment['attributes'], true);
    if ($decoded && is_array($decoded)) {
        $attributes = $decoded;
    }
}

// Get serial number (case-insensitive)
$serialNumber = '';
foreach (['s_n', 'S_N', 'SN', 'sn', 'serial_number', 'Serial_Number'] as $key) {
    if (isset($attributes[$key])) {
        $serialNumber = $attributes[$key];
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Equipment Accountability Form</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    @media print {
      .no-print { display: none; }
      body { margin: 0; }
      .page-break { page-break-before: always; }
    }
    body { 
      font-family: Arial, sans-serif; 
      padding: 15px;
      font-size: 11px;
      line-height: 1.45;
    }
    .accountability-form {
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;
    }
    .header-section {
      margin-bottom: 15px;
      border-bottom: 1px solid #000;
      padding-bottom: 12px;
    }
    .company-info h4 {
      margin: 0;
      font-weight: bold;
      font-size: 20px;
    }
    .form-title {
      margin: 15px 0;
      text-align: center;
      font-size: 11px;
      font-weight: bold;
      text-decoration: underline;
    }
    .section-title {
      font-weight: bold;
      font-size: 10px;
      margin: 10px 0 6px 0;
    }
    .acknowledgment-box {
      border: 1px solid #000;
      padding: 10px;
      margin: 10px 0;
      font-size: 10px;
      line-height: 1.5;
    }
    .terms-list {
      margin: 0;
      padding-left: 20px;
    }
    .terms-list li {
      margin-bottom: 5px;
    }
    ol {
      counter-reset: item;
      padding-left: 0;
    }
    ol > li {
      display: block;
      margin-bottom: 5px;
    }
    ol > li:before {
      content: counter(item, lower-alpha) ") ";
      counter-increment: item;
      font-weight: normal;
    }
  </style>
</head>
<body>
  <div class="accountability-form">
    <!-- Header -->
    <div class="header-section">
      <div style="display: flex; align-items: center; justify-content: space-between;">
        <div style="flex: 0 0 auto;">
          <img src="assets/img/LOGO-ACCOUNTABILITY.png" alt="Company Logo" style="height: 60px;">
        </div>
        <div style="flex: 1; text-align: center; padding-left: 20px;">
          <h4 style="margin: 0; font-weight: bold; font-size: 18px;">STARBREAKER CORP.</h4>
          <p style="margin: 3px 0 0 0; font-size: 10px;">FIRST LUCKY PLACE, 2259 Chino Roces Avenue Extn., Makati City</p>
        </div>
      </div>
    </div>

    <!-- Form Title -->
    <div class="form-title">EQUIPMENT RESPONSIBILITY & ACCOUNTABILITY FORM</div>

    <!-- Content Box -->
    <div style="border: 2px solid #000; padding: 12px; font-size: 11px;">
      <!-- Acknowledgement Section -->
      <div class="section-title">Acknowledgement</div>
      <p style="margin: 6px 0; line-height: 1.45;">
        I hereby acknowledge and accept the responsibility for the proper use and safekeeping of the equipment provided to me by Starbreaker Corp. for business purposes. I understand and agree to the following terms and conditions:
      </p>

      <!-- Equipment Usage and Safekeeping Section -->
      <div class="section-title">Equipment Usage and Safekeeping:</div>
      <ol class="terms-list">
        <li>The assigned equipment, including laptops and mobile phones, shall be used exclusively for company-related activities and in accordance with company policies and guidelines.</li>
        <li>It is my responsibility to protect the equipment from loss, theft, damage, or unauthorized use.</li>
        <li>I will ensure the equipment is kept in a secure and appropriate location when not in use.</li>
        <li>The equipment shall not be loaned, sold, modified, or tampered with without prior written approval from the Company.</li>
      </ol>

      <!-- Reporting of Damage or Loss Section -->
      <div class="section-title">Reporting of Damage or Loss:</div>
      <ol class="terms-list">
        <li>In the event that the assigned equipment is broken, damaged, lost, or stolen, I will report it immediately to my supervisor or the IT Department.</li>
        <li>I understand that failure to report such incidents promptly may result in disciplinary action and potential liability for the cost of replacement or repair.</li>
      </ol>

      <!-- Liability for Damage Section -->
      <div class="section-title">Liability for Damage:</div>
      <ol class="terms-list">
        <li>I accept full responsibility for any damage caused to the equipment due to my improper use, negligence, carelessness, or intentional actions.</li>
        <li>I agree to bear the cost of repair or replacement resulting from such damage, as determined by the Company.</li>
      </ol>

      <!-- Return of Equipment Section -->
      <div class="section-title">Return of Equipment:</div>
      <ol class="terms-list">
        <li>Upon termination or cessation of employment, I understand that I am required to immediately, without the need for demand, return all assigned equipment to the company.</li>
        <li>In the event that I fail to return the equipment promptly, I acknowledge and agree that the company reserves the right to withhold any outstanding backpay or final pay owed to me.</li>
      </ol>

      <!-- Monitoring and Privacy Section -->
      <div class="section-title">Monitoring and Privacy:</div>
      <ol class="terms-list">
        <li>I acknowledge that while using the assigned equipment, the Company has the right to monitor and access my IT and telecommunications usage, including but not limited to emails, internet activity, and calls, without prior notice.</li>
        <li>I acknowledge that the company has the authority to monitor without my prior knowledge or consent, and the results of monitoring will be kept confidential, in accordance with business requirements.</li>
        <li>I understand that all of the Employer's Systems are the property of the company, and I have no reasonable expectation of privacy while using them.</li>
        <li>I will be expected to comply fully with the company's information security policies and procedures. The Company may introduce new rules or modify existing ones, and I will be informed and required to comply with any such changes.</li>
      </ol>

      <!-- Company Property Section -->
      <div class="section-title">Company Property:</div>
      <ol class="terms-list">
        <li>I recognize that the assigned equipment and any accompanying accessories remain the property of Starbreaker Corp. at all times.</li>
        <li>I will not remove or attempt to remove any identification marks, labels, or proprietary software from the equipment.</li>
      </ol>

      <p style="margin-top: 10px; line-height: 1.45;">
        I have read, understood, and agree to comply with the above terms and conditions regarding the responsibility and accountability for the assigned equipment. I am aware that violation of these terms may result in disciplinary action, including termination of employment, and potential legal consequences.
      </p>
    </div>
  </div>

  <!-- PAGE 2 - Employee and Equipment Details -->
  <div class="accountability-form page-break">
    <!-- Header -->
    <div class="header-section" style="border-bottom: none;">
      <div style="display: flex; align-items: center; justify-content: space-between;">
        <div style="flex: 0 0 auto;">
          <img src="assets/img/LOGO-ACCOUNTABILITY.png" alt="Company Logo" style="height: 60px;">
        </div>
        <div style="flex: 1; text-align: center; padding-left: 20px;">
          <h4 style="margin: 0; font-weight: bold; font-size: 18px;">STARBREAKER CORP.</h4>
          <p style="margin: 3px 0 0 0; font-size: 10px;">FIRST LUCKY PLACE, 2259 Chino Roces Avenue Extn., Makati City</p>
        </div>
      </div>
    </div>

    <!-- List of Equipment Title -->
    <div style="margin: 20px 0 15px 0; font-size: 16px; font-weight: bold;">List of Equipment:</div>

    <!-- Equipment Table -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 10px;">
      <thead>
        <tr style="background-color: #f0f0f0;">
          <th style="border: 1px solid #000; padding: 8px; text-align: left; font-weight: bold;">Equipment Description/Type</th>
          <th style="border: 1px solid #000; padding: 8px; text-align: left; font-weight: bold;">Serial Number</th>
          <th style="border: 1px solid #000; padding: 8px; text-align: left; font-weight: bold;">Condition</th>
          <th style="border: 1px solid #000; padding: 8px; text-align: left; font-weight: bold;">Remarks</th>
          <th style="border: 1px solid #000; padding: 8px; text-align: left; font-weight: bold;">Branch</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td style="border: 1px solid #000; padding: 8px; vertical-align: top;">
            <strong><?php echo htmlspecialchars($assignment['item_name']); ?></strong><br>
            <span style="font-size: 9px;">
              <?php 
              // Display all attributes except serial number
              if (!empty($attributes)) {
                $specs = [];
                foreach ($attributes as $key => $value) {
                  if (in_array(strtolower($key), ['s_n', 'sn', 'serial_number'])) continue;
                  $label = str_replace('_', ' ', $key);
                  $label = ucwords(strtolower($label));
                  if (strtoupper($label) === 'CPU') $label = 'CPU';
                  if (strtoupper($label) === 'RAM') $label = 'RAM';
                  if (strtoupper($label) === 'IP') $label = 'IP';
                  if (strtoupper($label) === 'MAC') $label = 'MAC';
                  $specs[] = $label . ': ' . htmlspecialchars($value);
                }
                echo implode('<br>', $specs);
              }
              ?>
            </span>
          </td>
          <td style="border: 1px solid #000; padding: 8px; vertical-align: top;">
            <?php echo $serialNumber ? 'S/N: ' . htmlspecialchars($serialNumber) : '-'; ?>
          </td>
          <td style="border: 1px solid #000; padding: 8px; vertical-align: top;">
            <?php echo htmlspecialchars($assignment['item_condition']); ?>
          </td>
          <td style="border: 1px solid #000; padding: 8px; vertical-align: top;">
            <?php echo htmlspecialchars($assignment['notes'] ?: ''); ?>
          </td>
          <td style="border: 1px solid #000; padding: 8px; vertical-align: top;">
            <?php echo htmlspecialchars($assignment['category_name']); ?>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Approved for Issuance Section -->
    <div style="margin: 30px 0 15px 0; font-size: 16px; font-weight: bold;">Approved for Issuance</div>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 10px;">
      <thead>
        <tr style="background-color: #f0f0f0;">
          <th style="border: 1px solid #000; padding: 8px; text-align: left; font-weight: bold; width: 50%;">Approving Authority</th>
          <th style="border: 1px solid #000; padding: 8px; text-align: left; font-weight: bold; width: 50%;">Released by: <span style="float: right;"><?php echo strtoupper(date('F j, Y', strtotime($assignment['assigned_at']))); ?></span></th>
        </tr>
      </thead>
      <tbody>
      <tr>
        <td style="border: 1px solid #000; padding: 8px; vertical-align: top;">
          <div style="text-align: center; margin-top: 40px;">
            <div style="border-bottom: 1px solid #000; display: inline-block; min-width: 250px; padding-bottom: 2px;">
              <!-- Signature space -->
            </div>
            <div style="margin-top: 5px; font-weight: bold;">RODRIGO VALBUENA</div>
            <div style="font-size: 9px;">IT Specialist</div>
          </div>
        </td>
        <td style="border: 1px solid #000; padding: 8px; vertical-align: top;">
          <div style="text-align: center; margin-top: 40px;">
            <div style="border-bottom: 1px solid #000; display: inline-block; min-width: 250px; padding-bottom: 2px;">
              <!-- Signature space -->
            </div>
            <div style="margin-top: 5px; font-weight: bold;">MIELNAFFAYE ARELLANO | ARNULF SARTIN |</div>
            <div style="font-weight: bold;">KENNETH HUYBERS | DARLENE REVILLAS</div>
            <div style="font-size: 9px;">IT Department</div>
          </div>
        </td>
      </tr>
      </tbody>
    </table>

    <!-- Employee Acknowledgement Section -->
    <div style="margin: 30px 0 15px 0; font-size: 16px; font-weight: bold;">Employee Acknowledgement</div>
    <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
      <tbody>
      <tr>
        <td style="border: 1px solid #000; padding: 8px; vertical-align: top; width: 50%;">
          <div style="font-weight: bold; margin-bottom: 10px;">Employee's Signature</div>
          <div style="text-align: center; margin-top: 40px;">
            <div style="border-bottom: 1px solid #000; display: inline-block; min-width: 250px; padding-bottom: 2px;">
              <!-- Signature space -->
            </div>
            <div style="margin-top: 5px; font-weight: bold;"><?php echo strtoupper(trim($assignment['first_name'] . ' ' . $assignment['last_name'])); ?></div>
            <div style="font-size: 9px;"><?php echo htmlspecialchars($assignment['category_name']); ?> / <?php echo htmlspecialchars($assignment['department'] ?? 'N/A'); ?></div>
          </div>
        </td>
        <td style="border: 1px solid #000; padding: 8px; vertical-align: top; width: 50%;">
          <div style="font-weight: bold; text-align: right; margin-bottom: 10px;"><?php echo strtoupper(date('F j, Y', strtotime($assignment['assigned_at']))); ?></div>
        </td>
      </tr>
      </tbody>
    </table>

    <?php if ($assignment['unassigned_at']): ?>
    <!-- Return Information -->
    <div style="margin-top: 40px; padding-top: 20px; border-top: 2px dashed #000;">
      <div style="margin: 20px 0 10px 0; font-size: 14px; font-weight: bold;">Equipment Return</div>
      <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
        <tr>
          <td style="border: 1px solid #000; padding: 8px; width: 50%; vertical-align: top;">
            <div style="font-weight: bold; margin-bottom: 5px;">Returned by Employee:</div>
            <div style="text-align: center; margin-top: 40px;">
              <div style="border-bottom: 1px solid #000; display: inline-block; min-width: 250px; padding-bottom: 2px;">
                <!-- Signature space -->
              </div>
              <div style="margin-top: 5px; font-weight: bold;"><?php echo strtoupper(trim($assignment['first_name'] . ' ' . $assignment['last_name'])); ?></div>
              <div style="font-size: 9px;">Date: <?php echo date('F j, Y', strtotime($assignment['unassigned_at'])); ?></div>
            </div>
          </td>
          <td style="border: 1px solid #000; padding: 8px; width: 50%; vertical-align: top;">
            <div style="font-weight: bold; margin-bottom: 5px;">Received by IT Department:</div>
            <div style="text-align: center; margin-top: 40px;">
              <div style="border-bottom: 1px solid #000; display: inline-block; min-width: 250px; padding-bottom: 2px;">
                <!-- Signature space -->
              </div>
              <div style="margin-top: 5px; font-weight: bold;">IT DEPARTMENT</div>
              <div style="font-size: 9px;">Date: ___________________</div>
            </div>
          </td>
        </tr>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Print Button -->
  <div class="text-center mt-4 no-print">
    <button onclick="window.print()" class="btn btn-primary btn-lg">
      <i class="fas fa-print"></i> Print Form
    </button>
    <button onclick="window.close()" class="btn btn-secondary btn-lg">
      Close
    </button>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>
