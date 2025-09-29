<?php

namespace App\Repositories;

use App\Entities\PedidoEntity;
use App\Infraestructura\ConnectionManager;
use Exception;

class PedidoRepository
{
    private ConnectionManager $connectionManager;
    private string $table = 'pedido';

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    /**
     * Crear un nuevo pedido
     */
    public function create(PedidoEntity $pedido): ?PedidoEntity
    {
        try {
            $data = [
                'estado_pedido' => $pedido->getEstadoPedido(),
                'costo_total_pedido' => 0.0, // Se calculará después
                'idCliente' => $pedido->getIdCliente(),
                'fecha_entrega' => $pedido->getFechaEntrega() ? $pedido->getFechaEntrega()->format('Y-m-d H:i:s') : null,
                'monto_pagado' => $pedido->getMontoPagado(),
                'estado_pago' => $pedido->getEstadoPago()
            ];

            $id = $this->connectionManager->insert($this->table, $data);
            
            if ($id) {
                return $this->findById($id);
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error creating pedido: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener pedido por ID
     */
    public function findById(int $id): ?PedidoEntity
    {
        try {
            $sql = "SELECT p.*, 
                           c.primer_nombre as cliente_primer_nombre, 
                           c.primer_apellido as cliente_primer_apellido
                    FROM {$this->table} p 
                    LEFT JOIN cliente c ON p.idCliente = c.idCliente 
                    WHERE p.idPedido = :id";
            $result = $this->connectionManager->selectOne($sql, ['id' => $id]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding pedido by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener todos los pedidos
     */
    public function findAll(): array
    {
        try {
            $sql = "SELECT p.*, 
                           c.primer_nombre as cliente_primer_nombre, 
                           c.primer_apellido as cliente_primer_apellido
                    FROM {$this->table} p 
                    LEFT JOIN cliente c ON p.idCliente = c.idCliente 
                    ORDER BY p.fecha_pedido DESC";
            $results = $this->connectionManager->select($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding all pedidos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener pedidos por cliente
     */
    public function findByClient(int $clienteId): array
    {
        try {
            $sql = "SELECT p.*, 
                           c.primer_nombre as cliente_primer_nombre, 
                           c.primer_apellido as cliente_primer_apellido
                    FROM {$this->table} p 
                    LEFT JOIN cliente c ON p.idCliente = c.idCliente 
                    WHERE p.idCliente = :cliente_id
                    ORDER BY p.fecha_pedido DESC";
            $results = $this->connectionManager->select($sql, ['cliente_id' => $clienteId]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding pedidos by client: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener pedidos por estado
     */
    public function findByStatus(string $estado): array
    {
        try {
            $sql = "SELECT p.*, 
                           c.primer_nombre as cliente_primer_nombre, 
                           c.primer_apellido as cliente_primer_apellido
                    FROM {$this->table} p 
                    LEFT JOIN cliente c ON p.idCliente = c.idCliente 
                    WHERE p.estado_pedido = :estado
                    ORDER BY p.fecha_pedido DESC";
            $results = $this->connectionManager->select($sql, ['estado' => $estado]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding pedidos by status: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener pedidos por fecha
     */
    public function findByDate(string $fecha): array
    {
        try {
            $sql = "SELECT p.*, 
                           c.primer_nombre as cliente_primer_nombre, 
                           c.primer_apellido as cliente_primer_apellido
                    FROM {$this->table} p 
                    LEFT JOIN cliente c ON p.idCliente = c.idCliente 
                    WHERE DATE(p.fecha_pedido) = :fecha
                    ORDER BY p.fecha_pedido DESC";
            $results = $this->connectionManager->select($sql, ['fecha' => $fecha]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding pedidos by date: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener pedidos por rango de fechas
     */
    public function findByDateRange(string $fechaInicio, string $fechaFin): array
    {
        try {
            $sql = "SELECT p.*, 
                           c.primer_nombre as cliente_primer_nombre, 
                           c.primer_apellido as cliente_primer_apellido
                    FROM {$this->table} p 
                    LEFT JOIN cliente c ON p.idCliente = c.idCliente 
                    WHERE DATE(p.fecha_pedido) BETWEEN :fecha_inicio AND :fecha_fin
                    ORDER BY p.fecha_pedido DESC";
            
            $params = ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin];
            $results = $this->connectionManager->select($sql, $params);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding pedidos by date range: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener pedidos pendientes
     */
    public function findPending(): array
    {
        try {
            return $this->findByStatus('Pendiente');
        } catch (Exception $e) {
            error_log("Error finding pending pedidos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar pedido
     */
    public function update(PedidoEntity $pedido): bool
    {
        try {
            $data = [
                'estado_pedido' => $pedido->getEstadoPedido(),
                'idCliente' => $pedido->getIdCliente(),
                'fecha_entrega' => $pedido->getFechaEntrega() ? $pedido->getFechaEntrega()->format('Y-m-d H:i:s') : null,
                'monto_pagado' => $pedido->getMontoPagado(),
                'estado_pago' => $pedido->getEstadoPago()
            ];

            $where = "idPedido = :id";
            $whereParams = ['id' => $pedido->getIdPedido()];

            $rowCount = $this->connectionManager->update($this->table, $data, $where, $whereParams);
            return $rowCount > 0;
        } catch (Exception $e) {
            error_log("Error updating pedido: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar estado del pedido
     */
    public function updateStatus(int $id, string $nuevoEstado): bool
    {
        try {
            $data = ['estado_pedido' => $nuevoEstado];
            $where = "idPedido = :id";
            $whereParams = ['id' => $id];

            $rowCount = $this->connectionManager->update($this->table, $data, $where, $whereParams);
            return $rowCount > 0;
        } catch (Exception $e) {
            error_log("Error updating pedido status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar costo total del pedido
     */
    public function updateCostoTotal(int $id, float $costoTotal): bool
    {
        try {
            $data = ['costo_total_pedido' => $costoTotal];
            $where = "idPedido = :id";
            $whereParams = ['id' => $id];

            $rowCount = $this->connectionManager->update($this->table, $data, $where, $whereParams);
            return $rowCount > 0;
        } catch (Exception $e) {
            error_log("Error updating pedido costo total: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar pedido
     */
    public function delete(int $id): bool
    {
        try {
            $where = "idPedido = :id";
            $params = ['id' => $id];

            $rowCount = $this->connectionManager->delete($this->table, $where, $params);
            return $rowCount > 0;
        } catch (Exception $e) {
            error_log("Error deleting pedido: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener pedidos próximos a entregar
     */
    public function findUpcomingDeliveries(int $days = 7): array
    {
        try {
            $sql = "SELECT p.*, 
                           c.primer_nombre as cliente_primer_nombre, 
                           c.primer_apellido as cliente_primer_apellido
                    FROM {$this->table} p 
                    LEFT JOIN cliente c ON p.idCliente = c.idCliente 
                    WHERE p.fecha_entrega BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL {$days} DAY)
                    AND p.estado_pedido IN ('Pendiente')
                    ORDER BY p.fecha_entrega ASC";
            $results = $this->connectionManager->select($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding upcoming deliveries: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener pedidos vencidos
     */
    public function findOverdue(): array
    {
        try {
            $sql = "SELECT p.*, 
                           c.primer_nombre as cliente_primer_nombre, 
                           c.primer_apellido as cliente_primer_apellido
                    FROM {$this->table} p 
                    LEFT JOIN cliente c ON p.idCliente = c.idCliente 
                    WHERE p.fecha_entrega < NOW()
                    AND p.estado_pedido = 'Pendiente'
                    ORDER BY p.fecha_entrega ASC";
            $results = $this->connectionManager->select($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding overdue pedidos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar pedidos por estado
     */
    public function countByStatus(): array
    {
        try {
            $sql = "SELECT estado_pedido, COUNT(*) as count 
                    FROM {$this->table} 
                    GROUP BY estado_pedido";
            $results = $this->connectionManager->select($sql);
            
            $counts = [];
            foreach ($results as $result) {
                $counts[$result['estado_pedido']] = (int)$result['count'];
            }
            
            return $counts;
        } catch (Exception $e) {
            error_log("Error counting pedidos by status: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estadísticas de pedidos
     */
    public function getPedidosStats(string $fechaInicio, string $fechaFin): array
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_pedidos,
                        SUM(costo_total_pedido) as total_valor,
                        AVG(costo_total_pedido) as promedio_pedido,
                        SUM(monto_pagado) as total_pagado,
                        COUNT(CASE WHEN estado_pedido = 'Pendiente' THEN 1 END) as pendientes,
                        COUNT(CASE WHEN estado_pago = 'Pendiente' THEN 1 END) as pago_pendiente,
                        COUNT(CASE WHEN estado_pago = 'Completo' THEN 1 END) as pago_completo
                    FROM {$this->table} 
                    WHERE DATE(fecha_pedido) BETWEEN :fecha_inicio AND :fecha_fin";
                    
            $params = ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin];
            $result = $this->connectionManager->selectOne($sql, $params);
            
            return $result ?? [];
        } catch (Exception $e) {
            error_log("Error getting pedidos stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar pedidos por cliente
     */
    public function countByCliente(int $clienteId): int
    {
        try {
            return $this->connectionManager->count(
                $this->table,
                'idCliente = :cliente_id',
                ['cliente_id' => $clienteId]
            );
        } catch (Exception $e) {
            error_log("Error counting by cliente: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mapear datos de BD a entidad
     */
    private function mapToEntity(array $data): PedidoEntity
    {
        $pedido = new PedidoEntity();
        
        if (isset($data['idPedido'])) {
            $pedido->setIdPedido((int)$data['idPedido']);
        }
        
        if (isset($data['fecha_pedido'])) {
            try {
                $pedido->setFechaPedido(new \DateTime($data['fecha_pedido']));
            } catch (\Exception $e) {
                $pedido->setFechaPedido(new \DateTime());
            }
        }
        
        if (isset($data['estado_pedido'])) {
            $pedido->setEstadoPedido($data['estado_pedido']);
        }
        
        if (isset($data['idCliente'])) {
            $pedido->setIdCliente((int)$data['idCliente']);
        }
        
        if (isset($data['fecha_entrega']) && $data['fecha_entrega']) {
            try {
                $pedido->setFechaEntrega(new \DateTime($data['fecha_entrega']));
            } catch (\Exception $e) {
                $pedido->setFechaEntrega(null);
            }
        }
        
        if (isset($data['monto_pagado'])) {
            $pedido->setMontoPagado((float)$data['monto_pagado']);
        }
        
        if (isset($data['estado_pago'])) {
            $pedido->setEstadoPago($data['estado_pago']);
        }
        
        return $pedido;
    }
}