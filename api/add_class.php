<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include "../Includes/dbcon.php";
include "auth.php";

$user = validateToken($conn);
requireRole($user, ["Administrator"]);

$className = $_POST['className'] ?? '';

if (empty($className)) {
    echo json_encode([
        "success" => false,
        "message" => "Class name is required"
    ]);
    exit();
}

$checkStmt = $conn->prepare("SELECT Id FROM tblclass WHERE className = ?");
$checkStmt->bind_param("s", $className);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Class already exists"
    ]);
    $checkStmt->close();
    $conn->close();
    exit();
}
$checkStmt->close();

$stmt = $conn->prepare("INSERT INTO tblclass (className) VALUES (?)");
$stmt->bind_param("s", $className);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Class added successfully"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to add class"
    ]);
}

$stmt->close();
$conn->close();
?>