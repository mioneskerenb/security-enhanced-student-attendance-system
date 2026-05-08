<?php 
include '../Includes/dbcon.php';
include '../Includes/session.php';

requireRole("Administrator");

$statusMsg = "";

function cleanInput($data)
{
    return trim($data ?? '');
}

function escapeOutput($data)
{
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| SAVE NEW ADMIN
|--------------------------------------------------------------------------
*/

if (isset($_POST['save'])) {

    $firstName = cleanInput($_POST['firstName']);
    $lastName = cleanInput($_POST['lastName']);
    $emailAddress = cleanInput($_POST['emailAddress']);
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if ($firstName == "" || $lastName == "" || $emailAddress == "" || $password == "" || $confirmPassword == "") {
        $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                        <i class='fas fa-exclamation-circle mr-2'></i>
                        Please fill in all required fields.
                      </div>";
    } 
    else if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
        $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                        <i class='fas fa-exclamation-circle mr-2'></i>
                        Invalid email address.
                      </div>";
    } 
    else if ($password !== $confirmPassword) {
        $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                        <i class='fas fa-exclamation-circle mr-2'></i>
                        Passwords do not match.
                      </div>";
    } 
    else if (strlen($password) < 6) {
        $statusMsg = "<div class='alert alert-warning shadow-sm' role='alert'>
                        <i class='fas fa-info-circle mr-2'></i>
                        Password must be at least 6 characters.
                      </div>";
    } 
    else {

        $checkQuery = "SELECT Id FROM tbladmin WHERE emailAddress = ? LIMIT 1";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("s", $emailAddress);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                            <i class='fas fa-exclamation-circle mr-2'></i>
                            This admin email already exists.
                          </div>";
        } 
        else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $insertQuery = "INSERT INTO tbladmin (firstName, lastName, emailAddress, password)
                            VALUES (?, ?, ?, ?)";

            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("ssss", $firstName, $lastName, $emailAddress, $hashedPassword);

            if ($insertStmt->execute()) {
                $statusMsg = "<div class='alert alert-success shadow-sm' role='alert'>
                                <i class='fas fa-check-circle mr-2'></i>
                                Admin account created successfully.
                              </div>";
            } else {
                error_log("Admin insert failed: " . $conn->error);
                $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                                <i class='fas fa-exclamation-circle mr-2'></i>
                                An error occurred while creating admin.
                              </div>";
            }

            $insertStmt->close();
        }

        $stmt->close();
    }
}

/*
|--------------------------------------------------------------------------
| LOAD ADMINS
|--------------------------------------------------------------------------
*/

$admins = [];

$query = "SELECT Id, firstName, lastName, emailAddress FROM tbladmin ORDER BY Id DESC";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">

  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <link href="img/logo/attnlg.jpg" rel="icon">

  <?php include 'includes/title.php';?>

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

        <div class="container-fluid" id="container-wrapper">

          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
              <h1 class="h3 mb-0 text-gray-800 font-weight-bold">
                Create Admin
              </h1>

              <p class="mb-0 text-muted">
                Add and manage administrator accounts for the nursery attendance system.
              </p>
            </div>

            <ol class="breadcrumb bg-white shadow-sm border">
              <li class="breadcrumb-item">
                <a href="./" class="text-success">Home</a>
              </li>
              <li class="breadcrumb-item active" aria-current="page">
                Create Admin
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
                    Administrator Management
                  </span>

                  <h3 class="font-weight-bold mb-2">
                    Create Administrator Account
                  </h3>

                  <p class="mb-0">
                    Register new administrators who can manage nursery classes, class arms, teachers, students, sessions, and attendance records.
                  </p>
                </div>

                <div class="col-lg-4 text-lg-right mt-4 mt-lg-0">
                  <div class="bg-white text-success rounded shadow-sm p-3 d-inline-block">
                    <i class="fas fa-shield-alt fa-2x mb-2"></i>
                    <div class="font-weight-bold">
                      Secure Admin Access
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-lg-12">

              <!-- Create Admin Form Card -->
              <div class="card shadow mb-4 border-0">

                <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between border-bottom">
                  <h6 class="m-0 font-weight-bold text-success">
                    <i class="fas fa-user-plus mr-2"></i>
                    Create Admin Account
                  </h6>

                  <span class="badge badge-success px-3 py-2">
                    <i class="fas fa-edit mr-1"></i>
                    Admin Form
                  </span>
                </div>

                <div class="card-body">

                  <?php echo $statusMsg; ?>

                  <form method="post">

                    <div class="form-group row mb-3">

                      <div class="col-xl-6">
                        <label class="font-weight-bold">
                          First Name <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text bg-success text-white">
                              <i class="fas fa-user"></i>
                            </span>
                          </div>

                          <input 
                            type="text" 
                            class="form-control" 
                            required 
                            name="firstName"
                            placeholder="Enter first name">
                        </div>
                      </div>

                      <div class="col-xl-6">
                        <label class="font-weight-bold">
                          Last Name <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text bg-success text-white">
                              <i class="fas fa-user"></i>
                            </span>
                          </div>

                          <input 
                            type="text" 
                            class="form-control" 
                            required 
                            name="lastName"
                            placeholder="Enter last name">
                        </div>
                      </div>

                    </div>

                    <div class="form-group row mb-3">

                      <div class="col-xl-6">
                        <label class="font-weight-bold">
                          Email Address <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text bg-success text-white">
                              <i class="fas fa-envelope"></i>
                            </span>
                          </div>

                          <input 
                            type="email" 
                            class="form-control" 
                            required 
                            name="emailAddress"
                            placeholder="Enter email address">
                        </div>
                      </div>

                      <div class="col-xl-6">
                        <label class="font-weight-bold">
                          Password <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text bg-success text-white">
                              <i class="fas fa-lock"></i>
                            </span>
                          </div>

                          <input 
                            type="password" 
                            class="form-control" 
                            required 
                            name="password"
                            placeholder="Enter password">
                        </div>

                        <small class="text-muted">
                          <i class="fas fa-info-circle mr-1"></i>
                          Password must be at least 6 characters.
                        </small>
                      </div>

                    </div>

                    <div class="form-group row mb-3">

                      <div class="col-xl-6">
                        <label class="font-weight-bold">
                          Confirm Password <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text bg-success text-white">
                              <i class="fas fa-check-circle"></i>
                            </span>
                          </div>

                          <input 
                            type="password" 
                            class="form-control" 
                            required 
                            name="confirmPassword"
                            placeholder="Confirm password">
                        </div>
                      </div>

                    </div>

                    <button type="submit" name="save" class="btn btn-success shadow-sm px-4">
                      <i class="fas fa-save mr-1"></i>
                      Save Admin
                    </button>

                  </form>

                </div>
              </div>

              <!-- Admin Table Card -->
              <div class="card shadow mb-4 border-0">

                <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between border-bottom">
                  <h6 class="m-0 font-weight-bold text-success">
                    <i class="fas fa-list mr-2"></i>
                    All Admin Accounts
                  </h6>

                  <span class="badge badge-success px-3 py-2">
                    <i class="fas fa-table mr-1"></i>
                    Records
                  </span>
                </div>

                <div class="card-body">

                  <div class="alert alert-success border-left-success shadow-sm" role="alert">
                    <i class="fas fa-info-circle mr-2"></i>
                    This table displays all administrator accounts registered in the system.
                  </div>

                  <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped align-items-center" id="dataTableHover">
                      <thead class="thead-light">
                        <tr>
                          <th>#</th>
                          <th>First Name</th>
                          <th>Last Name</th>
                          <th>Email Address</th>
                        </tr>
                      </thead>

                      <tbody>
                        <?php
                        if (!empty($admins)) {
                            $sn = 0;

                            foreach ($admins as $admin) {
                                $sn++;

                                echo "
                                <tr>
                                  <td>
                                    <span class='badge badge-success'>" . escapeOutput($sn) . "</span>
                                  </td>

                                  <td>" . escapeOutput($admin['firstName']) . "</td>

                                  <td>" . escapeOutput($admin['lastName']) . "</td>

                                  <td>
                                    <span class='badge badge-primary px-3 py-2'>" . escapeOutput($admin['emailAddress']) . "</span>
                                  </td>
                                </tr>";
                            }
                        } else {
                            echo "
                            <tr>
                              <td colspan='4'>
                                <div class='alert alert-info mb-0' role='alert'>
                                  <i class='fas fa-info-circle mr-2'></i>
                                  No admin record found.
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

      <!-- Footer -->
      <?php include "Includes/footer.php";?>
      <!-- Footer -->

    </div>
  </div>

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