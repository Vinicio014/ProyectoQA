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
            $data = [
                'id_pedido' => $pago->getNrPedido(),
                'monto_pagado' => $pago->getMontoPagado(),
                'fecha_pago' => $pago->getFechaPago()->format('Y-m-d H:i:s'),
                'metodo_pago' => $pago->getMetodoPago(),
                'descripcion' => $pago->getDescripcion(),
                'id_venta' => $pago->getIdVenta()
            ];

            $id = $this->connectionManager->insert($this->table, $data);
            
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
                           v.Total as venta_total, v.fechaRegistro as fecha_venta,
                           ped.estado_pedido,
                           c.primer_nombre, c.primer_apellido
                    FROM {$this->table} p 
                    LEFT JOIN venta v ON p.id_venta = v.idVenta 
                    LEFT JOIN pedido ped ON p.id_pedido = ped.idPedido
                    LEFT JOIN cliente c ON v.idCliente = c.idCliente
                    WHERE p.idPago = :id";
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
                           v.Total as venta_total, v.fechaRegistro as fecha_venta,
                           ped.estado_pedido,
                           c.primer_nombre, c.primer_apellido
                    FROM {$this->table} p 
                    LEFT JOIN venta v ON p.id_venta = v.idVenta 
                    LEFT JOIN pedido ped ON p.id_pedido = ped.idPedido
                    LEFT JOIN cliente c ON v.idCliente = c.idCliente
                    ORDER BY p.fecha_pago DESC";
            $results = $this->connectionManager->select($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding all pagos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener pagos por pedido
     */
    public function findByPedido(int $pedidoId): array
    {
        try {
            $sql = "SELECT p.*, 
                           v.Total as venta_total, v.fechaRegistro as fecha_venta,
                           ped.estado_pedido
                    FROM {$this->table} p 
                    LEFT JOIN venta v ON p.id_venta = v.idVenta 
                    LEFT JOIN pedido ped ON p.id_pedido = ped.idPedido
                    WHERE p.id_pedido = :pedido_id
                    ORDER BY p.fecha_pago DESC";
            $results = $this->connectionManager->select($sql, ['pedido_id' => $pedidoId]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding pagos by pedido: " . $e->getMessage());
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
                           v.Total as venta_total, v.fechaRegistro as fecha_venta,
                           ped.estado_pedido
                    FROM {$this->table} p 
                    LEFT JOIN venta v ON p.id_venta = v.idVenta 
                    LEFT JOIN pedido ped ON p.id_pedido = ped.idPedido
                    WHERE p.id_venta = :venta_id
                    ORDER BY p.fecha_pago DESC";
            $results = $this->connectionManager->select($sql, ['venta_id' => $ventaId]);
            
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
                           v.Total as venta_total, v.fechaRegistro as fecha_venta,
                           ped.estado_pedido
                    FROM {$this->table} p 
                    LEFT JOIN venta v ON p.id_venta = v.idVenta 
                    LEFT JOIN pedido ped ON p.id_pedido = ped.idPedido
                    WHERE p.metodo_pago = :metodo_pago
                    ORDER BY p.fecha_pago DESC";
            $results = $this->connectionManager->select($sql, ['metodo_pago' => $metodoPago]);
            
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
                           v.Total as venta_total, v.fechaRegistro as fecha_venta,
                           ped.estado_pedido
                    FROM {$this->table} p 
                    LEFT JOIN venta v ON p.id_venta = v.idVenta 
                    LEFT JOIN pedido ped ON p.id_pedido = ped.idPedido
                    WHERE DATE(p.fecha_pago) = :fecha
                    ORDER BY p.fecha_pago DESC";
            $results = $this->connectionManager->select($sql, ['fecha' => $fecha]);
            
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
                           v.Total as venta_total, v.fechaRegistro as fecha_venta,
                           ped.estado_pedido
                    FROM {$this->table} p 
                    LEFT JOIN venta v ON p.id_venta = v.idVenta 
                    LEFT JOIN pedido ped ON p.id_pedido = ped.idPedido
                    WHERE DATE(p.fecha_pago) BETWEEN :fecha_inicio AND :fecha_fin
                    ORDER BY p.fecha_pago DESC";
            
            $params = ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin];
            $results = $this->connectionManager->select($sql, $params);
            
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
            $data = [
                'id_pedido' => $pago->getNrPedido(),
                'monto_pagado' => $pago->getMontoPagado(),
                'fecha_pago' => $pago->getFechaPago()->format('Y-m-d H:i:s'),
                'metodo_pago' => $pago->getMetodoPago(),
                'descripcion' => $pago->getDescripcion(),
                'id_venta' => $pago->getIdVenta()
            ];

            $where = "idPago = :id";
            $whereParams = ['id' => $pago->getIdPago()];

            $rowCount = $this->connectionManager->update($this->table, $data, $where, $whereParams);
            return $rowCount > 0;
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
            $where = "idPago = :id";
            $params = ['id' => $id];

            $rowCount = $this->connectionManager->delete($this->table, $where, $params);
            return $rowCount > 0;
        } catch (Exception $e) {
            error_log("Error deleting pago: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcular total pagado para un pedido
     */
    public function getTotalPaidForPedido(int $pedidoId): float
    {
        try {
            $sql = "SELECT COALESCE(SUM(monto_pagado), 0) as total 
                    FROM {$this->table} 
                    WHERE id_pedido = :pedido_id";
            $result = $this->connectionManager->selectOne($sql, ['pedido_id' => $pedidoId]);
            
            return (float)($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log("Error getting total paid for pedido: " . $e->getMessage());
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
                           SUM(monto_pagado) as total_monto
                    FROM {$this->table}
                    GROUP BY metodo_pago
                    ORDER BY cantidad_transacciones DESC";
                    
            return $this->connectionManager->select($sql);
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
                           SUM(monto_pagado) as total_monto,
                           AVG(monto_pagado) as promedio_monto
                    FROM {$this->table}
                    WHERE DATE(fecha_pago) BETWEEN :fecha_inicio AND :fecha_fin
                    GROUP BY metodo_pago
                    ORDER BY total_monto DESC";
                    
            $params = ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin];
            return $this->connectionManager->select($sql, $params);
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
            $sql = "SELECT COALESCE(SUM(monto_pagado), 0) as total 
                    FROM {$this->table} 
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
            $sql = "SELECT COALESCE(SUM(monto_pagado), 0) as total 
                    FROM {$this->table} 
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
     * Verificar si un pedido está completamente pagado
     */
    public function isPedidoFullyPaid(int $pedidoId): bool
    {
        try {
            $sql = "SELECT ped.costo_total_pedido,
                           COALESCE(SUM(p.monto_pagado), 0) as total_pagado
                    FROM pedido ped
                    LEFT JOIN {$this->table} p ON ped.idPedido = p.id_pedido
                    WHERE ped.idPedido = :pedido_id
                    GROUP BY ped.idPedido, ped.costo_total_pedido";
                    
            $result = $this->connectionManager->selectOne($sql, ['pedido_id' => $pedidoId]);
            
            if ($result) {
                return (float)$result['total_pagado'] >= (float)$result['costo_total_pedido'];
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error checking if pedido is fully paid: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Contar pagos por pedido
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
            error_log("Error counting by pedido: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mapear datos de BD a entidad
     */
    private function mapToEntity(array $data): PagoEntity
    {
        $pago = new PagoEntity();
        
        if (isset($data['idPago'])) {
            $pago->setIdPago((int)$data['idPago']);
        }
        
        if (isset($data['id_pedido'])) {
            $pago->setNrPedido((int)$data['id_pedido']);
        }
        
        if (isset($data['monto_pagado'])) {
            $pago->setMontoPagado((float)$data['monto_pagado']);
        }
        
        if (isset($data['fecha_pago'])) {
            try {
                $pago->setFechaPago(new \DateTime($data['fecha_pago']));
            } catch (\Exception $e) {
                $pago->setFechaPago(new \DateTime());
            }
        }
        
        if (isset($data['metodo_pago'])) {
            $pago->setMetodoPago($data['metodo_pago']);
        }
        
        if (isset($data['descripcion'])) {
            $pago->setDescripcion($data['descripcion']);
        }
        
        if (isset($data['id_venta'])) {
            $pago->setIdVenta($data['id_venta'] ? (int)$data['id_venta'] : null);
        }
        
        return $pago;
    }
}