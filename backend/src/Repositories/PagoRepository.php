<?php

namespace App\Repositories;

use App\Entities\PagoEntity;
use App\Infraestructura\ConnectionManager;
use Exception;

class PagoRepository
{
    private ConnectionManager $connectionManager;
    private string $table = 'pago';

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    /**
     * Crear un nuevo pago
     */
    public function create(PagoEntity $pago): ?PagoEntity
    {
        try {
            $sql = "INSERT INTO {$this->table} (venta_id, metodo_pago, monto, fecha_pago, referencia) 
                    VALUES (:venta_id, :metodo_pago, :monto, :fecha_pago, :referencia)";
            
            $params = [
                'venta_id' => $pago->getVentaId(),
                'metodo_pago' => $pago->getMetodoPago(),
                'monto' => $pago->getMonto(),
                'fecha_pago' => $pago->getFechaPago(),
                'referencia' => $pago->getReferencia()
            ];

            $id = $this->connectionManager->insert($sql, $params);
            
            if ($id) {
                return $this->findById($id);
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error creating pago: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener pago por ID
     */
    public function findById(int $id): ?PagoEntity
    {
        try {
            $sql = "SELECT p.*, 
                           v.total as venta_total, v.fecha_venta,
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido
                    FROM {$this->table} p 
                    LEFT JOIN venta v ON p.venta_id = v.id 
                    LEFT JOIN cliente c ON v.cliente_id = c.id
                    WHERE p.id = :id";
            $result = $this->connectionManager->selectOne($sql, ['id' => $id]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding pago by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener todos los pagos
     */
    public function findAll(): array
    {
        try {
            $sql = "SELECT p.*, 
                           v.total as venta_total, v.fecha_venta,
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido
                    FROM {$this->table} p 
                    LEFT JOIN venta v ON p.venta_id = v.id 
                    LEFT JOIN cliente c ON v.cliente_id = c.id
                    ORDER BY p.fecha_pago DESC";
            $results = $this->connectionManager->selectAll($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding all pagos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener pagos por venta
     */
    public function findByVenta(int $ventaId): array
    {
        try {
            $sql = "SELECT p.*, 
                           v.total as venta_total, v.fecha_venta,
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido
                    FROM {$this->table} p 
                    LEFT JOIN venta v ON p.venta_id = v.id 
                    LEFT JOIN cliente c ON v.cliente_id = c.id
                    WHERE p.venta_id = :venta_id
                    ORDER BY p.fecha_pago DESC";
            $results = $this->connectionManager->selectAll($sql, ['venta_id' => $ventaId]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding pagos by venta: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener pagos por método de pago
     */
    public function findByPaymentMethod(string $metodoPago): array
    {
        try {
            $sql = "SELECT p.*, 
                           v.total as venta_total, v.fecha_venta,
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido
                    FROM {$this->table} p 
                    LEFT JOIN venta v ON p.venta_id = v.id 
                    LEFT JOIN cliente c ON v.cliente_id = c.id
                    WHERE p.metodo_pago = :metodo_pago
                    ORDER BY p.fecha_pago DESC";
            $results = $this->connectionManager->selectAll($sql, ['metodo_pago' => $metodoPago]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding pagos by payment method: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener pagos por fecha
     */
    public function findByDate(string $fecha): array
    {
        try {
            $sql = "SELECT p.*, 
                           v.total as venta_total, v.fecha_venta,
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido
                    FROM {$this->table} p 
                    LEFT JOIN venta v ON p.venta_id = v.id 
                    LEFT JOIN cliente c ON v.cliente_id = c.id
                    WHERE DATE(p.fecha_pago) = :fecha
                    ORDER BY p.fecha_pago DESC";
            $results = $this->connectionManager->selectAll($sql, ['fecha' => $fecha]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding pagos by date: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener pagos por rango de fechas
     */
    public function findByDateRange(string $fechaInicio, string $fechaFin): array
    {
        try {
            $sql = "SELECT p.*, 
                           v.total as venta_total, v.fecha_venta,
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido
                    FROM {$this->table} p 
                    LEFT JOIN venta v ON p.venta_id = v.id 
                    LEFT JOIN cliente c ON v.cliente_id = c.id
                    WHERE DATE(p.fecha_pago) BETWEEN :fecha_inicio AND :fecha_fin
                    ORDER BY p.fecha_pago DESC";
            
            $params = ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin];
            $results = $this->connectionManager->selectAll($sql, $params);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding pagos by date range: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar pago
     */
    public function update(PagoEntity $pago): bool
    {
        try {
            $sql = "UPDATE {$this->table} 
                    SET venta_id = :venta_id, metodo_pago = :metodo_pago, 
                        monto = :monto, fecha_pago = :fecha_pago, referencia = :referencia
                    WHERE id = :id";
            
            $params = [
                'id' => $pago->getId(),
                'venta_id' => $pago->getVentaId(),
                'metodo_pago' => $pago->getMetodoPago(),
                'monto' => $pago->getMonto(),
                'fecha_pago' => $pago->getFechaPago(),
                'referencia' => $pago->getReferencia()
            ];

            return $this->connectionManager->update($sql, $params);
        } catch (Exception $e) {
            error_log("Error updating pago: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar pago
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = :id";
            return $this->connectionManager->delete($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log("Error deleting pago: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcular total pagado para una venta
     */
    public function getTotalPaidForVenta(int $ventaId): float
    {
        try {
            $sql = "SELECT COALESCE(SUM(monto), 0) as total FROM {$this->table} 
                    WHERE venta_id = :venta_id";
            $result = $this->connectionManager->selectOne($sql, ['venta_id' => $ventaId]);
            
            return (float)($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log("Error getting total paid for venta: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Obtener métodos de pago más utilizados
     */
    public function getMostUsedPaymentMethods(): array
    {
        try {
            $sql = "SELECT metodo_pago, 
                           COUNT(*) as cantidad_transacciones,
                           SUM(monto) as total_monto
                    FROM {$this->table}
                    GROUP BY metodo_pago
                    ORDER BY cantidad_transacciones DESC";
                    
            $results = $this->connectionManager->selectAll($sql);
            
            return $results;
        } catch (Exception $e) {
            error_log("Error getting most used payment methods: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estadísticas de pagos por método
     */
    public function getPaymentMethodStats(string $fechaInicio, string $fechaFin): array
    {
        try {
            $sql = "SELECT metodo_pago,
                           COUNT(*) as cantidad_transacciones,
                           SUM(monto) as total_monto,
                           AVG(monto) as promedio_monto
                    FROM {$this->table}
                    WHERE DATE(fecha_pago) BETWEEN :fecha_inicio AND :fecha_fin
                    GROUP BY metodo_pago
                    ORDER BY total_monto DESC";
                    
            $params = ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin];
            $results = $this->connectionManager->selectAll($sql, $params);
            
            return $results;
        } catch (Exception $e) {
            error_log("Error getting payment method stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener total de ingresos por fecha
     */
    public function getTotalIncomeByDate(string $fecha): float
    {
        try {
            $sql = "SELECT COALESCE(SUM(monto), 0) as total FROM {$this->table} 
                    WHERE DATE(fecha_pago) = :fecha";
            $result = $this->connectionManager->selectOne($sql, ['fecha' => $fecha]);
            
            return (float)($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log("Error getting total income by date: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Obtener total de ingresos por rango de fechas
     */
    public function getTotalIncomeByDateRange(string $fechaInicio, string $fechaFin): float
    {
        try {
            $sql = "SELECT COALESCE(SUM(monto), 0) as total FROM {$this->table} 
                    WHERE DATE(fecha_pago) BETWEEN :fecha_inicio AND :fecha_fin";
            
            $params = ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin];
            $result = $this->connectionManager->selectOne($sql, $params);
            
            return (float)($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log("Error getting total income by date range: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Verificar si una venta está completamente pagada
     */
    public function isVentaFullyPaid(int $ventaId): bool
    {
        try {
            $sql = "SELECT v.total as venta_total,
                           COALESCE(SUM(p.monto), 0) as total_pagado
                    FROM venta v
                    LEFT JOIN {$this->table} p ON v.id = p.venta_id
                    WHERE v.id = :venta_id
                    GROUP BY v.id, v.total";
                    
            $result = $this->connectionManager->selectOne($sql, ['venta_id' => $ventaId]);
            
            if ($result) {
                return (float)$result['total_pagado'] >= (float)$result['venta_total'];
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error checking if venta is fully paid: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mapear datos de BD a entidad
     */
    private function mapToEntity(array $data): PagoEntity
    {
        $pago = new PagoEntity();
        $pago->setId($data['id']);
        $pago->setVentaId($data['venta_id']);
        $pago->setMetodoPago($data['metodo_pago']);
        $pago->setMonto($data['monto']);
        $pago->setFechaPago($data['fecha_pago']);
        $pago->setReferencia($data['referencia']);
        
        return $pago;
    }
}