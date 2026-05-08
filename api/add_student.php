<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include "../Includes/dbcon.php";
include "auth.php";

$user = validateToken($conn);
requireRole($user, ["Administrator"]);

$firstName = $_POST['firstName'] ?? '';
$lastName = $_POST['lastName'] ?? '';
$otherName = $_POST['otherName'] ?? '';
$admissionNumber = $_POST['admissionNumber'] ?? '';
$classId = $_POST['classId'] ?? '';
$classArmId = $_POST['classArmId'] ?? '';

if (
    empty($firstName) ||
    empty($lastName) ||
    empty($admissionNumber) ||
    empty($classId) ||
    empty($classArmId)
) {
    echo json_encode([
        "success" => false,
        "message" => "Please fill in all required fields"
    ]);
    exit();
}

$checkStmt = $conn->prepare("SELECT Id FROM tblstudents WHERE admissionNumber = ?");
$checkStmt->bind_param("s", $admissionNumber);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Admission number already exists"
    ]);
    $checkStmt->close();
    $conn->close();
    exit();
}
$checkStmt->close();

$stmt = $conn->prepare("INSERT INTO tblstudents (firstName, lastName, otherName, admissionNumber, classId, classArmId, dateCreated) VALUES (?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("ssssii", $firstName, $lastName, $otherName, $admissionNumber, $classId, $classArmId);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Student added successfully"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to add student"
    ]);
}

$stmt->close();
$conn->close();
?>