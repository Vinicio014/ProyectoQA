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
            $data = [
                'cantidad_producto' => $detalle->getCantidadProducto(),
                'diseno' => $detalle->getDiseno(),
                'id_pedido' => $detalle->getIdPedido(),
                'id_producto' => $detalle->getIdProducto()
            ];

            $id = $this->connectionManager->insert($this->table, $data);
            
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
                           p.nombre as producto_nombre,
                           pe.fecha_pedido, pe.estado_pedido
                    FROM {$this->table} dp 
                    LEFT JOIN producto p ON dp.id_producto = p.idProducto 
                    LEFT JOIN pedido pe ON dp.id_pedido = pe.idPedido 
                    WHERE dp.id_detalle_pedido = :id";
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
                           p.nombre as producto_nombre,
                           pe.fecha_pedido, pe.estado_pedido
                    FROM {$this->table} dp 
                    LEFT JOIN producto p ON dp.id_producto = p.idProducto 
                    LEFT JOIN pedido pe ON dp.id_pedido = pe.idPedido 
                    WHERE dp.id_pedido = :pedido_id
                    ORDER BY dp.id_detalle_pedido";
            $results = $this->connectionManager->select($sql, ['pedido_id' => $pedidoId]);
            
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
                           p.nombre as producto_nombre,
                           pe.fecha_pedido, pe.estado_pedido
                    FROM {$this->table} dp 
                    LEFT JOIN producto p ON dp.id_producto = p.idProducto 
                    LEFT JOIN pedido pe ON dp.id_pedido = pe.idPedido 
                    WHERE dp.id_producto = :producto_id
                    ORDER BY pe.fecha_pedido DESC";
            $results = $this->connectionManager->select($sql, ['producto_id' => $productoId]);
            
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
                           p.nombre as producto_nombre,
                           pe.fecha_pedido, pe.estado_pedido
                    FROM {$this->table} dp 
                    LEFT JOIN producto p ON dp.id_producto = p.idProducto 
                    LEFT JOIN pedido pe ON dp.id_pedido = pe.idPedido 
                    ORDER BY pe.fecha_pedido DESC, dp.id_detalle_pedido";
            $results = $this->connectionManager->select($sql);
            
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
            $data = [
                'cantidad_producto' => $detalle->getCantidadProducto(),
                'diseno' => $detalle->getDiseno(),
                'id_pedido' => $detalle->getIdPedido(),
                'id_producto' => $detalle->getIdProducto()
            ];

            $where = "id_detalle_pedido = :id";
            $whereParams = ['id' => $detalle->getIdDetallePedido()];

            $rowCount = $this->connectionManager->update($this->table, $data, $where, $whereParams);
            return $rowCount > 0;
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
            $where = "id_detalle_pedido = :id";
            $params = ['id' => $id];

            $rowCount = $this->connectionManager->delete($this->table, $where, $params);
            return $rowCount > 0;
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
            $where = "id_pedido = :pedido_id";
            $params = ['pedido_id' => $pedidoId];

            $rowCount = $this->connectionManager->delete($this->table, $where, $params);
            return $rowCount > 0;
        } catch (Exception $e) {
            error_log("Error deleting detalles by pedido: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcular total de productos en un pedido
     */
    public function calculatePedidoTotalProducts(int $pedidoId): int
    {
        try {
            $count = $this->connectionManager->count(
                $this->table,
                'id_pedido = :pedido_id',
                ['pedido_id' => $pedidoId]
            );
            
            return $count;
        } catch (Exception $e) {
            error_log("Error calculating pedido total products: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener productos más pedidos
     */
    public function getMostOrderedProducts(int $limit = 10): array
    {
        try {
            $sql = "SELECT p.idProducto, p.nombre, 
                           SUM(dp.cantidad_producto) as total_pedido,
                           COUNT(DISTINCT dp.id_pedido) as numero_pedidos
                    FROM {$this->table} dp
                    INNER JOIN producto p ON dp.id_producto = p.idProducto
                    GROUP BY p.idProducto, p.nombre
                    ORDER BY total_pedido DESC
                    LIMIT {$limit}";
                    
            return $this->connectionManager->select($sql);
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
            $sql = "SELECT COALESCE(SUM(cantidad_producto), 0) as total 
                    FROM {$this->table} 
                    WHERE id_producto = :producto_id";
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
            $sql = "SELECT dp.id_producto, p.nombre as producto_nombre, 
                           dp.cantidad_producto as cantidad_pedida, p.stock as stock_disponible,
                           (p.stock >= dp.cantidad_producto) as disponible
                    FROM {$this->table} dp
                    INNER JOIN producto p ON dp.id_producto = p.idProducto
                    WHERE dp.id_pedido = :pedido_id";
                    
            return $this->connectionManager->select($sql, ['pedido_id' => $pedidoId]);
        } catch (Exception $e) {
            error_log("Error checking stock availability: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener detalles con diseño personalizado
     */
    public function findWithCustomDesign(): array
    {
        try {
            $sql = "SELECT dp.*, p.nombre as producto_nombre
                    FROM {$this->table} dp
                    INNER JOIN producto p ON dp.id_producto = p.idProducto
                    WHERE dp.diseno IS NOT NULL AND dp.diseno != ''
                    ORDER BY dp.id_detalle_pedido DESC";
            
            $results = $this->connectionManager->select($sql);
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding details with custom design: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar detalles por pedido
     */
    public function countByPedido(int $pedidoId): int
    {
        try {
            return $this->connectionManager->count(
                $this->table,
                'id_pedido = :pedido_id',
                ['pedido_id' => $pedidoId]
            );
        } catch (Exception $e) {
            error_log("Error counting detalles by pedido: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mapear datos de BD a entidad
     */
    private function mapToEntity(array $data): DetallePedidoEntity
    {
        $detalle = new DetallePedidoEntity();
        
        if (isset($data['id_detalle_pedido'])) {
            $detalle->setIdDetallePedido((int)$data['id_detalle_pedido']);
        }
        
        if (isset($data['cantidad_producto'])) {
            $detalle->setCantidadProducto((int)$data['cantidad_producto']);
        }
        
        if (isset($data['diseno'])) {
            $detalle->setDiseno($data['diseno']);
        }
        
        if (isset($data['id_pedido'])) {
            $detalle->setIdPedido((int)$data['id_pedido']);
        }
        
        if (isset($data['id_producto'])) {
            $detalle->setIdProducto((int)$data['id_producto']);
        }
        
        return $detalle;
    }
}