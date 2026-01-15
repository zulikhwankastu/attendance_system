<?php
session_start();

$logFolder = __DIR__ . "/logs";
$archiveFolder = __DIR__ . "/archive";


$currentFiles = glob($logFolder . "/*.txt");
$archivedFiles = glob($archiveFolder . "/*.txt");


$allFiles = array_merge($currentFiles, $archivedFiles);

usort($allFiles, function($a, $b) {
    return strcmp(basename($a), basename($b));
});


$selectedFile = isset($_GET['file']) ? $_GET['file'] : (count($currentFiles) ? basename($currentFiles[0]) : null);
$selectedPath = null;


foreach ($allFiles as $file) {
    if (basename($file) === $selectedFile) {
        $selectedPath = $file;
        break;
    }
}


$txtData = [];
$seenStaff = []; 

if ($selectedPath && file_exists($selectedPath)) {
    $handle = fopen($selectedPath, "r");
    while (($line = fgets($handle)) !== false) {
        $parts = preg_split('/\s+/', trim($line));
        if (count($parts) >= 3) {
            $staff_id = $parts[0];
            $date     = $parts[1]; // format: d/m/Y
            $time     = $parts[2];

            // Skip if staff already has a check-in recorded
            if (isset($seenStaff[$staff_id])) {
                continue;
            }

            // Build DateTime object from date+time in file
            $datetime = DateTime::createFromFormat("d/m/Y H:i:s", "$date $time");
            $suggested = $datetime ? clone $datetime : null;
            if ($suggested) {
                $suggested->modify("+10 hours");
            }

            $txtData[] = [
                "staff_id"        => $staff_id,
                "date"            => $date,
                "time"            => $time,
                "suggested_logout"=> $suggested ? $suggested->format("H:i:s") : "-"
            ];

            // Mark staff as processed
            $seenStaff[$staff_id] = true;
        }
    }
    fclose($handle);
}

// 🔍 Apply filters
if (isset($_GET['clear'])) {
    // Reset filters
    $searchId = '';
    $searchDate = '';
} else {
    $searchId   = isset($_GET['search_id']) ? trim($_GET['search_id']) : '';
    $searchDate = isset($_GET['search_date']) ? trim($_GET['search_date']) : '';
}

if ($searchId !== '' || $searchDate !== '') {
    $txtData = array_filter($txtData, function($row) use ($searchId, $searchDate) {
        $matchId   = ($searchId === ''   || stripos($row['staff_id'], $searchId) !== false);
        $matchDate = true;
        if ($searchDate !== '') {
            // Convert searchDate (Y-m-d) to d/m/Y to match file format
            $formattedSearchDate = DateTime::createFromFormat("Y-m-d", $searchDate);
            if ($formattedSearchDate) {
                $matchDate = ($row['date'] === $formattedSearchDate->format("d/m/Y"));
            }
        }
        return $matchId && $matchDate;
    });
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Attendance Report</title>
    <link rel="stylesheet" type="text/css" href="report.css">
</head>
<body>
    <h2>📂 Attendance Report</h2>

    <!-- Dropdown + Search filters -->
    <form method="GET" style="margin-bottom:20px;">
        <label>Select File:</label>
        <select name="file">
            <?php foreach ($allFiles as $file): ?>
                <option value="<?= basename($file) ?>" <?= ($selectedFile === basename($file)) ? 'selected' : '' ?>>
                    <?= basename($file) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Search filters -->
        <label style="margin-left:20px;">Search Staff ID:</label>
        <input type="text" name="search_id" value="<?= htmlspecialchars($searchId) ?>">

        <label style="margin-left:20px;">Filter Date:</label>
        <input type="date" name="search_date" value="<?= htmlspecialchars($searchDate) ?>">

        <button type="submit">Search</button>
        <button type="submit" name="clear" value="1">Clear</button>
    </form>

    <!-- Show table -->
    <?php if (!empty($txtData)): ?>
        <h3 style="margin-top:20px;">Showing: <?= htmlspecialchars($selectedFile) ?></h3>
        <table border="1" cellpadding="8" style="margin-top:20px;">
            <tr>
                <th>Staff ID</th>
                <th>Date</th>
                <th>Check-In Time</th>
                <th>Suggested Log-Out Time (+10h)</th>
            </tr>
            <?php foreach ($txtData as $row): ?>
            <tr>
                <td><?= $row['staff_id'] ?></td>
                <td><?= $row['date'] ?></td>
                <td><?= $row['time'] ?></td>
                <td><?= $row['suggested_logout'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p style="color:red; margin-top:20px;">⚠️ No data found for selected filters.</p>
    <?php endif; ?>
</body>
</html>
