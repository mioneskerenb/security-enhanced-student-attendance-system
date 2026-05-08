<?php
include "../Includes/dbcon.php";
include "auth.php";

$user = validateToken($conn);

$id = $_POST['id'];

$stmt = $conn->prepare("DELETE FROM tblclassteacher WHERE Id=?");
$stmt->bind_param("i",$id);

echo json_encode(["success"=>$stmt->execute()]);
?>