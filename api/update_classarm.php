<?php
include '../Includes/dbcon.php';

$id = $_POST['id'];
$classId = $_POST['classId'];
$classArmName = $_POST['classArmName'];

$query = "UPDATE tblclassarms SET classId='$classId', classArmName='$classArmName' WHERE Id='$id'";

if(mysqli_query($conn,$query)){
    echo json_encode(["success"=>true]);
}else{
    echo json_encode(["success"=>false]);
}
?>