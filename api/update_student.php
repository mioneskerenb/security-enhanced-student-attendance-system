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
$firstName = $_POST['firstName'] ?? '';
$lastName = $_POST['lastName'] ?? '';
$otherName = $_POST['otherName'] ?? '';
$admissionNumber = $_POST['admissionNumber'] ?? '';
$classId = $_POST['classId'] ?? '';
$classArmId = $_POST['classArmId'] ?? '';

if (
    empty($id) ||
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

$checkStmt = $conn->prepare("SELECT Id FROM tblstudents WHERE admissionNumber = ? AND Id <> ?");
$checkStmt->bind_param("si", $admissionNumber, $id);
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

$stmt = $conn->prepare("UPDATE tblstudents SET firstName=?, lastName=?, otherName=?, admissionNumber=?, classId=?, classArmId=? WHERE Id=?");
$stmt->bind_param("ssssiii", $firstName, $lastName, $otherName, $admissionNumber, $classId, $classArmId, $id);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Student updated successfully"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to update student"
    ]);
}

$stmt->close();
$conn->close();
?>