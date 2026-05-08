<?php 
/*
|--------------------------------------------------------------------------
| SECURED TAKE ATTENDANCE PAGE
|--------------------------------------------------------------------------
| Security improvements:
| 1. Only ClassTeacher can access this page.
| 2. Session values are validated and converted to integers.
| 3. MySQL app variables are set so attendance can only be inserted/updated
|    through the system, not manually in the database.
| 4. Prepared statements are used to prevent SQL Injection.
| 5. Output is escaped using htmlspecialchars() to reduce XSS risk.
|--------------------------------------------------------------------------
*/

// TEMPORARY: show errors while testing.
// After everything works, you may comment these two lines.
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../Includes/dbcon.php';
include '../Includes/session.php';

requireRole("ClassTeacher");

$statusMsg = "";

function escapeOutput($data)
{
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| SESSION VALIDATION
|--------------------------------------------------------------------------
*/

$userId     = isset($_SESSION['userId']) ? (int) $_SESSION['userId'] : 0;
$classId    = isset($_SESSION['classId']) ? (int) $_SESSION['classId'] : 0;
$classArmId = isset($_SESSION['classArmId']) ? (int) $_SESSION['classArmId'] : 0;

if ($userId <= 0 || $classId <= 0 || $classArmId <= 0) {
    session_unset();
    session_destroy();

    header("Location: ../index.php?message=invalid_session");
    exit();
}

/*
|--------------------------------------------------------------------------
| DATABASE ATTENDANCE PROTECTION
|--------------------------------------------------------------------------
| These MySQL session variables tell the database that the attendance
| request is coming from the authenticated ClassTeacher module.
|
| The database trigger will block manual INSERT or UPDATE attempts
| if these variables are not present.
|--------------------------------------------------------------------------
*/

$conn->query("SET @app_user_id = " . (int)$userId);
$conn->query("SET @app_role = 'ClassTeacher'");

$dateTaken = date("Y-m-d");

/*
|--------------------------------------------------------------------------
| GET CLASS TEACHER CLASS INFORMATION
|--------------------------------------------------------------------------
*/

$className = "Unknown Class";
$classArmName = "Unknown Arm";

$query = "SELECT tblclass.className, tblclassarms.classArmName 
          FROM tblclassteacher
          INNER JOIN tblclass ON tblclass.Id = tblclassteacher.classId
          INNER JOIN tblclassarms ON tblclassarms.Id = tblclassteacher.classArmId
          WHERE tblclassteacher.Id = ?
          LIMIT 1";

$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $rrw = $result->fetch_assoc();
        $className = $rrw['className'];
        $classArmName = $rrw['classArmName'];
    }

    $stmt->close();
} else {
    error_log("Class teacher info query failed: " . $conn->error);
}

/*
|--------------------------------------------------------------------------
| GET ACTIVE SESSION TERM
|--------------------------------------------------------------------------
*/

$sessionTermId = 0;

$query = "SELECT Id FROM tblsessionterm WHERE isActive = '1' LIMIT 1";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $rwws = mysqli_fetch_assoc($result);
    $sessionTermId = (int) $rwws['Id'];
}

if ($sessionTermId <= 0) {
    $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                    <i class='fas fa-exclamation-circle mr-2'></i>
                    No active session term found.
                  </div>";
}

/*
|--------------------------------------------------------------------------
| CREATE TODAY'S ATTENDANCE RECORDS IF NOT YET CREATED
|--------------------------------------------------------------------------
*/

if ($sessionTermId > 0) {

    $checkQuery = "SELECT COUNT(*) AS total 
                   FROM tblattendance 
                   WHERE classId = ? 
                   AND classArmId = ? 
                   AND dateTimeTaken = ?";

    $stmt = $conn->prepare($checkQuery);

    if ($stmt) {
        $stmt->bind_param("iis", $classId, $classArmId, $dateTaken);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = (int) $row['total'];

        $stmt->close();

        if ($count == 0) {

            $studentQuery = "SELECT admissionNumber 
                             FROM tblstudents 
                             WHERE classId = ? 
                             AND classArmId = ?";

            $studentStmt = $conn->prepare($studentQuery);

            if ($studentStmt) {
                $studentStmt->bind_param("ii", $classId, $classArmId);
                $studentStmt->execute();

                $studentsResult = $studentStmt->get_result();

                $insertQuery = "INSERT INTO tblattendance
                                (admissionNo, classId, classArmId, sessionTermId, status, dateTimeTaken)
                                VALUES (?, ?, ?, ?, '0', ?)";

                $insertStmt = $conn->prepare($insertQuery);

                if ($insertStmt) {
                    while ($student = $studentsResult->fetch_assoc()) {
                        $admissionNumber = $student['admissionNumber'];

                        $insertStmt->bind_param(
                            "siiis",
                            $admissionNumber,
                            $classId,
                            $classArmId,
                            $sessionTermId,
                            $dateTaken
                        );

                        $insertStmt->execute();
                    }

                    $insertStmt->close();
                } else {
                    error_log("Attendance insert prepare failed: " . $conn->error);
                    $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                                    <i class='fas fa-exclamation-circle mr-2'></i>
                                    Unable to prepare attendance records.
                                  </div>";
                }

                $studentStmt->close();
            } else {
                error_log("Student list prepare failed: " . $conn->error);
                $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                                <i class='fas fa-exclamation-circle mr-2'></i>
                                Unable to load students.
                              </div>";
            }
        }
    } else {
        error_log("Attendance check prepare failed: " . $conn->error);
        $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                        <i class='fas fa-exclamation-circle mr-2'></i>
                        Unable to check attendance records.
                      </div>";
    }
}

/*
|--------------------------------------------------------------------------
| SAVE ATTENDANCE
|--------------------------------------------------------------------------
*/

if (isset($_POST['save']) && $sessionTermId > 0) {

    $checkedStudents = isset($_POST['check']) && is_array($_POST['check']) ? $_POST['check'] : [];

    $checkTakenQuery = "SELECT COUNT(*) AS total 
                        FROM tblattendance  
                        WHERE classId = ? 
                        AND classArmId = ? 
                        AND dateTimeTaken = ? 
                        AND status = '1'";

    $stmt = $conn->prepare($checkTakenQuery);

    if ($stmt) {
        $stmt->bind_param("iis", $classId, $classArmId, $dateTaken);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $alreadyTaken = (int) $row['total'];

        $stmt->close();

        if ($alreadyTaken > 0) {
            $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                            <i class='fas fa-exclamation-circle mr-2'></i>
                            Attendance has already been taken for today.
                          </div>";
        } else {

            if (count($checkedStudents) == 0) {
                $statusMsg = "<div class='alert alert-warning shadow-sm' role='alert'>
                                <i class='fas fa-info-circle mr-2'></i>
                                Please select at least one student.
                              </div>";
            } else {

                $updateQuery = "UPDATE tblattendance 
                                SET status = '1' 
                                WHERE admissionNo = ? 
                                AND classId = ? 
                                AND classArmId = ? 
                                AND dateTimeTaken = ?";

                $updateStmt = $conn->prepare($updateQuery);

                if ($updateStmt) {

                    foreach ($checkedStudents as $admissionNo) {
                        $admissionNo = trim($admissionNo);

                        if ($admissionNo !== "") {
                            $updateStmt->bind_param(
                                "siis",
                                $admissionNo,
                                $classId,
                                $classArmId,
                                $dateTaken
                            );

                            $updateStmt->execute();
                        }
                    }

                    $updateStmt->close();

                    $statusMsg = "<div class='alert alert-success shadow-sm' role='alert'>
                                    <i class='fas fa-check-circle mr-2'></i>
                                    Attendance taken successfully.
                                  </div>";
                } else {
                    error_log("Attendance update prepare failed: " . $conn->error);
                    $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                                    <i class='fas fa-exclamation-circle mr-2'></i>
                                    An error occurred while saving attendance.
                                  </div>";
                }
            }
        }
    } else {
        error_log("Attendance already taken check failed: " . $conn->error);
        $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                        <i class='fas fa-exclamation-circle mr-2'></i>
                        An error occurred while checking attendance.
                      </div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">

  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  
  <meta name="description" content="">
  <meta name="author" content="">

  <link href="img/logo/attnlg.jpg" rel="icon">

  <title>Take Attendance</title>

  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="css/ruang-admin.min.css" rel="stylesheet">
</head>

<body id="page-top">

  <div id="wrapper">

    <!-- Sidebar -->
    <?php include "Includes/sidebar.php";?>
    <!-- Sidebar -->

    <div id="content-wrapper" class="d-flex flex-column bg-light">

      <div id="content">

        <!-- TopBar -->
        <?php include "Includes/topbar.php";?>
        <!-- Topbar -->

        <!-- Container Fluid-->
        <div class="container-fluid" id="container-wrapper">

          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
              <h1 class="h3 mb-0 text-gray-800 font-weight-bold">
                Take Attendance
              </h1>

              <p class="mb-0 text-muted">
                Today's Date: <?php echo escapeOutput(date("m-d-Y")); ?>
              </p>
            </div>

            <ol class="breadcrumb bg-white shadow-sm border">
              <li class="breadcrumb-item">
                <a href="./" class="text-success">Home</a>
              </li>
              <li class="breadcrumb-item active" aria-current="page">
                Take Attendance
              </li>
            </ol>
          </div>

          <!-- Header Card -->
          <div class="card bg-success text-white shadow mb-4 border-0">
            <div class="card-body py-4">
              <div class="row align-items-center">
                <div class="col-lg-8">
                  <span class="badge badge-light text-success mb-3 px-3 py-2">
                    <i class="fas fa-calendar-check mr-1"></i>
                    Nursery Attendance
                  </span>

                  <h3 class="font-weight-bold mb-2">
                    Take Student Attendance
                  </h3>

                  <p class="mb-0">
                    Mark the students who are present today. Students that are not checked will remain marked as absent.
                  </p>
                </div>

                <div class="col-lg-4 text-lg-right mt-4 mt-lg-0">
                  <div class="bg-white text-success rounded shadow-sm p-3 d-inline-block">
                    <i class="fas fa-school fa-2x mb-2"></i>
                    <div class="font-weight-bold">
                      <?php echo escapeOutput($className) . ' - ' . escapeOutput($classArmName); ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <form method="post">

            <div class="row">
              <div class="col-lg-12">

                <div class="card shadow mb-4 border-0">

                  <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between border-bottom">
                    <h6 class="m-0 font-weight-bold text-success">
                      <i class="fas fa-users mr-2"></i>
                      All Students in 
                      <?php echo escapeOutput($className) . ' - ' . escapeOutput($classArmName); ?> 
                      Class
                    </h6>

                    <span class="badge badge-warning px-3 py-2">
                      <i class="fas fa-info-circle mr-1"></i>
                      Check present students
                    </span>
                  </div>

                  <div class="card-body">

                    <?php echo $statusMsg; ?>

                    <div class="alert alert-success border-left-success shadow-sm" role="alert">
                      <i class="fas fa-info-circle mr-2"></i>
                      Click the checkbox beside each student who is present today, then click the Take Attendance button.
                    </div>

                    <div class="table-responsive">
                      <table class="table table-bordered table-hover table-striped align-items-center" id="dataTableHover">
                        <thead class="thead-light">
                          <tr>
                            <th>#</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Other Name</th>
                            <th>Admission No</th>
                            <th>Class</th>
                            <th>Class Arm</th>
                            <th class="text-center">Check</th>
                          </tr>
                        </thead>

                        <tbody>
                          <?php
                          $query = "SELECT 
                                      tblstudents.Id,
                                      tblstudents.admissionNumber,
                                      tblstudents.firstName,
                                      tblstudents.lastName,
                                      tblstudents.otherName,
                                      tblclass.className,
                                      tblclassarms.classArmName
                                    FROM tblstudents
                                    INNER JOIN tblclass ON tblclass.Id = tblstudents.classId
                                    INNER JOIN tblclassarms ON tblclassarms.Id = tblstudents.classArmId
                                    WHERE tblstudents.classId = ? 
                                    AND tblstudents.classArmId = ?
                                    ORDER BY tblstudents.lastName ASC";

                          $stmt = $conn->prepare($query);

                          if ($stmt) {
                              $stmt->bind_param("ii", $classId, $classArmId);
                              $stmt->execute();

                              $rs = $stmt->get_result();
                              $sn = 0;

                              if ($rs && $rs->num_rows > 0) {
                                  while ($rows = $rs->fetch_assoc()) {
                                      $sn++;
                                      $checkboxId = "studentCheck" . $sn;

                                      echo "
                                      <tr>
                                        <td>
                                          <span class='badge badge-success'>" . escapeOutput($sn) . "</span>
                                        </td>

                                        <td>" . escapeOutput($rows['firstName']) . "</td>

                                        <td>" . escapeOutput($rows['lastName']) . "</td>

                                        <td>" . escapeOutput($rows['otherName']) . "</td>

                                        <td>
                                          <span class='badge badge-primary px-3 py-2'>" . escapeOutput($rows['admissionNumber']) . "</span>
                                        </td>

                                        <td>
                                          <span class='badge badge-info px-3 py-2'>" . escapeOutput($rows['className']) . "</span>
                                        </td>

                                        <td>
                                          <span class='badge badge-success px-3 py-2'>" . escapeOutput($rows['classArmName']) . "</span>
                                        </td>

                                        <td class='text-center'>
                                          <div class='custom-control custom-checkbox d-inline-block'>
                                            <input 
                                              name='check[]' 
                                              type='checkbox' 
                                              value='" . escapeOutput($rows['admissionNumber']) . "' 
                                              class='custom-control-input' 
                                              id='" . escapeOutput($checkboxId) . "'>

                                            <label class='custom-control-label' for='" . escapeOutput($checkboxId) . "'>
                                              Present
                                            </label>
                                          </div>
                                        </td>
                                      </tr>";
                                  }
                              } else {
                                  echo "
                                  <tr>
                                    <td colspan='8'>
                                      <div class='alert alert-danger mb-0' role='alert'>
                                        <i class='fas fa-exclamation-circle mr-2'></i>
                                        No record found.
                                      </div>
                                    </td>
                                  </tr>";
                              }

                              $stmt->close();
                          } else {
                              error_log("Student display prepare failed: " . $conn->error);
                              echo "
                              <tr>
                                <td colspan='8'>
                                  <div class='alert alert-danger mb-0' role='alert'>
                                    <i class='fas fa-exclamation-circle mr-2'></i>
                                    Unable to load student records.
                                  </div>
                                </td>
                              </tr>";
                          }
                          ?>
                        </tbody>
                      </table>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                      <button type="submit" name="save" class="btn btn-success shadow-sm px-4">
                        <i class="fas fa-check-circle mr-1"></i>
                        Take Attendance
                      </button>
                    </div>

                  </div>

                </div>

              </div>
            </div>

          </form>

        </div>
        <!-- Container Fluid-->

      </div>

      <!-- Footer -->
      <?php include "Includes/footer.php";?>
      <!-- Footer -->

    </div>
  </div>

  <!-- Scroll to top -->
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>

  <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
  <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

  <script>
    $(document).ready(function () {
      $('#dataTableHover').DataTable();
    });
  </script>

</body>
</html>