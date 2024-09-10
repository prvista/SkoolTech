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
('professor1', MD5('professorpassword'), 'Prof. John Doe', 'PROF001');

-- Insert sample data into students table
INSERT INTO students (username, password, name, student_number) VALUES
('student1', MD5('studentpassword1'), 'Alice Johnson', 'STU001'),
('student2', MD5('studentpassword2'), 'Bob Smith', 'STU002');

-- Table for storing quizzes
CREATE TABLE quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    time_limit INT NOT NULL,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES professors(id) ON DELETE CASCADE
);

-- Table for storing quiz questions
CREATE TABLE quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT,
    question_text TEXT NOT NULL,
    choice_a TEXT NOT NULL,
    choice_b TEXT NOT NULL,
    choice_c TEXT NOT NULL,
    choice_d TEXT NOT NULL,
    correct_answer CHAR(1) NOT NULL,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);


CREATE TABLE quiz_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_username VARCHAR(255),
    quiz_id INT,
    score FLOAT,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id),
    FOREIGN KEY (student_username) REFERENCES students(username)
);

