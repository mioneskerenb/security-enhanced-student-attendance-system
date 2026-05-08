<?php
/*
|--------------------------------------------------------------------------
| SECURED ATTENDANCE EXPORT FILE
|--------------------------------------------------------------------------
| Security improvements:
| 1. ClassTeacher role protection added using requireRole("ClassTeacher").
| 2. Session values are validated and converted to integers.
| 3. Prepared statement is used to prevent SQL Injection.
| 4. Output is escaped using htmlspecialchars() to reduce XSS risk.
| 5. Headers are placed before output to prevent download errors.
|--------------------------------------------------------------------------
*/

include '../Includes/dbcon.php';
include '../Includes/session.php';

requireRole("ClassTeacher");

function escapeOutput($data)
{
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

// Validate session values
$classId = isset($_SESSION['classId']) ? (int) $_SESSION['classId'] : 0;
$classArmId = isset($_SESSION['classArmId']) ? (int) $_SESSION['classArmId'] : 0;

if ($classId <= 0 || $classArmId <= 0) {
    session_unset();
    session_destroy();

    header("Location: ../index.php?message=invalid_session");
    exit();
}

$filename = "Attendance-list";
$dateTaken = date("Y-m-d");

// Set download headers BEFORE any HTML output
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=" . $filename . "-report.xls");
header("Pragma: no-cache");
header("Expires: 0");

/*
|--------------------------------------------------------------------------
| SECURE ATTENDANCE QUERY
|--------------------------------------------------------------------------
| Old code directly inserted:
| $_SESSION[classId]
| $_SESSION[classArmId]
| $dateTaken
|
| New code uses prepared statement.
|--------------------------------------------------------------------------
*/

$query = "SELECT 
            tblattendance.Id,
            tblattendance.status,
            tblattendance.dateTimeTaken,
            tblclass.className,
            tblclassarms.classArmName,
            tblsessionterm.sessionName,
            tblsessionterm.termId,
            tblterm.termName,
            tblstudents.firstName,
            tblstudents.lastName,
            tblstudents.otherName,
            tblstudents.admissionNumber
          FROM tblattendance
          INNER JOIN tblclass ON tblclass.Id = tblattendance.classId
          INNER JOIN tblclassarms ON tblclassarms.Id = tblattendance.classArmId
          INNER JOIN tblsessionterm ON tblsessionterm.Id = tblattendance.sessionTermId
          INNER JOIN tblterm ON tblterm.Id = tblsessionterm.termId
          INNER JOIN tblstudents ON tblstudents.admissionNumber = tblattendance.admissionNo
          WHERE tblattendance.dateTimeTaken = ?
          AND tblattendance.classId = ?
          AND tblattendance.classArmId = ?
          ORDER BY tblstudents.lastName ASC";

$stmt = $conn->prepare($query);

if (!$stmt) {
    error_log("Attendance export prepare failed: " . $conn->error);
    echo "Unable to export attendance report.";
    exit();
}

$stmt->bind_param("sii", $dateTaken, $classId, $classArmId);
$stmt->execute();

$result = $stmt->get_result();
$cnt = 1;
?>

<table border="1">
    <thead>
        <tr>
            <th>#</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Other Name</th>
            <th>Admission No</th>
            <th>Class</th>
            <th>Class Arm</th>
            <th>Session</th>
            <th>Term</th>
            <th>Status</th>
            <th>Date</th>
        </tr>
    </thead>

    <tbody>
        <?php
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                if ($row['status'] == '1') {
                    $status = "Present";
                } else {
                    $status = "Absent";
                }

                echo "
                <tr>
                    <td>" . escapeOutput($cnt) . "</td>
                    <td>" . escapeOutput($row['firstName']) . "</td>
                    <td>" . escapeOutput($row['lastName']) . "</td>
                    <td>" . escapeOutput($row['otherName']) . "</td>
                    <td>" . escapeOutput($row['admissionNumber']) . "</td>
                    <td>" . escapeOutput($row['className']) . "</td>
                    <td>" . escapeOutput($row['classArmName']) . "</td>
                    <td>" . escapeOutput($row['sessionName']) . "</td>
                    <td>" . escapeOutput($row['termName']) . "</td>
                    <td>" . escapeOutput($status) . "</td>
                    <td>" . escapeOutput($row['dateTimeTaken']) . "</td>
                </tr>
                ";

                $cnt++;
            }
        } else {
            echo "
            <tr>
                <td colspan='11'>No attendance record found for today.</td>
            </tr>
            ";
        }

        $stmt->close();
        ?>
    </tbody>
</table>