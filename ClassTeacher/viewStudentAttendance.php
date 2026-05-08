<?php 
/*
|--------------------------------------------------------------------------
| SECURED VIEW STUDENT ATTENDANCE PAGE
|--------------------------------------------------------------------------
| Security improvements:
| 1. ClassTeacher role protection added using requireRole("ClassTeacher").
| 2. Session values are validated and converted to integers.
| 3. Student dropdown only shows students from logged-in teacher's class.
| 4. Form inputs are validated.
| 5. SQL Injection is prevented using prepared statements.
| 6. Output is escaped using htmlspecialchars() to reduce XSS risk.
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
| LOAD STUDENTS FOR DROPDOWN
|--------------------------------------------------------------------------
*/

$studentList = [];

$studentQuery = "SELECT admissionNumber, firstName, lastName 
                 FROM tblstudents 
                 WHERE classId = ? 
                 AND classArmId = ?
                 ORDER BY firstName ASC";

$stmt = $conn->prepare($studentQuery);

if ($stmt) {
    $stmt->bind_param("ii", $classId, $classArmId);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $studentList[] = $row;
        }
    }

    $stmt->close();
} else {
    error_log("Student dropdown query failed: " . $conn->error);
}

/*
|--------------------------------------------------------------------------
| VIEW STUDENT ATTENDANCE
|--------------------------------------------------------------------------
*/

if (isset($_POST['view'])) {

    $admissionNumber = trim($_POST['admissionNumber'] ?? '');
    $type = trim($_POST['type'] ?? '');

    if ($admissionNumber == "" || $type == "") {
        $statusMsg = "<div class='alert alert-danger custom-alert' role='alert'>Please select student and attendance type.</div>";
    } 
    else if (!in_array($type, ["1", "2", "3"])) {
        $statusMsg = "<div class='alert alert-danger custom-alert' role='alert'>Invalid attendance type selected.</div>";
    } 
    else {

        /*
        |--------------------------------------------------------------------------
        | BASE QUERY
        |--------------------------------------------------------------------------
        | This query is reused for All, Single Date, and Date Range.
        |--------------------------------------------------------------------------
        */

        $baseQuery = "SELECT 
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
                      WHERE tblattendance.admissionNo = ?
                      AND tblattendance.classId = ?
                      AND tblattendance.classArmId = ?";

        /*
        |--------------------------------------------------------------------------
        | TYPE 1: ALL ATTENDANCE
        |--------------------------------------------------------------------------
        */

        if ($type == "1") {

            $query = $baseQuery . " ORDER BY tblattendance.dateTimeTaken DESC";

            $stmt = $conn->prepare($query);

            if ($stmt) {
                $stmt->bind_param("sii", $admissionNumber, $classId, $classArmId);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | TYPE 2: SINGLE DATE ATTENDANCE
        |--------------------------------------------------------------------------
        */

        else if ($type == "2") {

            $singleDate = trim($_POST['singleDate'] ?? '');

            if ($singleDate == "") {
                $statusMsg = "<div class='alert alert-danger custom-alert' role='alert'>Please select a date.</div>";
                $stmt = null;
            } 
            else if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $singleDate)) {
                $statusMsg = "<div class='alert alert-danger custom-alert' role='alert'>Invalid date format.</div>";
                $stmt = null;
            } 
            else {
                $query = $baseQuery . " AND tblattendance.dateTimeTaken = ?
                                      ORDER BY tblattendance.dateTimeTaken DESC";

                $stmt = $conn->prepare($query);

                if ($stmt) {
                    $stmt->bind_param("siis", $admissionNumber, $classId, $classArmId, $singleDate);
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | TYPE 3: DATE RANGE ATTENDANCE
        |--------------------------------------------------------------------------
        */

        else if ($type == "3") {

            $fromDate = trim($_POST['fromDate'] ?? '');
            $toDate = trim($_POST['toDate'] ?? '');

            if ($fromDate == "" || $toDate == "") {
                $statusMsg = "<div class='alert alert-danger custom-alert' role='alert'>Please select from date and to date.</div>";
                $stmt = null;
            } 
            else if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $fromDate) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $toDate)) {
                $statusMsg = "<div class='alert alert-danger custom-alert' role='alert'>Invalid date format.</div>";
                $stmt = null;
            } 
            else if ($fromDate > $toDate) {
                $statusMsg = "<div class='alert alert-danger custom-alert' role='alert'>From Date cannot be greater than To Date.</div>";
                $stmt = null;
            } 
            else {
                $query = $baseQuery . " AND tblattendance.dateTimeTaken BETWEEN ? AND ?
                                      ORDER BY tblattendance.dateTimeTaken DESC";

                $stmt = $conn->prepare($query);

                if ($stmt) {
                    $stmt->bind_param("siiss", $admissionNumber, $classId, $classArmId, $fromDate, $toDate);
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | EXECUTE QUERY
        |--------------------------------------------------------------------------
        */

        if (isset($stmt) && $stmt) {

            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $attendanceRows[] = $row;
                }
            } else {
                $statusMsg = "<div class='alert alert-danger custom-alert' role='alert'>No Record Found!</div>";
            }

            $stmt->close();
        } 
        else if (empty($statusMsg)) {
            error_log("View student attendance prepare failed: " . $conn->error);
            $statusMsg = "<div class='alert alert-danger custom-alert' role='alert'>Unable to load attendance records.</div>";
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
  <title>View Student Attendance</title>

  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="css/ruang-admin.min.css" rel="stylesheet">

  <style>
    body {
      background: #f4fbf8;
      font-family: "Nunito", "Segoe UI", Arial, sans-serif;
    }

    #content-wrapper {
      background:
        linear-gradient(135deg, rgba(236, 253, 245, 0.96), rgba(239, 246, 255, 0.96)),
        url('../img/logo/nursery-attendance-bg.jpg');
      background-size: cover;
      background-position: center;
      background-attachment: scroll;
      min-height: 100vh;
      position: relative;
    }

    #content-wrapper::before {
      display: none;
    }

    #content,
    footer {
      position: relative;
      z-index: 1;
    }

    .navbar,
    .topbar {
      background: #ffffff !important;
      backdrop-filter: none !important;
      filter: none !important;
      opacity: 1 !important;
      box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06);
      position: relative;
      z-index: 50;
    }

    .topbar .nav-item .nav-link,
    .navbar .nav-item .nav-link {
      color: #334155 !important;
      opacity: 1 !important;
      filter: none !important;
    }

    .page-header-card {
      border: none;
      border-radius: 26px;
      overflow: hidden;
      background:
        linear-gradient(135deg, rgba(15, 118, 110, 0.95), rgba(34, 197, 94, 0.88)),
        url('../img/logo/nursery-attendance-bg.jpg');
      background-size: cover;
      background-position: center;
      box-shadow: 0 18px 45px rgba(15, 118, 110, 0.22);
      margin-bottom: 28px;
      position: relative;
    }

    .page-header-card::after {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(90deg, rgba(0, 0, 0, 0.15), rgba(0, 0, 0, 0.03));
      pointer-events: none;
    }

    .page-header-content {
      position: relative;
      z-index: 1;
      padding: 30px 32px;
      color: #ffffff;
    }

    .page-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 14px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.18);
      border: 1px solid rgba(255, 255, 255, 0.28);
      font-size: 12px;
      font-weight: 800;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      margin-bottom: 14px;
    }

    .page-badge i {
      color: #fde68a;
    }

    .page-title {
      font-size: 30px;
      font-weight: 800;
      margin-bottom: 8px;
      color: #ffffff;
    }

    .page-subtitle {
      margin-bottom: 0;
      color: rgba(255, 255, 255, 0.92);
      font-size: 15px;
      line-height: 1.7;
      max-width: 780px;
    }

    .custom-breadcrumb {
      background: rgba(255, 255, 255, 0.95);
      border: 1px solid rgba(209, 250, 229, 0.9);
      border-radius: 999px;
      padding: 10px 18px;
      box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
      margin-bottom: 0;
    }

    .custom-breadcrumb a {
      color: #0f766e;
      font-weight: 700;
    }

    .custom-breadcrumb .active {
      color: #64748b;
      font-weight: 700;
    }

    .nursery-card {
      border: none;
      border-radius: 24px;
      background: rgba(255, 255, 255, 0.97);
      box-shadow: 0 16px 38px rgba(15, 23, 42, 0.08);
      overflow: hidden;
      border: 1px solid rgba(209, 250, 229, 0.75);
      margin-bottom: 26px;
    }

    .nursery-card-header {
      background: linear-gradient(135deg, #ffffff, #f0fdf4);
      border-bottom: 1px solid rgba(209, 250, 229, 0.95);
      padding: 20px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      flex-wrap: wrap;
    }

    .nursery-card-title {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 0;
      color: #183b35;
      font-size: 17px;
      font-weight: 900;
    }

    .title-icon {
      width: 42px;
      height: 42px;
      border-radius: 16px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #ecfeff, #d1fae5);
      color: #0f766e;
      box-shadow: 0 10px 20px rgba(15, 118, 110, 0.12);
    }

    .nursery-card-body {
      padding: 24px;
    }

    .form-control-label {
      color: #334155;
      font-size: 13px;
      font-weight: 800;
      margin-bottom: 8px;
    }

    .form-control {
      height: 48px;
      border-radius: 15px;
      border: 1px solid #dbe7e3;
      background: #f8fbfa;
      color: #1f2937;
      font-size: 14px;
      transition: all 0.25s ease;
    }

    .form-control:focus {
      border-color: #13a386;
      background: #ffffff;
      box-shadow: 0 0 0 4px rgba(19, 163, 134, 0.12);
    }

    .btn-nursery {
      min-height: 48px;
      border: none;
      border-radius: 15px;
      background: linear-gradient(135deg, #0f766e, #22c55e);
      color: #ffffff;
      font-weight: 900;
      letter-spacing: 0.2px;
      padding: 12px 22px;
      box-shadow: 0 14px 26px rgba(34, 197, 94, 0.24);
      transition: all 0.25s ease;
    }

    .btn-nursery:hover {
      color: #ffffff;
      transform: translateY(-2px);
      box-shadow: 0 18px 34px rgba(34, 197, 94, 0.32);
      background: linear-gradient(135deg, #0d6f67, #16a34a);
    }

    .custom-alert {
      border-radius: 16px !important;
      border: none;
      font-size: 14px;
      line-height: 1.5;
      box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
      margin-bottom: 0;
    }

    .table-responsive {
      border-radius: 18px;
    }

    .attendance-table {
      margin-bottom: 0;
      color: #334155;
    }

    .attendance-table thead th {
      background: #ecfdf5 !important;
      color: #0f766e;
      font-size: 12px;
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-bottom: 1px solid #bbf7d0 !important;
      white-space: nowrap;
    }

    .attendance-table tbody td {
      vertical-align: middle;
      font-size: 13px;
      border-top: 1px solid #edf2f7;
      white-space: nowrap;
    }

    .attendance-table tbody tr:hover {
      background: #f8fffc;
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 82px;
      padding: 7px 12px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 900;
    }

    .status-present {
      background: #dcfce7;
      color: #166534;
      border: 1px solid #86efac;
    }

    .status-absent {
      background: #fee2e2;
      color: #991b1b;
      border: 1px solid #fca5a5;
    }

    .empty-state {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 18px;
      border-radius: 18px;
      background: linear-gradient(135deg, #ecfdf5, #eff6ff);
      border: 1px solid #d1fae5;
      color: #0f766e;
      font-weight: 700;
    }

    .empty-state i {
      width: 42px;
      height: 42px;
      min-width: 42px;
      border-radius: 15px;
      background: #ffffff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #0f766e;
      box-shadow: 0 10px 20px rgba(15, 118, 110, 0.12);
    }

    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
      border-radius: 12px;
      border: 1px solid #dbe7e3;
      padding: 6px 10px;
      outline: none;
    }

    .page-item.active .page-link {
      background-color: #0f766e;
      border-color: #0f766e;
    }

    .page-link {
      color: #0f766e;
      border-radius: 10px;
      margin: 0 2px;
    }

    @media (max-width: 768px) {
      .page-header-content {
        padding: 25px 22px;
      }

      .page-title {
        font-size: 24px;
      }

      .custom-breadcrumb {
        margin-top: 15px;
        border-radius: 18px;
      }

      .nursery-card-body {
        padding: 20px;
      }
    }
  </style>

  <script>
    function typeDropDown(str) {
      if (str == "") {
        document.getElementById("txtHint").innerHTML = "";
        return;
      }

      let html = "";

      if (str == "1") {
        html = "";
      }

      if (str == "2") {
        html = `
          <div class="form-group row mb-3">
            <div class="col-xl-6">
              <label class="form-control-label">Select Date<span class="text-danger ml-2">*</span></label>
              <input type="date" class="form-control" name="singleDate" required>
            </div>
          </div>
        `;
      }

      if (str == "3") {
        html = `
          <div class="form-group row mb-3">
            <div class="col-xl-6">
              <label class="form-control-label">From Date<span class="text-danger ml-2">*</span></label>
              <input type="date" class="form-control" name="fromDate" required>
            </div>

            <div class="col-xl-6">
              <label class="form-control-label">To Date<span class="text-danger ml-2">*</span></label>
              <input type="date" class="form-control" name="toDate" required>
            </div>
          </div>
        `;
      }

      document.getElementById("txtHint").innerHTML = html;
    }
  </script>
</head>

<body id="page-top">
  <div id="wrapper">

    <!-- Sidebar -->
    <?php include "Includes/sidebar.php";?>
    <!-- Sidebar -->

    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">

        <!-- TopBar -->
        <?php include "Includes/topbar.php";?>
        <!-- Topbar -->

        <!-- Container Fluid-->
        <div class="container-fluid" id="container-wrapper">

          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div></div>

            <ol class="breadcrumb custom-breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">View Student Attendance</li>
            </ol>
          </div>

          <div class="page-header-card">
            <div class="page-header-content">
              <div class="page-badge">
                <i class="fas fa-calendar-check"></i>
                Nursery Attendance Records
              </div>

              <h1 class="page-title">View Student Attendance</h1>

              <p class="page-subtitle">
                Select a nursery student and choose how you want to view the attendance record.
                You can check all records, a single date, or a specific date range.
              </p>
            </div>
          </div>

          <div class="row">
            <div class="col-lg-12">

              <!-- Form Basic -->
              <div class="card nursery-card">
                <div class="nursery-card-header">
                  <h6 class="nursery-card-title">
                    <span class="title-icon">
                      <i class="fas fa-search"></i>
                    </span>
                    Search Attendance Record
                  </h6>

                  <?php echo $statusMsg; ?>
                </div>

                <div class="card-body nursery-card-body">
                  <form method="post">

                    <div class="form-group row mb-3">
                      <div class="col-xl-6">
                        <label class="form-control-label">
                          Select Student<span class="text-danger ml-2">*</span>
                        </label>

                        <select required name="admissionNumber" class="form-control mb-3">
                          <option value="">--Select Student--</option>

                          <?php
                          foreach ($studentList as $student) {
                              $selected = "";
                              if (isset($_POST['admissionNumber']) && $_POST['admissionNumber'] == $student['admissionNumber']) {
                                  $selected = "selected";
                              }

                              echo '<option value="' . escapeOutput($student['admissionNumber']) . '" ' . $selected . '>' .
                                      escapeOutput($student['firstName'] . ' ' . $student['lastName']) .
                                   '</option>';
                          }
                          ?>
                        </select>
                      </div>

                      <div class="col-xl-6">
                        <label class="form-control-label">
                          Type<span class="text-danger ml-2">*</span>
                        </label>

                        <select required name="type" onchange="typeDropDown(this.value)" class="form-control mb-3">
                          <option value="">--Select--</option>
                          <option value="1" <?php echo (isset($_POST['type']) && $_POST['type'] == "1") ? "selected" : ""; ?>>All</option>
                          <option value="2" <?php echo (isset($_POST['type']) && $_POST['type'] == "2") ? "selected" : ""; ?>>By Single Date</option>
                          <option value="3" <?php echo (isset($_POST['type']) && $_POST['type'] == "3") ? "selected" : ""; ?>>By Date Range</option>
                        </select>
                      </div>
                    </div>

                    <div id="txtHint"></div>

                    <button type="submit" name="view" class="btn btn-nursery">
                      <i class="fas fa-eye mr-1"></i>
                      View Attendance
                    </button>
                  </form>
                </div>
              </div>

              <!-- Attendance Table -->
              <div class="row">
                <div class="col-lg-12">
                  <div class="card nursery-card">

                    <div class="nursery-card-header">
                      <h6 class="nursery-card-title">
                        <span class="title-icon">
                          <i class="fas fa-table"></i>
                        </span>
                        Student Attendance
                      </h6>
                    </div>

                    <div class="table-responsive p-3">
                      <table class="table align-items-center table-flush table-hover attendance-table" id="dataTableHover">
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
                          if (!empty($attendanceRows)) {
                              $sn = 0;

                              foreach ($attendanceRows as $rows) {
                                  $sn++;

                                  if ($rows['status'] == '1') {
                                      $status = "Present";
                                      $statusClass = "status-present";
                                  } else {
                                      $status = "Absent";
                                      $statusClass = "status-absent";
                                  }

                                  echo "
                                  <tr>
                                    <td>" . escapeOutput($sn) . "</td>
                                    <td>" . escapeOutput($rows['firstName']) . "</td>
                                    <td>" . escapeOutput($rows['lastName']) . "</td>
                                    <td>" . escapeOutput($rows['otherName']) . "</td>
                                    <td>" . escapeOutput($rows['admissionNumber']) . "</td>
                                    <td>" . escapeOutput($rows['className']) . "</td>
                                    <td>" . escapeOutput($rows['classArmName']) . "</td>
                                    <td>" . escapeOutput($rows['sessionName']) . "</td>
                                    <td>" . escapeOutput($rows['termName']) . "</td>
                                    <td><span class='status-badge " . escapeOutput($statusClass) . "'>" . escapeOutput($status) . "</span></td>
                                    <td>" . escapeOutput($rows['dateTimeTaken']) . "</td>
                                  </tr>";
                              }
                          } else {
                              echo "
                              <tr>
                                <td colspan='11'>
                                  <div class='empty-state'>
                                    <i class='fas fa-info-circle'></i>
                                    <span>Select a student and attendance type, then click View Attendance.</span>
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

      // Keep date fields visible after form submit
      const selectedType = document.querySelector('select[name="type"]').value;
      if (selectedType) {
        typeDropDown(selectedType);
      }
    });
  </script>

</body>
</html>