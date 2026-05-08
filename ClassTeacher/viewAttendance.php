<?php 
/*
|--------------------------------------------------------------------------
| SECURED VIEW CLASS ATTENDANCE PAGE
|--------------------------------------------------------------------------
| Security improvements:
| 1. ClassTeacher role protection added using requireRole("ClassTeacher").
| 2. Session values are validated and converted to integers.
| 3. Date input is validated before being used.
| 4. SQL Injection is prevented using prepared statements.
| 5. Output is escaped using htmlspecialchars() to reduce XSS risk.
|--------------------------------------------------------------------------
*/

include '../Includes/dbcon.php';
include '../Includes/session.php';

requireRole("ClassTeacher");

$statusMsg = "";
$attendanceRows = [];

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

/*
|--------------------------------------------------------------------------
| VIEW ATTENDANCE BY DATE
|--------------------------------------------------------------------------
| Old code directly inserted dateTaken and session values into SQL.
| New code validates the date and uses prepared statement.
|--------------------------------------------------------------------------
*/

if (isset($_POST['view'])) {

    $dateTaken = trim($_POST['dateTaken'] ?? '');

    if ($dateTaken == "") {
        $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                        <i class='fas fa-exclamation-circle mr-2'></i>
                        Please select a date.
                      </div>";
    } 
    else if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $dateTaken)) {
        $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                        <i class='fas fa-exclamation-circle mr-2'></i>
                        Invalid date format.
                      </div>";
    } 
    else {

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
            error_log("View class attendance prepare failed: " . $conn->error);
            $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                            <i class='fas fa-exclamation-circle mr-2'></i>
                            Unable to load attendance records.
                          </div>";
        } 
        else {
            $stmt->bind_param("sii", $dateTaken, $classId, $classArmId);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $attendanceRows[] = $row;
                }
            } else {
                $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                                <i class='fas fa-exclamation-circle mr-2'></i>
                                No Record Found!
                              </div>";
            }

            $stmt->close();
        }
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

  <title>View Class Attendance</title>

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
                View Class Attendance
              </h1>

              <p class="mb-0 text-muted">
                Search attendance records by selected date.
              </p>
            </div>

            <ol class="breadcrumb bg-white shadow-sm border">
              <li class="breadcrumb-item">
                <a href="./" class="text-success">Home</a>
              </li>
              <li class="breadcrumb-item active" aria-current="page">
                View Class Attendance
              </li>
            </ol>
          </div>

          <!-- Header Card -->
          <div class="card bg-success text-white shadow mb-4 border-0">
            <div class="card-body py-4">
              <div class="row align-items-center">
                <div class="col-lg-8">
                  <span class="badge badge-light text-success mb-3 px-3 py-2">
                    <i class="fas fa-calendar-alt mr-1"></i>
                    Nursery Class Attendance
                  </span>

                  <h3 class="font-weight-bold mb-2">
                    Class Attendance Records
                  </h3>

                  <p class="mb-0">
                    Select a date to view the attendance records of your assigned nursery class.
                    Present and absent students will be shown in the table below.
                  </p>
                </div>

                <div class="col-lg-4 text-lg-right mt-4 mt-lg-0">
                  <div class="bg-white text-success rounded shadow-sm p-3 d-inline-block">
                    <i class="fas fa-clipboard-check fa-2x mb-2"></i>
                    <div class="font-weight-bold">
                      Attendance Viewer
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-lg-12">

              <!-- Form Card -->
              <div class="card shadow mb-4 border-0">
                <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between border-bottom">
                  <h6 class="m-0 font-weight-bold text-success">
                    <i class="fas fa-search mr-2"></i>
                    Search Class Attendance
                  </h6>

                  <span class="badge badge-success px-3 py-2">
                    <i class="fas fa-calendar mr-1"></i>
                    Date Filter
                  </span>
                </div>

                <div class="card-body">

                  <?php echo $statusMsg; ?>

                  <form method="post">
                    <div class="form-group row mb-3">
                      <div class="col-xl-6 col-lg-7">
                        <label class="form-control-label font-weight-bold">
                          Select Date<span class="text-danger ml-2">*</span>
                        </label>

                        <input 
                          type="date" 
                          class="form-control" 
                          name="dateTaken" 
                          required
                          value="<?php echo isset($_POST['dateTaken']) ? escapeOutput($_POST['dateTaken']) : ''; ?>">
                      </div>
                    </div>

                    <button type="submit" name="view" class="btn btn-success shadow-sm px-4">
                      <i class="fas fa-eye mr-1"></i>
                      View Attendance
                    </button>
                  </form>
                </div>
              </div>

              <!-- Attendance Table -->
              <div class="row">
                <div class="col-lg-12">
                  <div class="card shadow mb-4 border-0">

                    <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between border-bottom">
                      <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-table mr-2"></i>
                        Class Attendance
                      </h6>

                      <span class="badge badge-success px-3 py-2">
                        <i class="fas fa-list mr-1"></i>
                        Records
                      </span>
                    </div>

                    <div class="card-body">

                      <div class="alert alert-success border-left-success shadow-sm" role="alert">
                        <i class="fas fa-info-circle mr-2"></i>
                        This table shows the attendance records for the selected date.
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
                              <th>Session</th>
                              <th>Term</th>
                              <th>Status</th>
                              <th>Date</th>
                            </tr>
                          </thead>

                          <tbody>
                            <?php
                            if (!empty($attendanceRows)) {
                                $sn = 0;

                                foreach ($attendanceRows as $rows) {
                                    $sn++;

                                    if ($rows['status'] == '1') {
                                        $status = "Present";
                                        $badgeClass = "badge badge-success px-3 py-2";
                                    } else {
                                        $status = "Absent";
                                        $badgeClass = "badge badge-danger px-3 py-2";
                                    }

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
                                      <td>" . escapeOutput($rows['sessionName']) . "</td>
                                      <td>" . escapeOutput($rows['termName']) . "</td>
                                      <td>
                                        <span class='" . escapeOutput($badgeClass) . "'>" . escapeOutput($status) . "</span>
                                      </td>
                                      <td>" . escapeOutput($rows['dateTimeTaken']) . "</td>
                                    </tr>";
                                }
                            } else {
                                echo "
                                <tr>
                                  <td colspan='11'>
                                    <div class='alert alert-info mb-0' role='alert'>
                                      <i class='fas fa-info-circle mr-2'></i>
                                      Select a date and click View Attendance.
                                    </div>
                                  </td>
                                </tr>";
                            }
                            ?>
                          </tbody>
                        </table>
                      </div>

                    </div>

                  </div>
                </div>
              </div>

            </div>
          </div>

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

  <!-- Page level plugins -->
  <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
  <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

  <script>
    $(document).ready(function () {
      $('#dataTableHover').DataTable();
    });
  </script>

</body>
</html>