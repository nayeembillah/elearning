<?php
// view_results.php

include('config.php'); // MUST be first. Starts session, connects to DB, defines BASE_URL
include('function.php'); // Include helper functions

checkUserRole('Admin'); // Restrict access to Admin only

// --- Fetch all exam attempts with user and exam details ---
$results_query = "
    SELECT
        ea.attempt_id,
        u.username,
        u.full_name AS student_name,
        c.c_id AS exam_c_id, -- Used to fetch total possible marks
        c.c_name AS exam_name,
        c.c_duration,
        c.passing_marks,
        ea.start_time,
        ea.end_time,
        ea.status AS attempt_status,
        ea.score,
        ea.total_questions_answered,
        ea.total_questions_correct,
        ce.mark_perct, -- *** ADDED THIS FOR PERCENTAGE ***
        ce.en_status AS final_enrollment_status -- Still useful for internal status check
    FROM exam_attempts ea
    JOIN users u ON ea.student_id = u.userid
    JOIN course c ON ea.exam_id = c.c_id
    LEFT JOIN course_enrollment ce ON ea.student_id = ce.student_id AND ea.exam_id = ce.course_id
    ORDER BY ea.end_time DESC
";
$results_run = mysqli_query($connection_db, $results_query);

// --- Now include the header HTML ---
include('includes/header.php');
?>

<div class="card-shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">All Exam Results</h6>
        <form action="<?= BASE_URL ?>export_results.php" method="POST">
            <button type="submit" name="export_csv" class="btn btn-info btn-sm">
                <i class="fas fa-file-excel"></i> Export to Excel (CSV)
            </button>
        </form>
    </div>
    <div class="card-body">
        <?php
        // Display messages
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
                            <th>Student Username</th>
                            <th>Student Name</th>
                            <th>Exam Name</th>
                            <th>Duration (Min)</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Attempt Status</th>
                            <th>Score</th>
                            <th>Correct</th>
                            <th>Total Questions</th>
                            <th>Passing Marks</th>
                            <th>Percentage (%)</th> </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 0;
                        while ($row = mysqli_fetch_assoc($results_run)) {
                            $counter++;
                            // Calculate total possible marks for the exam for this specific attempt's exam_id
                            $actual_exam_c_id = $row['exam_c_id'];

                            $total_possible_marks = 0;
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
                                <td><?= htmlspecialchars($row['username']); ?></td>
                                <td><?= htmlspecialchars($row['student_name']); ?></td>
                                <td><?= htmlspecialchars($row['exam_name']); ?></td>
                                <td><?= htmlspecialchars($row['c_duration']); ?></td>
                                <td><?= htmlspecialchars($row['start_time']); ?></td>
                                <td><?= htmlspecialchars($row['end_time'] ?? 'N/A'); ?></td>
                                <td><?= htmlspecialchars($row['attempt_status']); ?></td>
                                <td><?= htmlspecialchars($row['score'] ?? 'N/A'); ?> / <?= htmlspecialchars($total_possible_marks); ?></td>
                                <td><?= htmlspecialchars($row['total_questions_correct'] ?? 'N/A'); ?></td>
                                <td><?= htmlspecialchars($row['total_questions_answered'] ?? 'N/A'); ?></td>
                                <td><?= htmlspecialchars($row['passing_marks']); ?></td>
                                <td>
                                    <?php
                                    // *** DISPLAYING PERCENTAGE ***
                                    if ($row['mark_perct'] !== null) {
                                        echo htmlspecialchars(round($row['mark_perct'], 2)) . '%';
                                    } else {
                                        echo 'N/A';
                                    }
                                    // Optionally, you can still show Pass/Fail based on mark_perct and passing_marks if needed
                                    // if ($row['mark_perct'] !== null && $row['mark_perct'] >= $row['passing_marks']) {
                                    //     echo ' (Pass)';
                                    // } elseif ($row['mark_perct'] !== null) {
                                    //     echo ' (Fail)';
                                    // }
                                    ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } else { ?>
            <div class="alert alert-info">No exam results found yet.</div>
        <?php } ?>
    </div>
</div>

<?php include('includes/footer.php'); ?>
