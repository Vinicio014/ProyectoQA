<?php
/**
 * Archivo para verificar la conexión a la base de datos
 * Ubicar en: PROYECTO/test_conexion.php
 */

// Incluir la clase Database
require_once __DIR__ . '/conf/database.php';

echo "<h2>🔍 PRUEBA DE CONEXIÓN A BASE DE DATOS</h2>";

try {
    // Crear instancia de Database
    $database = new Database();
    
    // Obtener conexión
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "<p style='color: green;'>✅ <strong>CONEXIÓN EXITOSA</strong></p>";
        
        // Información de la conexión
        $stmt = $conn->query("SELECT DATABASE() as db_name, VERSION() as version, NOW() as fecha_actual");
        $info = $stmt->fetch();
        
        echo "<h3>Información de la BD:</h3>";
        echo "<ul>";
        echo "<li><strong>Base de datos:</strong> " . $info['db_name'] . "</li>";
        echo "<li><strong>Versión MySQL:</strong> " . $info['version'] . "</li>";
        echo "<li><strong>Fecha/Hora actual:</strong> " . $info['fecha_actual'] . "</li>";
        echo "</ul>";
        
        // Verificar si existen las tablas principales
        echo "<h3>Verificación de tablas:</h3>";
        $tablas_necesarias = [
            'usuario', 'rol', 'cliente', 'categoria', 
            'producto', 'venta', 'pedido', 'pago',
            'detalle_pedido', 'detalle_uniforme', 'detalleventa'
        ];
        
        echo "<ul>";
        foreach ($tablas_necesarias as $tabla) {
            try {
                $stmt = $conn->query("SHOW TABLES LIKE '$tabla'");
                if ($stmt->rowCount() > 0) {
                    echo "<li style='color: green;'>✅ Tabla '$tabla' existe</li>";
                } else {
                    echo "<li style='color: red;'>❌ Tabla '$tabla' NO existe</li>";
                }
            } catch (Exception $e) {
                echo "<li style='color: red;'>❌ Error verificando tabla '$tabla': " . $e->getMessage() . "</li>";
            }
        }
        echo "</ul>";
        
    } else {
        echo "<p style='color: red;'>❌ <strong>ERROR: No se pudo conectar a la base de datos</strong></p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ <strong>ERROR DE CONEXIÓN:</strong> " . $e->getMessage() . "</p>";
    echo "<h3>Posibles soluciones:</h3>";
    echo "<ul>";
    echo "<li>Verificar que MySQL esté ejecutándose</li>";
    echo "<li>Revisar las credenciales en conf/database.php</li>";
    echo "<li>Confirmar que la base de datos 'Uniformes_deportivos' exista</li>";
    echo "<li>Verificar permisos del usuario de MySQL</li>";
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>ERROR GENERAL:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Ubicación del archivo:</strong> " . __FILE__ . "</p>";
echo "<p><strong>Fecha de prueba:</strong> " . date('d/m/Y H:i:s') . "</p>";
?>