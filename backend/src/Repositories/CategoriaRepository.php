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
            $sql = "INSERT INTO {$this->table} (nombre, descripcion, activo, fecha_creacion) 
                    VALUES (:nombre, :descripcion, :activo, NOW())";
            
            $params = [
                'nombre' => $categoria->getNombre(),
                'descripcion' => $categoria->getDescripcion(),
                'activo' => $categoria->isActivo() ? 1 : 0
            ];

            $id = $this->connectionManager->insert($sql, $params);
            
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
            $sql = "SELECT * FROM {$this->table} WHERE id = :id";
            $result = $this->connectionManager->selectOne($sql, ['id' => $id]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding categoria by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener categoría por nombre
     */
    public function findByName(string $nombre): ?CategoriaEntity
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE nombre = :nombre";
            $result = $this->connectionManager->selectOne($sql, ['nombre' => $nombre]);
            
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
            $sql = "SELECT * FROM {$this->table} ORDER BY nombre";
            $results = $this->connectionManager->selectAll($sql);
            
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
            $sql = "SELECT * FROM {$this->table} WHERE activo = 1 ORDER BY nombre";
            $results = $this->connectionManager->selectAll($sql);
            
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
                    INNER JOIN producto p ON c.id = p.categoria_id 
                    WHERE c.activo = 1 AND p.activo = 1
                    ORDER BY c.nombre";
            $results = $this->connectionManager->selectAll($sql);
            
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
            $sql = "UPDATE {$this->table} 
                    SET nombre = :nombre, descripcion = :descripcion, activo = :activo,
                        fecha_modificacion = NOW()
                    WHERE id = :id";
            
            $params = [
                'id' => $categoria->getId(),
                'nombre' => $categoria->getNombre(),
                'descripcion' => $categoria->getDescripcion(),
                'activo' => $categoria->isActivo() ? 1 : 0
            ];

            return $this->connectionManager->update($sql, $params);
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
            $sql = "UPDATE {$this->table} SET activo = 0, fecha_modificacion = NOW() WHERE id = :id";
            return $this->connectionManager->update($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log("Error deleting categoria: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si existe nombre de categoría
     */
    public function existsByName(string $nombre): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE nombre = :nombre";
            $result = $this->connectionManager->selectOne($sql, ['nombre' => $nombre]);
            
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
            $sql = "SELECT COUNT(*) as count FROM producto WHERE categoria_id = :categoria_id AND activo = 1";
            $result = $this->connectionManager->selectOne($sql, ['categoria_id' => $categoriaId]);
            
            return (int)($result['count'] ?? 0);
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
            $sql = "SELECT c.*, COUNT(dv.id) as total_vendidos
                    FROM {$this->table} c
                    INNER JOIN producto p ON c.id = p.categoria_id
                    INNER JOIN detalleventa dv ON p.id = dv.producto_id
                    WHERE c.activo = 1 AND p.activo = 1
                    GROUP BY c.id
                    ORDER BY total_vendidos DESC
                    LIMIT :limit";
                    
            $results = $this->connectionManager->selectAll($sql, ['limit' => $limit]);
            
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
        $categoria->setId($data['id']);
        $categoria->setNombre($data['nombre']);
        $categoria->setDescripcion($data['descripcion']);
        $categoria->setActivo((bool)$data['activo']);
        $categoria->setFechaCreacion($data['fecha_creacion']);
        $categoria->setFechaModificacion($data['fecha_modificacion']);
        
        return $categoria;
    }
}