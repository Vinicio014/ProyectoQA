<?php

namespace App\Entities\Interfaces;

interface DetalleVentaEntityInterface
{
    // Getters
    public function getIdDetalleVenta(): ?int;
    public function getIdVenta(): ?int;
    public function getIdProducto(): ?int;
    public function getCantidad(): ?int;
    public function getSubTotal(): ?float;

    // Setters
    public function setIdDetalleVenta(?int $idDetalleVenta): void;
    public function setIdVenta(?int $idVenta): void;
    public function setIdProducto(?int $idProducto): void;
    public function setCantidad(?int $cantidad): void;
    public function setSubTotal(?float $subTotal): void;

    // Métodos de utilidad
    public function toArray(): array;
    public static function fromArray(array $data): self;
    public function isValid(): bool;
    public function calcularSubTotal(float $precioUnitario): void;
    public function getPrecioUnitario(): float;
}