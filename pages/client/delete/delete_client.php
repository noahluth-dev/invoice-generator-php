<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include("../../../db.php");

$user_id = $_SESSION['user_id'];
$client_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($client_id <= 0) {
    header("Location: ../client.php?msg=invalid");
    exit();
}

// Check ownership
$check_sql = "SELECT id FROM clients WHERE id = ? AND user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $client_id, $user_id);
$check_stmt->execute();
if ($check_stmt->get_result()->num_rows === 0) {
    header("Location: ../client.php?msg=notfound");
    exit();
}

// Delete
$delete_sql = "DELETE FROM clients WHERE id = ? AND user_id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("ii", $client_id, $user_id);
if ($delete_stmt->execute()) {
    header("Location: ../client.php?msg=deleted");
} else {
    header("Location: ../client.php?msg=error");
}
exit();
?>