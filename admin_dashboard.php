<?php
// admin_dashboard.php

include('config.php'); // MUST be first: Starts session, connects to DB, defines BASE_URL.
include('function.php'); // Include helper functions.

checkUserRole('Admin'); // Restrict access to Admin users only.

// --- No specific POST processing for this dashboard page, so we can proceed to HTML ---

// Now, include the common header HTML.
include('includes/header.php');
?>

<div class="card-shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Admin Dashboard</h6>
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
        <p class="mb-4">Welcome, Admin! From here you can manage various aspects of the system:</p>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-file-alt fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Manage Exams</h5>
                        <p class="card-text text-muted">Add, edit, or remove exam details.</p>
                        <a href="<?= BASE_URL ?>manage_exams.php" class="btn btn-primary mt-2">Go to Exams</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-question-circle fa-3x text-success mb-3"></i>
                        <h5 class="card-title">Manage Questions</h5>
                        <p class="card-text text-muted">Add multiple-choice questions for exams.</p>
                        <a href="<?= BASE_URL ?>manage_questions.php" class="btn btn-success mt-2">Go to Questions</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-user-graduate fa-3x text-info mb-3"></i>
                        <h5 class="card-title">Manage Students</h5>
                        <p class="card-text text-muted">Add and view student user accounts.</p>
                        <a href="<?= BASE_URL ?>manage_students.php" class="btn btn-info mt-2">Go to Students</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-chart-bar fa-3x text-warning mb-3"></i>
                        <h5 class="card-title">View Exam Results</h5>
                        <p class="card-text text-muted">Review and export all student exam performance data.</p>
                        <a href="<?= BASE_URL ?>view_results.php" class="btn btn-warning mt-2">Go to Results</a>
                    </div>
                </div>
            </div>

            </div></div>
</div>

<?php
// Include the common footer HTML and closing tags.
include('includes/footer.php');
?>
