<?php
include '../Includes/dbcon.php';

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$sessionName = isset($_POST['sessionName']) ? trim($_POST['sessionName']) : '';
$termId = isset($_POST['termId']) ? intval($_POST['termId']) : 0;

if ($id == 0 || $sessionName == '' || $termId == 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid input."
    ]);
    exit();
}

$query = "UPDATE tblsessionterm
          SET sessionName = '$sessionName', termId = '$termId'
          WHERE Id = '$id'";

if (mysqli_query($conn, $query)) {
    echo json_encode([
        "success" => true,
        "message" => "Updated successfully."
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to update record."
    ]);
}
?>