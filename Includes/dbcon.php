<?php
/*
|--------------------------------------------------------------------------
| OLD DATABASE CONNECTION CODE
|--------------------------------------------------------------------------
| This was the original code.
| It connects to MySQL using root with empty password.
| It also displays the exact database error to the user.
|
| Security concerns:
| 1. Using root account is risky because it has full database privileges.
| 2. Empty password is not secure.
| 3. Displaying database errors can reveal sensitive system information.
|--------------------------------------------------------------------------
*/

// $host = "localhost";
// $user = "root";
// $pass = "";
// $db = "attendancemsystem";

// $conn = new mysqli($host, $user, $pass, $db);
// if($conn->connect_error){
//     echo "Seems like you have not configured the database. Failed To Connect to database:" . $conn->connect_error;
// }


/*
|--------------------------------------------------------------------------
| SECURED DATABASE CONNECTION CODE
|--------------------------------------------------------------------------
| Changes made:
| 1. mysqli_report() is disabled for public error display.
| 2. The connection uses cleaner error handling.
| 3. The real database error is saved in the server log using error_log().
| 4. The user only sees a generic error message.
|
| Note:
| For local XAMPP, root with empty password can still work.
| But for a more secure setup, create a separate MySQL user with limited access.
|--------------------------------------------------------------------------
*/

$host = "localhost";
$user = "root";
$pass = "";
$db   = "attendancemsystem_new";

// Prevent mysqli from showing detailed errors directly on the page
mysqli_report(MYSQLI_REPORT_OFF);

// Create database connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection safely
if ($conn->connect_error) {

    // Log the real error for developer/admin checking
    error_log("Database Connection Failed: " . $conn->connect_error);

    // Show generic error only to the user
    die("Database connection failed. Please contact the system administrator.");
}

// Set character encoding to support safe text handling
$conn->set_charset("utf8mb4");
?>