<?php
include '../Includes/dbcon.php';

$id = $_POST['id'];

$query = "DELETE FROM tblclassarms WHERE Id='$id'";

if(mysqli_query($conn,$query)){
    echo json_encode(["success"=>true]);
}else{
    echo json_encode(["success"=>false]);
}
?>