<?php
include '../Includes/dbcon.php';

$query = "SELECT Id, termName FROM tblterm ORDER BY Id ASC";
$result = mysqli_query($conn, $query);

$data = array();

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => [
        ["Id" => 1, "termName" => "First"],
        ["Id" => 2, "termName" => "Second"],
        ["Id" => 3, "termName" => "Third"]
    ]
]);
?>