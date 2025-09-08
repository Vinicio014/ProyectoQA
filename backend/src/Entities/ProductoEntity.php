<?php

namespace App\Entities;

use App\Entities\Interfaces\ProductoEntityInterface;

class ProductoEntity implements ProductoEntityInterface
{
    private ?int $idProducto;
    private ?string $nombre;
    private ?string $marca;
    private ?string $descripcion;
    private ?int $idCategoria;
    private ?int $stock;
    private ?float $precioUnitario;
    private ?bool $esActivo;
    private ?\DateTime $fechaRegistro;

    public function __construct(
        ?int $idProducto = null,
        ?string $nombre = null,
        ?string $marca = null,
        ?string $descripcion = null,
        ?int $idCategoria = null,
        ?int $stock = 0,
        ?float $precioUnitario = 0.0,
        ?bool $esActivo = true,
        ?\DateTime $fechaRegistro = null
    ) {
        $this->idProducto = $idProducto;
        $this->nombre = $nombre;
        $this->marca = $marca;
        $this->descripcion = $descripcion;
        $this->idCategoria = $idCategoria;
        $this->stock = $stock ?? 0;
        $this->precioUnitario = $precioUnitario ?? 0.0;
        $this->esActivo = $esActivo ?? true;
        $this->fechaRegistro = $fechaRegistro ?? new \DateTime();
    }

    // Getters
    public function getIdProducto(): ?int
    {
        return $this->idProducto;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function getMarca(): ?string
    {
        return $this->marca;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function getIdCategoria(): ?int
    {
        return $this->idCategoria;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function getPrecioUnitario(): ?float
    {
        return $this->precioUnitario;
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
    public function setIdProducto(?int $idProducto): void
    {
        $this->idProducto = $idProducto;
    }

    public function setNombre(?string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function setMarca(?string $marca): void
    {
        $this->marca = $marca;
    }

    public function setDescripcion(?string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }

    public function setIdCategoria(?int $idCategoria): void
    {
        $this->idCategoria = $idCategoria;
    }

    public function setStock(?int $stock): void
    {
        $this->stock = $stock;
    }

    public function setPrecioUnitario(?float $precioUnitario): void
    {
        $this->precioUnitario = $precioUnitario;
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
            'idProducto' => $this->idProducto,
            'nombre' => $this->nombre,
            'marca' => $this->marca,
            'descripcion' => $this->descripcion,
            'idCategoria' => $this->idCategoria,
            'stock' => $this->stock,
            'precio_unitario' => $this->precioUnitario,
            'esActivo' => $this->esActivo,
            'fechaRegistro' => $this->fechaRegistro?->format('Y-m-d H:i:s')
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['idProducto'] ?? null,
            $data['nombre'] ?? null,
            $data['marca'] ?? null,
            $data['descripcion'] ?? null,
            $data['idCategoria'] ?? null,
            $data['stock'] ?? 0,
            $data['precio_unitario'] ?? 0.0,
            $data['esActivo'] ?? true,
            isset($data['fechaRegistro']) ? new \DateTime($data['fechaRegistro']) : null
        );
    }

    public function isValid(): bool
    {
        return !empty($this->nombre) && 
               !empty($this->marca) &&
               $this->precioUnitario >= 0 &&
               $this->stock >= 0 &&
               !empty($this->idCategoria);
    }

    public function tieneStock(): bool
    {
        return $this->stock > 0;
    }

    public function reducirStock(int $cantidad): bool
    {
        if ($this->stock >= $cantidad) {
            $this->stock -= $cantidad;
            return true;
        }
        return false;
    }

    public function aumentarStock(int $cantidad): void
    {
        $this->stock += $cantidad;
    }
}