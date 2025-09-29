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
            $data = [
                'idVenta' => $detalle->getIdVenta(),
                'idProducto' => $detalle->getIdProducto(),
                'cantidad' => $detalle->getCantidad(),
                'sub_total' => $detalle->getSubTotal()
            ];

            $id = $this->connectionManager->insert($this->table, $data);
            
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
                           p.nombre as producto_nombre,
                           v.fechaRegistro as fecha_venta
                    FROM {$this->table} dv 
                    LEFT JOIN producto p ON dv.idProducto = p.idProducto 
                    LEFT JOIN venta v ON dv.idVenta = v.idVenta 
                    WHERE dv.idDetalleVenta = :id";
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
                           p.nombre as producto_nombre,
                           v.fechaRegistro as fecha_venta
                    FROM {$this->table} dv 
                    LEFT JOIN producto p ON dv.idProducto = p.idProducto 
                    LEFT JOIN venta v ON dv.idVenta = v.idVenta 
                    WHERE dv.idVenta = :venta_id
                    ORDER BY dv.idDetalleVenta";
            $results = $this->connectionManager->select($sql, ['venta_id' => $ventaId]);
            
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
                           p.nombre as producto_nombre,
                           v.fechaRegistro as fecha_venta
                    FROM {$this->table} dv 
                    LEFT JOIN producto p ON dv.idProducto = p.idProducto 
                    LEFT JOIN venta v ON dv.idVenta = v.idVenta 
                    WHERE dv.idProducto = :producto_id
                    ORDER BY v.fechaRegistro DESC";
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
            $sql = "SELECT dv.*, 
                           p.nombre as producto_nombre,
                           v.fechaRegistro as fecha_venta
                    FROM {$this->table} dv 
                    LEFT JOIN producto p ON dv.idProducto = p.idProducto 
                    LEFT JOIN venta v ON dv.idVenta = v.idVenta 
                    ORDER BY v.fechaRegistro DESC, dv.idDetalleVenta";
            $results = $this->connectionManager->select($sql);
            
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
            $data = [
                'idVenta' => $detalle->getIdVenta(),
                'idProducto' => $detalle->getIdProducto(),
                'cantidad' => $detalle->getCantidad(),
                'sub_total' => $detalle->getSubTotal()
            ];

            $where = "idDetalleVenta = :id";
            $whereParams = ['id' => $detalle->getIdDetalleVenta()];

            $rowCount = $this->connectionManager->update($this->table, $data, $where, $whereParams);
            return $rowCount > 0;
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
            $where = "idDetalleVenta = :id";
            $params = ['id' => $id];

            $rowCount = $this->connectionManager->delete($this->table, $where, $params);
            return $rowCount > 0;
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
            $where = "idVenta = :venta_id";
            $params = ['venta_id' => $ventaId];

            $rowCount = $this->connectionManager->delete($this->table, $where, $params);
            return $rowCount > 0;
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
            $sql = "SELECT COALESCE(SUM(sub_total), 0) as total 
                    FROM {$this->table} 
                    WHERE idVenta = :venta_id";
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
            $sql = "SELECT p.idProducto, p.nombre, 
                           SUM(dv.cantidad) as total_vendido,
                           COUNT(DISTINCT dv.idVenta) as numero_ventas,
                           SUM(dv.sub_total) as ingreso_total
                    FROM {$this->table} dv
                    INNER JOIN producto p ON dv.idProducto = p.idProducto
                    GROUP BY p.idProducto, p.nombre
                    ORDER BY total_vendido DESC
                    LIMIT {$limit}";
                    
            return $this->connectionManager->select($sql);
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
            $sql = "SELECT COALESCE(SUM(cantidad), 0) as total 
                    FROM {$this->table} 
                    WHERE idProducto = :producto_id";
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
                           p.nombre as producto_nombre,
                           v.fechaRegistro as fecha_venta
                    FROM {$this->table} dv 
                    LEFT JOIN producto p ON dv.idProducto = p.idProducto 
                    LEFT JOIN venta v ON dv.idVenta = v.idVenta 
                    WHERE DATE(v.fechaRegistro) BETWEEN :fecha_inicio AND :fecha_fin
                    ORDER BY v.fechaRegistro DESC";
                    
            $params = ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin];
            $results = $this->connectionManager->select($sql, $params);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding detalles by date range: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar detalles por venta
     */
    public function countByVenta(int $ventaId): int
    {
        try {
            return $this->connectionManager->count(
                $this->table,
                'idVenta = :venta_id',
                ['venta_id' => $ventaId]
            );
        } catch (Exception $e) {
            error_log("Error counting by venta: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mapear datos de BD a entidad
     */
    private function mapToEntity(array $data): DetalleVentaEntity
    {
        $detalle = new DetalleVentaEntity();
        
        if (isset($data['idDetalleVenta'])) {
            $detalle->setIdDetalleVenta((int)$data['idDetalleVenta']);
        }
        
        if (isset($data['idVenta'])) {
            $detalle->setIdVenta((int)$data['idVenta']);
        }
        
        if (isset($data['idProducto'])) {
            $detalle->setIdProducto((int)$data['idProducto']);
        }
        
        if (isset($data['cantidad'])) {
            $detalle->setCantidad((int)$data['cantidad']);
        }
        
        if (isset($data['sub_total'])) {
            $detalle->setSubTotal((float)$data['sub_total']);
        }
        
        return $detalle;
    }
}