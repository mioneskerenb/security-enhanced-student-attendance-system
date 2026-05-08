<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include "../Includes/dbcon.php";
include "auth.php";

$user = validateToken($conn);

$sql = "SELECT t.Id, t.firstName, t.lastName, t.emailAddress,
        c.className, ca.classArmName
        FROM tblclassteacher t
        LEFT JOIN tblclass c ON t.classId = c.Id
        LEFT JOIN tblclassarms ca ON t.classArmId = ca.Id";

$result = $conn->query($sql);

$data = [];
while($row = $result->fetch_assoc()){
    $data[] = $row;
}

echo json_encode(["success"=>true,"data"=>$data]);
?>