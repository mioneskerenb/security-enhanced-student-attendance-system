<?php
include '../Includes/dbcon.php';

$sessionName = isset($_POST['sessionName']) ? trim($_POST['sessionName']) : '';
$termId = isset($_POST['termId']) ? intval($_POST['termId']) : 0;

if ($sessionName == '' || $termId == 0) {
    echo json_encode([
        "success" => false,
        "message" => "Session name and term are required."
    ]);
    exit();
}

$checkQuery = "SELECT * FROM tblsessionterm WHERE sessionName = '$sessionName' AND termId = '$termId'";
$checkResult = mysqli_query($conn, $checkQuery);

if (mysqli_num_rows($checkResult) > 0) {
    echo json_encode([
        "success" => false,
        "message" => "This session and term already exists."
    ]);
    exit();
}

$query = "INSERT INTO tblsessionterm(sessionName, termId, isActive, dateCreated)
          VALUES('$sessionName', '$termId', 0, CURDATE())";

if (mysqli_query($conn, $query)) {
    echo json_encode([
        "success" => true,
        "message" => "Saved successfully."
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to save record."
    ]);
}
?>