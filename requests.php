<?php

session_start();


if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$message = "";
$error = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $requestId = intval($_POST["request_id"] ?? 0);
    $status = trim($_POST["status"] ?? "");
    $remarks = trim($_POST["remarks"] ?? "");

    $allowedStatuses = [
        "Pending",
        "Processing",
        "Ready for Pickup",
        "Completed",
        "Rejected"
    ];

    if ($requestId <= 0) {

        $error = "Invalid request.";

    } elseif (!in_array($status, $allowedStatuses, true)) {

        $error = "Invalid status.";

    } else {

        $update = $conn->prepare("
            UPDATE requests
            SET status = ?, remarks = ?
            WHERE id = ?
        ");

        if (!$update) {

            $error = "Database Error: " . $conn->error;

        } else {

            $update->bind_param(
                "ssi",
                $status,
                $remarks,
                $requestId
            );

            if ($update->execute()) {

                $message = "Request updated successfully.";

            } else {

                $error = "Failed to update request: " . $update->error;
            }

            $update->close();
        }
    }
}

$requestQuery = $conn->query("
    SELECT
        requests.id,
        requests.reference_number,
        requests.purpose,
        requests.status,
        requests.remarks,
        requests.submitted_at,
        requests.updated_at,

        users.full_name,
        users.email,

        services.service_name,
        services.fee

    FROM requests

    INNER JOIN users
        ON requests.user_id = users.id

    INNER JOIN services
        ON requests.service_id = services.id

    ORDER BY requests.submitted_at DESC
");

if (!$requestQuery) {

    die("Database Query Error: " . $conn->error);
}

$counts = [
    "Pending" => 0,
    "Processing" => 0,
    "Ready for Pickup" => 0,
    "Completed" => 0,
    "Rejected" => 0
];

$countResult = $conn->query("
    SELECT
        status,
        COUNT(*) AS total
    FROM requests
    GROUP BY status
");

if ($countResult) {

    while ($row = $countResult->fetch_assoc()) {

        if (isset($counts[$row["status"]])) {

            $counts[$row["status"]] = $row["total"];
        }
    }
}


$adminName = $_SESSION["full_name"] ?? "Barangay Administrator";
$adminRole = $_SESSION["role"] ?? "admin";

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
        Manage Requests | Barangay Service Hub
    </title>


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f8fc;
            color: #17365d;
        }


        a {
            text-decoration: none;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;

            width: 268px;

            background: #103e70;
            color: white;

            padding: 22px 12px;

            overflow-y: auto;
        }


        .brand {
            padding: 0 16px 25px;
        }


        .brand-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }


        .logo {
            width: 58px;
            height: 58px;

            border-radius: 50%;

            background: white;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 25px;
        }


        .brand h1 {
            font-size: 21px;
            line-height: 1.1;
        }


        .brand p {
            margin-top: 12px;

            font-size: 10px;

            color: #d5e6f7;
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

            transition: 0.2s;
        }


        .nav-item:hover {
            background: #185b98;
        }


        .nav-item.active {
            background: #2679c4;
        }


        .nav-icon {
            width: 23px;

            text-align: center;

            font-size: 18px;
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

            background: white;

            border-bottom: 1px solid #e3eaf2;

            display: flex;

            align-items: center;

            justify-content: flex-end;

            padding: 0 30px;
        }


        .profile {
            display: flex;

            align-items: center;

            gap: 10px;
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

            text-transform: uppercase;
        }


        .profile-info strong {
            display: block;

            font-size: 13px;
        }


        .profile-info span {
            color: #637b97;

            font-size: 11px;

            text-transform: capitalize;
        }


        .content {
            padding: 30px;
        }


        .page-title h1 {
            font-size: 28px;
        }


        .page-title p {
            margin-top: 7px;

            color: #607895;

            font-size: 14px;
        }


        .alert {
            margin-top: 20px;

            padding: 13px 16px;

            border-radius: 7px;

            font-size: 13px;
        }


        .success {
            background: #e4f7e9;

            border: 1px solid #b9e6c4;

            color: #18743a;
        }


        .error {
            background: #ffe7e7;

            border: 1px solid #f2bcbc;

            color: #b52d2d;
        }

        .stats {
            display: grid;

            grid-template-columns:
                repeat(5, 1fr);

            gap: 14px;

            margin-top: 25px;
        }


        .stat {
            background: white;

            border: 1px solid #dce8f4;

            border-radius: 9px;

            padding: 18px;
        }


        .stat-label {
            color: #617994;

            font-size: 11px;

            margin-bottom: 8px;
        }


        .stat-number {
            font-size: 25px;

            font-weight: bold;
        }

        .requests-card {
            margin-top: 25px;

            background: white;

            border: 1px solid #dce8f4;

            border-radius: 10px;

            padding: 20px;
        }


        .requests-header {
            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 18px;
        }


        .requests-header h2 {
            font-size: 19px;
        }


        .requests-header p {
            margin-top: 5px;

            color: #607895;

            font-size: 12px;
        }


        .table-wrapper {
            overflow-x: auto;
        }


        table {
            width: 100%;

            border-collapse: collapse;

            min-width: 1100px;
        }


        th {
            background: #f5f8fc;

            text-align: left;

            padding: 13px;

            font-size: 11px;

            color: #21466f;
        }


        td {
            padding: 14px 13px;

            border-bottom:
                1px solid #edf1f6;

            font-size: 12px;

            color: #315273;

            vertical-align: middle;
        }


        .reference {
            color: #1265aa;

            font-weight: bold;
        }


        td small {
            color: #71859d;

            font-size: 10px;
        }


        .status {
            display: inline-block;

            padding: 6px 12px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: bold;

            white-space: nowrap;
        }


        .pending {
            background: #fff0c7;

            color: #b66a00;
        }


        .processing {
            background: #dbeeff;

            color: #075da8;
        }


        .ready {
            background: #e1f7e7;

            color: #17803a;
        }


        .completed {
            background: #dff6e5;

            color: #18833c;
        }


        .rejected {
            background: #ffe1e1;

            color: #c52e2e;
        }


        .update-form {
            display: flex;

            align-items: center;

            gap: 7px;

            flex-wrap: nowrap;
        }


        .update-form select {
            border: 1px solid #cbd9e8;

            border-radius: 6px;

            padding: 7px;

            font-size: 10px;

            background: white;

            min-width: 125px;
        }


        .update-form input {
            border: 1px solid #cbd9e8;

            border-radius: 6px;

            padding: 7px;

            font-size: 10px;

            width: 150px;

            outline: none;
        }


        .update-form input:focus,
        .update-form select:focus {
            border-color: #1265aa;
        }


        .update-btn {
            border: none;

            background: #1265aa;

            color: white;

            padding: 8px 12px;

            border-radius: 6px;

            cursor: pointer;

            font-size: 10px;

            font-weight: bold;
        }


        .update-btn:hover {
            background: #0d548f;
        }


        .empty-state {
            text-align: center;

            padding: 45px 20px;

            color: #71859d;
        }


        .empty-state-icon {
            font-size: 35px;

            margin-bottom: 10px;
        }

        .logout-link {
            margin-top: 20px;

            border-top: 1px solid rgba(255,255,255,0.12);

            padding-top: 15px;
        }

        @media (max-width: 1100px) {

            .stats {
                grid-template-columns:
                    repeat(3, 1fr);
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

        }


        @media (max-width: 600px) {

            .topbar {
                padding: 0 15px;
            }


            .content {
                padding: 20px 15px;
            }


            .stats {
                grid-template-columns: 1fr 1fr;
            }


            .profile-info {
                display: none;
            }


            .page-title h1 {
                font-size: 23px;
            }


            .requests-card {
                padding: 15px;
            }

        }

    </style>

</head>


<body>


<aside class="sidebar">


    <div class="brand">

        <div class="brand-title">

            <div class="logo">
                ☀
            </div>

            <h1>
                Barangay<br>
                Service Hub
            </h1>

        </div>

        <p>
            Mas Mabilis • Mas Madali • Para sa Lahat
        </p>

    </div>


    <nav>

        <a
            href="dashboard.php"
            class="nav-item"
        >

            <div class="nav-icon">
                ⌂
            </div>

            <span>
                Dashboard
            </span>

        </a>


        <a
            href="requests.php"
            class="nav-item active"
        >

            <div class="nav-icon">
                ▤
            </div>

            <span>
                Manage Requests
            </span>

        </a>


        <a
            href="../services.php"
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
            href="#"
            class="nav-item"
        >

            <div class="nav-icon">
                👥
            </div>

            <span>
                Residents
            </span>

        </a>


        <a
            href="../logout.php"
            class="nav-item logout-link"
        >

            <div class="nav-icon">
                ↪
            </div>

            <span>
                Logout
            </span>

        </a>

    </nav>


    <div class="sidebar-bottom">

        🏠<br>

        Barangay Administration

    </div>


</aside>


<main class="main">


    <header class="topbar">

        <div class="profile">

            <div class="avatar">

                <?= htmlspecialchars(
                    strtoupper(substr($adminName, 0, 1))
                ); ?>

            </div>


            <div class="profile-info">

                <strong>
                    <?= htmlspecialchars($adminName); ?>
                </strong>

                <span>
                    <?= htmlspecialchars($adminRole); ?>
                </span>

            </div>

        </div>

    </header>


    <div class="content">


        <div class="page-title">

            <h1>
                Manage Requests
            </h1>

            <p>
                Review resident requests and update their status.
            </p>

        </div>


        <?php if ($message !== ""): ?>

            <div class="alert success">

                <?= htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>


        <?php if ($error !== ""): ?>

            <div class="alert error">

                <?= htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <div class="stats">


            <div class="stat">

                <div class="stat-label">
                    Pending
                </div>

                <div class="stat-number">
                    <?= $counts["Pending"]; ?>
                </div>

            </div>


            <div class="stat">

                <div class="stat-label">
                    Processing
                </div>

                <div class="stat-number">
                    <?= $counts["Processing"]; ?>
                </div>

            </div>


            <div class="stat">

                <div class="stat-label">
                    Ready for Pickup
                </div>

                <div class="stat-number">
                    <?= $counts["Ready for Pickup"]; ?>
                </div>

            </div>


            <div class="stat">

                <div class="stat-label">
                    Completed
                </div>

                <div class="stat-number">
                    <?= $counts["Completed"]; ?>
                </div>

            </div>


            <div class="stat">

                <div class="stat-label">
                    Rejected
                </div>

                <div class="stat-number">
                    <?= $counts["Rejected"]; ?>
                </div>

            </div>


        </div>


        <div class="requests-card">


            <div class="requests-header">

                <div>

                    <h2>
                        Resident Requests
                    </h2>

                    <p>
                        All submitted service requests.
                    </p>

                </div>

            </div>



            <div class="table-wrapper">

                <table>


                    <thead>

                        <tr>

                            <th>
                                Reference #
                            </th>

                            <th>
                                Resident
                            </th>

                            <th>
                                Service
                            </th>

                            <th>
                                Purpose
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Update
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if ($requestQuery->num_rows > 0): ?>


                        <?php while ($request = $requestQuery->fetch_assoc()): ?>


                            <?php

                            switch ($request["status"]) {

                                case "Pending":
                                    $statusClass = "pending";
                                    break;

                                case "Processing":
                                    $statusClass = "processing";
                                    break;

                                case "Ready for Pickup":
                                    $statusClass = "ready";
                                    break;

                                case "Completed":
                                    $statusClass = "completed";
                                    break;

                                case "Rejected":
                                    $statusClass = "rejected";
                                    break;

                                default:
                                    $statusClass = "pending";
                            }

                            ?>


                            <tr>

                                <td>

                                    <span class="reference">

                                        <?= htmlspecialchars(
                                            $request["reference_number"]
                                        ); ?>

                                    </span>

                                </td>

                                <td>

                                    <?= htmlspecialchars(
                                        $request["full_name"]
                                    ); ?>

                                    <br>

                                    <small>

                                        <?= htmlspecialchars(
                                            $request["email"]
                                        ); ?>

                                    </small>

                                </td>

                                <td>

                                    <?= htmlspecialchars(
                                        $request["service_name"]
                                    ); ?>

                                </td>

                                <td>

                                    <?= htmlspecialchars(
                                        $request["purpose"]
                                    ); ?>

                                </td>

                                <td>

                                    <?= date(
                                        "M d, Y",
                                        strtotime(
                                            $request["submitted_at"]
                                        )
                                    ); ?>

                                </td>

                                <td>

                                    <span
                                        class="status <?= $statusClass; ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $request["status"]
                                        ); ?>

                                    </span>

                                </td>

                                <td>


                                    <form
                                        method="POST"
                                        class="update-form"
                                    >


                                        <input
                                            type="hidden"
                                            name="request_id"
                                            value="<?= (int) $request["id"]; ?>"
                                        >


                                        <select
                                            name="status"
                                            required
                                        >

                                            <option
                                                value="Pending"
                                                <?= $request["status"] === "Pending"
                                                    ? "selected"
                                                    : ""; ?>
                                            >
                                                Pending
                                            </option>


                                            <option
                                                value="Processing"
                                                <?= $request["status"] === "Processing"
                                                    ? "selected"
                                                    : ""; ?>
                                            >
                                                Processing
                                            </option>


                                            <option
                                                value="Ready for Pickup"
                                                <?= $request["status"] === "Ready for Pickup"
                                                    ? "selected"
                                                    : ""; ?>
                                            >
                                                Ready
                                            </option>


                                            <option
                                                value="Completed"
                                                <?= $request["status"] === "Completed"
                                                    ? "selected"
                                                    : ""; ?>
                                            >
                                                Completed
                                            </option>


                                            <option
                                                value="Rejected"
                                                <?= $request["status"] === "Rejected"
                                                    ? "selected"
                                                    : ""; ?>
                                            >
                                                Rejected
                                            </option>

                                        </select>


                                        <input
                                            type="text"
                                            name="remarks"
                                            placeholder="Remarks"
                                            value="<?= htmlspecialchars(
                                                $request["remarks"] ?? ""
                                            ); ?>"
                                        >


                                        <button
                                            type="submit"
                                            class="update-btn"
                                        >
                                            Update
                                        </button>


                                    </form>


                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="7"
                                class="empty-state"
                            >

                                <div class="empty-state-icon">
                                    ▤
                                </div>

                                No requests have been submitted yet.

                            </td>

                        </tr>


                    <?php endif; ?>


                    </tbody>


                </table>

            </div>


        </div>


    </div>


</main>


</body>

</html>