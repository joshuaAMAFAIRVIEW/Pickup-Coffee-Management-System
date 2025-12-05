-- Add contact_employee_number column to stores table
ALTER TABLE stores 
ADD COLUMN contact_employee_number VARCHAR(50) NULL AFTER contact_person;
