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
    $address = htmlspecialchars($_POST['address']);

    $year = $_POST['birth_year'];
    $month = str_pad($_POST['birth_month'], 2, '0', STR_PAD_LEFT);
    $day = str_pad($_POST['birth_day'], 2, '0', STR_PAD_LEFT);
    $birth = "$year-$month-$day";

    $photoData = null;
    $photoMime = null;
    if (!empty($_FILES['photo']['tmp_name'])) {
        $photoData = file_get_contents($_FILES['photo']['tmp_name']);
        $photoMime = mime_content_type($_FILES['photo']['tmp_name']);
    }

    // Store in session only
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
    background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
}

/* Form container */
form {
    background: #ffffff;
    width: 420px;
    padding: 35px 40px;
    border-radius: 20px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    gap: 20px;
    transition: transform 0.2s;
}
form:hover {
    transform: translateY(-2px);
}

/* Heading */
form h2 {
    text-align: center;
    color: #1e293b;
    font-size: 1.8rem;
    margin-bottom: 15px;
    font-weight: 700;
}

/* Labels */
label {
    font-weight: 600;
    margin-bottom: 6px;
    color: #334155;
    font-size: 0.95rem;
}

/* Inputs and selects */
input[type="text"],
input[type="number"],
input[type="file"],
select {
    width: 100%;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    font-size: 0.95rem;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}
input:focus,
select:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.2);
    outline: none;
    background: #ffffff;
}

/* Dropdown group for birth date */
.birth-selects {
    display: flex;
    gap: 8px;
}

/* Buttons */
button {
    background: #2563eb;
    color: white;
    font-weight: 600;
    padding: 14px;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    font-size: 1rem;
    margin-top: 10px;
    transition: background 0.2s, transform 0.1s, box-shadow 0.2s;
}
button:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
button:active {
    transform: translateY(0);
}

/* Logo */
.logo-container {
    text-align: center;
    margin-bottom: 25px;
}
.logo-container img {
    width: 130px;
    height: auto;
    border-radius: 12px;
    transition: transform 0.2s;
}
.logo-container img:hover {
    transform: scale(1.05);
}

/* Responsive */
@media (max-width: 480px) {
    form { width: 100%; padding: 25px; }
    .birth-selects { flex-direction: column; gap: 10px; }
}
</style>
</head>
<body>

<form method="POST" enctype="multipart/form-data">
    <!-- Logo -->
    <div class="logo-container">
        <img src="Logo.png" alt="Logo">
    </div>

    <h2>Enter Your Biodata</h2>

    <label>Profile Image</label>
    <input type="file" name="photo" accept="image/*" required>

    <label>Full Name</label>
    <input type="text" name="name" placeholder="John Doe" required>

    <label>Age</label>
    <input type="number" name="age" min="1" placeholder="25" required>

    <label>Birth Date</label>
    <div class="birth-selects">
        <!-- Month -->
        <select name="birth_month" required>
            <option value="">Month</option>
            <?php
            for ($m = 1; $m <= 12; $m++) {
                $monthName = date('F', mktime(0, 0, 0, $m, 10));
                echo "<option value='$m'>$monthName</option>";
            }
            ?>
        </select>

        <!-- Day -->
        <select name="birth_day" required>
            <option value="">Day</option>
            <?php
            for ($d = 1; $d <= 31; $d++) {
                echo "<option value='$d'>$d</option>";
            }
            ?>
        </select>

        <!-- Year -->
        <select name="birth_year" required>
            <option value="">Year</option>
            <?php
            $currentYear = date('Y');
            for ($y = $currentYear; $y >= 1900; $y--) {
                echo "<option value='$y'>$y</option>";
            }
            ?>
        </select>
    </div>

    <label>Address</label>
    <input type="text" name="address" placeholder="123 Main St, City, Country" required>

    <button type="submit">Generate Biodata</button>
</form>

</body>
</html>
