<?php
/*
|--------------------------------------------------------------------------
| CLASS TEACHER CHANGE PASSWORD PAGE
|--------------------------------------------------------------------------
| Purpose:
| Allows logged-in ClassTeacher to change their own password.
|
| Security improvements:
| 1. Only ClassTeacher can access this page.
| 2. Uses password_verify() and md5 fallback for old passwords.
| 3. Saves new password using password_hash().
| 4. Uses prepared statements to prevent SQL Injection.
| 5. Validates password length and confirmation.
|--------------------------------------------------------------------------
*/

include '../Includes/dbcon.php';
include '../Includes/session.php';

requireRole("ClassTeacher");

$statusMsg = "";

function escapeOutput($data)
{
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

function checkPassword($plainPassword, $storedPassword)
{
    // New secure password_hash()
    if (password_verify($plainPassword, $storedPassword)) {
        return true;
    }

    // Old MD5 support
    if (md5($plainPassword) === $storedPassword) {
        return true;
    }

    return false;
}

$userId = isset($_SESSION['userId']) ? (int) $_SESSION['userId'] : 0;

if ($userId <= 0) {
    session_unset();
    session_destroy();

    header("Location: ../index.php?message=invalid_session");
    exit();
}

/*
|--------------------------------------------------------------------------
| CHANGE PASSWORD PROCESS
|--------------------------------------------------------------------------
*/

if (isset($_POST['changePassword'])) {

    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if ($currentPassword == "" || $newPassword == "" || $confirmPassword == "") {
        $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                        <i class='fas fa-exclamation-circle mr-2'></i>
                        Please fill in all fields.
                      </div>";
    } 
    else if (strlen($newPassword) < 6) {
        $statusMsg = "<div class='alert alert-warning shadow-sm' role='alert'>
                        <i class='fas fa-info-circle mr-2'></i>
                        New password must be at least 6 characters.
                      </div>";
    } 
    else if ($newPassword !== $confirmPassword) {
        $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                        <i class='fas fa-exclamation-circle mr-2'></i>
                        New password and confirm password do not match.
                      </div>";
    } 
    else {

        // Get current stored password
        $query = "SELECT password FROM tblclassteacher WHERE Id = ? LIMIT 1";
        $stmt = $conn->prepare($query);

        if (!$stmt) {
            error_log("Change password select prepare failed: " . $conn->error);
            $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                            <i class='fas fa-exclamation-circle mr-2'></i>
                            An error occurred. Please contact administrator.
                          </div>";
        } 
        else {
            $stmt->bind_param("i", $userId);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {

                $row = $result->fetch_assoc();
                $storedPassword = $row['password'];

                if (!checkPassword($currentPassword, $storedPassword)) {
                    $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                                    <i class='fas fa-exclamation-circle mr-2'></i>
                                    Current password is incorrect.
                                  </div>";
                } 
                else {

                    // Save new password using password_hash()
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                    $updateQuery = "UPDATE tblclassteacher SET password = ? WHERE Id = ?";
                    $updateStmt = $conn->prepare($updateQuery);

                    if (!$updateStmt) {
                        error_log("Change password update prepare failed: " . $conn->error);
                        $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                                        <i class='fas fa-exclamation-circle mr-2'></i>
                                        An error occurred while updating password.
                                      </div>";
                    } 
                    else {
                        $updateStmt->bind_param("si", $hashedPassword, $userId);

                        if ($updateStmt->execute()) {
                            $statusMsg = "<div class='alert alert-success shadow-sm' role='alert'>
                                            <i class='fas fa-check-circle mr-2'></i>
                                            Password changed successfully.
                                          </div>";
                        } 
                        else {
                            error_log("Change password update failed: " . $conn->error);
                            $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                                            <i class='fas fa-exclamation-circle mr-2'></i>
                                            Failed to change password.
                                          </div>";
                        }

                        $updateStmt->close();
                    }
                }
            } 
            else {
                $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                                <i class='fas fa-exclamation-circle mr-2'></i>
                                Teacher account not found.
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

  <link href="img/logo/attnlg.jpg" rel="icon">

  <title>Change Password</title>

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
                Change Password
              </h1>

              <p class="mb-0 text-muted">
                Update your class teacher account password securely.
              </p>
            </div>

            <ol class="breadcrumb bg-white shadow-sm border">
              <li class="breadcrumb-item">
                <a href="./" class="text-success">Home</a>
              </li>
              <li class="breadcrumb-item active" aria-current="page">
                Change Password
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
                    Account Security
                  </span>

                  <h3 class="font-weight-bold mb-2">
                    Secure Password Update
                  </h3>

                  <p class="mb-0">
                    Change your password regularly to keep your nursery attendance account protected.
                    Your new password will be saved using secure password hashing.
                  </p>
                </div>

                <div class="col-lg-4 text-lg-right mt-4 mt-lg-0">
                  <div class="bg-white text-success rounded shadow-sm p-3 d-inline-block">
                    <i class="fas fa-lock fa-2x mb-2"></i>
                    <div class="font-weight-bold">
                      Protected Account
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row">

            <div class="col-xl-7 col-lg-8">

              <div class="card shadow mb-4 border-0">

                <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between border-bottom">
                  <h6 class="m-0 font-weight-bold text-success">
                    <i class="fas fa-key mr-2"></i>
                    Change Your Password
                  </h6>

                  <span class="badge badge-success px-3 py-2">
                    <i class="fas fa-shield-alt mr-1"></i>
                    Secure Form
                  </span>
                </div>

                <div class="card-body">

                  <?php echo $statusMsg; ?>

                  <form method="post">

                    <div class="form-group">
                      <label class="font-weight-bold">
                        Current Password <span class="text-danger">*</span>
                      </label>

                      <div class="input-group">
                        <div class="input-group-prepend">
                          <span class="input-group-text bg-success text-white">
                            <i class="fas fa-lock"></i>
                          </span>
                        </div>

                        <input 
                          type="password" 
                          name="currentPassword" 
                          class="form-control" 
                          required 
                          placeholder="Enter current password">
                      </div>
                    </div>

                    <div class="form-group">
                      <label class="font-weight-bold">
                        New Password <span class="text-danger">*</span>
                      </label>

                      <div class="input-group">
                        <div class="input-group-prepend">
                          <span class="input-group-text bg-success text-white">
                            <i class="fas fa-key"></i>
                          </span>
                        </div>

                        <input 
                          type="password" 
                          name="newPassword" 
                          class="form-control" 
                          required 
                          placeholder="Enter new password">
                      </div>

                      <small class="text-muted">
                        <i class="fas fa-info-circle mr-1"></i>
                        Password must be at least 6 characters.
                      </small>
                    </div>

                    <div class="form-group">
                      <label class="font-weight-bold">
                        Confirm New Password <span class="text-danger">*</span>
                      </label>

                      <div class="input-group">
                        <div class="input-group-prepend">
                          <span class="input-group-text bg-success text-white">
                            <i class="fas fa-check-circle"></i>
                          </span>
                        </div>

                        <input 
                          type="password" 
                          name="confirmPassword" 
                          class="form-control" 
                          required 
                          placeholder="Confirm new password">
                      </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                      <button type="submit" name="changePassword" class="btn btn-success shadow-sm px-4">
                        <i class="fas fa-save mr-1"></i>
                        Change Password
                      </button>
                    </div>

                  </form>

                </div>
              </div>

            </div>

            <div class="col-xl-5 col-lg-4">

              <div class="card shadow mb-4 border-0">
                <div class="card-header bg-white py-3 border-bottom">
                  <h6 class="m-0 font-weight-bold text-success">
                    <i class="fas fa-info-circle mr-2"></i>
                    Password Reminder
                  </h6>
                </div>

                <div class="card-body">

                  <div class="alert alert-success border-left-success shadow-sm" role="alert">
                    <i class="fas fa-check-circle mr-2"></i>
                    Use a password that is easy for you to remember but difficult for others to guess.
                  </div>

                  <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0">
                      <i class="fas fa-check text-success mr-2"></i>
                      Minimum of 6 characters.
                    </li>

                    <li class="list-group-item px-0">
                      <i class="fas fa-check text-success mr-2"></i>
                      Avoid using your name as password.
                    </li>

                    <li class="list-group-item px-0">
                      <i class="fas fa-check text-success mr-2"></i>
                      Keep your account credentials private.
                    </li>
                  </ul>

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

</body>
</html>