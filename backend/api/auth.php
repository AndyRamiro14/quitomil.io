<?php
// ==============================================
// auth.php - API de autenticación y registro
// ==============================================

// Habilitar reporte de errores para desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Headers para CORS y JSON
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Método no permitido. Solo POST.'
    ]);
    exit;
}

// Obtener y decodificar JSON
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'JSON inválido: ' . json_last_error_msg()
    ]);
    exit;
}

// Validar campos obligatorios
$requiredFields = ['name', 'email', 'password', 'phone', 'city', 'service_type'];
foreach ($requiredFields as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Campo obligatorio faltante: ' . $field
        ]);
        exit;
    }
}

// Validar email
if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Email inválido'
    ]);
    exit;
}

// Validar contraseña
if (strlen($input['password']) < 6) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'La contraseña debe tener al menos 6 caracteres'
    ]);
    exit;
}

// Configuración de la base de datos
$hostname = 'sql304.infinityfree.com';
$username = 'if0_39966296';
$password = 'tu_password_mysql'; // Reemplaza con tu password real
$database = 'if0_39966296_quitomil'; // Reemplaza con tu nombre de BD

try {
    // Conectar a la base de datos
    $conn = new mysqli($hostname, $username, $password, $database);
    
    // Verificar conexión
    if ($conn->connect_error) {
        throw new Exception('Error de conexión: ' . $conn->connect_error);
    }
    
    // Verificar si el email ya existe
    $checkEmail = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $checkEmail->bind_param("s", $input['email']);
    $checkEmail->execute();
    $checkEmail->store_result();
    
    if ($checkEmail->num_rows > 0) {
        http_response_code(409);
        echo json_encode([
            'status' => 'error',
            'message' => 'El email ya está registrado'
        ]);
        exit;
    }
    $checkEmail->close();
    
    // Hash de la contraseña
    $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
    
    // Insertar nuevo usuario
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password, telefono, ciudad, servicio, instituciones, metodo_pago) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $institutions = isset($input['institutions']) ? $input['institutions'] : '';
    $paymentMethod = isset($input['payment_method']) ? $input['payment_method'] : 'efectivo';
    
    $stmt->bind_param(
        "ssssssss", 
        $input['name'],
        $input['email'],
        $hashedPassword,
        $input['phone'],
        $input['city'],
        $input['service_type'],
        $institutions,
        $paymentMethod
    );
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        
        // Respuesta exitosa
        echo json_encode([
            'status' => 'success',
            'message' => 'Usuario registrado exitosamente',
            'user_id' => $user_id,
            'user_data' => [
                'name' => $input['name'],
                'email' => $input['email'],
                'service' => $input['service_type']
            ]
        ]);
    } else {
        throw new Exception('Error al insertar usuario: ' . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
    exit;
}

?>