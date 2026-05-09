
CREATE DATABASE IF NOT EXISTS EduRIft_db;

USE EduRIft_db;

DROP TABLE IF EXISTS Material;
DROP TABLE IF EXISTS Announcement;
DROP TABLE IF EXISTS Activity;

CREATE TABLE IF NOT EXISTS Account(
	account_id INT AUTO_INCREMENT PRIMARY KEY,
	username VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS Users(
	user_id INT AUTO_INCREMENT,
    account_id INT NOT NULL,
	first_name varchar(255) NOT NULL,
    last_name varchar(255) NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    PRIMARY KEY(user_id),
    FOREIGN KEY(account_id) REFERENCES Account(account_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Classes(
    class_id INT AUTO_INCREMENT,
    class_name VARCHAR(255) NOT NULL,
    class_desc VARCHAR(255),
    class_code VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(class_id),
    created_by INT NOT NULL,
    FOREIGN KEY (created_by) REFERENCES Users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT "Active"
);

CREATE TABLE IF NOT EXISTS Class_User(
    class_user_id INT AUTO_INCREMENT,
    class_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('student', 'teacher') NOT NULL,
    PRIMARY KEY(class_user_id),
    join_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES Classes(class_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY(user_id) REFERENCES Users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE Post (
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    postedBy INT NOT NULL,
    type ENUM('assignment','material','announcement') NOT NULL,
    title VARCHAR(255),
    description TEXT,
    due_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (class_id) REFERENCES Classes(class_id)
    ON DELETE CASCADE ON UPDATE CASCADE,

    FOREIGN KEY (postedBy) REFERENCES Users(user_id)
    ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE Activity (
    activity_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL UNIQUE,
	max_score INT DEFAULT 100,
	allow_late BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (post_id) REFERENCES Post(post_id)
    ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS Submission(
	submission_id INT AUTO_INCREMENT NOT NULL,
    class_user_id INT NOT NULL,
    activity_id INT NOT NULL,
    grade DECIMAL(5,2) ,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(submission_id),
    FOREIGN KEY (class_user_id) REFERENCES Class_User(class_user_id)
	ON DELETE CASCADE
	ON UPDATE CASCADE,
    FOREIGN KEY (activity_id) REFERENCES Activity(activity_id)
	ON DELETE CASCADE
	ON UPDATE CASCADE
);

CREATE TABLE Material (
    material_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL UNIQUE,

    FOREIGN KEY (post_id) REFERENCES Post(post_id)
    ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE Announcement (
    announcement_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL UNIQUE,

    FOREIGN KEY (post_id) REFERENCES Post(post_id)
    ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE Attachment (
    attachment_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    attachment_type ENUM('image','pdf','doc','video','other') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (post_id) REFERENCES Post(post_id) ON DELETE CASCADE ON UPDATE CASCADE
);

ALTER TABLE Class_User
ADD UNIQUE (class_id, user_id);

ALTER TABLE Submission
ADD UNIQUE (class_user_id, activity_id);

CREATE INDEX idx_class_user_class ON Class_User(class_id);
CREATE INDEX idx_post_user ON Post(postedBy);
CREATE INDEX idx_submission_activity ON Submission(activity_id);
CREATE INDEX idx_class_user_user ON Class_User(user_id);
CREATE INDEX idx_post_class_user_combo ON Post(class_id, postedBy);
CREATE INDEX idx_post_class_type ON Post(class_id, type);


