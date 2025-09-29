<?php

namespace App\Entities;

use App\Entities\Interfaces\CategoriaEntityInterface;

class CategoriaEntity implements CategoriaEntityInterface
{
    private ?int $idCategoria;
    private ?string $descripcion;
    private ?bool $esActivo;
    private ?\DateTime $fechaRegistro;

    public function __construct(
        ?int $idCategoria = null,
        ?string $descripcion = null,
        ?bool $esActivo = true,
        ?\DateTime $fechaRegistro = null
    ) {
        $this->idCategoria = $idCategoria;
        $this->descripcion = $descripcion;
        $this->esActivo = $esActivo ?? true;
        $this->fechaRegistro = $fechaRegistro ?? new \DateTime();
    }

    // Getters
    public function getIdCategoria(): ?int
    {
        return $this->idCategoria;
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
    public function setIdCategoria(?int $idCategoria): void
    {
        $this->idCategoria = $idCategoria;
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
// Agregar después del setFechaRegistro existente
public function setFechaRegistroFromString(?string $fecha): void
{
    if ($fecha !== null) {
        try {
            $this->fechaRegistro = new \DateTime($fecha);
        } catch (\Exception $e) {
            $this->fechaRegistro = new \DateTime();
        }
    } else {
        $this->fechaRegistro = null;
    }
}



    // Métodos de utilidad
    public function toArray(): array
    {
        return [
            'idCategoria' => $this->idCategoria,
            'descripcion' => $this->descripcion,
            'esActivo' => $this->esActivo,
            'fechaRegistro' => $this->fechaRegistro?->format('Y-m-d H:i:s')
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['idCategoria'] ?? null,
            $data['descripcion'] ?? null,
            $data['esActivo'] ?? true,
            isset($data['fechaRegistro']) ? new \DateTime($data['fechaRegistro']) : null
        );
    }

    public function isValid(): bool
    {
        return !empty($this->descripcion);
    }
}