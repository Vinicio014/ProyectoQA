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
            $data = [
                'idCliente' => $venta->getIdCliente(),
                'idUsuario' => $venta->getIdUsuario(),
                'Total' => $venta->getTotal(),
                'impuestosTotal' => $venta->getImpuestosTotal()
                // fechaRegistro se genera automáticamente con DEFAULT CURRENT_TIMESTAMP
            ];

            $id = $this->connectionManager->insert($this->table, $data);
            
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
            $sql = "SELECT v.idVenta, v.fechaRegistro, v.idCliente, v.idUsuario, 
                           v.Total, v.impuestosTotal,
                           CONCAT(c.primer_nombre, ' ', c.primer_apellido) as cliente_nombre,
                           u.nombre as usuario_nombre
                    FROM {$this->table} v 
                    LEFT JOIN cliente c ON v.idCliente = c.idCliente 
                    LEFT JOIN usuario u ON v.idUsuario = u.idUsuario 
                    WHERE v.idVenta = :id";
            
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
            $sql = "SELECT v.idVenta, v.fechaRegistro, v.idCliente, v.idUsuario, 
                           v.Total, v.impuestosTotal,
                           CONCAT(c.primer_nombre, ' ', c.primer_apellido) as cliente_nombre,
                           u.nombre as usuario_nombre
                    FROM {$this->table} v 
                    LEFT JOIN cliente c ON v.idCliente = c.idCliente 
                    LEFT JOIN usuario u ON v.idUsuario = u.idUsuario 
                    ORDER BY v.fechaRegistro DESC";
            
            $results = $this->connectionManager->select($sql);
            
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
            $sql = "SELECT v.idVenta, v.fechaRegistro, v.idCliente, v.idUsuario, 
                           v.Total, v.impuestosTotal,
                           CONCAT(c.primer_nombre, ' ', c.primer_apellido) as cliente_nombre,
                           u.nombre as usuario_nombre
                    FROM {$this->table} v 
                    LEFT JOIN cliente c ON v.idCliente = c.idCliente 
                    LEFT JOIN usuario u ON v.idUsuario = u.idUsuario 
                    WHERE v.idCliente = :idCliente
                    ORDER BY v.fechaRegistro DESC";
            
            $results = $this->connectionManager->select($sql, ['idCliente' => $clienteId]);
            
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
            $sql = "SELECT v.idVenta, v.fechaRegistro, v.idCliente, v.idUsuario, 
                           v.Total, v.impuestosTotal,
                           CONCAT(c.primer_nombre, ' ', c.primer_apellido) as cliente_nombre,
                           u.nombre as usuario_nombre
                    FROM {$this->table} v 
                    LEFT JOIN cliente c ON v.idCliente = c.idCliente 
                    LEFT JOIN usuario u ON v.idUsuario = u.idUsuario 
                    WHERE v.idUsuario = :idUsuario
                    ORDER BY v.fechaRegistro DESC";
            
            $results = $this->connectionManager->select($sql, ['idUsuario' => $usuarioId]);
            
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
            $sql = "SELECT v.idVenta, v.fechaRegistro, v.idCliente, v.idUsuario, 
                           v.Total, v.impuestosTotal,
                           CONCAT(c.primer_nombre, ' ', c.primer_apellido) as cliente_nombre,
                           u.nombre as usuario_nombre
                    FROM {$this->table} v 
                    LEFT JOIN cliente c ON v.idCliente = c.idCliente 
                    LEFT JOIN usuario u ON v.idUsuario = u.idUsuario 
                    WHERE DATE(v.fechaRegistro) = :fecha
                    ORDER BY v.fechaRegistro DESC";
            
            $results = $this->connectionManager->select($sql, ['fecha' => $fecha]);
            
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
            $sql = "SELECT v.idVenta, v.fechaRegistro, v.idCliente, v.idUsuario, 
                           v.Total, v.impuestosTotal,
                           CONCAT(c.primer_nombre, ' ', c.primer_apellido) as cliente_nombre,
                           u.nombre as usuario_nombre
                    FROM {$this->table} v 
                    LEFT JOIN cliente c ON v.idCliente = c.idCliente 
                    LEFT JOIN usuario u ON v.idUsuario = u.idUsuario 
                    WHERE DATE(v.fechaRegistro) BETWEEN :fecha_inicio AND :fecha_fin
                    ORDER BY v.fechaRegistro DESC";
            
            $params = ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin];
            $results = $this->connectionManager->select($sql, $params);
            
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
            $data = [
                'idCliente' => $venta->getIdCliente(),
                'idUsuario' => $venta->getIdUsuario(),
                'Total' => $venta->getTotal(),
                'impuestosTotal' => $venta->getImpuestosTotal()
            ];

            $where = "idVenta = :id";
            $whereParams = ['id' => $venta->getIdVenta()];

            return $this->connectionManager->update($this->table, $data, $where, $whereParams);
        } catch (Exception $e) {
            error_log("Error updating venta: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar venta (hard delete)
     */
    public function delete(int $id): bool
    {
        try {
            $where = "idVenta = :id";
            $params = ['id' => $id];
            
            return $this->connectionManager->delete($this->table, $where, $params);
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
            $sql = "SELECT COALESCE(SUM(Total), 0) as total 
                    FROM {$this->table} 
                    WHERE DATE(fechaRegistro) = :fecha";
            
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
            $sql = "SELECT COALESCE(SUM(Total), 0) as total 
                    FROM {$this->table} 
                    WHERE DATE(fechaRegistro) BETWEEN :fecha_inicio AND :fecha_fin";
            
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
                        SUM(Total) as total_ingresos,
                        AVG(Total) as promedio_venta,
                        MIN(Total) as venta_minima,
                        MAX(Total) as venta_maxima,
                        SUM(impuestosTotal) as total_impuestos
                    FROM {$this->table} 
                    WHERE DATE(fechaRegistro) BETWEEN :fecha_inicio AND :fecha_fin";
                    
            $params = ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin];
            $result = $this->connectionManager->selectOne($sql, $params);
            
            return [
                'total_ventas' => (int)($result['total_ventas'] ?? 0),
                'total_ingresos' => (float)($result['total_ingresos'] ?? 0),
                'promedio_venta' => (float)($result['promedio_venta'] ?? 0),
                'venta_minima' => (float)($result['venta_minima'] ?? 0),
                'venta_maxima' => (float)($result['venta_maxima'] ?? 0),
                'total_impuestos' => (float)($result['total_impuestos'] ?? 0)
            ];
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
                        u.idUsuario, u.nombre, u.correo,
                        COUNT(v.idVenta) as total_ventas,
                        COALESCE(SUM(v.Total), 0) as total_vendido
                    FROM usuario u
                    LEFT JOIN {$this->table} v ON u.idUsuario = v.idUsuario 
                        AND DATE(v.fechaRegistro) BETWEEN :fecha_inicio AND :fecha_fin
                    GROUP BY u.idUsuario, u.nombre, u.correo
                    ORDER BY total_vendido DESC";
                    
            $params = ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin];
            $results = $this->connectionManager->select($sql, $params);
            
            return $results;
        } catch (Exception $e) {
            error_log("Error getting sales report by user: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar ventas por cliente
     */
    public function countByClient(int $clienteId): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE idCliente = :idCliente";
            $result = $this->connectionManager->selectOne($sql, ['idCliente' => $clienteId]);
            
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log("Error counting ventas by client: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener total de ventas de un cliente
     */
    public function getTotalByClient(int $clienteId): float
    {
        try {
            $sql = "SELECT COALESCE(SUM(Total), 0) as total 
                    FROM {$this->table} 
                    WHERE idCliente = :idCliente";
            
            $result = $this->connectionManager->selectOne($sql, ['idCliente' => $clienteId]);
            
            return (float)($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log("Error getting total by client: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Mapear datos de BD a entidad
     */
    private function mapToEntity(array $data): VentaEntity
    {
        $venta = new VentaEntity();
        $venta->setIdVenta((int)$data['idVenta']);
        $venta->setIdCliente((int)$data['idCliente']);
        $venta->setIdUsuario((int)$data['idUsuario']);
        $venta->setTotal((float)$data['Total']);
        $venta->setImpuestosTotal((float)$data['impuestosTotal']);
        
        // Manejo de DateTime
        if (isset($data['fechaRegistro'])) {
            try {
                $venta->setFechaRegistro(new \DateTime($data['fechaRegistro']));
            } catch (\Exception $e) {
                $venta->setFechaRegistro(new \DateTime());
            }
        }
        
        return $venta;
    }
}