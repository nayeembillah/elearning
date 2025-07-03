-- SQL Script for Online MCQ Exam System

-- 1. Create the database (if it doesn't exist)
CREATE DATABASE IF NOT EXISTS elearning;

-- 2. Use the newly created database
USE elearning;

-- 3. Table: `users` - For user authentication (students and admins)
--    Passwords should always be stored hashed (e.g., using PHP's password_hash())
CREATE TABLE `users` (
    `userid` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL, -- Stores hashed passwords
    `email` VARCHAR(255) UNIQUE NULL, -- Optional: for recovery/notifications
    `full_name` VARCHAR(255) NULL,
    `usertype` VARCHAR(50) NOT NULL DEFAULT 'Student', -- 'Student', 'Admin'
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Table: `course` - Renamed to `exams` for clarity in this context, but keeping `course` for consistency with your old structure.
--    This table defines the exams available.
CREATE TABLE `course` (
    `c_id` VARCHAR(50) PRIMARY KEY, -- Using VARCHAR for consistency with your c_id
    `c_name` VARCHAR(255) NOT NULL,
    `c_category` VARCHAR(100) NULL,
    `c_duration` INT NOT NULL, -- Duration in minutes
    `c_inst_name` VARCHAR(255) NULL, -- Instructor/Creator Name
    `c_status` VARCHAR(50) NOT NULL DEFAULT 'active', -- 'active', 'inactive'
    `c_type` VARCHAR(50) NOT NULL DEFAULT 'exam', -- 'exam' or 'course'
    `division_br` VARCHAR(100) NULL, -- Division/Branch related to the exam
    `passing_marks` DECIMAL(5,2) NOT NULL DEFAULT 0.00, -- Minimum score to pass the exam
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Table: `quiz_question` - Stores the questions for each exam.
CREATE TABLE `quiz_question` (
    `question_id` INT AUTO_INCREMENT PRIMARY KEY,
    `exam_id` VARCHAR(50) NOT NULL, -- Foreign Key to course.c_id
    `question_text` TEXT NOT NULL,
    `question_type` VARCHAR(50) NOT NULL DEFAULT 'MCQ', -- For this system, we'll primarily use 'MCQ'
    `order_num` INT NOT NULL, -- To maintain the desired display order of questions
    `marks` DECIMAL(5,2) NOT NULL DEFAULT 1.00, -- Marks awarded for correctly answering this question
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`exam_id`) REFERENCES `course`(`c_id`) ON DELETE CASCADE
);

-- 6. Table: `question_options` - Stores the multiple-choice options for each MCQ question.
CREATE TABLE `question_options` (
    `option_id` INT AUTO_INCREMENT PRIMARY KEY,
    `question_id` INT NOT NULL, -- Foreign Key to quiz_question.question_id
    `option_text` VARCHAR(255) NOT NULL,
    `is_correct` BOOLEAN NOT NULL DEFAULT 0, -- 1 if this is the correct option, 0 otherwise
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`question_id`) REFERENCES `quiz_question`(`question_id`) ON DELETE CASCADE
);

-- 7. Table: `course_enrollment` - Links students to exams and tracks their overall enrollment status.
--    This table also contains their final result for an exam attempt.
CREATE TABLE `course_enrollment` (
    `enroll_id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL, -- Foreign Key to users.userid
    `course_id` VARCHAR(50) NOT NULL, -- Foreign Key to course.c_id (the exam)
    `en_status` VARCHAR(50) NOT NULL DEFAULT 'enrolled', -- 'enrolled', 'submitted', 'Pass', 'Fail'
    `mark_gain` DECIMAL(5,2) NULL, -- Final score obtained by the student for this exam
    `mark_perct` DECIMAL(5,2) NULL, -- Percentage obtained
    `enrollment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`userid`) ON DELETE CASCADE,
    FOREIGN KEY (`course_id`) REFERENCES `course`(`c_id`) ON DELETE CASCADE,
    UNIQUE (`student_id`, `course_id`) -- A student can enroll in a specific course/exam only once
);

-- 8. Table: `exam_attempts` - Tracks each individual attempt a student makes at an exam.
--    Crucial for timer management and tracking progress of a specific session.
CREATE TABLE `exam_attempts` (
    `attempt_id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL, -- Foreign Key to users.userid
    `exam_id` VARCHAR(50) NOT NULL, -- Foreign Key to course.c_id
    `start_time` DATETIME NOT NULL,
    `end_time` DATETIME NULL, -- Null until the exam is submitted or time runs out
    `status` VARCHAR(50) DEFAULT 'started', -- 'started', 'submitted', 'graded', 'abandoned'
    `score` DECIMAL(5,2) DEFAULT NULL, -- Score for THIS specific attempt
    `total_questions_answered` INT DEFAULT 0,
    `total_questions_correct` INT DEFAULT 0,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`userid`) ON DELETE CASCADE,
    FOREIGN KEY (`exam_id`) REFERENCES `course`(`c_id`) ON DELETE CASCADE
);

-- 9. Table: `student_answers` - Stores the answers provided by a student for each question during an attempt.
CREATE TABLE `student_answers` (
    `answer_id` INT AUTO_INCREMENT PRIMARY KEY,
    `attempt_id` INT NOT NULL, -- Foreign Key to exam_attempts.attempt_id
    `question_id` INT NOT NULL, -- Foreign Key to quiz_question.question_id
    `student_response` TEXT NULL, -- Stores the selected option_id (for MCQ) or text (for SA)
    `is_correct_submission` BOOLEAN DEFAULT NULL, -- 1 if correctly answered, 0 if incorrect, NULL if not auto-graded/manual
    `answered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`attempt_id`) REFERENCES `exam_attempts`(`attempt_id`) ON DELETE CASCADE,
    FOREIGN KEY (`question_id`) REFERENCES `quiz_question`(`question_id`) ON DELETE CASCADE,
    UNIQUE (`attempt_id`, `question_id`) -- A student can only answer a question once per attempt
);

-- 10. (Optional) `all_employee_s`, `certificate_setup`, `certificate_tbl`, `certificate_tbl2`, `designation_short`, `division_branch_group`, `employee`, `ict_employee_details`, `instructor_job`, `intructor`, `last_status`, `temp_4_cert`:
--     These tables were in your original `show tables;` output. They are not directly used in the MCQ exam system logic provided,
--     but you can add their CREATE TABLE statements here if you need them in your full project setup.
--     I'm omitting their DDL here as they weren't part of the core exam system requirements.

-- Add a default admin user for testing (password is 'password', remember to change in production!)
INSERT INTO `users` (`username`, `password`, `email`, `full_name`, `usertype`) VALUES
('admin', '$2y$10$QO0hR1X1H2I3J4K5L6M7N8O9P0Q1R2S3T4U5V6W7X8Y8Z9a0b1c2d3e4f5g6h7i8j', 'admin@example.com', 'Admin User', 'Admin');

-- Add a default student user for testing (password is 'password', remember to change in production!)
INSERT INTO `users` (`userid`, `username`, `password`, `email`, `full_name`, `usertype`) VALUES
(NULL, 'student1', '$2y$10$QO0hR1X1H2I3J4K5L6M7N8O9P0Q1R2S3T4U5V6W7X8Y8Z9a0b1c2d3e4f5g6h7i8j', 'student1@example.com', 'Student One', 'Student');

-- Add a sample exam
INSERT INTO `course` (`c_id`, `c_name`, `c_category`, `c_duration`, `c_inst_name`, `c_status`, `c_type`, `division_br`, `passing_marks`) VALUES
('EXM001', 'Intro to PHP Quiz', 'Programming', 30, 'John Doe', 'active', 'exam', 'IT Dept', 60.00);

-- Add sample questions for EXM001
INSERT INTO `quiz_question` (`exam_id`, `question_text`, `question_type`, `order_num`, `marks`) VALUES
('EXM001', 'What does PHP stand for?', 'MCQ', 1, 1.00),
('EXM001', 'Which symbol is used for comments in PHP?', 'MCQ', 2, 1.00),
('EXM001', 'Which of the following is NOT a valid PHP variable name?', 'MCQ', 3, 1.00),
('EXM001', 'What function is used to output text in PHP?', 'MCQ', 4, 1.00);

-- Add options for Question 1 (ID 1 - adjust if your auto-increment starts differently)
INSERT INTO `question_options` (`question_id`, `option_text`, `is_correct`) VALUES
((SELECT question_id FROM quiz_question WHERE exam_id='EXM001' AND order_num=1), 'Personal Home Page', 0),
((SELECT question_id FROM quiz_question WHERE exam_id='EXM001' AND order_num=1), 'Hypertext Preprocessor', 1),
((SELECT question_id FROM quiz_question WHERE exam_id='EXM001' AND order_num=1), 'Private Hosting Provider', 0),
((SELECT question_id FROM quiz_question WHERE exam_id='EXM001' AND order_num=1), 'Programming Home Page', 0);

-- Add options for Question 2 (ID 2)
INSERT INTO `question_options` (`question_id`, `option_text`, `is_correct`) VALUES
((SELECT question_id FROM quiz_question WHERE exam_id='EXM001' AND order_num=2), '//', 1),
((SELECT question_id FROM quiz_question WHERE exam_id='EXM001' AND order_num=2), '/*', 0),
((SELECT question_id FROM quiz_question WHERE exam_id='EXM001' AND order_num=2), '#', 0),
((SELECT question_id FROM quiz_question WHERE exam_id='EXM001' AND order_num=2), '', 0);

-- Add options for Question 3 (ID 3)
INSERT INTO `question_options` (`question_id`, `option_text`, `is_correct`) VALUES
((SELECT question_id FROM quiz_question WHERE exam_id='EXM001' AND order_num=3), '$my_var', 0),
((SELECT question_id FROM quiz_question WHERE exam_id='EXM001' AND order_num=3), '$1var', 1),
((SELECT question_id FROM quiz_question WHERE exam_id='EXM001' AND order_num=3), '$_my_var', 0),
((SELECT question_id FROM quiz_question WHERE exam_id='EXM001' AND order_num=3), '$MyVar', 0);

-- Add options for Question 4 (ID 4)
INSERT INTO `question_options` (`question_id`, `option_text`, `is_correct`) VALUES
((SELECT question_id FROM quiz_question WHERE exam_id='EXM001' AND order_num=4), 'print()', 0),
((SELECT question_id FROM quiz_question WHERE exam_id='EXM001' AND order_num=4), 'echo()', 0),
((SELECT question_id FROM quiz_question WHERE exam_id='EXM001' AND order_num=4), 'write()', 0),
((SELECT question_id FROM quiz_question WHERE exam_id='EXM001' AND order_num=4), 'Both print() and echo()', 1);
