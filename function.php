<?php
// function.php

// This function seems to generate a serial number.
// For newly created tables with AUTO_INCREMENT PKs, it's often not needed.
// However, if you use it for other purposes (like for `c_id` in 'course' table), keep it.
function slnum($table, $field) {
    global $connection_db;
    $sql = "SELECT MAX(`$field`) AS last_id FROM `$table`";
    $result = mysqli_query($connection_db, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $last_id = $row['last_id'];
        // Assuming your IDs are like ENR001, QUES001, EXM001 etc.
        if ($last_id) {
            preg_match('/(\D+)(\d+)/', $last_id, $matches);
            $prefix = $matches[1];
            $number = intval($matches[2]);
            $new_number = $number + 1;
            return $prefix . str_pad($new_number, strlen($matches[2]), '0', STR_PAD_LEFT);
        }
    }
    // Default starting ID if no previous records
    if ($table == 'course_enrollment') return 'ENR001';
    if ($table == 'course') return 'EXM001'; // For exam IDs
    // Add more prefixes as needed
    return '001'; // Fallback
}

// Helper to check user role (useful for security)
function checkUserRole($required_role) {
    if (!isset($_SESSION['userid']) || $_SESSION['usertype'] !== $required_role) {
        $_SESSION['status'] = "Access Denied. You do not have permission to view this page.";
        header('Location: ' . BASE_URL . 'index.php'); // Redirect to a suitable page
        exit();
    }
}
?>
