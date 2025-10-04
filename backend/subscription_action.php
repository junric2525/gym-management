<?php
session_start();
include 'db.php'; // Make sure path is correct

// ✅ Verify admin access
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../Guest/Index.html");
    exit();
}

// Check if form is submitted correctly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['subscription_id'])) {
    $action = $_POST['action'];
    $subscription_id = intval($_POST['subscription_id']);

    if ($action === 'delete') {
        // Delete subscription
        $stmt = $conn->prepare("DELETE FROM subscription WHERE subscription_id = ?");
        $stmt->bind_param("i", $subscription_id);

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: ../Admin/subscriptionview.php?success=deleted");
            exit();
        } else {
            $error_msg = urlencode("Failed to delete subscription: " . $stmt->error);
            $stmt->close();
            header("Location: ../Admin/subscriptionview.php?error=db_error&msg={$error_msg}");
            exit();
        }
    } else {
        header("Location: ../Admin/subscriptionview.php?error=db_error&msg=Invalid action");
        exit();
    }
} else {
    header("Location: ../Admin/subscriptionview.php?error=db_error&msg=Missing subscription ID or action");
    exit();
}

