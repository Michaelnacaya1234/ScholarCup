-- Add missing columns to student_profiles table
ALTER TABLE student_profiles
ADD COLUMN student_id VARCHAR(20) NOT NULL UNIQUE,
ADD COLUMN contact_number VARCHAR(15);

-- Add missing column to staff_profiles table
ALTER TABLE staff_profiles
ADD COLUMN staff_id VARCHAR(20) NOT NULL UNIQUE;
