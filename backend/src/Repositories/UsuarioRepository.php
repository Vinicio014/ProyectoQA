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
            $sql = "INSERT INTO {$this->table} (username, email, password_hash, nombre, apellido, rol_id, activo, fecha_creacion) 
                    VALUES (:username, :email, :password_hash, :nombre, :apellido, :rol_id, :activo, NOW())";
            
            $params = [
                'username' => $usuario->getUsername(),
                'email' => $usuario->getEmail(),
                'password_hash' => $usuario->getPasswordHash(),
                'nombre' => $usuario->getNombre(),
                'apellido' => $usuario->getApellido(),
                'rol_id' => $usuario->getRolId(),
                'activo' => $usuario->isActivo() ? 1 : 0
            ];

            $id = $this->connectionManager->insert($sql, $params);
            
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
            $sql = "SELECT u.*, r.nombre as rol_nombre 
                    FROM {$this->table} u 
                    LEFT JOIN rol r ON u.rol_id = r.id 
                    WHERE u.id = :id";
            $result = $this->connectionManager->selectOne($sql, ['id' => $id]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding usuario by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener usuario por email
     */
    public function findByEmail(string $email): ?UsuarioEntity
    {
        try {
            $sql = "SELECT u.*, r.nombre as rol_nombre 
                    FROM {$this->table} u 
                    LEFT JOIN rol r ON u.rol_id = r.id 
                    WHERE u.email = :email";
            $result = $this->connectionManager->selectOne($sql, ['email' => $email]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding usuario by email: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener usuario por username
     */
    public function findByUsername(string $username): ?UsuarioEntity
    {
        try {
            $sql = "SELECT u.*, r.nombre as rol_nombre 
                    FROM {$this->table} u 
                    LEFT JOIN rol r ON u.rol_id = r.id 
                    WHERE u.username = :username";
            $result = $this->connectionManager->selectOne($sql, ['username' => $username]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding usuario by username: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener todos los usuarios
     */
    public function findAll(): array
    {
        try {
            $sql = "SELECT u.*, r.nombre as rol_nombre 
                    FROM {$this->table} u 
                    LEFT JOIN rol r ON u.rol_id = r.id 
                    ORDER BY u.nombre, u.apellido";
            $results = $this->connectionManager->selectAll($sql);
            
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
            $sql = "SELECT u.*, r.nombre as rol_nombre 
                    FROM {$this->table} u 
                    LEFT JOIN rol r ON u.rol_id = r.id 
                    WHERE u.rol_id = :rol_id 
                    ORDER BY u.nombre, u.apellido";
            $results = $this->connectionManager->selectAll($sql, ['rol_id' => $rolId]);
            
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
            $sql = "SELECT u.*, r.nombre as rol_nombre 
                    FROM {$this->table} u 
                    LEFT JOIN rol r ON u.rol_id = r.id 
                    WHERE u.activo = 1 
                    ORDER BY u.nombre, u.apellido";
            $results = $this->connectionManager->selectAll($sql);
            
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
            $sql = "UPDATE {$this->table} 
                    SET username = :username, email = :email, nombre = :nombre, 
                        apellido = :apellido, rol_id = :rol_id, activo = :activo,
                        fecha_modificacion = NOW()
                    WHERE id = :id";
            
            $params = [
                'id' => $usuario->getId(),
                'username' => $usuario->getUsername(),
                'email' => $usuario->getEmail(),
                'nombre' => $usuario->getNombre(),
                'apellido' => $usuario->getApellido(),
                'rol_id' => $usuario->getRolId(),
                'activo' => $usuario->isActivo() ? 1 : 0
            ];

            return $this->connectionManager->update($sql, $params);
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
            $sql = "UPDATE {$this->table} SET activo = 0, fecha_modificacion = NOW() WHERE id = :id";
            return $this->connectionManager->update($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log("Error deleting usuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar credenciales de login
     */
    public function verifyCredentials(string $email, string $password): ?UsuarioEntity
    {
        try {
            $usuario = $this->findByEmail($email);
            
            if ($usuario && $usuario->isActivo() && password_verify($password, $usuario->getPasswordHash())) {
                $this->updateLastLogin($usuario->getId());
                return $usuario;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error verifying credentials: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verificar si existe email
     */
    public function existsByEmail(string $email): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = :email";
            $result = $this->connectionManager->selectOne($sql, ['email' => $email]);
            
            return ($result['count'] ?? 0) > 0;
        } catch (Exception $e) {
            error_log("Error checking email existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si existe username
     */
    public function existsByUsername(string $username): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE username = :username";
            $result = $this->connectionManager->selectOne($sql, ['username' => $username]);
            
            return ($result['count'] ?? 0) > 0;
        } catch (Exception $e) {
            error_log("Error checking username existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar último login
     */
    public function updateLastLogin(int $id): bool
    {
        try {
            $sql = "UPDATE {$this->table} SET ultimo_login = NOW() WHERE id = :id";
            return $this->connectionManager->update($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log("Error updating last login: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambiar contraseña
     */
    public function changePassword(int $id, string $newPassword): bool
    {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE {$this->table} SET password_hash = :password, fecha_modificacion = NOW() WHERE id = :id";
            
            return $this->connectionManager->update($sql, [
                'id' => $id,
                'password' => $hashedPassword
            ]);
        } catch (Exception $e) {
            error_log("Error changing password: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mapear datos de BD a entidad
     */
    private function mapToEntity(array $data): UsuarioEntity
    {
        $usuario = new UsuarioEntity();
        $usuario->setId($data['id']);
        $usuario->setUsername($data['username']);
        $usuario->setEmail($data['email']);
        $usuario->setPasswordHash($data['password_hash']);
        $usuario->setNombre($data['nombre']);
        $usuario->setApellido($data['apellido']);
        $usuario->setRolId($data['rol_id']);
        $usuario->setActivo((bool)$data['activo']);
        $usuario->setFechaCreacion($data['fecha_creacion']);
        $usuario->setFechaModificacion($data['fecha_modificacion']);
        $usuario->setUltimoLogin($data['ultimo_login']);
        
        return $usuario;
    }
}