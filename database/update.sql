ALTER TABLE submissions 
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Initialize existing rows with current timestamp
UPDATE submissions SET created_at = CURRENT_TIMESTAMP;
