<?php
include 'db.php';
session_start();

// Handle Clear button
if (isset($_GET['clear'])) {
    $search_id = '';
    $search_date = $_SESSION['upload_date'] ?? date("Y-m-d");
} else {
    $search_id = isset($_GET['search_id']) ? $_GET['search_id'] : '';
    $search_date = isset($_GET['search_date']) ? $_GET['search_date'] : (@$_SESSION['upload_date'] ?: date("Y-m-d"));
}

// Build query (DB data)
$sql = "SELECT staff_id, check_in, suggested_log_out 
        FROM attendance_uploads 
        WHERE upload_date = '$search_date'";

if ($search_id !== '') {
    $sql .= " AND staff_id LIKE '%$search_id%'";
}

$sql .= " ORDER BY check_in ASC";
$result = $conn->query($sql);

// 🔹 Auto-detect latest TXT file in logs folder
$logFolder = __DIR__ . "/logs";
$txtData = [];

$files = glob($logFolder . "/*.txt");
if (!empty($files)) {
    // Sort by modified time, newest first
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    $latestFile = $files[0];

    $handle = fopen($latestFile, "r");
    while (($line = fgets($handle)) !== false) {
        $parts = preg_split('/\s+/', trim($line));
        if (count($parts) >= 3) {
            $txtData[] = [
                "staff_id" => $parts[0],
                "date"     => $parts[1],
                "time"     => $parts[2]
            ];
        }
    }
    fclose($handle);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Attendance Report</title>
    <link rel="stylesheet" type="text/css" href="report.css">
</head>
<body>
    <h2>Attendance for <?= date("d M Y", strtotime($search_date)) ?></h2>

    <form method="GET">
        <label>Search Staff ID:</label>
        <input type="text" name="search_id" value="<?= htmlspecialchars($search_id) ?>">
        <label style="margin-left:20px;">Select Date:</label>
        <input type="date" name="search_date" value="<?= $search_date ?>">
        <button type="submit">Search</button>
        <button type="submit" name="clear" value="1">Clear</button>
    </form>

    <button class="back-btn" onclick="window.location.href='upload_folder.php'">⬅ Back to Upload</button>

    <!-- Database Table -->
    <h3>📊 Data from Database</h3>
    <table border="1" cellpadding="8" style="margin-top:20px;">
        <tr>
            <th>Staff ID</th>
            <th>Check-In Date</th>
            <th>Check-In Time</th>
            <th>Suggested Log-Out Time</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <?php
            $checkInDate = date("d M Y", strtotime($row['check_in']));
            $checkInTime = date("H:i:s", strtotime($row['check_in']));
            $logoutTime  = date("H:i:s", strtotime($row['suggested_log_out']));
        ?>
        <tr>
            <td><?= $row['staff_id'] ?></td>
            <td><?= $checkInDate ?></td>
            <td><?= $checkInTime ?></td>
            <td><?= $logoutTime ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <!-- TXT File Table -->
    <?php if (!empty($txtData)): ?>
    <h3 style="margin-top:40px;">📂 Data from Latest TXT File (<?= basename($latestFile) ?>)</h3>
    <table border="1" cellpadding="8" style="margin-top:20px;">
        <tr>
            <th>Staff ID</th>
            <th>Date</th>
            <th>Time</th>
        </tr>
        <?php foreach ($txtData as $row): ?>
        <tr>
            <td><?= $row['staff_id'] ?></td>
            <td><?= $row['date'] ?></td>
            <td><?= $row['time'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
        <p style="margin-top:20px; color:red;">⚠️ No TXT file found in logs folder.</p>
    <?php endif; ?>
</body>
</html>
