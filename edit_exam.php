<?php
// edit_exam.php

include('config.php');
include('function.php');
checkUserRole('Admin');

$exam_id = $_GET['c_id'] ?? null; // Get exam ID from URL

if (!$exam_id) {
    $_SESSION['status'] = "No exam ID provided for editing.";
    redirect(BASE_URL . 'manage_exams.php');
}

// --- Process POST data for updating the exam ---
if (isset($_POST['update_exam'])) {
    $c_id_post = mysqli_real_escape_string($connection_db, $_POST['c_id']); // Original ID (hidden field)
    $c_name = mysqli_real_escape_string($connection_db, $_POST['c_name']);
    $c_category = mysqli_real_escape_string($connection_db, $_POST['c_category']);
    $c_duration = (int)$_POST['c_duration'];
    $c_inst_name = mysqli_real_escape_string($connection_db, $_POST['c_inst_name']);
    $c_status = mysqli_real_escape_string($connection_db, $_POST['c_status']); // Allow status change
    $division_br = mysqli_real_escape_string($connection_db, $_POST['division_br']);
    $passing_marks = (float)$_POST['passing_marks'];

    // Basic validation
    if (empty($c_id_post) || empty($c_name) || empty($c_category) || empty($c_duration) || empty($c_inst_name) || empty($division_br) || $passing_marks === '') {
        $_SESSION['status'] = "All fields are required for updating an exam.";
        redirect(BASE_URL . 'edit_exam.php?c_id=' . urlencode($c_id_post));
    }

    mysqli_begin_transaction($connection_db);
    try {
        $update_query = "UPDATE course SET c_name = ?, c_category = ?, c_duration = ?, c_inst_name = ?, c_status = ?, division_br = ?, passing_marks = ? WHERE c_id = ? AND c_type = 'exam'";
        $stmt = mysqli_prepare($connection_db, $update_query);
        // Corrected bind_param type string: "sssisdss" for 8 parameters
        // s (c_name), s (c_category), i (c_duration), s (c_inst_name), s (c_status), s (division_br), d (passing_marks), s (c_id_post)
        mysqli_stmt_bind_param($stmt, "sssisdss", $c_name, $c_category, $c_duration, $c_inst_name, $c_status, $division_br, $passing_marks, $c_id_post);

        if (!mysqli_stmt_execute($stmt)) {
            // Use mysqli_stmt_error() for specific statement errors
            throw new Exception("Error updating exam: " . mysqli_stmt_error($stmt));
        }

        mysqli_commit($connection_db);
        $_SESSION['success'] = "Exam '" . htmlspecialchars($c_name) . "' updated successfully!";
    } catch (Exception $e) {
        mysqli_rollback($connection_db);
        $_SESSION['status'] = "Failed to update exam: " . $e->getMessage();
        error_log("Update exam failed: " . $e->getMessage());
    }
    redirect(BASE_URL . 'manage_exams.php'); // Redirect back to manage_exams
}


// --- Fetch exam data to pre-fill the form ---
$exam_query = "SELECT * FROM course WHERE c_id = ? AND c_type = 'exam'";
$stmt_fetch = mysqli_prepare($connection_db, $exam_query);
mysqli_stmt_bind_param($stmt_fetch, "s", $exam_id);
mysqli_stmt_execute($stmt_fetch);
$exam_result = mysqli_stmt_get_result($stmt_fetch);
$exam_data = mysqli_fetch_assoc($exam_result);
mysqli_stmt_close($stmt_fetch); // Close statement

if (!$exam_data) {
    $_SESSION['status'] = "Exam not found or is not an exam type.";
    redirect(BASE_URL . 'manage_exams.php');
}

include('includes/header.php');
?>

<div class="card-shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Edit Exam: <?= htmlspecialchars($exam_data['c_name']); ?> (ID: <?= htmlspecialchars($exam_data['c_id']); ?>)</h6>
    </div>
    <div class="card-body">
        <?php
        if (isset($_SESSION['success']) && $_SESSION['success'] != '') {
            echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['status']) && $_SESSION['status'] != '') {
            echo '<div class="alert alert-danger">' . $_SESSION['status'] . '</div>';
            unset($_SESSION['status']);
        }
        ?>

        <form action="<?= BASE_URL ?>edit_exam.php?c_id=<?= htmlspecialchars($exam_data['c_id']); ?>" method="POST">
            <input type="hidden" name="c_id" value="<?= htmlspecialchars($exam_data['c_id']); ?>">

            <div class="form-group">
                <label for="c_name">Exam Name:</label>
                <input type="text" class="form-control" id="c_name" name="c_name" value="<?= htmlspecialchars($exam_data['c_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="c_category">Category:</label>
                <input type="text" class="form-control" id="c_category" name="c_category" value="<?= htmlspecialchars($exam_data['c_category']); ?>" required>
            </div>
            <div class="form-group">
                <label for="c_duration">Duration (Minutes):</label>
                <input type="number" class="form-control" id="c_duration" name="c_duration" value="<?= htmlspecialchars($exam_data['c_duration']); ?>" min="1" required>
            </div>
            <div class="form-group">
                <label for="c_inst_name">Instructor/Creator Name:</label>
                <input type="text" class="form-control" id="c_inst_name" name="c_inst_name" value="<?= htmlspecialchars($exam_data['c_inst_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="c_status">Status:</label>
                <select class="form-control" id="c_status" name="c_status" required>
                    <option value="active" <?= ($exam_data['c_status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?= ($exam_data['c_status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="form-group">
                <label for="division_br">Division/Branch:</label>
                <input type="text" class="form-control" id="division_br" name="division_br" value="<?= htmlspecialchars($exam_data['division_br']); ?>" required>
            </div>
            <div class="form-group">
                <label for="passing_marks">Passing Marks:</label>
                <input type="number" class="form-control" id="passing_marks" name="passing_marks" value="<?= htmlspecialchars($exam_data['passing_marks']); ?>" step="0.01" min="0" required>
            </div>
            <button type="submit" name="update_exam" class="btn btn-primary">Update Exam</button>
            <a href="<?= BASE_URL ?>manage_exams.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include('includes/footer.php'); ?>