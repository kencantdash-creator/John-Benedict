<?php

session_start();

require_once __DIR__ . "/config/database.php";

$error = "";
$registered = isset($_GET['registered']) && $_GET['registered'] == '1';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $error = "Please enter your email and password.";

    } else {

        $sql = "
            SELECT id, full_name, email, role, password
            FROM users
            WHERE email = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            $error = "Database error: " . $conn->error;

        } else {

            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows === 1) {

                $user = $result->fetch_assoc();

                if (password_verify($password, $user["password"])) {

                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["full_name"] = $user["full_name"];
                    $_SESSION["email"] = $user["email"];
                    $_SESSION["role"] = $user["role"];

                    if ($user["role"] === "admin") {

                        header("Location: admin/dashboard.php");
                        exit;

                    }

                    header("Location: dashboard.php");
                    exit;

                } else {

                    $error = "Invalid email or password.";

                }

            } else {

                $error = "Invalid email or password.";

            }

            $stmt->close();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login - Barangay Service Hub</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
    margin: 0;
    min-height: 100vh;
    font-family: Arial, Helvetica, sans-serif;
    background:
        linear-gradient(
            rgba(245, 248, 252, 0.82),
            rgba(245, 248, 252, 0.82)
        ),
        url("assets/image/hero.jpg") center / cover fixed no-repeat;
    display: flex;
    align-items: center;
    justify-content: center;
}

        .login-card {
            width: 420px;
            max-width: 92%;
            background: #ffffff;
            border-radius: 12px;
            padding: 38px 40px;
            box-shadow:
                0 8px 30px rgba(0, 0, 0, 0.08);
        }

        .logo {
            width: 70px;
            height: 70px;
            margin: 0 auto 15px;
            background: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        h1 {
            text-align: center;
            margin: 0;
            color: #124b91;
            font-size: 27px;
        }

        .subtitle {
            text-align: center;
            color: #70809a;
            margin: 8px 0 28px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            color: #173b6d;
            font-weight: bold;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        input {
            width: 100%;
            height: 46px;
            border: 1px solid #cbd8e8;
            border-radius: 8px;
            padding: 0 14px;
            font-size: 14px;
            outline: none;
            color: #1c2d42;
            background: #ffffff;
        }

        input:focus {
            border-color: #1257a6;
            box-shadow:
                0 0 0 3px rgba(18, 87, 166, 0.10);
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 52px;
        }

        .toggle-password {
            position: absolute;
            right: 9px;
            top: 50%;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            border: none;
            background: transparent;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #68788f;
            border-radius: 6px;
        }

        .toggle-password:hover {
            color: #1257a6;
            background: #f1f6fc;
        }

        .toggle-password:focus {
            outline: none;
            color: #1257a6;
        }

        .toggle-password svg {
            width: 20px;
            height: 20px;
            display: block;
        }

        .alert {
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert.error {
            background: #fff0f0;
            border: 1px solid #ffbaba;
            color: #d60000;
        }

        .alert.success {
            background: #effaf2;
            border: 1px solid #a8dfb5;
            color: #168238;
        }

        .login-button {
            width: 100%;
            height: 46px;
            border: none;
            border-radius: 8px;
            background: #1257a6;
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .login-button:hover {
            background: #0d478b;
        }

        .register-text {
            text-align: center;
            margin-top: 25px;
            color: #70809a;
            font-size: 14px;
        }

        .register-text a {
            color: #0756aa;
            font-weight: bold;
            text-decoration: none;
        }

        .register-text a:hover {
            text-decoration: underline;
        }

        .home-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #647a99;
            text-decoration: none;
            font-size: 14px;
        }

        .home-link:hover {
            color: #1257a6;
        }

    </style>

</head>

<body>

<div class="login-card">

    <div class="logo">
        <img
            src="assets/image/logo.png"
            alt="Barangay Service Hub Logo"
        >
    </div>

    <h1>
        Barangay Service Hub
    </h1>

    <div class="subtitle">
        Login to access your account
    </div>

    <?php if ($registered): ?>

        <div class="alert success">
            Account created successfully!
            You can now login.
        </div>

    <?php endif; ?>

    <?php if ($error !== ""): ?>

        <div class="alert error">
            <?= htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>

    <form method="POST" action="">

        <div class="form-group">

            <label for="email">
                Email Address
            </label>

            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter your email"
                autocomplete="email"
                required
            >

        </div>

        <div class="form-group">

            <label for="password">
                Password
            </label>

            <div class="password-wrapper">

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >

                <button
                    type="button"
                    class="toggle-password"
                    id="togglePassword"
                    aria-label="Show password"
                    title="Show password"
                >

                    <svg
                        id="eyeIcon"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >

                        <path
                            d="M2.062 12.348a1 1 0 0 1 0-.696
                            10.75 10.75 0 0 1 19.876 0
                            1 1 0 0 1 0 .696
                            10.75 10.75 0 0 1-19.876 0"
                        />

                        <circle
                            cx="12"
                            cy="12"
                            r="3"
                        />

                    </svg>

                </button>

            </div>

        </div>

        <button
            type="submit"
            class="login-button"
        >
            Login
        </button>

    </form>

    <div class="register-text">

        Don't have an account?

        <a href="register.php">
            Create Account
        </a>

    </div>

    <a
        href="index.php"
        class="home-link"
    >
        ← Back to Home
    </a>

</div>

<script>

const passwordInput =
    document.getElementById("password");

const togglePassword =
    document.getElementById("togglePassword");

const eyeIcon =
    document.getElementById("eyeIcon");

togglePassword.addEventListener(
    "click",
    function () {

        if (passwordInput.type === "password") {

            passwordInput.type = "text";

            togglePassword.setAttribute(
                "aria-label",
                "Hide password"
            );

            togglePassword.setAttribute(
                "title",
                "Hide password"
            );

            eyeIcon.innerHTML = `

                <path
                    d="M2.062 12.348a1 1 0 0 1 0-.696
                    10.75 10.75 0 0 1 19.876 0
                    1 1 0 0 1 0 .696
                    10.75 10.75 0 0 1-19.876 0"
                />

                <circle
                    cx="12"
                    cy="12"
                    r="3"
                />

            `;

        } else {

            passwordInput.type = "password";

            togglePassword.setAttribute(
                "aria-label",
                "Show password"
            );

            togglePassword.setAttribute(
                "title",
                "Show password"
            );

            eyeIcon.innerHTML = `

                <path
                    d="M3 3l18 18"
                />

                <path
                    d="M10.584 10.587
                    a2 2 0 0 0 2.829 2.829"
                />

                <path
                    d="M9.363 5.365
                    A10.94 10.94 0 0 0 2.062 12.348
                    a1 1 0 0 0 0 .696
                    10.75 10.75 0 0 0 15.167 5.834"
                />

                <path
                    d="M14.06 18.635
                    A10.94 10.94 0 0 0 21.938 12.348
                    a1 1 0 0 0-.696
                    10.75 10.75 0 0 0-2.239-3.592"
                />

            `;

        }

    }
);

</script>

</body>

</html>