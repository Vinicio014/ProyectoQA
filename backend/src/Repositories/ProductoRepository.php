<?php

namespace App\Repositories;

use App\Entities\ProductoEntity;
use App\Infraestructura\ConnectionManager;
use Exception;

class ProductoRepository
{
    private ConnectionManager $connectionManager;
    private string $table = 'producto';

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    /**
     * Crear un nuevo producto
     */
    public function create(ProductoEntity $producto): ?ProductoEntity
    {
        try {
            $sql = "INSERT INTO {$this->table} (codigo, nombre, descripcion, precio, stock, 
                    categoria_id, activo, fecha_creacion) 
                    VALUES (:codigo, :nombre, :descripcion, :precio, :stock, 
                    :categoria_id, :activo, NOW())";
            
            $params = [
                'codigo' => $producto->getCodigo(),
                'nombre' => $producto->getNombre(),
                'descripcion' => $producto->getDescripcion(),
                'precio' => $producto->getPrecio(),
                'stock' => $producto->getStock(),
                'categoria_id' => $producto->getCategoriaId(),
                'activo' => $producto->isActivo() ? 1 : 0
            ];

            $id = $this->connectionManager->insert($sql, $params);
            
            if ($id) {
                return $this->findById($id);
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error creating producto: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener producto por ID
     */
    public function findById(int $id): ?ProductoEntity
    {
        try {
            $sql = "SELECT p.*, c.nombre as categoria_nombre 
                    FROM {$this->table} p 
                    LEFT JOIN categoria c ON p.categoria_id = c.id 
                    WHERE p.id = :id";
            $result = $this->connectionManager->selectOne($sql, ['id' => $id]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding producto by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener producto por código
     */
    public function findByCode(string $codigo): ?ProductoEntity
    {
        try {
            $sql = "SELECT p.*, c.nombre as categoria_nombre 
                    FROM {$this->table} p 
                    LEFT JOIN categoria c ON p.categoria_id = c.id 
                    WHERE p.codigo = :codigo";
            $result = $this->connectionManager->selectOne($sql, ['codigo' => $codigo]);
            
            return $result ? $this->mapToEntity($result) : null;
        } catch (Exception $e) {
            error_log("Error finding producto by code: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener todos los productos
     */
    public function findAll(): array
    {
        try {
            $sql = "SELECT p.*, c.nombre as categoria_nombre 
                    FROM {$this->table} p 
                    LEFT JOIN categoria c ON p.categoria_id = c.id 
                    ORDER BY p.nombre";
            $results = $this->connectionManager->selectAll($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding all productos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener productos por categoría
     */
    public function findByCategory(int $categoriaId): array
    {
        try {
            $sql = "SELECT p.*, c.nombre as categoria_nombre 
                    FROM {$this->table} p 
                    LEFT JOIN categoria c ON p.categoria_id = c.id 
                    WHERE p.categoria_id = :categoria_id 
                    ORDER BY p.nombre";
            $results = $this->connectionManager->selectAll($sql, ['categoria_id' => $categoriaId]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding productos by category: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar productos por nombre
     */
    public function searchByName(string $name): array
    {
        try {
            $sql = "SELECT p.*, c.nombre as categoria_nombre 
                    FROM {$this->table} p 
                    LEFT JOIN categoria c ON p.categoria_id = c.id 
                    WHERE p.nombre LIKE :name OR p.descripcion LIKE :name
                    ORDER BY p.nombre";
            
            $searchTerm = "%{$name}%";
            $results = $this->connectionManager->selectAll($sql, ['name' => $searchTerm]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error searching productos by name: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener productos activos
     */
    public function findActive(): array
    {
        try {
            $sql = "SELECT p.*, c.nombre as categoria_nombre 
                    FROM {$this->table} p 
                    LEFT JOIN categoria c ON p.categoria_id = c.id 
                    WHERE p.activo = 1 
                    ORDER BY p.nombre";
            $results = $this->connectionManager->selectAll($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding active productos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener productos con stock bajo
     */
    public function findLowStock(int $threshold = 10): array
    {
        try {
            $sql = "SELECT p.*, c.nombre as categoria_nombre 
                    FROM {$this->table} p 
                    LEFT JOIN categoria c ON p.categoria_id = c.id 
                    WHERE p.stock <= :threshold AND p.activo = 1
                    ORDER BY p.stock ASC";
            $results = $this->connectionManager->selectAll($sql, ['threshold' => $threshold]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding low stock productos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener productos sin stock
     */
    public function findOutOfStock(): array
    {
        try {
            $sql = "SELECT p.*, c.nombre as categoria_nombre 
                    FROM {$this->table} p 
                    LEFT JOIN categoria c ON p.categoria_id = c.id 
                    WHERE p.stock = 0 AND p.activo = 1
                    ORDER BY p.nombre";
            $results = $this->connectionManager->selectAll($sql);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding out of stock productos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar producto
     */
    public function update(ProductoEntity $producto): bool
    {
        try {
            $sql = "UPDATE {$this->table} 
                    SET codigo = :codigo, nombre = :nombre, descripcion = :descripcion, 
                        precio = :precio, stock = :stock, categoria_id = :categoria_id,
                        activo = :activo, fecha_modificacion = NOW()
                    WHERE id = :id";
            
            $params = [
                'id' => $producto->getId(),
                'codigo' => $producto->getCodigo(),
                'nombre' => $producto->getNombre(),
                'descripcion' => $producto->getDescripcion(),
                'precio' => $producto->getPrecio(),
                'stock' => $producto->getStock(),
                'categoria_id' => $producto->getCategoriaId(),
                'activo' => $producto->isActivo() ? 1 : 0
            ];

            return $this->connectionManager->update($sql, $params);
        } catch (Exception $e) {
            error_log("Error updating producto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar stock
     */
    public function updateStock(int $id, int $newStock): bool
    {
        try {
            $sql = "UPDATE {$this->table} SET stock = :stock, fecha_modificacion = NOW() WHERE id = :id";
            return $this->connectionManager->update($sql, ['id' => $id, 'stock' => $newStock]);
        } catch (Exception $e) {
            error_log("Error updating stock: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reducir stock
     */
    public function reduceStock(int $id, int $quantity): bool
    {
        try {
            $sql = "UPDATE {$this->table} 
                    SET stock = stock - :quantity, fecha_modificacion = NOW() 
                    WHERE id = :id AND stock >= :quantity";
            
            $params = ['id' => $id, 'quantity' => $quantity];
            return $this->connectionManager->update($sql, $params);
        } catch (Exception $e) {
            error_log("Error reducing stock: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Aumentar stock
     */
    public function increaseStock(int $id, int $quantity): bool
    {
        try {
            $sql = "UPDATE {$this->table} 
                    SET stock = stock + :quantity, fecha_modificacion = NOW() 
                    WHERE id = :id";
            
            $params = ['id' => $id, 'quantity' => $quantity];
            return $this->connectionManager->update($sql, $params);
        } catch (Exception $e) {
            error_log("Error increasing stock: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar producto (soft delete)
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "UPDATE {$this->table} SET activo = 0, fecha_modificacion = NOW() WHERE id = :id";
            return $this->connectionManager->update($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log("Error deleting producto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si existe código de producto
     */
    public function existsByCode(string $codigo): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE codigo = :codigo";
            $result = $this->connectionManager->selectOne($sql, ['codigo' => $codigo]);
            
            return ($result['count'] ?? 0) > 0;
        } catch (Exception $e) {
            error_log("Error checking codigo existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener productos más vendidos
     */
    public function getMostSold(int $limit = 10): array
    {
        try {
            $sql = "SELECT p.*, c.nombre as categoria_nombre, 
                           SUM(dv.cantidad) as total_vendido
                    FROM {$this->table} p
                    LEFT JOIN categoria c ON p.categoria_id = c.id
                    INNER JOIN detalleventa dv ON p.id = dv.producto_id
                    WHERE p.activo = 1
                    GROUP BY p.id
                    ORDER BY total_vendido DESC
                    LIMIT :limit";
                    
            $results = $this->connectionManager->selectAll($sql, ['limit' => $limit]);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error getting most sold products: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Filtrar productos por rango de precio
     */
    public function findByPriceRange(float $minPrice, float $maxPrice): array
    {
        try {
            $sql = "SELECT p.*, c.nombre as categoria_nombre 
                    FROM {$this->table} p 
                    LEFT JOIN categoria c ON p.categoria_id = c.id 
                    WHERE p.precio BETWEEN :min_price AND :max_price AND p.activo = 1
                    ORDER BY p.precio ASC";
                    
            $params = ['min_price' => $minPrice, 'max_price' => $maxPrice];
            $results = $this->connectionManager->selectAll($sql, $params);
            
            return array_map([$this, 'mapToEntity'], $results);
        } catch (Exception $e) {
            error_log("Error finding products by price range: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mapear datos de BD a entidad
     */
    private function mapToEntity(array $data): ProductoEntity
    {
        $producto = new ProductoEntity();
        $producto->setId($data['id']);
        $producto->setCodigo($data['codigo']);
        $producto->setNombre($data['nombre']);
        $producto->setDescripcion($data['descripcion']);
        $producto->setPrecio($data['precio']);
        $producto->setStock($data['stock']);
        $producto->setCategoriaId($data['categoria_id']);
        $producto->setActivo((bool)$data['activo']);
        $producto->setFechaCreacion($data['fecha_creacion']);
        $producto->setFechaModificacion($data['fecha_modificacion']);
        
        return $producto;
    }
}