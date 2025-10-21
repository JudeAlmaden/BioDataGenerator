<?php
session_start();

// Redirect if no data
if (!isset($_SESSION['saved_biodata'])) {
    header("Location: index.php");
    exit;
}


$savedData = $_SESSION['saved_biodata'];

// DB connection
$host = "gateway01.ap-southeast-1.prod.aws.tidbcloud.com";
$port = 4000;
$dbname = "biodata";
$username = "2YBS2CAczZWzjKH.root";
$password = "BUNYVuDZU6cqyppy";

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
$options = [
    PDO::MYSQL_ATTR_SSL_CA => "isrgrootx1.pem",
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle save request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $stmt = $pdo->prepare("INSERT INTO biodata (full_name, age, birth_date, address, photo, photo_mime) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $savedData['name'],
        $savedData['age'],
        $savedData['birth'],
        $savedData['address'],
        base64_decode($savedData['photoData']),
        $savedData['photoMime']
    ]);

    unset($_SESSION['saved_biodata']); // clear session after saving
    echo "<script>alert('Biodata saved successfully!'); window.location.href='index.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Biodata Card</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
body { background: #f0f4f8; display: flex; flex-direction: column; align-items: center; padding: 20px; min-height: 100vh; }
.paper { width: 794px; height: 1123px; background: #ffffff; padding: 60px 70px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); position: relative; display: flex; flex-direction: column; gap: 20px; margin-bottom: 20px; }
.logo { position: absolute; top: 20px; left: 20px; width: 70px; }
h2 { text-align: center; text-transform: uppercase; font-size: 1.6rem; letter-spacing: 1px; color: #1e293b; }
.profile-pic { display: block; width: 160px; height: 160px; border-radius: 50%; border: 4px solid #e2e8f0; object-fit: cover; margin: 20px auto; }
.field { display: flex; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding: 8px 0; font-size: 1rem; }
.label { font-weight: 600; color: #1f2937; }
.value { color: #111827; text-align: right; }
.actions { display: flex; justify-content: center; gap: 15px; margin-bottom: 40px; }
button { padding: 12px 20px; border-radius: 10px; border: none; cursor: pointer; font-weight: 600; font-size: 1rem; transition: all 0.2s; }
button.save { background: #16a34a; color: white; }
button.back { background: #2563eb; color: white; }
button.back:hover { background: #1d4ed8; }
@media (max-width: 850px) { .paper { width: 90%; padding: 40px; } }
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

    <div class="field"><span class="label">Full Name</span><span class="value"><?= $savedData['name'] ?></span></div>
    <div class="field"><span class="label">Age</span><span class="value"><?= $savedData['age'] ?></span></div>
    <div class="field"><span class="label">Birth Date</span><span class="value"><?= $savedData['birth'] ?></span></div>
    <div class="field"><span class="label">Address</span><span class="value"><?= $savedData['address'] ?></span></div>
</div>

<div class="actions">
    <form method="POST">
        <button type="submit" name="save" class="save">Save & Download</button>
    </form>
    <button class="back" onclick="window.location.href='index.php'">Back</button>
</div>

<!-- Optional: Download as PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
document.querySelector('button.save').addEventListener('click', function() {
    download()
    setTimeout(() => {
    }, 3000); // slight delay for PHP to handle DB save
});
</script>

</body>
</html>
<!-- html2canvas -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>


<!-- Download as pdf -->
<script>
async function downloadPdf() {
  const { jsPDF } = window.jspdf;
  const paper = document.querySelector('.paper');

  // Render at high resolution
  const canvas = await html2canvas(paper, {
    scale: 3,
    useCORS: true,
    backgroundColor: "#ffffff"
  });

  const imgData = canvas.toDataURL("image/png");
  const imgWidth = canvas.width;
  const imgHeight = canvas.height;
  const aspectRatio = imgWidth / imgHeight;

  // Define PDF page size (A4 in portrait)
  const pdf = new jsPDF({
    orientation: aspectRatio > 1 ? "l" : "p",
    unit: "mm",
    format: "a4",
  });

  const pageWidth = pdf.internal.pageSize.getWidth();
  const pageHeight = pdf.internal.pageSize.getHeight();
  const pageAspect = pageWidth / pageHeight;

  // Fit proportionally within A4 page
  let renderWidth, renderHeight;
  if (aspectRatio > pageAspect) {
    renderWidth = pageWidth;
    renderHeight = renderWidth / aspectRatio;
  } else {
    renderHeight = pageHeight;
    renderWidth = renderHeight * aspectRatio;
  }

  // Center image on page
  const x = (pageWidth - renderWidth) / 2;
  const y = (pageHeight - renderHeight) / 2;

  pdf.addImage(imgData, "PNG", x, y, renderWidth, renderHeight);
  pdf.save("biodata.pdf");
}
</script>

<!-- This calls the download pdf function last as it may require internet to work -->
<script>
async function download() {
  const paper = document.querySelector('.paper');

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

  // Create a download link
  const link = document.createElement('a');
  link.download = 'biodata.png';
  link.href = imgData;
  link.click();

  downloadPdf();
}
</script>
