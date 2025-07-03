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

/*include('includes/header.php');*/
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login Form</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&family=Orbitron:wght@700&family=Freestyle+Script&family=Bungee&display=swap" rel="stylesheet">
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

        .logo {
            position: absolute;
            top: 20px;
            left: 40px;
            width: 95px;
            height: auto;
            z-index: 10000;
        }

        .header-text {
            position: absolute;
            top: 140px;
            left: 30px;
            font-family: 'Impact', cursive;
            font-size: 40px;
            font-weight: 700;
            line-height: 1.2;
        }

        .header-text span {
            display: block;
            font-family: 'Impact', cursive !important; /* Force Bungee inside spans */
        }

        .header-text .red {
            color: #ff416c;
        }

        .header-text .white {
            color: #ffffff;
        }

        .punchline {
            position: absolute;
            top: 40%;
            right: 50px;
            transform: translateY(-50%);
            color: #fff;
            text-align: right;
        }

        .punchline .line1 {
            font-family: 'Freestyle Script', cursive;
            font-size: 42px;
            font-weight: 700;
        }

        .punchline .line2 {
            font-family: 'Arial Black', sans-serif;
            font-size: 42px;
            font-weight: 900;
            margin-top: 5px;
        }

        .typed-words {
            font-weight: bold;
        }

        .word1 { color: #ff416c; }
        .word2 { color: #00ffe5; }
        .word3 { color: #ffe600; }
        .word4 { color: #ff6f00; }
        .word5 { color: #00ff2f; }

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

        .corner-gif {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 200px;
            height: auto;
            z-index: 9999;
        }
    </style>
</head>
<body>

<!-- Logo in top left corner -->
<img src="images/logo.png" alt="Logo" class="logo">

<!-- Header text below the logo -->
<div class="header-text">
    <span class="red">ONLINE</span>
    <span class="white">EXAM</span>
    <span class="red">SYSTEM</span>
</div>

<div class="punchline">
    <div class="line1">Here You Can:</div>
    <div class="line2"><span class="typed-words"></span></div>
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
        Dont have an account? <a href="register.php">Register</a>
    </div>
</div>

<img src="images/gif.gif" alt="Corner GIF" class="corner-gif">

<script>
    const words = [
        { text: 'Learn', class: 'word1' },
        { text: 'Practice', class: 'word2' },
        { text: 'Solve', class: 'word3' },
        { text: 'Grow', class: 'word4' },
        { text: 'Achieve', class: 'word5' }
    ];

    const typedSpan = document.querySelector('.typed-words');

    let wordIndex = 0;
    let charIndex = 0;
    let currentWord = words[wordIndex].text;
    let isDeleting = false;

    function type() {
        const displayText = currentWord.substring(0, charIndex);
        typedSpan.innerHTML = `<span class="${words[wordIndex].class}">${displayText}</span>`;

        if (!isDeleting && charIndex < currentWord.length) {
            charIndex++;
            setTimeout(type, 150);
        } else if (isDeleting && charIndex > 0) {
            charIndex--;
            setTimeout(type, 100);
        } else {
            if (!isDeleting) {
                isDeleting = true;
                setTimeout(type, 1000);
            } else {
                isDeleting = false;
                wordIndex = (wordIndex + 1) % words.length;
                currentWord = words[wordIndex].text;
                setTimeout(type, 500);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', type);
</script>

</body>
</html>

<?php include('includes/footer.php'); ?>
