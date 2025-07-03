<?php
// register.php - Allows new students to create an account

include('config.php'); // MUST be first. Starts session, connects to DB, defines BASE_URL
include('function.php'); // Include helper functions like redirect

// If a user is already logged in, redirect them to their dashboard
if (isset($_SESSION['userid'])) {
    if ($_SESSION['usertype'] == 'Admin') {
        redirect(BASE_URL . 'admin_dashboard.php');
    } else {
        redirect(BASE_URL . 'course_register_exam.php');
    }
}

// --- Process registration form submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_submit'])) {
    $username = mysqli_real_escape_string($connection_db, $_POST['username']);
    $password = $_POST['password']; // Plaintext password
    $confirm_password = $_POST['confirm_password'];
    $full_name = mysqli_real_escape_string($connection_db, $_POST['full_name']);
    $email = mysqli_real_escape_string($connection_db, $_POST['email']);
    $usertype = 'Student'; // New registrations are always 'Student'
    $status = 'active'; // Default status for new accounts

    // --- Input Validation ---
    if (empty($username) || empty($password) || empty($confirm_password) || empty($full_name) || empty($email)) {
        $_SESSION['status'] = "All fields are required.";
        redirect(BASE_URL . 'register.php');
    }
    if ($password !== $confirm_password) {
        $_SESSION['status'] = "Password and confirmation do not match.";
        redirect(BASE_URL . 'register.php');
    }
    if (strlen($password) < 8) {
        $_SESSION['status'] = "Password must be at least 8 characters long.";
        redirect(BASE_URL . 'register.php');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['status'] = "Invalid email format.";
        redirect(BASE_URL . 'register.php');
    }

    // Check if username or email already exists
    $check_query = "SELECT userid FROM users WHERE username = ? OR email = ?";
    $stmt_check = mysqli_prepare($connection_db, $check_query);
    mysqli_stmt_bind_param($stmt_check, "ss", $username, $email);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);

    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        $_SESSION['status'] = "Username or Email already exists. Please choose a different one.";
        redirect(BASE_URL . 'register.php');
    }
    mysqli_stmt_close($stmt_check);

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    mysqli_begin_transaction($connection_db);
    try {
        $insert_query = "INSERT INTO users (username, password, full_name, email, usertype, status) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_insert = mysqli_prepare($connection_db, $insert_query);
        // Bind parameters: s (username), s (hashed_password), s (full_name), s (email), s (usertype), s (status)
        mysqli_stmt_bind_param($stmt_insert, "ssssss", $username, $hashed_password, $full_name, $email, $usertype, $status);

        if (!mysqli_stmt_execute($stmt_insert)) {
            throw new Exception("Database error: " . mysqli_error($connection_db));
        }

        mysqli_commit($connection_db);
        $_SESSION['success'] = "Account created successfully! You can now log in.";
        redirect(BASE_URL . 'index.php'); // Redirect to login page after successful registration
    } catch (Exception $e) {
        mysqli_rollback($connection_db);
        $_SESSION['status'] = "Failed to create account: " . $e->getMessage();
        error_log("Student registration failed (register.php): " . $e->getMessage());
        redirect(BASE_URL . 'register.php'); // Redirect back to registration on failure
    }
}

// --- HTML structure for the registration page ---
include('includes/header.php');
?>

<div class="login-container card-shadow p-4"> <!-- Reusing login-container styles for consistency -->
    <h2 class="text-center mb-4">Register New Account</h2>
    <?php
    if (isset($_SESSION['status'])) {
        echo '<div class="alert alert-danger">' . $_SESSION['status'] . '</div>';
        unset($_SESSION['status']);
    }
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
        unset($_SESSION['success']);
    }
    ?>
    <form action="<?= BASE_URL ?>register.php" method="POST">
        <div class="form-group">
            <label for="full_name">Full Name:</label>
            <input type="text" class="form-control" id="full_name" name="full_name" required value="<?= htmlspecialchars($_POST['full_name'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" class="form-control" id="username" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm Password:</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        </div>
        <button type="submit" name="register_submit" class="btn btn-primary btn-block">Register</button>
    </form>
    <hr>
    <p class="text-center mt-3">Already have an account? <a href="<?= BASE_URL ?>index.php">Login here</a></p>
</div>

<?php
// The .login-container style is already defined in header.php's style block.
// No additional style needed here unless specific to this page.
include('includes/footer.php');
?>