<?php

namespace App\Entities\Interfaces;

interface DetallePedidoEntityInterface
{
    // Getters
    public function getIdDetallePedido(): ?int;
    public function getCantidadProducto(): ?int;
    public function getDiseno(): ?string;
    public function getIdPedido(): ?int;
    public function getIdProducto(): ?int;

    // Setters
    public function setIdDetallePedido(?int $idDetallePedido): void;
    public function setCantidadProducto(?int $cantidadProducto): void;
    public function setDiseno(?string $diseno): void;
    public function setIdPedido(?int $idPedido): void;
    public function setIdProducto(?int $idProducto): void;

    // Métodos de utilidad
    public function toArray(): array;
    public static function fromArray(array $data): self;
    public function isValid(): bool;
    public function tieneDiseno(): bool;
    public function calcularTotal(float $precioUnitario): float;
}