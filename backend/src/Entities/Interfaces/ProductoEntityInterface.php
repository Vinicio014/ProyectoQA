<?php

namespace App\Entities\Interfaces;

interface ProductoEntityInterface
{
    // Getters
    public function getIdProducto(): ?int;
    public function getNombre(): ?string;
    public function getMarca(): ?string;
    public function getDescripcion(): ?string;
    public function getIdCategoria(): ?int;
    public function getStock(): ?int;
    public function getPrecioUnitario(): ?float;
    public function getEsActivo(): ?bool;
    public function getFechaRegistro(): ?\DateTime;

    // Setters
    public function setIdProducto(?int $idProducto): void;
    public function setNombre(?string $nombre): void;
    public function setMarca(?string $marca): void;
    public function setDescripcion(?string $descripcion): void;
    public function setIdCategoria(?int $idCategoria): void;
    public function setStock(?int $stock): void;
    public function setPrecioUnitario(?float $precioUnitario): void;
    public function setEsActivo(?bool $esActivo): void;
    public function setFechaRegistro(?\DateTime $fechaRegistro): void;

    // Métodos de utilidad
    public function toArray(): array;
    public static function fromArray(array $data): self;
    public function isValid(): bool;
    public function tieneStock(): bool;
    public function reducirStock(int $cantidad): bool;
    public function aumentarStock(int $cantidad): void;
}