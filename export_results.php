<?php
// export_results.php

include('config.php'); // MUST be first. Starts session, connects to DB
include('function.php'); // Include helper functions

checkUserRole('Admin'); // Restrict access to Admin only

if (isset($_POST['export_csv'])) {
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=exam_results_' . date('Ymd_His') . '.csv');

    // Create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');

    // Define CSV headers
    $csv_headers = [
        'SL',
        'Student Username',
        'Student Name',
        'Exam Name',
        'Exam Duration (Min)',
        'Attempt Start Time',
        'Attempt End Time',
        'Attempt Status',
        'Score',
        'Total Possible Score', // Added for clarity in CSV
        'Correct Answers',
        'Total Questions Answered',
        'Passing Marks',
        'Percentage (%)' // *** HEADER CHANGED ***
    ];
    fputcsv($output, $csv_headers); // Write headers to CSV

    // Fetch all exam attempts data
    $results_query = "
        SELECT
            ea.attempt_id,
            u.username,
            u.full_name AS student_name,
            c.c_id AS exam_c_id, -- Get c_id to calculate total marks
            c.c_name AS exam_name,
            c.c_duration,
            c.passing_marks,
            ea.start_time,
            ea.end_time,
            ea.status AS attempt_status,
            ea.score,
            ea.total_questions_answered,
            ea.total_questions_correct,
            ce.mark_perct -- *** ADDED THIS FOR PERCENTAGE ***
        FROM exam_attempts ea
        JOIN users u ON ea.student_id = u.userid
        JOIN course c ON ea.exam_id = c.c_id
        LEFT JOIN course_enrollment ce ON ea.student_id = ce.student_id AND ea.exam_id = ce.course_id
        ORDER BY ea.end_time DESC
    ";
    $results_run = mysqli_query($connection_db, $results_query);

    $counter = 0;
    while ($row = mysqli_fetch_assoc($results_run)) {
        $counter++;

        // Calculate total possible marks for the exam (on the fly for this export)
        $total_possible_marks = 0;
        if ($row['exam_c_id']) {
            $total_marks_query = "SELECT SUM(marks) AS total_marks FROM quiz_question WHERE exam_id = ?";
            $stmt_total_marks = mysqli_prepare($connection_db, $total_marks_query);
            mysqli_stmt_bind_param($stmt_total_marks, "s", $row['exam_c_id']);
            mysqli_stmt_execute($stmt_total_marks);
            $total_marks_result = mysqli_stmt_get_result($stmt_total_marks);
            $total_marks_row = mysqli_fetch_assoc($total_marks_result);
            $total_possible_marks = $total_marks_row['total_marks'] ?? 0;
            mysqli_stmt_close($stmt_total_marks); // Close statement
        }

        $csv_data = [
            $counter,
            $row['username'],
            $row['student_name'],
            $row['exam_name'],
            $row['c_duration'],
            $row['start_time'],
            ($row['end_time'] ?? 'N/A'),
            $row['attempt_status'],
            ($row['score'] ?? 'N/A'),
            $total_possible_marks, // Include total possible score in the row
            ($row['total_questions_correct'] ?? 'N/A'),
            ($row['total_questions_answered'] ?? 'N/A'),
            $row['passing_marks'],
            ($row['mark_perct'] !== null ? round($row['mark_perct'], 2) . '%' : 'N/A') // *** DISPLAYING PERCENTAGE ***
        ];
        fputcsv($output, $csv_data); // Write data row to CSV
    }

    fclose($output); // Close the output stream
    exit(); // Terminate script after sending the file
} else {
    $_SESSION['status'] = "Invalid request for export.";
    redirect(BASE_URL . 'view_results.php');
}
?>
