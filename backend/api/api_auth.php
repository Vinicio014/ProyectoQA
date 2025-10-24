<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../conf/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$correo = trim($input['correo'] ?? '');
$contrasenia = $input['contrasenia'] ?? '';

if (empty($correo) || empty($contrasenia)) {
    echo json_encode(['exito' => false, 'mensaje' => 'Correo y contraseña requeridos']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Consulta simple sin CAST primero
    $query = "SELECT 
                u.idUsuario,
                u.nombre,
                u.correo,
                u.contrasenia,
                u.idRol,
                u.esActivo,
                r.descripcion as rol
              FROM usuario u
              LEFT JOIN rol r ON u.idRol = r.idRol
              WHERE u.correo = :correo";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':correo' => $correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        echo json_encode(['exito' => false, 'mensaje' => 'Credenciales incorrectas']);
        exit();
    }
    
    // Verificar contraseña
    $hashContrasenia = md5($contrasenia);
    if ($usuario['contrasenia'] !== $hashContrasenia) {
        echo json_encode(['exito' => false, 'mensaje' => 'Credenciales incorrectas']);
        exit();
    }
    
    // NO VERIFICAR esActivo por ahora - comentado para debug
    /*
    $esActivo = $usuario['esActivo'];
    if (is_string($esActivo)) {
        $esActivo = ord($esActivo) == 1;
    } else {
        $esActivo = (bool)$esActivo;
    }
    
    if (!$esActivo) {
        echo json_encode(['exito' => false, 'mensaje' => 'Usuario inactivo.']);
        exit();
    }
    */
    
    // Login exitoso
    $token = bin2hex(random_bytes(32));
    
    $datosUsuario = [
        'idUsuario' => (int)$usuario['idUsuario'],
        'nombre' => $usuario['nombre'],
        'correo' => $usuario['correo'],
        'rol' => $usuario['rol'],
        'idRol' => (int)$usuario['idRol'],
        'token' => $token
    ];
    
    echo json_encode([
        'exito' => true,
        'mensaje' => 'Login exitoso',
        'datos' => $datosUsuario
    ], JSON_NUMERIC_CHECK);
    
} catch (Exception $e) {
    echo json_encode([
        'exito' => false, 
        'mensaje' => 'Error: ' . $e->getMessage()
    ]);
}
?>