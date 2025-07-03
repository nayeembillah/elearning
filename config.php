<?php
// config.php

// Temporary: Enable error display for debugging. REMOVE OR SET TO 0 IN PRODUCTION.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// MUST be the very first thing after <?php tag in files that need session access
// It initiates or resumes the session.
session_start();

// --- Database Connection Settings ---
$host = "localhost"; // Your database host (e.g., 'localhost' or '127.0.0.1')
$username = "root";  // Your database username
$password = "Fsibl@100";      // Your database password
$dbname = "elearning"; // The name of your database

// Create database connection
$connection_db = mysqli_connect($host, $username, $password, $dbname);

// Check connection
if (!$connection_db) {
    // If connection fails, terminate script and display error
    die("Connection failed: " . mysqli_connect_error());
}

// Set character set for proper UTF-8 handling, crucial for international characters
mysqli_set_charset($connection_db, "utf8");

// --- Base URL Definition ---
// This is critical for all redirects and links within your application.
// It should be the exact URL path to your project's root folder.
// Based on previous discussions: https://10.20.22.156/online_exam_system/
define('BASE_URL', 'https://ictexam.fsiblbd.com/');

// --- Helper Function for Redirection ---
// This function encapsulates the header redirection and ensures script termination.
function redirect($url) {
    header("Location: " . $url);
    exit(); // IMPORTANT: Always exit after a header redirect to prevent further script execution
}
?>
