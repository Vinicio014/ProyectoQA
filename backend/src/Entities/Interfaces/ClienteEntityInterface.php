<?php

namespace App\Entities\Interfaces;

interface ClienteEntityInterface
{
    // Getters
    public function getIdCliente(): ?int;
    public function getPrimerNombre(): ?string;
    public function getSegundoNombre(): ?string;
    public function getPrimerApellido(): ?string;
    public function getSegundoApellido(): ?string;
    public function getGenero(): ?string;
    public function getDireccion(): ?string;
    public function getTelefono(): ?int;

    // Setters
    public function setIdCliente(?int $idCliente): void;
    public function setPrimerNombre(?string $primerNombre): void;
    public function setSegundoNombre(?string $segundoNombre): void;
    public function setPrimerApellido(?string $primerApellido): void;
    public function setSegundoApellido(?string $segundoApellido): void;
    public function setGenero(?string $genero): void;
    public function setDireccion(?string $direccion): void;
    public function setTelefono(?int $telefono): void;

    // Métodos de utilidad
    public function toArray(): array;
    public static function fromArray(array $data): self;
    public function isValid(): bool;
    public function getNombreCompleto(): string;
}