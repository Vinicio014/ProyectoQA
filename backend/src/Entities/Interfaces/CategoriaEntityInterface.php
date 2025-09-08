<?php

namespace App\Entities\Interfaces;

interface CategoriaEntityInterface
{
    // Getters
    public function getIdCategoria(): ?int;
    public function getDescripcion(): ?string;
    public function getEsActivo(): ?bool;
    public function getFechaRegistro(): ?\DateTime;

    // Setters
    public function setIdCategoria(?int $idCategoria): void;
    public function setDescripcion(?string $descripcion): void;
    public function setEsActivo(?bool $esActivo): void;
    public function setFechaRegistro(?\DateTime $fechaRegistro): void;

    // Métodos de utilidad
    public function toArray(): array;
    public static function fromArray(array $data): self;
    public function isValid(): bool;
}