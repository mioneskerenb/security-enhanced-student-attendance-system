<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include "../Includes/dbcon.php";
include "auth.php";

$user = validateToken($conn);
requireRole($user, ["ClassTeacher"]);

$teacherId = $user['user_id'];
$dateTaken = isset($_POST['dateTaken']) ? trim($_POST['dateTaken']) : '';
$attendance = isset($_POST['attendance']) ? trim($_POST['attendance']) : '';

if ($dateTaken == '' || $attendance == '') {
    echo json_encode([
        "success" => false,
        "message" => "dateTaken and attendance are required."
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

/*
Expected attendance JSON string:
[
  {"admissionNo":"STD001","status":1},
  {"admissionNo":"STD002","status":0}
]
*/

$attendanceList = json_decode($attendance, true);

if (!is_array($attendanceList)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid attendance format. Send JSON array string."
    ]);
    exit();
}

/*
Get class teacher assigned class and class arm
*/
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

/*
Get active session term
*/
$sessionQuery = mysqli_query($conn, "
    SELECT Id 
    FROM tblsessionterm 
    WHERE isActive = '1'
    LIMIT 1
");

if (!$sessionQuery || mysqli_num_rows($sessionQuery) == 0) {
    echo json_encode([
        "success" => false,
        "message" => "No active session term found. Please activate one first."
    ]);
    exit();
}

$session = mysqli_fetch_assoc($sessionQuery);
$sessionTermId = $session['Id'];

$successCount = 0;

foreach ($attendanceList as $item) {
    $admissionNo = isset($item['admissionNo']) ? mysqli_real_escape_string($conn, $item['admissionNo']) : '';
    $status = isset($item['status']) ? intval($item['status']) : 0;

    if ($admissionNo == '') {
        continue;
    }

    $status = ($status == 1) ? 1 : 0;

    /*
    Make sure student belongs to this teacher's class
    */
    $studentCheck = mysqli_query($conn, "
        SELECT Id 
        FROM tblstudents 
        WHERE admissionNumber = '$admissionNo'
        AND classId = '$classId'
        AND classArmId = '$classArmId'
        LIMIT 1
    ");

    if (!$studentCheck || mysqli_num_rows($studentCheck) == 0) {
        continue;
    }

    /*
    Check if attendance already exists
    */
    $existing = mysqli_query($conn, "
        SELECT Id 
        FROM tblattendance
        WHERE admissionNo = '$admissionNo'
        AND classId = '$classId'
        AND classArmId = '$classArmId'
        AND sessionTermId = '$sessionTermId'
        AND dateTimeTaken = '$dateTaken'
        LIMIT 1
    ");

    if ($existing && mysqli_num_rows($existing) > 0) {
        $row = mysqli_fetch_assoc($existing);
        $attendanceId = $row['Id'];

        $query = mysqli_query($conn, "
            UPDATE tblattendance
            SET status = '$status'
            WHERE Id = '$attendanceId'
        ");
    } else {
        $query = mysqli_query($conn, "
            INSERT INTO tblattendance
            (admissionNo, classId, classArmId, sessionTermId, status, dateTimeTaken)
            VALUES
            ('$admissionNo', '$classId', '$classArmId', '$sessionTermId', '$status', '$dateTaken')
        ");
    }

    if ($query) {
        $successCount++;
    }
}

echo json_encode([
    "success" => true,
    "message" => "Attendance saved successfully.",
    "savedCount" => $successCount
]);

$conn->close();
?>