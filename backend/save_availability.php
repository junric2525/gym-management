<?php
require 'db_connect.php'; 

$coach_id = $_POST['coach_id'];
$days_of_week = $_POST['day_of_week'];
$start_times = $_POST['start_time'];
$end_times = $_POST['end_time'];
$slot_durations = $_POST['slot_duration_minutes'];

// Optional: First, delete all existing availability for this coach to ensure a clean update
$delete_sql = "DELETE FROM coach_availability WHERE coach_id = ?";
$stmt_delete = $conn->prepare($delete_sql);
$stmt_delete->bind_param("i", $coach_id);
$stmt_delete->execute();
$stmt_delete->close();

// 1. Prepare Insertion Statement
$insert_sql = "INSERT INTO coach_availability (coach_id, day_of_week, start_time, end_time, slot_duration_minutes) 
               VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insert_sql);

// 2. Loop through all 7 days and insert the data
for ($i = 0; $i < count($days_of_week); $i++) {
    $day = $days_of_week[$i];
    $start = $start_times[$i];
    $end = $end_times[$i];
    $duration = $slot_durations[$i];
    
    // Skip insertion if times are intentionally empty (coach is not available)
    if (!empty($start) && !empty($end)) {
        $stmt->bind_param("iissi", $coach_id, $day, $start, $end, $duration);
        $stmt->execute();
    }
}

echo "Coach schedule saved successfully!";

$stmt->close();
$conn->close();
?>