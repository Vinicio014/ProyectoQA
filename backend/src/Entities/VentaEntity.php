<?php

namespace App\Entities;

use App\Entities\Interfaces\VentaEntityInterface;

class VentaEntity implements VentaEntityInterface
{
    private ?int $idVenta;
    private ?\DateTime $fechaRegistro;
    private ?int $idUsuario;
    private ?int $idCliente;
    private ?float $total;
    private ?float $impuestosTotal;

    public function __construct(
        ?int $idVenta = null,
        ?\DateTime $fechaRegistro = null,
        ?int $idUsuario = null,
        ?int $idCliente = null,
        ?float $total = 0.0,
        ?float $impuestosTotal = 0.0
    ) {
        $this->idVenta = $idVenta;
        $this->fechaRegistro = $fechaRegistro ?? new \DateTime();
        $this->idUsuario = $idUsuario;
        $this->idCliente = $idCliente;
        $this->total = $total ?? 0.0;
        $this->impuestosTotal = $impuestosTotal ?? 0.0;
    }

    // Getters
    public function getIdVenta(): ?int
    {
        return $this->idVenta;
    }

    public function getFechaRegistro(): ?\DateTime
    {
        return $this->fechaRegistro;
    }

    public function getIdUsuario(): ?int
    {
        return $this->idUsuario;
    }

    public function getIdCliente(): ?int
    {
        return $this->idCliente;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function getImpuestosTotal(): ?float
    {
        return $this->impuestosTotal;
    }

    // Setters
    public function setIdVenta(?int $idVenta): void
    {
        $this->idVenta = $idVenta;
    }

    public function setFechaRegistro(?\DateTime $fechaRegistro): void
    {
        $this->fechaRegistro = $fechaRegistro;
    }

    public function setIdUsuario(?int $idUsuario): void
    {
        $this->idUsuario = $idUsuario;
    }

    public function setIdCliente(?int $idCliente): void
    {
        $this->idCliente = $idCliente;
    }

    public function setTotal(?float $total): void
    {
        $this->total = $total;
    }

    public function setImpuestosTotal(?float $impuestosTotal): void
    {
        $this->impuestosTotal = $impuestosTotal;
    }

    // Métodos de utilidad
    public function toArray(): array
    {
        return [
            'idVenta' => $this->idVenta,
            'fechaRegistro' => $this->fechaRegistro?->format('Y-m-d H:i:s'),
            'idUsuario' => $this->idUsuario,
            'idCliente' => $this->idCliente,
            'Total' => $this->total,
            'impuestosTotal' => $this->impuestosTotal
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['idVenta'] ?? null,
            isset($data['fechaRegistro']) ? new \DateTime($data['fechaRegistro']) : null,
            $data['idUsuario'] ?? null,
            $data['idCliente'] ?? null,
            $data['Total'] ?? 0.0,
            $data['impuestosTotal'] ?? 0.0
        );
    }

    public function isValid(): bool
    {
        return !empty($this->idUsuario) && 
               !empty($this->idCliente) &&
               $this->total >= 0;
    }

    public function calcularSubtotal(): float
    {
        return $this->total - $this->impuestosTotal;
    }

    public function calcularImpuestos(float $porcentajeImpuesto = 0.12): void
    {
        $subtotal = $this->total / (1 + $porcentajeImpuesto);
        $this->impuestosTotal = $this->total - $subtotal;
    }
}