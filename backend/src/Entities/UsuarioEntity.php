<?php

namespace App\Entities;

use App\Entities\Interfaces\UsuarioEntityInterface;

class UsuarioEntity implements UsuarioEntityInterface
{
    private ?int $idUsuario;
    private ?string $nombre;
    private ?string $correo;
    private ?int $idRol;
    private ?string $contrasenia;
    private ?bool $esActivo;
    private ?\DateTime $fechaRegistro;

    public function __construct(
        ?int $idUsuario = null,
        ?string $nombre = null,
        ?string $correo = null,
        ?int $idRol = null,
        ?string $contrasenia = null,
        ?bool $esActivo = true,
        ?\DateTime $fechaRegistro = null
    ) {
        $this->idUsuario = $idUsuario;
        $this->nombre = $nombre;
        $this->correo = $correo;
        $this->idRol = $idRol;
        $this->contrasenia = $contrasenia;
        $this->esActivo = $esActivo ?? true;
        $this->fechaRegistro = $fechaRegistro ?? new \DateTime();
    }

    // Getters
    public function getIdUsuario(): ?int
    {
        return $this->idUsuario;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function getCorreo(): ?string
    {
        return $this->correo;
    }

    public function getIdRol(): ?int
    {
        return $this->idRol;
    }

    public function getContrasenia(): ?string
    {
        return $this->contrasenia;
    }

    public function getEsActivo(): ?bool
    {
        return $this->esActivo;
    }

    public function getFechaRegistro(): ?\DateTime
    {
        return $this->fechaRegistro;
    }

    // Setters
    public function setIdUsuario(?int $idUsuario): void
    {
        $this->idUsuario = $idUsuario;
    }

    public function setNombre(?string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function setCorreo(?string $correo): void
    {
        $this->correo = $correo;
    }

    public function setIdRol(?int $idRol): void
    {
        $this->idRol = $idRol;
    }

    public function setContrasenia(?string $contrasenia): void
    {
        $this->contrasenia = $contrasenia;
    }

    public function setEsActivo(?bool $esActivo): void
    {
        $this->esActivo = $esActivo;
    }

    public function setFechaRegistro(?\DateTime $fechaRegistro): void
    {
        $this->fechaRegistro = $fechaRegistro;
    }

    // Métodos de utilidad
    public function toArray(): array
    {
        return [
            'idUsuario' => $this->idUsuario,
            'nombre' => $this->nombre,
            'correo' => $this->correo,
            'idRol' => $this->idRol,
            'contrasenia' => $this->contrasenia,
            'esActivo' => $this->esActivo,
            'fechaRegistro' => $this->fechaRegistro?->format('Y-m-d H:i:s')
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['idUsuario'] ?? null,
            $data['nombre'] ?? null,
            $data['correo'] ?? null,
            $data['idRol'] ?? null,
            $data['contrasenia'] ?? null,
            $data['esActivo'] ?? true,
            isset($data['fechaRegistro']) ? new \DateTime($data['fechaRegistro']) : null
        );
    }

    public function isValid(): bool
    {
        return !empty($this->nombre) && 
               !empty($this->correo) && 
               filter_var($this->correo, FILTER_VALIDATE_EMAIL) !== false &&
               !empty($this->contrasenia) &&
               !empty($this->idRol);
    }

    public function hashContrasenia(): void
    {
        if (!empty($this->contrasenia)) {
            $this->contrasenia = password_hash($this->contrasenia, PASSWORD_DEFAULT);
        }
    }

    public function verificarContrasenia(string $contrasenia): bool
    {
        return password_verify($contrasenia, $this->contrasenia);
    }
}