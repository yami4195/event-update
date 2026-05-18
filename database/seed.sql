-- Sample data for testing on XAMPP
-- Default password for all accounts: password123

USE hu_events;

INSERT INTO users (name, email, password_hash, role, status) VALUES
('HU Admin', 'admin@hu.edu.et', '$2y$10$.9fxdgbRdwnilbfoqmEudeWBELzsOHu2AEnola.dHnJPY78FAx1/i', 'admin', 'active'),
('HU Organizer', 'organizer@hu.edu.et', '$2y$10$.9fxdgbRdwnilbfoqmEudeWBELzsOHu2AEnola.dHnJPY78FAx1/i', 'organizer', 'active'),
('Abebe Student', 'student@hu.edu.et', '$2y$10$.9fxdgbRdwnilbfoqmEudeWBELzsOHu2AEnola.dHnJPY78FAx1/i', 'student', 'active');

SET @organizer_id = (SELECT id FROM users WHERE email = 'organizer@hu.edu.et' LIMIT 1);

INSERT INTO events (organizer_id, title, description, category, event_date, event_time, location, image_path, status) VALUES
(@organizer_id, 'AI and Research Seminar', 'A seminar on AI applications in Ethiopian higher education.', 'Seminar / Academic Talk', '2026-05-15', '10:00:00', 'Main Campus Hall A', 'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?auto=format&fit=crop&w=900&q=80', 'published'),
(@organizer_id, 'Frontend Skills Training', 'Practical training on modern web frontend tools.', 'Training', '2026-05-18', '14:00:00', 'ICT Lab 2', 'https://images.unsplash.com/photo-1515879218367-8466d910aaa4?auto=format&fit=crop&w=900&q=80', 'published'),
(@organizer_id, 'Engineering Club Meetup', 'Club members discuss projects and innovation ideas.', 'Club Programs', '2026-05-21', '16:30:00', 'Engineering Block', 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=900&q=80', 'published'),
(@organizer_id, 'Inter-College Football', 'Friendly football event among colleges.', 'Sport Events', '2026-05-25', '15:00:00', 'HU Sports Field', 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?auto=format&fit=crop&w=900&q=80', 'published'),
(@organizer_id, 'Traditional Culture Night', 'Celebrate cultural performances and food.', 'Cultural Events', '2026-05-29', '18:00:00', 'Student Center', 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=900&q=80', 'published'),
(@organizer_id, 'HU Innovation Hackathon', '48-hour challenge to build impactful student solutions.', 'Hackathons', '2026-06-03', '09:00:00', 'Innovation Hub', 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=900&q=80', 'published');
