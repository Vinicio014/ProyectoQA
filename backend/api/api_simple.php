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
    
    $query .= " ORDER BY p.idProducto ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($productos as &$p) {
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
    // Procesar esActivo correctamente
    $esActivo = 1; // Por defecto activo
    if (isset($datos['esActivo'])) {
        $valor = $datos['esActivo'];
        if (is_bool($valor)) {
            $esActivo = $valor ? 1 : 0;
        } elseif (is_numeric($valor)) {
            $esActivo = intval($valor) ? 1 : 0;
        } elseif (is_string($valor)) {
            $esActivo = ($valor === 'true' || $valor === '1') ? 1 : 0;
        }
    }
    
    $query = "INSERT INTO producto 
              (nombre, marca, descripcion, idCategoria, stock, precio_unitario, esActivo) 
              VALUES 
              (:nombre, :marca, :descripcion, :idCategoria, :stock, :precio_unitario, :esActivo)";
    
    $stmt = $conn->prepare($query);
    
    // Usar bindValue para asegurar el tipo correcto
    $stmt->bindValue(':nombre', $datos['nombre'], PDO::PARAM_STR);
    $stmt->bindValue(':marca', $datos['marca'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':descripcion', $datos['descripcion'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':idCategoria', (int)$datos['idCategoria'], PDO::PARAM_INT);
    $stmt->bindValue(':stock', (int)($datos['stock'] ?? 0), PDO::PARAM_INT);
    $stmt->bindValue(':precio_unitario', (float)$datos['precio_unitario'], PDO::PARAM_STR);
    $stmt->bindValue(':esActivo', $esActivo, PDO::PARAM_INT);
    
    $stmt->execute();
    
    return $conn->lastInsertId();
}

function actualizarProducto($conn, $id, $datos) {
    // Procesar esActivo correctamente
    $esActivo = 1;
    if (isset($datos['esActivo'])) {
        $valor = $datos['esActivo'];
        if (is_bool($valor)) {
            $esActivo = $valor ? 1 : 0;
        } elseif (is_numeric($valor)) {
            $esActivo = intval($valor) ? 1 : 0;
        } elseif (is_string($valor)) {
            $esActivo = ($valor === 'true' || $valor === '1') ? 1 : 0;
        }
    }
    
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
    
    $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
    $stmt->bindValue(':nombre', $datos['nombre'], PDO::PARAM_STR);
    $stmt->bindValue(':marca', $datos['marca'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':descripcion', $datos['descripcion'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':idCategoria', (int)$datos['idCategoria'], PDO::PARAM_INT);
    $stmt->bindValue(':stock', (int)$datos['stock'], PDO::PARAM_INT);
    $stmt->bindValue(':precio_unitario', (float)$datos['precio_unitario'], PDO::PARAM_STR);
    $stmt->bindValue(':esActivo', $esActivo, PDO::PARAM_INT);
    
    return $stmt->execute();
}

function eliminarProducto($conn, $id) {
    $query = "UPDATE producto SET esActivo = 0 WHERE idProducto = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
    return $stmt->execute();
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
    
    if (!empty($filtros['buscar'])) {
        $where[] = "u.nombre LIKE :buscar";
        $params[':buscar'] = '%' . $filtros['buscar'] . '%';
    }
    
    
    if (!empty($filtros['rol'])) {
        $where[] = "u.idRol = :rol";
        $params[':rol'] = $filtros['rol'];
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
        unset($u['contrasenia']);
    }
    
    return $usuarios;
}

function crearUsuario($conn, $datos) {
    // Validar que el correo no exista
    $queryCheck = "SELECT idUsuario FROM usuario WHERE correo = :correo";
    $stmtCheck = $conn->prepare($queryCheck);
    $stmtCheck->execute([':correo' => $datos['correo']]);
    
    if ($stmtCheck->fetch()) {
        return ['error' => 'El correo ya está registrado'];
    }
    
    // Hashear contraseña con MD5
    $contraseniaHash = md5($datos['contrasenia']);
    
    $query = "INSERT INTO usuario 
              (nombre, correo, contrasenia, idRol, esActivo) 
              VALUES 
              (:nombre, :correo, :contrasenia, :idRol, 1)";
    
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':nombre', $datos['nombre'], PDO::PARAM_STR);
    $stmt->bindValue(':correo', $datos['correo'], PDO::PARAM_STR);
    $stmt->bindValue(':contrasenia', $contraseniaHash, PDO::PARAM_STR);
    $stmt->bindValue(':idRol', (int)$datos['idRol'], PDO::PARAM_INT);
    
    $stmt->execute();
    
    return ['id' => $conn->lastInsertId()];
}

function actualizarUsuario($conn, $id, $datos) {
    // Al editar solo se puede cambiar nombre y rol
    $query = "UPDATE usuario SET 
              nombre = :nombre,
              idRol = :idRol
              WHERE idUsuario = :id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
    $stmt->bindValue(':nombre', $datos['nombre'], PDO::PARAM_STR);
    $stmt->bindValue(':idRol', (int)$datos['idRol'], PDO::PARAM_INT);
    
    return $stmt->execute();
}

function eliminarUsuario($conn, $id) {
    $query = "UPDATE usuario SET esActivo = 0 WHERE idUsuario = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
    return $stmt->execute();
}


// ==================== PEDIDOS ====================

function obtenerPedidos($conn, $filtros = []) {
    $query = "SELECT 
                p.idPedido,
                p.idUsuario,
                p.fechaPedido,
                p.fecha_entrega,
                p.costo_total_pedido,
                p.monto_pagado,
                p.estado_pago,
                p.estado_pedido,
                u.nombre as nombreCliente
              FROM pedido p
              LEFT JOIN usuario u ON p.idUsuario = u.idUsuario";
    
    $where = [];
    $params = [];
    
    if (!empty($filtros['id'])) {
        $where[] = "p.idPedido = :id";
        $params[':id'] = $filtros['id'];
    }
    
    if (!empty($filtros['buscar'])) {
        $where[] = "u.nombre LIKE :buscar";
        $params[':buscar'] = '%' . $filtros['buscar'] . '%';
    }
    
    if (!empty($filtros['estado'])) {
        $where[] = "p.estado_pedido = :estado";
        $params[':estado'] = $filtros['estado'];
    }
    
    if (!empty($filtros['estadoPago'])) {
        $where[] = "p.estado_pago = :estadoPago";
        $params[':estadoPago'] = $filtros['estadoPago'];
    }
    
    if (!empty($filtros['fecha'])) {
        $where[] = "DATE(p.fechaPedido) = :fecha";
        $params[':fecha'] = $filtros['fecha'];
    }
    if (!empty($filtros['urgentes'])) {
        $where[] = "(p.estado_pedido = 'Pendiente' OR p.estado_pago = 'Pendiente' OR p.estado_pago = 'Parcial')";
    }
    
    if (!empty($where)) {
        $query .= " WHERE " . implode(' AND ', $where);
    }
    
    if (!empty($filtros['urgentes'])) {
    $query .= " ORDER BY p.fechaPedido ASC"; // Más antiguos primero
    } else {
        $query .= " ORDER BY p.fechaPedido DESC"; // Más recientes primero
    }
    if (!empty($filtros['limit'])) {
    $query .= " LIMIT " . intval($filtros['limit']);
}
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si es detalle de un pedido, cargar productos y pagos
    if (!empty($filtros['id']) && count($pedidos) > 0) {
        $pedidos[0]['productos'] = obtenerDetalleProductosPedido($conn, $filtros['id']);
        $pedidos[0]['pagos'] = obtenerPagosPedido($conn, $filtros['id']);
    }
    
    foreach ($pedidos as &$p) {
        $p['idPedido'] = intval($p['idPedido']);
        $p['idUsuario'] = intval($p['idUsuario']);
        $p['costo_total_pedido'] = floatval($p['costo_total_pedido']);
        $p['monto_pagado'] = floatval($p['monto_pagado']);
    }
    
    return $pedidos;
}

function obtenerDetalleProductosPedido($conn, $idPedido) {
    $query = "SELECT 
                dp.id_detalle_pedido,
                dp.id_producto,
                dp.cantidad_producto,
                dp.diseño,
                pr.nombre as nombreProducto,
                pr.precio_unitario
              FROM detalle_pedido dp
              LEFT JOIN producto pr ON dp.id_producto = pr.idProducto
              WHERE dp.id_pedido = :idPedido";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':idPedido' => $idPedido]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Para cada producto, obtener detalle_uniforme si existe
    foreach ($productos as &$p) {
        $p['cantidad_producto'] = intval($p['cantidad_producto']);
        $p['precio_unitario'] = floatval($p['precio_unitario']);
        
        // Buscar detalle_uniforme
        $queryUniforme = "SELECT * FROM detalle_uniforme WHERE id_detalle_pedido = :id";
        $stmtUniforme = $conn->prepare($queryUniforme);
        $stmtUniforme->execute([':id' => $p['id_detalle_pedido']]);
        $uniforme = $stmtUniforme->fetch(PDO::FETCH_ASSOC);
        
        $p['detalleUniforme'] = $uniforme ?: null;
    }
    
    return $productos;
}

function obtenerPagosPedido($conn, $idPedido) {
    $query = "SELECT * FROM pago WHERE id_pedido = :id ORDER BY fecha_pago DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $idPedido]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function crearPedidoCompleto($conn, $datos) {
    try {
        $conn->beginTransaction();
        
        // 1. Calcular total
        $total = 0;
        foreach ($datos['productos'] as $prod) {
            $total += $prod['subtotal'];
        }
        
        // 2. Insertar pedido principal
        $query = "INSERT INTO pedido 
                  (idUsuario, costo_total_pedido, fechaPedido, fecha_entrega, 
                   monto_pagado, estado_pago, estado_pedido) 
                  VALUES 
                  (:idUsuario, :total, NOW(), :fechaEntrega, :anticipo, :estadoPago, 'Pendiente')";
        
        $estadoPago = ($datos['anticipo'] >= $total) ? 'Pagado' : 'Parcial';
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':idUsuario' => $datos['idUsuario'],
            ':total' => $total,
            ':fechaEntrega' => $datos['fechaEntrega'],
            ':anticipo' => $datos['anticipo'],
            ':estadoPago' => $estadoPago
        ]);
        
        $idPedido = $conn->lastInsertId();
        
        // 3. Insertar productos en detalle_pedido
        $queryDetalle = "INSERT INTO detalle_pedido 
                         (id_pedido, id_producto, cantidad_producto, diseño) 
                         VALUES 
                         (:idPedido, :idProducto, :cantidad, :diseno)";
        
        $stmtDetalle = $conn->prepare($queryDetalle);
        
        // 4. Query para detalle_uniforme
        $queryUniforme = "INSERT INTO detalle_uniforme 
                          (id_detalle_pedido, talla, genero, nombre_camisola, numero_camisola, 
                           nombre_abajo_numero, cantidad, con_medidas) 
                          VALUES 
                          (:idDetalle, :talla, :genero, :nombreCamisola, :numeroCamisola, 
                           :nombreAbajo, :cantidad, :conMedidas)";
        
        $stmtUniforme = $conn->prepare($queryUniforme);
        
        // 5. Reducir stock
        $queryStock = "UPDATE producto 
                       SET stock = stock - :cantidad 
                       WHERE idProducto = :idProducto 
                       AND stock >= :cantidad";
        
        $stmtStock = $conn->prepare($queryStock);
        
        foreach ($datos['productos'] as $prod) {
            // Verificar stock
            $queryCheck = "SELECT stock FROM producto WHERE idProducto = :id";
            $stmtCheck = $conn->prepare($queryCheck);
            $stmtCheck->execute([':id' => $prod['idProducto']]);
            $stockActual = $stmtCheck->fetchColumn();
            
            if ($stockActual < $prod['cantidad']) {
                throw new Exception("Stock insuficiente para el producto ID: " . $prod['idProducto']);
            }
            
            // Insertar en detalle_pedido
            $stmtDetalle->execute([
                ':idPedido' => $idPedido,
                ':idProducto' => $prod['idProducto'],
                ':cantidad' => $prod['cantidad'],
                ':diseno' => $prod['disenoGeneral'] ?? null
            ]);
            
            $idDetallePedido = $conn->lastInsertId();
            
            // Si tiene personalización de uniforme
            if (!empty($prod['detalleUniforme'])) {
                $du = $prod['detalleUniforme'];
                $stmtUniforme->execute([
                    ':idDetalle' => $idDetallePedido,
                    ':talla' => $du['talla'] ?? null,
                    ':genero' => $du['genero'] ?? 'M',
                    ':nombreCamisola' => $du['nombreCamisola'] ?? null,
                    ':numeroCamisola' => $du['numeroCamisola'] ?? null,
                    ':nombreAbajo' => $du['nombreAbajoNumero'] ?? null,
                    ':cantidad' => $prod['cantidad'],
                    ':conMedidas' => $du['conMedidas'] ?? 0
                ]);
            }
            
            // Reducir stock
            $stmtStock->execute([
                ':cantidad' => $prod['cantidad'],
                ':idProducto' => $prod['idProducto']
            ]);
            
            if ($stmtStock->rowCount() === 0) {
                throw new Exception("No se pudo reducir el stock del producto ID: " . $prod['idProducto']);
            }
        }
        
        // 6. Registrar anticipo en tabla pago
        $queryPago = "INSERT INTO pago 
                      (id_pedido, monto_pagado, metodo_pago, descripcion, fecha_pago) 
                      VALUES 
                      (:idPedido, :monto, :metodo, :descripcion, NOW())";
        
        $stmtPago = $conn->prepare($queryPago);
        $stmtPago->execute([
            ':idPedido' => $idPedido,
            ':monto' => $datos['anticipo'],
            ':metodo' => $datos['metodoPago'],
            ':descripcion' => $datos['descripcionPago']
        ]);
        
        $conn->commit();
        return ['id' => $idPedido];
        
    } catch (Exception $e) {
        $conn->rollBack();
        return ['error' => $e->getMessage()];
    }
}

function registrarPago($conn, $idPedido, $datos) {
    try {
        $conn->beginTransaction();
        
        // 1. Obtener info del pedido
        $query = "SELECT costo_total_pedido, monto_pagado FROM pedido WHERE idPedido = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([':id' => $idPedido]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pedido) {
            throw new Exception("Pedido no encontrado");
        }
        
        $total = floatval($pedido['costo_total_pedido']);
        $pagado = floatval($pedido['monto_pagado']);
        $saldo = $total - $pagado;
        
        if ($datos['monto'] > $saldo) {
            throw new Exception("El monto no puede ser mayor al saldo pendiente");
        }
        
        // 2. Insertar pago
        $queryPago = "INSERT INTO pago 
                      (id_pedido, monto_pagado, metodo_pago, descripcion, fecha_pago) 
                      VALUES 
                      (:idPedido, :monto, :metodo, :descripcion, NOW())";
        
        $stmtPago = $conn->prepare($queryPago);
        $stmtPago->execute([
            ':idPedido' => $idPedido,
            ':monto' => $datos['monto'],
            ':metodo' => $datos['metodo'],
            ':descripcion' => $datos['descripcion']
        ]);
        
        // 3. Actualizar pedido
        $nuevoPagado = $pagado + $datos['monto'];
        $nuevoEstadoPago = ($nuevoPagado >= $total) ? 'Pagado' : 'Parcial';
        
        $queryUpdate = "UPDATE pedido 
                        SET monto_pagado = :pagado, estado_pago = :estadoPago 
                        WHERE idPedido = :id";
        
        $stmtUpdate = $conn->prepare($queryUpdate);
        $stmtUpdate->execute([
            ':pagado' => $nuevoPagado,
            ':estadoPago' => $nuevoEstadoPago,
            ':id' => $idPedido
        ]);
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollBack();
        return ['error' => $e->getMessage()];
    }
}

function cambiarEstadoPedido($conn, $id, $nuevoEstado) {
    $query = "UPDATE pedido SET estado_pedido = :estado WHERE idPedido = :id";
    $stmt = $conn->prepare($query);
    return $stmt->execute([
        ':estado' => $nuevoEstado,
        ':id' => $id
    ]);
}

function cancelarPedido($conn, $id) {
    try {
        $conn->beginTransaction();
        
        // 1. Obtener productos del pedido
        $queryProductos = "SELECT id_producto, cantidad_producto 
                           FROM detalle_pedido 
                           WHERE id_pedido = :id";
        $stmt = $conn->prepare($queryProductos);
        $stmt->execute([':id' => $id]);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 2. Restaurar stock
        $queryStock = "UPDATE producto 
                       SET stock = stock + :cantidad 
                       WHERE idProducto = :idProducto";
        $stmtStock = $conn->prepare($queryStock);
        
        foreach ($productos as $prod) {
            $stmtStock->execute([
                ':cantidad' => $prod['cantidad_producto'],
                ':idProducto' => $prod['id_producto']
            ]);
        }
        
        // 3. Cambiar estado a Cancelado
        $queryCancelar = "UPDATE pedido SET estado_pedido = 'Cancelado' WHERE idPedido = :id";
        $stmtCancelar = $conn->prepare($queryCancelar);
        $stmtCancelar->execute([':id' => $id]);
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollBack();
        return false;
    }
}
// ==================== VENTAS ====================

function obtenerVentas($conn, $filtros = []) {
    $query = "SELECT 
                v.idVenta,
                v.fechaRegistro,
                v.Total,
                v.impuestoTotal,
                v.idUsuarioVendedor,
                v.idUsuarioCliente,
                uv.nombre as nombreVendedor,
                uc.nombre as nombreCliente
              FROM venta v
              LEFT JOIN usuario uv ON v.idUsuarioVendedor = uv.idUsuario
              LEFT JOIN usuario uc ON v.idUsuarioCliente = uc.idUsuario";
    
    $where = [];
    $params = [];
    
    if (!empty($filtros['id'])) {
        $where[] = "v.idVenta = :id";
        $params[':id'] = $filtros['id'];
    }
    
    if (!empty($filtros['vendedor'])) {
        $where[] = "v.idUsuarioVendedor = :vendedor";
        $params[':vendedor'] = $filtros['vendedor'];
    }
    
    if (!empty($filtros['cliente'])) {
        $where[] = "v.idUsuarioCliente = :cliente";
        $params[':cliente'] = $filtros['cliente'];
    }
    
    if (!empty($filtros['fecha'])) {
        $where[] = "DATE(v.fechaRegistro) = :fecha";
        $params[':fecha'] = $filtros['fecha'];
    }
    
    if (!empty($where)) {
        $query .= " WHERE " . implode(' AND ', $where);
    }
    
    $query .= " ORDER BY v.fechaRegistro DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($filtros['id']) && count($ventas) > 0) {
        $ventas[0]['productos'] = obtenerDetalleProductosVenta($conn, $filtros['id']);
    }
    
    foreach ($ventas as &$v) {
        $v['idVenta'] = intval($v['idVenta']);
        $v['Total'] = floatval($v['Total']);
        $v['impuestoTotal'] = floatval($v['impuestoTotal']);
    }
    
    return $ventas;
}

function obtenerDetalleProductosVenta($conn, $idVenta) {
    $query = "SELECT 
                dv.idDetalleVenta,
                dv.idProducto,
                dv.cantidad,
                dv.sub_total,
                p.nombre as nombreProducto,
                p.precio_unitario as precioUnitario
              FROM detalleventa dv
              LEFT JOIN producto p ON dv.idProducto = p.idProducto
              WHERE dv.idVenta = :idVenta";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':idVenta' => $idVenta]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($productos as &$p) {
        $p['cantidad'] = intval($p['cantidad']);
        $p['sub_total'] = floatval($p['sub_total']);
        $p['precioUnitario'] = floatval($p['precioUnitario']);
    }
    
    return $productos;
}

function crearVenta($conn, $datos) {
    try {
        $conn->beginTransaction();
        
        $query = "INSERT INTO venta 
                  (idUsuarioVendedor, idUsuarioCliente, Total, impuestoTotal, fechaRegistro) 
                  VALUES 
                  (:vendedor, :cliente, :total, :impuestos, NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':vendedor' => $datos['idUsuarioVendedor'],
            ':cliente' => $datos['idUsuarioCliente'],
            ':total' => $datos['Total'],
            ':impuestos' => $datos['impuestoTotal']
        ]);
        
        $idVenta = $conn->lastInsertId();
        
        $queryDetalle = "INSERT INTO detalleventa 
                         (idVenta, idProducto, cantidad, sub_total) 
                         VALUES 
                         (:idVenta, :idProducto, :cantidad, :subtotal)";
        
        $queryStock = "UPDATE producto 
                       SET stock = stock - :cantidad 
                       WHERE idProducto = :idProducto 
                       AND stock >= :cantidad";
        
        $stmtDetalle = $conn->prepare($queryDetalle);
        $stmtStock = $conn->prepare($queryStock);
        
        foreach ($datos['productos'] as $prod) {
            $queryCheck = "SELECT stock FROM producto WHERE idProducto = :id";
            $stmtCheck = $conn->prepare($queryCheck);
            $stmtCheck->execute([':id' => $prod['idProducto']]);
            $stockActual = $stmtCheck->fetchColumn();
            
            if ($stockActual < $prod['cantidad']) {
                throw new Exception("Stock insuficiente para el producto ID: " . $prod['idProducto']);
            }
            
            $stmtDetalle->execute([
                ':idVenta' => $idVenta,
                ':idProducto' => $prod['idProducto'],
                ':cantidad' => $prod['cantidad'],
                ':subtotal' => $prod['subtotal']
            ]);
            
            $stmtStock->execute([
                ':cantidad' => $prod['cantidad'],
                ':idProducto' => $prod['idProducto']
            ]);
            
            if ($stmtStock->rowCount() === 0) {
                throw new Exception("No se pudo reducir el stock del producto ID: " . $prod['idProducto']);
            }
        }
        
        $conn->commit();
        return ['id' => $idVenta];
        
    } catch (Exception $e) {
        $conn->rollBack();
        return ['error' => $e->getMessage()];
    }
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
        elseif ($metodo === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);
            
            if (empty($datos['nombre'])) respuesta(false, 'El nombre es requerido', null, 400);
            if (empty($datos['correo'])) respuesta(false, 'El correo es requerido', null, 400);
            if (empty($datos['contrasenia'])) respuesta(false, 'La contraseña es requerida', null, 400);
            if (empty($datos['idRol'])) respuesta(false, 'El rol es requerido', null, 400);
            
            $resultado = crearUsuario($conn, $datos);
            
            if (isset($resultado['error'])) {
                respuesta(false, $resultado['error'], null, 400);
            }
            
            respuesta(true, 'Usuario creado', ['id' => $resultado['id']], 201);
        }
        elseif ($metodo === 'PUT') {
            if (!$id) respuesta(false, 'ID requerido', null, 400);
            
            $datos = json_decode(file_get_contents('php://input'), true);
            
            if (actualizarUsuario($conn, $id, $datos)) {
                respuesta(true, 'Usuario actualizado');
            } else {
                respuesta(false, 'Error al actualizar', null, 500);
            }
        }
        elseif ($metodo === 'DELETE') {
            if (!$id) respuesta(false, 'ID requerido', null, 400);
            
            if (eliminarUsuario($conn, $id)) {
                respuesta(true, 'Usuario eliminado');
            } else {
                respuesta(false, 'Error al eliminar', null, 500);
            }
        }
        else {
            respuesta(false, 'Método no permitido', null, 405);
        }
    }
    
    // PEDIDOS
    elseif ($recurso === 'pedidos') {
        if ($metodo === 'GET') {
            $filtros = $_GET;
            if ($id) $filtros['id'] = $id;
            
            $pedidos = obtenerPedidos($conn, $filtros);
            
            if ($id && empty($pedidos)) {
                respuesta(false, 'Pedido no encontrado', null, 404);
            }
            
            $datos = $id ? ($pedidos[0] ?? null) : $pedidos;
            respuesta(true, 'Pedidos obtenidos', $datos);
        }
        elseif ($metodo === 'POST') {
            // Verificar si es registrar pago o crear pedido
            $uri = $_SERVER['REQUEST_URI'];
            
            if (strpos($uri, '/pago') !== false && $id) {
                // Registrar pago adicional
                $datos = json_decode(file_get_contents('php://input'), true);
                
                $resultado = registrarPago($conn, $id, $datos);
                
                if (isset($resultado['error'])) {
                    respuesta(false, $resultado['error'], null, 400);
                }
                
                respuesta(true, 'Pago registrado correctamente');
            } else {
                // Crear pedido nuevo
                $datos = json_decode(file_get_contents('php://input'), true);
                
                if (empty($datos['idUsuario'])) respuesta(false, 'El cliente es requerido', null, 400);
                if (empty($datos['productos'])) respuesta(false, 'Debe agregar productos', null, 400);
                if (empty($datos['fechaEntrega'])) respuesta(false, 'La fecha de entrega es requerida', null, 400);
                if (empty($datos['anticipo']) || $datos['anticipo'] <= 0) respuesta(false, 'El anticipo es requerido', null, 400);
                
                $resultado = crearPedidoCompleto($conn, $datos);
                
                if (isset($resultado['error'])) {
                    respuesta(false, $resultado['error'], null, 400);
                }
                
                respuesta(true, 'Pedido creado correctamente', ['id' => $resultado['id']], 201);
            }
        }
        elseif ($metodo === 'PUT') {
            if (!$id) respuesta(false, 'ID requerido', null, 400);
            
            $uri = $_SERVER['REQUEST_URI'];
            
            if (strpos($uri, '/estado') !== false) {
                $datos = json_decode(file_get_contents('php://input'), true);
                
                if (empty($datos['estado'])) respuesta(false, 'El estado es requerido', null, 400);
                
                if (cambiarEstadoPedido($conn, $id, $datos['estado'])) {
                    respuesta(true, 'Estado actualizado');
                } else {
                    respuesta(false, 'Error al actualizar estado', null, 500);
                }
            }
            elseif (strpos($uri, '/cancelar') !== false) {
                if (cancelarPedido($conn, $id)) {
                    respuesta(true, 'Pedido cancelado');
                } else {
                    respuesta(false, 'Error al cancelar pedido', null, 500);
                }
            }
            else {
                respuesta(false, 'Ruta no válida', null, 400);
            }
        }
        else {
            respuesta(false, 'Método no permitido', null, 405);
        }
    }

    // VENTAS
    elseif ($recurso === 'ventas') {
        if ($metodo === 'GET') {
            $filtros = $_GET;
            if ($id) $filtros['id'] = $id;
            
            $ventas = obtenerVentas($conn, $filtros);
            
            if ($id && empty($ventas)) {
                respuesta(false, 'Venta no encontrada', null, 404);
            }
            
            $datos = $id ? ($ventas[0] ?? null) : $ventas;
            respuesta(true, 'Ventas obtenidas', $datos);
        }
        elseif ($metodo === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);
            
            if (empty($datos['idUsuarioVendedor'])) respuesta(false, 'El vendedor es requerido', null, 400);
            if (empty($datos['idUsuarioCliente'])) respuesta(false, 'El cliente es requerido', null, 400);
            if (empty($datos['productos']) || count($datos['productos']) === 0) {
                respuesta(false, 'Debe agregar al menos un producto', null, 400);
            }
            
            $resultado = crearVenta($conn, $datos);
            
            if (isset($resultado['error'])) {
                respuesta(false, $resultado['error'], null, 400);
            }
            
            respuesta(true, 'Venta registrada correctamente', ['id' => $resultado['id']], 201);
        }
        else {
            respuesta(false, 'Método no permitido', null, 405);
        }
    }
    else {
        respuesta(false, 'Recurso no encontrado', null, 404);
    }
    
} catch (Exception $e) {
    respuesta(false, 'Error: ' . $e->getMessage(), null, 500);
    
}
//aqui