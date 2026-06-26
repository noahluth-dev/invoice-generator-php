<?php 
include("../db.php");
session_start();
$email = "";
$password = "";
$email_error = "";
$password_error = "";
$login_error = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $email = $_POST["email"];
    $password = $_POST["password"];

    if(empty($email) || empty($password)){
        $email_error = "email is required!";
    }

    if(empty($password)){
        $password_error = "password is required!";
    }

    if(empty($email_error) && empty($password_error)){
      $query = "SELECT id,first_name,last_name,email,password FROM users WHERE email = ?";
      $stmt = $conn->prepare($query);
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $result = $stmt->get_result();
      if($result->num_rows === 1){
        $user = $result->fetch_assoc();
        $hashed_password = $user["password"];
        if(password_verify($password, $hashed_password)){
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["user_name"] = $user["first_name"].' '.$user['last_name'];
        $_SESSION['email'] = $user['email'];
        header('Location:index.php');
        exit();
        }else{
            $login_error = "invalid username or password!";
        }
      }else{
        $login_error = "invalid username or password!";
      }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../global.css">
    <title>login</title>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <?php if (!empty($login_error)): ?>
                    <p class="error-message" style="background:#ffe6e6; padding:8px; border-radius:4px;">
                        <?php echo $login_error; ?>
                    </p>
                <?php endif; ?>
        <h1>Login to make your invoices</h1>
        <form action="login.php" method="post" id="login-form">
            <label for="email">Email</label>
            <input type="text" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>">
            <span id="email-error" class="error-message"><?php echo $email_error?></span>
            <br>
            <label for="password">Password</label>
            <input type="password" name="password" id="password">
            <span id="password-error" class="error-message"><?php echo $password_error?></span>
            <br>
            <button type="submit" value="login">Login</button>
            <a href="signup.php" class="navigate-link">create an account</a>
        </form>
        </div>
    </div>
    <script src="../script.js"></script>
</body>
</html>