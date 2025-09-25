<?php
class Progress {
    private $conn;
    private $table_name = "user_progress";

    public $id;
    public $user_id;
    public $module;
    public $progress;
    public $score;
    public $status;
    public $last_updated;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function readByUserId($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = ? 
                  ORDER BY last_updated DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        return $stmt;
    }

    public function getOverallProgress($user_id) {
        $query = "SELECT AVG(progress) as overall FROM " . $this->table_name . " 
                  WHERE user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['overall'] ? round($row['overall'], 2) : 0;
    }

    public function countCompletedModules($user_id) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                  WHERE user_id = ? AND progress = 100";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }

    public function countTotalModules() {
        $query = "SELECT COUNT(DISTINCT module) as total FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }
}
?>