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
('student2', MD5('s2'), 'Kim Canoza', 'STU002'),
('student3', MD5('s3'), 'Louis Arcigal', 'STU003'),
('student4', MD5('s4'), 'Stephen Bilog', 'STU004'),
('student5', MD5('s5'), 'Charles Cabusas', 'STU005'),
('student6', MD5('s6'), 'Mark Canizares', 'STU006'),
('student7', MD5('s7'), 'Khyro Ellerma', 'STU007'),
('student8', MD5('s8'), 'Ashton Esber', 'STU008'),
('student9', MD5('s9'), 'Lorraine Latayan', 'STU009'),
('student10', MD5('s10'), 'Paulo Tejada', 'STU0010'),
('student11', MD5('s11'), 'Fonzy Urriquia', 'STU0011'),
('student12', MD5('s12'), 'Ford Villanueva', 'STU0012'),
('student13', MD5('s13'), 'Sample Student13', 'STU0013'),
('student14', MD5('s14'), 'Sample Student14', 'STU0014');

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

ALTER TABLE assignments 
ADD COLUMN submit_to_professor BOOLEAN DEFAULT FALSE;

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


-- Insert sample quiz: Math Quiz
INSERT INTO quizzes (title, time_limit, deadline, created_by, subject)
VALUES ('Math Quiz 1', 15, '2024-10-01 17:00:00', 1, 'Math');

-- Get the quiz ID for Math Quiz
SET @quiz_id = LAST_INSERT_ID();

-- Insert sample quiz questions for Math Quiz
INSERT INTO quiz_questions (quiz_id, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer, question_type)
VALUES 
(@quiz_id, 'What is 2 + 2?', '3', '4', '5', '6', 'B', 'multiple_choice'),
(@quiz_id, 'What is the square root of 16?', '2', '4', '8', '16', 'B', 'multiple_choice'),
(@quiz_id, 'What is 9/3?', '6', '5', '3', '7', 'C', 'multiple_choice'),
(@quiz_id, 'How many years are there in a decade?', '5', '10', '15', '20', 'B', 'multiple_choice'),
(@quiz_id, 'What is the square of 15?', '252', '225', '30', '15', 'B', 'multiple_choice'),
(@quiz_id, 'If David’s age is 27 years old in 2011, what was his age in 2003?', '17', '37', '20', '19', 'A', 'multiple_choice'),
(@quiz_id, 'What is the sum of 130+125+191?', '335', '456', '446', '426', 'C', 'multiple_choice'),
(@quiz_id, '20 + (90 ÷ 2) is equal to?', '50', '65', '55', '60', 'B', 'multiple_choice'),
(@quiz_id, 'What is the next prime number after 5?', '6', '7', '9', '11', 'B', 'multiple_choice');

-- Insert sample quiz: Science Quiz
INSERT INTO quizzes (title, time_limit, deadline, created_by, subject)
VALUES ('Science Quiz 1', 15, '2024-12-25 17:00:00', 1, 'Science');

-- Get the quiz ID for Science Quiz
SET @quiz_id = LAST_INSERT_ID();

-- Insert sample quiz questions for Science Quiz
INSERT INTO quiz_questions (quiz_id, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer, question_type)
VALUES 
(@quiz_id, 'How many colors are in the rainbow?', '6', '7', '5', '8', 'B', 'multiple_choice'),
(@quiz_id, 'On what continent will you not find bees?', 'Africa', 'Antarctica', 'Australia', 'Asia', 'B', 'multiple_choice'),
(@quiz_id, 'How does fat leave your body when you lose weight?', 'Sweat only', 'Urine Only', 'Sweat, Urine and Breath', 'It converts into muscle', 'C', 'multiple_choice'),
(@quiz_id, 'Which blood type is the rarest in humans?', 'O negative', 'B negative', 'A negative', 'AB negative', 'D', 'multiple_choice'),
(@quiz_id, 'Which gas evolves when charcoal is burnt?', 'Ozone', 'Nitrogen', 'Carbon Dioxide', 'Oxygen', 'C', 'multiple_choice'),
(@quiz_id, 'How many planets are there in our solar system?', '8', '9', '10', '12', 'A', 'multiple_choice'),
(@quiz_id, 'What is the total number of bones in the human body?', '206', '32', '196', '512', 'A', 'multiple_choice'),
(@quiz_id, 'The Sun is a?', 'Huge Planet', 'Star', 'Comet', 'Satellite', 'B', 'multiple_choice'),
(@quiz_id, 'The three methods of science are observation, experimentation, and _______?', 'hypothesis', 'deduction', 'inference', 'measurement', 'D', 'multiple_choice');

-- Insert sample quiz: English Quiz
INSERT INTO quizzes (title, time_limit, deadline, created_by, subject)
VALUES ('English Quiz 1', 15, '2024-12-25 17:00:00', 1, 'English');

-- Get the quiz ID for English Quiz
SET @quiz_id = LAST_INSERT_ID();

INSERT INTO quiz_questions (quiz_id, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer, question_type) 
VALUES 
(@quiz_id, "They ______________ her and trusted her for years.", "know", "had known", "knew", "known", "C", "multiple_choice"),
(@quiz_id, "Every morning she ______________ up early and gets ready for work.", "is waking", "had woken", "has woken", "wakes", "D", "multiple_choice"),
(@quiz_id, "People ______________ walk on grass.", "couldn't", "needn't", "mustn't", "may not", "C", "multiple_choice"),
(@quiz_id, "______________ you speak any foreign languages?", "can't", "should", "couldn't", "can", "D", "multiple_choice"),
(@quiz_id, "World War I and World War II took place ______________ the 20th century.", "on", "in", "at", "into", "B", "multiple_choice"),
(@quiz_id, "They built this temple 3,000 years ago. This must ______________ a great civilization.", "not have been", "was", "has been", "have been", "D", "multiple_choice"),
(@quiz_id, "I wanted to go to the park, ______________ my mother refused.", "but", "or", "so", "and", "A", "multiple_choice"),
(@quiz_id, "Change the active voice into passive voice: The house ______________ by me every Saturday.", "cleaned", "will be cleaned", "will cleaned", "None of the above", "B", "multiple_choice"),
(@quiz_id, "This must not happen again, ______________ you will be dismissed.", "but", "or", "so", "and", "B", "multiple_choice"),
(@quiz_id, "If A is equal to B and B is equal to C, ______________ A is equal to C.", "than", "then", "so", "None of the above", "B", "multiple_choice");





-- Insert sample exam (Science)
INSERT INTO exams (title, time_limit, deadline, created_by, subject)
VALUES ('Science Exam 1', 60, '2024-12-16 10:00:00', 1, 'Science');

-- Get the exam ID
SET @exam_id = LAST_INSERT_ID();

-- Insert sample exam questions
INSERT INTO exam_questions (exam_id, question_type, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer)
VALUES 

(@exam_id, 'multiple_choice', 'What is H2O commonly known as?', 'Hydrogen', 'Oxygen', 'Water', 'Helium', 'C'),
(@exam_id, 'multiple_choice', 'What planet is known as the Red Planet?', 'Earth', 'Mars', 'Jupiter', 'Venus', 'B'),
(@exam_id, 'multiple_choice', 'Which of these is not a type of electromagnetic radiation?', 'X-Rays', 'Gamma Rays', 'Sound Waves', 'Ultraviolet rays', 'C'),
(@exam_id, 'multiple_choice', 'Which of these is not a fundamental force of nature?', 'Gravity', 'Electromagnetic force', 'Strong nuclear force', 'Centrifugal force', 'D'),
(@exam_id, 'multiple_choice', 'What is the chemical symbol of gold?', 'Au', 'Ag', 'Fe', 'Cu', 'A');

-- Insert sample exam questions
INSERT INTO exam_questions (exam_id, question_type, question_text, correct_answer)
VALUES 

(@exam_id, 'identification', 'Which organ pumps blood through your body?', 'Heart'),
(@exam_id, 'identification', "What is a plant's main food source?", 'Sunlight'),
(@exam_id, 'identification', 'What do butterflies start as before they grow wings?', 'Caterpillars'),
(@exam_id, 'identification', 'What is the hottest planet in our solar system?', 'Venus'),
(@exam_id, 'identification', 'What is the study of earthquakes called?', 'Seismology');

-- Insert sample exam questions
INSERT INTO exam_questions (exam_id, question_type, question_text, correct_answer)
VALUES 

(@exam_id, 'true_false', 'Is HTML a programming language?', 'True'),
(@exam_id, 'true_false', 'Does GPS stand for Graphical Placement System?', 'False'),
(@exam_id, 'true_false', 'Is the study of weather Meteorology?', 'True'),
(@exam_id, 'true_false', 'Is a ruler used to measure temperature?', 'False'),
(@exam_id, 'true_false', 'Is Condensation the process by which a liquid changes into a gas at any temperature below its boiling point?', 'False');

-- Insert sample exam (Math)
INSERT INTO exams (title, time_limit, deadline, created_by, subject)
VALUES ('Math Exam 1', 60, '2024-12-15 10:00:00', 1, 'Math');

-- Get the exam ID
SET @exam_id = LAST_INSERT_ID();

-- Insert sample exam questions
INSERT INTO exam_questions (exam_id, question_type, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer)
VALUES 
(@exam_id, 'multiple_choice' , 'What is 2 x 5?', '5', '3', '29', '10', 'D'),
(@exam_id, 'multiple_choice' , 'What is - 15 + 15?', '30', '18', '0', '169', 'C'),
(@exam_id, 'multiple_choice' , 'What is 253 x 34?', '8602', '28690543', '93930', '01039', 'A'),
(@exam_id, 'multiple_choice' , 'What is 250 x 4?', '750', '1000', '500', '100', 'B'),
(@exam_id, 'multiple_choice' , 'What is 76 x 6 - 24?', '432', '463', '264', '89', 'A');

-- Insert sample exam questions
INSERT INTO exam_questions (exam_id, question_type, question_text, correct_answer)
VALUES 

(@exam_id, 'identification' , 'What is 7291 + 8830?', '16121'),
(@exam_id, 'identification' , 'What is 3 + 23?', '26'),
(@exam_id, 'identification' , 'What is 372910/5?', '74582'),
(@exam_id, 'identification' , 'What is 761 x 28?', '21308'),
(@exam_id, 'identification' , 'What is -45 + 55?', '10');

-- Insert sample exam questions
INSERT INTO exam_questions (exam_id, question_type, question_text, correct_answer)
VALUES 

(@exam_id, 'true_false' , '121/11 is 11?', 'True'),
(@exam_id,  'true_false' , '60 x 8 = 409?', 'False'),
(@exam_id,  'true_false' , 'Is the next prime number after 7 13?', 'False'),
(@exam_id,  'true_false' , 'Is product of 131 × 0 × 300 × 4 = 0?', 'True'),
(@exam_id,  'true_false' , 'Is 131 × 0 × 300 × 4 = 24?', 'True');

-- Insert sample exam (English)
INSERT INTO exams (title, time_limit, deadline, created_by, subject)
VALUES ('English Exam 1', 60, '2024-12-17 10:00:00', 1, 'English');

-- Get the exam ID
SET @exam_id = LAST_INSERT_ID();

-- Insert sample exam questions
INSERT INTO exam_questions (exam_id, question_type, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer)
VALUES 
(@exam_id, 'multiple_choice' , "French people love cooking, ______________ the English don't seem very interested.", 'When', 'Whenever', 'Where', 'Whereas', 'D'),
(@exam_id, 'multiple_choice' , '______________ is the one who starts the communication.?', 'sender', 'receiver', 'feedback', 'noise', 'A'),
(@exam_id, 'multiple_choice' , '______________ is the manner in which the encoded message is transmitted.', 'Message', 'Voice', 'Media', 'Channel', 'C'),
(@exam_id, 'multiple_choice' , 'The receiver confirms to the sender that he has received the message and understood it through ______________.', 'feedback', 'decoding', 'encoding', 'receiving', 'A'),
(@exam_id, 'multiple_choice' , "There are ______________ C's in Communication principles.", 'eight', 'seven', 'nine', 'five', 'B');

-- Insert sample exam questions
INSERT INTO exam_questions (exam_id, question_type, question_text, correct_answer)
VALUES 

(@exam_id, 'identification', 'A brief literary writing that is explanatory in nature _________ ', 'Essay'),
(@exam_id, 'identification', 'A type of story that a person can read in one sitting is ________ ', 'Short Story'),
(@exam_id, 'identification', 'A long fictional story with a complex plot and has chapters is_________', 'Novel'),
(@exam_id, 'identification', "A daily journal or account of the writer's personal experiences, thoughts, activities, or observations is _________ ", 'Diary'),
(@exam_id, 'identification', 'A story of a certain individual written by someone who knows him well is _________', 'Biography');

-- Insert sample exam questions
INSERT INTO exam_questions (exam_id, question_type, question_text, correct_answer)
VALUES 

(@exam_id, 'true_false', '"Whom" is used as an object, while “Who” is used as a subject.', 'True'),
(@exam_id, 'true_false', 'An adverb can modify a noun.', 'False'),
(@exam_id, 'true_false', '"Its" and "It’s" have the same meaning.', 'False'),
(@exam_id, 'true_false', 'The Oxford comma is a mandatory element in English grammar.', 'False'),
(@exam_id, 'true_false', '“Affect” is a verb, and “effect” is a noun.', 'True');




-- Get the assignment ID after the insert (to use if needed)
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



CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100),
    role ENUM('student', 'professor') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
