<?php
include 'db.php';
date_default_timezone_set('Asia/Manila');

$today = date('Y-m-d');
$query = $conn->prepare("SELECT member_id, name, time_in, time_out FROM attendance WHERE date = ?");
$query->bind_param("s", $today);
$query->execute();
$result = $query->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

echo json_encode($logs);
?>
