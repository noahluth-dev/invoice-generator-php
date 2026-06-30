<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include("../../db.php");

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// --- Fetch current user data ---
$user_sql = "SELECT first_name, last_name, email, mobile FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// --- Fetch company settings (if exists) ---
$settings_sql = "SELECT * FROM company_settings WHERE user_id = ?";
$settings_stmt = $conn->prepare($settings_sql);
$settings_stmt->bind_param("i", $user_id);
$settings_stmt->execute();
$settings = $settings_stmt->get_result()->fetch_assoc();

// --- Handle Personal Information Update ---
if (isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $mobile     = trim($_POST['mobile']);

    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = "First name, last name, and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email already used by another user
        $check_email = "SELECT id FROM users WHERE email = ? AND id != ?";
        $check_stmt = $conn->prepare($check_email);
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $error = "Email already in use by another account.";
        } else {
            $update = "UPDATE users SET first_name = ?, last_name = ?, email = ?, mobile = ? WHERE id = ?";
            $upd_stmt = $conn->prepare($update);
            $upd_stmt->bind_param("ssssi", $first_name, $last_name, $email, $mobile, $user_id);
            if ($upd_stmt->execute()) {
                $success = "Personal information updated successfully.";
                // Refresh user data
                $user['first_name'] = $first_name;
                $user['last_name']  = $last_name;
                $user['email']      = $email;
                $user['mobile']     = $mobile;
                // Update session name
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            } else {
                $error = "Failed to update profile.";
            }
        }
    }
}

// --- Handle Business Settings Update ---
if (isset($_POST['update_business'])) {
    $business_name   = trim($_POST['business_name']);
    $business_address= trim($_POST['business_address']);
    $business_phone  = trim($_POST['business_phone']);
    $business_email  = trim($_POST['business_email']);
    $business_website= trim($_POST['business_website']);
    $business_gstin  = trim($_POST['business_gstin']);
    $bank_name       = trim($_POST['bank_name']);
    $bank_account    = trim($_POST['bank_account']);
    $bank_ifsc       = trim($_POST['bank_ifsc']);
    $payment_terms   = trim($_POST['payment_terms']);
    $invoice_prefix  = trim($_POST['invoice_prefix']);
    $tax_rate_default= floatval($_POST['tax_rate_default']);
    $currency_symbol = trim($_POST['currency_symbol']);
    $currency_code   = trim($_POST['currency_code']);

    if (empty($business_name)) {
        $error = "Business name is required.";
    } else {
        // Check if settings exist, then update or insert
        if ($settings) {
            $sql = "UPDATE company_settings SET 
                        business_name = ?, business_address = ?, business_phone = ?, business_email = ?, 
                        business_website = ?, business_gstin = ?, bank_name = ?, bank_account_number = ?, 
                        bank_ifsc = ?, payment_terms = ?, invoice_prefix = ?, tax_rate_default = ?, 
                        currency_symbol = ?, currency_code = ? 
                    WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssssssssi", 
                $business_name, $business_address, $business_phone, $business_email, 
                $business_website, $business_gstin, $bank_name, $bank_account, 
                $bank_ifsc, $payment_terms, $invoice_prefix, $tax_rate_default, 
                $currency_symbol, $currency_code, $user_id
            );
        } else {
            $sql = "INSERT INTO company_settings (
                        user_id, business_name, business_address, business_phone, business_email, 
                        business_website, business_gstin, bank_name, bank_account_number, bank_ifsc, 
                        payment_terms, invoice_prefix, tax_rate_default, currency_symbol, currency_code
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issssssssssssss", 
                $user_id, $business_name, $business_address, $business_phone, $business_email, 
                $business_website, $business_gstin, $bank_name, $bank_account, $bank_ifsc, 
                $payment_terms, $invoice_prefix, $tax_rate_default, $currency_symbol, $currency_code
            );
        }
        if ($stmt->execute()) {
            $success = "Business settings updated successfully.";
            // Refresh settings
            $settings = [
                'business_name' => $business_name,
                'business_address' => $business_address,
                'business_phone' => $business_phone,
                'business_email' => $business_email,
                'business_website' => $business_website,
                'business_gstin' => $business_gstin,
                'bank_name' => $bank_name,
                'bank_account_number' => $bank_account,
                'bank_ifsc' => $bank_ifsc,
                'payment_terms' => $payment_terms,
                'invoice_prefix' => $invoice_prefix,
                'tax_rate_default' => $tax_rate_default,
                'currency_symbol' => $currency_symbol,
                'currency_code' => $currency_code
            ];
        } else {
            $error = "Failed to update business settings.";
        }
    }
}

// --- Handle Password Change ---
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (empty($current) || empty($new) || empty($confirm)) {
        $error = "All password fields are required.";
    } elseif ($new !== $confirm) {
        $error = "New passwords do not match.";
    } elseif (strlen($new) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Verify current password
        $pass_sql = "SELECT password FROM users WHERE id = ?";
        $pass_stmt = $conn->prepare($pass_sql);
        $pass_stmt->bind_param("i", $user_id);
        $pass_stmt->execute();
        $hash = $pass_stmt->get_result()->fetch_assoc()['password'];

        if (!password_verify($current, $hash)) {
            $error = "Current password is incorrect.";
        } else {
            $new_hash = password_hash($new, PASSWORD_DEFAULT);
            $upd_pass = "UPDATE users SET password = ? WHERE id = ?";
            $upd_stmt = $conn->prepare($upd_pass);
            $upd_stmt->bind_param("si", $new_hash, $user_id);
            if ($upd_stmt->execute()) {
                $success = "Password changed successfully.";
            } else {
                $error = "Failed to change password.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../global.css">
    <link rel="stylesheet" href="profile.css">
    <title>Profile</title>
    
</head>
<body>
    <div class="main-wrapper">
        <?php include '../../components/navbar.php'; ?>
       
        <div class="content">
             <header class="page-header">
                <h3>My Profile</h3>
            </header>
            <div class="profile-container">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Personal Information -->
                <div class="profile-card">
                    <h2>👤 Personal Information</h2>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name <span class="required">*</span></label>
                                <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Last Name <span class="required">*</span></label>
                                <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email <span class="required">*</span></label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Mobile</label>
                                <input type="text" name="mobile" value="<?php echo htmlspecialchars($user['mobile'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="btn-group">
                            <button type="submit" name="update_profile" class="btn-submit">Update Profile</button>
                        </div>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="profile-card">
                    <h2>🔑 Change Password</h2>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Current Password <span class="required">*</span></label>
                                <input type="password" name="current_password" required>
                            </div>
                            <div class="form-group">
                                <label>New Password <span class="required">*</span></label>
                                <input type="password" name="new_password" required minlength="6">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password <span class="required">*</span></label>
                            <input type="password" name="confirm_password" required>
                        </div>
                        <div class="btn-group">
                            <button type="submit" name="change_password" class="btn-submit">Change Password</button>
                        </div>
                    </form>
                </div>

                <!-- Business Settings -->
                <div class="profile-card">
                    <h2>🏢 Business Settings</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>Business Name <span class="required">*</span></label>
                            <input type="text" name="business_name" value="<?php echo htmlspecialchars($settings['business_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Business Address</label>
                            <textarea name="business_address" rows="2"><?php echo htmlspecialchars($settings['business_address'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Business Phone</label>
                                <input type="text" name="business_phone" value="<?php echo htmlspecialchars($settings['business_phone'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Business Email</label>
                                <input type="email" name="business_email" value="<?php echo htmlspecialchars($settings['business_email'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Business Website</label>
                                <input type="url" name="business_website" value="<?php echo htmlspecialchars($settings['business_website'] ?? ''); ?>" placeholder="https://example.com">
                            </div>
                            <div class="form-group">
                                <label>GSTIN / VAT</label>
                                <input type="text" name="business_gstin" value="<?php echo htmlspecialchars($settings['business_gstin'] ?? ''); ?>">
                            </div>
                        </div>
                        <hr style="margin: 20px 0; border: 1px solid #e2e8f0;">
                        <h3 style="margin-top:0;">Bank Details</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Bank Name</label>
                                <input type="text" name="bank_name" value="<?php echo htmlspecialchars($settings['bank_name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Account Number</label>
                                <input type="text" name="bank_account" value="<?php echo htmlspecialchars($settings['bank_account_number'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>IFSC / SWIFT Code</label>
                            <input type="text" name="bank_ifsc" value="<?php echo htmlspecialchars($settings['bank_ifsc'] ?? ''); ?>">
                        </div>
                        <hr style="margin: 20px 0; border: 1px solid #e2e8f0;">
                        <h3 style="margin-top:0;">Invoice Defaults</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Invoice Prefix</label>
                                <input type="text" name="invoice_prefix" value="<?php echo htmlspecialchars($settings['invoice_prefix'] ?? 'INV'); ?>" placeholder="e.g. INV">
                            </div>
                            <div class="form-group">
                                <label>Default Tax Rate (%)</label>
                                <input type="number" name="tax_rate_default" step="0.01" min="0" max="100" value="<?php echo htmlspecialchars($settings['tax_rate_default'] ?? 0); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Currency Symbol</label>
                                <input type="text" name="currency_symbol" value="<?php echo htmlspecialchars($settings['currency_symbol'] ?? '$'); ?>" placeholder="$">
                            </div>
                            <div class="form-group">
                                <label>Currency Code</label>
                                <input type="text" name="currency_code" value="<?php echo htmlspecialchars($settings['currency_code'] ?? 'USD'); ?>" placeholder="USD">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Payment Terms (e.g. Net 15)</label>
                            <input type="text" name="payment_terms" value="<?php echo htmlspecialchars($settings['payment_terms'] ?? 'Net 15'); ?>" placeholder="Net 15">
                        </div>
                        <div class="btn-group">
                            <button type="submit" name="update_business" class="btn-submit">Save Business Settings</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</body>
</html>