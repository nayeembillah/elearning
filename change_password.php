<?php
// change_password.php - Allows logged-in users to change their local password

// Include config.php first: It starts the session, connects to the database, and defines BASE_URL.
include('config.php');
// Include function.php for helper functions like redirect.
include('function.php');

// Ensure user is logged in before allowing password change.
if (!isset($_SESSION['userid'])) {
    $_SESSION['status'] = "Please log in to change your password.";
    redirect(BASE_URL . 'index.php');
}

$user_id = $_SESSION['userid'];     // Get the current user's ID from session
$username = $_SESSION['username']; // Get the current user's username from session
$usertype = $_SESSION['usertype']; // Get the current user's type from session

// --- Process password change form submission ---
if (isset($_POST['change_password_submit'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    // --- Input Validation ---
    if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
        $_SESSION['status'] = "All password fields are required.";
        redirect(BASE_URL . 'change_password.php');
    }
    if ($new_password !== $confirm_new_password) {
        $_SESSION['status'] = "New password and confirmation do not match.";
        redirect(BASE_URL . 'change_password.php');
    }
    // Basic password strength check: minimum length
    if (strlen($new_password) < 8) { // You can add more complex checks here (e.g., regex for numbers, symbols)
        $_SESSION['status'] = "New password must be at least 8 characters long.";
        redirect(BASE_URL . 'change_password.php');
    }
    // Prevent changing to the same password as current (optional but good practice)
    if ($current_password === $new_password) {
        $_SESSION['status'] = "New password cannot be the same as the current password.";
        redirect(BASE_URL . 'change_password.php');
    }


    // Fetch the current hashed password from the database for verification
    $user_password_hash_query = "SELECT password FROM users WHERE userid = ?";
    $stmt_pwd_hash = mysqli_prepare($connection_db, $user_password_hash_query);
    mysqli_stmt_bind_param($stmt_pwd_hash, "i", $user_id); // 'i' for integer (userid)
    mysqli_stmt_execute($stmt_pwd_hash);
    $result_pwd_hash = mysqli_stmt_get_result($stmt_pwd_hash);
    $user_data = mysqli_fetch_assoc($result_pwd_hash);
    $stored_password_hash = $user_data['password'] ?? ''; // Get the stored hashed password
    mysqli_stmt_close($stmt_pwd_hash); // Close the prepared statement

    // Verify the entered current password against the stored hash
    if (password_verify($current_password, $stored_password_hash)) {
        // Current password is correct. Now hash the new password.
        $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the password in the database
        $update_query = "UPDATE users SET password = ? WHERE userid = ?";
        $stmt_update = mysqli_prepare($connection_db, $update_query);
        mysqli_stmt_bind_param($stmt_update, "si", $hashed_new_password, $user_id); // 's' for string (hash), 'i' for integer (userid)

        if (mysqli_stmt_execute($stmt_update)) {
            $_SESSION['success'] = "Password changed successfully!";
        } else {
            $_SESSION['status'] = "Error updating password: " . mysqli_error($connection_db);
            // Log the error for debugging, but don't show internal details to the user
            error_log("Password change failed for user $user_id: " . mysqli_error($connection_db));
        }
        mysqli_stmt_close($stmt_update); // Close the prepared statement
    } else {
        // Current password entered is incorrect
        $_SESSION['status'] = "Current password is incorrect.";
    }

    // Redirect back to the change password page to show messages and refresh the form
    redirect(BASE_URL . 'change_password.php');
}
// --- End of POST processing ---


// --- HTML structure for the Change Password page ---
// Include the common header HTML (which opens <html>, <head>, <body>, and main container divs).
// This must be AFTER all PHP logic that might lead to a redirect.
include('includes/header.php');
?>

<div class="card-shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Change Password</h6>
    </div>
    <div class="card-body">
        <?php
        // Display success or error messages stored in session
        if (isset($_SESSION['success']) && $_SESSION['success'] != '') {
            echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']); // Clear the message after displaying
        }
        if (isset($_SESSION['status']) && $_SESSION['status'] != '') {
            echo '<div class="alert alert-danger">' . $_SESSION['status'] . '</div>';
            unset($_SESSION['status']); // Clear the message after displaying
        }
        ?>

        <p class="text-info">Use the form below to change your password for username "<strong><?= htmlspecialchars($username); ?></strong>".</p>
        <form action="<?= BASE_URL ?>change_password.php" method="POST">
            <div class="form-group">
                <label for="current_password">Current Password:</label>
                <input type="password" class="form-control" id="current_password" name="current_password" required>
            </div>
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm_new_password">Confirm New Password:</label>
                <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required>
            </div>
            <button type="submit" name="change_password_submit" class="btn btn-primary">Change Password</button>
        </form>
    </div>
</div>

<?php
// Include the common footer HTML (which closes main container divs, </body>, and </html>).
include('includes/footer.php');
?>
