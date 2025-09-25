<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $name;
    public $email;
    public $password;
    public $phone;
    public $city;
    public $service_type;
    public $institutions;
    public $status;
    public $payment_method;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                     SET name=:name, email=:email, password=:password, 
                     phone=:phone, city=:city, service_type=:service_type, 
                     institutions=:institutions, payment_method=:payment_method, status='active'";
            
            $stmt = $this->conn->prepare($query);
            
            // Hash de la contraseña
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);
            
            // Bind parameters
            $stmt->bindParam(":name", $this->name);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":password", $this->password);
            $stmt->bindParam(":phone", $this->phone);
            $stmt->bindParam(":city", $this->city);
            $stmt->bindParam(":service_type", $this->service_type);
            $stmt->bindParam(":institutions", $this->institutions);
            $stmt->bindParam(":payment_method", $this->payment_method);
            
            if($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch(PDOException $exception) {
            error_log("Error creating user: " . $exception->getMessage());
            return false;
        }
    }

    public function emailExists() {
        $query = "SELECT id, name, password, status, service_type 
                 FROM " . $this->table_name . " 
                 WHERE email = ? 
                 LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();
        
        $num = $stmt->rowCount();
        
        if($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->password = $row['password'];
            $this->status = $row['status'];
            $this->service_type = $row['service_type'];
            return true;
        }
        return false;
    }

    public function readAll($from_record_num = 0, $records_per_page = 10) {
        $query = "SELECT * FROM " . $this->table_name . " 
                 ORDER BY created_at DESC 
                 LIMIT ?, ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(2, $records_per_page, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                 SET name=:name, email=:email, phone=:phone, 
                 city=:city, service_type=:service_type, 
                 institutions=:institutions, status=:status
                 WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":city", $this->city);
        $stmt->bindParam(":service_type", $this->service_type);
        $stmt->bindParam(":institutions", $this->institutions);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obtener usuario por ID
    public function getUserById() {
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE id = ? 
                 LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $num = $stmt->rowCount();
        
        if($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->phone = $row['phone'];
            $this->city = $row['city'];
            $this->service_type = $row['service_type'];
            $this->institutions = $row['institutions'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            
            return true;
        }
        return false;
    }

    // Contar total de usuarios
    public function count() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }

    // Contar usuarios por estado
    public function countByStatus($status) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                 WHERE status = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $status);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }

    // Actualizar perfil (sin contraseña)
    public function updateProfile() {
        $query = "UPDATE " . $this->table_name . " 
                 SET name=:name, phone=:phone, city=:city 
                 WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":city", $this->city);
        $stmt->bindParam(":id", $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obtener información de paginación
    public function getPaging($page, $records_per_page, $total_rows, $page_url) {
        $paging_arr = array();
        
        $paging_arr["first"] = $page > 1 ? "{$page_url}page=1" : "";
        $paging_arr["previous"] = $page > 1 ? "{$page_url}page=" . ($page - 1) : "";
        $paging_arr["current"] = $page;
        $paging_arr["next"] = $page < ceil($total_rows / $records_per_page) ? "{$page_url}page=" . ($page + 1) : "";
        $paging_arr["last"] = $page < ceil($total_rows / $records_per_page) ? "{$page_url}page=" . ceil($total_rows / $records_per_page) : "";
        
        return $paging_arr;
    }

    // Buscar usuarios por nombre o email
    public function search($keywords, $from_record_num = 0, $records_per_page = 10) {
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE name LIKE ? OR email LIKE ? 
                 ORDER BY created_at DESC 
                 LIMIT ?, ?";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitizar palabras clave
        $keywords = "%{$keywords}%";
        
        $stmt->bindParam(1, $keywords);
        $stmt->bindParam(2, $keywords);
        $stmt->bindParam(3, $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(4, $records_per_page, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt;
    }

    // Cambiar estado de usuario
    public function changeStatus($status) {
        $query = "UPDATE " . $this->table_name . " 
                 SET status = :status 
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Verificar si el usuario existe por ID
    public function exists() {
        $query = "SELECT id FROM " . $this->table_name . " 
                 WHERE id = ? 
                 LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $num = $stmt->rowCount();
        
        return $num > 0;
    }

    // Obtener todos los usuarios sin paginación (para selects)
    public function readAllSimple() {
        $query = "SELECT id, name, email FROM " . $this->table_name . " 
                 ORDER BY name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
}
?>