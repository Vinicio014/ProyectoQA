<?php

namespace App\Repositories;

use App\Entities\DetalleVentaEntity;
use App\Infraestructura\ConnectionManager;
use Exception;

class DetalleVentaRepository
{
    private ConnectionManager $connectionManager;
    private string $table = 'detalleventa';

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    /**
     * Crear un nuevo detalle de venta
     */
    public function create(DetalleVentaEntity $detalle): ?DetalleVentaEntity
    {
        try {
            $sql = "INSERT INTO {$this->table} (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                    VALUES (:venta_id, :producto_id, :cantidad, :precio_unitario, :subtotal)";
            
            $params = [
                'venta_id' => $detalle->getVentaId(),
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
            error_log("Error creating detalle venta: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener detalle por ID
     */
    public function findById(int $id): ?DetalleVentaEntity
    {
        try {
            $sql = "SELECT dv.*, 
                           p.nombre as producto_nombre, p.codigo as producto_codigo,
                           v.fecha_venta
                    FROM {$this->table} dv 
                    LEFT JOIN producto p ON dv.producto_id = p.id 
                    LEFT JOIN venta v ON dv.venta_id = v.id 
                    WHERE dv.id = :id";
            $result = $this->connectionManager->selectOne($sql, ['id' => $id]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding detalle venta by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener detalles por venta
     */
    public function findByVenta(int $ventaId): array
    {
        try {
            $sql = "SELECT dv.*, 
                           p.nombre as producto_nombre, p.codigo as producto_codigo,
                           v.fecha_venta
                    FROM {$this->table} dv 
                    LEFT JOIN producto p ON dv.producto_id = p.id 
                    LEFT JOIN venta v ON dv.venta_id = v.id 
                    WHERE dv.venta_id = :venta_id
                    ORDER BY dv.id";
            $results = $this->connectionManager->selectAll($sql, ['venta_id' => $ventaId]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding detalles by venta: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener detalles por producto
     */
    public function findByProduct(int $productoId): array
    {
        try {
            $sql = "SELECT dv.*, 
                           p.nombre as producto_nombre, p.codigo as producto_codigo,
                           v.fecha_venta
                    FROM {$this->table} dv 
                    LEFT JOIN producto p ON dv.producto_id = p.id 
                    LEFT JOIN venta v ON dv.venta_id = v.id 
                    WHERE dv.producto_id = :producto_id
                    ORDER BY v.fecha_venta DESC";
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
            $sql = "SELECT dv.*, 
                           p.nombre as producto_nombre, p.codigo as producto_codigo,
                           v.fecha_venta
                    FROM {$this->table} dv 
                    LEFT JOIN producto p ON dv.producto_id = p.id 
                    LEFT JOIN venta v ON dv.venta_id = v.id 
                    ORDER BY v.fecha_venta DESC, dv.id";
            $results = $this->connectionManager->selectAll($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding all detalles venta: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar detalle de venta
     */
    public function update(DetalleVentaEntity $detalle): bool
    {
        try {
            $sql = "UPDATE {$this->table} 
                    SET venta_id = :venta_id, producto_id = :producto_id, 
                        cantidad = :cantidad, precio_unitario = :precio_unitario, 
                        subtotal = :subtotal
                    WHERE id = :id";
            
            $params = [
                'id' => $detalle->getId(),
                'venta_id' => $detalle->getVentaId(),
                'producto_id' => $detalle->getProductoId(),
                'cantidad' => $detalle->getCantidad(),
                'precio_unitario' => $detalle->getPrecioUnitario(),
                'subtotal' => $detalle->getSubtotal()
            ];

            return $this->connectionManager->update($sql, $params);
        } catch (Exception $e) {
            error_log("Error updating detalle venta: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar detalle de venta
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = :id";
            return $this->connectionManager->delete($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log("Error deleting detalle venta: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar todos los detalles de una venta
     */
    public function deleteByVenta(int $ventaId): bool
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE venta_id = :venta_id";
            return $this->connectionManager->delete($sql, ['venta_id' => $ventaId]);
        } catch (Exception $e) {
            error_log("Error deleting detalles by venta: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcular subtotal de una venta
     */
    public function calculateVentaSubtotal(int $ventaId): float
    {
        try {
            $sql = "SELECT COALESCE(SUM(subtotal), 0) as total FROM {$this->table} 
                    WHERE venta_id = :venta_id";
            $result = $this->connectionManager->selectOne($sql, ['venta_id' => $ventaId]);
            
            return (float)($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log("Error calculating venta subtotal: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Obtener productos más vendidos
     */
    public function getMostSoldProducts(int $limit = 10): array
    {
        try {
            $sql = "SELECT p.id, p.nombre, p.codigo, 
                           SUM(dv.cantidad) as total_vendido,
                           COUNT(DISTINCT dv.venta_id) as numero_ventas
                    FROM {$this->table} dv
                    INNER JOIN producto p ON dv.producto_id = p.id
                    GROUP BY p.id
                    ORDER BY total_vendido DESC
                    LIMIT :limit";
                    
            $results = $this->connectionManager->selectAll($sql, ['limit' => $limit]);
            
            return $results;
        } catch (Exception $e) {
            error_log("Error getting most sold products: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener cantidad vendida de un producto
     */
    public function getProductQuantitySold(int $productoId): int
    {
        try {
            $sql = "SELECT COALESCE(SUM(cantidad), 0) as total FROM {$this->table} 
                    WHERE producto_id = :producto_id";
            $result = $this->connectionManager->selectOne($sql, ['producto_id' => $productoId]);
            
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log("Error getting product quantity sold: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener detalles por rango de fechas
     */
    public function findByDateRange(string $fechaInicio, string $fechaFin): array
    {
        try {
            $sql = "SELECT dv.*, 
                           p.nombre as producto_nombre, p.codigo as producto_codigo,
                           v.fecha_venta
                    FROM {$this->table} dv 
                    LEFT JOIN producto p ON dv.producto_id = p.id 
                    LEFT JOIN venta v ON dv.venta_id = v.id 
                    WHERE DATE(v.fecha_venta) BETWEEN :fecha_inicio AND :fecha_fin
                    ORDER BY v.fecha_venta DESC";
                    
            $params = ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin];
            $results = $this->connectionManager->selectAll($sql, $params);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding detalles by date range: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mapear datos de BD a entidad
     */
    private function mapToEntity(array $data): DetalleVentaEntity
    {
        $detalle = new DetalleVentaEntity();
        $detalle->setId($data['id']);
        $detalle->setVentaId($data['venta_id']);
        $detalle->setProductoId($data['producto_id']);
        $detalle->setCantidad($data['cantidad']);
        $detalle->setPrecioUnitario($data['precio_unitario']);
        $detalle->setSubtotal($data['subtotal']);
        
        return $detalle;
    }
}