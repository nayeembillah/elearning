<?php
// student_results.php - Lists student's completed exams and provides review option

// Include config.php first: It starts the session, connects to DB, and defines BASE_URL.
include('config.php');
// Include function.php for helper functions.
include('function.php');

// Ensure user is logged in and is a student. This check includes a redirect if unauthorized.
if (!isset($_SESSION['userid']) || $_SESSION['usertype'] !== 'Student') {
    $_SESSION['status'] = "Access Denied. Please log in as a student.";
    redirect(BASE_URL . 'index.php');
}

$student_id = $_SESSION['userid'];

// Fetch all exam attempts for the logged-in student that are submitted or graded
// We join with course and course_enrollment to get all necessary details.
$results_query = "
    SELECT
        ea.attempt_id,
        c.c_name AS exam_name,
        c.c_id AS exam_c_id,
        c.c_duration,
        c.passing_marks,
        ea.start_time,
        ea.end_time,
        ea.status AS attempt_status, -- Status from exam_attempts (e.g., 'submitted', 'graded')
        ea.score,
        ea.total_questions_answered,
        ea.total_questions_correct,
        ce.mark_perct,                -- Percentage from course_enrollment
        ce.en_status AS final_enrollment_status -- Final Pass/Fail status from course_enrollment
    FROM exam_attempts ea
    JOIN course c ON ea.exam_id = c.c_id
    LEFT JOIN course_enrollment ce ON ea.student_id = ce.student_id AND ea.exam_id = ce.course_id
    WHERE ea.student_id = ? AND ea.status IN ('submitted', 'graded') -- Only show completed/graded attempts
    ORDER BY ea.end_time DESC
";
$stmt_results = mysqli_prepare($connection_db, $results_query);
mysqli_stmt_bind_param($stmt_results, "i", $student_id);
mysqli_stmt_execute($stmt_results);
$results_run = mysqli_stmt_get_result($stmt_results);
mysqli_stmt_close($stmt_results); // Close statement after use

// --- Now include the header HTML. This comes AFTER all PHP logic that might redirect. ---
include('includes/header.php');
?>

<div class="card-shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">My Exam Results History</h6>
    </div>
    <div class="card-body">
        <?php
        // Display session messages (success/status) if any
        if (isset($_SESSION['success']) && $_SESSION['success'] != '') {
            echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['status']) && $_SESSION['status'] != '') {
            echo '<div class="alert alert-danger">' . $_SESSION['status'] . '</div>';
            unset($_SESSION['status']);
        }
        ?>

        <?php if (mysqli_num_rows($results_run) > 0) { ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="resultsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Exam Name</th>
                            <th>Attempt Date</th>
                            <th>Duration (Min)</th>
                            <th>Score</th>
                            <th>Total Possible</th>
                            <th>Percentage (%)</th>
                            <th>Final Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 0;
                        while ($row = mysqli_fetch_assoc($results_run)) {
                            $counter++;
                            $actual_exam_c_id = $row['exam_c_id'];
                            $total_possible_marks = 0;

                            // Calculate total possible marks for the exam for this specific exam
                            if ($actual_exam_c_id) {
                                $total_marks_query = "SELECT SUM(marks) AS total_marks FROM quiz_question WHERE exam_id = ?";
                                $stmt_total_marks = mysqli_prepare($connection_db, $total_marks_query);
                                mysqli_stmt_bind_param($stmt_total_marks, "s", $actual_exam_c_id);
                                mysqli_stmt_execute($stmt_total_marks);
                                $total_marks_result = mysqli_stmt_get_result($stmt_total_marks);
                                $total_marks_row = mysqli_fetch_assoc($total_marks_result);
                                $total_possible_marks = $total_marks_row['total_marks'] ?? 0;
                                mysqli_stmt_close($stmt_total_marks); // Close statement
                            }
                        ?>
                            <tr>
                                <td><?= $counter; ?></td>
                                <td><?= htmlspecialchars($row['exam_name']); ?></td>
                                <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($row['end_time'] ?? $row['start_time']))); ?></td>
                                <td><?= htmlspecialchars($row['c_duration']); ?></td>
                                <td><?= htmlspecialchars($row['score'] ?? 'N/A'); ?></td>
                                <td><?= htmlspecialchars($total_possible_marks); ?></td>
                                <td><?= htmlspecialchars(round($row['mark_perct'] ?? 0, 2)); ?>%</td>
                                <td><?= htmlspecialchars($row['final_enrollment_status'] ?? $row['attempt_status']); ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>review_student_answers.php?exam_id=<?= htmlspecialchars($row['exam_c_id']); ?>&attempt_id=<?= htmlspecialchars($row['attempt_id']); ?>" class="btn btn-info btn-sm">
                                        Review Answers
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } else { ?>
            <div class="alert alert-info">You have not completed any exams yet.</div>
        <?php } ?>
    </div>
</div>

<?php
// Include the common footer HTML and closing tags.
include('includes/footer.php');
?>
