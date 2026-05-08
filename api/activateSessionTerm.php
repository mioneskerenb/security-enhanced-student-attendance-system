<?php
include '../Includes/dbcon.php';

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id == 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid record ID."
    ]);
    exit();
}

mysqli_query($conn, "UPDATE tblsessionterm SET isActive = 0");
$query = mysqli_query($conn, "UPDATE tblsessionterm SET isActive = 1 WHERE Id = '$id'");

if ($query) {
    echo json_encode([
        "success" => true,
        "message" => "Activated successfully."
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to activate record."
    ]);
}
?>