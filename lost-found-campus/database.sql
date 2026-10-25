-- Lost & Found Campus Portal - schema + demo data
-- Import with phpMyAdmin (Import tab). Creates the database automatically.
-- Demo passwords: admin@campus.test = Admin@123, all students = Student@123
CREATE DATABASE IF NOT EXISTS lost_found_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lost_found_portal;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS claims;
DROP TABLE IF EXISTS items;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name     VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('student','admin') NOT NULL DEFAULT 'student',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE items (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  type        ENUM('lost','found') NOT NULL,
  title       VARCHAR(120) NOT NULL,
  category    VARCHAR(50)  NOT NULL,
  location    VARCHAR(150) NOT NULL,
  item_date   DATE NOT NULL,
  description TEXT NOT NULL,
  image       VARCHAR(100) DEFAULT NULL,
  status      ENUM('open','returned','resolved') NOT NULL DEFAULT 'open',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_items_type_status (type, status),
  KEY idx_items_category (category),
  KEY idx_items_user (user_id),
  CONSTRAINT fk_items_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE claims (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  item_id     INT UNSIGNED NOT NULL,
  claimant_id INT UNSIGNED NOT NULL,
  message     TEXT NOT NULL,
  status      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  admin_note  VARCHAR(255) DEFAULT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_claim_once (item_id, claimant_id),
  KEY idx_claims_status (status),
  CONSTRAINT fk_claims_item     FOREIGN KEY (item_id)     REFERENCES items(id) ON DELETE CASCADE,
  CONSTRAINT fk_claims_claimant FOREIGN KEY (claimant_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users (ids 1 = admin, 2-4 = students)
INSERT INTO users (id, full_name, email, password_hash, role) VALUES
(1, 'Campus Admin',  'admin@campus.test', '$2y$10$t.cPaHocdGXat/dslZJgC.3d8PDP1aggYk1NfXZoL2uDnXtsmWFMi', 'admin'),
(2, 'Aarav Sharma',  'aarav@campus.test', '$2y$10$b5284lN6RTYvAGhtbjm7Du8tGljbBzQrGF/u/EQIWYy3FnX8VSIhG', 'student'),
(3, 'Meera Iyer',    'meera@campus.test', '$2y$10$b5284lN6RTYvAGhtbjm7Du8tGljbBzQrGF/u/EQIWYy3FnX8VSIhG', 'student'),
(4, 'Rohan Das',     'rohan@campus.test', '$2y$10$b5284lN6RTYvAGhtbjm7Du8tGljbBzQrGF/u/EQIWYy3FnX8VSIhG', 'student');

-- Items
INSERT INTO items (id, user_id, type, title, category, location, item_date, description, status, created_at) VALUES
(1, 2, 'lost',  'Black laptop bag', 'Bags', 'Central Library', CURDATE() - INTERVAL 2 DAY,
 'Black Wildcraft laptop bag with a red zip. Contains a notebook and a charger. Left near the third-floor reading hall.', 'open', NOW() - INTERVAL 2 DAY),
(2, 3, 'found', 'Black laptop bag with red zip', 'Bags', 'Central Library', CURDATE() - INTERVAL 1 DAY,
 'Found a black laptop bag under a table in the library. Handed to the library desk; ask for the reporter to verify details.', 'open', NOW() - INTERVAL 1 DAY),
(3, 3, 'lost',  'Blue water bottle', 'Other', 'Sports Complex', CURDATE() - INTERVAL 4 DAY,
 'Blue steel water bottle with a sticker of a mountain on the side. Lost after evening practice.', 'open', NOW() - INTERVAL 4 DAY),
(4, 4, 'found', 'Student ID card', 'ID & Cards', 'Main Canteen', CURDATE() - INTERVAL 3 DAY,
 'Student ID card found on a canteen table. Name is partly faded. Describe the department and photo to claim.', 'open', NOW() - INTERVAL 3 DAY),
(5, 4, 'lost',  'Scientific calculator Casio fx-991', 'Electronics', 'Exam Hall B', CURDATE() - INTERVAL 6 DAY,
 'Casio fx-991EX with initials R.D. scratched on the back cover.', 'open', NOW() - INTERVAL 6 DAY),
(6, 2, 'found', 'Casio scientific calculator', 'Electronics', 'Exam Hall B', CURDATE() - INTERVAL 5 DAY,
 'Calculator found on a desk after the mid-semester exam. Kept with the exam cell.', 'open', NOW() - INTERVAL 5 DAY),
(7, 3, 'found', 'Hostel room keys with green keychain', 'Keys', 'Block C Hostel', CURDATE() - INTERVAL 7 DAY,
 'Set of three keys on a green keychain, found near the hostel gate.', 'returned', NOW() - INTERVAL 7 DAY),
(8, 2, 'lost',  'Silver wrist watch', 'Clothing & Accessories', 'Gymnasium', CURDATE() - INTERVAL 8 DAY,
 'Silver analog watch with a brown leather strap, gift from my father.', 'resolved', NOW() - INTERVAL 8 DAY),
(9, 4, 'found', 'Wireless earbuds case', 'Electronics', 'Computer Lab 2', CURDATE() - INTERVAL 1 DAY,
 'White earbuds charging case found beside a workstation in Computer Lab 2.', 'open', NOW() - INTERVAL 1 DAY),
(10, 3, 'lost', 'Physics textbook (Halliday)', 'Books & Stationery', 'Block A Classroom 204', CURDATE() - INTERVAL 3 DAY,
 'Fundamentals of Physics with a yellow cover and my name written on the first page.', 'open', NOW() - INTERVAL 3 DAY);

-- Claims
INSERT INTO claims (id, item_id, claimant_id, message, status, admin_note, created_at, reviewed_at) VALUES
(1, 2, 2, 'This is my bag. It has a red zip, a Wildcraft logo, and my chemistry notebook and a grey charger inside.', 'pending', NULL, NOW() - INTERVAL 1 DAY, NULL),
(2, 6, 4, 'The calculator is mine. My initials R.D. are scratched on the back and the battery cover is slightly cracked.', 'pending', NULL, NOW() - INTERVAL 4 DAY, NULL),
(3, 7, 4, 'These are my room keys for C-312. The keychain is a green rubber tag with my room number written on the back.', 'approved', 'Verified with hostel warden.', NOW() - INTERVAL 6 DAY, NOW() - INTERVAL 5 DAY),
(4, 4, 2, 'I think this is my friend\'s ID card; he asked me to collect it.', 'rejected', 'Claimant could not describe the card. Please ask the owner to apply.', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 1 DAY);
