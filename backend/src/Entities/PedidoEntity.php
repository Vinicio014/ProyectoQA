<?php

namespace App\Entities;

use App\Entities\Interfaces\PedidoEntityInterface;

class PedidoEntity implements PedidoEntityInterface
{
    private ?int $idPedido;
    private ?\DateTime $fechaPedido;
    private ?string $estadoPedido;
    private ?int $idCliente;
    private ?\DateTime $fechaEntrega;
    private ?float $montoPagado;
    private ?string $estadoPago;

    public function __construct(
        ?int $idPedido = null,
        ?\DateTime $fechaPedido = null,
        ?string $estadoPedido = 'PENDIENTE',
        ?int $idCliente = null,
        ?\DateTime $fechaEntrega = null,
        ?float $montoPagado = 0.0,
        ?string $estadoPago = 'PENDIENTE'
    ) {
        $this->idPedido = $idPedido;
        $this->fechaPedido = $fechaPedido ?? new \DateTime();
        $this->estadoPedido = $estadoPedido ?? 'PENDIENTE';
        $this->idCliente = $idCliente;
        $this->fechaEntrega = $fechaEntrega;
        $this->montoPagado = $montoPagado ?? 0.0;
        $this->estadoPago = $estadoPago ?? 'PENDIENTE';
    }

    // Getters
    public function getIdPedido(): ?int
    {
        return $this->idPedido;
    }

    public function getFechaPedido(): ?\DateTime
    {
        return $this->fechaPedido;
    }

    public function getEstadoPedido(): ?string
    {
        return $this->estadoPedido;
    }

    public function getIdCliente(): ?int
    {
        return $this->idCliente;
    }

    public function getFechaEntrega(): ?\DateTime
    {
        return $this->fechaEntrega;
    }

    public function getMontoPagado(): ?float
    {
        return $this->montoPagado;
    }

    public function getEstadoPago(): ?string
    {
        return $this->estadoPago;
    }

    // Setters
    public function setIdPedido(?int $idPedido): void
    {
        $this->idPedido = $idPedido;
    }

    public function setFechaPedido(?\DateTime $fechaPedido): void
    {
        $this->fechaPedido = $fechaPedido;
    }

    public function setEstadoPedido(?string $estadoPedido): void
    {
        $this->estadoPedido = $estadoPedido;
    }

    public function setIdCliente(?int $idCliente): void
    {
        $this->idCliente = $idCliente;
    }

    public function setFechaEntrega(?\DateTime $fechaEntrega): void
    {
        $this->fechaEntrega = $fechaEntrega;
    }

    public function setMontoPagado(?float $montoPagado): void
    {
        $this->montoPagado = $montoPagado;
    }

    public function setEstadoPago(?string $estadoPago): void
    {
        $this->estadoPago = $estadoPago;
    }

    // Métodos de utilidad
    public function toArray(): array
    {
        return [
            'idPedido' => $this->idPedido,
            'fecha_pedido' => $this->fechaPedido?->format('Y-m-d H:i:s'),
            'estado_pedido' => $this->estadoPedido,
            'idCliente' => $this->idCliente,
            'fecha_entrega' => $this->fechaEntrega?->format('Y-m-d H:i:s'),
            'monto_pagado' => $this->montoPagado,
            'estado_pago' => $this->estadoPago
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['idPedido'] ?? null,
            isset($data['fecha_pedido']) ? new \DateTime($data['fecha_pedido']) : null,
            $data['estado_pedido'] ?? 'PENDIENTE',
            $data['idCliente'] ?? null,
            isset($data['fecha_entrega']) ? new \DateTime($data['fecha_entrega']) : null,
            $data['monto_pagado'] ?? 0.0,
            $data['estado_pago'] ?? 'PENDIENTE'
        );
    }

    public function isValid(): bool
    {
        $estadosValidos = ['PENDIENTE', 'EN_PROCESO', 'COMPLETADO', 'CANCELADO'];
        $estadosPagoValidos = ['PENDIENTE', 'PARCIAL', 'COMPLETO'];
        
        return !empty($this->idCliente) &&
               in_array($this->estadoPedido, $estadosValidos) &&
               in_array($this->estadoPago, $estadosPagoValidos) &&
               $this->montoPagado >= 0;
    }

    public function esPendiente(): bool
    {
        return $this->estadoPedido === 'PENDIENTE';
    }

    public function estaCompleto(): bool
    {
        return $this->estadoPedido === 'COMPLETADO';
    }

    public function estaPagado(): bool
    {
        return $this->estadoPago === 'COMPLETO';
    }
}