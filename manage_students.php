<?php
// manage_students.php

include('config.php'); // MUST be first. Starts session, connects to DB, defines BASE_URL
include('function.php'); // Include helper functions like checkUserRole and redirect

checkUserRole('Admin'); // Restrict access to Admin only

// --- Process POST data for adding a new student ---
if (isset($_POST['add_student'])) {
    $username = mysqli_real_escape_string($connection_db, $_POST['username']);
    $password = $_POST['password']; // Plaintext password from form
    $full_name = mysqli_real_escape_string($connection_db, $_POST['full_name']);
    $email = mysqli_real_escape_string($connection_db, $_POST['email']);
    $usertype = 'Student'; // New users from this form are always 'Student'

    // Basic validation
    if (empty($username) || empty($password) || empty($full_name) || empty($email)) {
        $_SESSION['status'] = "All fields are required.";
        redirect(BASE_URL . 'manage_students.php');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['status'] = "Invalid email format.";
        redirect(BASE_URL . 'manage_students.php');
    }

    // Check if username or email already exists to prevent duplicates
    $check_query = "SELECT userid FROM users WHERE username = ? OR email = ?";
    $stmt_check = mysqli_prepare($connection_db, $check_query);
    mysqli_stmt_bind_param($stmt_check, "ss", $username, $email);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);

    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        $_SESSION['status'] = "Username or Email already exists. Please choose a different one.";
        redirect(BASE_URL . 'manage_students.php');
    }
    mysqli_stmt_close($stmt_check);

    // Hash the password securely
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    mysqli_begin_transaction($connection_db);
    try {
        $insert_query = "INSERT INTO users (username, password, full_name, email, usertype) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = mysqli_prepare($connection_db, $insert_query);
        mysqli_stmt_bind_param($stmt_insert, "sssss", $username, $hashed_password, $full_name, $email, $usertype);

        if (!mysqli_stmt_execute($stmt_insert)) {
            throw new Exception("Database error: " . mysqli_error($connection_db));
        }

        mysqli_stmt_close($stmt_insert);
        mysqli_commit($connection_db);
        $_SESSION['success'] = "Student '" . htmlspecialchars($full_name) . "' added successfully!";
    } catch (Exception $e) {
        mysqli_rollback($connection_db);
        $_SESSION['status'] = "Failed to add student: " . $e->getMessage();
        error_log("Failed to add student (manage_students.php): " . $e->getMessage());
    }
    redirect(BASE_URL . 'manage_students.php');
}
// --- End of POST processing for adding a new student ---


// --- Fetch all student users for display ---
$students_query = "SELECT userid, username, full_name, email, created_at FROM users WHERE usertype = 'Student' ORDER BY username ASC";
$students_result = mysqli_query($connection_db, $students_query);

// --- Now include the header HTML ---
include('includes/header.php');
?>

<div class="card-shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Manage Students</h6>
    </div>
    <div class="card-body">
        <?php
        // Display session messages (success/status)
        if (isset($_SESSION['success']) && $_SESSION['success'] != '') {
            echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['status']) && $_SESSION['status'] != '') {
            echo '<div class="alert alert-danger">' . $_SESSION['status'] . '</div>';
            unset($_SESSION['status']);
        }
        ?>

        <h5>Add New Student</h5>
        <form action="<?= BASE_URL ?>manage_students.php" method="POST" class="mb-5">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" class="form-control" id="full_name" name="full_name" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <button type="submit" name="add_student" class="btn btn-primary">Add Student</button>
        </form>

        <hr>

        <h5>Existing Students</h5>
        <?php if (mysqli_num_rows($students_result) > 0) { ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Registered On</th>
                            <th>Actions</th> </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 0;
                        while ($row = mysqli_fetch_assoc($students_result)) {
                            $counter++; ?>
                            <tr>
                                <td><?= $counter; ?></td>
                                <td><?= htmlspecialchars($row['userid']); ?></td>
                                <td><?= htmlspecialchars($row['username']); ?></td>
                                <td><?= htmlspecialchars($row['full_name']); ?></td>
                                <td><?= htmlspecialchars($row['email']); ?></td>
                                <td><?= htmlspecialchars($row['created_at']); ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>edit_student.php?userid=<?= htmlspecialchars($row['userid']); ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <form action="<?= BASE_URL ?>delete_student.php" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this student (<?= htmlspecialchars($row['username']); ?>) and ALL their exam data? This cannot be undone!');">
                                        <input type="hidden" name="userid" value="<?= htmlspecialchars($row['userid']); ?>">
                                        <button type="submit" name="delete_student" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } else { ?>
            <div class="alert alert-info">No student accounts found.</div>
        <?php } ?>
    </div>
</div>

<?php include('includes/footer.php'); ?>
