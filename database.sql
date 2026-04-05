CREATE DATABASE IF NOT EXISTS freelance_platform

USE freelance_platform;

--  USERS
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    full_name   VARCHAR(100)    NOT NULL,
    email       VARCHAR(150)    NOT NULL UNIQUE,
    password    VARCHAR(255)    NOT NULL,
    role        ENUM('freelancer','client') NOT NULL,
    avatar      VARCHAR(255)    DEFAULT NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

--  FREELANCER PROFILES
CREATE TABLE IF NOT EXISTS freelancer_profiles (
    id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id           INT UNSIGNED  NOT NULL UNIQUE,
    title             VARCHAR(150)  DEFAULT NULL,
    skills            TEXT          DEFAULT NULL,
    experience_level  ENUM('entry','intermediate','expert') DEFAULT 'entry',
    hourly_rate       DECIMAL(10,2) DEFAULT 0.00,
    bio               TEXT          DEFAULT NULL,
    created_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_fp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

--  JOBS
CREATE TABLE IF NOT EXISTS jobs (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    client_id   INT UNSIGNED    NOT NULL,
    title       VARCHAR(200)    NOT NULL,
    description TEXT            NOT NULL,
    budget      DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    deadline    DATE            DEFAULT NULL,
    status      ENUM('open','closed') NOT NULL DEFAULT 'open',
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_job_client FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

--  PROPOSALS
CREATE TABLE IF NOT EXISTS proposals (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    job_id          INT UNSIGNED    NOT NULL,
    freelancer_id   INT UNSIGNED    NOT NULL,
    proposal_text   TEXT            NOT NULL,
    bid_amount      DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    status          ENUM('pending','accepted','rejected','completed') NOT NULL DEFAULT 'pending',
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_proposal (job_id, freelancer_id),
    CONSTRAINT fk_prop_job  FOREIGN KEY (job_id)        REFERENCES jobs(id)  ON DELETE CASCADE,
    CONSTRAINT fk_prop_fl   FOREIGN KEY (freelancer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

--  MESSAGES
CREATE TABLE IF NOT EXISTS messages (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    sender_id       INT UNSIGNED    NOT NULL,
    recipient_id    INT UNSIGNED    NOT NULL,
    content         TEXT            NOT NULL,
    is_read         BOOLEAN         NOT NULL DEFAULT 0,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_msg_sender    FOREIGN KEY (sender_id)    REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_msg_recipient FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_conversation (sender_id, recipient_id),
    INDEX idx_recipient (recipient_id),
    INDEX idx_unread (recipient_id, is_read)
) ENGINE=InnoDB;

--  REVIEWS
CREATE TABLE IF NOT EXISTS reviews (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    proposal_id     INT UNSIGNED    NOT NULL,
    client_id       INT UNSIGNED    NOT NULL,
    freelancer_id   INT UNSIGNED    NOT NULL,
    rating          TINYINT         NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text     TEXT            DEFAULT NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_review_proposal (proposal_id),
    CONSTRAINT fk_rev_proposal  FOREIGN KEY (proposal_id)   REFERENCES proposals(id) ON DELETE CASCADE,
    CONSTRAINT fk_rev_client    FOREIGN KEY (client_id)     REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_rev_freelancer FOREIGN KEY (freelancer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;