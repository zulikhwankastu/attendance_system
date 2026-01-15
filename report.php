<?php
include 'db.php';
session_start();


if (isset($_GET['clear'])) {
    $search_id = '';
    $search_date = $_SESSION['upload_date'] ?? date("Y-m-d");
} else {
    $search_id = isset($_GET['search_id']) ? $_GET['search_id'] : '';
    $search_date = isset($_GET['search_date']) ? $_GET['search_date'] : (@$_SESSION['upload_date'] ?: date("Y-m-d"));
}

$sql = "SELECT staff_id, check_in, suggested_log_out 
        FROM attendance_uploads 
        WHERE upload_date = '$search_date'";

if ($search_id !== '') {
    $sql .= " AND staff_id LIKE '%$search_id%'";
}

$sql .= " ORDER BY check_in ASC";
$result = $conn->query($sql);
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

    <button class="back-btn" onclick="window.location.href='upload.php'">⬅ Back to Upload</button>


<table border="1" cellpadding="8" style="margin-top:20px;">
     <tr> 
        <th>Staff ID</th> 
        <th>Check-In Date</th> 
        <th>Check-In Time</th> 
        <th>Suggested Log-Out Time</th> 
    </tr> <?php while ($row = $result->fetch_assoc()): ?> 
        <?php $checkInDate = date("d M Y", strtotime($row['check_in'])); 
        $checkInTime = date("H:i:s", strtotime($row['check_in'])); 
        $logoutTime = date("H:i:s", strtotime($row['suggested_log_out'])); ?> 
        <tr> 
            <td><?= $row['staff_id'] ?></td>
             <td><?= $checkInDate ?></td> 
             <td><?= $checkInTime ?></td> 
             <td><?= $logoutTime ?></td>
        </tr> 
    <?php endwhile; ?>
 </table>

</body>
</html>
