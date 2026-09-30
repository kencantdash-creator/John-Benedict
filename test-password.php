<?php

require_once __DIR__ . "/config/database.php";

$email = "john@gmail.com";
$password = "admin123";

$stmt = $conn->prepare("
    SELECT password
    FROM users
    WHERE email = ?
    LIMIT 1
");

$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 1) {

    $user = $result->fetch_assoc();

    if (password_verify($password, $user["password"])) {
        echo "PASSWORD IS CORRECT";
    } else {
        echo "PASSWORD DOES NOT MATCH";
    }

} else {

    echo "ACCOUNT NOT FOUND";

}
?>