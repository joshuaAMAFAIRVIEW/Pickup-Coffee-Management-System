-- Add incident_photo_path column to item_assignments table
-- This stores the file path of incident report photos when equipment is returned damaged

ALTER TABLE `item_assignments` 
ADD COLUMN `incident_photo_path` VARCHAR(500) NULL DEFAULT NULL 
COMMENT 'Path to incident report photo for damaged returns' 
AFTER `damage_details`;
