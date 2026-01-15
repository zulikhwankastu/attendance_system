<?php
include 'db.php';


$searchId   = isset($_GET['search_id']) ? trim($_GET['search_id']) : '';
$searchDate = isset($_GET['search_date']) ? trim($_GET['search_date']) : '';
$selectedFile = isset($_GET['file']) ? $_GET['file'] : '';


$sql = "SELECT staff_id, DATE_FORMAT(date, '%d/%m/%Y') AS date, time, suggested_logout 
        FROM attendance_records 
        WHERE TIME(time) < '13:00:00'";  

$params = [];
$types  = "";

if ($selectedFile !== '') {
    $sql .= " AND file_name = ?";
    $params[] = $selectedFile;
    $types   .= "s";
}

if ($searchId !== '') {
    $sql .= " AND staff_id LIKE ?";
    $params[] = "%".$searchId."%";
    $types   .= "s";
}

if ($searchDate !== '') {
    $sql .= " AND date = ?";
    $params[] = $searchDate;
    $types   .= "s";
}

$sql .= " ORDER BY staff_id ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<?php if ($result->num_rows > 0): ?>
<?php
$fileBase = pathinfo($selectedFile, PATHINFO_FILENAME);

$displayDate = $fileBase;
if (preg_match('/^\d{8}$/', $fileBase)) {
    $dt = DateTime::createFromFormat('dmY', $fileBase);
    if ($dt) {
       
        $displayDate = $dt->format('d F Y');
        
    }
}
?>
<h3 style="margin-top:20px;">Showing Morning Records: <?= htmlspecialchars($displayDate) ?></h3>

<table border="1" cellpadding="8" style="margin-top:20px;">
    <tr>
        <th>Staff ID</th>
        <th>Date</th>
        <th>Check-In Time</th>
        <th>Suggested Log-Out Time (+10h)</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($row['staff_id']) ?></td>
        <td><?= htmlspecialchars($row['date']) ?></td>
        <td><?= htmlspecialchars($row['time']) ?></td>
        <td><?= htmlspecialchars($row['suggested_logout']) ?></td>
    </tr>
    <?php endwhile; ?>
</table>
<?php else: ?>
<p style="color:red; margin-top:20px;">⚠️ No morning data found for selected filters.</p>
<?php endif; ?>

<?php
$stmt->close();
$conn->close();
?>
