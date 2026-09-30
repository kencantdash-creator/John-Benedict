<?php

session_start();

require_once __DIR__ . "/config/database.php";

$services = [];

$sql = "SELECT id, service_name, description, processing_days, fee
        FROM services
        ORDER BY service_name ASC
        LIMIT 6";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Barangay Service Hub</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #26364d;
            background: #ffffff;
        }


        .navbar {
            width: 100%;
            height: 74px;
            background: #ffffff;
            border-bottom: 1px solid #e6ebf2;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 7%;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .brand-logo {
            width: 43px;
            height: 43px;

            background: #0b3d91;
            color: white;

            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 14px;
            font-weight: 800;
        }

        .brand-info strong {
            display: block;
            color: #123b73;
            font-size: 15px;
        }

        .brand-info span {
            display: block;
            color: #8795a8;
            font-size: 10px;
            margin-top: 3px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 28px;
        }

        .nav-links a {
            color: #53647b;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .nav-links a:hover {
            color: #0b4ea2;
        }

        .login-btn {
            background: #0b4ea2;
            color: white !important;

            padding: 10px 19px;

            border-radius: 7px;
        }

        .login-btn:hover {
            background: #083d80;
        }


        .hero {
            background: #eef5ff;
            min-height: 540px;

            display: flex;
            align-items: center;

            padding: 70px 7%;
        }

        .hero-content {
            width: 55%;
        }

        .hero-label {
            display: inline-block;

            background: #dceaff;
            color: #0b4ea2;

            padding: 8px 13px;

            border-radius: 20px;

            font-size: 12px;
            font-weight: 700;

            margin-bottom: 18px;
        }

        .hero h1 {
            color: #123b73;
            font-size: 48px;
            line-height: 1.15;

            max-width: 650px;
        }

        .hero h1 span {
            color: #0b4ea2;
        }

        .hero p {
            color: #63748b;
            font-size: 16px;
            line-height: 1.7;

            max-width: 590px;

            margin-top: 20px;
        }

        .hero-buttons {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        .primary-btn {
            background: #0b4ea2;
            color: white;

            padding: 14px 23px;

            border-radius: 8px;

            text-decoration: none;

            font-size: 14px;
            font-weight: 700;
        }

        .primary-btn:hover {
            background: #083d80;
        }

        .secondary-btn {
            background: white;
            color: #0b4ea2;

            padding: 14px 23px;

            border-radius: 8px;

            text-decoration: none;

            font-size: 14px;
            font-weight: 700;

            border: 1px solid #cbd9eb;
        }

        .secondary-btn:hover {
            background: #f7faff;
        }


        .hero-visual {
            width: 45%;

            display: flex;
            justify-content: center;
            align-items: center;
        }

        .portal-card {
            width: 380px;
            background: white;

            border-radius: 18px;

            padding: 28px;

            box-shadow: 0 20px 50px rgba(20, 60, 120, 0.12);

            border: 1px solid #e0e8f3;
        }

        .portal-header {
            display: flex;
            align-items: center;
            gap: 13px;

            padding-bottom: 20px;
            border-bottom: 1px solid #edf1f6;
        }

        .portal-icon {
            width: 50px;
            height: 50px;

            background: #e5efff;
            color: #0b4ea2;

            border-radius: 11px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 22px;
            font-weight: bold;
        }

        .portal-header strong {
            display: block;
            color: #243c60;
            font-size: 15px;
        }

        .portal-header span {
            display: block;
            color: #8492a5;
            font-size: 11px;
            margin-top: 4px;
        }

        .portal-row {
            display: flex;
            justify-content: space-between;
            align-items: center;

            padding: 16px 0;

            border-bottom: 1px solid #f0f3f7;
        }

        .portal-row:last-child {
            border-bottom: none;
        }

        .portal-row-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .mini-icon {
            width: 32px;
            height: 32px;

            background: #f0f5fb;

            border-radius: 7px;

            display: flex;
            align-items: center;
            justify-content: center;

            color: #41668f;
            font-size: 13px;
        }

        .portal-row strong {
            font-size: 12px;
            color: #40536d;
        }

        .status {
            font-size: 10px;
            font-weight: 700;

            padding: 5px 9px;

            border-radius: 20px;
        }

        .status.processing {
            background: #e5f0ff;
            color: #1769d1;
        }

        .status.ready {
            background: #e8f7ee;
            color: #218838;
        }

        .status.pending {
            background: #fff5d9;
            color: #a87500;
        }


        .services {
            padding: 75px 7%;
            background: #ffffff;
        }

        .section-heading {
            text-align: center;
            margin-bottom: 40px;
        }

        .section-heading h2 {
            color: #173d72;
            font-size: 30px;
        }

        .section-heading p {
            color: #718096;
            font-size: 14px;
            margin-top: 9px;
        }

        .service-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .service-card {
            border: 1px solid #e3e9f1;
            border-radius: 12px;

            padding: 23px;

            background: white;

            transition: 0.2s;
        }

        .service-card:hover {
            transform: translateY(-3px);

            box-shadow: 0 10px 25px rgba(20, 60, 120, 0.08);
        }

        .service-icon {
            width: 45px;
            height: 45px;

            background: #e8f1ff;
            color: #0b4ea2;

            border-radius: 10px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-weight: 800;
            font-size: 17px;

            margin-bottom: 16px;
        }

        .service-card h3 {
            color: #263f65;
            font-size: 16px;
        }

        .service-card p {
            color: #718096;
            font-size: 13px;
            line-height: 1.6;

            margin-top: 8px;

            min-height: 42px;
        }

        .service-info {
            display: flex;
            justify-content: space-between;

            margin-top: 18px;

            padding-top: 15px;

            border-top: 1px solid #edf1f6;

            font-size: 11px;
        }

        .service-info span:first-child {
            color: #718096;
        }

        .service-info span:last-child {
            color: #0b4ea2;
            font-weight: 700;
        }

        .no-services {
            text-align: center;
            color: #8190a5;
            padding: 30px;
            grid-column: 1 / -1;
        }


        .how-it-works {
            background: #f7faff;
            padding: 75px 7%;
        }

        .steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }

        .step {
            text-align: center;
            padding: 20px;
        }

        .step-number {
            width: 52px;
            height: 52px;

            margin: 0 auto 16px;

            border-radius: 50%;

            background: #0b4ea2;
            color: white;

            display: flex;
            align-items: center;
            justify-content: center;

            font-weight: 800;
            font-size: 17px;
        }

        .step h3 {
            color: #263f65;
            font-size: 16px;
        }

        .step p {
            color: #718096;
            font-size: 13px;
            line-height: 1.6;

            margin-top: 7px;
        }

        .cta {
            padding: 65px 7%;
            background: #0b3d91;
            text-align: center;
        }

        .cta h2 {
            color: white;
            font-size: 30px;
        }

        .cta p {
            color: #c8d8ed;
            font-size: 14px;
            margin-top: 10px;
        }

        .cta a {
            display: inline-block;

            margin-top: 25px;

            background: white;
            color: #0b3d91;

            padding: 13px 24px;

            border-radius: 8px;

            text-decoration: none;

            font-size: 14px;
            font-weight: 700;
        }

        footer {
            background: #082d5c;
            color: #b7c8dc;

            padding: 25px 7%;

            display: flex;
            justify-content: space-between;
            align-items: center;

            font-size: 12px;
        }

        footer strong {
            color: white;
        }


        @media (max-width: 900px) {

            .hero {
                flex-direction: column;
                text-align: center;
            }

            .hero-content,
            .hero-visual {
                width: 100%;
            }

            .hero-content {
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .hero-visual {
                margin-top: 45px;
            }

            .service-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .steps {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 700px) {

            .navbar {
                padding: 0 20px;
            }

            .nav-links a:not(.login-btn) {
                display: none;
            }

            .hero {
                padding: 55px 20px;
            }

            .hero h1 {
                font-size: 36px;
            }

            .hero-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 300px;
            }

            .services,
            .how-it-works {
                padding: 55px 20px;
            }

            .service-grid {
                grid-template-columns: 1fr;
            }

            .portal-card {
                width: 100%;
                max-width: 380px;
            }

            footer {
                flex-direction: column;
                gap: 8px;
                text-align: center;
            }

        }

    </style>

</head>

<body>

<nav class="navbar">

    <a href="index.php" class="brand">

        <div class="brand-logo">
            BS
        </div>

        <div class="brand-info">

            <strong>Barangay Service Hub</strong>

            <span>Digital Resident Services</span>

        </div>

    </a>


    <div class="nav-links">

        <a href="#home">
            Home
        </a>

        <a href="#services">
            Services
        </a>

        <a href="#how-it-works">
            How It Works
        </a>

        <?php if (isset($_SESSION['user_id'])): ?>

            <a href="dashboard.php" class="login-btn">
                Dashboard
            </a>

        <?php else: ?>

            <a href="login.php" class="login-btn">
                Login
            </a>

        <?php endif; ?>

    </div>

</nav>


<section class="hero" id="home">

    <div class="hero-content">

        <div class="hero-label">
            BARANGAY DIGITAL SERVICES
        </div>

        <h1>
            Your Barangay Services,
            <span>Made Simple.</span>
        </h1>

        <p>
            Barangay Service Hub makes it easier for residents
            to request, manage, and track barangay documents
            and community services online.
        </p>

        <div class="hero-buttons">

            <?php if (isset($_SESSION['user_id'])): ?>

                <a href="dashboard.php" class="primary-btn">
                    Go to Dashboard
                </a>

                <a href="submit-request.php" class="secondary-btn">
                    Submit a Request
                </a>

            <?php else: ?>

                <a href="register.php" class="primary-btn">
                    Create an Account
                </a>

                <a href="login.php" class="secondary-btn">
                    Login to Portal
                </a>

            <?php endif; ?>

        </div>

    </div>


    <div class="hero-visual">

        <div class="portal-card">

            <div class="portal-header">

                <div class="portal-icon">
                    BS
                </div>

                <div>

                    <strong>
                        Resident Service Portal
                    </strong>

                    <span>
                        Track your barangay requests
                    </span>

                </div>

            </div>


            <div class="portal-row">

                <div class="portal-row-left">

                    <div class="mini-icon">
                        BC
                    </div>

                    <strong>
                        Barangay Clearance
                    </strong>

                </div>

                <span class="status processing">
                    Processing
                </span>

            </div>


            <div class="portal-row">

                <div class="portal-row-left">

                    <div class="mini-icon">
                        CR
                    </div>

                    <strong>
                        Certificate of Residency
                    </strong>

                </div>

                <span class="status ready">
                    Ready
                </span>

            </div>


            <div class="portal-row">

                <div class="portal-row-left">

                    <div class="mini-icon">
                        CI
                    </div>

                    <strong>
                        Certificate of Indigency
                    </strong>

                </div>

                <span class="status pending">
                    Pending
                </span>

            </div>

        </div>

    </div>

</section>


<section class="services" id="services">

    <div class="section-heading">

        <h2>
            Available Barangay Services
        </h2>

        <p>
            Explore the services available through our digital portal.
        </p>

    </div>


    <div class="service-grid">

        <?php if (count($services) > 0): ?>

            <?php foreach ($services as $service): ?>

                <div class="service-card">

                    <div class="service-icon">
                        BS
                    </div>

                    <h3>
                        <?= htmlspecialchars($service['service_name']) ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars(
                            $service['description'] ?: 'Barangay service available for residents.'
                        ) ?>
                    </p>

                    <div class="service-info">

                        <span>
                            Processing:
                            <?= (int) $service['processing_days'] ?> day(s)
                        </span>

                        <span>

                            <?php if ((float) $service['fee'] > 0): ?>

                                ₱<?= number_format((float) $service['fee'], 2) ?>

                            <?php else: ?>

                                Free

                            <?php endif; ?>

                        </span>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="no-services">
                No services have been added yet.
            </div>

        <?php endif; ?>

    </div>

</section>


<section class="how-it-works" id="how-it-works">

    <div class="section-heading">

        <h2>
            How It Works
        </h2>

        <p>
            Requesting barangay services is simple.
        </p>

    </div>


    <div class="steps">

        <div class="step">

            <div class="step-number">
                1
            </div>

            <h3>
                Create an Account
            </h3>

            <p>
                Register your resident account using
                your basic information.
            </p>

        </div>


        <div class="step">

            <div class="step-number">
                2
            </div>

            <h3>
                Submit a Request
            </h3>

            <p>
                Choose a barangay service and submit
                your request online.
            </p>

        </div>


        <div class="step">

            <div class="step-number">
                3
            </div>

            <h3>
                Track Your Request
            </h3>

            <p>
                Monitor the status of your request
                through your resident dashboard.
            </p>

        </div>

    </div>

</section>


<section class="cta">

    <h2>
        Access Barangay Services Online
    </h2>

    <p>
        Save time and conveniently manage your barangay requests.
    </p>

    <?php if (isset($_SESSION['user_id'])): ?>

        <a href="dashboard.php">
            Open Resident Dashboard
        </a>

    <?php else: ?>

        <a href="register.php">
            Get Started
        </a>

    <?php endif; ?>

</section>

<footer>

    <div>
        © <?= date('Y') ?>
        <strong>Barangay Service Hub</strong>
    </div>

    <div>
        Digital Barangay Services
    </div>

</footer>


</body>

</html>