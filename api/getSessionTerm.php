<?php
include '../Includes/dbcon.php';

$query = "
SELECT 
    st.Id,
    st.sessionName,
    st.termId,
    t.termName,
    st.isActive,
    CASE
        WHEN st.isActive = 1 THEN 'Active'
        ELSE 'Inactive'
    END AS statusText,
    st.dateCreated
FROM tblsessionterm st
INNER JOIN tblterm t ON t.Id = st.termId
ORDER BY st.Id DESC
";

$result = mysqli_query($conn, $query);

$data = array();

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $data
]);
?>