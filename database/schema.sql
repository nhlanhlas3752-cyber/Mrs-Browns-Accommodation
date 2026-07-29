-- =============================================================
--  Hilret Trust — Full Database Schema
--  Engine: MySQL 8.0+
--  Run: import via phpMyAdmin or mysql CLI
-- =============================================================

CREATE DATABASE IF NOT EXISTS hilret_trust
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE hilret_trust;

-- -------------------------------------------------------------
-- 1. ADMIN USERS
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100)  NOT NULL,
  email         VARCHAR(150)  UNIQUE NOT NULL,
  password_hash VARCHAR(255)  NOT NULL,
  role          ENUM('admin','manager') DEFAULT 'admin',
  last_login    DATETIME      NULL,
  created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- DEPLOYMENT: Change this password immediately after importing via the dashboard forgot-password flow
INSERT INTO users (name, email, password_hash, role) VALUES
('Hilret Admin', 'admin@hilrettrust.co.za',
 '$2y$10$lurP5xFX0gLFY1CCf11H.uwrc4qcXsEFVh4MgTo17IL6fF7BuSAIO', 'admin');


-- -------------------------------------------------------------
-- 2. PASSWORD RESETS
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_resets (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(150) NOT NULL,
  token      VARCHAR(64)  NOT NULL UNIQUE,
  expires_at DATETIME     NOT NULL,
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_attempts (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  identifier   VARCHAR(100) NOT NULL,
  attempted_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_identifier (identifier),
  INDEX idx_attempted_at (attempted_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_log (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT          NULL,
  action     VARCHAR(100) NOT NULL,
  entity     VARCHAR(50)  NULL,
  entity_id  INT          NULL,
  details    TEXT         NULL,
  ip_address VARCHAR(45)  NULL,
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;


-- -------------------------------------------------------------
-- 3. RESIDENCES
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS residences (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  name             VARCHAR(100)   NOT NULL,
  slug             VARCHAR(100)   UNIQUE NOT NULL,
  address          VARCHAR(255),
  region           VARCHAR(100)   DEFAULT 'Botanic Grove',
  price_from       DECIMAL(10,2)  DEFAULT 0.00,
  email            VARCHAR(150),
  phone            VARCHAR(30),
  description      TEXT,
  total_rooms      INT            DEFAULT 0,
  available_rooms  INT            DEFAULT 0,
  hero_image       VARCHAR(255),
  detail_page      VARCHAR(100),
  status           ENUM('active','inactive') DEFAULT 'active',
  created_at       TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO residences (name, slug, address, region, price_from, email, phone, description, total_rooms, available_rooms, hero_image, detail_page) VALUES
('House 29', 'house-29', '29 Botanic Grove, Berea, Durban', 'Botanic Grove', 4200.00,
 'westview@hilrettrust.co.za', '031 000 1234',
 'A fully-furnished residence in Botanic Grove — secure, connected, and move-in ready.',
 12, 2, 'hause 29.jpg', 'residence-view-29.html'),

('House 35', 'house-35', '35 Botanic Grove, Berea, Durban', 'Botanic Grove', 3850.00,
 'meridian@hilrettrust.co.za', '035 000 5678',
 'Modern, affordable accommodation with all amenities included.',
 10, 2, 'DSC_0011.jpg', 'residence-view-35.html'),

('House 39', 'house-39', '39 Botanic Grove, Berea, Durban', 'Botanic Grove', 3850.00,
 'meridian@hilrettrust.co.za', '035 000 5678',
 'Comfortable living close to DUT campus and Berea Center shopping.',
 10, 2, 'DSC_0020.jpg', 'residence-view-39.html'),

('House 28', 'house-28', '28 Botanic Grove, Berea, Durban', 'Botanic Grove', 3600.00,
 'harbour@hilrettrust.co.za', '035 000 9012',
 'Great value accommodation in a quiet section of Botanic Grove.',
 8, 2, 'DSC_0031.jpg', 'residence-view-28.html');


-- -------------------------------------------------------------
-- 3. ROOMS
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rooms (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  residence_id  INT          NOT NULL,
  room_number   VARCHAR(20)  NOT NULL,
  type          ENUM('single','double','shared') DEFAULT 'single',
  floor         TINYINT      DEFAULT 1,
  price         DECIMAL(10,2),
  status        ENUM('available','occupied','maintenance') DEFAULT 'available',
  notes         TEXT,
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (residence_id) REFERENCES residences(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- House 29 rooms (id=1)
INSERT INTO rooms (residence_id, room_number, type, floor, price, status) VALUES
(1,'101','single',1,4200.00,'occupied'),
(1,'102','single',1,4200.00,'occupied'),
(1,'103','single',1,4200.00,'available'),
(1,'104','single',1,4200.00,'available'),
(1,'201','single',2,4200.00,'occupied'),
(1,'202','single',2,4200.00,'occupied'),
(1,'203','single',2,4200.00,'occupied'),
(1,'204','single',2,4200.00,'occupied'),
(1,'301','single',3,4200.00,'occupied'),
(1,'302','single',3,4200.00,'occupied'),
(1,'303','single',3,4200.00,'occupied'),
(1,'304','single',3,4200.00,'occupied');

-- House 35 rooms (id=2)
INSERT INTO rooms (residence_id, room_number, type, floor, price, status) VALUES
(2,'101','single',1,3850.00,'occupied'),
(2,'102','single',1,3850.00,'occupied'),
(2,'103','single',1,3850.00,'available'),
(2,'104','single',1,3850.00,'available'),
(2,'201','single',2,3850.00,'occupied'),
(2,'202','single',2,3850.00,'occupied'),
(2,'203','single',2,3850.00,'occupied'),
(2,'204','single',2,3850.00,'occupied'),
(2,'301','single',3,3850.00,'occupied'),
(2,'302','single',3,3850.00,'occupied');

-- House 39 rooms (id=3)
INSERT INTO rooms (residence_id, room_number, type, floor, price, status) VALUES
(3,'101','single',1,3850.00,'occupied'),
(3,'102','single',1,3850.00,'occupied'),
(3,'103','single',1,3850.00,'available'),
(3,'104','single',1,3850.00,'available'),
(3,'201','single',2,3850.00,'occupied'),
(3,'202','single',2,3850.00,'occupied'),
(3,'203','single',2,3850.00,'occupied'),
(3,'204','single',2,3850.00,'occupied'),
(3,'301','single',3,3850.00,'occupied'),
(3,'302','single',3,3850.00,'occupied');

-- House 28 rooms (id=4)
INSERT INTO rooms (residence_id, room_number, type, floor, price, status) VALUES
(4,'101','single',1,3600.00,'occupied'),
(4,'102','single',1,3600.00,'occupied'),
(4,'103','single',1,3600.00,'available'),
(4,'104','single',1,3600.00,'available'),
(4,'201','single',2,3600.00,'occupied'),
(4,'202','single',2,3600.00,'occupied'),
(4,'203','single',2,3600.00,'occupied'),
(4,'204','single',2,3600.00,'occupied');


-- -------------------------------------------------------------
-- 4. APPLICATIONS
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS applications (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  -- Step 1: Personal
  first_name          VARCHAR(100)  NOT NULL,
  last_name           VARCHAR(100)  NOT NULL,
  email               VARCHAR(150)  NOT NULL,
  phone               VARCHAR(30)   NOT NULL,
  dob                 DATE,
  gender              ENUM('male','female','other') DEFAULT 'male',
  -- Step 2: Background
  occupation          VARCHAR(50)   NOT NULL,
  employer            VARCHAR(200),
  funding_source      VARCHAR(50)   NOT NULL,
  -- Step 3: Preferences
  preferred_residence VARCHAR(50),
  room_type           VARCHAR(50)   DEFAULT 'single',
  move_in_date        DATE,
  notes               TEXT,
  -- Admin
  status              ENUM('pending','approved','rejected','waitlisted') DEFAULT 'pending',
  reviewed_by         INT           NULL,
  reviewed_at         DATETIME      NULL,
  review_notes        TEXT,
  terms_accepted      TINYINT(1)    DEFAULT 0,
  -- Document uploads
  doc_id_path          VARCHAR(255)  NULL,
  doc_enrollment_path  VARCHAR(255)  NULL,
  doc_bursary_path     VARCHAR(255)  NULL,
  doc_sponsorship_path VARCHAR(255)  NULL,
  doc_income_path      VARCHAR(255)  NULL,
  submitted_at        TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Sample applications
INSERT INTO applications (first_name, last_name, email, phone, dob, gender, occupation, employer, funding_source, preferred_residence, room_type, move_in_date, status, terms_accepted) VALUES
('Thabo', 'Mokoena', 'thabo@example.com', '082 111 2222', '2002-03-15', 'male', 'student', 'DUT', 'bursary', 'house29', 'single', '2026-02-01', 'pending', 1),
('Zanele', 'Dlamini', 'zanele@example.com', '083 333 4444', '2001-07-20', 'female', 'student', 'DUT', 'family', 'house35', 'single', '2026-02-01', 'approved', 1),
('Sipho', 'Nkosi', 'sipho@example.com', '084 555 6666', '2000-11-05', 'male', 'working', 'Transnet', 'self', 'house28', 'single', '2026-01-15', 'approved', 1),
('Nomsa', 'Khumalo', 'nomsa@example.com', '085 777 8888', '2003-01-30', 'female', 'student', 'DUT', 'bursary', 'house39', 'single', '2026-02-01', 'pending', 1),
('Lethiwe', 'Buthelezi', 'lethiwe@example.com', '086 999 0000', '2001-05-10', 'female', 'job-seeker', NULL, 'family', 'house29', 'single', '2026-03-01', 'waitlisted', 1);


-- -------------------------------------------------------------
-- 5. RESIDENTS (active tenants)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS residents (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  application_id   INT           NOT NULL,
  room_id          INT           NOT NULL,
  lease_start      DATE,
  lease_end        DATE,
  monthly_rent     DECIMAL(10,2),
  deposit_amount   DECIMAL(10,2),
  deposit_status   ENUM('unpaid','paid','refunded') DEFAULT 'unpaid',
  status           ENUM('active','moved-out','terminated') DEFAULT 'active',
  created_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (application_id) REFERENCES applications(id),
  FOREIGN KEY (room_id) REFERENCES rooms(id)
) ENGINE=InnoDB;

-- Zanele in House 35 room 101, Sipho in House 28 room 101
INSERT INTO residents (application_id, room_id, lease_start, lease_end, monthly_rent, deposit_amount, deposit_status, status) VALUES
(2, 11, '2026-02-01', '2026-11-30', 3850.00, 3850.00, 'paid', 'active'),
(3, 31, '2026-01-15', '2026-12-14', 3600.00, 3600.00, 'paid', 'active');


-- -------------------------------------------------------------
-- 6. ENQUIRIES (footer contact form)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS enquiries (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(150)  NOT NULL,
  email        VARCHAR(150)  NOT NULL,
  message      TEXT          NOT NULL,
  status       ENUM('new','read','replied') DEFAULT 'new',
  submitted_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO enquiries (name, email, message, status) VALUES
('Ayanda Mthembu', 'ayanda@example.com', 'Hi, I wanted to ask about parking availability at House 29. Do you have any bays left?', 'new'),
('Nokuthula Zulu', 'nokuthula@example.com', 'Good day. I am interested in applying but I am currently on NSFAS. Are there any exceptions?', 'read');


-- -------------------------------------------------------------
-- 7. FAQs
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS faqs (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  category   VARCHAR(50)   NOT NULL,
  question   TEXT          NOT NULL,
  answer     TEXT          NOT NULL,
  language   ENUM('en','zu','af') DEFAULT 'en',
  sort_order INT           DEFAULT 0,
  active     TINYINT(1)    DEFAULT 1,
  created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO faqs (category, question, answer, sort_order) VALUES
('applications','How do I apply for accommodation at Hilret Trust?','Visit our Apply page and complete the online registration form. You will need to provide your personal details, proof of current university enrolment, and select your preferred residence. The process takes less than ten minutes.',1),
('applications','What documents do I need to submit with my application?','You will need: a certified copy of your South African ID or passport; proof of current student enrolment; and proof of funding — bursary award letter, employer sponsorship letter, or bank statements.',2),
('applications','Can I apply for more than one residence?','You may indicate a preferred alternative residence on the application form. Starting a new application automatically cancels any other pending application invites you have received.',3),
('applications','How long does the application process take?','Once we have received all required documents, you will typically receive a decision within 5–7 working days.',4),
('funding','Does Hilret Trust accept NSFAS funding?','No. Hilret Trust accommodation is not accredited for NSFAS funding. Our residences are open to self-payers and students or professionals who hold a private bursary or employer sponsorship.',1),
('funding','What payment methods are accepted?','We accept direct bank transfers (EFT), and bursary payments made on behalf of residents by employers or institutions. Monthly rent is due on the 1st of each month.',2),
('funding','I have a private bursary — can it be used to pay my rent?','Yes. If your bursary provider pays accommodation costs directly to third parties, we can issue an official invoice and proof of residence for your sponsor.',3),
('documentation','What proof of enrolment do I need to provide?','We accept a current Proof of Registration (POR) issued by your institution, or an official acceptance letter for new students who have not yet registered.',1),
('documentation','What happens if my registration status changes during the year?','You are required to notify us in writing within five business days. Failure to do so may affect your lease standing and deposit refund eligibility.',2),
('amenities','Is Wi-Fi included in the monthly room rate?','Yes, high-speed fibre Wi-Fi is included in your monthly rate at all Hilret Trust residences. A fair-use policy applies.',1),
('amenities','Is parking available at Hilret Trust residences?','Most Hilret Trust residences offer secure, covered parking for an additional monthly fee, separate from your room rate. Bays are limited and allocated on a first-come, first-served basis.',2),
('amenities','Are cleaning services included in my room rate?','Our housekeeping team cleans all shared areas on a regular schedule. Individual room cleaning frequency varies by residence and room type.',3),
('amenities','Is shuttle transport to campus provided?','Select Hilret Trust residences operate a scheduled shuttle service to and from the nearest campus or public transport hub.',4),
('house-rules','Are visitors allowed at Hilret Trust residences?','Yes, visitors are permitted during designated visiting hours. All visitors must sign in at the security desk. Overnight guests must be pre-approved by residence management.',1),
('house-rules','What is the quiet-hours policy?','Standard quiet hours are 22:00 to 07:00 Monday to Friday, and 23:00 to 08:00 on weekends and public holidays.',2),
('house-rules','Are pets allowed in the residence?','No. Pets of any kind are not permitted at any Hilret Trust residence. Service animals are the only exception and must be approved in writing.',3),
('general','When can I move in?','Move-in dates are confirmed once your application is approved, your deposit has been received, and all required documents are on file.',1),
('general','What if I need to cancel my booking?','Deposits are non-refundable if you cancel fewer than 30 days before your confirmed move-in date. Contact us as soon as possible.',2),
('general','Who do I contact if there is a maintenance issue?','Log all non-urgent maintenance requests with your on-site residence manager. For urgent issues use the 24/7 emergency contact number in your lease agreement.',3),
('general','How is my deposit calculated, and will I get it back?','The deposit is equal to one month rent and is due before or at move-in. Refunded within 30 days of your move-out date, provided your room is in the same condition.',4);
