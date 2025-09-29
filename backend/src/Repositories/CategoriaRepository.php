<?php

namespace App\Repositories;

use App\Entities\CategoriaEntity;
use App\Infraestructura\ConnectionManager;
use Exception;

class CategoriaRepository
{
    private ConnectionManager $connectionManager;
    private string $table = 'categoria';

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    /**
     * Crear una nueva categoría
     */
    public function create(CategoriaEntity $categoria): ?CategoriaEntity
    {
        try {
            // Preparar datos según estructura de BD
            $data = [
                'descripcion' => $categoria->getDescripcion(),
                'esActivo' => $categoria->getEsActivo() ? 1 : 0
                // fechaRegistro se auto-genera con DEFAULT CURRENT_TIMESTAMP
            ];

            // Usar el método insert de ConnectionManager
            $id = $this->connectionManager->insert($this->table, $data);
            
            if ($id) {
                return $this->findById($id);
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error creating categoria: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener categoría por ID
     */
    public function findById(int $id): ?CategoriaEntity
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE idCategoria = :id";
            $result = $this->connectionManager->selectOne($sql, ['id' => $id]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding categoria by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener categoría por descripción
     */
    public function findByName(string $descripcion): ?CategoriaEntity
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE descripcion = :descripcion";
            $result = $this->connectionManager->selectOne($sql, ['descripcion' => $descripcion]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding categoria by name: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener todas las categorías
     */
    public function findAll(): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} ORDER BY descripcion";
            $results = $this->connectionManager->select($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding all categorias: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener categorías activas
     */
    public function findActive(): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE esActivo = 1 ORDER BY descripcion";
            $results = $this->connectionManager->select($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding active categorias: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener categorías con productos
     */
    public function findWithProducts(): array
    {
        try {
            $sql = "SELECT DISTINCT c.* 
                    FROM {$this->table} c 
                    INNER JOIN producto p ON c.idCategoria = p.idCategoria 
                    WHERE c.esActivo = 1 AND p.esActivo = 1
                    ORDER BY c.descripcion";
            $results = $this->connectionManager->select($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding categorias with products: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar categoría
     */
    public function update(CategoriaEntity $categoria): bool
    {
        try {
            $data = [
                'descripcion' => $categoria->getDescripcion(),
                'esActivo' => $categoria->getEsActivo() ? 1 : 0
            ];

            $where = "idCategoria = :id";
            $whereParams = ['id' => $categoria->getIdCategoria()];

            $rowCount = $this->connectionManager->update($this->table, $data, $where, $whereParams);
            return $rowCount > 0;
        } catch (Exception $e) {
            error_log("Error updating categoria: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar categoría (soft delete)
     */
    public function delete(int $id): bool
    {
        try {
            $data = ['esActivo' => 0];
            $where = "idCategoria = :id";
            $whereParams = ['id' => $id];

            $rowCount = $this->connectionManager->update($this->table, $data, $where, $whereParams);
            return $rowCount > 0;
        } catch (Exception $e) {
            error_log("Error deleting categoria: " . $e->getMessage());
            return false;
        }
    }
    /**
 * Activar categoría
 */
public function activate(int $id): bool
{
    try {
        $data = ['esActivo' => 1];
        $where = "idCategoria = :id";
        $whereParams = ['id' => $id];
        
        return $this->connectionManager->update($this->table, $data, $where, $whereParams);
    } catch (Exception $e) {
        error_log("Error activating categoria: " . $e->getMessage());
        return false;
    }
}

    /**
     * Verificar si existe descripción de categoría
     */
    public function existsByName(string $descripcion): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE descripcion = :descripcion";
            $result = $this->connectionManager->selectOne($sql, ['descripcion' => $descripcion]);
            
            return ($result['count'] ?? 0) > 0;
        } catch (Exception $e) {
            error_log("Error checking categoria existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Contar productos por categoría
     */
    public function countProducts(int $categoriaId): int
    {
        try {
            $count = $this->connectionManager->count(
                'producto', 
                'idCategoria = :categoria_id AND esActivo = 1',
                ['categoria_id' => $categoriaId]
            );
            
            return $count;
        } catch (Exception $e) {
            error_log("Error counting products by categoria: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener categorías más vendidas
     */
    public function getMostSoldCategories(int $limit = 5): array
    {
        try {
            $sql = "SELECT c.*, COUNT(dv.idDetalleVenta) as total_vendidos
                    FROM {$this->table} c
                    INNER JOIN producto p ON c.idCategoria = p.idCategoria
                    INNER JOIN detalleventa dv ON p.idProducto = dv.idProducto
                    WHERE c.esActivo = 1 AND p.esActivo = 1
                    GROUP BY c.idCategoria, c.descripcion, c.esActivo, c.fechaRegistro
                    ORDER BY total_vendidos DESC
                    LIMIT {$limit}";
                    
            $results = $this->connectionManager->select($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error getting most sold categories: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mapear datos de BD a entidad
     */
    private function mapToEntity(array $data): CategoriaEntity
    {
        $categoria = new CategoriaEntity();
        
        // Mapear ID
        if (isset($data['idCategoria'])) {
            $categoria->setIdCategoria((int)$data['idCategoria']);
        }
        
        // Mapear descripción
        if (isset($data['descripcion'])) {
            $categoria->setDescripcion($data['descripcion']);
        }
        
        // Mapear esActivo (convertir bit a bool)
        if (isset($data['esActivo'])) {
            // MySQL devuelve bit como string "\x00" o "\x01"
            $esActivo = $data['esActivo'];
            if (is_string($esActivo)) {
                $categoria->setEsActivo($esActivo !== "\x00" && $esActivo !== '0');
            } else {
                $categoria->setEsActivo((bool)$esActivo);
            }
        }
        
        // Mapear fechaRegistro
        if (isset($data['fechaRegistro'])) {
            if (method_exists($categoria, 'setFechaRegistroFromString')) {
                $categoria->setFechaRegistroFromString($data['fechaRegistro']);
            } else {
                try {
                    $categoria->setFechaRegistro(new \DateTime($data['fechaRegistro']));
                } catch (\Exception $e) {
                    $categoria->setFechaRegistro(new \DateTime());
                }
            }
        }
        
        return $categoria;
    }
}