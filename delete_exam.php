<?php
// delete_exam.php

include('config.php');
include('function.php');
checkUserRole('Admin');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_exam'])) {
    $exam_id = mysqli_real_escape_string($connection_db, $_POST['c_id']);

    if (empty($exam_id)) {
        $_SESSION['status'] = "No exam ID provided for deletion.";
        redirect(BASE_URL . 'manage_exams.php');
    }

    mysqli_begin_transaction($connection_db);
    try {
        // Prepare the delete query
        // The `ON DELETE CASCADE` foreign key constraints in your database schema
        // (for quiz_question, exam_attempts, student_answers, course_enrollment)
        // will automatically delete all related records when the course (exam) is deleted.
        $delete_query = "DELETE FROM course WHERE c_id = ? AND c_type = 'exam'";
        $stmt = mysqli_prepare($connection_db, $delete_query);
        mysqli_stmt_bind_param($stmt, "s", $exam_id);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error deleting exam: " . mysqli_error($connection_db));
        }

        // Check if any row was actually deleted
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            mysqli_commit($connection_db);
            $_SESSION['success'] = "Exam (ID: " . htmlspecialchars($exam_id) . ") and all related data deleted successfully.";
        } else {
            // No row deleted, might mean ID didn't exist or wasn't an 'exam' type
            mysqli_rollback($connection_db);
            $_SESSION['status'] = "Exam (ID: " . htmlspecialchars($exam_id) . ") not found or could not be deleted.";
        }
    } catch (Exception $e) {
        mysqli_rollback($connection_db);
        $_SESSION['status'] = "Failed to delete exam: " . $e->getMessage();
        error_log("Delete exam failed: " . $e->getMessage());
    }

    redirect(BASE_URL . 'manage_exams.php');
} else {
    // If accessed directly via GET or without the delete_exam POST parameter
    $_SESSION['status'] = "Invalid request for exam deletion.";
    redirect(BASE_URL . 'manage_exams.php');
}
?>
