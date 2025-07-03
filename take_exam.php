<?php
// take_exam.php - Displays individual exam questions, handles saving answers, and timer.

// 1. MUST be first: Include config.php to start the session, connect to DB, and define BASE_URL.
include('config.php');
// 2. Include function.php for helper functions.
include('function.php');

// 3. Ensure user is logged in and is a student. This check includes a redirect if unauthorized.
if (!isset($_SESSION['userid']) || $_SESSION['usertype'] !== 'Student') {
    $_SESSION['status'] = "Access Denied. Please log in as a student.";
    redirect(BASE_URL . 'index.php');
}

$student_id = $_SESSION['userid'];
$exam_id = $_GET['exam_id'] ?? null; // Get exam_id from URL
$question_num = (int)($_GET['q'] ?? 1); // Current question number, default to 1

if (!$exam_id) {
    $_SESSION['status'] = "No exam selected.";
    redirect(BASE_URL . 'course_register_exam.php');
}

// Fetch exam details to get duration
$exam_query = "SELECT c_id, c_name, c_duration FROM course WHERE c_id = ? AND c_type = 'exam'";
$stmt = mysqli_prepare($connection_db, $exam_query);
mysqli_stmt_bind_param($stmt, "s", $exam_id);
mysqli_stmt_execute($stmt);
$exam_result = mysqli_stmt_get_result($stmt);
$exam_data = mysqli_fetch_assoc($exam_result);
mysqli_stmt_close($stmt); // Close statement after use

if (!$exam_data) {
    $_SESSION['status'] = "Exam not found or not active.";
    redirect(BASE_URL . 'course_register_exam.php');
}

// --- Exam Attempt and Timer Management (before any output) ---
if (!isset($_SESSION['exam_attempts_data'][$exam_id])) {
    // Check if an existing 'started' attempt exists for this student and exam
    $check_attempt_query = "SELECT attempt_id, start_time FROM exam_attempts WHERE student_id = ? AND exam_id = ? AND status = 'started' ORDER BY start_time DESC LIMIT 1";
    $stmt_check_attempt = mysqli_prepare($connection_db, $check_attempt_query);
    mysqli_stmt_bind_param($stmt_check_attempt, "is", $student_id, $exam_id);
    mysqli_stmt_execute($stmt_check_attempt);
    $check_attempt_result = mysqli_stmt_get_result($stmt_check_attempt);

    if ($check_attempt_data = mysqli_fetch_assoc($check_attempt_result)) {
        // Resume existing attempt
        $_SESSION['exam_attempts_data'][$exam_id] = [
            'attempt_id' => $check_attempt_data['attempt_id'],
            'start_time' => strtotime($check_attempt_data['start_time'])
        ];
    } else {
        // Check enrollment status before starting a new attempt
        $check_enrollment_query = "SELECT en_status FROM course_enrollment WHERE student_id = ? AND course_id = ?";
        $stmt_enrollment_status = mysqli_prepare($connection_db, $check_enrollment_query);
        mysqli_stmt_bind_param($stmt_enrollment_status, "is", $student_id, $exam_id);
        mysqli_stmt_execute($stmt_enrollment_status);
        $enrollment_status_result = mysqli_stmt_get_result($stmt_enrollment_status);
        $enrollment_row = mysqli_fetch_assoc($enrollment_status_result);
        mysqli_stmt_close($stmt_enrollment_status);

        if (!$enrollment_row || ($enrollment_row['en_status'] != 'enrolled' && $enrollment_row['en_status'] != 'started')) {
            $_SESSION['status'] = "You are not enrolled or have already completed this exam.";
            redirect(BASE_URL . 'course_register_exam.php');
        }

        // Proceed to start new attempt
        $start_time = date('Y-m-d H:i:s');
        $insert_attempt_query = "INSERT INTO exam_attempts (student_id, exam_id, start_time, status) VALUES (?, ?, ?, 'started')";
        $stmt_insert_attempt = mysqli_prepare($connection_db, $insert_attempt_query);
        mysqli_stmt_bind_param($stmt_insert_attempt, "iss", $student_id, $exam_id, $start_time);
        mysqli_stmt_execute($stmt_insert_attempt);

        if (mysqli_stmt_affected_rows($stmt_insert_attempt) > 0) {
            $_SESSION['exam_attempts_data'][$exam_id] = [
                'attempt_id' => mysqli_insert_id($connection_db),
                'start_time' => time() // Use current PHP time for session
            ];
            // Also update course_enrollment status if it was just 'enrolled'
            $update_enrollment_status = "UPDATE course_enrollment SET en_status = 'started' WHERE student_id = ? AND course_id = ? AND en_status = 'enrolled'";
            $stmt_update_enroll = mysqli_prepare($connection_db, $update_enrollment_status);
            mysqli_stmt_bind_param($stmt_update_enroll, "is", $student_id, $exam_id);
            mysqli_stmt_execute($stmt_update_enroll);
            mysqli_stmt_close($stmt_update_enroll);
        } else {
            $_SESSION['status'] = "Failed to start exam attempt. Please try again.";
            redirect(BASE_URL . 'course_register_exam.php');
        }
    }
}

$attempt_id = $_SESSION['exam_attempts_data'][$exam_id]['attempt_id'];
$exam_start_timestamp = $_SESSION['exam_attempts_data'][$exam_id]['start_time'];
$exam_duration_seconds = $exam_data['c_duration'] * 60; // Convert minutes to seconds
$time_remaining = $exam_duration_seconds - (time() - $exam_start_timestamp);

// If time is up, auto-submit the exam (before any output)
if ($time_remaining <= 0) {
    $_SESSION['status'] = "Time's up! Your exam has been automatically submitted.";
    redirect(BASE_URL . 'process_exam_submission.php?exam_id=' . urlencode($exam_id));
}

// Fetch all questions for this exam, ordered by order_num
$questions_query = "SELECT question_id, question_text, question_type, order_num FROM quiz_question WHERE exam_id = ? ORDER BY order_num ASC";
$stmt_questions = mysqli_prepare($connection_db, $questions_query);
mysqli_stmt_bind_param($stmt_questions, "s", $exam_id);
mysqli_stmt_execute($stmt_questions);
$questions_result = mysqli_stmt_get_result($stmt_questions);
$all_questions = [];
while ($row = mysqli_fetch_assoc($questions_result)) {
    $all_questions[] = $row;
}
mysqli_stmt_close($stmt_questions); // Close statement after use

$total_questions = count($all_questions);

if ($total_questions == 0) {
    $_SESSION['status'] = "No questions found for this exam.";
    redirect(BASE_URL . 'course_register_exam.php');
}

// Initialize session for exam answers if not already done
if (!isset($_SESSION['exam_answers'][$exam_id])) {
    $_SESSION['exam_answers'][$exam_id] = [];
}


// --- Process answer submission for the current question (before rendering HTML) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_answer'])) {
    $posted_question_id = $_POST['question_id'];
    $student_response = $_POST['answer'] ?? '';

    // Store the answer in the session for this specific exam and question
    $_SESSION['exam_answers'][$exam_id][$posted_question_id] = $student_response;

    // Handle navigation based on button value
    $nav_action = $_POST['save_answer']; // 'prev', 'next', 'save'
    if ($nav_action === 'next' && $question_num < $total_questions) {
        $question_num++;
    } elseif ($nav_action === 'prev' && $question_num > 1) {
        $question_num--;
    }
    // Redirect to the new question number. This MUST be able to execute before any HTML output.
    redirect(BASE_URL . 'take_exam.php?exam_id=' . urlencode($exam_id) . '&q=' . $question_num);
}
// --- End of POST processing and potential redirect ---


// Determine the current question array index (0-based) for display
$current_question_index = $question_num - 1;
if ($current_question_index < 0 || $current_question_index >= $total_questions) {
    // If question_num became invalid after a redirect, correct it
    $_SESSION['status'] = "Invalid question number. Redirecting to first question.";
    redirect(BASE_URL . 'take_exam.php?exam_id=' . urlencode($exam_id) . '&q=1');
}

$current_question = $all_questions[$current_question_index];
$question_id = $current_question['question_id'];

// Get the student's previously saved answer for the current question from session
$student_saved_answer = $_SESSION['exam_answers'][$exam_id][$question_id] ?? '';

// Fetch options for MCQ questions
$options_query = "SELECT option_id, option_text FROM question_options WHERE question_id = ?";
$stmt_options = mysqli_prepare($connection_db, $options_query);
mysqli_stmt_bind_param($stmt_options, "i", $question_id);
mysqli_stmt_execute($stmt_options);
$options_result = mysqli_stmt_get_result($stmt_options);
$options = [];
while ($row = mysqli_fetch_assoc($options_result)) {
    $options[] = $row;
}
mysqli_stmt_close($stmt_options); // Close statement after use

// --- Now include the header HTML. This comes AFTER all PHP logic that might redirect. ---
include('includes/header.php');
?>

<div class="card-shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">
            Exam: <?php echo htmlspecialchars($exam_data['c_name']); ?>
        </h6>
        <div class="text-right">
            <h5 class="mb-0">Time Remaining: <span id="exam-timer" class="text-danger"></span></h5>
        </div>
    </div>
    <div class="card-body">
        <?php
        if (isset($_SESSION['status']) && $_SESSION['status'] != '') {
            echo '<div class="alert alert-danger">' . $_SESSION['status'] . '</div>';
            unset($_SESSION['status']);
        }
        ?>
        <form id="exam-form" action="<?= BASE_URL ?>take_exam.php?exam_id=<?php echo htmlspecialchars($exam_id); ?>&q=<?php echo htmlspecialchars($question_num); ?>" method="POST">
            <input type="hidden" name="question_id" value="<?php echo htmlspecialchars($question_id); ?>">

            <div class="form-group mb-4">
                <h4>Question <?php echo htmlspecialchars($question_num); ?> of <?php echo htmlspecialchars($total_questions); ?></h4>
                <p class="lead"><?php echo nl2br(htmlspecialchars($current_question['question_text'])); ?></p>
            </div>

            <div class="form-group">
                <?php if ($current_question['question_type'] == 'MCQ') { ?>
                    <?php foreach ($options as $option) { ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="answer" id="option<?php echo htmlspecialchars($option['option_id']); ?>" value="<?php echo htmlspecialchars($option['option_id']); ?>"
                                <?php echo ($student_saved_answer == $option['option_id']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="option<?php echo htmlspecialchars($option['option_id']); ?>">
                                <?php echo htmlspecialchars($option['option_text']); ?>
                            </label>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>

            <div class="mt-4 d-flex justify-content-between">
                <?php if ($question_num > 1) { ?>
                    <button type="submit" name="save_answer" value="prev" class="btn btn-secondary">Previous</button>
                <?php } else { ?>
                    <button type="button" class="btn btn-secondary" disabled>Previous</button>
                <?php } ?>

                <button type="submit" name="save_answer" value="save" class="btn btn-info">Save & Stay</button>

                <?php if ($question_num < $total_questions) { ?>
                    <button type="submit" name="save_answer" value="next" class="btn btn-primary">Next</button>
                <?php } else { ?>
                    <a href="<?= BASE_URL ?>exam_review.php?exam_id=<?php echo htmlspecialchars($exam_id); ?>" class="btn btn-success">Review & Finish</a>
                <?php } ?>
            </div>
        </form>

        <hr class="my-4">
        <h5>Question Navigation</h5>
        <div class="question-nav-grid">
            <?php
            foreach ($all_questions as $idx => $q_nav) {
                $q_nav_num = $idx + 1;
                $nav_btn_class = 'btn-secondary'; // Default for unanswered/unvisited
                if (isset($_SESSION['exam_answers'][$exam_id][$q_nav['question_id']]) && !empty($_SESSION['exam_answers'][$exam_id][$q_nav['question_id']])) {
                    $nav_btn_class = 'btn-success'; // Answered
                }
                if ($q_nav_num == $question_num) {
                    $nav_btn_class = 'btn-warning'; // Current question
                }
            ?>
                <a href="<?= BASE_URL ?>take_exam.php?exam_id=<?php echo htmlspecialchars($exam_id); ?>&q=<?php echo $q_nav_num; ?>" class="btn <?= $nav_btn_class; ?>"><?php echo $q_nav_num; ?></a>
            <?php } ?>
        </div>
    </div>
</div>

<script>
    // JavaScript for Timer
    var timeRemaining = <?php echo (int)$time_remaining; ?>;
    var timerElement = document.getElementById('exam-timer');
    var examId = "<?php echo htmlspecialchars($exam_id); ?>";
    var baseUrl = "<?php echo BASE_URL; ?>";

    function updateTimer() {
        var minutes = Math.floor(timeRemaining / 60);
        var seconds = timeRemaining % 60;

        timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

        if (timeRemaining <= 0) {
            clearInterval(timerInterval);
            alert("Time's up! Your exam will be submitted automatically.");
            // Force form submission to process_exam_submission.php
            window.location.href = baseUrl + 'process_exam_submission.php?exam_id=' + examId;
            return;
        }
        timeRemaining--;
    }

    var timerInterval = setInterval(updateTimer, 1000);
    updateTimer(); // Initial call to display immediately

    // This script is only for handling the timer and auto-submission
    // The form submission with 'save_answer' and redirection is handled by PHP.
</script>

<?php include('includes/footer.php'); ?>