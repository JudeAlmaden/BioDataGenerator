<?php
session_start();

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $age = htmlspecialchars($_POST['age']);
    $birth = htmlspecialchars($_POST['birth_date']);
    $address = htmlspecialchars($_POST['address']);
    $photoData = null;
    $photoMime = null;

    if (!empty($_FILES['photo']['tmp_name'])) {
        $photoData = file_get_contents($_FILES['photo']['tmp_name']);
        $photoMime = mime_content_type($_FILES['photo']['tmp_name']);
    }

    $stmt = $pdo->prepare("INSERT INTO biodata (full_name, age, birth_date, address, photo, photo_mime) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $age, $birth, $address, $photoData, $photoMime]);

    $_SESSION['saved_biodata'] = [
        'name' => $name,
        'age' => $age,
        'birth' => $birth,
        'address' => $address,
        'photoData' => base64_encode($photoData),
        'photoMime' => $photoMime
    ];

    header("Location: biodata_display.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MyBio</title>
<style>
/* Reset */
* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }

body {
    background: #f0f4f8;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
}

/* Form container */
form {
    background: #ffffff;
    width: 400px;
    padding: 30px 35px;
    border-radius: 16px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    gap: 18px;
}

/* Heading */
form h2 {
    text-align: center;
    color: #1e293b;
    font-size: 1.6rem;
    margin-bottom: 10px;
    font-weight: 700;
}

/* Labels */
label {
    font-weight: 500;
    margin-bottom: 6px;
    color: #334155;
    font-size: 0.95rem;
}

/* Inputs */
input[type="text"],
input[type="number"],
input[type="date"],
input[type="file"] {
    width: 100%;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    font-size: 0.95rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}
input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.2);
    outline: none;
}

/* Submit button */
button {
    background: #2563eb;
    color: white;
    font-weight: 600;
    padding: 12px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    font-size: 1rem;
    margin-top: 10px;
    transition: background 0.2s, transform 0.1s;
}
button:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}
button:active {
    transform: translateY(0);
}

/* Responsive */
@media (max-width: 450px) {
    form { width: 100%; padding: 25px; }
}
</style>
</head>
<body>

<form method="POST" enctype="multipart/form-data">
    <!-- Logo -->
    <div style="text-align:center; margin-bottom:20px;">
        <img src="Logo.png" alt="Logo" style="width:150px; height:auto;">
    </div>

    <h2>Enter Your Biodata</h2>

    <label>Profile Image</label>
    <input type="file" name="photo" accept="image/*" required>

    <label>Full Name</label>
    <input type="text" name="name" required>

    <label>Age</label>
    <input type="number" name="age" min="1" required>

    <label>Birth Date</label>
    <input type="date" name="birth_date" required>

    <label>Address</label>
    <input type="text" name="address" required>

    <button type="submit">Generate Biodata</button>
</form>

</body>
</html>
