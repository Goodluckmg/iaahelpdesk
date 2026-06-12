<?php
// generate_badge.php - Generate PDF badge for student (A6 Size)
session_start();
require_once 'config/database.php';

// ========== FUNCTION TO GET BADGE CODE FROM VARIOUS SOURCES ==========
function getBadgeCode($conn) {
    if (isset($_GET['badge']) && !empty(trim($_GET['badge']))) {
        return mysqli_real_escape_string($conn, trim($_GET['badge']));
    }
    
    if (isset($_SESSION['student_id']) && $_SESSION['student_id'] > 0) {
        $student_id = $_SESSION['student_id'];
        $query = "SELECT badge_code FROM tickets 
                  WHERE user_id = $student_id 
                  AND payment_verified = 1 
                  AND badge_code IS NOT NULL 
                  AND badge_code != ''
                  ORDER BY id DESC LIMIT 1";
        $result = mysqli_query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            if (!empty($row['badge_code'])) {
                return $row['badge_code'];
            }
        }
    }
    
    if (isset($_SESSION['staff_id']) && $_SESSION['staff_id'] > 0) {
        $query = "SELECT badge_code FROM tickets 
                  WHERE payment_verified = 1 
                  AND badge_code IS NOT NULL 
                  AND badge_code != ''
                  ORDER BY id DESC LIMIT 1";
        $result = mysqli_query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            if (!empty($row['badge_code'])) {
                return $row['badge_code'];
            }
        }
    }
    
    return '';
}

$badge_code = getBadgeCode($conn);
$download = isset($_GET['download']) ? true : false;

if (empty($badge_code)) {
    die("❌ Invalid badge request. No badge code provided.");
}

$query = "SELECT t.*, s.fullname, s.reg_no, s.email, s.phone, s.profile_photo, s.program
          FROM tickets t
          JOIN students s ON t.user_id = s.id
          WHERE t.badge_code = '$badge_code' AND t.payment_verified = 1";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("❌ Invalid or unverified badge. Please contact Finance Office.");
}

$current_date = date('Y-m-d');
$badge_expiry = $data['badge_expiry_date'];
$is_expired = ($badge_expiry && $current_date > $badge_expiry);

$officer_name = "Finance Officer";
$officer_id = $data['verified_by'];
if ($officer_id) {
    $officer_query = "SELECT fullname FROM students WHERE id = $officer_id";
    $officer_result = mysqli_query($conn, $officer_query);
    if ($officer_result && mysqli_num_rows($officer_result) > 0) {
        $officer = mysqli_fetch_assoc($officer_result);
        $officer_name = $officer['fullname'];
    }
}

if (!empty($data['badge_issue_date'])) {
    $issue_date = date('d/m/Y', strtotime($data['badge_issue_date']));
} else {
    $issue_date = date('d/m/Y', strtotime($data['verified_at']));
}

if (!empty($data['badge_expiry_date'])) {
    $expiry_date = date('d/m/Y', strtotime($data['badge_expiry_date']));
} else {
    $expiry_date = date('d/m/Y', strtotime('+30 days', strtotime($issue_date)));
}

$student_photo = $data['profile_photo'];
$has_photo = !empty($student_photo);
$program = !empty($data['program']) ? strtoupper($data['program']) : 'STUDENT';
$pass_id = 'IAA-EX-' . date('Y') . '-' . str_pad($data['id'], 6, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>IAA Examination Badge – <?php echo htmlspecialchars($data['fullname']); ?></title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #7ecfdf 0%, #b0dde8 50%, #e0f3f8 100%);
      font-family: 'Segoe UI', Arial, sans-serif;
      gap: 18px;
      padding: 20px;
    }

    .scene {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 16px;
    }

    .card {
      width: 300px;
      border-radius: 18px;
      overflow: hidden;
      background: #fff;
      box-shadow: 0 12px 40px rgba(0,0,0,0.25), 0 2px 8px rgba(0,0,0,0.12);
      position: relative;
    }
    
    .card.expired {
      opacity: 0.85;
      position: relative;
    }
    
    .expired-stamp {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) rotate(-25deg);
      background: rgba(204, 0, 0, 0.85);
      color: white;
      padding: 5px 20px;
      font-size: 14px;
      font-weight: bold;
      border: 2px solid white;
      z-index: 10;
      white-space: nowrap;
      border-radius: 5px;
    }

    .card__header {
      background: #1a2f7a;
      padding: 15px 16px 8px;
      text-align: center;
      position: relative;
    }

    .logo-wrap {
      display: flex;
      justify-content: center;
      margin-bottom: 8px;
    }

    .logo-circle {
      width: 45px;
      height: 45px;
      border-radius: 50%;
      background: rgba(255,255,255,0.15);
      backdrop-filter: blur(2px);
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid #f5c542;
      box-shadow: 0 2px 10px rgba(0,0,0,0.2);
      overflow: hidden;
    }

    .iaa-logo {
      width: 38px;
      height: 38px;
      object-fit: contain;
      opacity: 0.85;
    }

    .org-name {
      color: #fff;
      font-size: 14px;
      font-weight: 800;
      letter-spacing: 1px;
      text-transform: uppercase;
      line-height: 1.2;
      text-shadow: 0 1px 1px rgba(0,0,0,0.2);
    }

    /* WAVE SECTION */
    .card__wave {
      position: relative;
      width: 100%;
      height: 100px;
      margin-top: -2px;
    }

    .wave-svg {
      position: absolute;
      top: 0; left: 0;
      width: 100%;
      height: 100%;
    }

    /* IAA LOGO WATERMARK - Behind the text */
    .iaa-watermark {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1;
      pointer-events: none;
    }
    
    .iaa-watermark img {
      width: 180px;
      height: auto;
      opacity: 0.12;
      filter: grayscale(100%);
    }

    /* Student Photo - Clear on top */
    .photo-wrap {
      position: absolute;
      top: 5px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 3;
    }

    .photo-ring {
      width: 90px;
      height: 90px;
      border-radius: 50%;
      background: rgba(255,255,255,0.2);
      display: flex;
      align-items: center;
      justify-content: center;
      border: 3px solid #fff;
      box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }

    .photo-inner {
      width: 78px;
      height: 78px;
      border-radius: 50%;
      overflow: hidden;
      background: #e0e0e0;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .photo-inner img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    
    .no-photo {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #2c7da0;
      color: white;
      font-size: 28px;
      font-weight: bold;
    }

    /* BODY - Text area */
    .card__body {
      padding: 50px 20px 15px;
      text-align: center;
      position: relative;
      z-index: 2;
      background: transparent;
    }

    .person-name {
      font-size: 16px;
      font-weight: 800;
      color: #1a2f7a;
      letter-spacing: 1px;
      text-transform: uppercase;
      margin-bottom: 2px;
    }

    .person-title {
      font-size: 10px;
      font-weight: 700;
      color: #cc2229;
      letter-spacing: 1px;
      text-transform: uppercase;
      margin-bottom: 12px;
    }

    .info-grid {
      display: grid;
      grid-template-columns: auto 10px 1fr;
      gap: 4px 0;
      text-align: left;
      margin-bottom: 12px;
    }

    .info-label {
      font-size: 9px;
      font-weight: 600;
      color: #cc2229;
      padding-right: 3px;
    }

    .info-sep {
      font-size: 9px;
      color: #444;
      text-align: center;
    }

    .info-val {
      font-size: 9px;
      color: #222;
      padding-left: 4px;
    }

    .barcode-wrap {
      border-top: 1px solid #e8e8e8;
      padding-top: 8px;
    }

    #barcodeCanvas {
      display: block;
      margin: 0 auto;
      width: 200px;
      height: 38px;
    }

    .barcode-text {
      font-size: 7px;
      color: #555;
      letter-spacing: 1px;
      margin-top: 2px;
    }

    .btn-group {
      display: flex;
      gap: 10px;
      margin-top: 10px;
      flex-wrap: wrap;
      justify-content: center;
    }
    
    .btn {
      cursor: pointer;
      border: none;
      padding: 8px 20px;
      border-radius: 30px;
      font-size: 13px;
      font-weight: 600;
      color: white;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
      transition: background 0.2s;
      text-decoration: none;
      display: inline-block;
    }
    
    .btn-print { background: #1a2f7a; }
    .btn-print:hover { background: #0f2050; }
    .btn-download { background: #27ae60; }
    .btn-download:hover { background: #1e8449; }
    .btn-back { background: #7f8c8d; }
    .btn-back:hover { background: #6c7a7d; }
    
    .note-tip {
      font-size: 9px;
      background: rgba(0,0,0,0.6);
      color: #f0f0f0;
      padding: 3px 10px;
      border-radius: 20px;
      backdrop-filter: blur(6px);
      margin-top: -6px;
    }
    
    @media print {
      body {
        background: white;
        padding: 0;
        margin: 0;
      }
      .btn-group, .note-tip {
        display: none;
      }
      .card {
        box-shadow: none;
        margin: 0;
        border: 1px solid #003366;
      }
      .card__header, .badge-title, .notice {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
    }
    
    .loading {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.7);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 9999;
      color: white;
      font-size: 18px;
      flex-direction: column;
      gap: 15px;
    }
    .loading-spinner {
      width: 50px;
      height: 50px;
      border: 5px solid #f3f3f3;
      border-top: 5px solid #f39c12;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
</head>
<body>

<div class="scene">
  <div class="card <?php echo $is_expired ? 'expired' : ''; ?>" id="badgeContent">
    <?php if ($is_expired): ?>
    <div class="expired-stamp">EXPIRED</div>
    <?php endif; ?>
    
    <div class="card__header">
      <div class="logo-wrap">
        <div class="logo-circle">
          <img src="iaa_logo.png" alt="IAA Logo" class="iaa-logo" onerror="this.style.display='none'">
        </div>
      </div>
      <h1 class="org-name">INSTITUTE OF ACCOUNTANCY ARUSHA</h1>
    </div>

    <div class="card__wave">
      <svg class="wave-svg" viewBox="0 0 320 120" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M0,0 L320,0 L320,70 Q260,110 200,80 Q140,50 80,90 Q40,110 0,80 Z" fill="#1a2f7a"/>
        <path d="M0,82 Q40,112 80,92 Q140,52 200,82 Q260,112 320,72 L320,78 Q260,118 200,88 Q140,58 80,98 Q40,118 0,88 Z" fill="#cc2229"/>
      </svg>
      
      <!-- IAA LOGO WATERMARK - Behind the text -->
      <div class="iaa-watermark">
        <img src="iaa_logo.png" alt="IAA" onerror="this.style.display='none'">
      </div>
      
      <!-- Student Photo - Clear on top -->
      <div class="photo-wrap">
        <div class="photo-ring">
          <div class="photo-inner">
            <?php if ($has_photo): ?>
              <img src="data:img/jpg;base64,<?php echo htmlspecialchars($student_photo); ?>" alt="Student Photo">
            <?php else: ?>
              <div class="no-photo">
                <span>📷</span>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="card__body">
      <h2 class="person-name"><?php echo htmlspecialchars($data['fullname']); ?></h2>
      <p class="person-title"><?php echo htmlspecialchars($program); ?></p>

      <div class="info-grid">
        <span class="info-label">REG NO</span>
        <span class="info-sep">:</span>
        <span class="info-val"><?php echo htmlspecialchars($data['reg_no']); ?></span>

        <span class="info-label">FROM</span>
        <span class="info-sep">:</span>
        <span class="info-val"><?php echo $issue_date; ?></span>

        <span class="info-label">TO</span>
        <span class="info-sep">:</span>
        <span class="info-val"><?php echo $expiry_date; ?></span>

        <span class="info-label">ISSUED BY</span>
        <span class="info-sep">:</span>
        <span class="info-val"><?php echo htmlspecialchars($officer_name); ?></span>

        <span class="info-label">SIGN</span>
        <span class="info-sep">:</span>
        <span class="info-val">.....................</span>
      </div>

      <div class="barcode-wrap">
        <canvas id="barcodeCanvas"></canvas>
        <p class="barcode-text"><?php echo htmlspecialchars($data['badge_code']); ?></p>
        <p class="barcode-text" style="margin-top: 3px;">Authorized to sit for examination</p>
      </div>
    </div>
  </div>

  <div class="btn-group">
 
    <?php if (!$is_expired): ?>
    <button class="btn btn-download" id="downloadBtn">📥 Download PDF</button>
    <?php endif; ?>
   
<script>
  // Draw barcode
  (function drawBarcode() {
    const canvas = document.getElementById('barcodeCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const W = 400, H = 76;
    canvas.width = W;
    canvas.height = H;
    
    let data = '<?php echo htmlspecialchars($data['badge_code']); ?>';
    let fullData = data;
    while (fullData.length < 40) {
      fullData += fullData;
    }
    
    const barCount = 70;
    const barW = W / barCount;

    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, W, H);

    for (let i = 0; i < barCount; i++) {
      const charVal = fullData.charCodeAt(i % fullData.length);
      const bitPos = i % 8;
      const filled = (charVal >> bitPos) & 1;
      if (filled) {
        ctx.fillStyle = '#111';
        ctx.fillRect(i * barW, 0, Math.max(barW - 0.5, 1), H);
      }
    }

    ctx.fillStyle = '#111';
    ctx.fillRect(0, 0, 3, H);
    ctx.fillRect(3, 0, 1, H);
    ctx.fillRect(W - 4, 0, 1, H);
    ctx.fillRect(W - 3, 0, 3, H);
    
    ctx.fillStyle = '#f5f5f5';
    ctx.fillRect(0, 0, 2, H);
    ctx.fillRect(W-2, 0, 2, H);
  })();

  // PDF Download functionality
  const downloadBtn = document.getElementById('downloadBtn');
  if (downloadBtn) {
    downloadBtn.addEventListener('click', function() {
      const loadingDiv = document.createElement('div');
      loadingDiv.className = 'loading';
      loadingDiv.innerHTML = '<div class="loading-spinner"></div><span>Preparing badge...</span>';
      document.body.appendChild(loadingDiv);
      
      const element = document.getElementById('badgeContent');
      
      const opt = {
        margin: [2, 2, 2, 2],
        filename: 'Exam_Badge_<?php echo $data['badge_code']; ?>.pdf',
        image: { type: 'jpeg', quality: 1 },
        html2canvas: { scale: 3, letterRendering: true, useCORS: true, backgroundColor: '#ffffff' },
        jsPDF: { unit: 'mm', format: 'a6', orientation: 'portrait' }
      };
      
      setTimeout(() => {
        html2pdf().set(opt).from(element).save().then(() => {
          loadingDiv.remove();
        }).catch(err => {
          loadingDiv.innerHTML = '<span style="color:red">Error. Please use Print instead (Ctrl+P)</span>';
          setTimeout(() => loadingDiv.remove(), 2000);
        });
      }, 300);
    });
  }
</script>

</body>
</html>