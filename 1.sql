-- Database: elearning (as per your prompt)

-- 1. Table: `users` (Existing - ensure it has 'userid', 'username', 'password', 'usertype')
--    Example:
-- CREATE TABLE `users` (
--     `userid` INT AUTO_INCREMENT PRIMARY KEY,
--     `username` VARCHAR(100) UNIQUE NOT NULL,
--     `password` VARCHAR(255) NOT NULL, -- Store hashed passwords
--     `usertype` VARCHAR(50) NOT NULL DEFAULT 'Student' -- 'Student', 'Admin'
-- );

-- 2. Table: `course` (Existing - acts as our Exams table for `c_type='exam'`)
--    Ensure these fields are present and relevant:
--    `c_id` (VARCHAR or INT, PRIMARY KEY or UNIQUE INDEX if not PK, used as exam_id)
--    `c_name` (Exam Name)
--    `c_category`
--    `c_duration` (INT, in minutes)
--    `c_inst_name` (Instructor/Creator)
--    `c_status` (VARCHAR, 'active'/'inactive')
--    `c_type` (VARCHAR, 'exam' for our purpose)
--    `division_br`
--    ADD THIS FIELD:
--    `passing_marks` DECIMAL(5,2) DEFAULT 0.00 -- Minimum score to pass the exam

-- 3. Table: `course_enrollment` (Existing)
--    This table links students to courses/exams.
--    `enroll_id` (PK)
--    `student_id` (FK to users.userid)
--    `course_id` (FK to course.c_id)
--    `en_status` (VARCHAR, e.g., 'enrolled', 'submitted', 'Pass', 'Fail')
--    `mark_gain` (DECIMAL, nullable)
--    `mark_perct` (DECIMAL, nullable)
--    ... other fields

-- 4. Table: `quiz_question` (Modified - now stores exam questions)
--    This table needs significant additions/modifications.
CREATE TABLE `quiz_question` (
    `question_id` INT AUTO_INCREMENT PRIMARY KEY,
    `exam_id` VARCHAR(50) NOT NULL, -- Foreign Key to course.c_id
    `question_text` TEXT NOT NULL,
    `question_type` VARCHAR(50) NOT NULL DEFAULT 'MCQ', -- For now, we'll focus on 'MCQ'
    `order_num` INT NOT NULL, -- To maintain question order within an exam
    `marks` DECIMAL(5,2) NOT NULL DEFAULT 1.00, -- Marks for this question
    -- `correct_answer` TEXT NULL, -- Not strictly needed for MCQ if using `question_options.is_correct`
    FOREIGN KEY (`exam_id`) REFERENCES `course`(`c_id`) ON DELETE CASCADE
);

-- 5. Table: `question_options` (NEW - stores options for MCQ questions)
CREATE TABLE `question_options` (
    `option_id` INT AUTO_INCREMENT PRIMARY KEY,
    `question_id` INT NOT NULL, -- Foreign Key to quiz_question.question_id
    `option_text` VARCHAR(255) NOT NULL,
    `is_correct` BOOLEAN NOT NULL DEFAULT 0, -- 1 for correct, 0 for incorrect
    FOREIGN KEY (`question_id`) REFERENCES `quiz_question`(`question_id`) ON DELETE CASCADE
);

-- 6. Table: `exam_attempts` (NEW - tracks each student's attempt at an exam)
CREATE TABLE `exam_attempts` (
    `attempt_id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL, -- Foreign Key to users.userid
    `exam_id` VARCHAR(50) NOT NULL, -- Foreign Key to course.c_id
    `start_time` DATETIME NOT NULL,
    `end_time` DATETIME NULL, -- Null until submitted
    `status` VARCHAR(50) DEFAULT 'started', -- 'started', 'submitted', 'graded'
    `score` DECIMAL(5,2) DEFAULT NULL, -- Student's score for this attempt
    `total_questions_answered` INT DEFAULT 0,
    `total_questions_correct` INT DEFAULT 0,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`userid`),
    FOREIGN KEY (`exam_id`) REFERENCES `course`(`c_id`)
);

-- 7. Table: `student_answers` (Modified - from your `exam_answer`)
--    Renaming for clarity and adding `attempt_id`.
CREATE TABLE `student_answers` (
    `answer_id` INT AUTO_INCREMENT PRIMARY KEY,
    `attempt_id` INT NOT NULL, -- Foreign Key to exam_attempts.attempt_id
    `question_id` INT NOT NULL, -- Foreign Key to quiz_question.question_id
    `student_response` TEXT, -- Stores the selected option_id (for MCQ) or text (for SA)
    `is_correct_submission` BOOLEAN DEFAULT NULL, -- 1 if correct, 0 if incorrect, NULL if not graded yet/manual
    FOREIGN KEY (`attempt_id`) REFERENCES `exam_attempts`(`attempt_id`) ON DELETE CASCADE,
    FOREIGN KEY (`question_id`) REFERENCES `quiz_question`(`question_id`) ON DELETE CASCADE
);
