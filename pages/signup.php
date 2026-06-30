<?php
include("../db.php");
$first_name = $last_name = $email = $business_name = $address = $mobile = $password = "";
$first_name_error = $last_name_error = $email_error = $business_name_error = $address_error = $mobile_error = $password_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
    if (empty($password)) {
        $password_error = "Password is required!";
    }

    if (
        empty($first_name_error) && empty($last_name_error) && empty($email_error) &&
        empty($business_name_error) && empty($mobile_error) && empty($password_error)
    ) {
        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users(first_name, last_name, email, password, business_name, address, mobile) VALUES (?,?,?,?,?,?,?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssssss", $first_name, $last_name, $email, $hashed, $business_name, $address, $mobile);
            $stmt->execute();
            $stmt->close();
            header("Location: login.php");
            exit();
        } catch (Exception $e) {
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
    <title>Sign Up</title>
    <style>
        /* ----- Consistent styling with login page ----- */
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
        }

        .form-container {
            background: var(--ghost-white);
            padding: 2.5rem 2.5rem 2rem;
            border-radius: 2.5rem;
            width: 100%;
            max-width: 480px;
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
            margin: 0 0 0.3rem 0;
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

        .signup-icon {
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .signup-icon svg {
            width: 56px;
            height: 56px;
            fill: var(--primary);
            opacity: 0.9;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0 1rem;
        }

        @media (max-width: 480px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
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

        .form-group input,
        .form-group textarea {
            width: 100%;
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

        .form-group input {
            height: 3.2rem;
        }

        .form-group textarea {
            height: 4rem;
            padding-top: 0.8rem;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.15);
            background: #ffffff;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
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

        /* Responsive */
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
            <!-- Decorative Icon -->
            <div class="signup-icon">
                <svg viewBox="0 0 24 24" width="56" height="56">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                </svg>
            </div>

            <h1>Create Account</h1>
            <p class="subtitle">Start managing your invoices today</p>

            <form action="signup.php" method="post" id="signup-form">
                <!-- Row: First & Last Name -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" name="first_name" id="first_name" placeholder="John" value="<?php echo htmlspecialchars($first_name); ?>" />
                        <span id="first-name-error" class="error-message"><?php echo $first_name_error; ?></span>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" name="last_name" id="last_name" placeholder="Doe" value="<?php echo htmlspecialchars($last_name); ?>" />
                        <span id="last-name-error" class="error-message"><?php echo $last_name_error; ?></span>
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="text" name="email" id="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($email); ?>" />
                    <span id="email-error" class="error-message"><?php echo $email_error; ?></span>
                </div>

                <!-- Business Name -->
                <div class="form-group">
                    <label for="business_name">Business Name</label>
                    <input type="text" name="business_name" id="business_name" placeholder="My Business" value="<?php echo htmlspecialchars($business_name); ?>" />
                    <span id="business-name-error" class="error-message"><?php echo $business_name_error; ?></span>
                </div>

                <!-- Address -->
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea name="address" id="address" placeholder="Street, City, State, ZIP"><?php echo htmlspecialchars($address); ?></textarea>
                    <span id="address-error" class="error-message"><?php echo $address_error; ?></span>
                </div>

                <!-- Row: Mobile & Password -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="mobile">Mobile</label>
                        <input type="tel" name="mobile" id="mobile" placeholder="+1 234 567 890" value="<?php echo htmlspecialchars($mobile); ?>" />
                        <span id="mobile-error" class="error-message"><?php echo $mobile_error; ?></span>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" placeholder="••••••••" />
                        <span id="password-error" class="error-message"><?php echo $password_error; ?></span>
                    </div>
                </div>

                <button type="submit" value="signup">Create Account</button>

                <a href="login.php" class="navigate-link">Already have an account? Sign in</a>
            </form>
        </div>
    </div>
    <script src="../scripts/signup.js"></script>
</body>

</html>