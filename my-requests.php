<?php

session_start();

require_once __DIR__ . "/config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];

$deleted = isset($_GET['deleted']);
$error = $_GET['error'] ?? "";

$sql = "
    SELECT
        r.id,
        r.reference_number,
        r.purpose,
        r.status,
        r.submitted_at,
        r.updated_at,
        s.service_name,
        s.fee
    FROM requests r
    INNER JOIN services s
        ON r.service_id = s.id
    WHERE r.user_id = ?
    ORDER BY r.submitted_at DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die(
        "Database Query Error: " .
        htmlspecialchars($conn->error)
    );
}

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();

$requests = [];

while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

$stmt->close();

function statusClass($status)
{
    switch ($status) {
        case "Pending":
            return "pending";

        case "Processing":
            return "processing";

        case "Ready for Pickup":
            return "ready";

        case "Completed":
            return "completed";

        case "Rejected":
            return "rejected";

        default:
            return "pending";
    }
}

$userName = $_SESSION['full_name'] ?? 'Resident';

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
    My Requests | Barangay Service Hub
</title>

<style>

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family:
        Arial,
        Helvetica,
        sans-serif;
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
    min-height: 100vh;
    display: flex;
}

.sidebar {
    width: 268px;
    background: #103e70;
    color: white;
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    padding: 22px 12px;
    z-index: 100;
}

.brand {
    padding: 0 16px 25px;
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
    font-size: 21px;
    line-height: 1.1;
    color: white;
}

.brand p {
    margin-top: 12px;
    font-size: 10px;
    color: #d5e6f7;
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
    color: white;
    font-size: 14px;
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
    width: 23px;
    text-align: center;
    font-size: 18px;
    flex-shrink: 0;
}

.sidebar-bottom {
    position: absolute;
    bottom: 25px;
    left: 20px;
    right: 20px;
    text-align: center;
    color: #8ebbe2;
    font-size: 12px;
    line-height: 1.5;
}

.main {
    margin-left: 268px;
    width: calc(100% - 268px);
    min-height: 100vh;
}

.topbar {
    height: 70px;
    background:
        rgba(255, 255, 255, 0.96);
    border-bottom:
        1px solid #e3eaf2;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 0 30px;
    position: sticky;
    top: 0;
    z-index: 50;
    backdrop-filter: blur(6px);
}

.header-right {
    display: flex;
    align-items: center;
    gap: 20px;
}

.notification {
    position: relative;
}

.notification button {
    border: none;
    background: transparent;
    cursor: pointer;
    padding: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification svg {
    width: 22px;
    height: 22px;
    stroke: #17365d;
}

.notification-dot {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 8px;
    height: 8px;
    background: #ef4444;
    border-radius: 50%;
    border: 2px solid white;
}

.profile {
    position: relative;
    display: flex;
    align-items: center;
}

.profile-button {
    border: none;
    background: transparent;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    padding: 0;
}

.avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #173e6d;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
}

.profile-info strong {
    display: block;
    font-size: 13px;
    color: #17365d;
}

.profile-info span {
    font-size: 11px;
    color: #637b97;
}

.profile-arrow {
    font-size: 10px;
    color: #637b97;
}

.dropdown {
    display: none;
    position: absolute;
    right: 0;
    top: 50px;
    width: 190px;
    background: white;
    border:
        1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow:
        0 8px 25px rgba(0, 0, 0, 0.12);
    overflow: hidden;
    z-index: 200;
}

.dropdown.show {
    display: block;
}

.dropdown a {
    display: block;
    padding: 13px 16px;
    color: #17365d;
    font-size: 14px;
}

.dropdown a:hover {
    background: #f1f5f9;
}

.notification-dropdown {
    width: 280px;
    right: -100px;
}

.notification-title {
    padding: 14px 16px;
    font-weight: bold;
    color: #17365d;
    border-bottom:
        1px solid #e5e7eb;
}

.notification-empty {
    padding: 18px 16px;
    color: #64748b;
    font-size: 14px;
}

.content {
    padding: 30px;
}

.page-title {
    margin-bottom: 25px;
}

.page-title h1 {
    font-size: 28px;
    color: #17365d;
}

.page-title p {
    margin-top: 7px;
    color: #607895;
    font-size: 14px;
}

.alert {
    padding: 13px 16px;
    border-radius: 7px;
    margin-bottom: 20px;
    font-size: 13px;
}

.alert.success {
    background: #e4f7e9;
    border: 1px solid #b9e6c4;
    color: #18743a;
}

.alert.error {
    background: #ffe7e7;
    border: 1px solid #f2bcbc;
    color: #b52d2d;
}

.empty {
    background:
        rgba(255, 255, 255, 0.95);
    border:
        1px solid #dce8f4;
    border-radius: 10px;
    min-height: 250px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    text-align: center;
    padding: 30px;
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
    font-size: 25px;
    font-weight: bold;
    margin-bottom: 15px;
}

.empty-icon::before {
    content: "R";
}

.empty h3 {
    color: #17365d;
    margin-bottom: 8px;
}

.empty p {
    color: #64748b;
    margin-bottom: 18px;
    font-size: 13px;
}

.btn {
    display: inline-block;
    background: #1265aa;
    color: white;
    padding: 11px 18px;
    border-radius: 7px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-size: 13px;
    font-weight: bold;
    transition:
        background 0.2s ease,
        transform 0.2s ease;
}

.btn:hover {
    background: #0d548f;
    transform: translateY(-1px);
}

.request-card {
    background:
        rgba(255, 255, 255, 0.95);
    border:
        1px solid #dce8f4;
    border-radius: 10px;
    overflow-x: auto;
    box-shadow:
        0 5px 18px rgba(16, 62, 112, 0.05);
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

th {
    background: #f8fafc;
    text-align: left;
    padding: 15px;
    font-size: 12px;
    color: #64748b;
    border-bottom:
        1px solid #e5e7eb;
    white-space: nowrap;
}

td {
    padding: 15px;
    border-bottom:
        1px solid #e5e7eb;
    font-size: 13px;
    color: #344e6e;
    vertical-align: middle;
}

tr:last-child td {
    border-bottom: none;
}

tbody tr {
    transition: background 0.2s ease;
}

tbody tr:hover {
    background: #f8fbff;
}

.reference {
    font-weight: bold;
    color: #123f70;
    white-space: nowrap;
}

.status {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}

.status.pending {
    background: #fef3c7;
    color: #92400e;
}

.status.processing {
    background: #dbeafe;
    color: #1d4ed8;
}

.status.ready {
    background: #ede9fe;
    color: #6d28d9;
}

.status.completed {
    background: #dcfce7;
    color: #166534;
}

.status.rejected {
    background: #fee2e2;
    color: #991b1b;
}

.view-btn {
    display: inline-block;
    color: #1265aa;
    text-decoration: none;
    font-weight: 600;
    font-size: 13px;
    padding: 6px 10px;
    border-radius: 5px;
}

.view-btn:hover {
    background: #eaf4ff;
    text-decoration: none;
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
        padding:
            0 4px 20px;
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
        width:
            calc(100% - 80px);
    }

    .content {
        padding: 20px;
    }

}

@media (max-width: 600px) {

    .topbar {
        height: 62px;
        padding: 0 15px;
    }

    .profile-info {
        display: none;
    }

    .profile-arrow {
        display: none;
    }

    .header-right {
        gap: 10px;
    }

    .notification-dropdown {
        right: -50px;
    }

    .content {
        padding: 20px 15px;
    }

    .page-title h1 {
        font-size: 24px;
    }

    .page-title p {
        font-size: 13px;
    }

    .request-card {
        border-radius: 8px;
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
                    ＋
                </div>

                <span>
                    Submit Request
                </span>

            </a>

            <a
                href="my-requests.php"
                class="nav-item active"
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

            A Stronger Barangay,<br>

            A Brighter Community

        </div>

    </aside>

    <main class="main">

        <header class="topbar">

            <div class="header-right">

                <div class="notification">

                    <button
                        type="button"
                        onclick="toggleNotifications()"
                        aria-label="Notifications"
                    >

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                        >

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M14.857 17.082a23.848 23.848 0 0 1-5.714 0
                                M18.75 10.5c0 7.142 3 7.142 3 7.142H2.25
                                s3 0 3-7.142a6.75 6.75 0 0 1 13.5 0Z"
                            />

                        </svg>

                    </button>

                    <span class="notification-dot"></span>

                    <div
                        id="notificationDropdown"
                        class="dropdown notification-dropdown"
                    >

                        <div class="notification-title">
                            Notifications
                        </div>

                        <div class="notification-empty">
                            No new notifications.
                        </div>

                    </div>

                </div>

                <div class="profile">

                    <button
                        type="button"
                        class="profile-button"
                        onclick="toggleProfile()"
                    >

                        <div class="avatar">

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
                                Resident
                            </span>

                        </div>

                        <span class="profile-arrow">
                            ▼
                        </span>

                    </button>

                    <div
                        id="profileDropdown"
                        class="dropdown"
                    >

                        <a href="profile.php">
                            Profile
                        </a>

                        <a href="my-requests.php">
                            Request History
                        </a>

                        <a href="logout.php">
                            Logout
                        </a>

                    </div>

                </div>

            </div>

        </header>

        <div class="content">

            <div class="page-title">

                <h1>
                    My Requests
                </h1>

                <p>
                    Track and view the status of your submitted barangay service requests.
                </p>

            </div>

            <?php if ($deleted): ?>

                <div class="alert success">
                    Request cancelled successfully.
                </div>

            <?php endif; ?>

            <?php if ($error === "cannot_delete"): ?>

                <div class="alert error">
                    This request cannot be cancelled because it is already being processed.
                </div>

            <?php endif; ?>

            <?php if (count($requests) === 0): ?>

                <div class="empty">

                    <div class="empty-icon"></div>

                    <h3>
                        No Requests Yet
                    </h3>

                    <p>
                        You don't have any service requests yet.
                    </p>

                    <a
                        href="submit-request.php"
                        class="btn"
                    >
                        Request a Service
                    </a>

                </div>

            <?php else: ?>

                <div class="request-card">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Reference
                                </th>

                                <th>
                                    Service
                                </th>

                                <th>
                                    Purpose
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Submitted
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach (
                                $requests
                                as $request
                            ): ?>

                                <tr>

                                    <td>

                                        <span class="reference">

                                            <?= htmlspecialchars(
                                                $request['reference_number']
                                            ); ?>

                                        </span>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $request['service_name']
                                        ); ?>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $request['purpose']
                                        ); ?>

                                    </td>

                                    <td>

                                        <span
                                            class="status <?= statusClass($request['status']); ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $request['status']
                                            ); ?>

                                        </span>

                                    </td>

                                    <td>

                                        <?= date(
                                            "M d, Y h:i A",
                                            strtotime(
                                                $request['submitted_at']
                                            )
                                        ); ?>

                                    </td>

                                    <td>

                                        <a
                                            href="view-request.php?id=<?= (int) $request['id']; ?>"
                                            class="view-btn"
                                        >
                                            View
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </main>

</div>

<script>

function toggleProfile() {

    const dropdown =
        document.getElementById(
            "profileDropdown"
        );

    const notification =
        document.getElementById(
            "notificationDropdown"
        );

    notification.classList.remove(
        "show"
    );

    dropdown.classList.toggle(
        "show"
    );

}

function toggleNotifications() {

    const dropdown =
        document.getElementById(
            "notificationDropdown"
        );

    const profile =
        document.getElementById(
            "profileDropdown"
        );

    profile.classList.remove(
        "show"
    );

    dropdown.classList.toggle(
        "show"
    );

}

document.addEventListener(
    "click",
    function(event) {

        const profile =
            document.querySelector(
                ".profile"
            );

        const notification =
            document.querySelector(
                ".notification"
            );

        const profileDropdown =
            document.getElementById(
                "profileDropdown"
            );

        const notificationDropdown =
            document.getElementById(
                "notificationDropdown"
            );

        if (
            profile &&
            !profile.contains(event.target)
        ) {

            profileDropdown.classList.remove(
                "show"
            );

        }

        if (
            notification &&
            !notification.contains(event.target)
        ) {

            notificationDropdown.classList.remove(
                "show"
            );

        }

    }
);

</script>

</body>

</html>