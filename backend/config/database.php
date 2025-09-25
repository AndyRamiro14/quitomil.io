<?php
// backend/config/database.php
declare(strict_types=1);

class Database
{
    private $host = "sql109.infinityfree.com";
    private $db_name = "if0_39967148_quitomil";
    private $username = "if0_39967148";
    private $password = "ZzCEuD0kaLS3YD";
    private $port = 3306; // Puerto específico de InfinityFree

    public $conn;

    public function getConnection()
    {
        $this->conn = null;

        try {
            // DSN con puerto específico y opciones mejoradas
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => true, // Conexiones persistentes
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
            // Verificar conexión ejecutando una consulta simple
            $this->conn->query("SELECT 1");
            
            error_log("✅ Conexión EXITOSA a {$this->db_name} en {$this->host}");

        } catch (PDOException $e) {
            error_log("❌ Error de conexión PDO: " . $e->getMessage());
            // Intentar conexión sin nombre de BD para verificar credenciales
            $this->testConnectionWithoutDB();
            return null;
        }

        return $this->conn;
    }

    // Método para probar conexión sin BD (diagnóstico)
    private function testConnectionWithoutDB()
    {
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};charset=utf8mb4";
            $tempConn = new PDO($dsn, $this->username, $this->password);
            error_log("✅ Credenciales OK - Problema puede ser la BD");
        } catch (PDOException $e) {
            error_log("❌ Error en credenciales/host: " . $e->getMessage());
        }
    }
}