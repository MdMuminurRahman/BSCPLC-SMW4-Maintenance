<?php
class User {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function findUserByEmail($email) {
        $this->db->query("SELECT * FROM users WHERE email = :email");
        $this->db->bind(':email', $email);
        return $this->db->single();
    }

    public function createUser($data) {
        $this->db->query("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
        
        // Bind values
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':password', $data['password']);

        // Execute
        try {
            return $this->db->execute();
        } catch(PDOException $e) {
            // Check if error is due to duplicate email
            if($e->errorInfo[1] === 1062) {
                return false;
            }
            throw $e;
        }
    }

    public function updateUserProfile($id, $data) {
        $this->db->query("UPDATE users SET name = :name, email = :email WHERE id = :id");
        
        $this->db->bind(':id', $id);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':email', $data['email']);

        return $this->db->execute();
    }

    public function changePassword($id, $newPassword) {
        $this->db->query("UPDATE users SET password = :password WHERE id = :id");
        
        $this->db->bind(':id', $id);
        $this->db->bind(':password', password_hash($newPassword, PASSWORD_DEFAULT));

        return $this->db->execute();
    }
}