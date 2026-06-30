<?php
include("../db.php");
session_start();
$email = "";
$password = "";
$email_error = "";
$password_error = "";
$login_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {
        $email_error = "Email is required!";
    }

    if (empty($password)) {
        $password_error = "Password is required!";
    }

    if (empty($email_error) && empty($password_error)) {
        $query = "SELECT id, first_name, last_name, email, password FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $hashed_password = $user["password"];
            if (password_verify($password, $hashed_password)) {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["first_name"] . ' ' . $user['last_name'];
                $_SESSION['email'] = $user['email'];
                header('Location: index.php');
                exit();
            } else {
                $login_error = "Invalid username or password!";
            }
        } else {
            $login_error = "Invalid username or password!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../global.css" />
    <title>Login</title>
    <style>
        /* ----- Override/Enhance Global Styles for Login Page ----- */
        body {
            margin: 0;
            font-family: "Outfit", sans-serif;
        }

        .container {
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #023047, #014f86, #6a11cb, #2575fc);
            background-size: 400% 400%;
            animation: gradientShift 8s ease infinite;
            padding: 1.5rem;
            box-sizing: border-box;
        }

        .form-container {
            background: var(--ghost-white);
            padding: 2.5rem 2.5rem 2rem;
            border-radius: 2.5rem;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            transition: transform 0.25s ease, box-shadow 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(2px);
        }

        .form-container:hover {
            transform: translateY(-4px);
            box-shadow: 0 28px 72px rgba(0, 0, 0, 0.25);
        }

        .form-container h1 {
            margin: 0 0 0.5rem 0;
            font-size: 1.9rem;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: -0.5px;
            text-align: center;
        }

        .form-container .subtitle {
            text-align: center;
            color: #4a5568;
            font-size: 0.95rem;
            margin-bottom: 1.8rem;
            font-weight: 400;
            opacity: 0.8;
        }

        .form-group {
            margin-bottom: 1.2rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.3rem;
            font-weight: 600;
            font-size: 0.9rem;
            color: #2d3748;
            letter-spacing: 0.3px;
        }

        .form-group input {
            width: 100%;
            height: 3.2rem;
            padding: 0 1.2rem;
            border: 2px solid #e2e8f0;
            border-radius: 1.6rem;
            font-family: "Outfit", sans-serif;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #f7fafc;
            box-sizing: border-box;
            outline: none;
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.15);
            background: #ffffff;
        }

        .form-group input::placeholder {
            color: #a0aec0;
            font-weight: 300;
        }

        .error-message {
            color: #e53e3e;
            font-size: 0.8rem;
            display: block;
            margin-top: 0.25rem;
            font-weight: 500;
        }

        .global-error {
            background: #fed7d7;
            color: #c53030;
            padding: 0.75rem 1rem;
            border-radius: 1rem;
            margin-bottom: 1.2rem;
            border-left: 4px solid #e53e3e;
            font-size: 0.9rem;
            text-align: center;
        }

        button[type="submit"] {
            background: var(--primary);
            width: 100%;
            border: none;
            padding: 1rem;
            margin-top: 0.5rem;
            border-radius: 2rem;
            font-family: "Outfit", sans-serif;
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff;
            cursor: pointer;
            transition: background 0.25s, transform 0.15s, box-shadow 0.3s;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 14px rgba(52, 152, 219, 0.35);
        }

        button[type="submit"]:hover {
            background: #2980b9;
            transform: scale(1.01);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.45);
        }

        button[type="submit"]:active {
            transform: scale(0.98);
        }

        .navigate-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.2s;
        }

        .navigate-link:hover {
            color: #1a365d;
            text-decoration: underline;
        }

        /* ----- Add a decorative icon or logo (optional) ----- */
        .login-icon {
            text-align: center;
            margin-bottom: 0.8rem;
        }

        .login-icon svg {
            width: 56px;
            height: 56px;
            fill: var(--primary);
            opacity: 0.9;
        }

        /* Responsive tweaks */
        @media (max-width: 480px) {
            .form-container {
                padding: 1.8rem 1.5rem;
                border-radius: 2rem;
            }

            .form-container h1 {
                font-size: 1.6rem;
            }
        }


        @keyframes gradientShift {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="form-container">

            <!-- Optional icon (replace with your own logo if desired) -->
            <div class="login-icon">
                <svg viewBox="0 0 24 24" width="56" height="56">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                </svg>
            </div>

            <h1>Welcome Back</h1>
            <p class="subtitle">Sign in to manage your invoices</p>

            <?php if (!empty($login_error)): ?>
                <div class="global-error"><?php echo htmlspecialchars($login_error); ?></div>
            <?php endif; ?>

            <form action="login.php" method="post" id="login-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="text" name="email" id="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($email); ?>" />
                    <span id="email-error" class="error-message"><?php echo $email_error; ?></span>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" placeholder="••••••••" />
                    <span id="password-error" class="error-message"><?php echo $password_error; ?></span>
                </div>

                <button type="submit" value="login">Sign In</button>

                <a href="signup.php" class="navigate-link">Don’t have an account? Create one</a>
            </form>
        </div>
    </div>
    <script src="../script.js"></script>
</body>

</html>