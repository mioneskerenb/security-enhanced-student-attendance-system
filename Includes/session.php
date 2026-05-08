<?php
/*
|--------------------------------------------------------------------------
| SECURED SESSION CODE
|--------------------------------------------------------------------------
| Purpose:
| Protects pages from unauthorized access.
|
| Security improvements:
| 1. Starts session safely.
| 2. Redirects users who are not logged in.
| 3. Adds 30-minute session timeout.
| 4. Adds basic browser validation.
| 5. Adds requireRole() function for Admin/ClassTeacher access control.
| 6. Adds database app context for database triggers.
|--------------------------------------------------------------------------
*/

// Start session safely only if no session is currently active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set session expiry time to 30 minutes
// 1800 seconds = 30 minutes
$expiry = 1800;

// Check if the user is logged in
// If userId is not set, it means the user has not logged in yet
if (!isset($_SESSION['userId'])) {
    header("Location: ../index.php");
    exit();
}

// Check if the user has been inactive for more than 30 minutes
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $expiry)) {
    session_unset();
    session_destroy();

    header("Location: ../index.php?message=session_expired");
    exit();
}

// Update last activity time
$_SESSION['LAST_ACTIVITY'] = time();

// Store browser information when session starts
if (!isset($_SESSION['USER_AGENT'])) {
    $_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
}

// If browser information changes, destroy session
elseif ($_SESSION['USER_AGENT'] !== $_SERVER['HTTP_USER_AGENT']) {
    session_unset();
    session_destroy();

    header("Location: ../index.php?message=session_invalid");
    exit();
}

/*
|--------------------------------------------------------------------------
| DATABASE APP CONTEXT
|--------------------------------------------------------------------------
| These MySQL session variables tell the database which authenticated
| user and role is using the system.
|
| Purpose:
| Database triggers can use these values to allow actions from the system
| and block manual database changes from phpMyAdmin.
|
| Example:
| @app_user_id = logged-in user ID
| @app_role    = Administrator or ClassTeacher
|--------------------------------------------------------------------------
*/

if (isset($conn) && isset($_SESSION['userId']) && isset($_SESSION['userType'])) {
    $appUserId = (int) $_SESSION['userId'];
    $appRole = $conn->real_escape_string($_SESSION['userType']);

    $conn->query("SET @app_user_id = {$appUserId}");
    $conn->query("SET @app_role = '{$appRole}'");
}

/*
|--------------------------------------------------------------------------
| ROLE-BASED ACCESS CONTROL FUNCTION
|--------------------------------------------------------------------------
| This function checks if the logged-in user has the correct role.
|
| Example:
| Admin pages:
| requireRole("Administrator");
|
| ClassTeacher pages:
| requireRole("ClassTeacher");
|--------------------------------------------------------------------------
*/

function requireRole($requiredRole)
{
    if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== $requiredRole) {
        session_unset();
        session_destroy();

        header("Location: ../index.php?message=unauthorized");
        exit();
    }
}
?>