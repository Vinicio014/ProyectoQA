<?php

namespace App\Entities\Interfaces;

interface VentaEntityInterface
{
    // Getters
    public function getIdVenta(): ?int;
    public function getFechaRegistro(): ?\DateTime;
    public function getIdUsuario(): ?int;
    public function getIdCliente(): ?int;
    public function getTotal(): ?float;
    public function getImpuestosTotal(): ?float;

    // Setters
    public function setIdVenta(?int $idVenta): void;
    public function setFechaRegistro(?\DateTime $fechaRegistro): void;
    public function setIdUsuario(?int $idUsuario): void;
    public function setIdCliente(?int $idCliente): void;
    public function setTotal(?float $total): void;
    public function setImpuestosTotal(?float $impuestosTotal): void;

    // Métodos de utilidad
    public function toArray(): array;
    public static function fromArray(array $data): self;
    public function isValid(): bool;
    public function calcularSubtotal(): float;
    public function calcularImpuestos(float $porcentajeImpuesto = 0.12): void;
}