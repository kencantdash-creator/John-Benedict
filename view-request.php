<?php

session_start();

require_once __DIR__ . "/config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

$requestId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($requestId <= 0) {
    header("Location: my-requests.php");
    exit;
}


$sql = "
    SELECT
        r.id,
        r.reference_number,
        r.purpose,
        r.status,
        r.submitted_at,
        r.updated_at,
        s.service_name,
        s.description,
        s.processing_days,
        s.fee
    FROM requests r
    INNER JOIN services s
        ON r.service_id = s.id
    WHERE r.id = ?
    AND r.user_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    header("Location: my-requests.php");
    exit;
}

$stmt->bind_param("ii", $requestId, $userId);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: my-requests.php");
    exit;
}

$request = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>View Request | Barangay Service Hub</title>

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

.main {
    min-height: 100vh;
    padding: 40px;
}

.container {
    max-width: 900px;
    margin: auto;
}

.back {
    display: inline-block;
    margin-bottom: 20px;
    color: #1769aa;
    text-decoration: none;
    font-weight: bold;
}

.card {
    background: white;
    border: 1px solid #dce6f0;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,.05);
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    padding-bottom: 25px;
    border-bottom: 1px solid #e5edf5;
    margin-bottom: 25px;
}

h1 {
    color: #123d70;
    margin-bottom: 7px;
}

.reference {
    color: #6b7d93;
    font-size: 14px;
}

.status {
    padding: 8px 15px;
    border-radius: 20px;
    background: #eaf4ff;
    color: #1769aa;
    font-size: 13px;
    font-weight: bold;
}

.details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.item {
    background: #f8fbfe;
    border: 1px solid #e1ebf4;
    border-radius: 8px;
    padding: 18px;
}

.item.full {
    grid-column: 1 / -1;
}

.label {
    display: block;
    color: #71839b;
    font-size: 12px;
    margin-bottom: 7px;
}

.value {
    color: #17365f;
    font-weight: bold;
    line-height: 1.5;
}

.description {
    font-weight: normal;
}

@media (max-width: 700px) {

    .main {
        padding: 20px;
    }

    .header {
        flex-direction: column;
        align-items: flex-start;
    }

    .details {
        grid-template-columns: 1fr;
    }

    .item.full {
        grid-column: auto;
    }

}

</style>

</head>

<body>

<main class="main">

<div class="container">

    <a href="my-requests.php" class="back">
        ← Back to Request History
    </a>

    <div class="card">

        <div class="header">

            <div>

                <h1>
                    Request Details
                </h1>

                <div class="reference">
                    Reference:
                    <?= htmlspecialchars($request['reference_number']); ?>
                </div>

            </div>

            <span class="status">
                <?= htmlspecialchars($request['status']); ?>
            </span>

        </div>


        <div class="details">


            <div class="item">

                <span class="label">
                    Service
                </span>

                <div class="value">
                    <?= htmlspecialchars($request['service_name']); ?>
                </div>

            </div>


            <div class="item">

                <span class="label">
                    Date Submitted
                </span>

                <div class="value">

                    <?= date(
                        "M d, Y h:i A",
                        strtotime($request['submitted_at'])
                    ); ?>

                </div>

            </div>


            <div class="item">

                <span class="label">
                    Last Updated
                </span>

                <div class="value">

                    <?= date(
                        "M d, Y h:i A",
                        strtotime($request['updated_at'])
                    ); ?>

                </div>

            </div>


            <div class="item">

                <span class="label">
                    Service Fee
                </span>

                <div class="value">

                    ₱<?= number_format(
                        (float) $request['fee'],
                        2
                    ); ?>

                </div>

            </div>


            <div class="item full">

                <span class="label">
                    Purpose
                </span>

                <div class="value">
                    <?= htmlspecialchars($request['purpose']); ?>
                </div>

            </div>


            <div class="item full">

                <span class="label">
                    Service Description
                </span>

                <div class="value description">

                    <?= nl2br(
                        htmlspecialchars(
                            $request['description']
                        )
                    ); ?>

                </div>

            </div>


            <div class="item">

                <span class="label">
                    Processing Time
                </span>

                <div class="value">

                    <?= htmlspecialchars(
                        $request['processing_days']
                    ); ?>

                    day(s)

                </div>

            </div>


        </div>

    </div>

</div>

</main>

</body>

</html>