<?php

namespace App\Repositories;

use App\Entities\DetallePedidoEntity;
use App\Infraestructura\ConnectionManager;
use Exception;

class DetallePedidoRepository
{
    private ConnectionManager $connectionManager;
    private string $table = 'detalle_pedido';

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    /**
     * Crear un nuevo detalle de pedido
     */
    public function create(DetallePedidoEntity $detalle): ?DetallePedidoEntity
    {
        try {
            $sql = "INSERT INTO {$this->table} (pedido_id, producto_id, cantidad, precio_unitario, subtotal) 
                    VALUES (:pedido_id, :producto_id, :cantidad, :precio_unitario, :subtotal)";
            
            $params = [
                'pedido_id' => $detalle->getPedidoId(),
                'producto_id' => $detalle->getProductoId(),
                'cantidad' => $detalle->getCantidad(),
                'precio_unitario' => $detalle->getPrecioUnitario(),
                'subtotal' => $detalle->getSubtotal()
            ];

            $id = $this->connectionManager->insert($sql, $params);
            
            if ($id) {
                return $this->findById($id);
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error creating detalle pedido: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener detalle por ID
     */
    public function findById(int $id): ?DetallePedidoEntity
    {
        try {
            $sql = "SELECT dp.*, 
                           p.nombre as producto_nombre, p.codigo as producto_codigo,
                           pe.fecha_pedido, pe.estado as pedido_estado
                    FROM {$this->table} dp 
                    LEFT JOIN producto p ON dp.producto_id = p.id 
                    LEFT JOIN pedido pe ON dp.pedido_id = pe.id 
                    WHERE dp.id = :id";
            $result = $this->connectionManager->selectOne($sql, ['id' => $id]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding detalle pedido by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener detalles por pedido
     */
    public function findByPedido(int $pedidoId): array
    {
        try {
            $sql = "SELECT dp.*, 
                           p.nombre as producto_nombre, p.codigo as producto_codigo,
                           pe.fecha_pedido, pe.estado as pedido_estado
                    FROM {$this->table} dp 
                    LEFT JOIN producto p ON dp.producto_id = p.id 
                    LEFT JOIN pedido pe ON dp.pedido_id = pe.id 
                    WHERE dp.pedido_id = :pedido_id
                    ORDER BY dp.id";
            $results = $this->connectionManager->selectAll($sql, ['pedido_id' => $pedidoId]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding detalles by pedido: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener detalles por producto
     */
    public function findByProduct(int $productoId): array
    {
        try {
            $sql = "SELECT dp.*, 
                           p.nombre as producto_nombre, p.codigo as producto_codigo,
                           pe.fecha_pedido, pe.estado as pedido_estado
                    FROM {$this->table} dp 
                    LEFT JOIN producto p ON dp.producto_id = p.id 
                    LEFT JOIN pedido pe ON dp.pedido_id = pe.id 
                    WHERE dp.producto_id = :producto_id
                    ORDER BY pe.fecha_pedido DESC";
            $results = $this->connectionManager->selectAll($sql, ['producto_id' => $productoId]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding detalles by product: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener todos los detalles
     */
    public function findAll(): array
    {
        try {
            $sql = "SELECT dp.*, 
                           p.nombre as producto_nombre, p.codigo as producto_codigo,
                           pe.fecha_pedido, pe.estado as pedido_estado
                    FROM {$this->table} dp 
                    LEFT JOIN producto p ON dp.producto_id = p.id 
                    LEFT JOIN pedido pe ON dp.pedido_id = pe.id 
                    ORDER BY pe.fecha_pedido DESC, dp.id";
            $results = $this->connectionManager->selectAll($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding all detalles pedido: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar detalle de pedido
     */
    public function update(DetallePedidoEntity $detalle): bool
    {
        try {
            $sql = "UPDATE {$this->table} 
                    SET pedido_id = :pedido_id, producto_id = :producto_id, 
                        cantidad = :cantidad, precio_unitario = :precio_unitario, 
                        subtotal = :subtotal
                    WHERE id = :id";
            
            $params = [
                'id' => $detalle->getId(),
                'pedido_id' => $detalle->getPedidoId(),
                'producto_id' => $detalle->getProductoId(),
                'cantidad' => $detalle->getCantidad(),
                'precio_unitario' => $detalle->getPrecioUnitario(),
                'subtotal' => $detalle->getSubtotal()
            ];

            return $this->connectionManager->update($sql, $params);
        } catch (Exception $e) {
            error_log("Error updating detalle pedido: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar detalle de pedido
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = :id";
            return $this->connectionManager->delete($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log("Error deleting detalle pedido: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar todos los detalles de un pedido
     */
    public function deleteByPedido(int $pedidoId): bool
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE pedido_id = :pedido_id";
            return $this->connectionManager->delete($sql, ['pedido_id' => $pedidoId]);
        } catch (Exception $e) {
            error_log("Error deleting detalles by pedido: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcular total de un pedido
     */
    public function calculatePedidoTotal(int $pedidoId): float
    {
        try {
            $sql = "SELECT COALESCE(SUM(subtotal), 0) as total FROM {$this->table} 
                    WHERE pedido_id = :pedido_id";
            $result = $this->connectionManager->selectOne($sql, ['pedido_id' => $pedidoId]);
            
            return (float)($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log("Error calculating pedido total: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Obtener productos más pedidos
     */
    public function getMostOrderedProducts(int $limit = 10): array
    {
        try {
            $sql = "SELECT p.id, p.nombre, p.codigo, 
                           SUM(dp.cantidad) as total_pedido,
                           COUNT(DISTINCT dp.pedido_id) as numero_pedidos
                    FROM {$this->table} dp
                    INNER JOIN producto p ON dp.producto_id = p.id
                    GROUP BY p.id
                    ORDER BY total_pedido DESC
                    LIMIT :limit";
                    
            $results = $this->connectionManager->selectAll($sql, ['limit' => $limit]);
            
            return $results;
        } catch (Exception $e) {
            error_log("Error getting most ordered products: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener cantidad pedida de un producto
     */
    public function getProductQuantityOrdered(int $productoId): int
    {
        try {
            $sql = "SELECT COALESCE(SUM(cantidad), 0) as total FROM {$this->table} 
                    WHERE producto_id = :producto_id";
            $result = $this->connectionManager->selectOne($sql, ['producto_id' => $productoId]);
            
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log("Error getting product quantity ordered: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Verificar disponibilidad de stock para pedido
     */
    public function checkStockAvailability(int $pedidoId): array
    {
        try {
            $sql = "SELECT dp.producto_id, p.nombre as producto_nombre, 
                           dp.cantidad as cantidad_pedida, p.stock as stock_disponible,
                           (p.stock >= dp.cantidad) as disponible
                    FROM {$this->table} dp
                    INNER JOIN producto p ON dp.producto_id = p.id
                    WHERE dp.pedido_id = :pedido_id";
                    
            $results = $this->connectionManager->selectAll($sql, ['pedido_id' => $pedidoId]);
            
            return $results;
        } catch (Exception $e) {
            error_log("Error checking stock availability: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mapear datos de BD a entidad
     */
    private function mapToEntity(array $data): DetallePedidoEntity
    {
        $detalle = new DetallePedidoEntity();
        $detalle->setId($data['id']);
        $detalle->setPedidoId($data['pedido_id']);
        $detalle->setProductoId($data['producto_id']);
        $detalle->setCantidad($data['cantidad']);
        $detalle->setPrecioUnitario($data['precio_unitario']);
        $detalle->setSubtotal($data['subtotal']);
        
        return $detalle;
    }
}