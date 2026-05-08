<?php
/*
|--------------------------------------------------------------------------
| ADMIN DASHBOARD - SECURED VERSION
|--------------------------------------------------------------------------
| Fixes included:
| 1. Admin role protection using requireRole("Administrator").
| 2. Removed the accidental login role dropdown from the dashboard.
| 3. Uses COUNT(*) instead of SELECT * for dashboard totals.
| 4. Uses htmlspecialchars() when displaying values.
|--------------------------------------------------------------------------
*/

// TEMPORARY: show errors while testing.
// After everything works, you can comment these two lines.
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../Includes/dbcon.php';
include '../Includes/session.php';

requireRole("Administrator");

function safeOutput($data)
{
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

function getTotalCount($conn, $tableName)
{
    $allowedTables = [
        'tblstudents',
        'tblclass',
        'tblclassarms',
        'tblattendance',
        'tblclassteacher',
        'tblsessionterm',
        'tblterm'
    ];

    if (!in_array($tableName, $allowedTables)) {
        return 0;
    }

    $query = "SELECT COUNT(*) AS total FROM $tableName";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        error_log("Count query failed for table {$tableName}: " . mysqli_error($conn));
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return (int) $row['total'];
}

$students      = getTotalCount($conn, 'tblstudents');
$class         = getTotalCount($conn, 'tblclass');
$classArms     = getTotalCount($conn, 'tblclassarms');
$totAttendance = getTotalCount($conn, 'tblattendance');
$classTeacher  = getTotalCount($conn, 'tblclassteacher');
$sessTerm      = getTotalCount($conn, 'tblsessionterm');
$termonly      = getTotalCount($conn, 'tblterm');
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

  <title>Administrator Dashboard</title>

  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="css/ruang-admin.min.css" rel="stylesheet">
</head>

<body id="page-top">

  <div id="wrapper">

    <!-- Sidebar -->
    <?php include "Includes/sidebar.php"; ?>
    <!-- Sidebar -->

    <div id="content-wrapper" class="d-flex flex-column bg-light">

      <div id="content">

        <!-- TopBar -->
        <?php include "Includes/topbar.php"; ?>
        <!-- Topbar -->

        <!-- Container Fluid -->
        <div class="container-fluid" id="container-wrapper">

          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
              <h1 class="h3 mb-0 text-gray-800 font-weight-bold">
                Administrator Dashboard
              </h1>

              <p class="mb-0 text-muted">
                Manage nursery classes, teachers, students, sessions, and attendance records.
              </p>
            </div>

            <ol class="breadcrumb bg-white shadow-sm border">
              <li class="breadcrumb-item">
                <a href="./" class="text-success">Home</a>
              </li>
              <li class="breadcrumb-item active" aria-current="page">
                Dashboard
              </li>
            </ol>
          </div>

          <!-- Header Card -->
          <div class="card bg-success text-white shadow mb-4 border-0">
            <div class="card-body py-4">
              <div class="row align-items-center">
                <div class="col-lg-8">
                  <span class="badge badge-light text-success mb-3 px-3 py-2">
                    <i class="fas fa-user-shield mr-1"></i>
                    Nursery Attendance Administration
                  </span>

                  <h3 class="font-weight-bold mb-2">
                    Welcome to the Admin Control Center
                  </h3>

                  <p class="mb-0">
                    View system totals and manage the important records needed for nursery attendance monitoring.
                  </p>
                </div>

                <div class="col-lg-4 text-lg-right mt-4 mt-lg-0">
                  <div class="bg-white text-success rounded shadow-sm p-3 d-inline-block">
                    <i class="fas fa-school fa-2x mb-2"></i>
                    <div class="font-weight-bold">
                      Admin Panel
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row mb-3">

            <!-- Students Card -->
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card shadow h-100 border-0">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">

                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                        Students
                      </div>

                      <div class="h4 mb-0 font-weight-bold text-gray-800">
                        <?php echo safeOutput($students); ?>
                      </div>

                      <div class="small text-muted mt-2">
                        Total registered learners
                      </div>
                    </div>

                    <div class="col-auto">
                      <div class="bg-success text-white rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:55px;height:55px;">
                        <i class="fas fa-users fa-lg"></i>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>

            <!-- Classes Card -->
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card shadow h-100 border-0">
                <div class="card-body">
                  <div class="row align-items-center">

                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                        Classes
                      </div>

                      <div class="h4 mb-0 font-weight-bold text-gray-800">
                        <?php echo safeOutput($class); ?>
                      </div>

                      <div class="small text-muted mt-2">
                        Total class records
                      </div>
                    </div>

                    <div class="col-auto">
                      <div class="bg-primary text-white rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:55px;height:55px;">
                        <i class="fas fa-chalkboard fa-lg"></i>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>

            <!-- Class Arms Card -->
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card shadow h-100 border-0">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">

                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                        Class Arms
                      </div>

                      <div class="h4 mb-0 font-weight-bold text-gray-800">
                        <?php echo safeOutput($classArms); ?>
                      </div>

                      <div class="small text-muted mt-2">
                        Total sections or arms
                      </div>
                    </div>

                    <div class="col-auto">
                      <div class="bg-success text-white rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:55px;height:55px;">
                        <i class="fas fa-code-branch fa-lg"></i>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>

            <!-- Total Attendance Card -->
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card shadow h-100 border-0">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">

                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                        Total Attendance
                      </div>

                      <div class="h4 mb-0 font-weight-bold text-gray-800">
                        <?php echo safeOutput($totAttendance); ?>
                      </div>

                      <div class="small text-muted mt-2">
                        All attendance records
                      </div>
                    </div>

                    <div class="col-auto">
                      <div class="bg-warning text-white rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:55px;height:55px;">
                        <i class="fas fa-calendar-check fa-lg"></i>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>

            <!-- Class Teachers Card -->
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card shadow h-100 border-0">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">

                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                        Class Teachers
                      </div>

                      <div class="h4 mb-0 font-weight-bold text-gray-800">
                        <?php echo safeOutput($classTeacher); ?>
                      </div>

                      <div class="small text-muted mt-2">
                        Registered teachers
                      </div>
                    </div>

                    <div class="col-auto">
                      <div class="bg-danger text-white rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:55px;height:55px;">
                        <i class="fas fa-chalkboard-teacher fa-lg"></i>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>

            <!-- Session and Terms Card -->
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card shadow h-100 border-0">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">

                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                        Session & Terms
                      </div>

                      <div class="h4 mb-0 font-weight-bold text-gray-800">
                        <?php echo safeOutput($sessTerm); ?>
                      </div>

                      <div class="small text-muted mt-2">
                        Academic session records
                      </div>
                    </div>

                    <div class="col-auto">
                      <div class="bg-info text-white rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:55px;height:55px;">
                        <i class="fas fa-calendar-alt fa-lg"></i>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>

            <!-- Terms Card -->
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card shadow h-100 border-0">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">

                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                        Terms
                      </div>

                      <div class="h4 mb-0 font-weight-bold text-gray-800">
                        <?php echo safeOutput($termonly); ?>
                      </div>

                      <div class="small text-muted mt-2">
                        Total academic terms
                      </div>
                    </div>

                    <div class="col-auto">
                      <div class="bg-secondary text-white rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:55px;height:55px;">
                        <i class="fas fa-th fa-lg"></i>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>

          </div>
          <!-- Row -->

          <div class="row">
            <div class="col-lg-12 mb-4">
              <div class="card shadow border-0">
                <div class="card-header bg-white py-3 border-bottom">
                  <h6 class="m-0 font-weight-bold text-success">
                    <i class="fas fa-info-circle mr-2"></i>
                    Administrator Overview
                  </h6>
                </div>

                <div class="card-body">
                  <div class="alert alert-success border-left-success shadow-sm mb-0" role="alert">
                    <i class="fas fa-shield-alt mr-2"></i>
                    You are signed in as an administrator. Use the sidebar to manage classes, class arms, class teachers, students, sessions, and terms.
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
        <!-- Container Fluid -->

      </div>
      <!-- Content -->

      <!-- Footer -->
      <?php include 'includes/footer.php'; ?>
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
  <script src="../vendor/chart.js/Chart.min.js"></script>
  <script src="js/demo/chart-area-demo.js"></script>

</body>

</html>