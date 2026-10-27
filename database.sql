-- ============================================================
-- EventPro - Online Event Management System
-- Internet Programming 622
-- Import this file in phpMyAdmin (Import tab) or run:
--     mysql -u root -p < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS eventpro
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE eventpro;

-- Drop in child-to-parent order so the file can be re-imported
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS users;

-- ------------------------------------------------------------
-- USERS
-- email is UNIQUE so the database itself prevents duplicates
-- password_hash stores the output of PHP's password_hash()
-- ------------------------------------------------------------
CREATE TABLE users (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name       VARCHAR(100)  NOT NULL,
    email           VARCHAR(150)  NOT NULL,
    password_hash   VARCHAR(255)  NOT NULL,
    profile_picture VARCHAR(60)   DEFAULT NULL,
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- EVENTS  (one user hosts many events)
-- created_at / updated_at are the timestamps required by the brief
-- ------------------------------------------------------------
CREATE TABLE events (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    title       VARCHAR(150) NOT NULL,
    description TEXT         DEFAULT NULL,
    location    VARCHAR(150) NOT NULL,
    category    VARCHAR(50)  NOT NULL,
    event_date  DATETIME     NOT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_events_date (event_date),
    KEY idx_events_user (user_id),
    CONSTRAINT fk_events_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- MESSAGES  (separate table for private messaging)
-- ------------------------------------------------------------
CREATE TABLE messages (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sender_id   INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    body        TEXT         NOT NULL,
    is_read     TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL,
    PRIMARY KEY (id),
    KEY idx_msg_pair (sender_id, receiver_id, created_at),
    KEY idx_msg_inbox (receiver_id, is_read),
    CONSTRAINT fk_msg_sender   FOREIGN KEY (sender_id)   REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_msg_receiver FOREIGN KEY (receiver_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- PASSWORD RESETS
-- Only a SHA-256 hash of the token is stored, never the token itself
-- ------------------------------------------------------------
CREATE TABLE password_resets (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    token_hash CHAR(64)     NOT NULL,
    expires_at DATETIME     NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_reset_token (token_hash),
    CONSTRAINT fk_reset_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- OPTIONAL DEMO DATA  (both accounts use the password: Password123)
-- Delete this section if you want an empty database.
-- ------------------------------------------------------------
INSERT INTO users (full_name, email, password_hash) VALUES
('Demo Host',  'host@eventpro.test',  '$2y$10$S8XVYqhPdH9A1Enw5rCH6uaoRWgru8Gt1pNifJbXsadf3o14uLs4q'),
('Demo Guest', 'guest@eventpro.test', '$2y$10$S8XVYqhPdH9A1Enw5rCH6uaoRWgru8Gt1pNifJbXsadf3o14uLs4q');

INSERT INTO events (user_id, title, description, location, category, event_date) VALUES
(1, 'Graduation Celebration', 'Speeches, awards and group photos. Formal dress code.', 'Campus Hall', 'Education', DATE_ADD(NOW(), INTERVAL 7 DAY)),
(1, 'Web Dev Workshop',       'Hands-on PHP and MySQL session for beginners.',         'Lab 3',       'Workshop',  DATE_ADD(NOW(), INTERVAL 3 DAY)),
(2, 'Community Sports Day',   'Bring the whole family. Snacks provided.',              'City Stadium','Sports',    DATE_ADD(NOW(), INTERVAL 14 DAY));
