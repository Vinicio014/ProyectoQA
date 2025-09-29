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
            $data = [
                'primer_nombre' => $cliente->getPrimerNombre(),
                'segundo_nombre' => $cliente->getSegundoNombre(),
                'primer_apellido' => $cliente->getPrimerApellido(),
                'segundo_apellido' => $cliente->getSegundoApellido(),
                'genero' => $cliente->getGenero(),
                'direccion' => $cliente->getDireccion(),
                'telefono' => $cliente->getTelefono()
            ];

            $id = $this->connectionManager->insert($this->table, $data);
            
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
            $sql = "SELECT * FROM {$this->table} WHERE idCliente = :id";
            $result = $this->connectionManager->selectOne($sql, ['id' => $id]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding cliente by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener cliente por teléfono
     */
    public function findByPhone(int $telefono): ?ClienteEntity
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
            $sql = "SELECT * FROM {$this->table} ORDER BY primer_nombre, primer_apellido";
            $results = $this->connectionManager->select($sql);
            
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
                    WHERE CONCAT(primer_nombre, ' ', IFNULL(segundo_nombre, ''), ' ', 
                                 primer_apellido, ' ', IFNULL(segundo_apellido, '')) LIKE :name 
                    OR primer_nombre LIKE :name 
                    OR primer_apellido LIKE :name 
                    ORDER BY primer_nombre, primer_apellido";
            
            $searchTerm = "%{$name}%";
            $results = $this->connectionManager->select($sql, ['name' => $searchTerm]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error searching clientes by name: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar clientes por género
     */
    public function findByGender(string $genero): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE genero = :genero ORDER BY primer_nombre";
            $results = $this->connectionManager->select($sql, ['genero' => $genero]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding clientes by gender: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar cliente
     */
    public function update(ClienteEntity $cliente): bool
    {
        try {
            $data = [
                'primer_nombre' => $cliente->getPrimerNombre(),
                'segundo_nombre' => $cliente->getSegundoNombre(),
                'primer_apellido' => $cliente->getPrimerApellido(),
                'segundo_apellido' => $cliente->getSegundoApellido(),
                'genero' => $cliente->getGenero(),
                'direccion' => $cliente->getDireccion(),
                'telefono' => $cliente->getTelefono()
            ];

            $where = "idCliente = :id";
            $whereParams = ['id' => $cliente->getIdCliente()];

            $rowCount = $this->connectionManager->update($this->table, $data, $where, $whereParams);
            return $rowCount > 0;
        } catch (Exception $e) {
            error_log("Error updating cliente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar cliente (hard delete - ya que no hay campo activo)
     */
    public function delete(int $id): bool
    {
        try {
            $where = "idCliente = :id";
            $params = ['id' => $id];

            $rowCount = $this->connectionManager->delete($this->table, $where, $params);
            return $rowCount > 0;
        } catch (Exception $e) {
            error_log("Error deleting cliente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si existe teléfono
     */
    public function existsByPhone(int $telefono): bool
    {
        try {
            $count = $this->connectionManager->count(
                $this->table,
                'telefono = :telefono',
                ['telefono' => $telefono]
            );
            
            return $count > 0;
        } catch (Exception $e) {
            error_log("Error checking phone existence: " . $e->getMessage());
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
                    INNER JOIN pedido p ON c.idCliente = p.idCliente 
                    WHERE p.estado_pedido = 'Pendiente' 
                    ORDER BY c.primer_nombre, c.primer_apellido";
            $results = $this->connectionManager->select($sql);
            
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
                        COUNT(DISTINCT v.idVenta) as total_ventas,
                        COUNT(DISTINCT p.idPedido) as total_pedidos,
                        COALESCE(SUM(v.Total), 0) as total_comprado,
                        MAX(v.fechaRegistro) as ultima_compra
                    FROM {$this->table} c
                    LEFT JOIN venta v ON c.idCliente = v.idCliente
                    LEFT JOIN pedido p ON c.idCliente = p.idCliente
                    WHERE c.idCliente = :client_id";
                    
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
                           COUNT(v.idVenta) as total_ventas,
                           SUM(v.Total) as total_comprado
                    FROM {$this->table} c
                    INNER JOIN venta v ON c.idCliente = v.idCliente
                    GROUP BY c.idCliente, c.primer_nombre, c.segundo_nombre, 
                             c.primer_apellido, c.segundo_apellido, c.genero, 
                             c.direccion, c.telefono
                    ORDER BY total_comprado DESC
                    LIMIT {$limit}";
                    
            $results = $this->connectionManager->select($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error getting top clients: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar clientes por género
     */
    public function countByGender(): array
    {
        try {
            $sql = "SELECT genero, COUNT(*) as total 
                    FROM {$this->table} 
                    GROUP BY genero";
            
            return $this->connectionManager->select($sql);
        } catch (Exception $e) {
            error_log("Error counting clients by gender: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mapear datos de BD a entidad
     */
    private function mapToEntity(array $data): ClienteEntity
    {
        $cliente = new ClienteEntity();
        
        if (isset($data['idCliente'])) {
            $cliente->setIdCliente((int)$data['idCliente']);
        }
        
        if (isset($data['primer_nombre'])) {
            $cliente->setPrimerNombre($data['primer_nombre']);
        }
        
        if (isset($data['segundo_nombre'])) {
            $cliente->setSegundoNombre($data['segundo_nombre']);
        }
        
        if (isset($data['primer_apellido'])) {
            $cliente->setPrimerApellido($data['primer_apellido']);
        }
        
        if (isset($data['segundo_apellido'])) {
            $cliente->setSegundoApellido($data['segundo_apellido']);
        }
        
        if (isset($data['genero'])) {
            $cliente->setGenero($data['genero']);
        }
        
        if (isset($data['direccion'])) {
            $cliente->setDireccion($data['direccion']);
        }
        
        if (isset($data['telefono'])) {
            $cliente->setTelefono((int)$data['telefono']);
        }
        
        return $cliente;
    }
}