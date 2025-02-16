-- Create triggers for data integrity
DELIMITER //

-- Trigger to update bandwidth format before insert
CREATE TRIGGER before_circuit_insert 
BEFORE INSERT ON circuit_lists
FOR EACH ROW
BEGIN
    IF NEW.bandwidth LIKE '%VC4-64C%' THEN
        SET NEW.bandwidth = 'STM64';
    ELSEIF NEW.bandwidth LIKE '%VC4-16C%' THEN
        SET NEW.bandwidth = 'STM16';
    ELSEIF NEW.bandwidth LIKE '%VC4-4C%' THEN
        SET NEW.bandwidth = 'STM4';
    ELSEIF NEW.bandwidth LIKE '%VC4%' THEN
        SET NEW.bandwidth = 'STM1';
    END IF;
END//

-- Trigger to update bandwidth format before update
CREATE TRIGGER before_circuit_update 
BEFORE UPDATE ON circuit_lists
FOR EACH ROW
BEGIN
    IF NEW.bandwidth LIKE '%VC4-64C%' THEN
        SET NEW.bandwidth = 'STM64';
    ELSEIF NEW.bandwidth LIKE '%VC4-16C%' THEN
        SET NEW.bandwidth = 'STM16';
    ELSEIF NEW.bandwidth LIKE '%VC4-4C%' THEN
        SET NEW.bandwidth = 'STM4';
    ELSEIF NEW.bandwidth LIKE '%VC4%' THEN
        SET NEW.bandwidth = 'STM1';
    END IF;
END//

-- Trigger to log maintenance deletions
CREATE TRIGGER after_maintenance_delete
AFTER DELETE ON maintenance_records
FOR EACH ROW
BEGIN
    INSERT INTO upload_logs (file_type, file_name, uploaded_by)
    VALUES ('maintenance', CONCAT('Deleted maintenance: ', OLD.title), 
            (SELECT id FROM users WHERE id = @current_user_id));
END//

DELIMITER ;

-- Create stored procedures for common operations
DELIMITER //

-- Get all active circuits
CREATE PROCEDURE get_active_circuits()
BEGIN
    SELECT * FROM circuit_lists 
    WHERE LOWER(status) = 'active'
    ORDER BY circuit_id;
END//

-- Get affected circuits for maintenance
CREATE PROCEDURE get_affected_circuits(IN maintenance_id INT)
BEGIN
    SELECT ac.circuit_id, ac.bandwidth
    FROM affected_circuits ac
    WHERE ac.maintenance_id = maintenance_id
    ORDER BY ac.circuit_id;
END//

-- Update maintenance schedule
CREATE PROCEDURE update_maintenance_schedule(
    IN p_id INT,
    IN p_title VARCHAR(255),
    IN p_start_time DATETIME,
    IN p_end_time DATETIME
)
BEGIN
    UPDATE maintenance_records
    SET title = p_title,
        start_time = p_start_time,
        end_time = p_end_time,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_id;
END//

DELIMITER ;