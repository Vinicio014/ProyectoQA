<?php

namespace App\Infraestructura;

use App\Infraestructura\Interfaces\DatabaseInterface;
use Exception;

/**
 * Factory para crear instancias de base de datos
 * Implementa el patrón Singleton para garantizar una sola conexión
 */
class DatabaseFactory
{
    private static ?DatabaseInterface $instance = null;
    private static array $config = [];

    /**
     * Obtener instancia única de la base de datos
     */
    public static function getInstance(string $type = 'mysql', array $config = null): DatabaseInterface
    {
        if (self::$instance === null) {
            self::$config = $config ?? self::loadDefaultConfig();
            self::$instance = self::createDatabase($type);
        }

        return self::$instance;
    }

    /**
     * Crear instancia de base de datos según el tipo
     */
    private static function createDatabase(string $type): DatabaseInterface
    {
        switch (strtolower($type)) {
            case 'mysql':
                return new MySQLDatabase(self::$config);
                
            // Puedes agregar más tipos de base de datos aquí
            case 'sqlite':
                throw new Exception("SQLite no implementado aún");
                
            case 'postgresql':
                throw new Exception("PostgreSQL no implementado aún");
                
            default:
                throw new Exception("Tipo de base de datos no soportado: {$type}");
        }
    }

    /**
     * Cargar configuración por defecto
     */
    private static function loadDefaultConfig(): array
    {
        $configPath = __DIR__ . '/../../conf/database.php';
        
        if (!file_exists($configPath)) {
            throw new Exception("Archivo de configuración no encontrado");
        }
        
        return require $configPath;
    }

    /**
     * Resetear la instancia (útil para pruebas)
     */
    public static function reset(): void
    {
        if (self::$instance !== null) {
            self::$instance->disconnect();
            self::$instance = null;
        }
        self::$config = [];
    }

    /**
     * Obtener configuración actual
     */
    public static function getConfig(): array
    {
        return self::$config;
    }

    /**
     * Probar conexión sin crear instancia singleton
     */
    public static function testConnection(string $type = 'mysql', array $config = null): array
    {
        try {
            $tempConfig = $config ?? self::loadDefaultConfig();
            $tempDatabase = self::createDatabase($type);
            
            return $tempDatabase->testConnection();
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'info' => null
            ];
        }
    }
}