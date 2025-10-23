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

// Pagination setup
$perPage = isset($_GET['perPage']) ? max(1, intval($_GET['perPage'])) : 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// Filters
$refQuery = trim($_GET['reference'] ?? '');
$nameQuery = trim($_GET['name'] ?? '');

// Build dynamic WHERE clause
$where = [];
$params = [];

if ($refQuery !== '') {
    $where[] = "reference LIKE ?";
    $params[] = "%$refQuery%";
}
if ($nameQuery !== '') {
    $where[] = "full_name LIKE ?";
    $params[] = "%$nameQuery%";
}

$whereSQL = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total results
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM biodata $whereSQL");
$countStmt->execute($params);
$totalResults = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalResults / $perPage));

// Fetch results
$query = "
    SELECT id, reference, full_name, age, birth_date, address, photo, photo_mime
    FROM biodata
    $whereSQL
    ORDER BY full_name ASC
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($query);
foreach ($params as $i => $param) {
    $stmt->bindValue($i + 1, $param, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
h2 { margin-bottom: 20px; color:#1e293b; }
form {
    display:flex;
    flex-wrap:wrap;
    justify-content:center;
    align-items:center;
    gap:10px;
    background:#fff;
    padding:15px 25px;
    border-radius:12px;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
    width:90%;
    max-width:1000px;
    margin-bottom:25px;
}
input, select, button {
    padding:10px 15px;
    font-size:1rem;
    border-radius:8px;
    border:1px solid #cbd5e1;
}
input[type="text"] {
    flex:1;
    min-width:200px;
}
select {
    width:100px;
}
button {
    background:#2563eb;
    color:white;
    cursor:pointer;
    border:none;
    transition:0.2s;
}
button:hover { background:#1d4ed8; }
table {
    border-collapse:collapse;
    width:90%;
    max-width:1000px;
    background:#fff;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
}
th, td { text-align:left; padding:12px 15px; border-bottom:1px solid #e2e8f0; }
th { background:#2563eb; color:white; }
tr:hover { background:#f9fafb; }
.view-btn {
    background:#16a34a;
    color:white;
    padding:6px 12px;
    border:none;
    border-radius:6px;
    cursor:pointer;
}
.view-btn:hover { background:#15803d; }
.pagination {
    display:flex;
    gap:8px;
    justify-content:center;
    margin:20px 0;
    flex-wrap:wrap;
}
.pagination a, .pagination span {
    padding:8px 12px;
    border-radius:6px;
    border:1px solid #cbd5e1;
    text-decoration:none;
    color:#1e293b;
}
.pagination a.active {
    background:#2563eb;
    color:white;
    border-color:#2563eb;
}
.pagination a:hover {
    background:#1d4ed8;
    color:white;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    overflow-y: auto;
    padding: 60px 20px; /* adds top/bottom space */
    backdrop-filter: blur(3px);
    display: flex;
    justify-content: center;
    align-items: flex-start; /* ensures modal starts slightly lower */
}

.modal-content {
    margin-top: 40px; /* pushes modal a bit downward */
    background: #f9fafb;
    border-radius: 16px;
    padding: 30px 40px;
    width: 90%;
    max-width: 900px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
    position: relative;
    animation: fadeIn 0.3s ease;
}

.logo { position: absolute; top: 20px; left: 20px; width: 70px; }

.close-btn {
    position: absolute;
    top: 12px;
    right: 20px;
    font-size: 1.8rem;
    font-weight: 600;
    cursor: pointer;
    color: #334155;
    transition: color 0.2s;
}
.close-btn:hover {
    color: #ef4444;
}

.paper {
    width: 794px;
    height: 1123px;
    background: #ffffff;
    padding: 60px 70px;
    border-radius: 20px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    gap: 24px;
    margin: 0 auto 30px auto;
    position: relative;
      border: 1px solid #e2e8f0;
}

.profile-pic {
    display:block;
    width:150px;
    height:150px;
    border-radius:50%;
    border:3px solid #e2e8f0;
    object-fit:cover;
    margin:15px auto;
}
.field { display:flex; justify-content:space-between; border-bottom:1px solid #e2e8f0; padding:8px 0; font-size:1rem; }
.label { font-weight:600; color:#1f2937; }
.value { color:#111827; text-align:right; }
.download-btn {
    background:#2563eb;
    color:white;
    border:none;
    border-radius:8px;
    padding:10px 15px;
    cursor:pointer;
    margin-top:15px;
    margin-bottom:10px;
}
.download-btn:hover { background:#1d4ed8; }

@media(max-width:850px) {
    form { flex-direction:column; align-items:stretch; }
    input[type="text"] { width:100%; }
    table { width:100%; }
}
</style>
</head>
<body>

<h2>Search Biodata</h2>

<form method="GET">
    <input type="text" name="reference" placeholder="Search by Reference" value="<?= htmlspecialchars($refQuery) ?>">
    <input type="text" name="name" placeholder="Search by Name" value="<?= htmlspecialchars($nameQuery) ?>">
    <label for="perPage">Show:</label>
    <select name="perPage" id="perPage" onchange="this.form.submit()">
        <?php foreach ([5,10,20,50] as $num): ?>
            <option value="<?= $num ?>" <?= $num == $perPage ? 'selected' : '' ?>><?= $num ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Search</button>
    <button type="button" onclick="window.location.href='index.php'">Back</button>
</form>

<table>
    <thead>
        <tr>
            <th>Reference</th>
            <th>Full Name</th>
            <th>Age</th>
            <th>Birth Date</th>
            <th>Address</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if(count($results) > 0): ?>
            <?php foreach($results as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['reference']) ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['age']) ?></td>
                    <td><?= htmlspecialchars($row['birth_date']) ?></td>
                    <td><?= htmlspecialchars($row['address']) ?></td>
                    <td><button class="view-btn" onclick='viewBiodata(<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_AMP) ?>)'>View</button></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6" style="text-align:center;">No records found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php if($totalPages > 1): ?>
<div class="pagination">
    <?php if($page > 1): ?>
        <a href="?reference=<?= urlencode($refQuery) ?>&name=<?= urlencode($nameQuery) ?>&perPage=<?= $perPage ?>&page=<?= $page-1 ?>">&laquo; Prev</a>
    <?php endif; ?>

    <?php for($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?reference=<?= urlencode($refQuery) ?>&name=<?= urlencode($nameQuery) ?>&perPage=<?= $perPage ?>&page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>

    <?php if($page < $totalPages): ?>
        <a href="?reference=<?= urlencode($refQuery) ?>&name=<?= urlencode($nameQuery) ?>&perPage=<?= $perPage ?>&page=<?= $page+1 ?>">Next &raquo;</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Modal -->
<div class="modal" id="biodataModal" style="display:none">
    <div class="modal-content">
        <button class="download-btn" onclick="downloadPDF()">Download</button>
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <div class="paper" id="modalPaper"></div>
    </div>
</div>

<div style="text-align:right; margin:10px 0;">
  <a href="biodata_all.php" 
     style="text-decoration:none; color:#1e293b; background:#f1f5f9; padding:6px 14px; border-radius:6px; font-size:0.95rem; border:1px solid #e2e8f0; transition:all 0.2s;">
     View list of all submitted biodata
  </a>
</div>

<!-- html2canvas & jsPDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
function viewBiodata(data) {
  const modal = document.getElementById('biodataModal');
  const paper = document.getElementById('modalPaper');

  let photoHTML = '';

  if (data.photo) {
    if (data.photo_mime === 'image/svg+xml') {
      // Encode the SVG text properly for embedding
      const encodedSvg = encodeURIComponent(data.photo)
        .replace(/'/g, '%27')
        .replace(/"/g, '%22');
      photoHTML = `<img src="data:image/svg+xml;charset=utf-8,${encodedSvg}" class="profile-pic">`;
    } else {
      // Regular base64 image (like JPEG)
      photoHTML = `<img src="data:${data.photo_mime};base64,${data.photo}" class="profile-pic">`;
    }
  }

  paper.innerHTML = `
    <img src="Logo.png" alt="Logo" class="logo" />
    <h2 style="text-align:center; text-transform:uppercase;">Personal Biodata</h2>
    ${photoHTML}
    <div style="font-weight:bold; text-align:center;">District 1 Member</div>
    <div class="field"><span class="label">Full Name</span><span class="value" id="data-person">${data.full_name}</span></div>
    <div class="field"><span class="label">Age</span><span class="value">${data.age}</span></div>
    <div class="field"><span class="label">Birth Date</span><span class="value">${data.birth_date}</span></div>
    <div class="field"><span class="label">Address</span><span class="value">${data.address}</span></div>
  `;

  modal.style.display = 'flex';
}

function closeModal() {
  document.getElementById('biodataModal').style.display = 'none';
}

async function downloadPDF() {
  const paper = document.getElementById('modalPaper');
  const { jsPDF } = window.jspdf;
  const name = document.getElementById('data-person').innerText;
  const canvas = await html2canvas(paper, { scale: 3, backgroundColor: "#ffffff" });
  const imgData = canvas.toDataURL('image/png');

  const pdf = new jsPDF({ orientation: 'p', unit: 'mm', format: 'a4' });
  const pdfWidth = pdf.internal.pageSize.getWidth();
  const pdfHeight = pdf.internal.pageSize.getHeight();

  const imgWidth = canvas.width;
  const imgHeight = canvas.height;
  const ratio = imgWidth / imgHeight;

  let renderWidth = pdfWidth;
  let renderHeight = renderWidth / ratio;

  if (renderHeight > pdfHeight) {
    renderHeight = pdfHeight;
    renderWidth = renderHeight * ratio;
  }

  const x = (pdfWidth - renderWidth) / 2;
  const y = (pdfHeight - renderHeight) / 2;

  pdf.addImage(imgData, 'PNG', x, y, renderWidth, renderHeight);
  pdf.save(name + '.pdf');
}
</script>
</body>
</html>
