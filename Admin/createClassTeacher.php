<?php 
/*
|--------------------------------------------------------------------------
| SECURED CREATE CLASS TEACHER PAGE
|--------------------------------------------------------------------------
| Security improvements:
| 1. Admin role protection added using requireRole("Administrator").
| 2. error_reporting(0) removed because it hides problems during development.
| 3. Inputs are validated and cleaned.
| 4. SQL Injection is prevented using prepared statements.
| 5. Password is stored using password_hash() instead of md5().
| 6. Output is escaped using htmlspecialchars() to reduce XSS risk.
| 7. Delete and update actions use prepared statements.
|--------------------------------------------------------------------------
*/

include '../Includes/dbcon.php';
include '../Includes/session.php';

requireRole("Administrator");

$statusMsg = "";
$row = [
    'firstName' => '',
    'lastName' => '',
    'emailAddress' => '',
    'phoneNo' => '',
    'classId' => '',
    'classArmId' => ''
];

function cleanInput($data)
{
    return trim($data);
}

function escapeOutput($data)
{
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

// ------------------------ SAVE CLASS TEACHER ------------------------

if (isset($_POST['save'])) {

    $firstName    = cleanInput($_POST['firstName']);
    $lastName     = cleanInput($_POST['lastName']);
    $emailAddress = cleanInput($_POST['emailAddress']);
    $phoneNo      = cleanInput($_POST['phoneNo']);
    $classId      = (int) $_POST['classId'];
    $classArmId   = (int) $_POST['classArmId'];
    $dateCreated  = date("Y-m-d");

    if ($firstName == "" || $lastName == "" || $emailAddress == "" || $classId <= 0 || $classArmId <= 0) {
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
    else {

        // Check if email already exists
        $checkQuery = "SELECT Id FROM tblclassteacher WHERE emailAddress = ? LIMIT 1";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("s", $emailAddress);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                            <i class='fas fa-exclamation-circle mr-2'></i>
                            This Email Address Already Exists!
                          </div>";
        } 
        else {

            /*
            |--------------------------------------------------------------------------
            | DEFAULT PASSWORD
            |--------------------------------------------------------------------------
            | Old code used:
            | $sampPass_2 = md5("pass123");
            |
            | New code uses password_hash() for stronger password storage.
            |--------------------------------------------------------------------------
            */

            $defaultPassword = "pass123";
            $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

            $insertQuery = "INSERT INTO tblclassteacher
                (firstName, lastName, emailAddress, password, phoneNo, classId, classArmId, dateCreated)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param(
                "sssssiis",
                $firstName,
                $lastName,
                $emailAddress,
                $hashedPassword,
                $phoneNo,
                $classId,
                $classArmId,
                $dateCreated
            );

            if ($insertStmt->execute()) {

                // Mark class arm as assigned
                $updateArmQuery = "UPDATE tblclassarms SET isAssigned = '1' WHERE Id = ?";
                $updateStmt = $conn->prepare($updateArmQuery);
                $updateStmt->bind_param("i", $classArmId);

                if ($updateStmt->execute()) {
                    $statusMsg = "<div class='alert alert-success shadow-sm' role='alert'>
                                    <i class='fas fa-check-circle mr-2'></i>
                                    Created Successfully! Default password: <strong>pass123</strong>
                                  </div>";
                } else {
                    error_log("Class arm update failed: " . $conn->error);
                    $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                                    <i class='fas fa-exclamation-circle mr-2'></i>
                                    Class teacher created, but class arm assignment failed.
                                  </div>";
                }

                $updateStmt->close();
            } 
            else {
                error_log("Class teacher insert failed: " . $conn->error);
                $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                                <i class='fas fa-exclamation-circle mr-2'></i>
                                An error occurred while creating class teacher.
                              </div>";
            }

            $insertStmt->close();
        }

        $stmt->close();
    }
}

// ------------------------ EDIT: GET CURRENT DATA ------------------------

if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "edit") {

    $Id = (int) $_GET['Id'];

    $selectQuery = "SELECT * FROM tblclassteacher WHERE Id = ? LIMIT 1";
    $stmt = $conn->prepare($selectQuery);
    $stmt->bind_param("i", $Id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
    }

    $stmt->close();

    // ------------------------ UPDATE CLASS TEACHER ------------------------

    if (isset($_POST['update'])) {

        $firstName    = cleanInput($_POST['firstName']);
        $lastName     = cleanInput($_POST['lastName']);
        $emailAddress = cleanInput($_POST['emailAddress']);
        $phoneNo      = cleanInput($_POST['phoneNo']);
        $classId      = (int) $_POST['classId'];
        $classArmId   = (int) $_POST['classArmId'];

        if ($firstName == "" || $lastName == "" || $emailAddress == "" || $classId <= 0 || $classArmId <= 0) {
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
        else {

            $updateQuery = "UPDATE tblclassteacher 
                            SET firstName = ?, lastName = ?, emailAddress = ?, phoneNo = ?, classId = ?, classArmId = ?
                            WHERE Id = ?";

            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param(
                "ssssiii",
                $firstName,
                $lastName,
                $emailAddress,
                $phoneNo,
                $classId,
                $classArmId,
                $Id
            );

            if ($stmt->execute()) {
                header("Location: createClassTeacher.php?message=updated");
                exit();
            } 
            else {
                error_log("Class teacher update failed: " . $conn->error);
                $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                                <i class='fas fa-exclamation-circle mr-2'></i>
                                An error occurred while updating class teacher.
                              </div>";
            }

            $stmt->close();
        }
    }
}

// ------------------------ DELETE CLASS TEACHER ------------------------

if (isset($_GET['Id']) && isset($_GET['classArmId']) && isset($_GET['action']) && $_GET['action'] == "delete") {

    $Id = (int) $_GET['Id'];
    $classArmId = (int) $_GET['classArmId'];

    $deleteQuery = "DELETE FROM tblclassteacher WHERE Id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $Id);

    if ($stmt->execute()) {

        $updateArmQuery = "UPDATE tblclassarms SET isAssigned = '0' WHERE Id = ?";
        $updateStmt = $conn->prepare($updateArmQuery);
        $updateStmt->bind_param("i", $classArmId);

        if ($updateStmt->execute()) {
            header("Location: createClassTeacher.php?message=deleted");
            exit();
        } 
        else {
            error_log("Class arm unassign failed: " . $conn->error);
            $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                            <i class='fas fa-exclamation-circle mr-2'></i>
                            Teacher deleted, but class arm update failed.
                          </div>";
        }

        $updateStmt->close();
    } 
    else {
        error_log("Class teacher delete failed: " . $conn->error);
        $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                        <i class='fas fa-exclamation-circle mr-2'></i>
                        An error occurred while deleting class teacher.
                      </div>";
    }

    $stmt->close();
}

// ------------------------ URL MESSAGE ------------------------

if (isset($_GET['message'])) {
    if ($_GET['message'] == "updated") {
        $statusMsg = "<div class='alert alert-success shadow-sm' role='alert'>
                        <i class='fas fa-check-circle mr-2'></i>
                        Updated Successfully!
                      </div>";
    } 
    else if ($_GET['message'] == "deleted") {
        $statusMsg = "<div class='alert alert-success shadow-sm' role='alert'>
                        <i class='fas fa-check-circle mr-2'></i>
                        Deleted Successfully!
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

  <?php include 'includes/title.php';?>

  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="css/ruang-admin.min.css" rel="stylesheet">

  <script>
    function classArmDropdown(str) {
      if (str == "") {
        document.getElementById("txtHint").innerHTML = "";
        return;
      } else { 
        let xmlhttp;

        if (window.XMLHttpRequest) {
          xmlhttp = new XMLHttpRequest();
        } else {
          xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }

        xmlhttp.onreadystatechange = function() {
          if (this.readyState == 4 && this.status == 200) {
            document.getElementById("txtHint").innerHTML = this.responseText;
          }
        };

        xmlhttp.open("GET", "ajaxClassArms.php?cid=" + encodeURIComponent(str), true);
        xmlhttp.send();
      }
    }
  </script>
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
                Create Class Teachers
              </h1>

              <p class="mb-0 text-muted">
                Add, update, and manage class teacher accounts for nursery classes.
              </p>
            </div>

            <ol class="breadcrumb bg-white shadow-sm border">
              <li class="breadcrumb-item">
                <a href="./" class="text-success">Home</a>
              </li>
              <li class="breadcrumb-item active" aria-current="page">
                Create Class Teachers
              </li>
            </ol>
          </div>

          <!-- Header Card -->
          <div class="card bg-success text-white shadow mb-4 border-0">
            <div class="card-body py-4">
              <div class="row align-items-center">
                <div class="col-lg-8">
                  <span class="badge badge-light text-success mb-3 px-3 py-2">
                    <i class="fas fa-chalkboard-teacher mr-1"></i>
                    Nursery Teacher Management
                  </span>

                  <h3 class="font-weight-bold mb-2">
                    Manage Class Teacher Accounts
                  </h3>

                  <p class="mb-0">
                    Create teacher accounts, assign classes and class arms, and manage existing teacher records.
                  </p>
                </div>

                <div class="col-lg-4 text-lg-right mt-4 mt-lg-0">
                  <div class="bg-white text-success rounded shadow-sm p-3 d-inline-block">
                    <i class="fas fa-user-tie fa-2x mb-2"></i>
                    <div class="font-weight-bold">
                      Teacher Setup
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
                    <i class="fas fa-user-plus mr-2"></i>
                    <?php echo isset($Id) ? "Update Class Teacher" : "Create Class Teacher"; ?>
                  </h6>

                  <span class="badge badge-success px-3 py-2">
                    <i class="fas fa-edit mr-1"></i>
                    Teacher Form
                  </span>
                </div>

                <div class="card-body">

                  <?php echo $statusMsg; ?>

                  <form method="post">

                    <div class="form-group row mb-3">

                      <div class="col-xl-6">
                        <label class="form-control-label font-weight-bold">
                          Firstname<span class="text-danger ml-2">*</span>
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
                            placeholder="Enter first name"
                            value="<?php echo escapeOutput($row['firstName']); ?>">
                        </div>
                      </div>

                      <div class="col-xl-6">
                        <label class="form-control-label font-weight-bold">
                          Lastname<span class="text-danger ml-2">*</span>
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
                            placeholder="Enter last name"
                            value="<?php echo escapeOutput($row['lastName']); ?>">
                        </div>
                      </div>

                    </div>

                    <div class="form-group row mb-3">

                      <div class="col-xl-6">
                        <label class="form-control-label font-weight-bold">
                          Email Address<span class="text-danger ml-2">*</span>
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
                            placeholder="Enter email address"
                            value="<?php echo escapeOutput($row['emailAddress']); ?>">
                        </div>
                      </div>

                      <div class="col-xl-6">
                        <label class="form-control-label font-weight-bold">
                          Phone No<span class="text-danger ml-2">*</span>
                        </label>

                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text bg-success text-white">
                              <i class="fas fa-phone"></i>
                            </span>
                          </div>

                          <input 
                            type="text" 
                            class="form-control" 
                            name="phoneNo" 
                            placeholder="Enter phone number"
                            value="<?php echo escapeOutput($row['phoneNo']); ?>">
                        </div>
                      </div>

                    </div>

                    <div class="form-group row mb-3">

                      <div class="col-xl-6">
                        <label class="form-control-label font-weight-bold">
                          Select Class<span class="text-danger ml-2">*</span>
                        </label>

                        <?php
                        $qry = "SELECT Id, className FROM tblclass ORDER BY className ASC";
                        $result = $conn->query($qry);

                        echo '<select required name="classId" onchange="classArmDropdown(this.value)" class="form-control mb-3">';
                        echo '<option value="">--Select Class--</option>';

                        if ($result && $result->num_rows > 0) {
                            while ($rows = $result->fetch_assoc()) {
                                $selected = ($row['classId'] == $rows['Id']) ? "selected" : "";
                                echo '<option value="' . escapeOutput($rows['Id']) . '" ' . $selected . '>' . escapeOutput($rows['className']) . '</option>';
                            }
                        }

                        echo '</select>';
                        ?>  
                      </div>

                      <div class="col-xl-6">
                        <label class="form-control-label font-weight-bold">
                          Class Arm<span class="text-danger ml-2">*</span>
                        </label>

                        <div id="txtHint">
                          <?php
                          if (!empty($row['classArmId'])) {
                              echo "<div class='alert alert-info shadow-sm mb-0' role='alert'>
                                      <i class='fas fa-info-circle mr-2'></i>
                                      Current Class Arm ID: " . escapeOutput($row['classArmId']) . ". Please reselect if updating.
                                    </div>";
                          } else {
                              echo "<div class='alert alert-light border shadow-sm mb-0' role='alert'>
                                      <i class='fas fa-arrow-left mr-2 text-success'></i>
                                      Select a class to load class arms.
                                    </div>";
                          }
                          ?>
                        </div>
                      </div>

                    </div>

                    <?php if (isset($Id)) { ?>
                      <button type="submit" name="update" class="btn btn-warning shadow-sm px-4">
                        <i class="fas fa-save mr-1"></i>
                        Update
                      </button>

                      <a href="createClassTeacher.php" class="btn btn-secondary shadow-sm px-4 ml-2">
                        <i class="fas fa-times mr-1"></i>
                        Cancel
                      </a>
                    <?php } else { ?>
                      <button type="submit" name="save" class="btn btn-success shadow-sm px-4">
                        <i class="fas fa-save mr-1"></i>
                        Save
                      </button>
                    <?php } ?>

                  </form>

                </div>
              </div>

              <!-- Table Card -->
              <div class="row">
                <div class="col-lg-12">

                  <div class="card shadow mb-4 border-0">

                    <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between border-bottom">
                      <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-list mr-2"></i>
                        All Class Teachers
                      </h6>

                      <span class="badge badge-success px-3 py-2">
                        <i class="fas fa-table mr-1"></i>
                        Records
                      </span>
                    </div>

                    <div class="card-body">

                      <div class="alert alert-success border-left-success shadow-sm" role="alert">
                        <i class="fas fa-info-circle mr-2"></i>
                        This table displays all registered class teachers and their assigned classes.
                      </div>

                      <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped align-items-center" id="dataTableHover">
                          <thead class="thead-light">
                            <tr>
                              <th>#</th>
                              <th>First Name</th>
                              <th>Last Name</th>
                              <th>Email Address</th>
                              <th>Phone No</th>
                              <th>Class</th>
                              <th>Class Arm</th>
                              <th>Date Created</th>
                              <th>Edit</th>
                              <th>Delete</th>
                            </tr>
                          </thead>

                          <tbody>
                            <?php
                            $query = "SELECT 
                                        tblclassteacher.Id,
                                        tblclass.className,
                                        tblclassarms.classArmName,
                                        tblclassarms.Id AS classArmId,
                                        tblclassteacher.firstName,
                                        tblclassteacher.lastName,
                                        tblclassteacher.emailAddress,
                                        tblclassteacher.phoneNo,
                                        tblclassteacher.dateCreated
                                      FROM tblclassteacher
                                      INNER JOIN tblclass ON tblclass.Id = tblclassteacher.classId
                                      INNER JOIN tblclassarms ON tblclassarms.Id = tblclassteacher.classArmId
                                      ORDER BY tblclassteacher.Id DESC";

                            $rs = $conn->query($query);
                            $sn = 0;

                            if ($rs && $rs->num_rows > 0) { 
                                while ($rows = $rs->fetch_assoc()) {
                                    $sn++;

                                    echo "
                                    <tr>
                                      <td>
                                        <span class='badge badge-success'>" . escapeOutput($sn) . "</span>
                                      </td>

                                      <td>" . escapeOutput($rows['firstName']) . "</td>

                                      <td>" . escapeOutput($rows['lastName']) . "</td>

                                      <td>
                                        <span class='badge badge-primary px-3 py-2'>" . escapeOutput($rows['emailAddress']) . "</span>
                                      </td>

                                      <td>" . escapeOutput($rows['phoneNo']) . "</td>

                                      <td>
                                        <span class='badge badge-info px-3 py-2'>" . escapeOutput($rows['className']) . "</span>
                                      </td>

                                      <td>
                                        <span class='badge badge-success px-3 py-2'>" . escapeOutput($rows['classArmName']) . "</span>
                                      </td>

                                      <td>" . escapeOutput($rows['dateCreated']) . "</td>

                                      <td>
                                        <a href='?action=edit&Id=" . escapeOutput($rows['Id']) . "' class='btn btn-sm btn-warning shadow-sm'>
                                          <i class='fas fa-fw fa-edit'></i>
                                          Edit
                                        </a>
                                      </td>

                                      <td>
                                        <a href='?action=delete&Id=" . escapeOutput($rows['Id']) . "&classArmId=" . escapeOutput($rows['classArmId']) . "' 
                                           class='btn btn-sm btn-danger shadow-sm'
                                           onclick=\"return confirm('Are you sure you want to delete this class teacher?');\">
                                          <i class='fas fa-fw fa-trash'></i>
                                          Delete
                                        </a>
                                      </td>
                                    </tr>";
                                }
                            } else {
                                echo "
                                <tr>
                                  <td colspan='10'>
                                    <div class='alert alert-danger mb-0' role='alert'>
                                      <i class='fas fa-exclamation-circle mr-2'></i>
                                      No Record Found!
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

  <!-- Page level custom scripts -->
  <script>
    $(document).ready(function () {
      $('#dataTableHover').DataTable();
    });
  </script>

</body>
</html>