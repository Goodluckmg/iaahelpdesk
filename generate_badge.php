<?php
// generate_badge.php - Generate PDF badge for student (A6 Size)
session_start();
require_once 'config/database.php';

// Get badge code from URL
$badge_code = isset($_GET['badge']) ? mysqli_real_escape_string($conn, $_GET['badge']) : '';
$download = isset($_GET['download']) ? true : false;

if (empty($badge_code)) {
    die("Invalid badge request");
}

// Get student and ticket info with profile photo
$query = "SELECT t.*, s.fullname, s.reg_no, s.email, s.phone, s.profile_photo
          FROM tickets t
          JOIN students s ON t.user_id = s.id
          WHERE t.badge_code = '$badge_code' AND t.payment_verified = 1";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("❌ Invalid or unverified badge. Please contact Finance Office.");
}

// Get finance officer info
$officer_name = "Finance Officer";
$officer_query = "SELECT fullname FROM students WHERE role = 'finance' AND id = {$data['verified_by']}";
$officer_result = mysqli_query($conn, $officer_query);
if ($officer_result && mysqli_num_rows($officer_result) > 0) {
    $officer = mysqli_fetch_assoc($officer_result);
    $officer_name = $officer['fullname'];
}

// Generate unique Pass ID
$pass_id = 'IAA-EX-' . date('Y') . '-' . str_pad($data['id'], 6, '0', STR_PAD_LEFT);

// Format dates
$issue_date = date('d F Y', strtotime($data['verified_at']));
$expiry_date = date('d F Y', strtotime('+45 days', strtotime($data['verified_at'])));

// Get student photo
$student_photo = $data['profile_photo'];
$has_photo = !empty($student_photo);

// If download=true, force download as A6 PDF
if ($download) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Exam_Badge_' . $data['badge_code'] . '.pdf"');
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>IAA Special Examination Clearance Pass</title>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    /* A6 Size: 105mm x 148mm */
    body {
        background: #e8f0f5;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 10px;
        font-family: Arial, sans-serif;
    }
    
    .badge-container {
        width: 105mm;
        background: white;
        border: 2px solid #003366;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 0 10px rgba(0,0,0,0.15);
    }
    
    /* Header */
    .badge-header {
        padding: 4mm 3mm;
        text-align: center;
        border-bottom: 1px solid #003366;
    }
    
    .badge-header img {
        width: 15mm;
        margin-bottom: 2mm;
    }
    
    .institution-name {
        color: #003366;
        margin: 2mm 0;
        font-size: 3.5mm;
        font-weight: bold;
    }
    
    .institution-tagline {
        color: #003366;
        font-size: 2.2mm;
    }
    
    /* Title */
    .badge-title {
        background: #003366;
        color: white;
        text-align: center;
        padding: 2mm;
        font-size: 3.5mm;
        font-weight: bold;
    }
    
    .badge-subtitle {
        text-align: center;
        font-size: 2mm;
        font-style: italic;
        padding: 2mm;
        background: #fef9e6;
        border-bottom: 0.5px solid #ccc;
    }
    
    /* Main Content - Flex row */
    .badge-main {
        display: flex;
        padding: 3mm;
        gap: 3mm;
    }
    
    /* Photo Section */
    .photo-section {
        width: 25mm;
    }
    
    .student-photo {
        width: 25mm;
        height: 32mm;
        border: 1.5px solid #003366;
        border-radius: 4px;
        object-fit: cover;
        background: #f0f0f0;
    }
    
    /* Details Section */
    .details-section {
        flex: 1;
    }
    
    .detail-row {
        margin-bottom: 1.5mm;
    }
    
    .detail-label {
        font-size: 2mm;
        color: #003366;
        font-weight: bold;
    }
    
    .detail-value {
        font-size: 2.2mm;
        color: #333;
        font-weight: normal;
    }
    
    .status-approved {
        background: green;
        color: white;
        padding: 1mm 2mm;
        border-radius: 3px;
        font-size: 2mm;
        display: inline-block;
    }
    
    /* QR Section */
    .qr-section {
        width: 25mm;
        text-align: center;
    }
    
    .qr-placeholder {
        width: 25mm;
        height: 25mm;
        border: 1px solid #003366;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f0f0f0;
        font-size: 1.8mm;
        color: #003366;
        text-align: center;
        margin-bottom: 1mm;
    }
    
    .qr-label {
        font-size: 1.8mm;
        font-weight: bold;
    }
    
    /* Dates Row */
    .dates-row {
        display: flex;
        justify-content: space-between;
        padding: 2mm 3mm;
        border-top: 0.5px solid #ccc;
        border-bottom: 0.5px solid #ccc;
        font-size: 2mm;
    }
    
    /* Notice */
    .notice {
        background: #003366;
        color: white;
        padding: 2mm;
        text-align: center;
        font-size: 1.8mm;
    }
    
    /* Signature Section */
    .signature-section {
        display: flex;
        justify-content: space-between;
        padding: 3mm;
    }
    
    .signature-box {
        text-align: center;
    }
    
    .signature-line {
        border-top: 0.5px solid black;
        width: 30mm;
        margin-top: 5mm;
    }
    
    .signature-name {
        font-size: 1.8mm;
        font-weight: bold;
        margin-top: 1mm;
    }
    
    .signature-title {
        font-size: 1.5mm;
        color: #666;
    }
    
    .stamp-box {
        width: 18mm;
        height: 18mm;
        border: 1.5px dashed #003366;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        font-size: 1.5mm;
        font-weight: bold;
        color: #003366;
    }
    
    /* Pass ID */
    .pass-id {
        text-align: center;
        padding: 2mm;
        border-top: 0.5px solid #ccc;
    }
    
    .pass-id h2 {
        color: #003366;
        font-size: 2.5mm;
    }
    
    /* Footer */
    .badge-footer {
        background: #003366;
        color: white;
        text-align: center;
        padding: 2mm;
        font-size: 1.5mm;
    }
    
    @media print {
        body {
            background: white;
            padding: 0;
            margin: 0;
        }
        .badge-container {
            box-shadow: none;
            margin: 0;
            width: 105mm;
        }
    }
</style>
</head>
<body>

<div class="badge-container" id="badgeContent">

    <!-- Header -->
    <div class="badge-header">
        <img src="iaa_logo.png" alt="IAA Logo" onerror="this.style.display='none'">
        <div class="institution-name">INSTITUTE OF ACCOUNTANCY ARUSHA</div>
        <div class="institution-tagline">Excellence and Professionalism</div>
    </div>

    <!-- Title -->
    <div class="badge-title">SPECIAL EXAMINATION CLEARANCE PASS</div>
    <div class="badge-subtitle">Authorized to Sit for Examination Pending Administrative Clearance</div>

    <!-- Main Content -->
    <div class="badge-main">
        <!-- Photo -->
        <div class="photo-section">
            <?php if ($has_photo): ?>
                <img src="data:image/jpeg;base64,<?php echo htmlspecialchars($student_photo); ?>" class="student-photo">
            <?php else: ?>
                <div class="student-photo" style="display:flex;align-items:center;justify-content:center;">
                    <span style="font-size:2mm;">No Photo</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Details -->
        <div class="details-section">
            <div class="detail-row">
                <div class="detail-label">Name:</div>
                <div class="detail-value"><?php echo substr(htmlspecialchars($data['fullname']), 0, 20); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Reg No:</div>
                <div class="detail-value"><?php echo htmlspecialchars($data['reg_no']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Programme:</div>
                <div class="detail-value">BSc. IT</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Academic Year:</div>
                <div class="detail-value">2025/2026</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Venue:</div>
                <div class="detail-value">Main Hall</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status:</div>
                <div class="status-approved">APPROVED</div>
            </div>
        </div>

        <!-- QR -->
        <div class="qr-section">
            <div class="qr-placeholder">
                SCAN<br>TO<br>VERIFY<br>
                <span style="font-size:1.2mm;"><?php echo substr(htmlspecialchars($data['badge_code']), -8); ?></span>
            </div>
            <div class="qr-label">SCAN TO VERIFY</div>
        </div>
    </div>

    <!-- Dates -->
    <div class="dates-row">
        <span><b>Issue:</b> <?php echo $issue_date; ?></span>
        <span><b>Expiry:</b> <?php echo $expiry_date; ?></span>
    </div>

    <!-- Notice -->
    <div class="notice">
        This student is authorized to sit for examination while administrative/financial issues are being resolved.
    </div>

    <!-- Signature -->
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-name"><?php echo htmlspecialchars($officer_name); ?></div>
            <div class="signature-title">Finance Officer</div>
        </div>
        <div class="stamp-box">
            OFFICIAL<br>STAMP
        </div>
    </div>

    <!-- Pass ID -->
    <div class="pass-id">
        <h2>PASS ID: <?php echo $pass_id; ?></h2>
    </div>

    <!-- Footer -->
    <div class="badge-footer">
        This pass must be presented with valid student ID
    </div>

</div>

<?php if($download): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    window.onload = function() {
        const element = document.getElementById('badgeContent');
        const opt = {
            margin: [0, 0, 0, 0],
            filename: 'Exam_Badge_<?php echo $data['badge_code']; ?>.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, letterRendering: true, useCORS: true },
            jsPDF: { unit: 'mm', format: 'a6', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    };
</script>
<?php endif; ?>

</body>
</html>