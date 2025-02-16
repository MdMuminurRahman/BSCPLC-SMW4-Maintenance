<?php
class Maintenance {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getLastUploadTime() {
        $this->db->query("SELECT MAX(uploaded_at) as last_upload FROM maintenance_records");
        return $this->db->single()->last_upload;
    }

    public function createMaintenance($data) {
        $this->db->query("INSERT INTO maintenance_records (title, start_time, end_time) 
                         VALUES (:title, :start_time, :end_time)");
        
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':start_time', $data['start_time']);
        $this->db->bind(':end_time', $data['end_time']);

        $this->db->execute();
        return $this->db->dbh->lastInsertId();
    }

    public function addCircuit($maintenanceId, $circuitId) {
        // First, add to maintenance_circuits
        $this->db->query("INSERT INTO maintenance_circuits (maintenance_id, circuit_id) 
                         VALUES (:maintenance_id, :circuit_id)");
        
        $this->db->bind(':maintenance_id', $maintenanceId);
        $this->db->bind(':circuit_id', $circuitId);
        $this->db->execute();

        // Then, get circuit details and add to affected_circuits
        $this->db->query("INSERT INTO affected_circuits (maintenance_id, circuit_id, bandwidth)
                         SELECT :maintenance_id, c.circuit_id, c.bandwidth
                         FROM circuit_lists c
                         WHERE c.circuit_id = :circuit_id
                         AND LOWER(c.status) = 'active'");
        
        $this->db->bind(':maintenance_id', $maintenanceId);
        $this->db->bind(':circuit_id', $circuitId);
        return $this->db->execute();
    }

    public function getAllMaintenanceRecords() {
        $this->db->query("SELECT * FROM maintenance_records ORDER BY start_time DESC");
        return $this->db->resultSet();
    }

    public function getMaintenanceById($id) {
        $this->db->query("SELECT * FROM maintenance_records WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function getAffectedCircuits($maintenanceId) {
        $this->db->query("SELECT circuit_id, bandwidth 
                         FROM affected_circuits 
                         WHERE maintenance_id = :maintenance_id 
                         ORDER BY circuit_id");
        $this->db->bind(':maintenance_id', $maintenanceId);
        return $this->db->resultSet();
    }

    public function updateMaintenance($id, $data) {
        $this->db->query("UPDATE maintenance_records 
                         SET title = :title, 
                             start_time = :start_time, 
                             end_time = :end_time 
                         WHERE id = :id");
        
        $this->db->bind(':id', $id);
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':start_time', $data['start_time']);
        $this->db->bind(':end_time', $data['end_time']);

        return $this->db->execute();
    }

    public function deleteMaintenance($id) {
        // Cascade delete will handle related records in other tables
        $this->db->query("DELETE FROM maintenance_records WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}