<?php

namespace App\Entities;

use App\Entities\Interfaces\DetalleVentaEntityInterface;

class DetalleVentaEntity implements DetalleVentaEntityInterface
{
    private ?int $idDetalleVenta;
    private ?int $idVenta;
    private ?int $idProducto;
    private ?int $cantidad;
    private ?float $subTotal;

    public function __construct(
        ?int $idDetalleVenta = null,
        ?int $idVenta = null,
        ?int $idProducto = null,
        ?int $cantidad = 0,
        ?float $subTotal = 0.0
    ) {
        $this->idDetalleVenta = $idDetalleVenta;
        $this->idVenta = $idVenta;
        $this->idProducto = $idProducto;
        $this->cantidad = $cantidad ?? 0;
        $this->subTotal = $subTotal ?? 0.0;
    }

    // Getters
    public function getIdDetalleVenta(): ?int
    {
        return $this->idDetalleVenta;
    }

    public function getIdVenta(): ?int
    {
        return $this->idVenta;
    }

    public function getIdProducto(): ?int
    {
        return $this->idProducto;
    }

    public function getCantidad(): ?int
    {
        return $this->cantidad;
    }

    public function getSubTotal(): ?float
    {
        return $this->subTotal;
    }

    // Setters
    public function setIdDetalleVenta(?int $idDetalleVenta): void
    {
        $this->idDetalleVenta = $idDetalleVenta;
    }

    public function setIdVenta(?int $idVenta): void
    {
        $this->idVenta = $idVenta;
    }

    public function setIdProducto(?int $idProducto): void
    {
        $this->idProducto = $idProducto;
    }

    public function setCantidad(?int $cantidad): void
    {
        $this->cantidad = $cantidad;
    }

    public function setSubTotal(?float $subTotal): void
    {
        $this->subTotal = $subTotal;
    }

    // Métodos de utilidad
    public function toArray(): array
    {
        return [
            'idDetalleVenta' => $this->idDetalleVenta,
            'idVenta' => $this->idVenta,
            'idProducto' => $this->idProducto,
            'cantidad' => $this->cantidad,
            'sub_total' => $this->subTotal
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['idDetalleVenta'] ?? null,
            $data['idVenta'] ?? null,
            $data['idProducto'] ?? null,
            $data['cantidad'] ?? 0,
            $data['sub_total'] ?? 0.0
        );
    }

    public function isValid(): bool
    {
        return !empty($this->idVenta) && 
               !empty($this->idProducto) &&
               $this->cantidad > 0 &&
               $this->subTotal >= 0;
    }

    public function calcularSubTotal(float $precioUnitario): void
    {
        $this->subTotal = $this->cantidad * $precioUnitario;
    }

    public function getPrecioUnitario(): float
    {
        if ($this->cantidad > 0) {
            return $this->subTotal / $this->cantidad;
        }
        return 0.0;
    }
}