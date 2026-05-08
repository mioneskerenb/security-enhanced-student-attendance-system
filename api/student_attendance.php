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
$admissionNo = isset($_GET['admissionNo']) ? trim($_GET['admissionNo']) : '';

if ($admissionNo == '') {
    echo json_encode([
        "success" => false,
        "message" => "admissionNo is required."
    ]);
    exit();
}

$admissionNo = mysqli_real_escape_string($conn, $admissionNo);

$teacherQuery = mysqli_query($conn, "
    SELECT classId, classArmId 
    FROM tblclassteacher 
    WHERE Id = '$teacherId'
    LIMIT 1
");

if (!$teacherQuery || mysqli_num_rows($teacherQuery) == 0) {
    echo json_encode([
        "success" => false,
        "message" => "Class teacher assignment not found."
    ]);
    exit();
}

$teacher = mysqli_fetch_assoc($teacherQuery);
$classId = $teacher['classId'];
$classArmId = $teacher['classArmId'];

$query = "
SELECT 
    a.Id,
    s.firstName,
    s.lastName,
    s.otherName,
    s.admissionNumber,
    c.className,
    ca.classArmName,
    a.status,
    CASE 
        WHEN a.status = 1 THEN 'Present'
        ELSE 'Absent'
    END AS statusText,
    a.dateTimeTaken
FROM tblattendance a
INNER JOIN tblstudents s 
    ON s.admissionNumber = a.admissionNo
INNER JOIN tblclass c 
    ON c.Id = a.classId
INNER JOIN tblclassarms ca 
    ON ca.Id = a.classArmId
WHERE a.classId = '$classId'
AND a.classArmId = '$classArmId'
AND a.admissionNo = '$admissionNo'
ORDER BY a.dateTimeTaken DESC
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
        "message" => "Failed to fetch student attendance."
    ]);
}

$conn->close();
?>