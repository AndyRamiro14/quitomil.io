<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
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
include_once '../models/Progress.php';

$database = new Database();
$db = $database->getConnection();

// Verificar autenticación
function isLoggedIn()
{
    session_start();
    return isset($_SESSION['user_id']);
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(array("message" => "Debe iniciar sesión para acceder a este recurso."));
    exit();
}

$user_id = $_SESSION['user_id'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

// Obtener información del usuario
if ($uri[count($uri) - 2] == 'user' && $uri[count($uri) - 1] == 'profile') {
    if ($requestMethod == 'GET') {
        $user = new User($db);
        $user->id = $user_id;

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
    } elseif ($requestMethod == 'PUT') {
        // Actualizar perfil de usuario
        $data = json_decode(file_get_contents("php://input"));

        $user = new User($db);
        $user->id = $user_id;
        $user->name = $data->name;
        $user->phone = $data->phone;
        $user->city = $data->city;

        if ($user->updateProfile()) {
            http_response_code(200);
            echo json_encode(array("message" => "Perfil actualizado correctamente."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "No se pudo actualizar el perfil."));
        }
    }
}

// Obtener pagos del usuario
elseif ($uri[count($uri) - 2] == 'user' && $uri[count($uri) - 1] == 'payments') {
    if ($requestMethod == 'GET') {
        $payment = new Payment($db);
        $stmt = $payment->readByUserId($user_id);
        $num = $stmt->rowCount();

        if ($num > 0) {
            $payments_arr = array();
            $payments_arr["records"] = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $payment_item = array(
                    "id" => $id,
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

// Obtener progreso del usuario
elseif ($uri[count($uri) - 2] == 'user' && $uri[count($uri) - 1] == 'progress') {
    if ($requestMethod == 'GET') {
        $progress = new Progress($db);
        $stmt = $progress->readByUserId($user_id);
        $num = $stmt->rowCount();

        if ($num > 0) {
            $progress_arr = array();
            $progress_arr["records"] = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $progress_item = array(
                    "id" => $id,
                    "module" => $module,
                    "progress" => $progress,
                    "score" => $score,
                    "status" => $status,
                    "last_updated" => $last_updated
                );
                array_push($progress_arr["records"], $progress_item);
            }

            http_response_code(200);
            echo json_encode($progress_arr);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "No se encontró información de progreso."));
        }
    }
}

// Obtener estadísticas del dashboard del usuario
elseif ($uri[count($uri) - 2] == 'user' && $uri[count($uri) - 1] == 'stats') {
    if ($requestMethod == 'GET') {
        $progress = new Progress($db);
        $payment = new Payment($db);

        $stats = array(
            "overall_progress" => $progress->getOverallProgress($user_id),
            "completed_modules" => $progress->countCompletedModules($user_id),
            "total_modules" => $progress->countTotalModules($user_id),
            "achievements" => 8, // Valor hardcodeado por ahora
            "study_time" => "36h", // Valor hardcodeado por ahora
            "next_payment" => $payment->getNextPayment($user_id)
        );

        http_response_code(200);
        echo json_encode($stats);
    }
}
?>