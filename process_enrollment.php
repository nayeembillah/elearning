<?php
include('config.php'); // This includes session_start()

if (!isset($_SESSION['userid']) || $_SESSION['usertype'] !== 'Student') {
    $_SESSION['status'] = "Access Denied. Please log in as a student.";
    redirect(BASE_URL . 'index.php');
}

if (isset($_POST['enroll_exam'])) {
    $student_id = $_SESSION['userid'];
    $course_id = mysqli_real_escape_string($connection_db, $_POST['course_id']);
    $c_name = mysqli_real_escape_string($connection_db, $_POST['c_name']);
    // $c_inst_name = mysqli_real_escape_string($connection_db, $_POST['c_inst_name']); // Not used in insert, but kept for context

    // Check if already enrolled to prevent duplicate entries
    $check_query = "SELECT enroll_id FROM course_enrollment WHERE student_id = ? AND course_id = ?";
    $stmt_check = mysqli_prepare($connection_db, $check_query);
    mysqli_stmt_bind_param($stmt_check, "is", $student_id, $course_id);
    mysqli_stmt_execute($stmt_check);
    $check_result = mysqli_stmt_get_result($stmt_check);

    if (mysqli_num_rows($check_result) > 0) {
        $_SESSION['status'] = "You are already enrolled in " . htmlspecialchars($c_name) . ".";
    } else {
        // Insert new enrollment
        $insert_query = "INSERT INTO course_enrollment (student_id, course_id, en_status) VALUES (?, ?, 'enrolled')";
        $stmt_insert = mysqli_prepare($connection_db, $insert_query);
        mysqli_stmt_bind_param($stmt_insert, "is", $student_id, $course_id);

        if (mysqli_stmt_execute($stmt_insert)) {
            $_SESSION['success'] = "Successfully enrolled in " . htmlspecialchars($c_name) . ". You can now take the exam.";
        } else {
            $_SESSION['status'] = "Error enrolling in exam: " . mysqli_error($connection_db);
        }
    }
} else {
    $_SESSION['status'] = "Invalid request.";
}

redirect(BASE_URL . 'course_register_exam.php');
?>
