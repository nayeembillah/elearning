<?php
// manage_exams.php - Allows admins to add, edit, and delete exams.

include('config.php'); // MUST be first. Starts session, connects to DB, defines BASE_URL
include('function.php'); // Include helper functions like checkUserRole and redirect

checkUserRole('Admin'); // Restrict access to Admin only

// --- Process POST data for adding a new exam ---
if (isset($_POST['add_exam'])) {
    $c_id = mysqli_real_escape_string($connection_db, $_POST['c_id']);
    $c_name = mysqli_real_escape_string($connection_db, $_POST['c_name']);
    $c_category = mysqli_real_escape_string($connection_db, $_POST['c_category']);
    $c_duration = (int)$_POST['c_duration'];
    $c_inst_name = mysqli_real_escape_string($connection_db, $_POST['c_inst_name']);
    $division_br = mysqli_real_escape_string($connection_db, $_POST['division_br']);
    $passing_marks = (float)$_POST['passing_marks'];

    // Basic validation
    if (empty($c_id) || empty($c_name) || empty($c_category) || empty($c_duration) || empty($c_inst_name) || empty($division_br) || $passing_marks === '') {
        $_SESSION['status'] = "All fields are required for adding an exam.";
        redirect(BASE_URL . 'manage_exams.php');
    }

    // Check if c_id already exists to prevent duplicates
    $check_id_query = "SELECT c_id FROM course WHERE c_id = ?";
    $stmt_check = mysqli_prepare($connection_db, $check_id_query);
    mysqli_stmt_bind_param($stmt_check, "s", $c_id);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);
    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        $_SESSION['status'] = "Exam ID '" . htmlspecialchars($c_id) . "' already exists. Please use a unique ID.";
        redirect(BASE_URL . 'manage_exams.php');
    }
    mysqli_stmt_close($stmt_check);


    mysqli_begin_transaction($connection_db);
    try {
        $insert_query = "INSERT INTO course (c_id, c_name, c_category, c_duration, c_inst_name, c_status, c_type, division_br, passing_marks) VALUES (?, ?, ?, ?, ?, 'active', 'exam', ?, ?)";
        $stmt = mysqli_prepare($connection_db, $insert_query);
        // Bind parameters: s (c_id), s (c_name), s (c_category), i (c_duration), s (c_inst_name), s (division_br), d (passing_marks)
        mysqli_stmt_bind_param($stmt, "sssisds", $c_id, $c_name, $c_category, $c_duration, $c_inst_name, $division_br, $passing_marks);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error adding exam: " . mysqli_error($connection_db));
        }

        mysqli_commit($connection_db);
        $_SESSION['success'] = "Exam '" . htmlspecialchars($c_name) . "' added successfully!";
    } catch (Exception $e) {
        mysqli_rollback($connection_db);
        $_SESSION['status'] = "Failed to add exam: " . $e->getMessage();
        error_log("Add exam failed: " . $e->getMessage());
    }
    redirect(BASE_URL . 'manage_exams.php');
}
// --- End of POST processing for adding a new exam ---


// --- Fetch all exams for display ---
$exams_query = "SELECT * FROM course WHERE c_type = 'exam' ORDER BY c_name ASC";
$exams_result = mysqli_query($connection_db, $exams_query);

// --- Now include the header HTML ---
include('includes/header.php');
?>

<div class="card-shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Manage Exams</h6>
    </div>
    <div class="card-body">
        <?php
        // Display session messages
        if (isset($_SESSION['success']) && $_SESSION['success'] != '') {
            echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['status']) && $_SESSION['status'] != '') {
            echo '<div class="alert alert-danger">' . $_SESSION['status'] . '</div>';
            unset($_SESSION['status']);
        }
        ?>

        <h5>Add New Exam</h5>
        <form action="<?= BASE_URL ?>manage_exams.php" method="POST" class="mb-5">
            <div class="form-row"> <div class="form-group col-md-6"> <label for="c_id">Exam ID (e.g., EXM001):</label>
                    <input type="text" class="form-control" id="c_id" name="c_id" value="<?php echo slnum('course', 'c_id'); ?>" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="c_name">Exam Name:</label>
                    <input type="text" class="form-control" id="c_name" name="c_name" placeholder="e.g., Intro to PHP Quiz" required>
                </div>
            </div>
            <div class="form-group"> <label for="c_category">Category:</label>
                <input type="text" class="form-control" id="c_category" name="c_category" placeholder="e.g., Programming, Database" required>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="c_duration">Duration (Minutes):</label>
                    <input type="number" class="form-control" id="c_duration" name="c_duration" min="1" placeholder="e.g., 30" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="passing_marks">Passing Marks:</label>
                    <input type="number" class="form-control" id="passing_marks" name="passing_marks" step="0.01" min="0" placeholder="e.g., 60.00" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="division_br">Stakeholder/Division:</label>
                    <input type="text" class="form-control" id="division_br" name="division_br" placeholder="e.g., IT Dept, HR Dept" required>
                </div>
            </div>
            <div class="form-group">
                <label for="c_inst_name">Instructor/Creator Name:</label>
                <input type="text" class="form-control" id="c_inst_name" name="c_inst_name" placeholder="e.g., John Doe" required>
            </div>
            <button type="submit" name="add_exam" class="btn btn-primary mt-3">Add Exam</button>
        </form>

        <hr>
        <h5>Existing Exams</h5>
        <?php if (mysqli_num_rows($exams_result) > 0) { ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Exam ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Duration (Min)</th>
                            <th>Instructor</th>
                            <th>Status</th>
                            <th>Passing Marks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 0;
                        while ($row = mysqli_fetch_assoc($exams_result)) {
                            $counter++; ?>
                            <tr>
                                <td><?= $counter; ?></td>
                                <td><?= htmlspecialchars($row['c_id']); ?></td>
                                <td><?= htmlspecialchars($row['c_name']); ?></td>
                                <td><?= htmlspecialchars($row['c_category']); ?></td>
                                <td><?= htmlspecialchars($row['c_duration']); ?></td>
                                <td><?= htmlspecialchars($row['c_inst_name']); ?></td>
                                <td><?= htmlspecialchars($row['c_status']); ?></td>
                                <td><?= htmlspecialchars($row['passing_marks']); ?></td>
                                <td>
                                    <div class="btn-group-actions"> <a href="<?= BASE_URL ?>manage_questions.php?exam_id=<?= htmlspecialchars($row['c_id']); ?>" class="btn btn-info btn-sm">Manage Questions</a>
                                        <a href="<?= BASE_URL ?>edit_exam.php?c_id=<?= htmlspecialchars($row['c_id']); ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <form action="<?= BASE_URL ?>delete_exam.php" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this exam (<?= htmlspecialchars($row['c_name']); ?>) and ALL its related data (questions, enrollments, results)? This cannot be undone!');">
                                            <input type="hidden" name="c_id" value="<?= htmlspecialchars($row['c_id']); ?>">
                                            <button type="submit" name="delete_exam" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } else { ?>
            <div class="alert alert-info">No exams created yet.</div>
        <?php } ?>
    </div>
</div>

<?php include('includes/footer.php'); ?>