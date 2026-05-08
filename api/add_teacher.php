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
$email = $_POST['emailAddress'] ?? '';
$password = $_POST['password'] ?? '';
$classId = $_POST['classId'] ?? '';
$classArmId = $_POST['classArmId'] ?? '';

if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
    echo json_encode(["success"=>false,"message"=>"All fields required"]);
    exit();
}

$password = strtoupper(md5($password));

$stmt = $conn->prepare("INSERT INTO tblclassteacher (firstName,lastName,emailAddress,password,classId,classArmId) VALUES (?,?,?,?,?,?)");
$stmt->bind_param("ssssii", $firstName,$lastName,$email,$password,$classId,$classArmId);

echo json_encode([
    "success"=>$stmt->execute(),
    "message"=>"Teacher added"
]);
?>