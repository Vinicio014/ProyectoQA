<?php

namespace App\Entities\Interfaces;

interface PedidoEntityInterface
{
    // Getters
    public function getIdPedido(): ?int;
    public function getFechaPedido(): ?\DateTime;
    public function getEstadoPedido(): ?string;
    public function getIdCliente(): ?int;
    public function getFechaEntrega(): ?\DateTime;
    public function getMontoPagado(): ?float;
    public function getEstadoPago(): ?string;

    // Setters
    public function setIdPedido(?int $idPedido): void;
    public function setFechaPedido(?\DateTime $fechaPedido): void;
    public function setEstadoPedido(?string $estadoPedido): void;
    public function setIdCliente(?int $idCliente): void;
    public function setFechaEntrega(?\DateTime $fechaEntrega): void;
    public function setMontoPagado(?float $montoPagado): void;
    public function setEstadoPago(?string $estadoPago): void;

    // Métodos de utilidad
    public function toArray(): array;
    public static function fromArray(array $data): self;
    public function isValid(): bool;
    public function esPendiente(): bool;
    public function estaCompleto(): bool;
    public function estaPagado(): bool;
}