<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include "../Includes/dbcon.php";

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
$userType = isset($_POST['userType']) ? trim($_POST['userType']) : '';

if ($username == '' || $password == '' || $userType == '') {
    echo json_encode([
        "success" => false,
        "message" => "Username, password, and user type are required."
    ]);
    exit();
}

/*
|--------------------------------------------------------------------------
| ADMIN LOGIN
|--------------------------------------------------------------------------
| tbladmin columns:
| Id, firstName, lastName, emailAddress, password
|--------------------------------------------------------------------------
*/
if ($userType == "Administrator") {

    $email = mysqli_real_escape_string($conn, $username);
    $hashedPassword = md5($password);

    $query = "SELECT * FROM tbladmin 
              WHERE emailAddress = '$email' 
              AND password = '$hashedPassword'
              LIMIT 1";

    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $userId = $row['Id'];

        mysqli_query($conn, "DELETE FROM api_tokens 
                             WHERE user_id = '$userId' 
                             AND user_type = 'Administrator'");

        $token = bin2hex(random_bytes(32));

        mysqli_query($conn, "INSERT INTO api_tokens 
                             (user_id, user_type, token, created_at)
                             VALUES 
                             ('$userId', 'Administrator', '$token', NOW())");

        echo json_encode([
            "success" => true,
            "message" => "Login successful.",
            "token" => $token,
            "userType" => "Administrator",
            "data" => [
                "Id" => $row['Id'],
                "firstName" => $row['firstName'],
                "lastName" => $row['lastName'],
                "emailAddress" => $row['emailAddress']
            ]
        ]);
        exit();
    }

    echo json_encode([
        "success" => false,
        "message" => "Invalid administrator email or password."
    ]);
    exit();
}

/*
|--------------------------------------------------------------------------
| CLASS TEACHER LOGIN
|--------------------------------------------------------------------------
*/
if ($userType == "ClassTeacher") {

    $username = mysqli_real_escape_string($conn, $username);
    $hashedPassword = md5($password);

    $query = "SELECT * FROM tblclassteacher 
              WHERE emailAddress = '$username' 
              AND password = '$hashedPassword'
              LIMIT 1";

    $result = mysqli_query($conn, $query);

    if (!$result || mysqli_num_rows($result) == 0) {
        $query = "SELECT * FROM tblclassteacher 
                  WHERE username = '$username' 
                  AND password = '$hashedPassword'
                  LIMIT 1";

        $result = mysqli_query($conn, $query);
    }

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $userId = $row['Id'];

        mysqli_query($conn, "DELETE FROM api_tokens 
                             WHERE user_id = '$userId' 
                             AND user_type = 'ClassTeacher'");

        $token = bin2hex(random_bytes(32));

        mysqli_query($conn, "INSERT INTO api_tokens 
                             (user_id, user_type, token, created_at)
                             VALUES 
                             ('$userId', 'ClassTeacher', '$token', NOW())");

        echo json_encode([
            "success" => true,
            "message" => "Login successful.",
            "token" => $token,
            "userType" => "ClassTeacher",
            "data" => [
                "Id" => $row['Id'],
                "firstName" => isset($row['firstName']) ? $row['firstName'] : "",
                "lastName" => isset($row['lastName']) ? $row['lastName'] : "",
                "emailAddress" => isset($row['emailAddress']) ? $row['emailAddress'] : "",
                "username" => isset($row['username']) ? $row['username'] : ""
            ]
        ]);
        exit();
    }

    echo json_encode([
        "success" => false,
        "message" => "Invalid class teacher username/email or password."
    ]);
    exit();
}

echo json_encode([
    "success" => false,
    "message" => "Invalid user type."
]);
?>