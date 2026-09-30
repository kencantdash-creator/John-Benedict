<?php

session_start();

require_once __DIR__ . "/config/database.php";


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];

$message = "";
$error = "";


$selectedServiceId = isset($_GET["service_id"])
    ? (int) $_GET["service_id"]
    : 0;

$purpose = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $serviceId = (int) ($_POST["service_id"] ?? 0);

    $purpose = trim($_POST["purpose"] ?? "");

    $selectedServiceId = $serviceId;


    if ($serviceId <= 0) {

        $error = "Please select a service.";

    } elseif ($purpose === "") {

        $error = "Please enter the purpose of your request.";

    } else {

        $serviceCheck = $conn->prepare("
            SELECT
                id,
                service_name,
                description,
                requirements,
                processing_days,
                fee
            FROM services
            WHERE id = ?
            AND status = 'Active'
            LIMIT 1
        ");


        if (!$serviceCheck) {

            $error = "Unable to check the selected service.";

        } else {

            $serviceCheck->bind_param(
                "i",
                $serviceId
            );

            $serviceCheck->execute();

            $serviceResult = $serviceCheck->get_result();


            if ($serviceResult->num_rows === 0) {

                $error = "The selected service is not available.";

            } else {


                $referenceNumber =
                    "BRGY-" .
                    date("Y") .
                    "-" .
                    strtoupper(substr(uniqid(), -6));

                $insert = $conn->prepare("
                    INSERT INTO requests
                    (
                        user_id,
                        service_id,
                        reference_number,
                        purpose,
                        status
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        'Pending'
                    )
                ");


                if (!$insert) {

                    $error = "Unable to prepare the request.";

                } else {

                    $insert->bind_param(
                        "iiss",
                        $userId,
                        $serviceId,
                        $referenceNumber,
                        $purpose
                    );


                    if ($insert->execute()) {

                        $message =
                            "Request submitted successfully! " .
                            "Your reference number is " .
                            $referenceNumber;

                        $selectedServiceId = 0;
                        $purpose = "";

                    } else {

                        $error =
                            "Something went wrong while submitting your request.";

                    }

                    $insert->close();
                }
            }

            $serviceCheck->close();
        }
    }
}


$servicesQuery = $conn->query("
    SELECT
        id,
        service_name,
        description,
        requirements,
        processing_days,
        fee
    FROM services
    WHERE status = 'Active'
    ORDER BY service_name ASC
");


if (!$servicesQuery) {

    die("Database Query Error: " . $conn->error);

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
        Submit Request | Barangay Service Hub
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

            justify-content: center;

            align-items: center;

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
            font-size: 10px;

            color: #d5e6f7;

            margin-top: 12px;
        }

        .nav-item {
            display: flex;

            align-items: center;

            gap: 15px;

            padding: 14px 18px;

            color: white;

            border-radius: 7px;

            margin-bottom: 5px;

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

            background: rgba(255, 255, 255, 0.96);

            border-bottom: 1px solid #e3eaf2;

            display: flex;

            align-items: center;

            justify-content: flex-end;

            padding: 0 30px;

            position: sticky;

            top: 0;

            z-index: 50;

            backdrop-filter: blur(6px);
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

            display: flex;

            align-items: center;

            justify-content: center;

            color: white;

            font-weight: bold;
        }


        .profile-info strong {
            display: block;

            font-size: 13px;
        }


        .profile-info span {
            font-size: 11px;

            color: #637b97;
        }


        .content {
            padding: 30px;

            max-width: 1000px;
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


        .form-card {
            margin-top: 25px;

            background: rgba(255, 255, 255, 0.95);

            border: 1px solid #dce8f4;

            border-radius: 10px;

            padding: 28px;

            box-shadow:
                0 8px 25px rgba(24, 68, 105, 0.06);
        }


        .form-card h2 {
            font-size: 20px;

            margin-bottom: 20px;
        }


        .form-group {
            margin-bottom: 20px;
        }


        label {
            display: block;

            font-size: 13px;

            font-weight: bold;

            margin-bottom: 8px;

            color: #21466f;
        }


        select,
        textarea {
            width: 100%;

            border: 1px solid #cbd9e8;

            border-radius: 7px;

            padding: 12px;

            font-family: inherit;

            font-size: 13px;

            outline: none;

            background: white;

            color: #17365d;
        }


        select:focus,
        textarea:focus {
            border-color: #2679c4;

            box-shadow:
                0 0 0 3px rgba(38, 121, 196, 0.08);
        }


        textarea {
            min-height: 130px;

            resize: vertical;
        }


        .service-info {
            background: #f2f8ff;

            border: 1px solid #d8eafa;

            border-radius: 8px;

            padding: 15px;

            margin-top: 10px;

            font-size: 12px;

            color: #456482;

            line-height: 1.6;
        }


        .service-info strong {
            color: #17365d;
        }

        .alert {
            padding: 13px 15px;

            border-radius: 7px;

            margin-bottom: 20px;

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


        .submit-btn {
            border: none;

            background: #1265aa;

            color: white;

            padding: 12px 25px;

            border-radius: 7px;

            cursor: pointer;

            font-size: 13px;

            font-weight: bold;

            transition:
                background 0.2s ease,
                transform 0.2s ease;
        }


        .submit-btn:hover {
            background: #0d548f;

            transform: translateY(-1px);
        }

        .reference-box {
            margin-top: 15px;

            background: #f2f8ff;

            border: 1px solid #cfe4f8;

            border-radius: 8px;

            padding: 15px;
        }


        .reference-label {
            font-size: 12px;

            color: #607895;

            margin-bottom: 5px;
        }


        .reference-number {
            font-size: 20px;

            font-weight: bold;

            color: #1265aa;
        }


        .request-link {
            display: inline-block;

            margin-top: 12px;

            color: #1265aa;

            font-size: 13px;

            font-weight: bold;
        }


        .request-link:hover {
            text-decoration: underline;
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

            .topbar {
                height: 62px;

                padding: 0 15px;
            }


            .content {
                padding: 20px 15px;
            }


            .form-card {
                padding: 20px;
            }


            .profile-info {
                display: none;
            }


            .page-title h1 {
                font-size: 24px;
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

        <nav>

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
                class="nav-item active"
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


            <div class="profile">


                <div class="avatar">

                    <?= strtoupper(
                        substr(
                            $_SESSION['full_name'] ?? 'R',
                            0,
                            1
                        )
                    ); ?>

                </div>


                <div class="profile-info">


                    <strong>

                        <?= htmlspecialchars(
                            $_SESSION['full_name'] ?? 'Resident'
                        ); ?>

                    </strong>


                    <span>
                        Resident
                    </span>


                </div>


            </div>


        </header>


        <div class="content">


            <div class="page-title">


                <h1>
                    Submit a Request
                </h1>


                <p>
                    Submit a request for a barangay service quickly and conveniently.
                </p>


            </div>



            <div class="form-card">


                <?php if ($message !== ""): ?>


                    <div class="alert success">

                        <?= htmlspecialchars($message); ?>


                    </div>


                    <div class="reference-box">


                        <div class="reference-label">
                            Your Reference Number
                        </div>


                        <div class="reference-number">

                            <?= htmlspecialchars(
                                preg_replace(
                                    '/.*is\s/',
                                    '',
                                    $message
                                )
                            ); ?>

                        </div>


                        <a
                            href="my-requests.php"
                            class="request-link"
                        >
                            View My Requests →
                        </a>


                    </div>


                <?php endif; ?>



                <?php if ($error !== ""): ?>


                    <div class="alert error">

                        <?= htmlspecialchars($error); ?>

                    </div>


                <?php endif; ?>


                <h2>
                    Request Information
                </h2>



                <form
                    method="POST"
                    action=""
                >

                    <div class="form-group">


                        <label for="service_id">
                            Select Service
                        </label>


                        <select
                            name="service_id"
                            id="service_id"
                            required
                        >


                            <option value="">
                                -- Select a service --
                            </option>


                            <?php

                            $serviceOptions = $conn->query("
                                SELECT
                                    id,
                                    service_name,
                                    fee,
                                    processing_days
                                FROM services
                                WHERE status = 'Active'
                                ORDER BY service_name ASC
                            ");

                            ?>


                            <?php if (
                                $serviceOptions &&
                                $serviceOptions->num_rows > 0
                            ): ?>


                                <?php while (
                                    $service = $serviceOptions->fetch_assoc()
                                ): ?>


                                    <option
                                        value="<?= (int) $service['id']; ?>"
                                        <?= (
                                            $selectedServiceId ===
                                            (int) $service['id']
                                        )
                                            ? 'selected'
                                            : ''; ?>
                                    >

                                        <?= htmlspecialchars(
                                            $service['service_name']
                                        ); ?>


                                        <?php if (
                                            (float) $service['fee'] > 0
                                        ): ?>

                                            -
                                            ₱<?= number_format(
                                                (float) $service['fee'],
                                                2
                                            ); ?>

                                        <?php else: ?>

                                            - FREE

                                        <?php endif; ?>


                                    </option>


                                <?php endwhile; ?>


                            <?php else: ?>


                                <option
                                    value=""
                                    disabled
                                >
                                    No services available
                                </option>


                            <?php endif; ?>


                        </select>


                    </div>


                    <div class="form-group">


                        <label for="purpose">
                            Purpose of Request
                        </label>


                        <textarea
                            name="purpose"
                            id="purpose"
                            placeholder="Example: I need this service for employment purposes."
                            required
                        ><?= htmlspecialchars(
                            $purpose
                        ); ?></textarea>


                    </div>


                    <div class="service-info">


                        <strong>
                            Before submitting:
                        </strong>


                        <br>


                        Make sure the information you provide is correct.


                        Your request will initially be marked as


                        <strong>
                            Pending
                        </strong>


                        and will be reviewed by the barangay administrator.


                    </div>



                    <br>

                    <button
                        type="submit"
                        class="submit-btn"
                    >

                        Submit Request →

                    </button>


                </form>


            </div>


        </div>


    </main>


</div>


</body>

</html>