<?php

namespace App\Infraestructura;

use App\Infraestructura\Interfaces\DatabaseInterface;
use PDO;
use PDOException;
use Exception;

class MySQLDatabase implements DatabaseInterface
{
    private ?PDO $connection = null;
    private array $config;
    private bool $isConnected = false;
    private string $logFile;

    public function __construct(array $config = null)
    {
        $this->config = $config ?? $this->loadConfig();
        $this->logFile = __DIR__ . '/../../log/database.log';
        
        // Configurar zona horaria
        if (isset($this->config['timezone'])) {
            date_default_timezone_set($this->config['timezone']);
        }
        
        $this->ensureLogDirectory();
    }

    /**
     * Cargar configuración de base de datos
     */
    private function loadConfig(): array
    {
        $configPath = __DIR__ . '/../../conf/database.php';
        
        if (!file_exists($configPath)) {
            throw new Exception("Archivo de configuración no encontrado: {$configPath}");
        }
        
        $config = require $configPath;
        
        if (!isset($config['mysql'])) {
            throw new Exception("Configuración MySQL no encontrada");
        }
        
        return $config;
    }

    /**
     * Asegurar que el directorio de logs existe
     */
    private function ensureLogDirectory(): void
    {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Obtener la conexión PDO
     */
    public function getConnection(): PDO
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
        
        return $this->connection;
    }

    /**
     * Conectar a la base de datos
     */
    public function connect(): void
    {
        try {
            $mysql = $this->config['mysql'];
            
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                $mysql['host'],
                $mysql['port'],
                $mysql['database'],
                $mysql['charset']
            );

            $this->connection = new PDO(
                $dsn,
                $mysql['username'],
                $mysql['password'],
                $mysql['options']
            );

            $this->isConnected = true;
            
            $this->log("Conexión establecida exitosamente a MySQL");
            
        } catch (PDOException $e) {
            $this->isConnected = false;
            $errorMsg = "Error de conexión a MySQL: " . $e->getMessage();
            $this->log($errorMsg, 'ERROR');
            throw new Exception($errorMsg, 0, $e);
        }
    }

    /**
     * Desconectar de la base de datos
     */
    public function disconnect(): void
    {
        if ($this->connection !== null) {
            $this->connection = null;
            $this->isConnected = false;
            $this->log("Conexión cerrada");
        }
    }

    /**
     * Verificar si hay conexión activa
     */
    public function isConnected(): bool
    {
        if (!$this->isConnected || $this->connection === null) {
            return false;
        }

        try {
            // Verificar que la conexión sigue activa
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            $this->isConnected = false;
            return false;
        }
    }

    /**
     * Comenzar una transacción
     */
    public function beginTransaction(): bool
    {
        try {
            $result = $this->getConnection()->beginTransaction();
            $this->log("Transacción iniciada");
            return $result;
        } catch (PDOException $e) {
            $this->log("Error al iniciar transacción: " . $e->getMessage(), 'ERROR');
            throw new Exception("Error al iniciar transacción", 0, $e);
        }
    }

    /**
     * Confirmar una transacción
     */
    public function commit(): bool
    {
        try {
            $result = $this->getConnection()->commit();
            $this->log("Transacción confirmada");
            return $result;
        } catch (PDOException $e) {
            $this->log("Error al confirmar transacción: " . $e->getMessage(), 'ERROR');
            throw new Exception("Error al confirmar transacción", 0, $e);
        }
    }

    /**
     * Cancelar una transacción
     */
    public function rollback(): bool
    {
        try {
            $result = $this->getConnection()->rollback();
            $this->log("Transacción cancelada");
            return $result;
        } catch (PDOException $e) {
            $this->log("Error al cancelar transacción: " . $e->getMessage(), 'ERROR');
            throw new Exception("Error al cancelar transacción", 0, $e);
        }
    }

    /**
     * Preparar una consulta
     */
    public function prepare(string $query): \PDOStatement
    {
        try {
            $statement = $this->getConnection()->prepare($query);
            
            if ($this->config['log_queries'] ?? false) {
                $this->log("Consulta preparada: " . $query);
            }
            
            return $statement;
        } catch (PDOException $e) {
            $this->log("Error al preparar consulta: " . $e->getMessage(), 'ERROR');
            throw new Exception("Error al preparar consulta", 0, $e);
        }
    }

    /**
     * Ejecutar una consulta directa
     */
    public function query(string $query): \PDOStatement
    {
        try {
            $statement = $this->getConnection()->query($query);
            
            if ($this->config['log_queries'] ?? false) {
                $this->log("Consulta ejecutada: " . $query);
            }
            
            return $statement;
        } catch (PDOException $e) {
            $this->log("Error al ejecutar consulta: " . $e->getMessage(), 'ERROR');
            throw new Exception("Error al ejecutar consulta", 0, $e);
        }
    }

    /**
     * Obtener el último ID insertado
     */
    public function lastInsertId(): string
    {
        return $this->getConnection()->lastInsertId();
    }

    /**
     * Obtener información de la conexión
     */
    public function getConnectionInfo(): array
    {
        if (!$this->isConnected()) {
            return ['status' => 'disconnected'];
        }

        try {
            $version = $this->connection->query('SELECT VERSION() as version')->fetch();
            $database = $this->config['mysql']['database'];
            $host = $this->config['mysql']['host'];
            $port = $this->config['mysql']['port'];

            return [
                'status' => 'connected',
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'version' => $version['version'] ?? 'unknown',
                'charset' => $this->config['mysql']['charset']
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Probar la conexión
     */
    public function testConnection(): array
    {
        try {
            $this->connect();
            return [
                'success' => true,
                'message' => 'Conexión exitosa',
                'info' => $this->getConnectionInfo()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'info' => null
            ];
        }
    }

    /**
     * Registrar eventos en el log
     */
    private function log(string $message, string $level = 'INFO'): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Limpiar logs antiguos
     */
    public function cleanOldLogs(int $days = 30): void
    {
        if (file_exists($this->logFile)) {
            $fileAge = time() - filemtime($this->logFile);
            $maxAge = $days * 24 * 60 * 60; // días a segundos
            
            if ($fileAge > $maxAge) {
                unlink($this->logFile);
                $this->log("Logs antiguos eliminados");
            }
        }
    }

    /**
     * Destructor para cerrar conexión automáticamente
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}