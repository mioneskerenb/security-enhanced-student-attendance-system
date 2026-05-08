<?php
header("Content-Type: application/json");
include "../Includes/dbcon.php";
include "auth.php";

$token = getBearerToken();

$response = [
    "received_token" => $token,
    "received_token_length" => $token ? strlen($token) : 0,
    "found_in_database" => false,
    "database_user_type" => null
];

if ($token) {
    $stmt = $conn->prepare("SELECT user_id, user_type FROM api_tokens WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $response["found_in_database"] = true;
        $response["database_user_type"] = $row["user_type"];
        $response["user_id"] = $row["user_id"];
    }
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>