<?php
session_start();

if (!isset($_SESSION['pending_biodata'])) {
    header("Location: index.php");
    exit;
}

$savedData = $_SESSION['pending_biodata'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Biodata Card</title>
<style>
* { box-sizing: border-box; margin:0; padding:0; font-family: 'Inter', sans-serif; }
body { background: #f0f4f8; display: flex; flex-direction: column; align-items: center; padding: 20px; min-height: 100vh; }
.paper { width: 794px; height: 1123px; background: #fff; padding: 60px 70px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); display:flex; flex-direction:column; gap:20px; margin-bottom:20px; position: relative; }
.logo { position: absolute; top: 20px; left: 20px; width: 70px; }
h2 { text-align:center; text-transform:uppercase; font-size:1.6rem; letter-spacing:1px; color:#1e293b; }
.profile-pic { display:block; width:160px; height:160px; border-radius:50%; border:4px solid #e2e8f0; object-fit:cover; margin:20px auto; }
.field { display:flex; justify-content:space-between; border-bottom:1px solid #e2e8f0; padding:8px 0; font-size:1rem; }
.label { font-weight:600; color:#1f2937; }
.value { color:#111827; text-align:right; }
.actions { display:flex; justify-content:center; gap:15px; margin-bottom:40px; }
button { padding:12px 20px; border-radius:10px; border:none; cursor:pointer; font-weight:600; font-size:1rem; transition:all 0.2s; }
button.save { background:#16a34a; color:white; }
button.back { background:#2563eb; color:white; }
button.back:hover { background:#1d4ed8; }
@media(max-width:850px) { .paper { width:90%; padding:40px; } }
</style>
</head>
<body>

<div class="paper" id="biodataCard">
    <img src="Logo.png" alt="Logo" class="logo" />
    <h2>Personal Biodata</h2>

    <?php if (!empty($savedData['photoData'])): ?>
        <img src="data:<?= $savedData['photoMime'] ?>;base64,<?= $savedData['photoData'] ?>" alt="Profile" class="profile-pic">
    <?php endif; ?>

    <div style="font-weight:bold; text-align:center;">District 1 Member</div>

    <div class="field"><span class="label">Full Name</span><span class="value" id="data-person"><?= htmlspecialchars($savedData['name']) ?></span></div>
    <div class="field"><span class="label">Age</span><span class="value"><?= htmlspecialchars($savedData['age']) ?></span></div>
    <div class="field"><span class="label">Birth Date</span><span class="value"><?= htmlspecialchars($savedData['birth']) ?></span></div>
    <div class="field"><span class="label">Address</span><span class="value"><?= htmlspecialchars($savedData['address']) ?></span></div>
</div>

<div class="actions">
    <button class="save" id="downloadBtn">Submit and Download</button>
    <button class="back" onclick="window.location.href='index.php'">Back</button>
</div>

<!-- html2canvas & jsPDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
async function download() {
  const paper = document.querySelector('.paper');
  const name = document.getElementById('data-person').innerText;
  
  // Render the element as a high-resolution canvas
  const canvas = await html2canvas(paper, {
    scale: 3, // higher = sharper image
    useCORS: true,
    backgroundColor: "#ffffff",
    scrollX: 0,
    scrollY: -window.scrollY,
  });

  // Convert canvas to image data
  const imgData = canvas.toDataURL('image/png');

  // Create a download link for PNG
  const link = document.createElement('a');
  link.download = 'biodata.png';
  link.href = imgData;
  link.click();

  // Generate PDF
  const { jsPDF } = window.jspdf;
  const pdf = new jsPDF({
    orientation: 'p',
    unit: 'mm',
    format: 'a4',
  });

  const imgWidth = canvas.width;
  const imgHeight = canvas.height;
  const pdfWidth = pdf.internal.pageSize.getWidth();
  const pdfHeight = pdf.internal.pageSize.getHeight();
  const aspectRatio = imgWidth / imgHeight;

  let renderWidth, renderHeight;
  if (aspectRatio > (pdfWidth / pdfHeight)) {
    renderWidth = pdfWidth;
    renderHeight = renderWidth / aspectRatio;
  } else {
    renderHeight = pdfHeight;
    renderWidth = renderHeight * aspectRatio;
  }

  const x = (pdfWidth - renderWidth) / 2;
  const y = (pdfHeight - renderHeight) / 2;

  pdf.addImage(imgData, 'PNG', x, y, renderWidth, renderHeight);
  pdf.save(name + '.pdf');

  // After downloads, redirect to biodata_upload.php
  window.location.href = 'biodata_upload.php';
}

// Attach click listener
document.getElementById('downloadBtn').addEventListener('click', download);
</script>
