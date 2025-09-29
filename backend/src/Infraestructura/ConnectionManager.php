<?php

namespace App\Infraestructura;

use App\Infraestructura\Interfaces\DatabaseInterface;
use Exception;

/**
 * Gestor de conexiones de base de datos
 * Proporciona métodos de alto nivel para operaciones comunes
 */
class ConnectionManager
{
    private DatabaseInterface $database;
    private bool $autoCommit = true;

    public function __construct(?DatabaseInterface $database = null)
    {
        $this->database = $database ?? DatabaseFactory::getInstance();
    }

    /**
     * Obtener la base de datos
     */
    public function getDatabase(): DatabaseInterface
    {
        return $this->database;
    }

    /**
     * Ejecutar una consulta SELECT y obtener todos los resultados
     */
    public function select(string $query, array $params = []): array
    {
        try {
            $stmt = $this->database->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error en SELECT: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Ejecutar una consulta SELECT y obtener un solo resultado
     */
    public function selectOne(string $query, array $params = []): ?array
    {
        try {
            $stmt = $this->database->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (Exception $e) {
            throw new Exception("Error en SELECT ONE: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Ejecutar una consulta INSERT
     */
    public function insert(string $table, array $data): int
    {
        try {
            $fields = array_keys($data);
            $placeholders = array_map(fn($field) => ":{$field}", $fields);
            
            $query = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                $table,
                implode(', ', $fields),
                implode(', ', $placeholders)
            );

            $stmt = $this->database->prepare($query);
            $stmt->execute($data);
            
            return (int) $this->database->lastInsertId();
            
        } catch (Exception $e) {
            throw new Exception("Error en INSERT: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Ejecutar una consulta UPDATE
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        try {
            $fields = array_keys($data);
            $setParts = array_map(fn($field) => "{$field} = :{$field}", $fields);
            
            $query = sprintf(
                "UPDATE %s SET %s WHERE %s",
                $table,
                implode(', ', $setParts),
                $where
            );

            $params = array_merge($data, $whereParams);
            $stmt = $this->database->prepare($query);
            $stmt->execute($params);
            
            return $stmt->rowCount();
            
        } catch (Exception $e) {
            throw new Exception("Error en UPDATE: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Ejecutar una consulta DELETE
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        try {
            $query = sprintf("DELETE FROM %s WHERE %s", $table, $where);
            
            $stmt = $this->database->prepare($query);
            $stmt->execute($params);
            
            return $stmt->rowCount();
            
        } catch (Exception $e) {
            throw new Exception("Error en DELETE: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Ejecutar múltiples operaciones en una transacción
     */
    public function transaction(callable $callback): mixed
    {
        $this->database->beginTransaction();
        
        try {
            $result = $callback($this);
            $this->database->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->database->rollback();
            throw new Exception("Error en transacción: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Verificar si una tabla existe
     */
    public function tableExists(string $tableName): bool
    {
        try {
            $query = "SHOW TABLES LIKE :tableName";
            $result = $this->selectOne($query, ['tableName' => $tableName]);
            return $result !== null;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtener información de las columnas de una tabla
     */
    public function getTableColumns(string $tableName): array
    {
        try {
            $query = "DESCRIBE {$tableName}";
            return $this->select($query);
        } catch (Exception $e) {
            throw new Exception("Error al obtener columnas de la tabla: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Contar registros en una tabla
     */
    public function count(string $table, string $where = '1=1', array $params = []): int
    {
        try {
            $query = "SELECT COUNT(*) as total FROM {$table} WHERE {$where}";
            $result = $this->selectOne($query, $params);
            return (int) ($result['total'] ?? 0);
        } catch (Exception $e) {
            throw new Exception("Error en COUNT: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Obtener el estado de la conexión
     */
    public function getStatus(): array
    {
        return [
            'connected' => $this->database->isConnected(),
            'info' => $this->database->getConnectionInfo(),
            'auto_commit' => $this->autoCommit
        ];
    }

    /**
     * Limpiar caché de consultas (si está habilitado)
     */
    public function clearQueryCache(): bool
    {
        try {
            $this->database->query("RESET QUERY CACHE");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}