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
            $sql = "INSERT INTO {$this->table} (cliente_id, fecha_pedido, fecha_entrega, 
                    estado, observaciones, total) 
                    VALUES (:cliente_id, :fecha_pedido, :fecha_entrega, :estado, :observaciones, :total)";
            
            $params = [
                'cliente_id' => $pedido->getClienteId(),
                'fecha_pedido' => $pedido->getFechaPedido(),
                'fecha_entrega' => $pedido->getFechaEntrega(),
                'estado' => $pedido->getEstado(),
                'observaciones' => $pedido->getObservaciones(),
                'total' => $pedido->getTotal()
            ];

            $id = $this->connectionManager->insert($sql, $params);
            
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
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido
                    FROM {$this->table} p 
                    LEFT JOIN cliente c ON p.cliente_id = c.id 
                    WHERE p.id = :id";
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
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido
                    FROM {$this->table} p 
                    LEFT JOIN cliente c ON p.cliente_id = c.id 
                    ORDER BY p.fecha_pedido DESC";
            $results = $this->connectionManager->selectAll($sql);
            
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
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido
                    FROM {$this->table} p 
                    LEFT JOIN cliente c ON p.cliente_id = c.id 
                    WHERE p.cliente_id = :cliente_id
                    ORDER BY p.fecha_pedido DESC";
            $results = $this->connectionManager->selectAll($sql, ['cliente_id' => $clienteId]);
            
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
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido
                    FROM {$this->table} p 
                    LEFT JOIN cliente c ON p.cliente_id = c.id 
                    WHERE p.estado = :estado
                    ORDER BY p.fecha_pedido DESC";
            $results = $this->connectionManager->selectAll($sql, ['estado' => $estado]);
            
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
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido
                    FROM {$this->table} p 
                    LEFT JOIN cliente c ON p.cliente_id = c.id 
                    WHERE DATE(p.fecha_pedido) = :fecha
                    ORDER BY p.fecha_pedido DESC";
            $results = $this->connectionManager->selectAll($sql, ['fecha' => $fecha]);
            
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
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido
                    FROM {$this->table} p 
                    LEFT JOIN cliente c ON p.cliente_id = c.id 
                    WHERE DATE(p.fecha_pedido) BETWEEN :fecha_inicio AND :fecha_fin
                    ORDER BY p.fecha_pedido DESC";
            
            $params = ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin];
            $results = $this->connectionManager->selectAll($sql, $params);
            
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
            return $this->findByStatus('PENDIENTE');
        } catch (Exception $e) {
            error_log("Error finding pending pedidos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener pedidos en proceso
     */
    public function findInProcess(): array
    {
        try {
            return $this->findByStatus('EN_PROCESO');
        } catch (Exception $e) {
            error_log("Error finding in process pedidos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener pedidos completados
     */
    public function findCompleted(): array
    {
        try {
            return $this->findByStatus('COMPLETADO');
        } catch (Exception $e) {
            error_log("Error finding completed pedidos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener pedidos cancelados
     */
    public function findCanceled(): array
    {
        try {
            return $this->findByStatus('CANCELADO');
        } catch (Exception $e) {
            error_log("Error finding canceled pedidos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar pedido
     */
    public function update(PedidoEntity $pedido): bool
    {
        try {
            $sql = "UPDATE {$this->table} 
                    SET cliente_id = :cliente_id, fecha_pedido = :fecha_pedido, 
                        fecha_entrega = :fecha_entrega, estado = :estado, 
                        observaciones = :observaciones, total = :total
                    WHERE id = :id";
            
            $params = [
                'id' => $pedido->getId(),
                'cliente_id' => $pedido->getClienteId(),
                'fecha_pedido' => $pedido->getFechaPedido(),
                'fecha_entrega' => $pedido->getFechaEntrega(),
                'estado' => $pedido->getEstado(),
                'observaciones' => $pedido->getObservaciones(),
                'total' => $pedido->getTotal()
            ];

            return $this->connectionManager->update($sql, $params);
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
            $sql = "UPDATE {$this->table} SET estado = :estado WHERE id = :id";
            return $this->connectionManager->update($sql, ['id' => $id, 'estado' => $nuevoEstado]);
        } catch (Exception $e) {
            error_log("Error updating pedido status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar pedido
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = :id";
            return $this->connectionManager->delete($sql, ['id' => $id]);
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
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido
                    FROM {$this->table} p 
                    LEFT JOIN cliente c ON p.cliente_id = c.id 
                    WHERE p.fecha_entrega BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL :days DAY)
                    AND p.estado IN ('PENDIENTE', 'EN_PROCESO')
                    ORDER BY p.fecha_entrega ASC";
            $results = $this->connectionManager->selectAll($sql, ['days' => $days]);
            
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
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido
                    FROM {$this->table} p 
                    LEFT JOIN cliente c ON p.cliente_id = c.id 
                    WHERE p.fecha_entrega < NOW()
                    AND p.estado IN ('PENDIENTE', 'EN_PROCESO')
                    ORDER BY p.fecha_entrega ASC";
            $results = $this->connectionManager->selectAll($sql);
            
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
            $sql = "SELECT estado, COUNT(*) as count 
                    FROM {$this->table} 
                    GROUP BY estado";
            $results = $this->connectionManager->selectAll($sql);
            
            $counts = [];
            foreach ($results as $result) {
                $counts[$result['estado']] = (int)$result['count'];
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
                        SUM(total) as total_valor,
                        AVG(total) as promedio_pedido,
                        COUNT(CASE WHEN estado = 'PENDIENTE' THEN 1 END) as pendientes,
                        COUNT(CASE WHEN estado = 'EN_PROCESO' THEN 1 END) as en_proceso,
                        COUNT(CASE WHEN estado = 'COMPLETADO' THEN 1 END) as completados,
                        COUNT(CASE WHEN estado = 'CANCELADO' THEN 1 END) as cancelados
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
     * Mapear datos de BD a entidad
     */
    private function mapToEntity(array $data): PedidoEntity
    {
        $pedido = new PedidoEntity();
        $pedido->setId($data['id']);
        $pedido->setClienteId($data['cliente_id']);
        $pedido->setFechaPedido($data['fecha_pedido']);
        $pedido->setFechaEntrega($data['fecha_entrega']);
        $pedido->setEstado($data['estado']);
        $pedido->setObservaciones($data['observaciones']);
        $pedido->setTotal($data['total']);
        
        return $pedido;
    }
}