<?php
session_start();

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

$foundData = null;
$searchAttempted = false;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['reference'])) {
    $reference = $_GET['reference'];
    $searchAttempted = true;

    $stmt = $pdo->prepare("SELECT * FROM biodata WHERE reference = ? LIMIT 1");
    $stmt->execute([$reference]);
    $foundData = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Biodata Search</title>
<style>
* { box-sizing: border-box; margin:0; padding:0; font-family: 'Inter', sans-serif; }
body { background: #f0f4f8; display: flex; flex-direction: column; align-items: center; padding: 20px; min-height: 100vh; }
form { display:flex; flex-direction:column; gap:10px; background:#fff; padding:20px; border-radius:12px; box-shadow:0 5px 15px rgba(0,0,0,0.1); width:350px; margin-bottom:30px; }
input, button { padding:10px; font-size:1rem; border-radius:8px; border:1px solid #cbd5e1; width:100%; }
button { background:#2563eb; color:white; cursor:pointer; border:none; transition:0.2s; }
button:hover { background:#1d4ed8; }
.paper { width:794px; height:1123px; background:#fff; padding:60px 70px; border-radius:16px; box-shadow:0 10px 25px rgba(0,0,0,0.1); display:flex; flex-direction:column; gap:20px; margin-bottom:20px; position: relative; }
.logo { position:absolute; top:20px; left:20px; width:70px; }
h2 { text-align:center; text-transform:uppercase; font-size:1.6rem; letter-spacing:1px; color:#1e293b; }
.profile-pic { display:block; width:160px; height:160px; border-radius:50%; border:4px solid #e2e8f0; object-fit:cover; margin:20px auto; }
.field { display:flex; justify-content:space-between; border-bottom:1px solid #e2e8f0; padding:8px 0; font-size:1rem; }
.label { font-weight:600; color:#1f2937; }
.value { color:#111827; text-align:right; }
.actions { display:flex; justify-content:center; gap:15px; margin-bottom:40px; }
button.save { background:#16a34a; color:white; }
button.back { background:#2563eb; color:white; }
button.back:hover { background:#1d4ed8; }
@media(max-width:850px) { .paper { width:90%; padding:40px; } form { width:90%; } }
</style>
</head>
<body>

<h2>Biodata Search by Reference</h2>

<form method="GET">
    <input type="text" name="reference" placeholder="Reference Code" value="<?= htmlspecialchars($_GET['reference'] ?? '') ?>" required>
    <button type="submit">Search</button>
    <button type="button" class="back" onclick="window.location.href='index.php'">Back to Home</button>
</form>

<?php if($searchAttempted && $foundData): ?>
<div class="paper" id="biodataCard">
    <img src="Logo.png" alt="Logo" class="logo" />
    <h2>Personal Biodata</h2>

    <?php if (!empty($foundData['photo'])): ?>
        <img src="data:<?= $foundData['photo_mime'] ?>;base64,<?= base64_encode($foundData['photo']) ?>" alt="Profile" class="profile-pic">
    <?php endif; ?>

    <div style="font-weight:bold; text-align:center;">District 1 Member</div>

    <div class="field"><span class="label">Full Name</span><span class="value"><?= htmlspecialchars($foundData['full_name']) ?></span></div>
    <div class="field"><span class="label">Age</span><span class="value"><?= htmlspecialchars($foundData['age']) ?></span></div>
    <div class="field"><span class="label">Birth Date</span><span class="value"><?= htmlspecialchars($foundData['birth_date']) ?></span></div>
    <div class="field"><span class="label">Address</span><span class="value"><?= htmlspecialchars($foundData['address']) ?></span></div>
</div>

<div class="actions">
    <button class="save" id="downloadBtn">Print / Save PDF</button>
    <button class="back" onclick="window.location.href='index.php'">Back</button>
</div>
<?php elseif($searchAttempted): ?>
    <p>No match found for the provided reference code.</p>
<?php endif; ?>

<!-- html2canvas & jsPDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
async function download() {
  const paper = document.getElementById('biodataCard');

  const canvas = await html2canvas(paper, {
    scale: 3,
    useCORS: true,
    backgroundColor: "#ffffff",
    scrollX: 0,
    scrollY: -window.scrollY,
  });

  const imgData = canvas.toDataURL('image/png');

  // PNG download
  const link = document.createElement('a');
  link.download = 'biodata.png';
  link.href = imgData;
  link.click();

  // PDF download
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
  pdf.save('biodata.pdf');
}

document.getElementById('downloadBtn').addEventListener('click', download);
</script>

</body>
</html>
