<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include "../Includes/dbcon.php";
include "auth.php";

$user = validateToken($conn);
requireRole($user, ["ClassTeacher"]);

$teacherId = $user['user_id'];

$query = "
SELECT 
    s.Id,
    s.firstName,
    s.lastName,
    s.otherName,
    s.admissionNumber,
    c.className,
    ca.classArmName,
    s.dateCreated
FROM tblclassteacher ct
INNER JOIN tblstudents s 
    ON s.classId = ct.classId 
    AND s.classArmId = ct.classArmId
INNER JOIN tblclass c 
    ON c.Id = s.classId
INNER JOIN tblclassarms ca 
    ON ca.Id = s.classArmId
WHERE ct.Id = '$teacherId'
ORDER BY s.firstName ASC
";

$result = mysqli_query($conn, $query);

$data = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    echo json_encode([
        "success" => true,
        "data" => $data
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch assigned students."
    ]);
}

$conn->close();
?>