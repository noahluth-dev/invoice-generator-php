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
    header("Location: ../clients.php");
    exit();
}

$sql = "SELECT * FROM clients WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $client_id, $user_id);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();
if (!$client) {
    header("Location: ../clients.php?msg=notfound");
    exit();
}

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
        $sql = "UPDATE clients SET name=?, email=?, phone=?, address=?, city=?, state=?, zip_code=?, country=?, gstin=? 
                WHERE id=? AND user_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssii", $name, $email, $phone, $address, $city, $state, $zip_code, $country, $gstin, $client_id, $user_id);
        if ($stmt->execute()) {
            header("Location: ../clients.php?msg=updated");
            exit();
        } else {
            $errors[] = "Database error.";
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
    <link rel="stylesheet" href="edit_client.css">
    <title>Edit Client</title>

</head>
<body>
    <div class="main-wrapper">
        <?php include '../../../components/navbar.php'; ?>
        <div class="content">
            <header class="page-header">
                <h3>Edit Client</h3>
            </header>
            <div class="form-container">
                <h2>Edit Client</h2>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Name <span class="required">*</span></label>
                            <input type="text" name="name" required value="<?php echo htmlspecialchars($client['name']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($client['email']); ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($client['phone']); ?>">
                        </div>
                        <div class="form-group">
                            <label>GSTIN / VAT</label>
                            <input type="text" name="gstin" value="<?php echo htmlspecialchars($client['gstin']); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" rows="2"><?php echo htmlspecialchars($client['address']); ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" value="<?php echo htmlspecialchars($client['city']); ?>">
                        </div>
                        <div class="form-group">
                            <label>State</label>
                            <input type="text" name="state" value="<?php echo htmlspecialchars($client['state']); ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Zip Code</label>
                            <input type="text" name="zip_code" value="<?php echo htmlspecialchars($client['zip_code']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Country</label>
                            <input type="text" name="country" value="<?php echo htmlspecialchars($client['country']); ?>">
                        </div>
                    </div>
                    <div class="btn-group">
                        <button type="submit" class="btn-submit">Update Client</button>
                        <a href="../client.php" class="btn-cancel">Cancel</a>
                        <a href="../delete/delete_client.php?id=<?php echo $client_id; ?>" 
                           class="btn-delete" 
                           onclick="return confirm('Are you sure you want to delete this client?')">Delete</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>