<?php

namespace App\Entities\Interfaces;

interface DetalleUniformeEntityInterface
{
    // Getters
    public function getIdDetalleUniforme(): ?int;
    public function getIdDetallePedido(): ?int;
    public function getTalla(): ?string;
    public function getGenero(): ?string;
    public function getNombreCamisola(): ?string;
    public function getNumeroCamisola(): ?string;
    public function getNombreAbajo(): ?string;
    public function getCantidad(): ?int;
    public function getConMedidas(): ?float;

    // Setters
    public function setIdDetalleUniforme(?int $idDetalleUniforme): void;
    public function setIdDetallePedido(?int $idDetallePedido): void;
    public function setTalla(?string $talla): void;
    public function setGenero(?string $genero): void;
    public function setNombreCamisola(?string $nombreCamisola): void;
    public function setNumeroCamisola(?string $numeroCamisola): void;
    public function setNombreAbajo(?string $nombreAbajo): void;
    public function setCantidad(?int $cantidad): void;
    public function setConMedidas(?float $conMedidas): void;

    // Métodos de utilidad
    public function toArray(): array;
    public static function fromArray(array $data): self;
    public function isValid(): bool;
    public function tieneCamisola(): bool;
    public function tieneAbajo(): bool;
    public function requiereMedidas(): bool;
    public function getEspecificacionesCompletas(): string;
}