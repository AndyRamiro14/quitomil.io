<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';
include_once '../models/User.php';
include_once '../models/Payment.php';
include_once '../models/Service.php';

$database = new Database();
$db = $database->getConnection();

// Verificar autenticación y permisos de administrador
function isAdmin() {
    session_start();
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(array("message" => "Acceso denegado. Se requieren permisos de administrador."));
    exit();
}

$requestMethod = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

// Endpoints para usuarios
if ($uri[count($uri) - 2] == 'admin' && $uri[count($uri) - 1] == 'users') {
    $user = new User($db);
    
    if ($requestMethod == 'GET') {
        // Obtener todos los usuarios o un usuario específico
        if (isset($_GET['id'])) {
            $user->id = $_GET['id'];
            if ($user->getUserById()) {
                $user_arr = array(
                    "id" => $user->id,
                    "name" => $user->name,
                    "email" => $user->email,
                    "phone" => $user->phone,
                    "city" => $user->city,
                    "service_type" => $user->service_type,
                    "institutions" => $user->institutions,
                    "status" => $user->status,
                    "created_at" => $user->created_at
                );
                http_response_code(200);
                echo json_encode($user_arr);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Usuario no encontrado."));
            }
        } else {
            // Listar todos los usuarios con paginación
            $page = isset($_GET['page']) ? $_GET['page'] : 1;
            $records_per_page = 10;
            $from_record_num = ($records_per_page * $page) - $records_per_page;
            
            $stmt = $user->readAll($from_record_num, $records_per_page);
            $num = $stmt->rowCount();
            
            if ($num > 0) {
                $users_arr = array();
                $users_arr["records"] = array();
                $users_arr["paging"] = array();
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    extract($row);
                    $user_item = array(
                        "id" => $id,
                        "name" => $name,
                        "email" => $email,
                        "phone" => $phone,
                        "city" => $city,
                        "service_type" => $service_type,
                        "status" => $status,
                        "created_at" => $created_at
                    );
                    array_push($users_arr["records"], $user_item);
                }
                
                // Paginación
                $total_rows = $user->count();
                $page_url = "http://localhost/quitomil/backend/api/admin/users?";
                $paging = $user->getPaging($page, $records_per_page, $total_rows, $page_url);
                $users_arr["paging"] = $paging;
                
                http_response_code(200);
                echo json_encode($users_arr);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "No se encontraron usuarios."));
            }
        }
    }
    
    elseif ($requestMethod == 'PUT') {
        // Actualizar usuario
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->id)) {
            $user->id = $data->id;
            $user->name = $data->name;
            $user->email = $data->email;
            $user->phone = $data->phone;
            $user->city = $data->city;
            $user->service_type = $data->service_type;
            $user->institutions = $data->institutions;
            $user->status = $data->status;
            
            if ($user->update()) {
                http_response_code(200);
                echo json_encode(array("message" => "Usuario actualizado correctamente."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo actualizar el usuario."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Datos incompletos. No se puede actualizar."));
        }
    }
    
    elseif ($requestMethod == 'DELETE') {
        // Eliminar usuario
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->id)) {
            $user->id = $data->id;
            
            if ($user->delete()) {
                http_response_code(200);
                echo json_encode(array("message" => "Usuario eliminado correctamente."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo eliminar el usuario."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "No se proporcionó ID de usuario."));
        }
    }
}

// Endpoints para pagos
elseif ($uri[count($uri) - 2] == 'admin' && $uri[count($uri) - 1] == 'payments') {
    $payment = new Payment($db);
    
    if ($requestMethod == 'GET') {
        // Obtener todos los pagos
        $stmt = $payment->readAll();
        $num = $stmt->rowCount();
        
        if ($num > 0) {
            $payments_arr = array();
            $payments_arr["records"] = array();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $payment_item = array(
                    "id" => $id,
                    "user_id" => $user_id,
                    "user_name" => $user_name,
                    "amount" => $amount,
                    "payment_method" => $payment_method,
                    "status" => $status,
                    "created_at" => $created_at
                );
                array_push($payments_arr["records"], $payment_item);
            }
            
            http_response_code(200);
            echo json_encode($payments_arr);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "No se encontraron pagos."));
        }
    }
}

// Endpoint para estadísticas del dashboard
elseif ($uri[count($uri) - 2] == 'admin' && $uri[count($uri) - 1] == 'stats') {
    if ($requestMethod == 'GET') {
        $user = new User($db);
        $payment = new Payment($db);
        
        $stats = array(
            "total_users" => $user->count(),
            "active_users" => $user->countByStatus('active'),
            "pending_users" => $user->countByStatus('pending'),
            "total_revenue" => $payment->getTotalRevenue(),
            "pending_payments" => $payment->countByStatus('pending')
        );
        
        http_response_code(200);
        echo json_encode($stats);
    }
}
?>