<?php
// delete_student.php

include('config.php');
include('function.php');
checkUserRole('Admin');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_student'])) {
    $user_id_to_delete = mysqli_real_escape_string($connection_db, $_POST['userid']);
    $current_admin_id = $_SESSION['userid']; // Get the ID of the currently logged-in admin

    if (empty($user_id_to_delete)) {
        $_SESSION['status'] = "No user ID provided for deletion.";
        redirect(BASE_URL . 'manage_students.php');
    }

    // --- Security Check: Prevent admin from deleting themselves ---
    if ($user_id_to_delete == $current_admin_id) {
        $_SESSION['status'] = "You cannot delete your own admin account.";
        redirect(BASE_URL . 'manage_students.php');
    }

    mysqli_begin_transaction($connection_db);
    try {
        // Fetch the usertype of the user being deleted to provide a more specific message
        $get_usertype_query = "SELECT username, usertype FROM users WHERE userid = ?";
        $stmt_get_type = mysqli_prepare($connection_db, $get_usertype_query);
        mysqli_stmt_bind_param($stmt_get_type, "i", $user_id_to_delete);
        mysqli_stmt_execute($stmt_get_type);
        $user_data_result = mysqli_stmt_get_result($stmt_get_type);
        $user_to_delete_data = mysqli_fetch_assoc($user_data_result);
        mysqli_stmt_close($stmt_get_type);

        if (!$user_to_delete_data) {
            $_SESSION['status'] = "User not found or already deleted.";
            redirect(BASE_URL . 'manage_students.php');
        }

        // Prepare the delete query
        // The `ON DELETE CASCADE` foreign key constraints in your database schema
        // (from exam_attempts and course_enrollment tables referencing users.userid)
        // will automatically delete all related exam data when the user is deleted.
        $delete_query = "DELETE FROM users WHERE userid = ?";
        $stmt = mysqli_prepare($connection_db, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $user_id_to_delete);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error deleting user: " . mysqli_error($connection_db));
        }

        // Check if any row was actually deleted
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            mysqli_commit($connection_db);
            $_SESSION['success'] = htmlspecialchars($user_to_delete_data['usertype']) . " '" . htmlspecialchars($user_to_delete_data['username']) . "' and all their related data deleted successfully.";
        } else {
            // No row deleted, might mean ID didn't exist
            mysqli_rollback($connection_db);
            $_SESSION['status'] = "User (ID: " . htmlspecialchars($user_id_to_delete) . ") not found or could not be deleted.";
        }
        mysqli_stmt_close($stmt);

    } catch (Exception $e) {
        mysqli_rollback($connection_db);
        $_SESSION['status'] = "Failed to delete user: " . $e->getMessage();
        error_log("Delete user failed: " . $e->getMessage());
    }

    redirect(BASE_URL . 'manage_students.php');
} else {
    // If accessed directly via GET or without the delete_student POST parameter
    $_SESSION['status'] = "Invalid request for user deletion.";
    redirect(BASE_URL . 'manage_students.php');
}
?>
