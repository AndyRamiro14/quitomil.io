<?php
// controllers/AuthController.php
require_once '../config/database.php';

class AuthController {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function register($userData) {
        if (!$this->db) {
            return ['success' => false, 'message' => 'Error de conexión a BD'];
        }
        
        // Validar datos según tu estructura de tabla
        $required = ['name', 'email', 'password', 'phone', 'city', 'service_type'];
        foreach ($required as $field) {
            if (empty($userData[$field])) {
                return ['success' => false, 'message' => "El campo $field es obligatorio"];
            }
        }
        
        try {
            // Hash de contraseña
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // INSERT exacto según tu estructura
            $sql = "INSERT INTO users (name, email, password, phone, city, service_type, institutions, payment_method, status) 
                    VALUES (:name, :email, :password, :phone, :city, :service_type, :institutions, :payment_method, 'pending')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':name', $userData['name']);
            $stmt->bindParam(':email', $userData['email']);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':phone', $userData['phone']);
            $stmt->bindParam(':city', $userData['city']);
            $stmt->bindParam(':service_type', $userData['service_type']);
            $stmt->bindParam(':institutions', $userData['institutions'] ?? '');
            $stmt->bindParam(':payment_method', $userData['payment_method'] ?? 'efectivo');
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Usuario registrado exitosamente. Estado: pendiente'];
            } else {
                return ['success' => false, 'message' => 'Error al ejecutar la consulta'];
            }
            
        } catch (PDOException $e) {
            // Manejar error de duplicado de email
            if ($e->getCode() == 23000) {
                return ['success' => false, 'message' => 'El email ya está registrado'];
            }
            error_log("Error PDO: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()];
        }
    }
}