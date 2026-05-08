<?php
/*
|--------------------------------------------------------------------------
| SECURED AJAX CLASS ARMS FILE
|--------------------------------------------------------------------------
| Purpose:
| This file loads available class arms based on the selected class.
|
| Security improvements:
| 1. Checks if cid exists.
| 2. Validates cid as an integer.
| 3. Uses prepared statements to prevent SQL Injection.
| 4. Uses htmlspecialchars() when displaying class arm names.
| 5. Shows a safe dropdown even when no record is found.
|--------------------------------------------------------------------------
*/

include '../Includes/dbcon.php';

// Check if cid exists and is a valid number
if (!isset($_GET['cid']) || !filter_var($_GET['cid'], FILTER_VALIDATE_INT)) {
    echo '<select required name="classArmId" class="form-control mb-3">';
    echo '<option value="">Invalid Class Selected</option>';
    echo '</select>';
    exit();
}

// Convert cid to integer
$cid = (int) $_GET['cid'];

// Prepare query instead of placing cid directly in SQL
$query = "SELECT Id, classArmName 
          FROM tblclassarms 
          WHERE classId = ? AND isAssigned = '0'
          ORDER BY classArmName ASC";

$stmt = $conn->prepare($query);

if (!$stmt) {
    error_log("ajaxClassArms prepare failed: " . $conn->error);

    echo '<select required name="classArmId" class="form-control mb-3">';
    echo '<option value="">Error loading class arms</option>';
    echo '</select>';
    exit();
}

$stmt->bind_param("i", $cid);
$stmt->execute();

$result = $stmt->get_result();

echo '<select required name="classArmId" class="form-control mb-3">';
echo '<option value="">--Select Class Arm--</option>';

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo '<option value="' . htmlspecialchars($row['Id'], ENT_QUOTES, 'UTF-8') . '">' 
             . htmlspecialchars($row['classArmName'], ENT_QUOTES, 'UTF-8') . 
             '</option>';
    }
} else {
    echo '<option value="">No available class arm</option>';
}

echo '</select>';

$stmt->close();
?>