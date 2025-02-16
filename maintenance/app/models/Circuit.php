<?php
class Circuit {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getLastUploadTime() {
        $this->db->query("SELECT MAX(uploaded_at) as last_upload FROM circuit_lists");
        return $this->db->single()->last_upload;
    }

    public function truncateTable() {
        $this->db->query("TRUNCATE TABLE circuit_lists");
        return $this->db->execute();
    }

    public function insert($data) {
        $this->db->query("INSERT INTO circuit_lists (circuit_id, admin_a, admin_b, bandwidth, status) 
                         VALUES (:circuit_id, :admin_a, :admin_b, :bandwidth, :status)");
        
        $this->db->bind(':circuit_id', $data['circuit_id']);
        $this->db->bind(':admin_a', $data['admin_a']);
        $this->db->bind(':admin_b', $data['admin_b']);
        $this->db->bind(':bandwidth', $data['bandwidth']);
        $this->db->bind(':status', $data['status']);

        return $this->db->execute();
    }

    public function findByCircuitId($circuitId) {
        $this->db->query("SELECT * FROM circuit_lists WHERE circuit_id = :circuit_id");
        $this->db->bind(':circuit_id', $circuitId);
        return $this->db->single();
    }

    public function getActiveCircuits() {
        $this->db->query("SELECT * FROM circuit_lists WHERE LOWER(status) = 'active'");
        return $this->db->resultSet();
    }
}