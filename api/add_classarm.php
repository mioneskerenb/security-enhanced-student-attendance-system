<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include "../Includes/dbcon.php";
include "auth.php";

$user = validateToken($conn);
requireRole($user, ["Administrator"]);

$classId = $_POST['classId'] ?? '';
$classArmName = $_POST['classArmName'] ?? '';

if (empty($classId) || empty($classArmName)) {
    echo json_encode([
        "success" => false,
        "message" => "Class and class arm name are required"
    ]);
    exit();
}

$checkStmt = $conn->prepare("SELECT Id FROM tblclassarms WHERE classId = ? AND classArmName = ?");
$checkStmt->bind_param("is", $classId, $classArmName);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Class arm already exists for this class"
    ]);
    $checkStmt->close();
    $conn->close();
    exit();
}
$checkStmt->close();

$status = "UnAssigned";

$stmt = $conn->prepare("INSERT INTO tblclassarms (classId, classArmName, isAssigned) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $classId, $classArmName, $status);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Class arm added successfully"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to add class arm"
    ]);
}

$stmt->close();
$conn->close();
?>