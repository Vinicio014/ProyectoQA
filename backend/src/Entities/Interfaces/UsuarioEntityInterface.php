<?php

namespace App\Entities\Interfaces;

interface UsuarioEntityInterface
{
    // Getters
    public function getIdUsuario(): ?int;
    public function getNombre(): ?string;
    public function getCorreo(): ?string;
    public function getIdRol(): ?int;
    public function getContrasenia(): ?string;
    public function getEsActivo(): ?bool;
    public function getFechaRegistro(): ?\DateTime;

    // Setters
    public function setIdUsuario(?int $idUsuario): void;
    public function setNombre(?string $nombre): void;
    public function setCorreo(?string $correo): void;
    public function setIdRol(?int $idRol): void;
    public function setContrasenia(?string $contrasenia): void;
    public function setEsActivo(?bool $esActivo): void;
    public function setFechaRegistro(?\DateTime $fechaRegistro): void;

    // Métodos de utilidad
    public function toArray(): array;
    public static function fromArray(array $data): self;
    public function isValid(): bool;
    public function hashContrasenia(): void;
    public function verificarContrasenia(string $contrasenia): bool;
}