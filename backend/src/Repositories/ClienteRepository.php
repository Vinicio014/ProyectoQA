<?php

namespace App\Repositories;

use App\Entities\ClienteEntity;
use App\Infraestructura\ConnectionManager;
use Exception;

class ClienteRepository
{
    private ConnectionManager $connectionManager;
    private string $table = 'cliente';

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    /**
     * Crear un nuevo cliente
     */
    public function create(ClienteEntity $cliente): ?ClienteEntity
    {
        try {
            $sql = "INSERT INTO {$this->table} (nombre, apellido, documento, tipo_documento, telefono, 
                    email, direccion, activo, fecha_creacion) 
                    VALUES (:nombre, :apellido, :documento, :tipo_documento, :telefono, 
                    :email, :direccion, :activo, NOW())";
            
            $params = [
                'nombre' => $cliente->getNombre(),
                'apellido' => $cliente->getApellido(),
                'documento' => $cliente->getDocumento(),
                'tipo_documento' => $cliente->getTipoDocumento(),
                'telefono' => $cliente->getTelefono(),
                'email' => $cliente->getEmail(),
                'direccion' => $cliente->getDireccion(),
                'activo' => $cliente->isActivo() ? 1 : 0
            ];

            $id = $this->connectionManager->insert($sql, $params);
            
            if ($id) {
                return $this->findById($id);
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error creating cliente: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener cliente por ID
     */
    public function findById(int $id): ?ClienteEntity
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id = :id";
            $result = $this->connectionManager->selectOne($sql, ['id' => $id]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding cliente by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener cliente por documento
     */
    public function findByDocument(string $documento): ?ClienteEntity
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE documento = :documento";
            $result = $this->connectionManager->selectOne($sql, ['documento' => $documento]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding cliente by document: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener cliente por teléfono
     */
    public function findByPhone(string $telefono): ?ClienteEntity
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE telefono = :telefono";
            $result = $this->connectionManager->selectOne($sql, ['telefono' => $telefono]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding cliente by phone: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener todos los clientes
     */
    public function findAll(): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} ORDER BY nombre, apellido";
            $results = $this->connectionManager->selectAll($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding all clientes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar clientes por nombre
     */
    public function searchByName(string $name): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE CONCAT(nombre, ' ', apellido) LIKE :name 
                    OR nombre LIKE :name 
                    OR apellido LIKE :name 
                    ORDER BY nombre, apellido";
            
            $searchTerm = "%{$name}%";
            $results = $this->connectionManager->selectAll($sql, ['name' => $searchTerm]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error searching clientes by name: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener clientes activos
     */
    public function findActive(): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE activo = 1 ORDER BY nombre, apellido";
            $results = $this->connectionManager->selectAll($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding active clientes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar cliente
     */
    public function update(ClienteEntity $cliente): bool
    {
        try {
            $sql = "UPDATE {$this->table} 
                    SET nombre = :nombre, apellido = :apellido, documento = :documento, 
                        tipo_documento = :tipo_documento, telefono = :telefono, email = :email,
                        direccion = :direccion, activo = :activo, fecha_modificacion = NOW()
                    WHERE id = :id";
            
            $params = [
                'id' => $cliente->getId(),
                'nombre' => $cliente->getNombre(),
                'apellido' => $cliente->getApellido(),
                'documento' => $cliente->getDocumento(),
                'tipo_documento' => $cliente->getTipoDocumento(),
                'telefono' => $cliente->getTelefono(),
                'email' => $cliente->getEmail(),
                'direccion' => $cliente->getDireccion(),
                'activo' => $cliente->isActivo() ? 1 : 0
            ];

            return $this->connectionManager->update($sql, $params);
        } catch (Exception $e) {
            error_log("Error updating cliente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar cliente (soft delete)
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "UPDATE {$this->table} SET activo = 0, fecha_modificacion = NOW() WHERE id = :id";
            return $this->connectionManager->update($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log("Error deleting cliente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si existe documento
     */
    public function existsByDocument(string $documento): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE documento = :documento";
            $result = $this->connectionManager->selectOne($sql, ['documento' => $documento]);
            
            return ($result['count'] ?? 0) > 0;
        } catch (Exception $e) {
            error_log("Error checking document existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener clientes con pedidos pendientes
     */
    public function findWithPendingOrders(): array
    {
        try {
            $sql = "SELECT DISTINCT c.* 
                    FROM {$this->table} c 
                    INNER JOIN pedido p ON c.id = p.cliente_id 
                    WHERE p.estado = 'PENDIENTE' 
                    ORDER BY c.nombre, c.apellido";
            $results = $this->connectionManager->selectAll($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding clients with pending orders: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estadísticas de cliente
     */
    public function getClientStats(int $clientId): array
    {
        try {
            $sql = "SELECT 
                        COUNT(DISTINCT v.id) as total_ventas,
                        COUNT(DISTINCT p.id) as total_pedidos,
                        COALESCE(SUM(v.total), 0) as total_comprado,
                        MAX(v.fecha_venta) as ultima_compra
                    FROM {$this->table} c
                    LEFT JOIN venta v ON c.id = v.cliente_id
                    LEFT JOIN pedido p ON c.id = p.cliente_id
                    WHERE c.id = :client_id";
                    
            $result = $this->connectionManager->selectOne($sql, ['client_id' => $clientId]);
            
            return $result ?? [];
        } catch (Exception $e) {
            error_log("Error getting client stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener top clientes por ventas
     */
    public function getTopClients(int $limit = 10): array
    {
        try {
            $sql = "SELECT c.*, 
                           COUNT(v.id) as total_ventas,
                           SUM(v.total) as total_comprado
                    FROM {$this->table} c
                    INNER JOIN venta v ON c.id = v.cliente_id
                    GROUP BY c.id
                    ORDER BY total_comprado DESC
                    LIMIT :limit";
                    
            $results = $this->connectionManager->selectAll($sql, ['limit' => $limit]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error getting top clients: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mapear datos de BD a entidad
     */
    private function mapToEntity(array $data): ClienteEntity
    {
        $cliente = new ClienteEntity();
        $cliente->setId($data['id']);
        $cliente->setNombre($data['nombre']);
        $cliente->setApellido($data['apellido']);
        $cliente->setDocumento($data['documento']);
        $cliente->setTipoDocumento($data['tipo_documento']);
        $cliente->setTelefono($data['telefono']);
        $cliente->setEmail($data['email']);
        $cliente->setDireccion($data['direccion']);
        $cliente->setActivo((bool)$data['activo']);
        $cliente->setFechaCreacion($data['fecha_creacion']);
        $cliente->setFechaModificacion($data['fecha_modificacion']);
        
        return $cliente;
    }
}