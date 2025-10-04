
<?php
include __DIR__ . "/db.php";

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $conn->query("DELETE FROM membership_temp WHERE id = $id");

    header("Location: ../admin/payment_pendingview.php");
    exit();
}
?>
