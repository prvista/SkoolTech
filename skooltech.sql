-- Create the database
CREATE DATABASE skooltech;

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
('professor1', MD5('profpass'), 'Prof. Heart', 'PROF001');

-- Insert sample data into students table
INSERT INTO students (username, password, name, student_number) VALUES
('student1', MD5('stupass1'), 'Paul Vista', 'STU001'),
('student2', MD5('stupass2'), 'Kim Canoza', 'STU002');

-- Table for storing quizzes
CREATE TABLE quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    time_limit INT NOT NULL,
    deadline DATETIME,
    created_by INT,
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
    correct_answer CHAR(1), -- Assuming 'A', 'B', 'C', 'D' or NULL for identification
    question_type ENUM('multiple_choice', 'identification', 'true_false') NOT NULL,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Table for storing quiz results
CREATE TABLE quiz_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_username VARCHAR(50),
    quiz_id INT,
    score FLOAT,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id),
    FOREIGN KEY (student_username) REFERENCES students(username)
);

-- Table for storing exams
CREATE TABLE exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    time_limit INT NOT NULL,
    created_by INT NOT NULL,
    deadline DATETIME NOT NULL,
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
    correct_answer VARCHAR(255), -- For 'identification' questions, this will store the correct answer text
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
    student_username VARCHAR(255),
    exam_id INT,
    score FLOAT,
    FOREIGN KEY (exam_id) REFERENCES exams(id),
    FOREIGN KEY (student_username) REFERENCES students(username)
); 
