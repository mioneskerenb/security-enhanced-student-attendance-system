<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "../Includes/dbcon.php";

function getCount($conn, $table) {
    $query = "SELECT COUNT(*) as total FROM $table";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

echo json_encode([
    "success" => true,
    "data" => [
        "students" => getCount($conn, "tblstudents"),
        "classes" => getCount($conn, "tblclass"),
        "classArms" => getCount($conn, "tblclassarms"),
        "totalAttendance" => getCount($conn, "tblattendance"),
        "classTeachers" => getCount($conn, "tblclassteacher"),
        "sessionTerms" => getCount($conn, "tblsessionterm"),
        "terms" => getCount($conn, "tblterm")
    ]
]);
?>