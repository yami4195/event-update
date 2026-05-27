-- Sample data for testing on XAMPP
-- Default password for all accounts: password123

USE hu_events;

INSERT INTO categories (name, description) VALUES
  ('Technology', 'Tech talks, hackathons, and digital innovation events'),
  ('Staff', 'Staff meetings, orientations, and internal programs'),
  ('Workshop', 'Hands-on training and skill-building sessions'),
  ('Sports', 'Athletic competitions and recreational sports'),
  ('Education', 'Seminars, academic talks, and learning programs'),
  ('Business', 'Entrepreneurship, career, and professional development'),
  ('Entertainment', 'Cultural nights, social gatherings, and performances');

INSERT INTO users (name, email, password_hash, role, status) VALUES
('HU Admin', 'admin@hu.edu.et', '$2y$10$.9fxdgbRdwnilbfoqmEudeWBELzsOHu2AEnola.dHnJPY78FAx1/i', 'admin', 'active'),
('HU Organizer', 'organizer@hu.edu.et', '$2y$10$.9fxdgbRdwnilbfoqmEudeWBELzsOHu2AEnola.dHnJPY78FAx1/i', 'organizer', 'active'),
('Abebe Student', 'student@hu.edu.et', '$2y$10$.9fxdgbRdwnilbfoqmEudeWBELzsOHu2AEnola.dHnJPY78FAx1/i', 'student', 'active');

SET @organizer_id = (SELECT id FROM users WHERE email = 'organizer@hu.edu.et' LIMIT 1);
SET @cat_education = (SELECT id FROM categories WHERE name = 'Education' LIMIT 1);
SET @cat_workshop = (SELECT id FROM categories WHERE name = 'Workshop' LIMIT 1);
SET @cat_sports = (SELECT id FROM categories WHERE name = 'Sports' LIMIT 1);
SET @cat_entertainment = (SELECT id FROM categories WHERE name = 'Entertainment' LIMIT 1);
SET @cat_technology = (SELECT id FROM categories WHERE name = 'Technology' LIMIT 1);

INSERT INTO events (organizer_id, title, description, category_id, event_date, event_time, location, image_path, status) VALUES
(@organizer_id, 'AI and Research Seminar', 'A seminar on AI applications in Ethiopian higher education.', @cat_education, '2026-05-15', '10:00:00', 'Main Campus Hall A', 'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?auto=format&fit=crop&w=900&q=80', 'upcoming'),
(@organizer_id, 'Frontend Skills Training', 'Practical training on modern web frontend tools.', @cat_workshop, '2026-05-18', '14:00:00', 'ICT Lab 2', 'https://images.unsplash.com/photo-1515879218367-8466d910aaa4?auto=format&fit=crop&w=900&q=80', 'upcoming'),
(@organizer_id, 'Engineering Club Meetup', 'Club members discuss projects and innovation ideas.', @cat_education, '2026-05-21', '16:30:00', 'Engineering Block', 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=900&q=80', 'upcoming'),
(@organizer_id, 'Inter-College Football', 'Friendly football event among colleges.', @cat_sports, '2026-05-25', '15:00:00', 'HU Sports Field', 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?auto=format&fit=crop&w=900&q=80', 'upcoming'),
(@organizer_id, 'Traditional Culture Night', 'Celebrate cultural performances and food.', @cat_entertainment, '2026-05-29', '18:00:00', 'Student Center', 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=900&q=80', 'upcoming'),
(@organizer_id, 'HU Innovation Hackathon', '48-hour challenge to build impactful student solutions.', @cat_technology, '2026-06-03', '09:00:00', 'Innovation Hub', 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=900&q=80', 'upcoming');
