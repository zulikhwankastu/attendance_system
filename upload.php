<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logfile'])) {
    $filename = $_FILES['logfile']['tmp_name'];
    $handle = fopen($filename, "r");
    $firstLogins = [];

    while (($line = fgets($handle)) !== false) {
        $parts = preg_split('/\s+/', trim($line));
        if (count($parts) < 3) continue;

        $staff_id = $parts[0];
        $date = $parts[1];
        $time = $parts[2];
        $datetime = DateTime::createFromFormat("d/m/Y H:i:s", "$date $time");

        if (!isset($firstLogins[$staff_id])) {
            $firstLogins[$staff_id] = $datetime;
        }
    }
    fclose($handle);

    foreach ($firstLogins as $id => $checkIn) {
        $logout = clone $checkIn;
        $logout->modify("+10 hours");
        $upload_date = $checkIn->format("Y-m-d");

       
        $check_existing = "SELECT id FROM attendance_uploads 
                           WHERE staff_id = '$id' AND check_in = '".$checkIn->format("Y-m-d H:i:s")."'";
        $exists = $conn->query($check_existing);

        if ($exists->num_rows === 0) {
            $sql = "INSERT IGNORE INTO attendance_uploads (staff_id, check_in, suggested_log_out, upload_date)
                    VALUES ('$id', '".$checkIn->format("Y-m-d H:i:s")."', '".$logout->format("Y-m-d H:i:s")."', '$upload_date')";
            $conn->query($sql);
        }
    }

    $_SESSION['upload_date'] = $checkIn->format("Y-m-d");
    header("Location: report.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Attendance Log</title>
    <link rel="stylesheet" type="text/css" href="upload.css">
</head>
<body class="upload-page">
    <div class="upload-container">
        <h2>📂 Upload Attendance Log</h2>
        <form method="POST" enctype="multipart/form-data">
            <label for="logfile" class="file-label">Choose Log File:</label>
            <input type="file" id="logfile" name="logfile" required>
            <button type="submit" class="upload-btn">Process</button>
        </form>
    </div>
</body>
</html>
