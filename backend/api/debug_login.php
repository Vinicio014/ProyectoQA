<?php
/**
 * API de Autenticación para Uniformes Deportivos
 * Maneja login de usuarios y administradores
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir database
require_once __DIR__ . '/../conf/database.php';

function respuesta($exito, $mensaje, $datos = null, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode([
        'exito' => $exito,
        'mensaje' => $mensaje,
        'datos' => $datos
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

function obtenerConexion() {
    try {
        $db = new Database();
        return $db->getConnection();
    } catch (Exception $e) {
        respuesta(false, 'Error de conexión: ' . $e->getMessage(), null, 500);
    }
}

// ==================== LOGIN ====================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = json_decode(file_get_contents('php://input'), true);
    
    $correo = $datos['correo'] ?? '';
    $contrasenia = $datos['contrasenia'] ?? '';
    
    if (empty($correo) || empty($contrasenia)) {
        respuesta(false, 'Correo y contraseña son requeridos', null, 400);
    }
    
    try {
        $conn = obtenerConexion();
        
        // Buscar usuario
        $query = "SELECT 
                    u.idUsuario,
                    u.nombre,
                    u.correo,
                    u.contrasenia,
                    u.esActivo,
                    u.idRol,
                    r.descripcion as rol
                  FROM usuario u
                  INNER JOIN rol r ON u.idRol = r.idRol
                  WHERE u.correo = :correo";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([':correo' => $correo]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            respuesta(false, 'Credenciales incorrectas', null, 401);
        }
        
        // Verificar si está activo (campo BIT en MySQL)
        $esActivo = $usuario['esActivo'];
        // Convertir BIT a booleano
        if (is_string($esActivo)) {
            $esActivo = ord($esActivo);
        }
        if ($esActivo != 1 && $esActivo !== true) {
            respuesta(false, 'Usuario inactivo. Contacte al administrador', null, 403);
        }
        
        // Verificar contraseña (simple - en producción usar password_hash)
        if ($usuario['contrasenia'] !== $contrasenia) {
            respuesta(false, 'Credenciales incorrectas', null, 401);
        }
        
        // Login exitoso - generar token simple (en producción usar JWT)
        $token = base64_encode($usuario['idUsuario'] . ':' . time());
        
        // Preparar datos de respuesta
        $datosUsuario = [
            'idUsuario' => $usuario['idUsuario'],
            'nombre' => $usuario['nombre'],
            'correo' => $usuario['correo'],
            'rol' => $usuario['rol'],
            'idRol' => $usuario['idRol'],
            'token' => $token
        ];
        
        respuesta(true, 'Login exitoso', $datosUsuario);
        
    } catch (Exception $e) {
        respuesta(false, 'Error en el servidor: ' . $e->getMessage(), null, 500);
    }
}

// ==================== VERIFICAR SESIÓN ====================

elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = $_GET['token'] ?? '';
    
    if (empty($token)) {
        respuesta(false, 'Token requerido', null, 400);
    }
    
    try {
        // Decodificar token simple
        $decoded = base64_decode($token);
        $partes = explode(':', $decoded);
        $idUsuario = $partes[0] ?? null;
        
        if (!$idUsuario) {
            respuesta(false, 'Token inválido', null, 401);
        }
        
        $conn = obtenerConexion();
        
        $query = "SELECT 
                    u.idUsuario,
                    u.nombre,
                    u.correo,
                    u.esActivo,
                    u.idRol,
                    r.descripcion as rol
                  FROM usuario u
                  INNER JOIN rol r ON u.idRol = r.idRol
                  WHERE u.idUsuario = :id";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([':id' => $idUsuario]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            respuesta(false, 'Sesión inválida', null, 401);
        }
        
        // Verificar si está activo (campo BIT)
        $esActivo = $usuario['esActivo'];
        if (is_string($esActivo)) {
            $esActivo = ord($esActivo);
        }
        if ($esActivo != 1 && $esActivo !== true) {
            respuesta(false, 'Sesión inválida', null, 401);
        }
        
        $datosUsuario = [
            'idUsuario' => $usuario['idUsuario'],
            'nombre' => $usuario['nombre'],
            'correo' => $usuario['correo'],
            'rol' => $usuario['rol'],
            'idRol' => $usuario['idRol']
        ];
        
        respuesta(true, 'Sesión válida', $datosUsuario);
        
    } catch (Exception $e) {
        respuesta(false, 'Error en el servidor: ' . $e->getMessage(), null, 500);
    }
}

else {
    respuesta(false, 'Método no permitido', null, 405);
}
?>