<?php

session_start();

require_once __DIR__ . "/config/database.php";

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($fullName === '' || $email === '' || $password === '' || $confirmPassword === '') {

        $error = "Please fill in all fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 6) {

        $error = "Password must be at least 6 characters.";

    } elseif ($password !== $confirmPassword) {

        $error = "Passwords do not match.";

    } else {

        $checkSql = "SELECT id FROM users WHERE email = ? LIMIT 1";

        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();

        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {

            $error = "An account with this email already exists.";

        } else {

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $role = "user";

            $sql = "INSERT INTO users
                    (full_name, email, password, role)
                    VALUES (?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "ssss",
                $fullName,
                $email,
                $hashedPassword,
                $role
            );

            if ($stmt->execute()) {

                header("Location: login.php?registered=1");
                exit;

            } else {

                $error = "Registration failed. Please try again.";

            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Create Account | Barangay Service Hub</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #eef5ff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .register-container {
            width: 100%;
            max-width: 450px;
        }

        .register-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 35px rgba(20, 60, 120, 0.12);
        }

        .logo {
            width: 75px;
            height: 75px;
            background: #0b3d91;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 26px;
            font-weight: bold;
        }

        .title {
            text-align: center;
            color: #123b73;
            font-size: 25px;
            font-weight: 700;
        }

        .subtitle {
            text-align: center;
            color: #718096;
            font-size: 14px;
            margin-top: 7px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 17px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            color: #344767;
            font-size: 14px;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #d7e0ec;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
        }

        .form-group input:focus {
            border-color: #1769d1;
            box-shadow: 0 0 0 3px rgba(23, 105, 209, 0.08);
        }

        .register-btn {
            width: 100%;
            border: none;
            background: #0b4ea2;
            color: #ffffff;
            padding: 14px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 5px;
        }

        .register-btn:hover {
            background: #083d80;
        }

        .error {
            background: #fff0f0;
            color: #c62828;
            border: 1px solid #f2c2c2;
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: 13px;
        }

        .success {
            background: #e8f7ee;
            color: #218838;
            border: 1px solid #bce5ca;
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: 13px;
        }

        .login-text {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: #718096;
        }

        .login-text a {
            color: #0b4ea2;
            font-weight: 700;
            text-decoration: none;
        }

        .login-text a:hover {
            text-decoration: underline;
        }

        .back-home {
            text-align: center;
            margin-top: 15px;
        }

        .back-home a {
            color: #718096;
            font-size: 13px;
            text-decoration: none;
        }

        .back-home a:hover {
            color: #0b4ea2;
        }

    </style>

</head>

<body>

<div class="register-container">

    <div class="register-card">

        <div class="logo">
            BS
        </div>

        <h1 class="title">
            Create Account
        </h1>

        <p class="subtitle">
            Register for Barangay Service Hub
        </p>

        <?php if ($error !== ''): ?>

            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <?php if ($success !== ''): ?>

            <div class="success">
                <?= htmlspecialchars($success) ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="form-group">

                <label for="full_name">
                    Full Name
                </label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    placeholder="Enter your full name"
                    value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                    required
                >

            </div>

            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                >

            </div>

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Create a password"
                    required
                >

            </div>

            <div class="form-group">

                <label for="confirm_password">
                    Confirm Password
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Confirm your password"
                    required
                >

            </div>

            <button
                type="submit"
                class="register-btn"
            >
                Create Account
            </button>

        </form>

        <div class="login-text">

            Already have an account?

            <a href="login.php">
                Login
            </a>

        </div>

        <div class="back-home">

            <a href="dashboard.php">
                ← Back to Home
            </a>

        </div>

    </div>

</div>

</body>

</html>