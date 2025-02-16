-- Add performance monitoring table
CREATE TABLE IF NOT EXISTS performance_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    operation_type VARCHAR(50) NOT NULL,
    operation_details TEXT,
    duration_ms FLOAT NOT NULL,
    memory_usage INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_operation (operation_type, created_at),
    INDEX idx_duration (duration_ms)
);

-- Add indexes for frequently accessed columns
ALTER TABLE circuit_lists
    ADD INDEX idx_admin_search (admin_a, admin_b),
    ADD INDEX idx_bandwidth (bandwidth);

ALTER TABLE maintenance_records
    ADD INDEX idx_date_range (start_time, end_time);

-- Add fulltext search capability for circuit search
ALTER TABLE circuit_lists
    ADD FULLTEXT INDEX ft_circuit_search (circuit_id, admin_a, admin_b);

-- Add partitioning for large tables
ALTER TABLE affected_circuits
    PARTITION BY RANGE (UNIX_TIMESTAMP(created_at)) (
        PARTITION p_2024_01 VALUES LESS THAN (UNIX_TIMESTAMP('2024-02-01 00:00:00')),
        PARTITION p_2024_02 VALUES LESS THAN (UNIX_TIMESTAMP('2024-03-01 00:00:00')),
        PARTITION p_2024_03 VALUES LESS THAN (UNIX_TIMESTAMP('2024-04-01 00:00:00')),
        PARTITION p_future VALUES LESS THAN MAXVALUE
    );

-- Create views for common queries
CREATE OR REPLACE VIEW v_active_circuits AS
SELECT circuit_id, admin_a, admin_b, bandwidth
FROM circuit_lists
WHERE LOWER(status) = 'active';

CREATE OR REPLACE VIEW v_upcoming_maintenance AS
SELECT m.id, m.title, m.start_time, m.end_time,
       COUNT(mc.circuit_id) as affected_circuits_count
FROM maintenance_records m
LEFT JOIN maintenance_circuits mc ON m.id = mc.maintenance_id
WHERE m.start_time > NOW()
GROUP BY m.id;

-- Add automated cleanup procedure
DELIMITER //
CREATE PROCEDURE cleanup_old_records()
BEGIN
    DECLARE cleanup_date DATE;
    SET cleanup_date = DATE_SUB(CURRENT_DATE, INTERVAL 1 YEAR);
    
    START TRANSACTION;
    
    -- Archive old maintenance records
    INSERT INTO maintenance_archives
    SELECT * FROM maintenance_records
    WHERE end_time < cleanup_date;
    
    -- Remove old records
    DELETE FROM maintenance_records WHERE end_time < cleanup_date;
    DELETE FROM performance_logs WHERE created_at < cleanup_date;
    
    -- Optimize tables
    OPTIMIZE TABLE circuit_lists;
    OPTIMIZE TABLE maintenance_records;
    OPTIMIZE TABLE affected_circuits;
    
    COMMIT;
END //
DELIMITER ;

-- Create event to run cleanup monthly
CREATE EVENT IF NOT EXISTS e_monthly_cleanup
ON SCHEDULE EVERY 1 MONTH
DO CALL cleanup_old_records();