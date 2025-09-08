<?php

namespace App\Repositories;

use App\Entities\VentaEntity;
use App\Infraestructura\ConnectionManager;
use Exception;

class VentaRepository
{
    private ConnectionManager $connectionManager;
    private string $table = 'venta';

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    /**
     * Crear una nueva venta
     */
    public function create(VentaEntity $venta): ?VentaEntity
    {
        try {
            $sql = "INSERT INTO {$this->table} (cliente_id, usuario_id, fecha_venta, subtotal, 
                    impuesto, total) 
                    VALUES (:cliente_id, :usuario_id, :fecha_venta, :subtotal, :impuesto, :total)";
            
            $params = [
                'cliente_id' => $venta->getClienteId(),
                'usuario_id' => $venta->getUsuarioId(),
                'fecha_venta' => $venta->getFechaVenta(),
                'subtotal' => $venta->getSubtotal(),
                'impuesto' => $venta->getImpuesto(),
                'total' => $venta->getTotal()
            ];

            $id = $this->connectionManager->insert($sql, $params);
            
            if ($id) {
                return $this->findById($id);
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error creating venta: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener venta por ID
     */
    public function findById(int $id): ?VentaEntity
    {
        try {
            $sql = "SELECT v.*, 
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido,
                           u.nombre as usuario_nombre, u.apellido as usuario_apellido
                    FROM {$this->table} v 
                    LEFT JOIN cliente c ON v.cliente_id = c.id 
                    LEFT JOIN usuario u ON v.usuario_id = u.id 
                    WHERE v.id = :id";
            $result = $this->connectionManager->selectOne($sql, ['id' => $id]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding venta by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener todas las ventas
     */
    public function findAll(): array
    {
        try {
            $sql = "SELECT v.*, 
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido,
                           u.nombre as usuario_nombre, u.apellido as usuario_apellido
                    FROM {$this->table} v 
                    LEFT JOIN cliente c ON v.cliente_id = c.id 
                    LEFT JOIN usuario u ON v.usuario_id = u.id 
                    ORDER BY v.fecha_venta DESC";
            $results = $this->connectionManager->selectAll($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding all ventas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener ventas por cliente
     */
    public function findByClient(int $clienteId): array
    {
        try {
            $sql = "SELECT v.*, 
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido,
                           u.nombre as usuario_nombre, u.apellido as usuario_apellido
                    FROM {$this->table} v 
                    LEFT JOIN cliente c ON v.cliente_id = c.id 
                    LEFT JOIN usuario u ON v.usuario_id = u.id 
                    WHERE v.cliente_id = :cliente_id
                    ORDER BY v.fecha_venta DESC";
            $results = $this->connectionManager->selectAll($sql, ['cliente_id' => $clienteId]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding ventas by client: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener ventas por usuario (vendedor)
     */
    public function findByUser(int $usuarioId): array
    {
        try {
            $sql = "SELECT v.*, 
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido,
                           u.nombre as usuario_nombre, u.apellido as usuario_apellido
                    FROM {$this->table} v 
                    LEFT JOIN cliente c ON v.cliente_id = c.id 
                    LEFT JOIN usuario u ON v.usuario_id = u.id 
                    WHERE v.usuario_id = :usuario_id
                    ORDER BY v.fecha_venta DESC";
            $results = $this->connectionManager->selectAll($sql, ['usuario_id' => $usuarioId]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding ventas by user: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener ventas por fecha
     */
    public function findByDate(string $fecha): array
    {
        try {
            $sql = "SELECT v.*, 
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido,
                           u.nombre as usuario_nombre, u.apellido as usuario_apellido
                    FROM {$this->table} v 
                    LEFT JOIN cliente c ON v.cliente_id = c.id 
                    LEFT JOIN usuario u ON v.usuario_id = u.id 
                    WHERE DATE(v.fecha_venta) = :fecha
                    ORDER BY v.fecha_venta DESC";
            $results = $this->connectionManager->selectAll($sql, ['fecha' => $fecha]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding ventas by date: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener ventas por rango de fechas
     */
    public function findByDateRange(string $fechaInicio, string $fechaFin): array
    {
        try {
            $sql = "SELECT v.*, 
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido,
                           u.nombre as usuario_nombre, u.apellido as usuario_apellido
                    FROM {$this->table} v 
                    LEFT JOIN cliente c ON v.cliente_id = c.id 
                    LEFT JOIN usuario u ON v.usuario_id = u.id 
                    WHERE DATE(v.fecha_venta) BETWEEN :fecha_inicio AND :fecha_fin
                    ORDER BY v.fecha_venta DESC";
            
            $params = ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin];
            $results = $this->connectionManager->selectAll($sql, $params);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding ventas by date range: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar venta
     */
    public function update(VentaEntity $venta): bool
    {
        try {
            $sql = "UPDATE {$this->table} 
                    SET cliente_id = :cliente_id, usuario_id = :usuario_id, 
                        fecha_venta = :fecha_venta, subtotal = :subtotal, 
                        impuesto = :impuesto, total = :total
                    WHERE id = :id";
            
            $params = [
                'id' => $venta->getId(),
                'cliente_id' => $venta->getClienteId(),
                'usuario_id' => $venta->getUsuarioId(),
                'fecha_venta' => $venta->getFechaVenta(),
                'subtotal' => $venta->getSubtotal(),
                'impuesto' => $venta->getImpuesto(),
                'total' => $venta->getTotal()
            ];

            return $this->connectionManager->update($sql, $params);
        } catch (Exception $e) {
            error_log("Error updating venta: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar venta
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = :id";
            return $this->connectionManager->delete($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log("Error deleting venta: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener ventas del día
     */
    public function getTodaySales(): array
    {
        try {
            return $this->findByDate(date('Y-m-d'));
        } catch (Exception $e) {
            error_log("Error getting today sales: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener ventas del mes
     */
    public function getMonthSales(int $year, int $month): array
    {
        try {
            $fechaInicio = sprintf('%d-%02d-01', $year, $month);
            $fechaFin = date('Y-m-t', strtotime($fechaInicio));
            
            return $this->findByDateRange($fechaInicio, $fechaFin);
        } catch (Exception $e) {
            error_log("Error getting month sales: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calcular total de ventas por fecha
     */
    public function getTotalSalesByDate(string $fecha): float
    {
        try {
            $sql = "SELECT COALESCE(SUM(total), 0) as total FROM {$this->table} 
                    WHERE DATE(fecha_venta) = :fecha";
            $result = $this->connectionManager->selectOne($sql, ['fecha' => $fecha]);
            
            return (float)($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log("Error getting total sales by date: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Calcular total de ventas por rango de fechas
     */
    public function getTotalSalesByDateRange(string $fechaInicio, string $fechaFin): float
    {
        try {
            $sql = "SELECT COALESCE(SUM(total), 0) as total FROM {$this->table} 
                    WHERE DATE(fecha_venta) BETWEEN :fecha_inicio AND :fecha_fin";
            
            $params = ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin];
            $result = $this->connectionManager->selectOne($sql, $params);
            
            return (float)($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log("Error getting total sales by date range: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Obtener estadísticas de ventas
     */
    public function getSalesStats(string $fechaInicio, string $fechaFin): array
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_ventas,
                        SUM(total) as total_ingresos,
                        AVG(total) as promedio_venta,
                        MIN(total) as venta_minima,
                        MAX(total) as venta_maxima
                    FROM {$this->table} 
                    WHERE DATE(fecha_venta) BETWEEN :fecha_inicio AND :fecha_fin";
                    
            $params = ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin];
            $result = $this->connectionManager->selectOne($sql, $params);
            
            return $result ?? [];
        } catch (Exception $e) {
            error_log("Error getting sales stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener reporte de ventas por vendedor
     */
    public function getSalesReportByUser(string $fechaInicio, string $fechaFin): array
    {
        try {
            $sql = "SELECT 
                        u.id, u.nombre, u.apellido,
                        COUNT(v.id) as total_ventas,
                        SUM(v.total) as total_vendido
                    FROM usuario u
                    LEFT JOIN {$this->table} v ON u.id = v.usuario_id 
                        AND DATE(v.fecha_venta) BETWEEN :fecha_inicio AND :fecha_fin
                    GROUP BY u.id
                    ORDER BY total_vendido DESC";
                    
            $params = ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin];
            $results = $this->connectionManager->selectAll($sql, $params);
            
            return $results;
        } catch (Exception $e) {
            error_log("Error getting sales report by user: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mapear datos de BD a entidad
     */
    private function mapToEntity(array $data): VentaEntity
    {
        $venta = new VentaEntity();
        $venta->setId($data['id']);
        $venta->setClienteId($data['cliente_id']);
        $venta->setUsuarioId($data['usuario_id']);
        $venta->setFechaVenta($data['fecha_venta']);
        $venta->setSubtotal($data['subtotal']);
        $venta->setImpuesto($data['impuesto']);
        $venta->setTotal($data['total']);
        
        return $venta;
    }
}