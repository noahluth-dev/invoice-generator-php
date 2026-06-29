<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include("../../../db.php");

$user_id = $_SESSION['user_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zip_code = trim($_POST['zip_code'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $gstin = trim($_POST['gstin'] ?? '');

    if (empty($name)) $errors[] = "Name is required.";

    if (empty($errors)) {
        $sql = "INSERT INTO clients (user_id, name, email, phone, address, city, state, zip_code, country, gstin) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssssss", $user_id, $name, $email, $phone, $address, $city, $state, $zip_code, $country, $gstin);
        if ($stmt->execute()) {
            header("Location: ../client.php?msg=created");
            exit();
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../../global.css">
    <link rel="stylesheet" href="create_client.css">
    <title>Add Client</title>
</head>
<body>
    <div class="main-wrapper">
        <?php include '../../../components/navbar.php'; ?>
        <div class="content">
            <header class="page-header">
                <h3>Create Client</h3>
            </header>
            <div class="form-container">
                <h2>Add New Client</h2>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Name <span class="required">*</span></label>
                            <input type="text" name="name" required placeholder="Full name">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" placeholder="client@example.com">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" placeholder="+1 234 567 890">
                        </div>
                        <div class="form-group">
                            <label>GSTIN / VAT</label>
                            <input type="text" name="gstin" placeholder="GSTIN number">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" rows="2" placeholder="Street address"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" placeholder="City">
                        </div>
                        <div class="form-group">
                            <label>State</label>
                            <input type="text" name="state" placeholder="State">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Zip Code</label>
                            <input type="text" name="zip_code" placeholder="Zip code">
                        </div>
                        <div class="form-group">
                            <label>Country</label>
                            <input type="text" name="country" placeholder="Country">
                        </div>
                    </div>
                    <div class="btn-group">
                        <button type="submit" class="btn-submit">Add Client</button>
                        <a href="../client.php" class="btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>