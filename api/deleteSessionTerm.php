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

$query = "DELETE FROM tblsessionterm WHERE Id = '$id'";

if (mysqli_query($conn, $query)) {
    echo json_encode([
        "success" => true,
        "message" => "Deleted successfully."
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to delete record."
    ]);
}
?>