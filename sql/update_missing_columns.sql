-- Add missing columns to existing tables

-- Add event_id column to return_service_activities table
ALTER TABLE return_service_activities
ADD COLUMN event_id INT NULL,
ADD CONSTRAINT fk_rs_event_id FOREIGN KEY (event_id) REFERENCES events(id);

-- Add event_id column to announcements table
ALTER TABLE announcements
ADD COLUMN event_id INT NULL,
ADD CONSTRAINT fk_announcement_event_id FOREIGN KEY (event_id) REFERENCES events(id);

-- Add hours column to return_service_activities if it doesn't exist
ALTER TABLE return_service_activities
ADD COLUMN hours INT NULL;

-- Add proof_file column to return_service_activities if it does n't exist
ALTER TABLE return_service_activities
ADD COLUMN proof_file VARCHAR(255) NULL;

-- Add title column to return_service_activities if it doesn't exist
ALTER TABLE return_service_activities
ADD COLUMN title VARCHAR(255) NULL;

-- Add user_id column to return_service_activities if it doesn't exist (to replace student_id)
ALTER TABLE return_service_activities
ADD COLUMN user_id INT NULL,
ADD CONSTRAINT fk_rs_user_id FOREIGN KEY (user_id) REFERENCES users(id);