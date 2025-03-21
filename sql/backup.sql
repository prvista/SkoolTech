-- Create the database
CREATE DATABASE IF NOT EXISTS skooltech;

USE skooltech;

-- Table for storing professors
CREATE TABLE professors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    professor_id VARCHAR(20) NOT NULL UNIQUE
);

-- Table for storing students
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    student_number VARCHAR(20) NOT NULL UNIQUE
);

-- Insert sample data into professors table
INSERT INTO professors (username, password, name, professor_id) VALUES
('professor1', MD5('p'), 'Prof. Heart', 'PROF001');

-- Insert sample data into students table
INSERT INTO students (username, password, name, student_number) VALUES
('student1', MD5('s1'), 'Paul Vista', 'STU001'),
('student2', MD5('s2'), 'Kim Canoza', 'STU002');

-- Table for storing quizzes
CREATE TABLE quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    time_limit INT NOT NULL,
    deadline DATETIME,
    created_by INT,
    subject ENUM('English', 'Science', 'Math') NOT NULL,
    FOREIGN KEY (created_by) REFERENCES professors(id) ON DELETE CASCADE
);

-- Table for storing quiz questions
CREATE TABLE quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT,
    question_text TEXT NOT NULL,
    choice_a TEXT,
    choice_b TEXT,
    choice_c TEXT,
    choice_d TEXT,
    correct_answer CHAR(1),
    question_type ENUM('multiple_choice', 'identification', 'true_false') NOT NULL,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Table for storing quiz results
CREATE TABLE quiz_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    quiz_id INT,
    score FLOAT,
    raw_score FLOAT,
    submitted_answer VARCHAR(255),
    subject ENUM('English', 'Science', 'Math'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id),
    FOREIGN KEY (student_id) REFERENCES students(id)
);

-- Table for storing exams
CREATE TABLE exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    time_limit INT NOT NULL,
    created_by INT NOT NULL,
    deadline DATETIME NOT NULL,
    subject ENUM('English', 'Science', 'Math') NOT NULL,
    FOREIGN KEY (created_by) REFERENCES professors(id)
);

-- Table for storing exam questions
CREATE TABLE exam_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    question_type ENUM('multiple_choice', 'identification', 'true_false') NOT NULL,
    question_text TEXT NOT NULL,
    choice_a VARCHAR(255),
    choice_b VARCHAR(255),
    choice_c VARCHAR(255),
    choice_d VARCHAR(255),
    correct_answer VARCHAR(255),
    FOREIGN KEY (exam_id) REFERENCES exams(id)
);

-- Table for storing student exams
CREATE TABLE student_exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    exam_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    score DECIMAL(5,2),
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (exam_id) REFERENCES exams(id)
);

-- Table for storing exam results
CREATE TABLE exam_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    exam_id INT,
    score FLOAT,
    subject ENUM('English', 'Science', 'Math') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id),
    FOREIGN KEY (student_id) REFERENCES students(id)
);

-- Table for storing notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    user_type ENUM('student', 'professor') NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Table for storing assignments
CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    criteria TEXT NOT NULL,
    deadline DATETIME NOT NULL,
    due_date DATETIME NOT NULL,
    created_by INT,
    subject ENUM('English', 'Science', 'Math') NOT NULL,
    grade DECIMAL(5,2) DEFAULT 0, -- Column for storing the grade
    FOREIGN KEY (created_by) REFERENCES professors(id) ON DELETE CASCADE
);

-- Table for storing assignment submissions
CREATE TABLE assignment_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    submitted_file VARCHAR(255) NOT NULL,
    submission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    grade DECIMAL(5,2),
    score DECIMAL(5,2),
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id)
);

-- New table for storing subject scores
CREATE TABLE subject_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    student_name VARCHAR(100),
    subject ENUM('English', 'Science', 'Math') NOT NULL,
    assignment_score DECIMAL(5,2) DEFAULT 0,
    quiz_score DECIMAL(5,2) DEFAULT 0,
    exam_score DECIMAL(5,2) DEFAULT 0,
    FOREIGN KEY (student_id) REFERENCES students(id),
    UNIQUE KEY (student_id, subject)
);

-- Insert sample quiz
INSERT INTO quizzes (title, time_limit, deadline, created_by, subject)
VALUES ('Math Quiz 1', 15, '2024-10-01 17:00:00', 1, 'Math');

-- Get the quiz ID
SET @quiz_id = LAST_INSERT_ID();

-- Insert sample quiz questions
INSERT INTO quiz_questions (quiz_id, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer, question_type)
VALUES 
(@quiz_id, 'What is 2 + 2?', '3', '4', '5', '6', 'B', 'multiple_choice'),
(@quiz_id, 'What is the square root of 16?', '2', '4', '8', '16', 'B', 'multiple_choice');

-- Insert sample exam
INSERT INTO exams (title, time_limit, deadline, created_by, subject)
VALUES ('Science Exam 1', 60, '2024-10-15 10:00:00', 1, 'Science');

-- Get the exam ID
SET @exam_id = LAST_INSERT_ID();

-- Insert sample exam questions
INSERT INTO exam_questions (exam_id, question_type, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer)
VALUES 
(@exam_id, 'multiple_choice', 'What is H2O commonly known as?', 'Hydrogen', 'Oxygen', 'Water', 'Helium', 'C'),
(@exam_id, 'multiple_choice', 'What planet is known as the Red Planet?', 'Earth', 'Mars', 'Jupiter', 'Venus', 'B');

-- Insert sample assignment
INSERT INTO assignments (title, description, criteria, deadline, due_date, created_by, subject)
VALUES ('English Essay 1', 'Write a 500-word essay on your favorite book.', 'Criteria: Clarity, Structure, and Argumentation.', '2024-10-10 23:59:59', '2024-10-10 23:59:59', 1, 'English');

-- Get the assignment ID
SET @assignment_id = LAST_INSERT_ID();

-- Ensure the subject_scores table is updated with scores from quiz_results
INSERT INTO subject_scores (student_id, student_name, subject, quiz_score)
SELECT s.id, s.name, q.subject, COALESCE(SUM(qr.score), 0)
FROM quiz_results qr
JOIN quizzes q ON qr.quiz_id = q.id
JOIN students s ON qr.student_id = s.id
GROUP BY s.id, s.name, q.subject
ON DUPLICATE KEY UPDATE
quiz_score = quiz_score + VALUES(quiz_score),
student_name = VALUES(student_name);  -- Update student_name if necessary

-- Ensure the subject_scores table is updated with scores from exam_results
INSERT INTO subject_scores (student_id, student_name, subject, exam_score)
SELECT s.id, s.name, e.subject, COALESCE(SUM(er.score), 0)
FROM exam_results er
JOIN exams e ON er.exam_id = e.id
JOIN students s ON er.student_id = s.id
GROUP BY s.id, s.name, e.subject
ON DUPLICATE KEY UPDATE
exam_score = exam_score + VALUES(exam_score),
student_name = VALUES(student_name);  -- Update student_name if necessary

-- Ensure the subject_scores table is updated with scores from assignment_submissions
INSERT INTO subject_scores (student_id, student_name, subject, assignment_score)
SELECT s.id, s.name, a.subject, COALESCE(SUM(asu.score), 0)
FROM assignment_submissions asu
JOIN assignments a ON asu.assignment_id = a.id
JOIN students s ON asu.student_id = s.id
GROUP BY s.id, s.name, a.subject
ON DUPLICATE KEY UPDATE
assignment_score = assignment_score + VALUES(assignment_score),
student_name = VALUES(student_name);  -- Update student_name if necessary
