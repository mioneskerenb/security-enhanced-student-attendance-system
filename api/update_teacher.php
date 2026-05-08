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
$email = $_POST['emailAddress'] ?? '';
$classId = $_POST['classId'] ?? '';
$classArmId = $_POST['classArmId'] ?? '';

if (
    empty($id) ||
    empty($firstName) ||
    empty($lastName) ||
    empty($email) ||
    empty($classId) ||
    empty($classArmId)
) {
    echo json_encode([
        "success" => false,
        "message" => "Please fill in all required fields"
    ]);
    exit();
}

$stmt = $conn->prepare("UPDATE tblclassteacher SET firstName=?, lastName=?, emailAddress=?, classId=?, classArmId=? WHERE Id=?");
$stmt->bind_param("sssiii", $firstName, $lastName, $email, $classId, $classArmId, $id);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Teacher updated successfully"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to update teacher"
    ]);
}

$stmt->close();
$conn->close();
?>