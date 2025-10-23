<?php
/**
 * API REST para Uniformes Deportivos - CORREGIDA
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

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

function parsearRuta() {
    $uri = $_SERVER['REQUEST_URI'];
    $path = parse_url($uri, PHP_URL_PATH);
    $partes = explode('/', trim($path, '/'));
    
    $recurso = '';
    $id = null;
    
    foreach ($partes as $i => $parte) {
        if (strpos($parte, '.php') !== false) {
            $recurso = $partes[$i + 1] ?? '';
            $id = $partes[$i + 2] ?? null;
            break;
        }
    }
    
    return ['recurso' => $recurso, 'id' => $id];
}

// ==================== PRODUCTOS ====================

function obtenerProductos($conn, $filtros = []) {
    $query = "SELECT 
                p.idProducto,
                p.nombre,
                p.marca,
                p.descripcion,
                p.stock,
                p.precio_unitario,
                p.esActivo,
                p.fechaRegistro,
                p.idCategoria,
                c.descripcion as categoria
              FROM producto p
              LEFT JOIN categoria c ON p.idCategoria = c.idCategoria";
    
    $where = [];
    $params = [];
    
    if (!empty($filtros['id'])) {
        $where[] = "p.idProducto = :id";
        $params[':id'] = $filtros['id'];
    }
    
    if (!empty($filtros['buscar'])) {
        $where[] = "(p.nombre LIKE :buscar OR p.descripcion LIKE :buscar OR p.marca LIKE :buscar)";
        $params[':buscar'] = '%' . $filtros['buscar'] . '%';
    }
    
    if (!empty($filtros['categoria'])) {
        $where[] = "p.idCategoria = :categoria";
        $params[':categoria'] = $filtros['categoria'];
    }
    
    if (isset($filtros['estado'])) {
        $where[] = "p.esActivo = :estado";
        $params[':estado'] = $filtros['estado'];
    } elseif (!isset($filtros['todos'])) {
        $where[] = "p.esActivo = 1";
    }
    
    if (isset($filtros['stock_bajo'])) {
        $where[] = "p.stock <= 10 AND p.stock > 0";
    }
    
    if (!empty($where)) {
        $query .= " WHERE " . implode(' AND ', $where);
    }
    
    // CORREGIDO: Ordenar por ID ascendente
    $query .= " ORDER BY p.idProducto ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Procesar datos - CORREGIDO: Manejo correcto del campo BIT
    foreach ($productos as &$p) {
        // Convertir BIT a boolean correctamente
        $esActivoRaw = $p['esActivo'];
        if (is_string($esActivoRaw)) {
            $p['esActivo'] = ord($esActivoRaw) == 1;
        } else {
            $p['esActivo'] = (bool)$esActivoRaw;
        }
        
        $p['precio_unitario'] = floatval($p['precio_unitario']);
        $p['stock'] = intval($p['stock']);
        $p['idProducto'] = intval($p['idProducto']);
        $p['idCategoria'] = intval($p['idCategoria']);
    }
    
    return $productos;
}

function crearProducto($conn, $datos) {
    $query = "INSERT INTO producto 
              (nombre, marca, descripcion, idCategoria, stock, precio_unitario, esActivo) 
              VALUES 
              (:nombre, :marca, :descripcion, :idCategoria, :stock, :precio_unitario, :esActivo)";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':nombre' => $datos['nombre'],
        ':marca' => $datos['marca'] ?? '',
        ':descripcion' => $datos['descripcion'] ?? '',
        ':idCategoria' => $datos['idCategoria'],
        ':stock' => $datos['stock'] ?? 0,
        ':precio_unitario' => $datos['precio_unitario'],
        ':esActivo' => isset($datos['esActivo']) ? ($datos['esActivo'] ? 1 : 0) : 1
    ]);
    
    return $conn->lastInsertId();
}

function actualizarProducto($conn, $id, $datos) {
    $query = "UPDATE producto SET 
              nombre = :nombre,
              marca = :marca,
              descripcion = :descripcion,
              idCategoria = :idCategoria,
              stock = :stock,
              precio_unitario = :precio_unitario,
              esActivo = :esActivo
              WHERE idProducto = :id";
    
    $stmt = $conn->prepare($query);
    return $stmt->execute([
        ':id' => $id,
        ':nombre' => $datos['nombre'],
        ':marca' => $datos['marca'],
        ':descripcion' => $datos['descripcion'],
        ':idCategoria' => $datos['idCategoria'],
        ':stock' => $datos['stock'],
        ':precio_unitario' => $datos['precio_unitario'],
        ':esActivo' => $datos['esActivo'] ? 1 : 0
    ]);
}

function eliminarProducto($conn, $id) {
    $query = "UPDATE producto SET esActivo = 0 WHERE idProducto = :id";
    $stmt = $conn->prepare($query);
    return $stmt->execute([':id' => $id]);
}

// ==================== CATEGORÍAS ====================

function obtenerCategorias($conn, $filtros = []) {
    $query = "SELECT idCategoria, descripcion, esActivo, fechaRegistro 
              FROM categoria";
    
    $where = [];
    $params = [];
    
    if (!empty($filtros['id'])) {
        $where[] = "idCategoria = :id";
        $params[':id'] = $filtros['id'];
    }
    
    if (!isset($filtros['todos'])) {
        $where[] = "esActivo = 1";
    }
    
    if (!empty($where)) {
        $query .= " WHERE " . implode(' AND ', $where);
    }
    
    $query .= " ORDER BY descripcion ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($categorias as &$c) {
        $esActivoRaw = $c['esActivo'];
        if (is_string($esActivoRaw)) {
            $c['esActivo'] = ord($esActivoRaw) == 1;
        } else {
            $c['esActivo'] = (bool)$esActivoRaw;
        }
        $c['idCategoria'] = intval($c['idCategoria']);
    }
    
    return $categorias;
}

// ==================== USUARIOS ====================

function obtenerUsuarios($conn, $filtros = []) {
    $query = "SELECT 
                u.idUsuario,
                u.nombre,
                u.correo,
                u.idRol,
                u.esActivo,
                u.fechaRegistro,
                r.descripcion as rol
              FROM usuario u
              LEFT JOIN rol r ON u.idRol = r.idRol";
    
    $where = [];
    $params = [];
    
    if (!empty($filtros['id'])) {
        $where[] = "u.idUsuario = :id";
        $params[':id'] = $filtros['id'];
    }
    
    if (!isset($filtros['todos'])) {
        $where[] = "u.esActivo = 1";
    }
    
    if (!empty($where)) {
        $query .= " WHERE " . implode(' AND ', $where);
    }
    
    $query .= " ORDER BY u.nombre ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($usuarios as &$u) {
        $esActivoRaw = $u['esActivo'];
        if (is_string($esActivoRaw)) {
            $u['esActivo'] = ord($esActivoRaw) == 1;
        } else {
            $u['esActivo'] = (bool)$esActivoRaw;
        }
        $u['idUsuario'] = intval($u['idUsuario']);
        $u['idRol'] = intval($u['idRol']);
        unset($u['contrasenia']); // No enviar contraseña
    }
    
    return $usuarios;
}

// ==================== PUNTO DE ENTRADA ====================

try {
    $conn = obtenerConexion();
    $metodo = $_SERVER['REQUEST_METHOD'];
    $ruta = parsearRuta();
    $recurso = $ruta['recurso'];
    $id = $ruta['id'];
    
    if (empty($recurso)) {
        respuesta(true, 'API de Uniformes Deportivos funcionando', [
            'version' => '1.0',
            'recursos' => ['productos', 'categorias', 'usuarios'],
            'estado' => 'OK'
        ]);
    }
    
    // PRODUCTOS
    if ($recurso === 'productos') {
        if ($metodo === 'GET') {
            $filtros = $_GET;
            if ($id) $filtros['id'] = $id;
            
            $productos = obtenerProductos($conn, $filtros);
            
            if ($id && empty($productos)) {
                respuesta(false, 'Producto no encontrado', null, 404);
            }
            
            $datos = $id ? ($productos[0] ?? null) : $productos;
            respuesta(true, 'Productos obtenidos', $datos);
        }
        elseif ($metodo === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);
            
            if (empty($datos['nombre'])) respuesta(false, 'El nombre es requerido', null, 400);
            if (empty($datos['precio_unitario'])) respuesta(false, 'El precio es requerido', null, 400);
            if (empty($datos['idCategoria'])) respuesta(false, 'La categoría es requerida', null, 400);
            
            $idNuevo = crearProducto($conn, $datos);
            respuesta(true, 'Producto creado', ['id' => $idNuevo], 201);
        }
        elseif ($metodo === 'PUT') {
            if (!$id) respuesta(false, 'ID requerido', null, 400);
            
            $datos = json_decode(file_get_contents('php://input'), true);
            
            if (actualizarProducto($conn, $id, $datos)) {
                respuesta(true, 'Producto actualizado');
            } else {
                respuesta(false, 'Error al actualizar', null, 500);
            }
        }
        elseif ($metodo === 'DELETE') {
            if (!$id) respuesta(false, 'ID requerido', null, 400);
            
            if (eliminarProducto($conn, $id)) {
                respuesta(true, 'Producto eliminado');
            } else {
                respuesta(false, 'Error al eliminar', null, 500);
            }
        }
        else {
            respuesta(false, 'Método no permitido', null, 405);
        }
    }
    
    // CATEGORÍAS
    elseif ($recurso === 'categorias') {
        if ($metodo === 'GET') {
            $filtros = $_GET;
            if ($id) $filtros['id'] = $id;
            
            $categorias = obtenerCategorias($conn, $filtros);
            $datos = $id ? ($categorias[0] ?? null) : $categorias;
            respuesta(true, 'Categorías obtenidas', $datos);
        }
        else {
            respuesta(false, 'Método no implementado', null, 501);
        }
    }
    
    // USUARIOS
    elseif ($recurso === 'usuarios') {
        if ($metodo === 'GET') {
            $filtros = $_GET;
            if ($id) $filtros['id'] = $id;
            
            $usuarios = obtenerUsuarios($conn, $filtros);
            $datos = $id ? ($usuarios[0] ?? null) : $usuarios;
            respuesta(true, 'Usuarios obtenidos', $datos);
        }
        else {
            respuesta(false, 'Método no implementado', null, 501);
        }
    }
    
    else {
        respuesta(false, 'Recurso no encontrado', null, 404);
    }
    
} catch (Exception $e) {
    respuesta(false, 'Error: ' . $e->getMessage(), null, 500);
}