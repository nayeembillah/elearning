<?php
include('config.php'); // This includes session_start()
// include('function.php'); // Not directly used here

// Ensure user is logged in and is a student
if (!isset($_SESSION['userid']) || $_SESSION['usertype'] !== 'Student') {
    $_SESSION['status'] = "Access Denied. Please log in as a student.";
    redirect(BASE_URL . 'index.php');
}

$student_id = $_SESSION['userid'];
$exam_id = $_POST['exam_id'] ?? $_GET['exam_id'] ?? null; // Can be POST from review or GET from timer

if (!$exam_id) {
    $_SESSION['status'] = "No exam ID provided for submission.";
    redirect(BASE_URL . 'course_register_exam.php');
}

// Get student's answers from session
$student_answers = $_SESSION['exam_answers'][$exam_id] ?? [];
$attempt_data = $_SESSION['exam_attempts_data'][$exam_id] ?? null;

if (!$attempt_data) {
    $_SESSION['status'] = "No active exam attempt found for this exam or already submitted.";
    redirect(BASE_URL . 'course_register_exam.php');
}

$attempt_id = $attempt_data['attempt_id'];
$exam_start_time_db = date('Y-m-d H:i:s', $attempt_data['start_time']); // Use timestamp from session for DB insert
$exam_end_time = date('Y-m-d H:i:s'); // Current time for submission


// Fetch exam details for passing marks and total possible score
$exam_details_query = "SELECT c.passing_marks, SUM(qq.marks) AS total_possible_score
                       FROM course c
                       LEFT JOIN quiz_question qq ON c.c_id = qq.exam_id
                       WHERE c.c_id = ? AND c.c_type = 'exam'
                       GROUP BY c.c_id";
$stmt_exam_details = mysqli_prepare($connection_db, $exam_details_query);
mysqli_stmt_bind_param($stmt_exam_details, "s", $exam_id);
mysqli_stmt_execute($stmt_exam_details);
$exam_details_result = mysqli_stmt_get_result($stmt_exam_details);
$exam_details = mysqli_fetch_assoc($exam_details_result);

$passing_marks = $exam_details['passing_marks'] ?? 0;
$total_possible_score = $exam_details['total_possible_score'] ?? 0;


// --- Start Transaction ---
mysqli_begin_transaction($connection_db);

try {
    // 1. Update `exam_attempts` record: Set end_time and status
    $update_attempt_query = "UPDATE exam_attempts SET end_time = ?, status = 'submitted' WHERE attempt_id = ?";
    $stmt_attempt = mysqli_prepare($connection_db, $update_attempt_query);
    mysqli_stmt_bind_param($stmt_attempt, "si", $exam_end_time, $attempt_id);
    if (!mysqli_stmt_execute($stmt_attempt)) {
        throw new Exception("Failed to update exam attempt status: " . mysqli_error($connection_db));
    }

    // 2. Save/Update `student_answers`
    $total_questions_answered_count = 0;
    foreach ($student_answers as $question_id => $response) {
        // Sanitize response
        $response = is_string($response) ? mysqli_real_escape_string($connection_db, $response) : $response;

        // Check if an answer already exists for this question in this attempt
        $check_answer_query = "SELECT answer_id FROM student_answers WHERE attempt_id = ? AND question_id = ?";
        $stmt_check = mysqli_prepare($connection_db, $check_answer_query);
        mysqli_stmt_bind_param($stmt_check, "ii", $attempt_id, $question_id);
        mysqli_stmt_execute($stmt_check);
        $check_result = mysqli_stmt_get_result($stmt_check);

        if (mysqli_num_rows($check_result) > 0) {
            // Update existing answer
            $update_answer_query = "UPDATE student_answers SET student_response = ?, answered_at = NOW() WHERE attempt_id = ? AND question_id = ?";
            $stmt_update = mysqli_prepare($connection_db, $update_answer_query);
            mysqli_stmt_bind_param($stmt_update, "sii", $response, $attempt_id, $question_id);
            if (!mysqli_stmt_execute($stmt_update)) {
                throw new Exception("Failed to update answer for question " . $question_id . ": " . mysqli_error($connection_db));
            }
        } else {
            // Insert new answer
            $insert_answer_query = "INSERT INTO student_answers (attempt_id, question_id, student_response) VALUES (?, ?, ?)";
            $stmt_insert = mysqli_prepare($connection_db, $insert_answer_query);
            mysqli_stmt_bind_param($stmt_insert, "iis", $attempt_id, $question_id, $response);
            if (!mysqli_stmt_execute($stmt_insert)) {
                throw new Exception("Failed to insert answer for question " . $question_id . ": " . mysqli_error($connection_db));
            }
        }
        $total_questions_answered_count++;
    }

    // 3. Automated Grading
    $correct_count = 0;
    $student_obtained_score = 0;

    // Fetch correct answers for all questions in this exam
    $correct_answers_query = "
        SELECT
            qq.question_id,
            qq.marks,
            qo.option_id AS correct_option_id
        FROM quiz_question qq
        LEFT JOIN question_options qo ON qq.question_id = qo.question_id AND qo.is_correct = 1
        WHERE qq.exam_id = ?
    ";
    $stmt_grading = mysqli_prepare($connection_db, $correct_answers_query);
    mysqli_stmt_bind_param($stmt_grading, "s", $exam_id);
    mysqli_stmt_execute($stmt_grading);
    $grading_result = mysqli_stmt_get_result($stmt_grading);

    $correct_answers_map = [];
    while ($row = mysqli_fetch_assoc($grading_result)) {
        $correct_answers_map[$row['question_id']] = [
            'marks' => $row['marks'],
            'correct_option_id' => $row['correct_option_id']
        ];
    }

    foreach ($student_answers as $q_id => $s_response) {
        if (isset($correct_answers_map[$q_id])) {
            $q_info = $correct_answers_map[$q_id];
            $is_correct = false;

            // For MCQ, compare student's selected option_id with the correct_option_id
            if ($s_response == $q_info['correct_option_id']) {
                $is_correct = true;
            }

            // Update `student_answers` with correctness
            $update_correctness_query = "UPDATE student_answers SET is_correct_submission = ? WHERE attempt_id = ? AND question_id = ?";
            $stmt_update_correctness = mysqli_prepare($connection_db, $update_correctness_query);
            $is_correct_int = (int)$is_correct; // Convert boolean to 0 or 1
            mysqli_stmt_bind_param($stmt_update_correctness, "iii", $is_correct_int, $attempt_id, $q_id);
            mysqli_stmt_execute($stmt_update_correctness); // Don't throw exception here, just log

            if ($is_correct) {
                $correct_count++;
                $student_obtained_score += $q_info['marks'];
            }
        }
    }

    // 4. Update `exam_attempts` with final score and counts
    $update_final_attempt_query = "UPDATE exam_attempts SET score = ?, total_questions_answered = ?, total_questions_correct = ?, status = 'graded' WHERE attempt_id = ?";
    $stmt_final_attempt = mysqli_prepare($connection_db, $update_final_attempt_query);
    mysqli_stmt_bind_param($stmt_final_attempt, "diii", $student_obtained_score, $total_questions_answered_count, $correct_count, $attempt_id);
    if (!mysqli_stmt_execute($stmt_final_attempt)) {
        throw new Exception("Failed to update final attempt scores: " . mysqli_error($connection_db));
    }

    // 5. Update `course_enrollment` status (Pass/Fail)
    $final_enrollment_status = 'submitted'; // Default
    $percentage = 0;
    if ($total_possible_score > 0) {
        $percentage = ($student_obtained_score / $total_possible_score) * 100;
        if ($student_obtained_score >= $passing_marks) {
            $final_enrollment_status = 'Pass';
        } else {
            $final_enrollment_status = 'Fail';
        }
    }

    $update_enrollment_status_query = "UPDATE course_enrollment SET en_status = ?, mark_gain = ?, mark_perct = ? WHERE student_id = ? AND course_id = ?";
    $stmt_enrollment_status = mysqli_prepare($connection_db, $update_enrollment_status_query);
    mysqli_stmt_bind_param($stmt_enrollment_status, "sdiis", $final_enrollment_status, $student_obtained_score, $percentage, $student_id, $exam_id);
    if (!mysqli_stmt_execute($stmt_enrollment_status)) {
        throw new Exception("Failed to update course enrollment status: " . mysqli_error($connection_db));
    }


    // --- Commit Transaction ---
    mysqli_commit($connection_db);

    // 6. Clear session data for this exam
    unset($_SESSION['exam_answers'][$exam_id]);
    unset($_SESSION['exam_attempts_data'][$exam_id]);

    $_SESSION['success'] = "Exam submitted successfully! You scored: " . htmlspecialchars($student_obtained_score) . " out of " . htmlspecialchars($total_possible_score) . ". Status: " . htmlspecialchars($final_enrollment_status);
    redirect(BASE_URL . 'course_register_exam.php'); // Redirect to exam list
} catch (Exception $e) {
    // --- Rollback Transaction on Error ---
    mysqli_rollback($connection_db);
    $_SESSION['status'] = "Exam submission failed: " . $e->getMessage();
    error_log("Exam Submission Error for student $student_id, exam $exam_id: " . $e->getMessage()); // Log detailed error
    redirect(BASE_URL . 'course_register_exam.php'); // Redirect to exam list with error
}
?>
