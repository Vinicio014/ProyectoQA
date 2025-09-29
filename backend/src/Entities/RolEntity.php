<?php

namespace App\Entities;

use App\Entities\Interfaces\RolEntityInterface;

class RolEntity implements RolEntityInterface
{
    private ?int $idRol;
    private ?string $descripcion;
    private ?bool $esActivo;
    private ?\DateTime $fechaRegistro;

    public function __construct(
        ?int $idRol = null,
        ?string $descripcion = null,
        ?bool $esActivo = true,
        ?\DateTime $fechaRegistro = null
    ) {
        $this->idRol = $idRol;
        $this->descripcion = $descripcion;
        $this->esActivo = $esActivo ?? true;
        $this->fechaRegistro = $fechaRegistro ?? new \DateTime();
    }

    // Getters
    public function getIdRol(): ?int
    {
        return $this->idRol;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
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
    public function setIdRol(?int $idRol): void
    {
        $this->idRol = $idRol;
    }

    public function setDescripcion(?string $descripcion): void
    {
        $this->descripcion = $descripcion;
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
            'idRol' => $this->idRol,
            'descripcion' => $this->descripcion,
            'esActivo' => $this->esActivo,
            'fechaRegistro' => $this->fechaRegistro?->format('Y-m-d H:i:s')
        ];
    }

    public static function fromArray(array $data): self
    {
        $fechaRegistro = null;
        if (isset($data['fechaRegistro'])) {
            try {
                $fechaRegistro = new \DateTime($data['fechaRegistro']);
            } catch (\Exception $e) {
                $fechaRegistro = new \DateTime();
            }
        }

        return new self(
            $data['idRol'] ?? null,
            $data['descripcion'] ?? null,
            $data['esActivo'] ?? true,
            $fechaRegistro
        );
    }

    public function isValid(): bool
    {
        return !empty($this->descripcion);
    }
}