<?php

session_start();

require_once __DIR__ . "/config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: my-requests.php");
    exit;
}

$requestId = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($requestId <= 0) {
    header("Location: my-requests.php?error=invalid");
    exit;
}

$sql = "
    DELETE FROM requests
    WHERE id = ?
      AND user_id = ?
      AND status = 'Pending'
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $requestId, $userId);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    header("Location: my-requests.php?deleted=1");
    exit;
}

header("Location: my-requests.php?error=cannot_delete");
exit;