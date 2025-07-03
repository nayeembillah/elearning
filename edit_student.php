<?php
// edit_student.php - Allows admin to modify student details, set new password, and change status

include('config.php');
include('function.php');
checkUserRole('Admin');

$user_id_to_edit = $_GET['userid'] ?? null; // Get user ID from URL

if (!$user_id_to_edit) {
    $_SESSION['status'] = "No user ID provided for editing.";
    redirect(BASE_URL . 'manage_students.php');
}

// --- Process POST data for updating the student ---
if (isset($_POST['update_student'])) {
    $userid_post = mysqli_real_escape_string($connection_db, $_POST['userid']); // Original ID (hidden field)
    $username = mysqli_real_escape_string($connection_db, $_POST['username']);
    $full_name = mysqli_real_escape_string($connection_db, $_POST['full_name']);
    $email = mysqli_real_escape_string($connection_db, $_POST['email']);
    $usertype = mysqli_real_escape_string($connection_db, $_POST['usertype']); // Allow changing usertype
    $new_password = $_POST['new_password']; // Plaintext new password (optional)
    $confirm_new_password = $_POST['confirm_new_password']; // Confirmation for new password
    $status = mysqli_real_escape_string($connection_db, $_POST['status']); // New status field

    // Basic validation for core fields
    if (empty($userid_post) || empty($username) || empty($full_name) || empty($email) || empty($usertype) || empty($status)) {
        $_SESSION['status'] = "All mandatory fields (Username, Full Name, Email, User Type, Status) are required.";
        redirect(BASE_URL . 'edit_student.php?userid=' . urlencode($userid_post));
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['status'] = "Invalid email format.";
        redirect(BASE_URL . 'edit_student.php?userid=' . urlencode($userid_post));
    }

    // Validate new password fields if they are not empty
    $password_changed = false;
    $hashed_new_password = '';
    if (!empty($new_password)) {
        if ($new_password !== $confirm_new_password) {
            $_SESSION['status'] = "New password and confirmation do not match.";
            redirect(BASE_URL . 'edit_student.php?userid=' . urlencode($userid_post));
        }
        if (strlen($new_password) < 8) { // Basic length check
            $_SESSION['status'] = "New password must be at least 8 characters long.";
            redirect(BASE_URL . 'edit_student.php?userid=' . urlencode($userid_post));
        }
        $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
        $password_changed = true;
    }

    // Check for username/email conflicts with another existing user (excluding the current user being edited)
    $check_conflict_query = "SELECT userid FROM users WHERE (username = ? OR email = ?) AND userid != ?";
    $stmt_conflict = mysqli_prepare($connection_db, $check_conflict_query);
    mysqli_stmt_bind_param($stmt_conflict, "ssi", $username, $email, $userid_post);
    mysqli_stmt_execute($stmt_conflict);
    mysqli_stmt_store_result($stmt_conflict);
    if (mysqli_stmt_num_rows($stmt_conflict) > 0) {
        $_SESSION['status'] = "Username or Email is already in use by another user.";
        redirect(BASE_URL . 'edit_student.php?userid=' . urlencode($userid_post));
    }
    mysqli_stmt_close($stmt_conflict);


    mysqli_begin_transaction($connection_db);
    try {
        $update_query_parts = [];
        $bind_types = "";
        $bind_params = [];

        // Always update these fields
        $update_query_parts[] = "username = ?";
        $bind_types .= "s";
        $bind_params[] = &$username;

        $update_query_parts[] = "full_name = ?";
        $bind_types .= "s";
        $bind_params[] = &$full_name;

        $update_query_parts[] = "email = ?";
        $bind_types .= "s";
        $bind_params[] = &$email;

        $update_query_parts[] = "usertype = ?";
        $bind_types .= "s";
        $bind_params[] = &$usertype;

        $update_query_parts[] = "status = ?"; // Add status update
        $bind_types .= "s";
        $bind_params[] = &$status;

        // Conditionally update password
        if ($password_changed) {
            $update_query_parts[] = "password = ?";
            $bind_types .= "s";
            $bind_params[] = &$hashed_new_password;
        }

        // Finalize query
        $update_query = "UPDATE users SET " . implode(", ", $update_query_parts) . " WHERE userid = ?";
        $bind_types .= "i"; // Add type for userid
        $bind_params[] = &$userid_post; // Add userid to bind parameters

        $stmt = mysqli_prepare($connection_db, $update_query);

        // Use call_user_func_array to bind parameters dynamically
        call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt, $bind_types], $bind_params));

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error updating student: " . mysqli_error($connection_db));
        }

        mysqli_commit($connection_db);
        $_SESSION['success'] = "Student '" . htmlspecialchars($full_name) . "' (User ID: " . htmlspecialchars($userid_post) . ") updated successfully!" . ($password_changed ? " Password also changed." : "");
    } catch (Exception $e) {
        mysqli_rollback($connection_db);
        $_SESSION['status'] = "Failed to update student: " . $e->getMessage();
        error_log("Update student failed: " . $e->getMessage());
    }
    redirect(BASE_URL . 'manage_students.php');
}


// --- Fetch student data to pre-fill the form ---
// Note: We deliberately do NOT fetch the 'password' field here for security.
$student_query = "SELECT userid, username, full_name, email, usertype, status FROM users WHERE userid = ?";
$stmt_fetch = mysqli_prepare($connection_db, $student_query);
mysqli_stmt_bind_param($stmt_fetch, "i", $user_id_to_edit);
mysqli_stmt_execute($stmt_fetch);
$student_result = mysqli_stmt_get_result($stmt_fetch);
$student_data = mysqli_fetch_assoc($student_result);
mysqli_stmt_close($stmt_fetch);

if (!$student_data) {
    $_SESSION['status'] = "Student not found.";
    redirect(BASE_URL . 'manage_students.php');
}

include('includes/header.php');
?>

<div class="card-shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Edit Student: <?= htmlspecialchars($student_data['username']); ?> (ID: <?= htmlspecialchars($student_data['userid']); ?>)</h6>
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

        <form action="<?= BASE_URL ?>edit_student.php?userid=<?= htmlspecialchars($student_data['userid']); ?>" method="POST">
            <input type="hidden" name="userid" value="<?= htmlspecialchars($student_data['userid']); ?>">

            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($student_data['username']); ?>" required>
            </div>
            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($student_data['full_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($student_data['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="usertype">User Type:</label>
                <select class="form-control" id="usertype" name="usertype" required>
                    <option value="Student" <?= ($student_data['usertype'] == 'Student') ? 'selected' : ''; ?>>Student</option>
                    <option value="Admin" <?= ($student_data['usertype'] == 'Admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label for="status">Account Status:</label>
                <select class="form-control" id="status" name="status" required>
                    <option value="active" <?= ($student_data['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?= ($student_data['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>

            <hr> <h5>Set New Password (Leave blank to keep current password)</h5>
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Enter new password">
            </div>
            <div class="form-group">
                <label for="confirm_new_password">Confirm New Password:</label>
                <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" placeholder="Confirm new password">
            </div>

            <button type="submit" name="update_student" class="btn btn-primary">Update Student</button>
            <a href="<?= BASE_URL ?>manage_students.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include('includes/footer.php'); ?>
