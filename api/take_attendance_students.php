<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include "../Includes/dbcon.php";
include "auth.php";

$user = validateToken($conn);

if ($user['user_type'] !== "ClassTeacher") {
    echo json_encode([
        "success" => false,
        "message" => "Access denied"
    ]);
    exit();
}

$teacherStmt = $conn->prepare("SELECT classId, classArmId FROM tblclassteacher WHERE Id = ?");
$teacherStmt->bind_param("i", $user['user_id']);
$teacherStmt->execute();
$teacherResult = $teacherStmt->get_result();

if (!$teacherRow = $teacherResult->fetch_assoc()) {
    echo json_encode([
        "success" => false,
        "message" => "Teacher assignment not found"
    ]);
    exit();
}

$classId = $teacherRow['classId'];
$classArmId = $teacherRow['classArmId'];

$stmt = $conn->prepare("
    SELECT 
        s.Id,
        s.firstName,
        s.lastName,
        s.otherName,
        s.admissionNumber,
        c.className,
        ca.classArmName
    FROM tblstudents s
    LEFT JOIN tblclass c ON s.classId = c.Id
    LEFT JOIN tblclassarms ca ON s.classArmId = ca.Id
    WHERE s.classId = ? AND s.classArmId = ?
    ORDER BY s.lastName ASC, s.firstName ASC
");
$stmt->bind_param("ii", $classId, $classArmId);
$stmt->execute();
$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "success" => true,
    "classId" => $classId,
    "classArmId" => $classArmId,
    "data" => $data
]);

$conn->close();
?>