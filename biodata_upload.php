<?php
session_start();

if (!isset($_SESSION['pending_biodata'])) {
    header("Location: index.php");
    exit;
}

$data = $_SESSION['pending_biodata'];

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

// Generate a unique 10-character alphanumeric reference
function generateReference($pdo) {
    do {
        $ref = strtoupper(bin2hex(random_bytes(5))); // 5 bytes → 10 hex chars
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM biodata WHERE reference = ?");
        $stmt->execute([$ref]);
        $count = $stmt->fetchColumn();
    } while ($count > 0);
    return $ref;
}

$reference = generateReference($pdo);

// Insert into DB
$stmt = $pdo->prepare("INSERT INTO biodata (full_name, age, birth_date, address, photo, photo_mime, reference) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $data['name'],
    $data['age'],
    $data['birth'],
    $data['address'],
    base64_decode($data['photoData']),
    $data['photoMime'],
    $reference
]);

unset($_SESSION['pending_biodata']); // clear session after saving
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Biodata Uploaded</title>
<style>
body {
    font-family: 'Inter', sans-serif;
    background: #f0f4f8;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    flex-direction: column;
}
.card {
    background: #fff;
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    text-align: center;
    width: 500px;
}
.card img {
    width: 160px;
    height: 160px;
    border-radius: 50%;
    border: 4px solid #e2e8f0;
    object-fit: cover;
    margin-bottom: 20px;
}
.card h2 {
    margin-bottom: 15px;
}
.card p {
    font-size: 1.1rem;
    margin: 8px 0;
}
button {
    margin-top: 25px;
    padding: 12px 25px;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    background: #2563eb;
    color: white;
    font-size: 1rem;
    transition: 0.2s;
}
button:hover {
    background: #1d4ed8;
}
</style>
</head>
<body>

<div style="background:#fff; padding:25px 30px; border-radius:16px; box-shadow:0 8px 20px rgba(0,0,0,0.1); max-width:500px; margin:20px auto; text-align:center;">
    <h2 style="margin-bottom:20px; color:#1e293b;">Biodata Uploaded Successfully!</h2>

    <?php if (!empty($data['photoData'])): ?>
        <img src="data:<?= $data['photoMime'] ?>;base64,<?= $data['photoData'] ?>" 
             alt="Profile" 
             style="width:150px; height:150px; border-radius:50%; border:4px solid #e2e8f0; object-fit:cover; margin-bottom:20px;">
    <?php endif; ?>

    <div style="font-size:1.1rem; margin-bottom:12px;">
        <span style="font-weight:600; color:#1f2937;">Name:</span> 
        <span style="color:#111827;"><?= htmlspecialchars($data['name']) ?></span>
    </div>

    <div style="font-size:1.1rem; margin-bottom:15px; line-height:1.4;">
        <span style="font-weight:600; color:#1f2937;">Reference Code:</span><br>
        <span style="color:#2563eb; font-size:1.2rem; font-weight:700;"><?= $reference ?></span>
    </div>

    <div style="margin-top:20px;">
        <em style="color:#6b7280;">Please take a screenshot or write down your reference code.</em>
    </div>

    <button onclick="window.location.href='index.php'" 
            style="margin-top:25px; padding:12px 25px; border-radius:12px; border:none; cursor:pointer; font-weight:600; background:#2563eb; color:white; font-size:1rem; transition:0.2s;">
        Add Another
    </button>
</div>


</body>
</html>
