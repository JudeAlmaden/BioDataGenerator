<?php
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

// Fetch all names alphabetically
$stmt = $pdo->query("SELECT full_name FROM biodata ORDER BY full_name ASC");
$results = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Biodata Directory</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
body {
  background: #f8fafc;
  padding: 30px 20px;
}
h2 {
  text-align: center;
  color: #1e293b;
  margin-bottom: 10px;
  font-size: 1.6rem;
}
.back-btn {
  display: inline-block;
  text-decoration: none;
  color: #1e293b;
  background: #f1f5f9;
  padding: 6px 14px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  font-size: 0.95rem;
  transition: all 0.2s;
  margin-bottom: 15px;
}
.back-btn:hover {
  background: #e2e8f0;
}
.table-container {
  width: 100%;
  max-width: 1200px;
  margin: auto;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
  padding: 10px;
}
table {
  border-collapse: collapse;
  width: 100%;
  font-size: 0.95rem;
}
td {
  border: 1px solid #e5e7eb;
  padding: 6px 10px;
  white-space: nowrap;
  color: #111827;
}
tr:nth-child(even) td {
  background: #f9fafb;
}
</style>
</head>
<body>

<div style="max-width:1200px; margin:auto; display:flex; justify-content:space-between; align-items:center;">
  <h2>All Biodata Submissions</h2>
  <a href="biodata_search.php" class="back-btn">← Back to Search</a>
</div>

<div class="table-container">
  <table>
    <?php
    $columns = 4; // number of columns
    $count = 0;
    echo "<tr>";
    foreach ($results as $name) {
        echo "<td>" . htmlspecialchars($name) . "</td>";
        $count++;
        if ($count % $columns == 0) echo "</tr><tr>";
    }
    if ($count % $columns != 0) {
        for ($i = $count % $columns; $i < $columns; $i++) echo "<td></td>";
        echo "</tr>";
    }
    ?>
  </table>
</div>

</body>
</html>
