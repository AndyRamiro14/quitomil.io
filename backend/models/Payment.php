<?php
class Payment {
    private $conn;
    private $table_name = "payments";

    public $id;
    public $user_id;
    public $amount;
    public $payment_method;
    public $transaction_id;
    public $status;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function readAll() {
        $query = "SELECT p.*, u.name as user_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN users u ON p.user_id = u.id 
                  ORDER BY p.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readByUserId($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = ? 
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        return $stmt;
    }

    public function getTotalRevenue() {
        $query = "SELECT SUM(amount) as total FROM " . $this->table_name . " 
                  WHERE status = 'completed'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'] ? $row['total'] : 0;
    }

    public function countByStatus($status) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                  WHERE status = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $status);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }

    public function getNextPayment($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = ? AND status = 'pending' 
                  ORDER BY created_at ASC 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row;
        }
        
        return null;
    }
}
?>