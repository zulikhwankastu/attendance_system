<?php
include 'db.php';

$logFolder = __DIR__ . "/logs";
$archiveFolder = __DIR__ . "/archive";
$allFiles = array_merge(glob($logFolder . "/*.txt"), glob($archiveFolder . "/*.txt"));
usort($allFiles, function($a, $b) {
    return strcmp(basename($a), basename($b));
});

// Pick latest file automatically
$selectedFile = basename(end($allFiles));
$selectedPath = end($allFiles);

if ($selectedPath && file_exists($selectedPath)) {
    $handle = fopen($selectedPath, "r");
    while (($line = fgets($handle)) !== false) {
        $parts = preg_split('/\s+/', trim($line));
        if (count($parts) >= 3) {
            $staff_id = $parts[0];
            $date     = DateTime::createFromFormat("d/m/Y", $parts[1]);
            $time     = DateTime::createFromFormat("H:i:s", $parts[2]);

            if ($date && $time) {
                $suggested = clone $time;
                $suggested->modify("+10 hours");

                $dateStr = $date->format("Y-m-d");
                $timeStr = $time->format("H:i:s");
                $logoutStr = $suggested->format("H:i:s");

                $stmt = $conn->prepare("INSERT IGNORE INTO attendance_records 
                    (staff_id, date, time, suggested_logout, file_name) 
                    VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $staff_id, $dateStr, $timeStr, $logoutStr, $selectedFile);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    fclose($handle);
}
?>
