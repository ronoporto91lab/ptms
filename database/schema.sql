CREATE TABLE IF NOT EXISTS users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Student', 'Tutor', 'Coordinator') NOT NULL,
    contact_number VARCHAR(30) NOT NULL
);

CREATE TABLE IF NOT EXISTS subjects (
    subject_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS tutor_subjects (
    tutor_subject_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tutor_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    FOREIGN KEY (tutor_id) REFERENCES users(user_id),
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id),
    UNIQUE (tutor_id, subject_id)
);

CREATE TABLE IF NOT EXISTS tutoring_sessions (
    session_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tutor_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    session_date DATE NOT NULL,
    time_slot VARCHAR(50) NOT NULL,
    status ENUM('Pending', 'Confirmed', 'Completed', 'Cancelled', 'No-show') NOT NULL DEFAULT 'Pending',
    location VARCHAR(150) NOT NULL,
    FOREIGN KEY (tutor_id) REFERENCES users(user_id),
    FOREIGN KEY (student_id) REFERENCES users(user_id),
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id),
    INDEX (session_date), INDEX (status)
);

CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    booking_date DATE NOT NULL,
    status VARCHAR(30) NOT NULL,
    FOREIGN KEY (session_id) REFERENCES tutoring_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id)
);

CREATE TABLE IF NOT EXISTS payments (
    payment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    status VARCHAR(30) NOT NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS feedback (
    feedback_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comments VARCHAR(500) NOT NULL,
    FOREIGN KEY (session_id) REFERENCES tutoring_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id),
    CHECK (rating BETWEEN 1 AND 5)
);

CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    message VARCHAR(255) NOT NULL,
    date_sent DATE NOT NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

INSERT IGNORE INTO users (user_id,name,email,password,role,contact_number) VALUES
(1,'Jasmine Reyes','jasmine@ptms.test','$2y$10$Sf/7NNJAPTfQ1rqxszFkLeTIZGXoGp3bRI7IJbRqkoHTn3fMVlP.a','Coordinator','09170000001'),
(2,'Marco Santos','marco@ptms.test','login-disabled','Tutor','09170000002'),
(3,'Nina Garcia','nina@ptms.test','login-disabled','Tutor','09170000003'),
(4,'Alyssa Cruz','alyssa@ptms.test','login-disabled','Student','09170000004'),
(5,'Paolo Reyes','paolo@ptms.test','login-disabled','Student','09170000005'),
(6,'Carl Joshua Cosep','carl.cosep@ptms.test','$2y$10$Uij50QMbrAIe9zWcRnAYceoW3TKLy9WEANfBCSGR0sXGqHggTJsn2','Coordinator','N/A');

INSERT IGNORE INTO subjects (subject_id,subject_name,description) VALUES
(1,'Data Structures','Trees, graphs, sorting, and algorithm fundamentals.'),
(2,'Database Systems','Relational design, SQL, normalization, and transactions.');

INSERT IGNORE INTO tutor_subjects (tutor_subject_id,tutor_id,subject_id) VALUES (1,2,1),(2,3,2);

INSERT IGNORE INTO tutoring_sessions
(session_id,tutor_id,student_id,subject_id,session_date,time_slot,status,location) VALUES
(1,2,4,1,'2026-09-02','10:00 AM - 11:00 AM','Confirmed','Library Room 2'),
(2,3,5,2,'2026-09-03','2:00 PM - 3:00 PM','Pending','Computer Lab 1');
