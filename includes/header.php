<?php
// includes/header.php
// This file assumes that session_start() has already been called
// and config.php (which defines BASE_URL) has been included
// in the main PHP script that calls this header.
// Example usage in your main PHP files (e.g., index.php, course_register_exam.php):
// include_once('config.php'); // This typically includes session_start()
// include_once('includes/header.php');
// include_once('function.php'); // Include helper functions if needed after header, but before content
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Exam System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* Global Styles */
        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #e9ecef; /* Light gray background for a softer feel */
            color: #343a40; /* Darker text for readability */
            line-height: 1.6;
        }

        h1, h2, h3, h4, h5, h6 {
            font-weight: 600; /* Slightly bolder headings */
            color: #212529; /* Even darker headings */
        }

        /* Navbar Styling */
        .navbar {
            background-color: #2c3e50; /* A deep, professional blue/gray */
            box-shadow: 0 2px 5px rgba(0,0,0,0.2); /* Subtle shadow for depth */
        }
        .navbar-brand, .nav-link {
            color: #ecf0f1 !important; /* Lighter text for contrast */
            transition: color 0.3s ease; /* Smooth hover effect */
        }
        .nav-link:hover {
            color: #a3ccff !important; /* Brighter color on hover */
        }
        .navbar-nav .nav-link.active {
            font-weight: bold;
            color: #85c1e9 !important; /* Highlight active link */
        }

        /* Main Container Padding */
        .pcoded-main-container {
            padding-top: 80px; /* More space from fixed navbar */
            padding-bottom: 30px;
            min-height: calc(100vh - 56px); /* Ensures content pushes footer down */
        }

        /* Card Enhancements */
        .card-shadow {
            box-shadow: 0 8px 16px rgba(0,0,0,0.15); /* More pronounced shadow */
            border-radius: 12px; /* Softer rounded corners */
            overflow: hidden;
            background-color: #ffffff;
            border: none; /* Remove default border */
        }
        .card-header {
            background-color: #f8f9fa; /* Lighter header background */
            border-bottom: 1px solid #dee2e6;
            font-size: 1.15em;
            padding: 1.25rem 1.5rem;
            color: #495057; /* Slightly darker header text */
            border-top-left-radius: 12px; /* Match card border-radius */
            border-top-right-radius: 12px;
        }
        .card-body {
            padding: 1.5rem; /* More internal spacing */
        }

        /* Table Enhancements */
        .table-responsive {
            border-radius: 8px; /* Rounded corners for tables too */
            overflow: hidden; /* Ensures rounded corners apply to content */
            border: 1px solid #dee2e6; /* Light border around responsive tables */
        }
        .table {
            margin-bottom: 0; /* Remove default table bottom margin */
        }
        .table thead th {
            background-color: #34495e; /* Darker header for tables */
            color: #ecf0f1; /* White text on dark header */
            border-bottom: none; /* Remove border */
            padding: 0.75rem 1.25rem;
        }
        .table tbody tr:nth-of-type(odd) {
            background-color: rgba(0,0,0,.03); /* Subtle stripe effect */
        }
        .table tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.08); /* Light blue hover effect */
            cursor: pointer;
        }
        .table td, .table th {
            vertical-align: middle; /* Center content vertically */
            border-top: 1px solid #e3e6f0; /* Lighter cell borders */
        }

        /* Alert Messages */
        .alert {
            margin-top: 20px;
            border-radius: 8px;
            padding: 1rem 1.25rem;
            font-size: 0.95em;
            line-height: 1.4;
        }
        .alert-success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
        .alert-danger { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .alert-info { background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .alert-warning { background-color: #fff3cd; border-color: #ffeeba; color: #856404; }


        /* Form Elements */
        .form-control {
            border-radius: 6px;
            border-color: #ced4da;
            padding: 0.75rem 1rem;
            font-size: 0.95em;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        .form-group label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #495057;
        }

        /* Buttons */
        .btn {
            border-radius: 6px;
            padding: 0.65rem 1.25rem;
            font-size: 0.9em;
            font-weight: 600;
            transition: all 0.2s ease-in-out;
        }
        .btn-primary { background-color: #007bff; border-color: #007bff; }
        .btn-primary:hover { background-color: #0056b3; border-color: #004085; transform: translateY(-1px); }
        .btn-success { background-color: #28a745; border-color: #28a745; }
        .btn-success:hover { background-color: #218838; border-color: #1e7e34; transform: translateY(-1px); }
        .btn-info { background-color: #17a2b8; border-color: #17a2b8; }
        .btn-info:hover { background-color: #138496; border-color: #117a8b; transform: translateY(-1px); }
        .btn-warning { background-color: #ffc107; border-color: #ffc107; color: #212529; }
        .btn-warning:hover { background-color: #e0a800; border-color: #cc9900; transform: translateY(-1px); }
        .btn-danger { background-color: #dc3545; border-color: #dc3545; }
        .btn-danger:hover { background-color: #c82333; border-color: #bd2130; transform: translateY(-1px); }
        .btn-secondary { background-color: #6c757d; border-color: #6c757d; }
        .btn-secondary:hover { background-color: #5a6268; border-color: #545b62; transform: translateY(-1px); }


        /* Admin Dashboard Cards (Specific Enhancements) */
        .col-md-4 .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .col-md-4 .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.2);
        }
        .card .fa-3x {
            font-size: 2.8rem; /* Adjust icon size slightly */
        }

        /* Question Navigation Grid (Exam Page) */
        .question-nav-grid .btn {
            font-size: 0.8em;
            padding: 6px 4px;
        }
        .question-nav-grid .btn.btn-warning {
            font-weight: bold;
            box-shadow: 0 0 0 2px #fff, 0 0 0 4px #ffc107; /* Highlight current question */
        }

        /* Additional UI improvements */
        .btn-group-actions .btn {
            margin-right: 5px; /* Space between action buttons in table */
        }
        .btn-group-actions .btn:last-child {
            margin-right: 0;
        }
        /* Specific styles for review_student_answers.php for correct/incorrect questions */
        .card.border-success { border-left: 5px solid #28a745 !important; }
        .card.border-danger { border-left: 5px solid #dc3545 !important; }
        .card.border-secondary { border-left: 5px solid #6c757d !important; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <a class="navbar-brand" href="<?= BASE_URL ?>">Online Exam System</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mr-auto">
                <?php if (isset($_SESSION['userid'])): ?>
                    <?php if ($_SESSION['usertype'] == 'Student'): ?>
                        <li class="nav-item <?= (basename($_SERVER['PHP_SELF']) == 'course_register_exam.php') ? 'active' : ''; ?>">
                            <a class="nav-link" href="<?= BASE_URL ?>course_register_exam.php">My Exams</a>
                        </li>
                        <li class="nav-item <?= (basename($_SERVER['PHP_SELF']) == 'student_results.php') ? 'active' : ''; ?>">
                            <a class="nav-link" href="<?= BASE_URL ?>student_results.php">My Results</a>
                        </li>
                    <?php elseif ($_SESSION['usertype'] == 'Admin'): ?>
                        <li class="nav-item <?= (basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php') ? 'active' : ''; ?>">
                            <a class="nav-link" href="<?= BASE_URL ?>admin_dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item <?= (basename($_SERVER['PHP_SELF']) == 'manage_exams.php') ? 'active' : ''; ?>">
                            <a class="nav-link" href="<?= BASE_URL ?>manage_exams.php">Manage Exams</a>
                        </li>
                        <li class="nav-item <?= (basename($_SERVER['PHP_SELF']) == 'manage_questions.php') ? 'active' : ''; ?>">
                            <a class="nav-link" href="<?= BASE_URL ?>manage_questions.php">Manage Questions</a>
                        </li>
                        <li class="nav-item <?= (basename($_SERVER['PHP_SELF']) == 'manage_students.php') ? 'active' : ''; ?>">
                            <a class="nav-link" href="<?= BASE_URL ?>manage_students.php">Manage Students</a>
                        </li>
                        <li class="nav-item <?= (basename($_SERVER['PHP_SELF']) == 'view_results.php') ? 'active' : ''; ?>">
                            <a class="nav-link" href="<?= BASE_URL ?>view_results.php">View All Exam Results</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item <?= (basename($_SERVER['PHP_SELF']) == 'change_password.php') ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?= BASE_URL ?>change_password.php">Change Password</a>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ml-auto">
                <?php if (isset($_SESSION['userid'])): ?>
                    <li class="nav-item">
                        <span class="nav-link">Welcome, <?= htmlspecialchars($_SESSION['username']); ?> (<?= htmlspecialchars($_SESSION['usertype']); ?>)</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?= BASE_URL ?>index.php">Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    <div class="pcoded-main-container">
        <div class="pcoded-wrapper">
            <div class="pcoded-content">
                <div class="pcoded-inner-content">
                    <div class="main-body">
                        <div class="page-wrapper">
