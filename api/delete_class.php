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

if (empty($id)) {
    echo json_encode([
        "success" => false,
        "message" => "Id is required"
    ]);
    exit();
}

$stmt = $conn->prepare("DELETE FROM tblclass WHERE Id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Class deleted successfully"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to delete class"
    ]);
}

$stmt->close();
$conn->close();
?>