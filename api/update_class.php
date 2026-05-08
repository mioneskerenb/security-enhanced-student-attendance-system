<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include "../Includes/dbcon.php";
include "auth.php";

$user = validateToken($conn);
requireRole($user, ["Administrator"]);

$id = $_POST['id'] ?? '';
$className = $_POST['className'] ?? '';

if (empty($id) || empty($className)) {
    echo json_encode([
        "success" => false,
        "message" => "Id and class name are required"
    ]);
    exit();
}

$checkStmt = $conn->prepare("SELECT Id FROM tblclass WHERE className = ? AND Id <> ?");
$checkStmt->bind_param("si", $className, $id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Class name already exists"
    ]);
    $checkStmt->close();
    $conn->close();
    exit();
}
$checkStmt->close();

$stmt = $conn->prepare("UPDATE tblclass SET className = ? WHERE Id = ?");
$stmt->bind_param("si", $className, $id);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Class updated successfully"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to update class"
    ]);
}

$stmt->close();
$conn->close();
?>