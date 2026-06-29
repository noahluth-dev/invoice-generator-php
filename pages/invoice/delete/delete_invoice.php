<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include("../db.php");

$user_id = $_SESSION['user_id'];
$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($invoice_id <= 0) {
    header("Location: invoices.php?msg=invalid");
    exit();
}

// Verify the invoice belongs to the logged-in user
$check_sql = "SELECT id FROM invoices WHERE id = ? AND user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $invoice_id, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    // Invoice not found or doesn't belong to user
    header("Location: invoices.php?msg=notfound");
    exit();
}

// Delete the invoice (items will be automatically deleted due to ON DELETE CASCADE)
$delete_sql = "DELETE FROM invoices WHERE id = ? AND user_id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("ii", $invoice_id, $user_id);

if ($delete_stmt->execute()) {
    // Deletion successful
    header("Location: invoices.php?msg=deleted");
} else {
    // Deletion failed
    header("Location: invoices.php?msg=error");
}
exit();
?>