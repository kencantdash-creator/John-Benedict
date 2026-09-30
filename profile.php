<?php

session_start();

require_once __DIR__ . "/config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($fullName === '' || $email === '') {

        $error = "Please fill in all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        $check = $conn->prepare("
            SELECT id
            FROM users
            WHERE email = ?
              AND id != ?
            LIMIT 1
        ");

        $check->bind_param("si", $email, $userId);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $error = "That email address is already being used.";

        } else {

            $update = $conn->prepare("
                UPDATE users
                SET full_name = ?, email = ?
                WHERE id = ?
            ");

            $update->bind_param(
                "ssi",
                $fullName,
                $email,
                $userId
            );

            if ($update->execute()) {

                $_SESSION['full_name'] = $fullName;
                $_SESSION['email'] = $email;

                $message = "Profile updated successfully.";

            } else {

                $error = "Unable to update your profile.";
            }
        }
    }
}

$stmt = $conn->prepare("
    SELECT full_name, email, role
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

if (!$user) {

    session_destroy();

    header("Location: login.php");
    exit;
}

$userName = $user['full_name'];
$userEmail = $user['email'];
$userRole = $user['role'];

?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Profile - Barangay Service Hub</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f7fb;
            color: #1f2937;
        }

        .navbar {
            height: 70px;
            background: #0756a3;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
        }

        .brand {
            font-size: 21px;
            font-weight: bold;
            color: white;
            text-decoration: none;
            transition: opacity 0.2s ease;
        }

        .brand:hover {
            color: white;
            opacity: 0.9;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-right a {
            color: white;
            text-decoration: none;
            font-size: 14px;
        }

        .nav-right a:hover {
            opacity: 0.9;
        }

        .logout {
            background: #ffffff;
            color: #0756a3 !important;
            padding: 9px 16px;
            border-radius: 7px;
            font-weight: bold;
        }

        .logout:hover {
            opacity: 0.9;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-title {
            margin-bottom: 25px;
        }

        .page-title h1 {
            font-size: 28px;
            color: #12345b;
            margin-bottom: 6px;
        }

        .page-title p {
            color: #6b7280;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 18px;
            padding-bottom: 25px;
            margin-bottom: 25px;
            border-bottom: 1px solid #e5e7eb;
        }

        .avatar {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            background: #0756a3;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
            font-weight: bold;
            flex-shrink: 0;
        }

        .profile-header h2 {
            font-size: 21px;
            margin-bottom: 5px;
        }

        .profile-header span {
            color: #6b7280;
            font-size: 14px;
        }

        .alert {
            padding: 13px 16px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            font-size: 15px;
            outline: none;
        }

        input:focus {
            border-color: #0756a3;
        }

        .role-box {
            background: #f3f6fa;
            padding: 12px 14px;
            border-radius: 7px;
            color: #374151;
            font-size: 14px;
        }

        .button-row {
            display: flex;
            justify-content: flex-end;
            margin-top: 25px;
        }

        button {
            border: none;
            background: #0756a3;
            color: white;
            padding: 12px 22px;
            border-radius: 7px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #064987;
        }

        @media (max-width: 600px) {

            .navbar {
                padding: 0 18px;
            }

            .brand {
                font-size: 17px;
            }

            .nav-right {
                gap: 10px;
            }

            .nav-right a:not(.logout) {
                display: none;
            }

            .container {
                margin-top: 25px;
            }

            .card {
                padding: 20px;
            }

            .page-title h1 {
                font-size: 24px;
            }

        }

    </style>

</head>

<body>

<nav class="navbar">
    <a href="dashboard.php" class="brand">
        Barangay Service Hub
    </a>

    <div class="nav-right">

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="my-requests.php">
            My Requests
        </a>

        <a href="logout.php" class="logout">
            Logout
        </a>

    </div>

</nav>


<div class="container">

    <div class="page-title">

        <h1>My Profile</h1>

        <p>
            Manage your personal information and account details.
        </p>

    </div>


    <div class="card">

        <div class="profile-header">

            <div class="avatar">
                <?= strtoupper(substr($userName, 0, 1)); ?>
            </div>

            <div>

                <h2>
                    <?= htmlspecialchars($userName); ?>
                </h2>

                <span>
                    <?= htmlspecialchars($userEmail); ?>
                </span>

            </div>

        </div>


        <?php if ($message): ?>

            <div class="alert success">
                <?= htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>


        <?php if ($error): ?>

            <div class="alert error">
                <?= htmlspecialchars($error); ?>
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
                    value="<?= htmlspecialchars($userName); ?>"
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
                    value="<?= htmlspecialchars($userEmail); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Account Type
                </label>

                <div class="role-box">
                    <?= htmlspecialchars(ucfirst($userRole)); ?>
                </div>

            </div>


            <div class="button-row">

                <button type="submit">
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>

</body>
</html>