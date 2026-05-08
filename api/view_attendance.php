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
$dateTaken = isset($_GET['dateTaken']) ? trim($_GET['dateTaken']) : '';

if ($dateTaken == '') {
    echo json_encode([
        "success" => false,
        "message" => "dateTaken is required."
    ]);
    exit();
}

if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $dateTaken)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid date format. Use YYYY-MM-DD."
    ]);
    exit();
}

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
    st.sessionName,
    t.termName,
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
LEFT JOIN tblsessionterm st 
    ON st.Id = a.sessionTermId
LEFT JOIN tblterm t 
    ON t.Id = st.termId
WHERE a.classId = '$classId'
AND a.classArmId = '$classArmId'
AND a.dateTimeTaken = '$dateTaken'
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
        "message" => "Failed to fetch attendance records."
    ]);
}

$conn->close();
?>