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
            $data = [
                'descripcion' => $rol->getDescripcion(),
                'esActivo' => $rol->getEsActivo() ? 1 : 0
                // fechaRegistro se genera automáticamente con DEFAULT CURRENT_TIMESTAMP
            ];

            $id = $this->connectionManager->insert($this->table, $data);
            
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
            $sql = "SELECT idRol, descripcion, esActivo, fechaRegistro 
                    FROM {$this->table} 
                    WHERE idRol = :id";
            
            $result = $this->connectionManager->selectOne($sql, ['id' => $id]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding rol by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener rol por descripción
     */
    public function findByDescripcion(string $descripcion): ?RolEntity
    {
        try {
            $sql = "SELECT idRol, descripcion, esActivo, fechaRegistro 
                    FROM {$this->table} 
                    WHERE descripcion = :descripcion";
            
            $result = $this->connectionManager->selectOne($sql, ['descripcion' => $descripcion]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding rol by descripcion: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener todos los roles
     */
    public function findAll(): array
    {
        try {
            $sql = "SELECT idRol, descripcion, esActivo, fechaRegistro 
                    FROM {$this->table} 
                    ORDER BY descripcion";
            
            $results = $this->connectionManager->select($sql);
            
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
            $sql = "SELECT idRol, descripcion, esActivo, fechaRegistro 
                    FROM {$this->table} 
                    WHERE esActivo = 1 
                    ORDER BY descripcion";
            
            $results = $this->connectionManager->select($sql);
            
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
            $data = [
                'descripcion' => $rol->getDescripcion(),
                'esActivo' => $rol->getEsActivo() ? 1 : 0
            ];

            $where = "idRol = :id";
            $whereParams = ['id' => $rol->getIdRol()];

            return $this->connectionManager->update($this->table, $data, $where, $whereParams);
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
            $data = ['esActivo' => 0];
            $where = "idRol = :id";
            $whereParams = ['id' => $id];
            
            return $this->connectionManager->update($this->table, $data, $where, $whereParams);
        } catch (Exception $e) {
            error_log("Error deleting rol: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Activar rol
     */
    public function activate(int $id): bool
    {
        try {
            $data = ['esActivo' => 1];
            $where = "idRol = :id";
            $whereParams = ['id' => $id];
            
            return $this->connectionManager->update($this->table, $data, $where, $whereParams);
        } catch (Exception $e) {
            error_log("Error activating rol: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si existe un rol por descripción
     */
    public function existsByDescripcion(string $descripcion, ?int $excludeId = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE descripcion = :descripcion";
            $params = ['descripcion' => $descripcion];
            
            if ($excludeId !== null) {
                $sql .= " AND idRol != :excludeId";
                $params['excludeId'] = $excludeId;
            }
            
            $result = $this->connectionManager->selectOne($sql, $params);
            
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
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE esActivo = 1";
            $result = $this->connectionManager->selectOne($sql);
            
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log("Error counting active roles: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Contar total de roles
     */
    public function count(): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table}";
            $result = $this->connectionManager->selectOne($sql);
            
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log("Error counting roles: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener roles con cantidad de usuarios asignados
     */
    public function findAllWithUserCount(): array
    {
        try {
            $sql = "SELECT r.idRol, r.descripcion, r.esActivo, r.fechaRegistro,
                           COUNT(u.idUsuario) as total_usuarios
                    FROM {$this->table} r
                    LEFT JOIN usuario u ON r.idRol = u.idRol
                    GROUP BY r.idRol, r.descripcion, r.esActivo, r.fechaRegistro
                    ORDER BY r.descripcion";
            
            $results = $this->connectionManager->select($sql);
            
            $roles = [];
            foreach ($results as $data) {
                $rol = $this->mapToEntity($data);
                $roles[] = [
                    'rol' => $rol,
                    'total_usuarios' => (int)$data['total_usuarios']
                ];
            }
            
            return $roles;
        } catch (Exception $e) {
            error_log("Error finding roles with user count: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mapear datos de BD a entidad
     */
    private function mapToEntity(array $data): RolEntity
    {
        $rol = new RolEntity();
        $rol->setIdRol((int)$data['idRol']);
        $rol->setDescripcion($data['descripcion']);
        
        // Manejo del tipo bit de MySQL
        if (isset($data['esActivo'])) {
            $esActivo = $data['esActivo'];
            if (is_string($esActivo)) {
                $rol->setEsActivo($esActivo !== "\x00" && $esActivo !== '0');
            } else {
                $rol->setEsActivo((bool)$esActivo);
            }
        }
        
        // Manejo de DateTime
        if (isset($data['fechaRegistro'])) {
            try {
                $rol->setFechaRegistro(new \DateTime($data['fechaRegistro']));
            } catch (\Exception $e) {
                $rol->setFechaRegistro(new \DateTime());
            }
        }
        
        return $rol;
    }
}