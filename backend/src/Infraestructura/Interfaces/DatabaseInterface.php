<?php

namespace App\Infraestructura\Interfaces;

use PDO;

interface DatabaseInterface
{
    /**
     * Obtener la conexión PDO
     */
    public function getConnection(): PDO;

    /**
     * Conectar a la base de datos
     */
    public function connect(): void;

    /**
     * Desconectar de la base de datos
     */
    public function disconnect(): void;

    /**
     * Verificar si hay conexión activa
     */
    public function isConnected(): bool;

    /**
     * Comenzar una transacción
     */
    public function beginTransaction(): bool;

    /**
     * Confirmar una transacción
     */
    public function commit(): bool;

    /**
     * Cancelar una transacción
     */
    public function rollback(): bool;

    /**
     * Preparar una consulta
     */
    public function prepare(string $query): \PDOStatement;

    /**
     * Ejecutar una consulta directa
     */
    public function query(string $query): \PDOStatement;

    /**
     * Obtener el último ID insertado
     */
    public function lastInsertId(): string;

    /**
     * Obtener información de la conexión
     */
    public function getConnectionInfo(): array;
}