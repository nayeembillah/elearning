<?php
// logout.php

// 1. Include config.php first. This is crucial!
//    It starts the session (session_start()), defines BASE_URL, and establishes the DB connection.
include('config.php');

// Note: No need for a separate session_start() here because config.php already calls it.

// Clear all session variables for the current session.
session_unset();

// Destroy the current session completely.
session_destroy();

// Redirect the user to the login page.
// The `redirect()` function is defined in config.php and handles the header redirect and exit.
redirect(BASE_URL . 'index.php');

// No further code is needed after a redirect.
?>
