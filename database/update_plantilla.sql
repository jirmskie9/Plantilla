-- Add new columns to records table
ALTER TABLE records
ADD COLUMN plantilla_no VARCHAR(50) AFTER id,
ADD COLUMN plantilla_division VARCHAR(255) AFTER plantilla_no,
ADD COLUMN plantilla_section VARCHAR(255) AFTER plantilla_division,
ADD COLUMN equivalent_division VARCHAR(255) AFTER plantilla_section,
ADD COLUMN plantilla_division_definition TEXT AFTER equivalent_division,
ADD COLUMN plantilla_section_definition TEXT AFTER plantilla_division_definition,
ADD COLUMN last_name VARCHAR(100) AFTER fullname,
ADD COLUMN first_name VARCHAR(100) AFTER last_name,
ADD COLUMN middle_name VARCHAR(100) AFTER first_name,
ADD COLUMN ext_name VARCHAR(50) AFTER middle_name,
ADD COLUMN mi VARCHAR(10) AFTER ext_name,
ADD COLUMN tech_code VARCHAR(50) AFTER item_number,
ADD COLUMN level VARCHAR(50) AFTER tech_code,
ADD COLUMN appointment_status VARCHAR(100) AFTER level,
ADD COLUMN step VARCHAR(10) AFTER sg,
ADD COLUMN monthly_salary DECIMAL(15,2) AFTER step,
ADD COLUMN date_of_birth DATE AFTER monthly_salary,
ADD COLUMN date_orig_appt DATE AFTER date_of_birth,
ADD COLUMN date_govt_srvc DATE AFTER date_orig_appt,
ADD COLUMN date_last_promotion DATE AFTER date_govt_srvc,
ADD COLUMN date_last_increment DATE AFTER date_last_promotion,
ADD COLUMN date_longevity DATE AFTER date_last_increment,
ADD COLUMN date_vacated DATE AFTER date_longevity,
ADD COLUMN vacated_due_to VARCHAR(255) AFTER date_vacated,
ADD COLUMN vacated_by VARCHAR(255) AFTER vacated_due_to,
ADD COLUMN id_no VARCHAR(50) AFTER vacated_by;

-- Add indexes for better performance
CREATE INDEX idx_plantilla_no ON records(plantilla_no);
CREATE INDEX idx_last_name ON records(last_name);
CREATE INDEX idx_first_name ON records(first_name);
CREATE INDEX idx_position_title ON records(position_title);
CREATE INDEX idx_sg ON records(sg);

-- Update existing records to split fullname into first_name, last_name, etc.
UPDATE records
SET 
    last_name = SUBSTRING_INDEX(fullname, ',', 1),
    first_name = TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(fullname, ',', 2), ',', -1)),
    middle_name = TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(fullname, ',', 3), ',', -1))
WHERE fullname LIKE '%,%';

-- Add foreign key constraint to link with divisions table
ALTER TABLE records
ADD CONSTRAINT fk_plantilla_division
FOREIGN KEY (plantilla_division) 
REFERENCES divisions(name)
ON UPDATE CASCADE;

-- Add foreign key constraint to link with sections table
ALTER TABLE records
ADD CONSTRAINT fk_plantilla_section
FOREIGN KEY (plantilla_section) 
REFERENCES divisions(name)
ON UPDATE CASCADE;
