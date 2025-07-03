<?php
include('config.php'); // This includes session_start()
include('includes/header.php');
include('function.php');

// Ensure user is logged in and is a student
if (!isset($_SESSION['userid']) || $_SESSION['usertype'] !== 'Student') {
    $_SESSION['status'] = "Access Denied. Please log in as a student.";
    redirect(BASE_URL . 'index.php');
}

$student_id = $_SESSION['userid'];
$exam_id = $_GET['exam_id'] ?? null;

if (!$exam_id) {
    $_SESSION['status'] = "No exam selected for review.";
    redirect(BASE_URL . 'course_register_exam.php');
}

// Check if exam attempt exists and is still "started"
if (!isset($_SESSION['exam_attempts_data'][$exam_id])) {
    $_SESSION['status'] = "No active exam session found. Please take the exam first.";
    redirect(BASE_URL . 'course_register_exam.php');
}

// Fetch exam details
$exam_query = "SELECT c_name, c_duration FROM course WHERE c_id = ? AND c_type = 'exam'";
$stmt = mysqli_prepare($connection_db, $exam_query);
mysqli_stmt_bind_param($stmt, "s", $exam_id);
mysqli_stmt_execute($stmt);
$exam_result = mysqli_stmt_get_result($stmt);
$exam_data = mysqli_fetch_assoc($exam_result);

if (!$exam_data) {
    $_SESSION['status'] = "Exam not found or not active.";
    redirect(BASE_URL . 'course_register_exam.php');
}

// Timer check on review page as well
$exam_start_timestamp = $_SESSION['exam_attempts_data'][$exam_id]['start_time'];
$exam_duration_seconds = $exam_data['c_duration'] * 60;
$time_remaining = $exam_duration_seconds - (time() - $exam_start_timestamp);

if ($time_remaining <= 0) {
    $_SESSION['status'] = "Time's up! Your exam has been automatically submitted.";
    redirect(BASE_URL . 'process_exam_submission.php?exam_id=' . urlencode($exam_id));
}


// Get all questions for the exam
$questions_query = "SELECT question_id, question_text, question_type, order_num FROM quiz_question WHERE exam_id = ? ORDER BY order_num ASC";
$stmt = mysqli_prepare($connection_db, $questions_query);
mysqli_stmt_bind_param($stmt, "s", $exam_id);
mysqli_stmt_execute($stmt);
$questions_result = mysqli_stmt_get_result($stmt);
$all_questions = [];
while ($row = mysqli_fetch_assoc($questions_result)) {
    $all_questions[] = $row;
}

// Get student's answers from session
$student_answers = $_SESSION['exam_answers'][$exam_id] ?? [];
$unanswered_count = 0;
?>

<div class="card-shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">
            Review Exam: <?php echo htmlspecialchars($exam_data['c_name']); ?>
        </h6>
        <div class="text-right">
            <h5 class="mb-0">Time Remaining: <span id="exam-timer" class="text-danger"></span></h5>
        </div>
    </div>
    <div class="card-body">
        <h4 class="mb-3">Summary of Your Answers</h4>
        <ul class="list-group mb-4">
            <?php foreach ($all_questions as $index => $question) {
                $question_num = $index + 1;
                $answer_key = $question['question_id'];
                $answer = $student_answers[$answer_key] ?? '';
                $answer_text = "Not Answered";
                $list_item_class = 'list-group-item-danger'; // Default to unanswered

                if (!empty($answer)) {
                    $list_item_class = 'list-group-item-success'; // Answered
                    if ($question['question_type'] == 'MCQ') {
                        // Fetch option text for MCQ
                        $option_text_query = "SELECT option_text FROM question_options WHERE option_id = ?";
                        $stmt_option = mysqli_prepare($connection_db, $option_text_query);
                        mysqli_stmt_bind_param($stmt_option, "i", $answer);
                        mysqli_stmt_execute($stmt_option);
                        $option_result = mysqli_stmt_get_result($stmt_option);
                        $option_row = mysqli_fetch_assoc($option_result);
                        $answer_text = htmlspecialchars($option_row['option_text'] ?? 'Invalid Option');
                    } else {
                        // For other types, just show the response
                        $answer_text = htmlspecialchars($answer);
                    }
                } else {
                    $unanswered_count++;
                }
            ?>
                <li class="list-group-item d-flex justify-content-between align-items-center <?php echo $list_item_class; ?>">
                    <span>
                        Q<?php echo htmlspecialchars($question_num); ?>: <?php echo nl2br(htmlspecialchars($question['question_text'])); ?>
                        <br>
                        Your Answer: <strong><?php echo $answer_text; ?></strong>
                    </span>
                    <a href="<?= BASE_URL ?>take_exam.php?exam_id=<?php echo htmlspecialchars($exam_id); ?>&q=<?php echo htmlspecialchars($question_num); ?>" class="btn btn-sm btn-info">Edit</a>
                </li>
            <?php } ?>
        </ul>

        <?php if ($unanswered_count > 0) { ?>
            <div class="alert alert-warning">
                You have **<?php echo $unanswered_count; ?>** question(s) unanswered. Please review them before submitting.
            </div>
        <?php } ?>

        <form action="<?= BASE_URL ?>process_exam_submission.php" method="POST">
            <input type="hidden" name="exam_id" value="<?php echo htmlspecialchars($exam_id); ?>">
            <button type="submit" name="submit_exam" class="btn btn-success btn-lg btn-block mt-4">
                Submit Exam
            </button>
        </form>
    </div>
</div>

<script>
    // JavaScript for Timer (similar to take_exam.php)
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
            window.location.href = baseUrl + 'process_exam_submission.php?exam_id=' + examId;
            return;
        }
        timeRemaining--;
    }

    var timerInterval = setInterval(updateTimer, 1000);
    updateTimer(); // Initial call
</script>

<?php include('includes/footer.php'); ?>
