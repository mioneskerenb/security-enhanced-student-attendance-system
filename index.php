<?php
/*
|--------------------------------------------------------------------------
| SECURED INDEX LOGIN PAGE WITH PROGRESSIVE RATE LIMITING
|--------------------------------------------------------------------------
| Features:
| 1. No role dropdown.
| 2. Automatically checks tbladmin first, then tblclassteacher.
| 3. Uses prepared statements to prevent SQL Injection.
| 4. Supports old MD5 passwords and new password_hash passwords.
| 5. Uses session_regenerate_id(true) after successful login.
| 6. Progressive rate limiting:
|    - 3 failed attempts = 15 seconds lock
|    - next 3 failed attempts = 2 minutes lock
|    - next 3 failed attempts = 15 minutes lock
|    - next 3 failed attempts = 2 hours lock
|    - next 3 failed attempts = 24 hours lock
|    - next locks = 24 hours again
| 7. Professional and non-duplicated alert messages.
|--------------------------------------------------------------------------
*/

// TEMPORARY: show errors while debugging.
// After everything works, you may comment these two lines.
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'Includes/dbcon.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$statusMsg = "";

/*
|--------------------------------------------------------------------------
| PROFESSIONAL ALERT MESSAGE FUNCTION
|--------------------------------------------------------------------------
*/

function showAlert($type, $title, $message)
{
    return "
    <div class='alert alert-{$type} alert-dismissible fade show mt-3 custom-alert' role='alert'>
        <strong>{$title}</strong><br>
        <span>{$message}</span>
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
            <span aria-hidden='true'>&times;</span>
        </button>
    </div>";
}

/*
|--------------------------------------------------------------------------
| PASSWORD CHECK FUNCTION
|--------------------------------------------------------------------------
| Supports:
| 1. password_hash() / password_verify()
| 2. old MD5 passwords
| 3. uppercase or lowercase MD5 values from database
|--------------------------------------------------------------------------
*/

function checkPassword($plainPassword, $storedPassword)
{
    $storedPassword = trim($storedPassword);

    if (password_verify($plainPassword, $storedPassword)) {
        return true;
    }

    if (strtolower(md5($plainPassword)) === strtolower($storedPassword)) {
        return true;
    }

    return false;
}

/*
|--------------------------------------------------------------------------
| GET CLIENT IP ADDRESS
|--------------------------------------------------------------------------
*/

function getClientIp()
{
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/*
|--------------------------------------------------------------------------
| FORMAT REMAINING LOCK TIME
|--------------------------------------------------------------------------
*/

function formatRemainingTime($seconds)
{
    if ($seconds <= 0) {
        return "a few seconds";
    }

    if ($seconds < 60) {
        return $seconds . " second" . ($seconds == 1 ? "" : "s");
    }

    if ($seconds < 3600) {
        $minutes = ceil($seconds / 60);
        return $minutes . " minute" . ($minutes == 1 ? "" : "s");
    }

    if ($seconds < 86400) {
        $hours = ceil($seconds / 3600);
        return $hours . " hour" . ($hours == 1 ? "" : "s");
    }

    $days = ceil($seconds / 86400);
    return $days . " day" . ($days == 1 ? "" : "s");
}

/*
|--------------------------------------------------------------------------
| GET LOCK DURATION BY LEVEL
|--------------------------------------------------------------------------
*/

function getLockDurationSeconds($lockLevel)
{
    $durations = [
        0 => 15,       // 15 seconds
        1 => 120,      // 2 minutes
        2 => 900,      // 15 minutes
        3 => 7200,     // 2 hours
        4 => 86400     // 24 hours
    ];

    if ($lockLevel >= 4) {
        return 86400; // 24 hours again and again
    }

    return $durations[$lockLevel];
}

/*
|--------------------------------------------------------------------------
| GET LOGIN LOCK MESSAGE
|--------------------------------------------------------------------------
| Returns professional lock message if user is still locked.
|--------------------------------------------------------------------------
*/

function getLoginLockMessage($conn, $emailAddress, $ipAddress)
{
    $query = "SELECT lockedUntil 
              FROM login_attempts 
              WHERE emailAddress = ? AND ipAddress = ? 
              LIMIT 1";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        error_log("Rate limit check prepare failed: " . $conn->error);
        return "";
    }

    $stmt->bind_param("ss", $emailAddress, $ipAddress);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if (!empty($row['lockedUntil'])) {
            $lockedUntilTime = strtotime($row['lockedUntil']);

            if ($lockedUntilTime > time()) {
                $remainingSeconds = $lockedUntilTime - time();
                $remainingText = formatRemainingTime($remainingSeconds);

                $stmt->close();

                return showAlert(
                    "danger",
                    "Too Many Login Attempts",
                    "For security reasons, login access is temporarily locked. Please try again in {$remainingText}."
                );
            }
        }
    }

    $stmt->close();
    return "";
}

/*
|--------------------------------------------------------------------------
| RECORD FAILED LOGIN ATTEMPT
|--------------------------------------------------------------------------
| Every 3 failed attempts, the lock level increases.
|--------------------------------------------------------------------------
*/

function recordFailedLogin($conn, $emailAddress, $ipAddress)
{
    $maxAttempts = 3;

    $query = "SELECT Id, attempts, lockLevel 
              FROM login_attempts 
              WHERE emailAddress = ? AND ipAddress = ? 
              LIMIT 1";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        error_log("Record failed login prepare failed: " . $conn->error);
        return;
    }

    $stmt->bind_param("ss", $emailAddress, $ipAddress);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        $newAttempts = (int)$row['attempts'] + 1;
        $currentLockLevel = (int)$row['lockLevel'];

        if ($newAttempts >= $maxAttempts) {
            $lockDuration = getLockDurationSeconds($currentLockLevel);
            $lockedUntil = date("Y-m-d H:i:s", time() + $lockDuration);
            $nextLockLevel = $currentLockLevel + 1;

            $updateQuery = "UPDATE login_attempts 
                            SET attempts = 0, 
                                lastAttempt = NOW(), 
                                lockedUntil = ?, 
                                lockLevel = ?
                            WHERE Id = ?";

            $updateStmt = $conn->prepare($updateQuery);

            if ($updateStmt) {
                $updateStmt->bind_param("sii", $lockedUntil, $nextLockLevel, $row['Id']);
                $updateStmt->execute();
                $updateStmt->close();
            }
        } else {
            $updateQuery = "UPDATE login_attempts 
                            SET attempts = ?, 
                                lastAttempt = NOW()
                            WHERE Id = ?";

            $updateStmt = $conn->prepare($updateQuery);

            if ($updateStmt) {
                $updateStmt->bind_param("ii", $newAttempts, $row['Id']);
                $updateStmt->execute();
                $updateStmt->close();
            }
        }
    } else {
        $insertQuery = "INSERT INTO login_attempts 
                        (emailAddress, ipAddress, attempts, lastAttempt, lockedUntil, lockLevel) 
                        VALUES (?, ?, 1, NOW(), NULL, 0)";

        $insertStmt = $conn->prepare($insertQuery);

        if ($insertStmt) {
            $insertStmt->bind_param("ss", $emailAddress, $ipAddress);
            $insertStmt->execute();
            $insertStmt->close();
        }
    }

    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| CLEAR FAILED ATTEMPTS AFTER SUCCESSFUL LOGIN
|--------------------------------------------------------------------------
| This clears attempts when login succeeds.
|--------------------------------------------------------------------------
*/

function clearFailedLogins($conn, $emailAddress, $ipAddress)
{
    $query = "DELETE FROM login_attempts 
              WHERE emailAddress = ? AND ipAddress = ?";

    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param("ss", $emailAddress, $ipAddress);
        $stmt->execute();
        $stmt->close();
    }
}

/*
|--------------------------------------------------------------------------
| LOGIN PROCESS
|--------------------------------------------------------------------------
*/

if (isset($_POST['login'])) {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $ipAddress = getClientIp();

    if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $statusMsg = showAlert(
            "warning",
            "Invalid Email Address",
            "Please enter a valid email address before signing in."
        );
    } 
    else if ($password == "") {
        $statusMsg = showAlert(
            "warning",
            "Password Required",
            "Please enter your password to continue."
        );
    } 
    else if (getLoginLockMessage($conn, $username, $ipAddress) != "") {
        $statusMsg = getLoginLockMessage($conn, $username, $ipAddress);
    }
    else {

        /*
        |--------------------------------------------------------------------------
        | CHECK ADMIN ACCOUNT FIRST
        |--------------------------------------------------------------------------
        */

        $query = "SELECT * FROM tbladmin WHERE emailAddress = ? LIMIT 1";
        $stmt = $conn->prepare($query);

        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();

            $rs = $stmt->get_result();

            if ($rs && $rs->num_rows > 0) {
                $rows = $rs->fetch_assoc();

                if (checkPassword($password, $rows['password'])) {

                    session_regenerate_id(true);

                    $_SESSION['userId'] = $rows['Id'];
                    $_SESSION['firstName'] = $rows['firstName'];
                    $_SESSION['lastName'] = $rows['lastName'];
                    $_SESSION['emailAddress'] = $rows['emailAddress'];
                    $_SESSION['userType'] = "Administrator";
                    $_SESSION['LAST_ACTIVITY'] = time();
                    $_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];

                    clearFailedLogins($conn, $username, $ipAddress);

                    $stmt->close();

                    header("Location: Admin/index.php");
                    exit();
                }
            }

            $stmt->close();
        } else {
            error_log("Admin login prepare failed: " . $conn->error);
        }

        /*
        |--------------------------------------------------------------------------
        | IF NOT ADMIN, CHECK CLASS TEACHER ACCOUNT
        |--------------------------------------------------------------------------
        */

        $query = "SELECT * FROM tblclassteacher WHERE emailAddress = ? LIMIT 1";
        $stmt = $conn->prepare($query);

        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();

            $rs = $stmt->get_result();

            if ($rs && $rs->num_rows > 0) {
                $rows = $rs->fetch_assoc();

                if (checkPassword($password, $rows['password'])) {

                    session_regenerate_id(true);

                    $_SESSION['userId'] = $rows['Id'];
                    $_SESSION['firstName'] = $rows['firstName'];
                    $_SESSION['lastName'] = $rows['lastName'];
                    $_SESSION['emailAddress'] = $rows['emailAddress'];
                    $_SESSION['classId'] = $rows['classId'];
                    $_SESSION['classArmId'] = $rows['classArmId'];
                    $_SESSION['userType'] = "ClassTeacher";
                    $_SESSION['LAST_ACTIVITY'] = time();
                    $_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];

                    clearFailedLogins($conn, $username, $ipAddress);

                    $stmt->close();

                    header("Location: ClassTeacher/index.php");
                    exit();
                }
            }

            $stmt->close();
        } else {
            error_log("ClassTeacher login prepare failed: " . $conn->error);
        }

        /*
        |--------------------------------------------------------------------------
        | FAILED LOGIN
        |--------------------------------------------------------------------------
        */

        recordFailedLogin($conn, $username, $ipAddress);

        $statusMsg = showAlert(
            "danger",
            "Login Failed",
            "The email address or password you entered is incorrect. Please try again."
        );
    }
}

/*
|--------------------------------------------------------------------------
| REDIRECT / SESSION MESSAGES
|--------------------------------------------------------------------------
| This prevents duplicate messages.
|--------------------------------------------------------------------------
*/

if ($statusMsg == "" && isset($_GET['message'])) {

    if ($_GET['message'] == "session_expired") {
        $statusMsg = showAlert(
            "warning",
            "Session Expired",
            "Your session has expired due to inactivity. Please sign in again to continue."
        );
    } 
    else if ($_GET['message'] == "logged_out") {
        $statusMsg = showAlert(
            "success",
            "Logged Out Successfully",
            "You have been securely logged out of the system."
        );
    } 
    else if ($_GET['message'] == "unauthorized") {
        $statusMsg = showAlert(
            "danger",
            "Access Denied",
            "You do not have permission to access that page. Please sign in using the correct account."
        );
    } 
    else if ($_GET['message'] == "session_invalid") {
        $statusMsg = showAlert(
            "danger",
            "Invalid Session",
            "Your session could not be verified. Please sign in again."
        );
    }
    else if ($_GET['message'] == "invalid_session") {
        $statusMsg = showAlert(
            "danger",
            "Session Error",
            "Your session is invalid or incomplete. Please sign in again."
        );
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <meta name="description" content="Nursery Student Attendance System Login">
    <meta name="author" content="">

    <link href="img/logo/attnlg.jpg" rel="icon">

    <title>Nursery Attendance System - Login</title>

    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            font-family: "Nunito", "Segoe UI", Arial, sans-serif;
            background:
                linear-gradient(135deg, rgba(8, 76, 97, 0.82), rgba(25, 135, 84, 0.72)),
                url('img/logo/nursery-attendance-bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, 0.22), transparent 30%),
                radial-gradient(circle at bottom right, rgba(255, 225, 130, 0.18), transparent 35%);
            pointer-events: none;
            z-index: 0;
        }

        .page-wrapper {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 35px 18px;
        }

        .login-shell {
            width: 100%;
            max-width: 1080px;
            min-height: 620px;
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            border-radius: 28px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.30);
            backdrop-filter: blur(14px);
        }

        .login-info-panel {
            position: relative;
            padding: 48px;
            color: #ffffff;
            background:
                linear-gradient(145deg, rgba(0, 121, 107, 0.93), rgba(43, 158, 122, 0.88)),
                url('img/logo/nursery-attendance-bg.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .login-info-panel::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.08), rgba(0, 0, 0, 0.24));
            pointer-events: none;
        }

        .info-content,
        .info-footer {
            position: relative;
            z-index: 1;
        }

        .system-badge {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 9px 15px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.30);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.4px;
            margin-bottom: 28px;
        }

        .system-badge i {
            color: #ffe08a;
        }

        .login-info-panel h1 {
            font-size: 42px;
            line-height: 1.12;
            font-weight: 800;
            margin-bottom: 18px;
            color: #ffffff;
        }

        .login-info-panel p {
            font-size: 16px;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.92);
            margin-bottom: 28px;
            max-width: 430px;
        }

        .feature-list {
            display: grid;
            gap: 14px;
            margin-top: 25px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 15px;
            font-weight: 650;
            color: #ffffff;
        }

        .feature-icon {
            width: 36px;
            height: 36px;
            border-radius: 13px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.25);
        }

        .info-footer {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.82);
        }

        .login-form-panel {
            padding: 48px 46px;
            background: rgba(255, 255, 255, 0.96);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-form-card {
            width: 100%;
            max-width: 420px;
        }

        .logo-area {
            text-align: center;
            margin-bottom: 28px;
        }

        .logo-frame {
            width: 104px;
            height: 104px;
            margin: 0 auto 18px;
            border-radius: 28px;
            padding: 10px;
            background: linear-gradient(145deg, #e8fff5, #ffffff);
            box-shadow: 0 14px 30px rgba(21, 128, 61, 0.15);
            border: 1px solid rgba(20, 184, 166, 0.20);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-frame img {
            width: 78px;
            height: 78px;
            object-fit: cover;
            border-radius: 20px;
        }

        .login-title {
            font-size: 25px;
            font-weight: 800;
            color: #183b35;
            margin-bottom: 7px;
        }

        .login-subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 0;
        }

        .section-label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #0f766e;
            font-weight: 800;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            margin-bottom: 18px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #14a38b;
            font-size: 15px;
            z-index: 2;
        }

        .input-wrapper .form-control {
            height: 52px;
            border-radius: 16px;
            border: 1px solid #dbe7e3;
            background: #f8fbfa;
            color: #1f2937;
            font-size: 14px;
            padding-left: 48px;
            padding-right: 18px;
            transition: all 0.25s ease;
        }

        .input-wrapper .form-control:focus {
            border-color: #13a386;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(19, 163, 134, 0.12);
        }

        .input-wrapper .form-control::placeholder {
            color: #9ca3af;
        }

        .btn-login {
            height: 52px;
            border: none;
            border-radius: 16px;
            background: linear-gradient(135deg, #0f766e, #22c55e);
            color: #ffffff;
            font-weight: 800;
            letter-spacing: 0.3px;
            box-shadow: 0 16px 30px rgba(34, 197, 94, 0.25);
            transition: all 0.25s ease;
        }

        .btn-login:hover {
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 20px 35px rgba(34, 197, 94, 0.32);
            background: linear-gradient(135deg, #0d6f67, #16a34a);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .security-note {
            margin-top: 18px;
            padding: 14px 16px;
            border-radius: 16px;
            background: #f0fdf4;
            color: #166534;
            font-size: 13px;
            line-height: 1.6;
            border: 1px solid #bbf7d0;
            display: flex;
            gap: 11px;
            align-items: flex-start;
        }

        .security-note i {
            margin-top: 3px;
            color: #16a34a;
        }

        .custom-alert {
            border-radius: 16px !important;
            text-align: left;
            border: none;
            font-size: 14px;
            line-height: 1.5;
            box-shadow: 0 12px 25px rgba(15, 23, 42, 0.08);
        }

        .custom-alert .close {
            outline: none;
        }

        .mobile-info {
            display: none;
            text-align: center;
            margin-bottom: 22px;
            padding: 14px;
            border-radius: 18px;
            background: linear-gradient(135deg, #ecfdf5, #eff6ff);
            border: 1px solid #d1fae5;
            color: #0f766e;
            font-size: 13px;
            font-weight: 700;
        }

        @media (max-width: 991px) {
            .login-shell {
                grid-template-columns: 1fr;
                max-width: 520px;
                min-height: auto;
            }

            .login-info-panel {
                display: none;
            }

            .login-form-panel {
                padding: 36px 26px;
            }

            .mobile-info {
                display: block;
            }
        }

        @media (max-width: 480px) {
            .page-wrapper {
                padding: 18px 12px;
            }

            .login-form-panel {
                padding: 30px 20px;
            }

            .login-title {
                font-size: 22px;
            }

            .logo-frame {
                width: 92px;
                height: 92px;
                border-radius: 24px;
            }

            .logo-frame img {
                width: 68px;
                height: 68px;
            }
        }
    </style>

</head>

<body>

    <div class="page-wrapper">

        <div class="login-shell">

            <!-- Left Design / Nursery Attendance Information Panel -->
            <div class="login-info-panel">

                <div class="info-content">
                    <div class="system-badge">
                        <i class="fas fa-child"></i>
                        Nursery Attendance Management
                    </div>

                    <h1>Secure and Organized Attendance Monitoring</h1>

                    <p>
                        A professional attendance system designed for nursery class monitoring,
                        teacher access, administrative control, and secure login protection.
                    </p>

                    <div class="feature-list">
                        <div class="feature-item">
                            <span class="feature-icon">
                                <i class="fas fa-user-shield"></i>
                            </span>
                            Secure admin and class teacher access
                        </div>

                        <div class="feature-item">
                            <span class="feature-icon">
                                <i class="fas fa-clipboard-check"></i>
                            </span>
                            Reliable nursery attendance tracking
                        </div>

                        <div class="feature-item">
                            <span class="feature-icon">
                                <i class="fas fa-lock"></i>
                            </span>
                            Protected with progressive login security
                        </div>
                    </div>
                </div>

                <div class="info-footer">
                    <i class="fas fa-shield-alt mr-1"></i>
                    Authorized personnel only. All login attempts are monitored for security.
                </div>

            </div>

            <!-- Right Login Form Panel -->
            <div class="login-form-panel">

                <div class="login-form-card">

                    <div class="mobile-info">
                        <i class="fas fa-school mr-1"></i>
                        Nursery Attendance Management System
                    </div>

                    <div class="logo-area">
                        <div class="logo-frame">
                            <img src="img/logo/attnlg.jpg" alt="System Logo">
                        </div>

                        <h1 class="login-title">Welcome Back</h1>
                        <p class="login-subtitle">
                            Sign in to continue to the attendance system.
                        </p>
                    </div>

                    <div class="section-label">
                        <i class="fas fa-sign-in-alt"></i>
                        Login Panel
                    </div>

                    <form class="user" method="post" action="">

                        <div class="form-group">
                            <div class="input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input 
                                    type="email" 
                                    class="form-control" 
                                    required 
                                    name="username" 
                                    id="exampleInputEmail" 
                                    placeholder="Enter Email Address"
                                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-wrapper">
                                <i class="fas fa-key"></i>
                                <input 
                                    type="password" 
                                    name="password" 
                                    required 
                                    class="form-control" 
                                    id="exampleInputPassword" 
                                    placeholder="Enter Password">
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <input 
                                type="submit" 
                                class="btn btn-login btn-block" 
                                value="Login Securely" 
                                name="login">
                        </div>

                    </form>

                    <div class="security-note">
                        <i class="fas fa-info-circle"></i>
                        <span>
                            Please use your registered administrator or class teacher account.
                            For protection, repeated failed login attempts will be temporarily locked.
                        </span>
                    </div>

                    <!-- Professional message appears here only once -->
                    <?php echo $statusMsg; ?>

                </div>

            </div>

        </div>

    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/ruang-admin.min.js"></script>

</body>

</html>