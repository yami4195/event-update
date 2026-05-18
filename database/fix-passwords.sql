-- Run in phpMyAdmin if demo logins fail (password: password123)
USE hu_events;

UPDATE users SET password_hash = '$2y$10$.9fxdgbRdwnilbfoqmEudeWBELzsOHu2AEnola.dHnJPY78FAx1/i'
WHERE email IN ('admin@hu.edu.et', 'organizer@hu.edu.et', 'student@hu.edu.et');

-- Add admin if missing
INSERT INTO users (name, email, password_hash, role, status)
SELECT 'HU Admin', 'admin@hu.edu.et', '$2y$10$.9fxdgbRdwnilbfoqmEudeWBELzsOHu2AEnola.dHnJPY78FAx1/i', 'admin', 'active'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@hu.edu.et');
