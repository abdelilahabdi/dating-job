CREATE DATABASE IF NOT EXISTS job_board;

USE job_board;


CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('ADMIN', 'STUDENT') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE students (
    user_id INT PRIMARY KEY,
    promotion VARCHAR(100) NOT NULL,
    specialization VARCHAR(100) NOT NULL,
    CONSTRAINT fk_student_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    sector VARCHAR(100) NOT NULL,
    location VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(30),
    avatar VARCHAR(255),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE job_offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    contract_type VARCHAR(50) NOT NULL,
    location VARCHAR(150) NOT NULL,
    image VARCHAR(255),
    description TEXT NOT NULL,
    skills TEXT,
    deleted BOOLEAN NOT NULL DEFAULT FALSE,
    company_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_job_company
        FOREIGN KEY (company_id)
        REFERENCES companies(id)
        ON DELETE CASCADE
);

CREATE INDEX idx_job_company ON job_offers(company_id);
CREATE INDEX idx_job_deleted ON job_offers(deleted);


CREATE TABLE job_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    job_offer_id INT NOT NULL,
    status ENUM('PENDING', 'ACCEPTED', 'REJECTED') NOT NULL DEFAULT 'PENDING',
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cover_letter TEXT,
    phone VARCHAR(30),
    availability_date DATE,
    expected_salary VARCHAR(50),

    CONSTRAINT fk_application_student
        FOREIGN KEY (student_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_application_job
        FOREIGN KEY (job_offer_id)
        REFERENCES job_offers(id)
        ON DELETE CASCADE,

    CONSTRAINT uq_student_job UNIQUE (student_id, job_offer_id)
);

CREATE INDEX idx_application_job ON job_applications(job_offer_id);
CREATE INDEX idx_application_status ON job_applications(status);