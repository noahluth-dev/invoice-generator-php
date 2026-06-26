<?php 
include("../db.php");
$first_name = $last_name = $email = $business_name = $address = $mobile = $password = "";
$first_name_error = $last_name_error = $email_error = $business_name_error = $address_error = $mobile_error = $password_error = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $first_name    = trim($_POST["first_name"] ?? '');
    $last_name     = trim($_POST["last_name"] ?? '');
    $email         = trim($_POST["email"] ?? '');
    $business_name = trim($_POST["business_name"] ?? '');
    $address       = trim($_POST["address"] ?? '');
    $mobile        = trim($_POST["mobile"] ?? '');
    $password      = trim($_POST["password"] ?? '');

    if (empty($first_name)) {
        $first_name_error = "First name is required!";
    }
    if (empty($last_name)) {
        $last_name_error = "Last name is required!";
    }
    if (empty($email)) {
        $email_error = "Email is required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email_error = "Please enter a valid email address!";
    }

    if (empty($business_name)) {
        $business_name_error = "Business name is required!";
    }

    if (!empty($mobile) && !preg_match('/^[0-9]{10,15}$/', $mobile)) {
        $mobile_error = "Please enter a valid mobile number (10-15 digits)!";
    }
    if 
    (empty($password)) {
        $password_error = "Password is required!";
    }

    // If no errors, proceed with registration (use prepared statements!)
    if (empty($first_name_error) && empty($last_name_error) && empty($email_error) && 
        empty($business_name_error) && empty($mobile_error) && 
        empty($password_error)) {
        try{
            $query = "INSERT INTO users(first_name,last_name,email,password,business_name,address,mobile) VALUES (?,?,?,?,?,?,?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssssss",$first_name,$last_name,$email,password_hash($password,PASSWORD_DEFAULT),$business_name,$address,$mobile);
            $stmt->execute();
            $stmt->close();

            header("Location:index.php");
        }catch(Exception $e){
            echo "Failed to create user!";
        }
        
        
    }

    
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../global.css">
    <title>signup</title>
</head>
<body>
        <div class="container">
        <div class="form-container">
        <h1>Singup to get started</h1>
        <form action="signup.php" method="post" id="signup-form">
    
    <!-- First Name -->
    <label for="first_name">First Name</label>
    <input type="text" name="first_name" id="first_name" 
           value="<?php echo htmlspecialchars($first_name); ?>">
    <span id="first-name-error" class="error-message"><?php echo $first_name_error; ?></span>
    <br>

    <!-- Last Name -->
    <label for="last_name">Last Name</label>
    <input type="text" name="last_name" id="last_name" 
           value="<?php echo htmlspecialchars($last_name); ?>">
    <span id="last-name-error" class="error-message"><?php echo $last_name_error; ?></span>
    <br>

    <!-- Email -->
    <label for="email">Email</label>
    <input type="text" name="email" id="email" 
           value="<?php echo htmlspecialchars($email); ?>">
    <span id="email-error" class="error-message"><?php echo $email_error; ?></span>
    <br>

    <!-- Business Name -->
    <label for="business_name">Business Name</label>
    <input type="text" name="business_name" id="business_name" 
           value="<?php echo htmlspecialchars($business_name); ?>">
    <span id="business-name-error" class="error-message"><?php echo $business_name_error; ?></span>
    <br>

    <!-- Address (textarea) -->
    <label for="address">Address</label>
    <textarea name="address" id="address"><?php echo htmlspecialchars($address); ?></textarea>
    <span id="address-error" class="error-message"><?php echo $address_error; ?></span>
    <br>

    <!-- Mobile -->
    <label for="mobile">Mobile</label>
    <input type="tel" name="mobile" id="mobile" 
           value="<?php echo htmlspecialchars($mobile); ?>">
    <span id="mobile-error" class="error-message"><?php echo $mobile_error; ?></span>
    <br>

    <!-- Password – Usually left empty for security -->
    <label for="password">Password</label>
    <input type="password" name="password" id="password" value="">
    <span id="password-error" class="error-message"><?php echo $password_error; ?></span>
    <br>

    <button type="submit" value="signup">Sign-up</button>
    <a href="login.php" class="navigate-link">already have an account</a>
</form>
        </div>
    </div>
    <script src="../scripts/signup.js"></script>
</body>
</html>