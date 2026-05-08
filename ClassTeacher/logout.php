<?php
/*
|--------------------------------------------------------------------------
| OLD LOGOUT CODE
|--------------------------------------------------------------------------
| This was the original logout code.
| It only destroys the session and redirects using JavaScript.
|
| Security concerns:
| 1. It does not clear all session variables.
| 2. It does not delete the session cookie.
| 3. JavaScript redirect is not as clean as PHP header redirect.
|--------------------------------------------------------------------------
*/

// session_start(); 
// session_destroy();
// echo "<script type = \"text/javascript\">
// window.location = (\"../index.php\");
// </script>";


/*
|--------------------------------------------------------------------------
| SECURED LOGOUT CODE
|--------------------------------------------------------------------------
| Changes made:
| 1. session_unset() clears all session variables.
| 2. session_destroy() destroys the session data.
| 3. session cookie is deleted from the browser.
| 4. PHP header() redirect is used instead of JavaScript.
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
session_unset();

// Delete session cookie from browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: ../index.php?message=logged_out");
exit();
?>