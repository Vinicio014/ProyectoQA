<?php

namespace App\Entities\Interfaces;

interface PagoEntityInterface
{
    // Getters
    public function getIdPago(): ?int;
    public function getNrPedido(): ?int;
    public function getMontoPagado(): ?float;
    public function getFechaPago(): ?\DateTime;
    public function getMetodoPago(): ?string;
    public function getDescripcion(): ?string;
    public function getIdVenta(): ?int;

    // Setters
    public function setIdPago(?int $idPago): void;
    public function setNrPedido(?int $nrPedido): void;
    public function setMontoPagado(?float $montoPagado): void;
    public function setFechaPago(?\DateTime $fechaPago): void;
    public function setMetodoPago(?string $metodoPago): void;
    public function setDescripcion(?string $descripcion): void;
    public function setIdVenta(?int $idVenta): void;

    // Métodos de utilidad
    public function toArray(): array;
    public static function fromArray(array $data): self;
    public function isValid(): bool;
    public function esEfectivo(): bool;
    public function esTarjeta(): bool;
}