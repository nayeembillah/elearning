<?php
include('config.php');
include('function.php');

if (isset($_SESSION['userid'])) {
    if ($_SESSION['usertype'] == 'Admin') {
        redirect(BASE_URL . 'admin_dashboard.php');
    } else {
        redirect(BASE_URL . 'course_register_exam.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login_submit'])) {
    $username = mysqli_real_escape_string($connection_db, $_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT userid, username, password, usertype FROM users WHERE username = ?";
    $stmt = mysqli_prepare($connection_db, $query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            $_SESSION['userid'] = $user['userid'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['usertype'] = $user['usertype'];
            $_SESSION['success'] = "Welcome, " . htmlspecialchars($user['username']) . "!";
            if ($user['usertype'] == 'Admin') {
                redirect(BASE_URL . 'admin_dashboard.php');
            } else {
                redirect(BASE_URL . 'course_register_exam.php');
            }
        } else {
            $_SESSION['status'] = "Invalid username or password.";
            redirect(BASE_URL . 'index.php');
        }
    } else {
        $_SESSION['status'] = "Invalid username or password.";
        redirect(BASE_URL . 'index.php');
    }
    mysqli_stmt_close($stmt);
}

//include('includes/header.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login Form</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: url('images/laptop-bg.jpg') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header-text {
            position: absolute;
            top: 30px;
            left: 30px;
            font-family: 'Orbitron', sans-serif;
            font-size: 40px;
            font-weight: 700;
            line-height: 1.2;
        }

        .header-text span {
            display: block;
            /*animation: slideIn 2s ease-in-out infinite alternate;*/
        }

        .header-text .red {
            color: #ff416c;
        }

        .header-text .white {
            color: #ffffff;
        }

        /*@keyframes slideIn {
            0% {
                transform: translateX(-20px);
                opacity: 0.3;
            }
            100% {
                transform: translateX(0px);
                opacity: 1;
            }
        }*/

        .login-wrapper {
            width: 400px;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(8px);
            padding: 40px 35px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            color: white;
        }

        .login-wrapper h2 {
            text-align: center;
            margin-bottom: 25px;
            font-size: 24px;
            color: #ff416c;
        }

        .form-group {
            position: relative;
            margin-bottom: 20px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: none;
            border-radius: 5px;
            background: white;
            color: #333;
            font-size: 14px;
        }

        .form-group .input-icon {
            position: absolute;
            top: 50%;
            left: 12px;
            transform: translateY(-50%);
            color: #ff416c;
            font-size: 16px;
        }

        .options {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .options label,
        .options a {
            color: #ddd;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background: #ff416c;
            border: none;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            font-size: 15px;
            transition: background 0.3s ease;
        }

        .login-btn:hover {
            background: #e63958;
        }

        .alert {
            padding: 10px;
            border-radius: 5px;
            font-size: 14px;
            margin-bottom: 15px;
            text-align: center;
        }

        .alert-danger {
            background: #ffdddd;
            color: #d8000c;
        }

        .alert-success {
            background: #ddffdd;
            color: #4f8a10;
        }

        .bottom-text {
            text-align: center;
            font-size: 13px;
            margin-top: 15px;
        }

        .bottom-text a {
            color: #58a6ff;
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="header-text">
    <span class="red">ONLINE</span>
    <span class="white">EXAM</span>
    <span class="red">SYSTEM</span>
</div>

<div class="login-wrapper">
    <h2>Login Here</h2>

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

    <form action="<?= BASE_URL ?>index.php" method="POST">
        <div class="form-group">
            <span class="input-icon"><i class="fas fa-user"></i></span>
            <input type="text" name="username" placeholder="Username" required>
        </div>

        <div class="form-group">
            <span class="input-icon"><i class="fas fa-lock"></i></span>
            <input type="password" name="password" placeholder="Password" required>
        </div>

        <div class="options">
            <a href="#">Forgot Password?</a>
            <label><input type="checkbox"> Remember Me</label>
        </div>

        <button type="submit" name="login_submit" class="login-btn">LOGIN</button>
    </form>

    <div class="bottom-text">
        Don’t have an account? <a href="register.php">Register</a>
    </div>
</div>

</body>
</html>

<?php include('includes/footer.php'); ?>
