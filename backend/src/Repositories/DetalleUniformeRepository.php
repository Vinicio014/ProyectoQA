<?php

namespace App\Repositories;

use App\Entities\DetalleUniformeEntity;
use App\Infraestructura\ConnectionManager;
use Exception;

class DetalleUniformeRepository
{
    private ConnectionManager $connectionManager;
    private string $table = 'detalle_uniforme';

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    /**
     * Crear un nuevo detalle de uniforme
     */
    public function create(DetalleUniformeEntity $detalle): ?DetalleUniformeEntity
    {
        try {
            $data = [
                'id_detalle_pedido' => $detalle->getIdDetallePedido(),
                'talla' => $detalle->getTalla(),
                'genero' => $detalle->getGenero(),
                'nombre_camisola' => $detalle->getNombreCamisola(),
                'numero_camisola' => $detalle->getNumeroCamisola(),
                'nombre_abajo_numero' => $detalle->getNombreAbajo(),
                'cantidad' => $detalle->getCantidad(),
                'con_medidas' => $detalle->getConMedidas()
            ];

            $id = $this->connectionManager->insert($this->table, $data);
            
            if ($id) {
                return $this->findById($id);
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error creating detalle uniforme: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener detalle por ID
     */
    public function findById(int $id): ?DetalleUniformeEntity
    {
        try {
            $sql = "SELECT du.*, 
                           dp.id_pedido, dp.cantidad_producto,
                           p.fecha_pedido, p.estado_pedido,
                           c.primer_nombre, c.primer_apellido
                    FROM {$this->table} du 
                    LEFT JOIN detalle_pedido dp ON du.id_detalle_pedido = dp.id_detalle_pedido
                    LEFT JOIN pedido p ON dp.id_pedido = p.idPedido 
                    LEFT JOIN cliente c ON p.idCliente = c.idCliente
                    WHERE du.idDetalle_uniforme = :id";
            $result = $this->connectionManager->selectOne($sql, ['id' => $id]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding detalle uniforme by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener detalles por detalle de pedido
     */
    public function findByDetallePedido(int $detallePedidoId): array
    {
        try {
            $sql = "SELECT du.*, 
                           dp.id_pedido, dp.cantidad_producto,
                           p.fecha_pedido, p.estado_pedido
                    FROM {$this->table} du 
                    LEFT JOIN detalle_pedido dp ON du.id_detalle_pedido = dp.id_detalle_pedido
                    LEFT JOIN pedido p ON dp.id_pedido = p.idPedido
                    WHERE du.id_detalle_pedido = :detalle_pedido_id
                    ORDER BY du.idDetalle_uniforme";
            $results = $this->connectionManager->select($sql, ['detalle_pedido_id' => $detallePedidoId]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding detalles by detalle pedido: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener detalles por talla
     */
    public function findByTalla(string $talla): array
    {
        try {
            $sql = "SELECT du.*, 
                           dp.id_pedido, dp.cantidad_producto,
                           p.fecha_pedido, p.estado_pedido
                    FROM {$this->table} du 
                    LEFT JOIN detalle_pedido dp ON du.id_detalle_pedido = dp.id_detalle_pedido
                    LEFT JOIN pedido p ON dp.id_pedido = p.idPedido
                    WHERE du.talla = :talla
                    ORDER BY p.fecha_pedido DESC";
            $results = $this->connectionManager->select($sql, ['talla' => $talla]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding detalles by talla: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener detalles por género
     */
    public function findByGender(string $genero): array
    {
        try {
            $sql = "SELECT du.*, 
                           dp.id_pedido, dp.cantidad_producto,
                           p.fecha_pedido, p.estado_pedido
                    FROM {$this->table} du 
                    LEFT JOIN detalle_pedido dp ON du.id_detalle_pedido = dp.id_detalle_pedido
                    LEFT JOIN pedido p ON dp.id_pedido = p.idPedido
                    WHERE du.genero = :genero
                    ORDER BY p.fecha_pedido DESC";
            $results = $this->connectionManager->select($sql, ['genero' => $genero]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding detalles by gender: " . $e->getMessage());
            return [];
        }
    }
/**
 * Obtener detalles de uniforme por talla
 */
public function findBySize(string $talla): array
{
    try {
        $sql = "SELECT idDetalle_uniforme, id_detalle_pedido, talla, genero, numero_camisola
                FROM {$this->table}
                WHERE talla = :talla
                ORDER BY id_detalle_pedido";
        
        $results = $this->connectionManager->select($sql, ['talla' => $talla]);
        
        return array_map([$this, 'mapToEntity'], $results);
    } catch (Exception $e) {
        error_log("Error finding detalles uniforme by size: " . $e->getMessage());
        return [];
    }
}
    /**
     * Obtener todos los detalles
     */
    public function findAll(): array
    {
        try {
            $sql = "SELECT du.*, 
                           dp.id_pedido, dp.cantidad_producto,
                           p.fecha_pedido, p.estado_pedido
                    FROM {$this->table} du 
                    LEFT JOIN detalle_pedido dp ON du.id_detalle_pedido = dp.id_detalle_pedido
                    LEFT JOIN pedido p ON dp.id_pedido = p.idPedido
                    ORDER BY p.fecha_pedido DESC, du.idDetalle_uniforme";
            $results = $this->connectionManager->select($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding all detalles uniforme: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar detalle de uniforme
     */
    public function update(DetalleUniformeEntity $detalle): bool
    {
        try {
            $data = [
                'id_detalle_pedido' => $detalle->getIdDetallePedido(),
                'talla' => $detalle->getTalla(),
                'genero' => $detalle->getGenero(),
                'nombre_camisola' => $detalle->getNombreCamisola(),
                'numero_camisola' => $detalle->getNumeroCamisola(),
                'nombre_abajo_numero' => $detalle->getNombreAbajo(),
                'cantidad' => $detalle->getCantidad(),
                'con_medidas' => $detalle->getConMedidas()
            ];

            $where = "idDetalle_uniforme = :id";
            $whereParams = ['id' => $detalle->getIdDetalleUniforme()];

            $rowCount = $this->connectionManager->update($this->table, $data, $where, $whereParams);
            return $rowCount > 0;
        } catch (Exception $e) {
            error_log("Error updating detalle uniforme: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar detalle de uniforme
     */
    public function delete(int $id): bool
    {
        try {
            $where = "idDetalle_uniforme = :id";
            $params = ['id' => $id];

            $rowCount = $this->connectionManager->delete($this->table, $where, $params);
            return $rowCount > 0;
        } catch (Exception $e) {
            error_log("Error deleting detalle uniforme: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar todos los detalles de un detalle de pedido
     */
    public function deleteByDetallePedido(int $detallePedidoId): bool
    {
        try {
            $where = "id_detalle_pedido = :detalle_pedido_id";
            $params = ['detalle_pedido_id' => $detallePedidoId];

            $rowCount = $this->connectionManager->delete($this->table, $where, $params);
            return $rowCount > 0;
        } catch (Exception $e) {
            error_log("Error deleting detalles by detalle pedido: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener tallas más solicitadas
     */
    public function getMostRequestedTallas(): array
    {
        try {
            $sql = "SELECT talla, 
                           COUNT(*) as cantidad_pedidos,
                           SUM(cantidad) as total_cantidad
                    FROM {$this->table}
                    WHERE talla IS NOT NULL
                    GROUP BY talla
                    ORDER BY cantidad_pedidos DESC";
                    
            return $this->connectionManager->select($sql);
        } catch (Exception $e) {
            error_log("Error getting most requested tallas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estadísticas por género
     */
    public function getGenderStats(): array
    {
        try {
            $sql = "SELECT genero, 
                           COUNT(*) as cantidad_pedidos,
                           SUM(cantidad) as total_cantidad,
                           AVG(con_medidas) as promedio_medidas
                    FROM {$this->table}
                    WHERE genero IS NOT NULL
                    GROUP BY genero
                    ORDER BY cantidad_pedidos DESC";
                    
            return $this->connectionManager->select($sql);
        } catch (Exception $e) {
            error_log("Error getting gender stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener números más populares para camisolas
     */
    public function getMostPopularNumbers(): array
    {
        try {
            $sql = "SELECT numero_camisola, 
                           COUNT(*) as cantidad_pedidos,
                           SUM(cantidad) as total_cantidad
                    FROM {$this->table}
                    WHERE numero_camisola IS NOT NULL AND numero_camisola != ''
                    GROUP BY numero_camisola
                    ORDER BY cantidad_pedidos DESC
                    LIMIT 20";
                    
            return $this->connectionManager->select($sql);
        } catch (Exception $e) {
            error_log("Error getting most popular numbers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener detalles que requieren medidas personalizadas
     */
    public function findWithCustomMeasurements(): array
    {
        try {
            $sql = "SELECT du.*, 
                           dp.id_pedido, dp.cantidad_producto,
                           p.fecha_pedido, p.estado_pedido
                    FROM {$this->table} du 
                    LEFT JOIN detalle_pedido dp ON du.id_detalle_pedido = dp.id_detalle_pedido
                    LEFT JOIN pedido p ON dp.id_pedido = p.idPedido
                    WHERE du.con_medidas > 0
                    ORDER BY p.fecha_pedido DESC";
            
            $results = $this->connectionManager->select($sql);
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding with custom measurements: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar detalles por detalle de pedido
     */
    public function countByDetallePedido(int $detallePedidoId): int
    {
        try {
            return $this->connectionManager->count(
                $this->table,
                'id_detalle_pedido = :detalle_pedido_id',
                ['detalle_pedido_id' => $detallePedidoId]
            );
        } catch (Exception $e) {
            error_log("Error counting by detalle pedido: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mapear datos de BD a entidad
     */
    private function mapToEntity(array $data): DetalleUniformeEntity
    {
        $detalle = new DetalleUniformeEntity();
        
        if (isset($data['idDetalle_uniforme'])) {
            $detalle->setIdDetalleUniforme((int)$data['idDetalle_uniforme']);
        }
        
        if (isset($data['id_detalle_pedido'])) {
            $detalle->setIdDetallePedido((int)$data['id_detalle_pedido']);
        }
        
        if (isset($data['talla'])) {
            $detalle->setTalla($data['talla']);
        }
        
        if (isset($data['genero'])) {
            $detalle->setGenero($data['genero']);
        }
        
        if (isset($data['nombre_camisola'])) {
            $detalle->setNombreCamisola($data['nombre_camisola']);
        }
        
        if (isset($data['numero_camisola'])) {
            $detalle->setNumeroCamisola($data['numero_camisola']);
        }
        
        if (isset($data['nombre_abajo_numero'])) {
            $detalle->setNombreAbajo($data['nombre_abajo_numero']);
        }
        
        if (isset($data['cantidad'])) {
            $detalle->setCantidad((int)$data['cantidad']);
        }
        
        if (isset($data['con_medidas'])) {
            $detalle->setConMedidas((float)$data['con_medidas']);
        }
        
        return $detalle;
    }
}