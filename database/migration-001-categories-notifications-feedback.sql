-- HU Events — Migration 001
-- Normalized categories, event lifecycle status, notifications, feedback
-- Run in phpMyAdmin on existing hu_events database (preserves event data)

USE hu_events;

-- ---------------------------------------------------------------------------
-- 1. Categories lookup table
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  description VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO categories (name, description) VALUES
  ('Technology', 'Tech talks, hackathons, and digital innovation events'),
  ('Staff', 'Staff meetings, orientations, and internal programs'),
  ('Workshop', 'Hands-on training and skill-building sessions'),
  ('Sports', 'Athletic competitions and recreational sports'),
  ('Education', 'Seminars, academic talks, and learning programs'),
  ('Business', 'Entrepreneurship, career, and professional development'),
  ('Entertainment', 'Cultural nights, social gatherings, and performances')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- ---------------------------------------------------------------------------
-- 2. Link events to categories (migrate from legacy category VARCHAR)
-- ---------------------------------------------------------------------------
SET @has_category_id = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND COLUMN_NAME = 'category_id'
);

SET @sql_add_category_id = IF(
  @has_category_id = 0,
  'ALTER TABLE events ADD COLUMN category_id INT UNSIGNED NULL AFTER description',
  'SELECT 1'
);
PREPARE stmt FROM @sql_add_category_id;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Map legacy category labels to normalized categories
UPDATE events e
SET e.category_id = (
  SELECT c.id FROM categories c WHERE c.name = CASE e.category
    WHEN 'Seminar / Academic Talk' THEN 'Education'
    WHEN 'Training' THEN 'Workshop'
    WHEN 'Club Programs' THEN 'Education'
    WHEN 'Sport Events' THEN 'Sports'
    WHEN 'Cultural Events' THEN 'Entertainment'
    WHEN 'Hackathons' THEN 'Technology'
    ELSE 'Education'
  END LIMIT 1
)
WHERE e.category_id IS NULL
  AND EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND COLUMN_NAME = 'category'
  );

-- Default any remaining rows
UPDATE events
SET category_id = (SELECT id FROM categories WHERE name = 'Education' LIMIT 1)
WHERE category_id IS NULL;

-- Drop legacy category column if present
SET @has_legacy_category = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND COLUMN_NAME = 'category'
);

SET @sql_drop_category = IF(
  @has_legacy_category > 0,
  'ALTER TABLE events DROP COLUMN category',
  'SELECT 1'
);
PREPARE stmt FROM @sql_drop_category;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Enforce FK (skip if already applied)
SET @has_fk = (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND CONSTRAINT_NAME = 'fk_events_category'
);

SET @sql_fk = IF(
  @has_fk = 0,
  'ALTER TABLE events
     MODIFY category_id INT UNSIGNED NOT NULL,
     ADD CONSTRAINT fk_events_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
     ADD INDEX idx_events_category_id (category_id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- 3. Event lifecycle status (upcoming | completed | cancelled)
-- ---------------------------------------------------------------------------
SET @status_col_type = (
  SELECT COLUMN_TYPE FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND COLUMN_NAME = 'status'
  LIMIT 1
);

SET @sql_status_widen = IF(
  @status_col_type LIKE 'enum(%draft%',
  'ALTER TABLE events MODIFY status VARCHAR(20) NOT NULL DEFAULT ''upcoming''',
  'SELECT 1'
);
PREPARE stmt FROM @sql_status_widen;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE events
SET status = CASE
  WHEN status = 'cancelled' THEN 'cancelled'
  WHEN status IN ('published', 'draft') AND TIMESTAMP(event_date, event_time) < NOW() THEN 'completed'
  WHEN status IN ('published', 'draft') THEN 'upcoming'
  ELSE status
END
WHERE status IN ('draft', 'published', 'cancelled');

SET @sql_status_enum = IF(
  @status_col_type LIKE 'enum(%draft%' OR @status_col_type LIKE 'varchar%',
  'ALTER TABLE events MODIFY status ENUM(''upcoming'', ''completed'', ''cancelled'') NOT NULL DEFAULT ''upcoming''',
  'SELECT 1'
);
PREPARE stmt FROM @sql_status_enum;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- 4. Notifications
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  message VARCHAR(500) NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notifications_user_read (user_id, is_read),
  INDEX idx_notifications_created (created_at)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- 5. Feedback
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS feedback (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  event_id INT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  comment TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_feedback_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  CONSTRAINT chk_feedback_rating CHECK (rating BETWEEN 1 AND 5),
  UNIQUE KEY uniq_feedback_user_event (user_id, event_id),
  INDEX idx_feedback_event (event_id)
) ENGINE=InnoDB;
