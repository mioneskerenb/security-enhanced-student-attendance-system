<?php
/*
|--------------------------------------------------------------------------
| CLASS TEACHER DASHBOARD - FIXED VERSION
|--------------------------------------------------------------------------
| Fixes:
| 1. Prevents fetch_assoc() on bool error.
| 2. Uses safe prepared statements.
| 3. Validates session values.
| 4. Shows clean dashboard counts.
| 5. Logs SQL errors instead of crashing the page.
|--------------------------------------------------------------------------
*/

error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../Includes/dbcon.php';
include '../Includes/session.php';

requireRole("ClassTeacher");

/*
|--------------------------------------------------------------------------
| SAFE OUTPUT FUNCTION
|--------------------------------------------------------------------------
*/
function safeOutput($data)
{
    return htmlspecialchars((string)($data ?? ''), ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| SESSION VALIDATION
|--------------------------------------------------------------------------
*/
$userId     = isset($_SESSION['userId']) ? (int)$_SESSION['userId'] : 0;
$classId    = isset($_SESSION['classId']) ? (int)$_SESSION['classId'] : 0;
$classArmId = isset($_SESSION['classArmId']) ? (int)$_SESSION['classArmId'] : 0;

if ($userId <= 0 || $classId <= 0 || $classArmId <= 0) {
    session_unset();
    session_destroy();

    header("Location: ../index.php?message=invalid_session");
    exit();
}

/*
|--------------------------------------------------------------------------
| GET CLASS TEACHER CLASS INFORMATION
|--------------------------------------------------------------------------
*/
$className = "Unknown Class";
$classArmName = "Unknown Arm";

$query = "SELECT c.className, ca.classArmName
          FROM tblclassteacher ct
          INNER JOIN tblclass c ON c.Id = ct.classId
          INNER JOIN tblclassarms ca ON ca.Id = ct.classArmId
          WHERE ct.Id = ?
          LIMIT 1";

$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        $stmt->bind_result($dbClassName, $dbClassArmName);

        if ($stmt->fetch()) {
            $className = $dbClassName;
            $classArmName = $dbClassArmName;
        }
    } else {
        error_log("Class teacher info execute failed: " . $stmt->error);
    }

    $stmt->close();
} else {
    error_log("Class teacher info prepare failed: " . $conn->error);
}

/*
|--------------------------------------------------------------------------
| COUNT ALL RECORDS FROM TABLE
|--------------------------------------------------------------------------
*/
function countAllRecords($conn, $tableName)
{
    $allowedTables = [
        'tblclass',
        'tblclassarms'
    ];

    if (!in_array($tableName, $allowedTables, true)) {
        return 0;
    }

    $query = "SELECT COUNT(*) AS total FROM $tableName";
    $result = $conn->query($query);

    if (!$result) {
        error_log("Count all records failed for $tableName: " . $conn->error);
        return 0;
    }

    $row = $result->fetch_assoc();

    if (!$row || !isset($row['total'])) {
        return 0;
    }

    return (int)$row['total'];
}

/*
|--------------------------------------------------------------------------
| COUNT STUDENTS FROM LOGGED-IN TEACHER'S CLASS
|--------------------------------------------------------------------------
*/
function countStudents($conn, $classId, $classArmId)
{
    $query = "SELECT COUNT(*) AS total
              FROM tblstudents
              WHERE classId = ? AND classArmId = ?";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        error_log("Count students prepare failed: " . $conn->error);
        return 0;
    }

    $stmt->bind_param("ii", $classId, $classArmId);

    if (!$stmt->execute()) {
        error_log("Count students execute failed: " . $stmt->error);
        $stmt->close();
        return 0;
    }

    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();

    return (int)$total;
}

/*
|--------------------------------------------------------------------------
| COUNT ATTENDANCE FROM LOGGED-IN TEACHER'S CLASS
|--------------------------------------------------------------------------
*/
function countAttendance($conn, $classId, $classArmId)
{
    $query = "SELECT COUNT(*) AS total
              FROM tblattendance
              WHERE classId = ? AND classArmId = ?";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        error_log("Count attendance prepare failed: " . $conn->error);
        return 0;
    }

    $stmt->bind_param("ii", $classId, $classArmId);

    if (!$stmt->execute()) {
        error_log("Count attendance execute failed: " . $stmt->error);
        $stmt->close();
        return 0;
    }

    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();

    return (int)$total;
}

/*
|--------------------------------------------------------------------------
| DASHBOARD TOTALS
|--------------------------------------------------------------------------
*/
$students      = countStudents($conn, $classId, $classArmId);
$class         = countAllRecords($conn, 'tblclass');
$classArms     = countAllRecords($conn, 'tblclassarms');
$totAttendance = countAttendance($conn, $classId, $classArmId);
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

  <title>Class Teacher Dashboard</title>

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

    .topbar .dropdown-toggle,
    .navbar .dropdown-toggle {
      opacity: 1 !important;
      filter: none !important;
    }

    .dashboard-header-card {
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

    .dashboard-header-card::after {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(90deg, rgba(0, 0, 0, 0.15), rgba(0, 0, 0, 0.03));
      pointer-events: none;
    }

    .dashboard-header-content {
      position: relative;
      z-index: 1;
      padding: 30px 32px;
      color: #ffffff;
    }

    .dashboard-badge {
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

    .dashboard-badge i {
      color: #fde68a;
    }

    .dashboard-title {
      font-size: 30px;
      font-weight: 800;
      margin-bottom: 8px;
      color: #ffffff;
    }

    .dashboard-subtitle {
      margin-bottom: 0;
      color: rgba(255, 255, 255, 0.92);
      font-size: 15px;
      line-height: 1.7;
    }

    .class-pill {
      display: inline-flex;
      align-items: center;
      gap: 9px;
      padding: 12px 16px;
      border-radius: 18px;
      background: rgba(255, 255, 255, 0.18);
      border: 1px solid rgba(255, 255, 255, 0.28);
      color: #ffffff;
      font-weight: 800;
      margin-top: 18px;
    }

    .class-pill i {
      color: #fde68a;
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

    .stat-card {
      border: none;
      border-radius: 24px;
      background: rgba(255, 255, 255, 0.97);
      box-shadow: 0 16px 38px rgba(15, 23, 42, 0.08);
      overflow: hidden;
      position: relative;
      transition: all 0.25s ease;
      border: 1px solid rgba(209, 250, 229, 0.75);
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 22px 50px rgba(15, 118, 110, 0.16);
    }

    .stat-card::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 5px;
      background: linear-gradient(90deg, #0f766e, #22c55e);
    }

    .stat-card-body {
      padding: 25px 24px;
    }

    .stat-label {
      font-size: 12px;
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: #64748b;
      margin-bottom: 10px;
    }

    .stat-value {
      font-size: 34px;
      font-weight: 900;
      color: #183b35;
      line-height: 1;
      margin-bottom: 8px;
    }

    .stat-desc {
      font-size: 13px;
      color: #64748b;
      margin-bottom: 0;
    }

    .stat-icon {
      width: 62px;
      height: 62px;
      border-radius: 20px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 25px;
      box-shadow: 0 14px 24px rgba(15, 118, 110, 0.12);
    }

    .icon-students {
      background: linear-gradient(135deg, #ecfeff, #d1fae5);
      color: #0891b2;
    }

    .icon-classes {
      background: linear-gradient(135deg, #eff6ff, #dbeafe);
      color: #2563eb;
    }

    .icon-arms {
      background: linear-gradient(135deg, #f0fdf4, #dcfce7);
      color: #16a34a;
    }

    .icon-attendance {
      background: linear-gradient(135deg, #fffbeb, #fef3c7);
      color: #d97706;
    }

    .quick-panel {
      border: none;
      border-radius: 24px;
      background: rgba(255, 255, 255, 0.97);
      box-shadow: 0 16px 38px rgba(15, 23, 42, 0.07);
      border: 1px solid rgba(209, 250, 229, 0.75);
      margin-top: 8px;
      overflow: hidden;
    }

    .quick-panel-body {
      padding: 24px 26px;
    }

    .quick-panel-title {
      color: #183b35;
      font-weight: 900;
      font-size: 18px;
      margin-bottom: 8px;
    }

    .quick-panel-text {
      color: #64748b;
      font-size: 14px;
      line-height: 1.7;
      margin-bottom: 0;
    }

    .teacher-note {
      display: flex;
      gap: 14px;
      align-items: flex-start;
      padding: 18px;
      border-radius: 20px;
      background: linear-gradient(135deg, #ecfdf5, #eff6ff);
      border: 1px solid #d1fae5;
    }

    .teacher-note-icon {
      width: 44px;
      height: 44px;
      min-width: 44px;
      border-radius: 16px;
      background: #ffffff;
      color: #0f766e;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 10px 22px rgba(15, 118, 110, 0.12);
    }

    @media (max-width: 768px) {
      .dashboard-header-content {
        padding: 25px 22px;
      }

      .dashboard-title {
        font-size: 24px;
      }

      .custom-breadcrumb {
        margin-top: 15px;
        border-radius: 18px;
      }

      .stat-value {
        font-size: 30px;
      }
    }
  </style>
</head>

<body id="page-top">

  <div id="wrapper">

    <!-- Sidebar -->
    <?php include "Includes/sidebar.php"; ?>
    <!-- Sidebar -->

    <div id="content-wrapper" class="d-flex flex-column">

      <div id="content">

        <!-- TopBar -->
        <?php include "Includes/topbar.php"; ?>
        <!-- Topbar -->

        <!-- Container Fluid -->
        <div class="container-fluid" id="container-wrapper">

          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div></div>

            <ol class="breadcrumb custom-breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
            </ol>
          </div>

          <div class="dashboard-header-card">
            <div class="dashboard-header-content">
              <div class="dashboard-badge">
                <i class="fas fa-child"></i>
                Nursery Attendance Management
              </div>

              <h1 class="dashboard-title">
                Class Teacher Dashboard
              </h1>

              <p class="dashboard-subtitle">
                Monitor your assigned nursery class, student records, and attendance summaries in one secure dashboard.
              </p>

              <div class="class-pill">
                <i class="fas fa-school"></i>
                <?php echo safeOutput($className); ?>
                -
                <?php echo safeOutput($classArmName); ?>
              </div>
            </div>
          </div>

          <div class="row mb-3">

            <!-- Students Card -->
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card h-100 stat-card">
                <div class="card-body stat-card-body">
                  <div class="row no-gutters align-items-center">

                    <div class="col mr-2">
                      <div class="stat-label">
                        Students
                      </div>

                      <div class="stat-value">
                        <?php echo safeOutput($students); ?>
                      </div>

                      <p class="stat-desc">
                        Learners assigned to your class.
                      </p>
                    </div>

                    <div class="col-auto">
                      <div class="stat-icon icon-students">
                        <i class="fas fa-users"></i>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>

            <!-- Classes Card -->
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card h-100 stat-card">
                <div class="card-body stat-card-body">
                  <div class="row align-items-center">

                    <div class="col mr-2">
                      <div class="stat-label">
                        Classes
                      </div>

                      <div class="stat-value">
                        <?php echo safeOutput($class); ?>
                      </div>

                      <p class="stat-desc">
                        Total class records in the system.
                      </p>
                    </div>

                    <div class="col-auto">
                      <div class="stat-icon icon-classes">
                        <i class="fas fa-chalkboard"></i>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>

            <!-- Class Arms Card -->
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card h-100 stat-card">
                <div class="card-body stat-card-body">
                  <div class="row no-gutters align-items-center">

                    <div class="col mr-2">
                      <div class="stat-label">
                        Class Arms
                      </div>

                      <div class="stat-value">
                        <?php echo safeOutput($classArms); ?>
                      </div>

                      <p class="stat-desc">
                        Available class sections or arms.
                      </p>
                    </div>

                    <div class="col-auto">
                      <div class="stat-icon icon-arms">
                        <i class="fas fa-code-branch"></i>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>

            <!-- Total Attendance Card -->
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card h-100 stat-card">
                <div class="card-body stat-card-body">
                  <div class="row no-gutters align-items-center">

                    <div class="col mr-2">
                      <div class="stat-label">
                        Total Attendance
                      </div>

                      <div class="stat-value">
                        <?php echo safeOutput($totAttendance); ?>
                      </div>

                      <p class="stat-desc">
                        Attendance records for your class.
                      </p>
                    </div>

                    <div class="col-auto">
                      <div class="stat-icon icon-attendance">
                        <i class="fas fa-calendar-check"></i>
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
              <div class="quick-panel">
                <div class="quick-panel-body">
                  <div class="teacher-note">
                    <div class="teacher-note-icon">
                      <i class="fas fa-shield-alt"></i>
                    </div>

                    <div>
                      <h5 class="quick-panel-title">
                        Secure Class Teacher Access
                      </h5>

                      <p class="quick-panel-text">
                        You are currently signed in as a class teacher. This dashboard displays only the class and class arm assigned to your account, helping keep nursery attendance monitoring organized and protected.
                      </p>
                    </div>
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
      <?php include 'Includes/footer.php'; ?>
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