<?php
// manage_questions.php - Allows admins to add and view exam questions.

// 1. MUST be first: Include config.php to start the session, connect to DB, and define BASE_URL.
include('config.php');
// 2. Include function.php for helper functions like checkUserRole and redirect.
include('function.php');

// 3. Restrict access to Admin users only. This check includes a redirect if unauthorized, so it must be early.
checkUserRole('Admin');

// --- Process POST data for adding a new question ---
// This entire block MUST execute before any HTML output from header.php.
if (isset($_POST['add_question'])) {
    $exam_id = mysqli_real_escape_string($connection_db, $_POST['exam_id']);
    $question_text = mysqli_real_escape_string($connection_db, $_POST['question_text']);
    $marks = (float)$_POST['marks'];
    $options = $_POST['options']; // Array of options
    $correct_option_index = (int)$_POST['correct_option']; // 0-indexed for options array

    // Basic validation
    if (empty($question_text) || empty($options) || !isset($_POST['correct_option'])) {
        $_SESSION['status'] = "Please fill all required fields and select a correct option.";
        redirect(BASE_URL . 'manage_questions.php?exam_id=' . urlencode($exam_id));
    }
    foreach ($options as $opt) {
        if (empty(trim($opt))) {
            $_SESSION['status'] = "Option text cannot be empty.";
            redirect(BASE_URL . 'manage_questions.php?exam_id=' . urlencode($exam_id));
        }
    }
    if ($correct_option_index < 0 || $correct_option_index >= count($options)) {
        $_SESSION['status'] = "Invalid correct option selection.";
        redirect(BASE_URL . 'manage_questions.php?exam_id=' . urlencode($exam_id));
    }

    // Get next order number for the exam
    $order_num_query = "SELECT MAX(order_num) AS max_order FROM quiz_question WHERE exam_id = ?";
    $stmt_order = mysqli_prepare($connection_db, $order_num_query);
    mysqli_stmt_bind_param($stmt_order, "s", $exam_id);
    mysqli_stmt_execute($stmt_order);
    $result_order = mysqli_stmt_get_result($stmt_order);
    $row_order = mysqli_fetch_assoc($result_order);
    $next_order_num = ($row_order['max_order'] ?? 0) + 1;
    mysqli_stmt_close($stmt_order); // Close statement after use


    mysqli_begin_transaction($connection_db);
    try {
        // Insert question
        $insert_question_query = "INSERT INTO quiz_question (exam_id, question_text, question_type, order_num, marks) VALUES (?, ?, 'MCQ', ?, ?)";
        $stmt_q = mysqli_prepare($connection_db, $insert_question_query);
        // Bind parameters: s (exam_id), s (question_text), i (order_num), d (marks)
        mysqli_stmt_bind_param($stmt_q, "ssid", $exam_id, $question_text, $next_order_num, $marks);
        if (!mysqli_stmt_execute($stmt_q)) {
            throw new Exception("Error adding question: " . mysqli_error($connection_db));
        }
        $new_question_id = mysqli_insert_id($connection_db);
        mysqli_stmt_close($stmt_q); // Close statement

        // Insert options
        foreach ($options as $index => $option_text) {
            $is_correct = ($index == $correct_option_index) ? 1 : 0;
            $insert_option_query = "INSERT INTO question_options (question_id, option_text, is_correct) VALUES (?, ?, ?)";
            $stmt_o = mysqli_prepare($connection_db, $insert_option_query);
            // Bind parameters: i (question_id), s (option_text), i (is_correct)
            mysqli_stmt_bind_param($stmt_o, "isi", $new_question_id, $option_text, $is_correct);
            if (!mysqli_stmt_execute($stmt_o)) {
                throw new Exception("Error adding option: " . mysqli_error($connection_db));
            }
            mysqli_stmt_close($stmt_o); // Close statement
        }

        mysqli_commit($connection_db);
        $_SESSION['success'] = "Question added successfully!";
    } catch (Exception $e) {
        mysqli_rollback($connection_db);
        $_SESSION['status'] = "Failed to add question: " . $e->getMessage();
        error_log("Add question failed: " . $e->getMessage());
    }
    // Redirect after processing, always. This must happen before any HTML output.
    redirect(BASE_URL . 'manage_questions.php?exam_id=' . urlencode($exam_id));
}
// --- End of POST processing for adding a new question ---


// --- Fetch list of exams for dropdown (runs after potential redirects) ---
$exams_query = "SELECT c_id, c_name FROM course WHERE c_type = 'exam' AND c_status = 'active' ORDER BY c_name ASC";
$exams_result = mysqli_query($connection_db, $exams_query);
$exams = [];
while ($row = mysqli_fetch_assoc($exams_result)) {
    $exams[] = $row;
}

$selected_exam_id = $_GET['exam_id'] ?? null;
$questions_for_exam = [];
$selected_exam_name = '';

if ($selected_exam_id) {
    // Validate selected_exam_id against existing exams
    $found_exam = false;
    foreach ($exams as $exam) {
        if ($exam['c_id'] == $selected_exam_id) {
            $selected_exam_name = $exam['c_name'];
            $found_exam = true;
            break;
        }
    }
    if (!$found_exam) {
        $_SESSION['status'] = "Invalid Exam ID selected.";
        $selected_exam_id = null; // Clear invalid selection
        redirect(BASE_URL . 'manage_questions.php');
    }

    // Fetch questions for the selected exam
    $questions_query = "
        SELECT
            qq.question_id,
            qq.question_text,
            qq.order_num,
            qq.marks,
            GROUP_CONCAT(qo.option_text ORDER BY qo.option_id ASC SEPARATOR '|||') AS options_text,
            GROUP_CONCAT(qo.option_id ORDER BY qo.option_id ASC SEPARATOR '|||') AS options_ids,
            GROUP_CONCAT(qo.is_correct ORDER BY qo.option_id ASC SEPARATOR '|||') AS options_correctness
        FROM quiz_question qq
        LEFT JOIN question_options qo ON qq.question_id = qo.question_id
        WHERE qq.exam_id = ?
        GROUP BY qq.question_id
        ORDER BY qq.order_num ASC
    ";
    $stmt = mysqli_prepare($connection_db, $questions_query);
    mysqli_stmt_bind_param($stmt, "s", $selected_exam_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $row['options_text'] = $row['options_text'] ? explode('|||', $row['options_text']) : [];
        $row['options_correctness'] = $row['options_correctness'] ? explode('|||', $row['options_correctness']) : [];
        $row['options_ids'] = $row['options_ids'] ? explode('|||', $row['options_ids']) : [];
        $questions_for_exam[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// --- Now include the header HTML. This comes AFTER all PHP logic that might redirect. ---
include('includes/header.php');
?>

<div class="card-shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Manage Exam Questions</h6>
    </div>
    <div class="card-body">
        <?php
        // Display session messages
        if (isset($_SESSION['success']) && $_SESSION['success'] != '') {
            echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['status']) && $_SESSION['status'] != '') {
            echo '<div class="alert alert-danger">' . $_SESSION['status'] . '</div>';
            unset($_SESSION['status']);
        }
        ?>

        <form action="<?= BASE_URL ?>manage_questions.php" method="GET" class="mb-4">
            <div class="form-group">
                <label for="select_exam">Select Exam:</label>
                <select class="form-control" id="select_exam" name="exam_id" onchange="this.form.submit()">
                    <option value="">-- Select an Exam --</option>
                    <?php foreach ($exams as $exam) { ?>
                        <option value="<?php echo htmlspecialchars($exam['c_id']); ?>" <?php echo ($selected_exam_id == $exam['c_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($exam['c_name']); ?> (ID: <?php echo htmlspecialchars($exam['c_id']); ?>)
                        </option>
                    <?php } ?>
                </select>
            </div>
        </form>

        <?php if ($selected_exam_id) { ?>
            <hr>
            <h5>Add New Question for:
                <strong><?php echo htmlspecialchars($selected_exam_name); ?></strong>
            </h5>
            <form action="<?= BASE_URL ?>manage_questions.php" method="POST" class="mb-5">
                <input type="hidden" name="exam_id" value="<?php echo htmlspecialchars($selected_exam_id); ?>">
                <div class="form-group">
                    <label for="question_text">Question Text:</label>
                    <textarea class="form-control" id="question_text" name="question_text" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label for="marks">Marks for this question:</label>
                    <input type="number" class="form-control" id="marks" name="marks" value="1" min="0.5" step="0.5" required>
                </div>
                <div id="options-container">
                    <label>Options (MCQ):</label>
                    <div class="form-group d-flex align-items-center mb-2">
                        <input type="text" class="form-control mr-2" name="options[]" placeholder="Option 1" required>
                        <input type="radio" name="correct_option" value="0" required> Correct
                    </div>
                    <div class="form-group d-flex align-items-center mb-2">
                        <input type="text" class="form-control mr-2" name="options[]" placeholder="Option 2" required>
                        <input type="radio" name="correct_option" value="1"> Correct
                    </div>
                </div>
                <button type="button" class="btn btn-secondary btn-sm mb-3" onclick="addOption()">Add Option</button>
                <button type="submit" name="add_question" class="btn btn-primary mt-3">Add Question</button>
            </form>

            <hr>
            <h5>Existing Questions for: <strong><?php echo htmlspecialchars($selected_exam_name); ?></strong></h5>
            <?php if (!empty($questions_for_exam)) { ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Question</th>
                                <th>Marks</th>
                                <th>Options</th>
                                <th>Correct Answer</th>
                                </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($questions_for_exam as $q) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($q['order_num']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($q['question_text'])); ?></td>
                                    <td><?php echo htmlspecialchars($q['marks']); ?></td>
                                    <td>
                                        <ul class="list-unstyled">
                                            <?php
                                            foreach ($q['options_text'] as $idx => $opt_text) {
                                                echo '<li>' . htmlspecialchars($opt_text) . '</li>';
                                            }
                                            ?>
                                        </ul>
                                    </td>
                                    <td>
                                        <?php
                                        $correct_opt_index = array_search('1', $q['options_correctness']);
                                        if ($correct_opt_index !== false) {
                                            echo htmlspecialchars($q['options_text'][$correct_opt_index]);
                                        } else {
                                            echo "N/A"; // Should not happen for valid MCQs
                                        }
                                        ?>
                                    </td>
                                    </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <div class="alert alert-info">No questions added for this exam yet.</div>
            <?php } ?>

        <?php } else { ?>
            <div class="alert alert-info">Please select an exam to manage its questions.</div>
        <?php } ?>

    </div>
</div>

<script>
    let optionCount = 2; // Start after the initial two options in the form

    function addOption() {
        optionCount++;
        const optionsContainer = document.getElementById('options-container');
        const newOptionDiv = document.createElement('div');
        newOptionDiv.classList.add('form-group', 'd-flex', 'align-items-center', 'mb-2');
        newOptionDiv.innerHTML = `
            <input type="text" class="form-control mr-2" name="options[]" placeholder="Option ${optionCount}" required>
            <input type="radio" name="correct_option" value="${optionCount - 1}"> Correct
        `;
        optionsContainer.appendChild(newOptionDiv);
    }
</script>

<?php include('includes/footer.php'); ?>