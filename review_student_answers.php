<?php
// review_student_answers.php - Shows student's answers with correct/incorrect highlighting

include('config.php'); // MUST be first. Starts session, connects to DB, defines BASE_URL
include('function.php'); // Include helper functions

// Ensure user is logged in and is a student
if (!isset($_SESSION['userid']) || $_SESSION['usertype'] !== 'Student') {
    $_SESSION['status'] = "Access Denied. Please log in as a student.";
    redirect(BASE_URL . 'index.php');
}

$student_id = $_SESSION['userid'];
$exam_id = $_GET['exam_id'] ?? null;
$attempt_id = $_GET['attempt_id'] ?? null;

if (!$exam_id || !$attempt_id) {
    $_SESSION['status'] = "Invalid exam or attempt details for review.";
    redirect(BASE_URL . 'student_results.php');
}

// Verify that the attempt belongs to the logged-in student and is completed
$verify_attempt_query = "SELECT ea.attempt_id, c.c_name, ea.score, ce.mark_perct, ce.en_status
                         FROM exam_attempts ea
                         JOIN course c ON ea.exam_id = c.c_id
                         LEFT JOIN course_enrollment ce ON ea.student_id = ce.student_id AND ea.exam_id = ce.course_id
                         WHERE ea.attempt_id = ? AND ea.student_id = ? AND ea.exam_id = ? AND ea.status IN ('submitted', 'graded')";
$stmt_verify = mysqli_prepare($connection_db, $verify_attempt_query);
mysqli_stmt_bind_param($stmt_verify, "iis", $attempt_id, $student_id, $exam_id);
mysqli_stmt_execute($stmt_verify);
$attempt_details_result = mysqli_stmt_get_result($stmt_verify);
$attempt_details = mysqli_fetch_assoc($attempt_details_result);
mysqli_stmt_close($stmt_verify);

if (!$attempt_details) {
    $_SESSION['status'] = "Review not available for this exam attempt or you don't have permission.";
    redirect(BASE_URL . 'student_results.php');
}

$exam_name = htmlspecialchars($attempt_details['c_name']);
$student_score = htmlspecialchars($attempt_details['score'] ?? 'N/A');
$student_percentage = htmlspecialchars(round($attempt_details['mark_perct'] ?? 0, 2));
$final_status = htmlspecialchars($attempt_details['en_status'] ?? 'N/A');

// Fetch all questions for this exam
$questions_query = "SELECT qq.question_id, qq.question_text, qq.question_type, qq.marks, qq.order_num
                    FROM quiz_question qq
                    WHERE qq.exam_id = ?
                    ORDER BY qq.order_num ASC";
$stmt_questions = mysqli_prepare($connection_db, $questions_query);
mysqli_stmt_bind_param($stmt_questions, "s", $exam_id);
mysqli_stmt_execute($stmt_questions);
$questions_result = mysqli_stmt_get_result($stmt_questions);
$all_questions = [];
while ($row = mysqli_fetch_assoc($questions_result)) {
    $all_questions[] = $row;
}
mysqli_stmt_close($stmt_questions);

// Fetch all student answers for this attempt
$student_answers_query = "SELECT question_id, student_response, is_correct_submission
                          FROM student_answers
                          WHERE attempt_id = ?";
$stmt_answers = mysqli_prepare($connection_db, $student_answers_query);
mysqli_stmt_bind_param($stmt_answers, "i", $attempt_id);
mysqli_stmt_execute($stmt_answers);
$student_answers_result = mysqli_stmt_get_result($stmt_answers);
$student_answers_map = [];
while ($row = mysqli_fetch_assoc($student_answers_result)) {
    $student_answers_map[$row['question_id']] = [
        'student_response' => $row['student_response'],
        'is_correct_submission' => $row['is_correct_submission']
    ];
}
mysqli_stmt_close($stmt_answers);

// Fetch all options for all questions in this exam (for MCQs) and map correct ones
$all_options_query = "SELECT qo.option_id, qo.question_id, qo.option_text, qo.is_correct
                      FROM question_options qo
                      JOIN quiz_question qq ON qo.question_id = qq.question_id
                      WHERE qq.exam_id = ?";
$stmt_all_options = mysqli_prepare($connection_db, $all_options_query);
mysqli_stmt_bind_param($stmt_all_options, "s", $exam_id);
mysqli_stmt_execute($stmt_all_options);
$all_options_result = mysqli_stmt_get_result($stmt_all_options);
$question_options_map = [];
$correct_options_map = [];
while ($row = mysqli_fetch_assoc($all_options_result)) {
    $question_options_map[$row['question_id']][] = $row;
    if ($row['is_correct']) {
        $correct_options_map[$row['question_id']] = $row['option_id'];
    }
}
mysqli_stmt_close($stmt_all_options);


// --- Now include the header HTML ---
include('includes/header.php');
?>

<div class="card-shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Review Answers for: <?= $exam_name; ?></h6>
        <div class="text-right">
            <span class="mr-3">Score: <?= $student_score; ?></span>
            <span class="mr-3">Percentage: <?= $student_percentage; ?>%</span>
            <span>Status: <?= $final_status; ?></span>
        </div>
    </div>
    <div class="card-body">
        <?php
        if (isset($_SESSION['status']) && $_SESSION['status'] != '') {
            echo '<div class="alert alert-danger">' . $_SESSION['status'] . '</div>';
            unset($_SESSION['status']);
        }
        ?>

        <?php if (!empty($all_questions)) : ?>
            <?php foreach ($all_questions as $index => $question) :
                $q_num = $index + 1;
                $question_id = $question['question_id'];
                $student_answer_data = $student_answers_map[$question_id] ?? ['student_response' => null, 'is_correct_submission' => null];
                $student_response_id = $student_answer_data['student_response'];
                $is_correct_answer = $student_answer_data['is_correct_submission'];
                $correct_option_id = $correct_options_map[$question_id] ?? null;

                $card_class = '';
                if ($is_correct_answer === 1) {
                    $card_class = 'border-success';
                } elseif ($is_correct_answer === 0) {
                    $card_class = 'border-danger';
                } else {
                    $card_class = 'border-secondary'; // Not answered or ungraded
                }
            ?>
                <div class="card mb-4 <?= $card_class; ?>">
                    <div class="card-header">
                        <h5 class="mb-0">Q<?= $q_num; ?>: <?= nl2br(htmlspecialchars($question['question_text'])); ?>
                            <span class="float-right badge badge-pill <?= ($is_correct_answer === 1 ? 'badge-success' : ($is_correct_answer === 0 ? 'badge-danger' : 'badge-secondary')); ?>">
                                <?= ($is_correct_answer === 1 ? 'Correct' : ($is_correct_answer === 0 ? 'Incorrect' : 'Not Answered')); ?>
                            </span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($question['question_type'] == 'MCQ') : ?>
                            <?php if (isset($question_options_map[$question_id])) : ?>
                                <p><strong>Options:</strong></p>
                                <ul class="list-unstyled">
                                    <?php foreach ($question_options_map[$question_id] as $option) :
                                        $option_text = htmlspecialchars($option['option_text']);
                                        $is_student_choice = ($student_response_id == $option['option_id']);
                                        $is_correct_choice = ($option['is_correct'] == 1);

                                        $li_class = '';
                                        if ($is_student_choice && $is_correct_answer === 1) {
                                            $li_class = 'text-success font-weight-bold'; // Student chose correctly
                                        } elseif ($is_student_choice && $is_correct_answer === 0) {
                                            $li_class = 'text-danger font-weight-bold'; // Student chose incorrectly
                                        } elseif ($is_correct_choice) {
                                            $li_class = 'text-success font-weight-bold'; // Correct option, student didn't choose it (or chose wrong)
                                        }
                                    ?>
                                        <li class="<?= $li_class; ?>">
                                            <i class="fas fa-<?= ($is_student_choice ? 'check-circle' : 'circle'); ?> mr-2"></i>
                                            <?= $option_text; ?>
                                            <?php if ($is_correct_choice && !$is_student_choice) : ?>
                                                <small class="text-success">(Correct Answer)</small>
                                            <?php endif; ?>
                                            <?php if ($is_student_choice && $is_correct_answer === 0) : ?>
                                                <small class="text-danger">(Your Incorrect Answer)</small>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <p class="text-muted">No options found for this question.</p>
                            <?php endif; ?>
                        <?php else : ?>
                            <p><strong>Your Answer:</strong></p>
                            <div class="alert <?= ($is_correct_answer === 1 ? 'alert-success' : ($is_correct_answer === 0 ? 'alert-danger' : 'alert-secondary')); ?>">
                                <?= nl2br(htmlspecialchars($student_answer_data['student_response'] ?? 'Not Answered')); ?>
                            </div>
                            <?php if ($is_correct_answer === 0 && !empty($question['correct_answer'])) : // For short answers, if it was wrong, show correct if available
                            ?>
                                <p><strong>Correct Answer:</strong></p>
                                <div class="alert alert-success">
                                    <?= nl2br(htmlspecialchars($question['correct_answer'])); ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="alert alert-info">No questions found for this exam or review data is incomplete.</div>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>student_results.php" class="btn btn-secondary mt-3">Back to My Results</a>
    </div>
</div>

<?php include('includes/footer.php'); ?>
