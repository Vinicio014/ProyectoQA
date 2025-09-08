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
            $sql = "INSERT INTO {$this->table} (pedido_id, talla, genero, numero_camisola, 
                    medida_pecho, medida_cintura, medida_cadera, observaciones) 
                    VALUES (:pedido_id, :talla, :genero, :numero_camisola, 
                    :medida_pecho, :medida_cintura, :medida_cadera, :observaciones)";
            
            $params = [
                'pedido_id' => $detalle->getPedidoId(),
                'talla' => $detalle->getTalla(),
                'genero' => $detalle->getGenero(),
                'numero_camisola' => $detalle->getNumeroCamisola(),
                'medida_pecho' => $detalle->getMedidaPecho(),
                'medida_cintura' => $detalle->getMedidaCintura(),
                'medida_cadera' => $detalle->getMedidaCadera(),
                'observaciones' => $detalle->getObservaciones()
            ];

            $id = $this->connectionManager->insert($sql, $params);
            
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
                           p.fecha_pedido, p.estado as pedido_estado,
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido
                    FROM {$this->table} du 
                    LEFT JOIN pedido p ON du.pedido_id = p.id 
                    LEFT JOIN cliente c ON p.cliente_id = c.id
                    WHERE du.id = :id";
            $result = $this->connectionManager->selectOne($sql, ['id' => $id]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding detalle uniforme by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener detalles por pedido
     */
    public function findByPedido(int $pedidoId): array
    {
        try {
            $sql = "SELECT du.*, 
                           p.fecha_pedido, p.estado as pedido_estado,
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido
                    FROM {$this->table} du 
                    LEFT JOIN pedido p ON du.pedido_id = p.id 
                    LEFT JOIN cliente c ON p.cliente_id = c.id
                    WHERE du.pedido_id = :pedido_id
                    ORDER BY du.id";
            $results = $this->connectionManager->selectAll($sql, ['pedido_id' => $pedidoId]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding detalles by pedido: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener detalles por talla
     */
    public function findBySize(string $talla): array
    {
        try {
            $sql = "SELECT du.*, 
                           p.fecha_pedido, p.estado as pedido_estado,
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido
                    FROM {$this->table} du 
                    LEFT JOIN pedido p ON du.pedido_id = p.id 
                    LEFT JOIN cliente c ON p.cliente_id = c.id
                    WHERE du.talla = :talla
                    ORDER BY p.fecha_pedido DESC";
            $results = $this->connectionManager->selectAll($sql, ['talla' => $talla]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding detalles by size: " . $e->getMessage());
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
                           p.fecha_pedido, p.estado as pedido_estado,
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido
                    FROM {$this->table} du 
                    LEFT JOIN pedido p ON du.pedido_id = p.id 
                    LEFT JOIN cliente c ON p.cliente_id = c.id
                    WHERE du.genero = :genero
                    ORDER BY p.fecha_pedido DESC";
            $results = $this->connectionManager->selectAll($sql, ['genero' => $genero]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding detalles by gender: " . $e->getMessage());
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
                           p.fecha_pedido, p.estado as pedido_estado,
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido
                    FROM {$this->table} du 
                    LEFT JOIN pedido p ON du.pedido_id = p.id 
                    LEFT JOIN cliente c ON p.cliente_id = c.id
                    ORDER BY p.fecha_pedido DESC, du.id";
            $results = $this->connectionManager->selectAll($sql);
            
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
            $sql = "UPDATE {$this->table} 
                    SET pedido_id = :pedido_id, talla = :talla, genero = :genero, 
                        numero_camisola = :numero_camisola, medida_pecho = :medida_pecho, 
                        medida_cintura = :medida_cintura, medida_cadera = :medida_cadera, 
                        observaciones = :observaciones
                    WHERE id = :id";
            
            $params = [
                'id' => $detalle->getId(),
                'pedido_id' => $detalle->getPedidoId(),
                'talla' => $detalle->getTalla(),
                'genero' => $detalle->getGenero(),
                'numero_camisola' => $detalle->getNumeroCamisola(),
                'medida_pecho' => $detalle->getMedidaPecho(),
                'medida_cintura' => $detalle->getMedidaCintura(),
                'medida_cadera' => $detalle->getMedidaCadera(),
                'observaciones' => $detalle->getObservaciones()
            ];

            return $this->connectionManager->update($sql, $params);
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
            $sql = "DELETE FROM {$this->table} WHERE id = :id";
            return $this->connectionManager->delete($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log("Error deleting detalle uniforme: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar todos los detalles de un pedido
     */
    public function deleteByPedido(int $pedidoId): bool
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE pedido_id = :pedido_id";
            return $this->connectionManager->delete($sql, ['pedido_id' => $pedidoId]);
        } catch (Exception $e) {
            error_log("Error deleting detalles by pedido: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener tallas más solicitadas
     */
    public function getMostRequestedSizes(): array
    {
        try {
            $sql = "SELECT talla, 
                           COUNT(*) as cantidad_pedidos,
                           COUNT(DISTINCT pedido_id) as pedidos_unicos
                    FROM {$this->table}
                    GROUP BY talla
                    ORDER BY cantidad_pedidos DESC";
                    
            $results = $this->connectionManager->selectAll($sql);
            
            return $results;
        } catch (Exception $e) {
            error_log("Error getting most requested sizes: " . $e->getMessage());
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
                           COUNT(DISTINCT pedido_id) as pedidos_unicos,
                           AVG(medida_pecho) as promedio_pecho,
                           AVG(medida_cintura) as promedio_cintura,
                           AVG(medida_cadera) as promedio_cadera
                    FROM {$this->table}
                    WHERE medida_pecho IS NOT NULL 
                    AND medida_cintura IS NOT NULL 
                    AND medida_cadera IS NOT NULL
                    GROUP BY genero
                    ORDER BY cantidad_pedidos DESC";
                    
            $results = $this->connectionManager->selectAll($sql);
            
            return $results;
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
                           COUNT(*) as cantidad_pedidos
                    FROM {$this->table}
                    WHERE numero_camisola IS NOT NULL
                    GROUP BY numero_camisola
                    ORDER BY cantidad_pedidos DESC
                    LIMIT 20";
                    
            $results = $this->connectionManager->selectAll($sql);
            
            return $results;
        } catch (Exception $e) {
            error_log("Error getting most popular numbers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar por medidas específicas
     */
    public function findByMeasurements(array $measurements): array
    {
        try {
            $conditions = [];
            $params = [];
            
            if (isset($measurements['pecho'])) {
                $conditions[] = "medida_pecho BETWEEN :pecho_min AND :pecho_max";
                $params['pecho_min'] = $measurements['pecho'] - 5;
                $params['pecho_max'] = $measurements['pecho'] + 5;
            }
            
            if (isset($measurements['cintura'])) {
                $conditions[] = "medida_cintura BETWEEN :cintura_min AND :cintura_max";
                $params['cintura_min'] = $measurements['cintura'] - 5;
                $params['cintura_max'] = $measurements['cintura'] + 5;
            }
            
            if (isset($measurements['cadera'])) {
                $conditions[] = "medida_cadera BETWEEN :cadera_min AND :cadera_max";
                $params['cadera_min'] = $measurements['cadera'] - 5;
                $params['cadera_max'] = $measurements['cadera'] + 5;
            }
            
            if (empty($conditions)) {
                return [];
            }
            
            $whereClause = implode(' AND ', $conditions);
            
            $sql = "SELECT du.*, 
                           p.fecha_pedido, p.estado as pedido_estado,
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido
                    FROM {$this->table} du 
                    LEFT JOIN pedido p ON du.pedido_id = p.id 
                    LEFT JOIN cliente c ON p.cliente_id = c.id
                    WHERE {$whereClause}
                    ORDER BY p.fecha_pedido DESC";
                    
            $results = $this->connectionManager->selectAll($sql, $params);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding by measurements: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener detalles con observaciones especiales
     */
    public function findWithSpecialObservations(): array
    {
        try {
            $sql = "SELECT du.*, 
                           p.fecha_pedido, p.estado as pedido_estado,
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido
                    FROM {$this->table} du 
                    LEFT JOIN pedido p ON du.pedido_id = p.id 
                    LEFT JOIN cliente c ON p.cliente_id = c.id
                    WHERE du.observaciones IS NOT NULL 
                    AND du.observaciones != ''
                    ORDER BY p.fecha_pedido DESC";
            $results = $this->connectionManager->selectAll($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding with special observations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mapear datos de BD a entidad
     */
    private function mapToEntity(array $data): DetalleUniformeEntity
    {
        $detalle = new DetalleUniformeEntity();
        $detalle->setId($data['id']);
        $detalle->setPedidoId($data['pedido_id']);
        $detalle->setTalla($data['talla']);
        $detalle->setGenero($data['genero']);
        $detalle->setNumeroCamisola($data['numero_camisola']);
        $detalle->setMedidaPecho($data['medida_pecho']);
        $detalle->setMedidaCintura($data['medida_cintura']);
        $detalle->setMedidaCadera($data['medida_cadera']);
        $detalle->setObservaciones($data['observaciones']);
        
        return $detalle;
    }
}