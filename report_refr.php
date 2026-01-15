<?php
session_start();
include 'db.php';

$searchId   = isset($_GET['search_id']) ? trim($_GET['search_id']) : '';
$searchDate = isset($_GET['search_date']) ? trim($_GET['search_date']) : '';
$selectedFile = isset($_GET['file']) ? $_GET['file'] : '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Attendance Report</title>
    <link rel="stylesheet" type="text/css" href="report_latest.css">
</head>
<body>
    <h2>📂 Attendance Report</h2>

    <div style="text-align:center; margin-top:20px;">
        <button id="refreshBtn">🔄 Refresh Now</button>
        <p id="lastRefreshed" style="margin-top:10px; color:#555; font-size:14px;">
            Last refreshed: <?= date("H:i:s") ?>
        </p>
    </div>

    <form method="GET" style="margin-bottom:20px;">
        <label>Search Staff ID:</label>
        <input type="text" name="search_id" value="<?= htmlspecialchars($searchId) ?>">

        <label style="margin-left:20px;">Filter Date:</label>
        <input type="date" name="search_date" value="<?= htmlspecialchars($searchDate) ?>">

        <button type="submit" name="action" value="staff">Search Staff</button>
        <button type="submit" name="clear" value="1">Clear</button>
    </form>

    <div id="attendanceTable">
        <?php include 'attendance_table.php'; ?>
    </div>

<script>
function refreshTable() {
    const params = new URLSearchParams({
        search_id: "<?= $searchId ?>",
        search_date: "<?= $searchDate ?>"
    });
    fetch("attendance_table.php?" + params.toString())
      .then(response => response.text())
      .then(html => {
          document.getElementById("attendanceTable").innerHTML = html;
          const now = new Date();
          document.getElementById("lastRefreshed").textContent =
              "Last refreshed: " + now.toLocaleTimeString();
      })
      .catch(err => console.error("Error refreshing table:", err));
}
setInterval(refreshTable, 1800000); // 30 minutes
document.getElementById("refreshBtn").addEventListener("click", refreshTable);
</script>
</body>
</html>
