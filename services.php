<?php

session_start();

require_once __DIR__ . "/config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];

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

$userName = $user['full_name'] ?? 'Resident';
$userRole = $user['role'] ?? 'resident';

$userQuery->close();

$servicesQuery = $conn->query("
    SELECT
        id,
        service_name,
        description,
        requirements,
        processing_days,
        fee,
        status
    FROM services
    WHERE status = 'Active'
    ORDER BY id ASC
");

if (!$servicesQuery) {
    die("
        <div style='
            font-family: Arial;
            padding: 30px;
            color: #b91c1c;
            background: #fee2e2;
            margin: 30px;
            border-radius: 10px;
        '>
            <h2>Database Query Error</h2>
            <p>" . htmlspecialchars($conn->error) . "</p>
        </div>
    ");
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

    <title>
        Services | Barangay Service Hub
    </title>

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
                url("assets/image/hero.jpg")
                center / cover fixed no-repeat;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .app {
            display: flex;
            min-height: 100vh;
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
            color: white;
            cursor: pointer;
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
            color: white;
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
            transition:
                background 0.2s ease,
                padding-left 0.2s ease;
        }

        .nav-item:hover {
            background: #185b98;
            padding-left: 22px;
        }

        .nav-item.active {
            background: #2679c4;
        }

        .nav-icon {
            width: 24px;
            text-align: center;
            font-size: 19px;
            flex-shrink: 0;
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
            min-height: 100vh;
        }

        .topbar {
            height: 70px;
            background: rgba(255, 255, 255, 0.96);
            border-bottom: 1px solid #e2eaf3;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 50;
            backdrop-filter: blur(6px);
        }

        .top-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 1px solid #e1e7ef;
            padding-left: 20px;
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

        .profile-info strong {
            display: block;
            font-size: 13px;
            color: #17365d;
        }

        .profile-info span {
            font-size: 11px;
            color: #58708d;
        }

        .content {
            padding: 25px 30px 40px;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h2 {
            font-size: 28px;
            color: #17365d;
            margin-bottom: 7px;
        }

        .page-header p {
            color: #55708f;
            font-size: 14px;
        }

        .service-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .service-card {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #dce8f4;
            border-radius: 10px;
            padding: 22px;
            display: flex;
            flex-direction: column;
            min-height: 290px;
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }

        .service-card:hover {
            transform: translateY(-2px);
            box-shadow:
                0 8px 20px rgba(
                    16,
                    62,
                    112,
                    0.10
                );
        }

        .service-icon {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            background: #e5f1ff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1265aa;
            font-size: 23px;
            font-weight: bold;
            margin-bottom: 18px;
        }

        .service-icon::before {
            content: "S";
        }

        .service-card h3 {
            color: #17365d;
            font-size: 17px;
            margin-bottom: 10px;
        }

        .service-description {
            color: #55708f;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 15px;
            flex: 1;
        }

        .service-details {
            border-top: 1px solid #edf1f6;
            padding-top: 14px;
            margin-top: auto;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 8px;
            font-size: 12px;
        }

        .detail-row:last-child {
            margin-bottom: 0;
        }

        .detail-label {
            color: #71849b;
            flex-shrink: 0;
        }

        .detail-value {
            color: #17365d;
            font-weight: bold;
            text-align: right;
            max-width: 65%;
            word-break: break-word;
        }

        .request-btn {
            display: block;
            width: 100%;
            background: #1265aa;
            color: white;
            text-align: center;
            padding: 11px 15px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: bold;
            margin-top: 16px;
            transition:
                background 0.2s ease,
                transform 0.2s ease;
        }

        .request-btn:hover {
            background: #0d528b;
            transform: translateY(-1px);
        }

        .empty-state {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #dce8f4;
            border-radius: 10px;
            padding: 50px 25px;
            text-align: center;
        }

        .empty-icon {
            width: 65px;
            height: 65px;
            border-radius: 18px;
            background: #e5f1ff;
            color: #1265aa;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 25px;
            font-weight: bold;
        }

        .empty-icon::before {
            content: "S";
        }

        .empty-state h3 {
            color: #17365d;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #6a7f98;
            font-size: 13px;
        }

        @media (max-width: 1100px) {

            .service-grid {
                grid-template-columns: repeat(2, 1fr);
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

            .nav-item:hover {
                padding-left: 5px;
            }

            .main {
                margin-left: 80px;
                width: calc(100% - 80px);
            }

        }

        @media (max-width: 600px) {

            .content {
                padding: 18px 12px 30px;
            }

            .topbar {
                padding: 0 15px;
            }

            .profile-info {
                display: none;
            }

            .profile {
                border-left: none;
                padding-left: 0;
            }

            .service-grid {
                grid-template-columns: 1fr;
            }

            .page-header h2 {
                font-size: 24px;
            }

            .service-card {
                min-height: auto;
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
                title="Back to Home"
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
                class="nav-item"
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
                class="nav-item active"
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

            A Stronger Barangay,<br>

            A Brighter Community

        </div>

    </aside>

    <main class="main">

        <header class="topbar">

            <div class="top-right">

                <div class="profile">

                    <div class="profile-avatar">

                        <?= strtoupper(
                            substr(
                                $userName,
                                0,
                                1
                            )
                        ); ?>

                    </div>

                    <div class="profile-info">

                        <strong>

                            <?= htmlspecialchars(
                                $userName
                            ); ?>

                        </strong>

                        <span>

                            <?= htmlspecialchars(
                                ucfirst($userRole)
                            ); ?>

                        </span>

                    </div>

                </div>

            </div>

        </header>

        <div class="content">

            <div class="page-header">

                <h2>
                    Barangay Services
                </h2>

                <p>
                    Browse available barangay services and submit your request online.
                </p>

            </div>

            <?php if ($servicesQuery->num_rows > 0): ?>

                <div class="service-grid">

                    <?php while (
                        $service = $servicesQuery->fetch_assoc()
                    ): ?>

                        <div class="service-card">

                            <div class="service-icon"></div>

                            <h3>

                                <?= htmlspecialchars(
                                    $service['service_name']
                                ); ?>

                            </h3>

                            <p class="service-description">

                                <?= htmlspecialchars(
                                    $service['description']
                                ); ?>

                            </p>

                            <div class="service-details">

                                <div class="detail-row">

                                    <span class="detail-label">
                                        Processing Time
                                    </span>

                                    <span class="detail-value">

                                        <?php

                                        $days =
                                            (int)
                                            $service['processing_days'];

                                        if ($days === 1) {

                                            echo "1 day";

                                        } elseif ($days > 1) {

                                            echo $days . " days";

                                        } else {

                                            echo "N/A";

                                        }

                                        ?>

                                    </span>

                                </div>

                                <div class="detail-row">

                                    <span class="detail-label">
                                        Fee
                                    </span>

                                    <span class="detail-value">

                                        <?php

                                        $fee =
                                            (float)
                                            $service['fee'];

                                        if ($fee <= 0) {

                                            echo "Free";

                                        } else {

                                            echo "₱" .
                                                number_format(
                                                    $fee,
                                                    2
                                                );

                                        }

                                        ?>

                                    </span>

                                </div>

                                <div class="detail-row">

                                    <span class="detail-label">
                                        Requirements
                                    </span>

                                    <span class="detail-value">

                                        <?= htmlspecialchars(
                                            $service['requirements']
                                        ); ?>

                                    </span>

                                </div>

                            </div>

                            <a
                                href="submit-request.php?service_id=<?= (int) $service['id']; ?>"
                                class="request-btn"
                            >

                                Request This Service

                            </a>

                        </div>

                    <?php endwhile; ?>

                </div>

            <?php else: ?>

                <div class="empty-state">

                    <div class="empty-icon"></div>

                    <h3>
                        No Services Available
                    </h3>

                    <p>
                        There are currently no active barangay services.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </main>

</div>

</body>

</html>