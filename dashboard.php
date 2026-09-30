<?php

session_start();

require_once __DIR__ . "/config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

$userQuery = $conn->prepare("
    SELECT full_name, role
    FROM users
    WHERE id = ?
    LIMIT 1
");

$userQuery->bind_param("i", $userId);
$userQuery->execute();

$userResult = $userQuery->get_result();
$user = $userResult->fetch_assoc();

$userName = $user['full_name'] ?? 'Juan Dela Cruz';
$userRole = $user['role'] ?? 'resident';

$servicesQuery = $conn->query("
    SELECT *
    FROM services
    WHERE status = 'Active'
    ORDER BY id ASC
    LIMIT 5
");

$statusCounts = [
    'Pending' => 0,
    'Processing' => 0,
    'Ready for Pickup' => 0,
    'Completed' => 0,
    'Rejected' => 0
];

$countQuery = $conn->prepare("
    SELECT status, COUNT(*) AS total
    FROM requests
    WHERE user_id = ?
    GROUP BY status
");

$countQuery->bind_param("i", $userId);
$countQuery->execute();

$countResult = $countQuery->get_result();

while ($row = $countResult->fetch_assoc()) {

    if (isset($statusCounts[$row['status']])) {
        $statusCounts[$row['status']] = $row['total'];
    }
}

$requestQuery = $conn->prepare("
    SELECT
        requests.id,
        requests.reference_number,
        requests.status,
        requests.submitted_at,
        services.service_name
    FROM requests
    INNER JOIN services
        ON requests.service_id = services.id
    WHERE requests.user_id = ?
    ORDER BY requests.submitted_at DESC
    LIMIT 4
");

$requestQuery->bind_param("i", $userId);
$requestQuery->execute();

$requestResult = $requestQuery->get_result();

$notificationQuery = $conn->prepare("
    SELECT
        requests.id,
        requests.reference_number,
        requests.status,
        requests.submitted_at,
        services.service_name
    FROM requests
    INNER JOIN services
        ON requests.service_id = services.id
    WHERE requests.user_id = ?
    ORDER BY requests.updated_at DESC, requests.submitted_at DESC
    LIMIT 5
");

$notificationQuery->bind_param("i", $userId);
$notificationQuery->execute();

$notificationResult = $notificationQuery->get_result();

$notifications = [];

while ($notification = $notificationResult->fetch_assoc()) {
    $notifications[] = $notification;
}

$notificationCount = count($notifications);

$userInitial = strtoupper(substr(trim($userName), 0, 1));

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Barangay Service Hub</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #17365d;
            min-height: 100vh;

            background:
                linear-gradient(
                    rgba(245, 248, 252, 0.85),
                    rgba(245, 248, 252, 0.85)
                ),
                url("assets/image/hero.jpg") center / cover fixed no-repeat;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button {
            font-family: inherit;
        }

        .app {
            display: flex;
            min-height: 100vh;
            background: transparent;
        }

        .sidebar {
            width: 268px;
            background: #103e70;
            color: white;
            padding: 22px 12px;
            display: flex;
            flex-direction: column;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 100;
        }

        .brand {
            padding: 0 16px 24px;
        }

        .brand-title {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            color: white;
            transition: opacity 0.2s ease;
        }

        .brand-title:hover {
            opacity: 0.9;
        }

        .logo {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .brand h1 {
            font-size: 22px;
            line-height: 1.1;
        }

        .brand p {
            margin-top: 12px;
            font-size: 11px;
            color: #d4e5f7;
        }

        .nav {
            margin-top: 8px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 14px 18px;
            margin-bottom: 5px;
            border-radius: 7px;
            color: #e9f3ff;
            font-size: 15px;
            transition: 0.2s;
        }

        .nav-item:hover {
            background: #185b98;
        }

        .nav-item.active {
            background: #2679c4;
        }

        .nav-icon {
            width: 24px;
            text-align: center;
            font-size: 19px;
        }

        .sidebar-bottom {
            margin-top: auto;
            text-align: center;
            padding: 20px 10px;
            color: #8ebbe2;
            font-size: 13px;
            line-height: 1.5;
        }

        .main {
            margin-left: 268px;
            width: calc(100% - 268px);
        }

        .topbar {
            height: 70px;
            background: rgba(255, 255, 255, 0.94);
            border-bottom: 1px solid #e2eaf3;

            display: flex;
            justify-content: flex-end;
            align-items: center;

            padding: 0 30px;

            position: relative;
            z-index: 50;
        }

        .top-right {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .notification-wrapper {
            position: relative;
        }

        .notification-button {
            width: 42px;
            height: 42px;

            border: none;
            background: transparent;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;
            cursor: pointer;

            color: #17365d;

            transition: background 0.2s ease;
        }

        .notification-button:hover {
            background: #eef5fc;
        }

        .notification-button svg {
            width: 22px;
            height: 22px;
            stroke: currentColor;
        }

        .notification-dot {
            position: absolute;

            width: 9px;
            height: 9px;

            border-radius: 50%;

            background: #ef4444;

            top: 5px;
            right: 6px;

            border: 2px solid white;
        }

        .notification-dropdown {
            position: absolute;

            top: 52px;
            right: -85px;

            width: 330px;

            background: white;

            border: 1px solid #e2eaf3;
            border-radius: 12px;

            box-shadow:
                0 12px 30px rgba(24, 62, 100, 0.15);

            display: none;

            overflow: hidden;
        }

        .notification-dropdown.show {
            display: block;
        }

        .notification-header {
            padding: 16px 18px;

            border-bottom: 1px solid #edf1f6;

            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-header h3 {
            font-size: 15px;
            color: #17365d;
        }

        .notification-header span {
            font-size: 11px;
            color: #6b829e;
        }

        .notification-list {
            max-height: 350px;
            overflow-y: auto;
        }

        .notification-item {
            display: flex;
            gap: 12px;

            padding: 14px 18px;

            border-bottom: 1px solid #edf1f6;

            transition: background 0.2s ease;
        }

        .notification-item:hover {
            background: #f7faff;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item-icon {
            width: 34px;
            height: 34px;

            flex-shrink: 0;

            border-radius: 50%;

            background: #e6f2ff;
            color: #1265aa;

            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notification-item-icon svg {
            width: 17px;
            height: 17px;
        }

        .notification-item-content {
            min-width: 0;
        }

        .notification-item-content strong {
            display: block;

            font-size: 12px;

            color: #17365d;

            margin-bottom: 4px;
        }

        .notification-item-content p {
            font-size: 11px;
            line-height: 1.4;

            color: #5d7591;
        }

        .notification-item-content small {
            display: block;

            margin-top: 5px;

            font-size: 9px;

            color: #8aa0b8;
        }

        .notification-footer {
            border-top: 1px solid #edf1f6;

            padding: 12px;

            text-align: center;
        }

        .notification-footer a {
            color: #1265aa;
            font-size: 11px;
            font-weight: bold;
        }

        .notification-empty {
            padding: 30px 20px;

            text-align: center;

            color: #7890aa;

            font-size: 12px;
        }

        .profile-wrapper {
            position: relative;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 10px;

            border-left: 1px solid #e1e7ef;

            padding-left: 20px;
        }

        .profile-button {
            display: flex;
            align-items: center;
            gap: 10px;

            border: none;
            background: transparent;

            cursor: pointer;

            padding: 5px 7px;

            border-radius: 9px;

            color: #17365d;

            transition: background 0.2s ease;
        }

        .profile-button:hover {
            background: #f0f5fa;
        }

        .profile-avatar {
            width: 38px;
            height: 38px;

            border-radius: 50%;

            background: #173e6d;
            color: white;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 15px;
            font-weight: bold;
        }

        .profile-info {
            text-align: left;
            min-width: 55px;
        }

        .profile-info strong {
            display: block;
            font-size: 13px;
            color: #17365d;
        }

        .profile-info span {
            font-size: 11px;
            color: #58708d;
        }

        .profile-arrow {
            width: 16px;
            height: 16px;

            transition: transform 0.2s ease;
        }

        .profile-button.active .profile-arrow {
            transform: rotate(180deg);
        }

        .profile-dropdown {
            position: absolute;

            top: 55px;
            right: 0;

            width: 210px;

            background: white;

            border: 1px solid #e2eaf3;

            border-radius: 12px;

            box-shadow:
                0 12px 30px rgba(24, 62, 100, 0.15);

            padding: 8px;

            display: none;
        }

        .profile-dropdown.show {
            display: block;
        }

        .dropdown-user {
            padding: 11px 12px 12px;

            border-bottom: 1px solid #edf1f6;

            margin-bottom: 5px;
        }

        .dropdown-user strong {
            display: block;

            font-size: 13px;

            color: #17365d;

            margin-bottom: 3px;
        }

        .dropdown-user span {
            font-size: 10px;
            color: #71869e;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;

            width: 100%;

            padding: 10px 12px;

            border-radius: 7px;

            font-size: 12px;

            color: #34516f;

            transition: background 0.2s ease;
        }

        .dropdown-item:hover {
            background: #f1f6fb;
        }

        .dropdown-item svg {
            width: 17px;
            height: 17px;
            stroke: currentColor;
        }

        .dropdown-item.logout-item {
            color: #c52e2e;
        }

        .dropdown-item.logout-item:hover {
            background: #fff1f1;
        }

        .content {
            padding: 20px 22px 35px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 328px;
            gap: 20px;
        }

        .hero {
            min-height: 273px;
            border-radius: 9px;
            overflow: hidden;
            position: relative;

            background:
                linear-gradient(
                    rgba(185, 225, 255, 0.55),
                    rgba(185, 225, 255, 0.55)
                ),
                url("assets/image/hero.jpg");

            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;

            padding: 32px 36px;

            display: flex;
            align-items: center;
        }

        .hero-content {
            width: 48%;
            position: relative;
            z-index: 2;
        }

        .hero h2 {
            font-size: 34px;
            line-height: 1.08;
            margin-bottom: 12px;
            color: #102f59;
        }

        .hero p {
            color: #174778;
            font-size: 15px;
            line-height: 1.5;
            margin-bottom: 17px;
        }

        .primary-btn {
            display: inline-block;

            background: #07549c;
            color: white;

            padding: 11px 22px;

            border-radius: 9px;

            font-size: 14px;
            font-weight: bold;

            transition: 0.2s;
        }

        .primary-btn:hover {
            background: #06447e;
        }

        .section {
            margin-top: 25px;
        }

        .section-header {
            margin-bottom: 14px;
        }

        .section-header h2 {
            font-size: 23px;
            color: #17365d;
        }

        .section-header p {
            margin-top: 5px;
            color: #4b6890;
            font-size: 13px;
        }

        .service-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
        }

        .service-card {
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid #dce8f4;
            border-radius: 9px;

            padding: 18px;

            min-height: 185px;

            display: flex;
            flex-direction: column;
        }

        .service-icon {
            width: 52px;
            height: 52px;

            border-radius: 17px;

            background: #e5f1ff;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 24px;

            margin-bottom: 14px;
        }

        .service-card h3 {
            font-size: 13px;
            color: #17365d;
            margin-bottom: 9px;
        }

        .service-card p {
            font-size: 11px;
            color: #55708f;
            line-height: 1.5;
            flex: 1;
        }

        .service-arrow {
            width: 28px;
            height: 28px;

            border-radius: 50%;

            background: #dceeff;
            color: #0865b3;

            display: flex;
            align-items: center;
            justify-content: center;

            margin: 12px auto 0;

            font-weight: bold;
        }

        .requests-box {
            background: rgba(255, 255, 255, 0.94);

            border: 1px solid #dce8f4;

            border-radius: 9px;

            margin-top: 20px;

            padding: 18px;
        }

        .requests-title {
            display: flex;
            justify-content: space-between;
            align-items: center;

            margin-bottom: 15px;
        }

        .requests-title h2 {
            font-size: 19px;
        }

        .requests-title p {
            font-size: 12px;
            color: #55708f;
            margin-top: 5px;
        }

        .view-all {
            border: 1px solid #9ac7f4;

            padding: 8px 15px;

            border-radius: 8px;

            color: #1765a8;

            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;

            background: #f5f8fc;

            padding: 12px;

            font-size: 11px;

            color: #21466f;
        }

        td {
            padding: 13px 12px;

            border-bottom: 1px solid #edf1f6;

            font-size: 12px;

            color: #23466c;
        }

        .status {
            display: inline-block;

            padding: 6px 13px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: bold;
        }

        .status-pending {
            background: #fff0c7;
            color: #b66a00;
        }

        .status-processing {
            background: #dbeeff;
            color: #075da8;
        }

        .status-ready {
            background: #e1f7e7;
            color: #17803a;
        }

        .status-completed {
            background: #dff6e5;
            color: #18833c;
        }

        .status-rejected {
            background: #ffe1e1;
            color: #c52e2e;
        }

        .action-btn {
            border: 1px solid #7bb7f0;

            color: #1164a9;

            padding: 6px 18px;

            border-radius: 20px;

            font-size: 10px;
        }

        .side-card {
            background: rgba(255, 255, 255, 0.94);

            border: 1px solid #dce8f4;

            border-radius: 9px;

            padding: 20px;

            margin-bottom: 15px;
        }

        .side-card h2 {
            font-size: 17px;
            margin-bottom: 12px;
        }

        .cta-card {
            background: rgba(234, 244, 255, 0.94);
        }

        .cta-icon {
            width: 60px;
            height: 60px;

            border-radius: 15px;

            background: #d4eaff;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 27px;

            margin-bottom: 13px;
        }

        .cta-card p {
            font-size: 12px;

            color: #4a6686;

            line-height: 1.5;

            margin-bottom: 17px;
        }

        .cta-button {
            display: block;

            background: #1265aa;
            color: white;

            text-align: center;

            padding: 11px;

            border-radius: 8px;

            font-size: 12px;
            font-weight: bold;
        }

        .summary-item {
            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 13px 0;

            border-bottom: 1px solid #edf1f6;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-left {
            display: flex;
            align-items: center;

            gap: 10px;

            font-size: 12px;
        }

        .summary-icon {
            width: 30px;
            height: 30px;

            border-radius: 50%;

            background: #e6f1ff;

            display: flex;
            align-items: center;
            justify-content: center;
        }

        .summary-number {
            font-weight: bold;
            font-size: 13px;
        }

        .announcement {
            padding: 12px 0;

            border-bottom: 1px solid #e8eef5;
        }

        .announcement:last-child {
            border-bottom: none;
        }

        .announcement small {
            color: #55708f;
            font-size: 10px;
        }

        .announcement h3 {
            font-size: 12px;
            margin: 5px 0;
        }

        .announcement p {
            font-size: 11px;

            line-height: 1.45;

            color: #526d8c;
        }

        .all-link {
            color: #0966ad;

            font-size: 11px;

            display: block;

            margin-top: 12px;
        }

        @media (max-width: 1200px) {

            .service-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .hero-content {
                width: 55%;
            }

        }

        @media (max-width: 850px) {

            .sidebar {
                width: 80px;
                padding: 15px 8px;
            }

            .brand h1,
            .brand p,
            .nav-item span,
            .sidebar-bottom {
                display: none;
            }

            .brand {
                padding: 0 4px 20px;
            }

            .brand-title {
                justify-content: center;
            }

            .logo {
                width: 50px;
                height: 50px;
            }

            .nav-item {
                justify-content: center;
                padding: 14px 5px;
            }

            .main {
                margin-left: 80px;
                width: calc(100% - 80px);
            }

            .service-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .hero-content {
                width: 65%;
            }

            .notification-dropdown {
                right: -50px;
            }

        }

        @media (max-width: 600px) {

            .content {
                padding: 12px;
            }

            .topbar {
                padding: 0 10px;
            }

            .top-right {
                gap: 8px;
            }

            .profile {
                padding-left: 8px;
            }

            .profile-info {
                display: none;
            }

            .profile-button {
                gap: 5px;
            }

            .notification-dropdown {
                position: fixed;

                top: 68px;
                left: 10px;
                right: 10px;

                width: auto;
            }

            .profile-dropdown {
                right: 0;
            }

            .hero {
                min-height: 330px;
                padding: 25px;

                background-position: center;
            }

            .hero-content {
                width: 100%;
            }

            .service-grid {
                grid-template-columns: 1fr;
            }

            .requests-box {
                overflow-x: auto;
            }

            table {
                min-width: 650px;
            }

        }

    </style>

</head>

<body>

<div class="app">

    <aside class="sidebar">

        <div class="brand">

            <a
                href="dashboard.php"
                class="brand-title"
            >

                <div class="logo">

                    <img
                        src="assets/image/logo.png"
                        alt="Barangay Service Hub Logo"
                    >

                </div>

                <h1>
                    Barangay<br>
                    Service Hub
                </h1>

            </a>

            <p>
                Mas Mabilis &nbsp;•&nbsp;
                Mas Madali &nbsp;•&nbsp;
                Para sa Lahat
            </p>

        </div>

        <nav class="nav">

            <a
                href="dashboard.php"
                class="nav-item active"
            >

                <div class="nav-icon">
                    ⌂
                </div>

                <span>
                    Home
                </span>

            </a>

            <a
                href="submit-request.php"
                class="nav-item"
            >

                <div class="nav-icon">
                    ⇱
                </div>

                <span>
                    Submit Request
                </span>

            </a>

            <a
                href="my-requests.php"
                class="nav-item"
            >

                <div class="nav-icon">
                    ▤
                </div>

                <span>
                    My Requests
                </span>

            </a>

            <a
                href="services.php"
                class="nav-item"
            >

                <div class="nav-icon">
                    ▦
                </div>

                <span>
                    Services
                </span>

            </a>

            <a
                href="profile.php"
                class="nav-item"
            >

                <div class="nav-icon">
                    ●
                </div>

                <span>
                    Profile
                </span>

            </a>

        </nav>

        <div class="sidebar-bottom">

            <br>

            A Stronger Barangay,<br>
            A Brighter Community

        </div>

    </aside>

    <main class="main">

        <header class="topbar">

            <div class="top-right">

                <div class="notification-wrapper">

                    <button
                        type="button"
                        class="notification-button"
                        id="notificationButton"
                        aria-label="Notifications"
                    >

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >

                            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>

                            <path d="M10 21h4"></path>

                        </svg>

                        <?php if ($notificationCount > 0): ?>

                            <span class="notification-dot"></span>

                        <?php endif; ?>

                    </button>

                    <div
                        class="notification-dropdown"
                        id="notificationDropdown"
                    >

                        <div class="notification-header">

                            <h3>
                                Notifications
                            </h3>

                            <span>
                                <?= $notificationCount; ?> recent
                            </span>

                        </div>

                        <div class="notification-list">

                            <?php if ($notificationCount > 0): ?>

                                <?php foreach ($notifications as $notification): ?>

                                    <?php

                                    $notificationStatus =
                                        $notification['status'];

                                    ?>

                                    <a
                                        href="view-request.php?id=<?= $notification['id']; ?>"
                                        class="notification-item"
                                    >

                                        <div class="notification-item-icon">

                                            <svg
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.8"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >

                                                <path d="M6 2h9l4 4v16H6z"></path>

                                                <path d="M14 2v5h5"></path>

                                                <path d="M9 13h6"></path>

                                                <path d="M9 17h4"></path>

                                            </svg>

                                        </div>

                                        <div class="notification-item-content">

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $notification['service_name']
                                                ); ?>
                                            </strong>

                                            <p>

                                                Request
                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $notification['reference_number']
                                                    ); ?>
                                                </strong>

                                                is currently
                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $notificationStatus
                                                    ); ?>
                                                </strong>.

                                            </p>

                                            <small>

                                                <?= date(
                                                    'M d, Y h:i A',
                                                    strtotime(
                                                        $notification['submitted_at']
                                                    )
                                                ); ?>

                                            </small>

                                        </div>

                                    </a>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <div class="notification-empty">

                                    No notifications yet.

                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="notification-footer">

                            <a href="my-requests.php">

                                View All Requests →

                            </a>

                        </div>

                    </div>

                </div>

                <div class="profile-wrapper">

                    <div class="profile">

                        <button
                            type="button"
                            class="profile-button"
                            id="profileButton"
                            aria-label="Open profile menu"
                        >

                            <div class="profile-avatar">

                                <?= htmlspecialchars($userInitial); ?>

                            </div>

                            <div class="profile-info">

                                <strong>
                                    <?= htmlspecialchars($userName); ?>
                                </strong>

                                <span>
                                    <?= htmlspecialchars(
                                        ucfirst($userRole)
                                    ); ?>
                                </span>

                            </div>

                            <svg
                                class="profile-arrow"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >

                                <path d="m6 9 6 6 6-6"></path>

                            </svg>

                        </button>

                        <div
                            class="profile-dropdown"
                            id="profileDropdown"
                        >

                            <div class="dropdown-user">

                                <strong>
                                    <?= htmlspecialchars($userName); ?>
                                </strong>

                                <span>
                                    <?= htmlspecialchars(
                                        ucfirst($userRole)
                                    ); ?>
                                </span>

                            </div>

                            <a
                                href="profile.php"
                                class="dropdown-item"
                            >

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >

                                    <circle
                                        cx="12"
                                        cy="8"
                                        r="4"
                                    ></circle>

                                    <path
                                        d="M4 21c0-4 3.5-7 8-7s8 3 8 7"
                                    ></path>

                                </svg>

                                My Profile

                            </a>

                            <a
                                href="my-requests.php"
                                class="dropdown-item"
                            >

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >

                                    <path d="M6 3h12v18H6z"></path>

                                    <path d="M9 7h6"></path>

                                    <path d="M9 11h6"></path>

                                    <path d="M9 15h4"></path>

                                </svg>

                                My Requests

                            </a>

                            <a
                                href="logout.php"
                                class="dropdown-item logout-item"
                            >

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >

                                    <path d="M10 17l5-5-5-5"></path>

                                    <path d="M15 12H3"></path>

                                    <path d="M21 19V5"></path>

                                </svg>

                                Logout

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </header>

        <div class="content">

            <div class="dashboard-grid">

                <div>

                    <section class="hero">

                        <div class="hero-content">

                            <h2>
                                Barangay<br>
                                Service Hub
                            </h2>

                            <p>

                                Submit your requests, track your status,
                                and access essential barangay services
                                — all in one place.

                            </p>

                            <a
                                href="submit-request.php"
                                class="primary-btn"
                            >

                                Get Started &nbsp; →

                            </a>

                        </div>

                    </section>

                    <section class="section">

                        <div class="section-header">

                            <h2>
                                Popular Services
                            </h2>

                            <p>
                                Quickly access the most requested barangay services.
                            </p>

                        </div>

                        <div class="service-grid">

                            <?php while (
                                $service =
                                $servicesQuery->fetch_assoc()
                            ): ?>

                                <div class="service-card">

                                    <div class="service-icon">
                                        📄
                                    </div>

                                    <h3>

                                        <?= htmlspecialchars(
                                            $service['service_name']
                                        ); ?>

                                    </h3>

                                    <p>

                                        <?= htmlspecialchars(
                                            substr(
                                                $service['description'],
                                                0,
                                                85
                                            )
                                        ); ?>

                                    </p>

                                    <a
                                        href="submit-request.php?service_id=<?= $service['id']; ?>"
                                        class="service-arrow"
                                    >

                                        →

                                    </a>

                                </div>

                            <?php endwhile; ?>

                        </div>

                    </section>

                    <section class="requests-box">

                        <div class="requests-title">

                            <div>

                                <h2>
                                    My Requests
                                </h2>

                                <p>
                                    Track the status of your submitted requests.
                                </p>

                            </div>

                            <a
                                href="my-requests.php"
                                class="view-all"
                            >

                                View All →

                            </a>

                        </div>

                        <table>

                            <thead>

                                <tr>

                                    <th>
                                        Reference #
                                    </th>

                                    <th>
                                        Service
                                    </th>

                                    <th>
                                        Date Submitted
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                            <?php if ($requestResult->num_rows > 0): ?>

                                <?php while (
                                    $request =
                                    $requestResult->fetch_assoc()
                                ): ?>

                                    <?php

                                    $statusClass = match (
                                        $request['status']
                                    ) {

                                        'Pending' =>
                                            'status-pending',

                                        'Processing' =>
                                            'status-processing',

                                        'Ready for Pickup' =>
                                            'status-ready',

                                        'Completed' =>
                                            'status-completed',

                                        'Rejected' =>
                                            'status-rejected',

                                        default =>
                                            'status-pending'

                                    };

                                    ?>

                                    <tr>

                                        <td>

                                            <?= htmlspecialchars(
                                                $request['reference_number']
                                            ); ?>

                                        </td>

                                        <td>

                                            <?= htmlspecialchars(
                                                $request['service_name']
                                            ); ?>

                                        </td>

                                        <td>

                                            <?= date(
                                                'M d, Y',
                                                strtotime(
                                                    $request['submitted_at']
                                                )
                                            ); ?>

                                        </td>

                                        <td>

                                            <span
                                                class="status <?= $statusClass; ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    $request['status']
                                                ); ?>

                                            </span>

                                        </td>

                                        <td>

                                            <a
                                                href="view-request.php?id=<?= $request['id']; ?>"
                                                class="action-btn"
                                            >

                                                View

                                            </a>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>

                                    <td
                                        colspan="5"
                                        style="text-align:center;"
                                    >

                                        No requests yet.

                                    </td>

                                </tr>

                            <?php endif; ?>

                            </tbody>

                        </table>

                    </section>

                </div>

                <aside>

                    <div class="side-card cta-card">

                        <div class="cta-icon">
                            📄
                        </div>

                        <h2>

                            Need a Barangay<br>
                            Service?

                        </h2>

                        <p>

                            Submit your request online and
                            avoid long lines. It's fast, easy,
                            and convenient!

                        </p>

                        <a
                            href="submit-request.php"
                            class="cta-button"
                        >

                            Submit a Request &nbsp; →

                        </a>

                    </div>

                    <div class="side-card">

                        <h2>
                            Request Status Summary
                        </h2>

                        <div class="summary-item">

                            <div class="summary-left">

                                <div class="summary-icon">
                                    🕐
                                </div>

                                Pending

                            </div>

                            <div class="summary-number">

                                <?= $statusCounts['Pending']; ?>

                            </div>

                        </div>

                        <div class="summary-item">

                            <div class="summary-left">

                                <div class="summary-icon">
                                    🔄
                                </div>

                                In Process

                            </div>

                            <div class="summary-number">

                                <?= $statusCounts['Processing']; ?>

                            </div>

                        </div>

                        <div class="summary-item">

                            <div class="summary-left">

                                <div class="summary-icon">
                                    📦
                                </div>

                                Ready for Pickup

                            </div>

                            <div class="summary-number">

                                <?= $statusCounts['Ready for Pickup']; ?>

                            </div>

                        </div>

                        <div class="summary-item">

                            <div class="summary-left">

                                <div class="summary-icon">
                                    ✓
                                </div>

                                Completed

                            </div>

                            <div class="summary-number">

                                <?= $statusCounts['Completed']; ?>

                            </div>

                        </div>

                        <a
                            href="my-requests.php"
                            class="all-link"
                        >

                            View All Requests →

                        </a>

                    </div>

                    <div class="side-card">

                        <h2>
                            📢 &nbsp;Announcements
                        </h2>

                        <div class="announcement">

                            <small>
                                Sep 28, 2026
                            </small>

                            <h3>
                                Barangay Assembly Meeting
                            </h3>

                            <p>

                                The next barangay assembly meeting
                                will be announced by the barangay office.

                            </p>

                        </div>

                        <div class="announcement">

                            <small>
                                Sep 25, 2026
                            </small>

                            <h3>
                                Updated Service Hours
                            </h3>

                            <p>

                                The barangay hall is open from
                                8:00 AM to 5:00 PM, Monday to Friday.

                            </p>

                        </div>

                        <div class="announcement">

                            <small>
                                Sep 20, 2026
                            </small>

                            <h3>
                                Community Clean-Up Drive
                            </h3>

                            <p>

                                Join the monthly community
                                clean-up activity.

                            </p>

                        </div>

                        <a
                            href="#"
                            class="all-link"
                        >

                            View All Announcements →

                        </a>

                    </div>

                </aside>

            </div>

        </div>

    </main>

</div>

<script>

document.addEventListener("DOMContentLoaded", function () {

    const notificationButton =
        document.getElementById("notificationButton");

    const notificationDropdown =
        document.getElementById("notificationDropdown");

    const profileButton =
        document.getElementById("profileButton");

    const profileDropdown =
        document.getElementById("profileDropdown");

    notificationButton.addEventListener("click", function (event) {

        event.stopPropagation();

        notificationDropdown.classList.toggle("show");

        profileDropdown.classList.remove("show");

        profileButton.classList.remove("active");

    });

    profileButton.addEventListener("click", function (event) {

        event.stopPropagation();

        profileDropdown.classList.toggle("show");

        notificationDropdown.classList.remove("show");

        profileButton.classList.toggle("active");

    });

    notificationDropdown.addEventListener(
        "click",
        function (event) {
            event.stopPropagation();
        }
    );

    profileDropdown.addEventListener(
        "click",
        function (event) {
            event.stopPropagation();
        }
    );

    document.addEventListener("click", function () {

        notificationDropdown.classList.remove("show");

        profileDropdown.classList.remove("show");

        profileButton.classList.remove("active");

    });

});

</script>

</body>

</html>