<?php

namespace App\Repositories;

use App\Entities\UsuarioEntity;
use App\Infraestructura\ConnectionManager;
use Exception;

class UsuarioRepository
{
    private ConnectionManager $connectionManager;
    private string $table = 'usuario';

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    /**
     * Crear un nuevo usuario
     */
    public function create(UsuarioEntity $usuario): ?UsuarioEntity
    {
        try {
            // Hash de contraseña antes de guardar
            $usuario->hashContrasenia();
            
            $data = [
                'nombre' => $usuario->getNombre(),
                'correo' => $usuario->getCorreo(),
                'idRol' => $usuario->getIdRol(),
                'contrasenia' => $usuario->getContrasenia(),
                'esActivo' => $usuario->getEsActivo() ? 1 : 0
                // fechaRegistro se genera automáticamente con DEFAULT CURRENT_TIMESTAMP
            ];

            $id = $this->connectionManager->insert($this->table, $data);
            
            if ($id) {
                return $this->findById($id);
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error creating usuario: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener usuario por ID
     */
    public function findById(int $id): ?UsuarioEntity
    {
        try {
            $sql = "SELECT u.idUsuario, u.nombre, u.correo, u.idRol, u.contrasenia, 
                           u.esActivo, u.fechaRegistro, r.descripcion as rol_descripcion 
                    FROM {$this->table} u 
                    LEFT JOIN rol r ON u.idRol = r.idRol 
                    WHERE u.idUsuario = :id";
            
            $result = $this->connectionManager->selectOne($sql, ['id' => $id]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding usuario by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener usuario por correo
     */
    public function findByCorreo(string $correo): ?UsuarioEntity
    {
        try {
            $sql = "SELECT u.idUsuario, u.nombre, u.correo, u.idRol, u.contrasenia, 
                           u.esActivo, u.fechaRegistro, r.descripcion as rol_descripcion 
                    FROM {$this->table} u 
                    LEFT JOIN rol r ON u.idRol = r.idRol 
                    WHERE u.correo = :correo";
            
            $result = $this->connectionManager->selectOne($sql, ['correo' => $correo]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding usuario by correo: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener todos los usuarios
     */
    public function findAll(): array
    {
        try {
            $sql = "SELECT u.idUsuario, u.nombre, u.correo, u.idRol, u.contrasenia, 
                           u.esActivo, u.fechaRegistro, r.descripcion as rol_descripcion 
                    FROM {$this->table} u 
                    LEFT JOIN rol r ON u.idRol = r.idRol 
                    ORDER BY u.nombre";
            
            $results = $this->connectionManager->select($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding all usuarios: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener usuarios por rol
     */
    public function findByRole(int $rolId): array
    {
        try {
            $sql = "SELECT u.idUsuario, u.nombre, u.correo, u.idRol, u.contrasenia, 
                           u.esActivo, u.fechaRegistro, r.descripcion as rol_descripcion 
                    FROM {$this->table} u 
                    LEFT JOIN rol r ON u.idRol = r.idRol 
                    WHERE u.idRol = :idRol 
                    ORDER BY u.nombre";
            
            $results = $this->connectionManager->select($sql, ['idRol' => $rolId]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding usuarios by role: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener usuarios activos
     */
    public function findActive(): array
    {
        try {
            $sql = "SELECT u.idUsuario, u.nombre, u.correo, u.idRol, u.contrasenia, 
                           u.esActivo, u.fechaRegistro, r.descripcion as rol_descripcion 
                    FROM {$this->table} u 
                    LEFT JOIN rol r ON u.idRol = r.idRol 
                    WHERE u.esActivo = 1 
                    ORDER BY u.nombre";
            
            $results = $this->connectionManager->select($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding active usuarios: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar usuario
     */
    public function update(UsuarioEntity $usuario): bool
    {
        try {
            $data = [
                'nombre' => $usuario->getNombre(),
                'correo' => $usuario->getCorreo(),
                'idRol' => $usuario->getIdRol(),
                'esActivo' => $usuario->getEsActivo() ? 1 : 0
            ];

            $where = "idUsuario = :id";
            $whereParams = ['id' => $usuario->getIdUsuario()];

            return $this->connectionManager->update($this->table, $data, $where, $whereParams);
        } catch (Exception $e) {
            error_log("Error updating usuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar usuario (soft delete)
     */
    public function delete(int $id): bool
    {
        try {
            $data = ['esActivo' => 0];
            $where = "idUsuario = :id";
            $whereParams = ['id' => $id];
            
            return $this->connectionManager->update($this->table, $data, $where, $whereParams);
        } catch (Exception $e) {
            error_log("Error deleting usuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Activar usuario
     */
    public function activate(int $id): bool
    {
        try {
            $data = ['esActivo' => 1];
            $where = "idUsuario = :id";
            $whereParams = ['id' => $id];
            
            return $this->connectionManager->update($this->table, $data, $where, $whereParams);
        } catch (Exception $e) {
            error_log("Error activating usuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar credenciales de login
     */
    public function verifyCredentials(string $correo, string $contrasenia): ?UsuarioEntity
    {
        try {
            $usuario = $this->findByCorreo($correo);
            
            if ($usuario && 
                $usuario->getEsActivo() && 
                $usuario->verificarContrasenia($contrasenia)) {
                return $usuario;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error verifying credentials: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verificar si existe correo
     */
    public function existsByCorreo(string $correo, ?int $excludeId = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE correo = :correo";
            $params = ['correo' => $correo];
            
            if ($excludeId !== null) {
                $sql .= " AND idUsuario != :excludeId";
                $params['excludeId'] = $excludeId;
            }
            
            $result = $this->connectionManager->selectOne($sql, $params);
            
            return ($result['count'] ?? 0) > 0;
        } catch (Exception $e) {
            error_log("Error checking correo existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambiar contraseña
     */
    public function changePassword(int $id, string $nuevaContrasenia): bool
    {
        try {
            $contraseniaHashed = password_hash($nuevaContrasenia, PASSWORD_DEFAULT);
            
            $data = ['contrasenia' => $contraseniaHashed];
            $where = "idUsuario = :id";
            $whereParams = ['id' => $id];
            
            return $this->connectionManager->update($this->table, $data, $where, $whereParams);
        } catch (Exception $e) {
            error_log("Error changing password: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Contar usuarios por rol
     */
    public function countByRole(int $rolId): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE idRol = :idRol";
            $result = $this->connectionManager->selectOne($sql, ['idRol' => $rolId]);
            
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log("Error counting usuarios by role: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Contar usuarios activos
     */
    public function countActive(): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE esActivo = 1";
            $result = $this->connectionManager->selectOne($sql);
            
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log("Error counting active usuarios: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mapear datos de BD a entidad
     */
    private function mapToEntity(array $data): UsuarioEntity
    {
        $usuario = new UsuarioEntity();
        $usuario->setIdUsuario((int)$data['idUsuario']);
        $usuario->setNombre($data['nombre']);
        $usuario->setCorreo($data['correo']);
        $usuario->setIdRol((int)$data['idRol']);
        $usuario->setContrasenia($data['contrasenia']);
        
        // Manejo del tipo bit de MySQL
        if (isset($data['esActivo'])) {
            $esActivo = $data['esActivo'];
            if (is_string($esActivo)) {
                $usuario->setEsActivo($esActivo !== "\x00" && $esActivo !== '0');
            } else {
                $usuario->setEsActivo((bool)$esActivo);
            }
        }
        
        // Manejo de DateTime
        if (isset($data['fechaRegistro'])) {
            try {
                $usuario->setFechaRegistro(new \DateTime($data['fechaRegistro']));
            } catch (\Exception $e) {
                $usuario->setFechaRegistro(new \DateTime());
            }
        }
        
        return $usuario;
    }
}