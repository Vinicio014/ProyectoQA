<?php

namespace App\Entities;

use App\Entities\Interfaces\PagoEntityInterface;

class PagoEntity implements PagoEntityInterface
{
    private ?int $idPago;
    private ?int $nrPedido;
    private ?float $montoPagado;
    private ?\DateTime $fechaPago;
    private ?string $metodoPago;
    private ?string $descripcion;
    private ?int $idVenta;

    public function __construct(
        ?int $idPago = null,
        ?int $nrPedido = null,
        ?float $montoPagado = 0.0,
        ?\DateTime $fechaPago = null,
        ?string $metodoPago = null,
        ?string $descripcion = null,
        ?int $idVenta = null
    ) {
        $this->idPago = $idPago;
        $this->nrPedido = $nrPedido;
        $this->montoPagado = $montoPagado ?? 0.0;
        $this->fechaPago = $fechaPago ?? new \DateTime();
        $this->metodoPago = $metodoPago;
        $this->descripcion = $descripcion;
        $this->idVenta = $idVenta;
    }

    // Getters
    public function getIdPago(): ?int
    {
        return $this->idPago;
    }

    public function getNrPedido(): ?int
    {
        return $this->nrPedido;
    }

    public function getMontoPagado(): ?float
    {
        return $this->montoPagado;
    }

    public function getFechaPago(): ?\DateTime
    {
        return $this->fechaPago;
    }

    public function getMetodoPago(): ?string
    {
        return $this->metodoPago;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function getIdVenta(): ?int
    {
        return $this->idVenta;
    }

    // Setters
    public function setIdPago(?int $idPago): void
    {
        $this->idPago = $idPago;
    }

    public function setNrPedido(?int $nrPedido): void
    {
        $this->nrPedido = $nrPedido;
    }

    public function setMontoPagado(?float $montoPagado): void
    {
        $this->montoPagado = $montoPagado;
    }

    public function setFechaPago(?\DateTime $fechaPago): void
    {
        $this->fechaPago = $fechaPago;
    }

    public function setMetodoPago(?string $metodoPago): void
    {
        $this->metodoPago = $metodoPago;
    }

    public function setDescripcion(?string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }

    public function setIdVenta(?int $idVenta): void
    {
        $this->idVenta = $idVenta;
    }

    // Métodos de utilidad
    public function toArray(): array
    {
        return [
            'idPago' => $this->idPago,
            'nr_pedido' => $this->nrPedido,
            'monto_pagado' => $this->montoPagado,
            'fecha_pago' => $this->fechaPago?->format('Y-m-d H:i:s'),
            'metodo_pago' => $this->metodoPago,
            'descripcion' => $this->descripcion,
            'id_venta' => $this->idVenta
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['idPago'] ?? null,
            $data['nr_pedido'] ?? null,
            $data['monto_pagado'] ?? 0.0,
            isset($data['fecha_pago']) ? new \DateTime($data['fecha_pago']) : null,
            $data['metodo_pago'] ?? null,
            $data['descripcion'] ?? null,
            $data['id_venta'] ?? null
        );
    }

    public function isValid(): bool
    {
        $metodosValidos = ['EFECTIVO', 'TARJETA', 'TRANSFERENCIA', 'CHEQUE'];
        
        return $this->montoPagado > 0 &&
               !empty($this->metodoPago) &&
               in_array(strtoupper($this->metodoPago), $metodosValidos);
    }

    public function esEfectivo(): bool
    {
        return strtoupper($this->metodoPago) === 'EFECTIVO';
    }

    public function esTarjeta(): bool
    {
        return strtoupper($this->metodoPago) === 'TARJETA';
    }
}