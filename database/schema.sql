-- NIJOSTAM Journal Management System
-- Canonical schema for a fresh installation.
-- Target: MySQL 8 / MariaDB, utf8mb4

CREATE DATABASE IF NOT EXISTS nijostam_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nijostam_db;

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(254) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('author','editor','reviewer','admin') NOT NULL DEFAULT 'author',
  status ENUM('active','suspended') NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  last_login_ip VARCHAR(45) NULL,
  reset_token VARCHAR(64) NULL,
  reset_expires_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role),
  KEY idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS author_profiles (
  user_id BIGINT UNSIGNED NOT NULL,
  affiliation VARCHAR(255) NULL,
  department VARCHAR(180) NULL,
  institution VARCHAR(255) NULL,
  country VARCHAR(120) NULL,
  phone VARCHAR(40) NULL,
  bio TEXT NULL,
  is_corresponding TINYINT(1) NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  CONSTRAINT fk_author_profiles_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS manuscripts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  reference_no VARCHAR(32) NOT NULL,
  title VARCHAR(600) NOT NULL,
  abstract TEXT NULL,
  keywords VARCHAR(500) NULL,
  subject VARCHAR(120) NULL,
  article_type VARCHAR(60) NULL,
  language VARCHAR(40) NOT NULL DEFAULT 'English',
  status VARCHAR(32) NOT NULL DEFAULT 'submitted',
  revision_round SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  submitted_by BIGINT UNSIGNED NOT NULL,
  submitted_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  screened_at DATETIME NULL,
  screened_by BIGINT UNSIGNED NULL,
  decided_at DATETIME NULL,
  decided_by BIGINT UNSIGNED NULL,
  decision VARCHAR(40) NULL,
  published_article_id BIGINT UNSIGNED NULL,
  notes TEXT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_manuscripts_reference (reference_no),
  KEY idx_manuscripts_status (status),
  KEY idx_manuscripts_submitter (submitted_by),
  KEY idx_manuscripts_submitted_at (submitted_at),
  CONSTRAINT fk_manuscripts_submitter FOREIGN KEY (submitted_by) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS manuscript_authors (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  manuscript_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  name VARCHAR(160) NOT NULL,
  email VARCHAR(254) NULL,
  affiliation VARCHAR(255) NULL,
  country VARCHAR(120) NULL,
  is_corresponding TINYINT(1) NOT NULL DEFAULT 0,
  author_order SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_ma_manuscript (manuscript_id),
  CONSTRAINT fk_ma_manuscript FOREIGN KEY (manuscript_id) REFERENCES manuscripts (id) ON DELETE CASCADE,
  CONSTRAINT fk_ma_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS manuscript_files (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  manuscript_id BIGINT UNSIGNED NOT NULL,
  file_type VARCHAR(32) NOT NULL DEFAULT 'manuscript',
  original_name VARCHAR(255) NOT NULL,
  stored_path VARCHAR(500) NOT NULL,
  mime_type VARCHAR(120) NULL,
  size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
  version SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  uploaded_by BIGINT UNSIGNED NULL,
  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_mf_manuscript (manuscript_id),
  CONSTRAINT fk_mf_manuscript FOREIGN KEY (manuscript_id) REFERENCES manuscripts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS editor_assignments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  manuscript_id BIGINT UNSIGNED NOT NULL,
  editor_id BIGINT UNSIGNED NOT NULL,
  assigned_by BIGINT UNSIGNED NULL,
  assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  screening_status VARCHAR(32) NULL,
  screening_notes TEXT NULL,
  PRIMARY KEY (id),
  KEY idx_ea_manuscript (manuscript_id),
  KEY idx_ea_editor (editor_id),
  CONSTRAINT fk_ea_manuscript FOREIGN KEY (manuscript_id) REFERENCES manuscripts (id) ON DELETE CASCADE,
  CONSTRAINT fk_ea_editor FOREIGN KEY (editor_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviewer_assignments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  manuscript_id BIGINT UNSIGNED NOT NULL,
  reviewer_id BIGINT UNSIGNED NOT NULL,
  editor_id BIGINT UNSIGNED NULL,
  status VARCHAR(24) NOT NULL DEFAULT 'invited',
  invited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  responded_at DATETIME NULL,
  due_at DATETIME NULL,
  comments_for_author TEXT NULL,
  comments_for_editor TEXT NULL,
  recommendation VARCHAR(32) NULL,
  PRIMARY KEY (id),
  KEY idx_ra_manuscript (manuscript_id),
  KEY idx_ra_reviewer (reviewer_id),
  KEY idx_ra_status (status),
  CONSTRAINT fk_ra_manuscript FOREIGN KEY (manuscript_id) REFERENCES manuscripts (id) ON DELETE CASCADE,
  CONSTRAINT fk_ra_reviewer FOREIGN KEY (reviewer_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_ra_editor FOREIGN KEY (editor_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviews (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  manuscript_id BIGINT UNSIGNED NOT NULL,
  reviewer_assignment_id BIGINT UNSIGNED NULL,
  reviewer_id BIGINT UNSIGNED NOT NULL,
  round SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  recommendation VARCHAR(32) NULL,
  comments_for_author TEXT NULL,
  comments_for_editor TEXT NULL,
  submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_reviews_manuscript (manuscript_id),
  KEY idx_reviews_reviewer (reviewer_id),
  CONSTRAINT fk_reviews_manuscript FOREIGN KEY (manuscript_id) REFERENCES manuscripts (id) ON DELETE CASCADE,
  CONSTRAINT fk_reviews_reviewer FOREIGN KEY (reviewer_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS editorial_decisions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  manuscript_id BIGINT UNSIGNED NOT NULL,
  editor_id BIGINT UNSIGNED NOT NULL,
  decision VARCHAR(40) NOT NULL,
  letter TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ed_manuscript (manuscript_id),
  CONSTRAINT fk_ed_manuscript FOREIGN KEY (manuscript_id) REFERENCES manuscripts (id) ON DELETE CASCADE,
  CONSTRAINT fk_ed_editor FOREIGN KEY (editor_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS revision_history (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  manuscript_id BIGINT UNSIGNED NOT NULL,
  version SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  notes TEXT NULL,
  file_id BIGINT UNSIGNED NULL,
  submitted_by BIGINT UNSIGNED NULL,
  submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_rh_manuscript (manuscript_id),
  CONSTRAINT fk_rh_manuscript FOREIGN KEY (manuscript_id) REFERENCES manuscripts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS journal_issues (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  volume VARCHAR(12) NOT NULL,
  issue_number VARCHAR(12) NOT NULL,
  period VARCHAR(80) NOT NULL,
  published_at DATE NOT NULL,
  cover_url VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_issue (volume, issue_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS published_articles (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  issue_id BIGINT UNSIGNED NOT NULL,
  manuscript_id BIGINT UNSIGNED NULL,
  title VARCHAR(600) NOT NULL,
  authors TEXT NULL,
  pages VARCHAR(30) NULL,
  category VARCHAR(100) NULL,
  doi VARCHAR(120) NULL,
  abstract TEXT NULL,
  keywords VARCHAR(500) NULL,
  article_url VARCHAR(500) NULL,
  pdf_url VARCHAR(500) NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  published_at DATE NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_pa_issue (issue_id),
  KEY idx_pa_manuscript (manuscript_id),
  CONSTRAINT fk_pa_issue FOREIGN KEY (issue_id) REFERENCES journal_issues (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  type VARCHAR(40) NOT NULL DEFAULT 'general',
  title VARCHAR(200) NOT NULL,
  message TEXT NULL,
  link VARCHAR(300) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notif_user (user_id, is_read),
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(60) NULL,
  entity_id BIGINT UNSIGNED NULL,
  description VARCHAR(500) NULL,
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_user (user_id),
  KEY idx_audit_entity (entity_type, entity_id),
  KEY idx_audit_created (created_at),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(80) NOT NULL,
  setting_value TEXT NULL,
  label VARCHAR(160) NULL,
  setting_group VARCHAR(60) NOT NULL DEFAULT 'general',
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS announcements (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(500) NOT NULL,
  published_at DATE NOT NULL,
  summary TEXT NULL,
  url VARCHAR(500) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_messages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(254) NOT NULL,
  subject VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('new','read','closed') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings (setting_key, setting_value, label, setting_group) VALUES
  ('journal_name', 'NIJOSTAM', 'Journal name', 'journal'),
  ('journal_full_name', 'Nigerian Journal of Science, Technology, and Applied Materials', 'Full journal name', 'journal'),
  ('publisher', 'Nigerian Institute of Leather and Science Technology', 'Publisher', 'journal'),
  ('eissn', '3027-2580', 'eISSN', 'journal'),
  ('publication_frequency', 'Quarterly', 'Publication frequency', 'journal'),
  ('apc_nigeria', '25000', 'Article processing charge (NGN)', 'fees'),
  ('apc_international', '30', 'Article processing charge (USD)', 'fees'),
  ('review_period_days', '21', 'Peer review period (days)', 'workflow'),
  ('manuscript_prefix', 'NIJOSTAM', 'Manuscript reference prefix', 'workflow'),
  ('max_file_size_mb', '10', 'Maximum upload size (MB)', 'uploads'),
  ('allowed_file_types', 'application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/msword', 'Allowed file types', 'uploads'),
  ('submission_open', '1', 'Accepting submissions', 'workflow'),
  ('social_facebook', 'https://www.facebook.com/nijostam', 'Facebook page URL', 'social'),
  ('social_x', 'https://x.com/nijostam', 'X (Twitter) profile URL', 'social'),
  ('social_instagram', 'https://www.instagram.com/nijostam', 'Instagram profile URL', 'social'),
  ('social_linkedin', 'https://www.linkedin.com/company/nijostam', 'LinkedIn page URL', 'social'),
  ('social_youtube', 'https://www.youtube.com/@nijostam', 'YouTube channel URL', 'social'),
  ('social_whatsapp', 'https://wa.me/2348000000000', 'WhatsApp contact URL', 'social');

SET FOREIGN_KEY_CHECKS = 1;
