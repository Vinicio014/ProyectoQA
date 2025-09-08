<?php

namespace App\Repositories;

use App\Entities\RolEntity;
use App\Infraestructura\ConnectionManager;
use Exception;

class RolRepository
{
    private ConnectionManager $connectionManager;
    private string $table = 'rol';

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    /**
     * Crear un nuevo rol
     */
    public function create(RolEntity $rol): ?RolEntity
    {
        try {
            $sql = "INSERT INTO {$this->table} (nombre, descripcion, activo, fecha_creacion) 
                    VALUES (:nombre, :descripcion, :activo, NOW())";
            
            $params = [
                'nombre' => $rol->getNombre(),
                'descripcion' => $rol->getDescripcion(),
                'activo' => $rol->isActivo() ? 1 : 0
            ];

            $id = $this->connectionManager->insert($sql, $params);
            
            if ($id) {
                return $this->findById($id);
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error creating rol: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener rol por ID
     */
    public function findById(int $id): ?RolEntity
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id = :id";
            $result = $this->connectionManager->selectOne($sql, ['id' => $id]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding rol by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener rol por nombre
     */
    public function findByName(string $nombre): ?RolEntity
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE nombre = :nombre";
            $result = $this->connectionManager->selectOne($sql, ['nombre' => $nombre]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding rol by name: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener todos los roles
     */
    public function findAll(): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} ORDER BY nombre";
            $results = $this->connectionManager->selectAll($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding all roles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener roles activos
     */
    public function findActive(): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE activo = 1 ORDER BY nombre";
            $results = $this->connectionManager->selectAll($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding active roles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar rol
     */
    public function update(RolEntity $rol): bool
    {
        try {
            $sql = "UPDATE {$this->table} 
                    SET nombre = :nombre, descripcion = :descripcion, activo = :activo,
                        fecha_modificacion = NOW()
                    WHERE id = :id";
            
            $params = [
                'id' => $rol->getId(),
                'nombre' => $rol->getNombre(),
                'descripcion' => $rol->getDescripcion(),
                'activo' => $rol->isActivo() ? 1 : 0
            ];

            return $this->connectionManager->update($sql, $params);
        } catch (Exception $e) {
            error_log("Error updating rol: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar rol (soft delete)
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "UPDATE {$this->table} SET activo = 0, fecha_modificacion = NOW() WHERE id = :id";
            return $this->connectionManager->update($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log("Error deleting rol: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si existe un rol por nombre
     */
    public function existsByName(string $nombre): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE nombre = :nombre";
            $result = $this->connectionManager->selectOne($sql, ['nombre' => $nombre]);
            
            return ($result['count'] ?? 0) > 0;
        } catch (Exception $e) {
            error_log("Error checking rol existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Contar roles activos
     */
    public function countActive(): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE activo = 1";
            $result = $this->connectionManager->selectOne($sql);
            
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log("Error counting active roles: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mapear datos de BD a entidad
     */
    private function mapToEntity(array $data): RolEntity
    {
        $rol = new RolEntity();
        $rol->setId($data['id']);
        $rol->setNombre($data['nombre']);
        $rol->setDescripcion($data['descripcion']);
        $rol->setActivo((bool)$data['activo']);
        $rol->setFechaCreacion($data['fecha_creacion']);
        $rol->setFechaModificacion($data['fecha_modificacion']);
        
        return $rol;
    }
}