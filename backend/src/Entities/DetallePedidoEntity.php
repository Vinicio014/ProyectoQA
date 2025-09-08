<?php

namespace App\Entities;

use App\Entities\Interfaces\DetallePedidoEntityInterface;

class DetallePedidoEntity implements DetallePedidoEntityInterface
{
    private ?int $idDetallePedido;
    private ?int $cantidadProducto;
    private ?string $diseno;
    private ?int $idPedido;
    private ?int $idProducto;

    public function __construct(
        ?int $idDetallePedido = null,
        ?int $cantidadProducto = 0,
        ?string $diseno = null,
        ?int $idPedido = null,
        ?int $idProducto = null
    ) {
        $this->idDetallePedido = $idDetallePedido;
        $this->cantidadProducto = $cantidadProducto ?? 0;
        $this->diseno = $diseno;
        $this->idPedido = $idPedido;
        $this->idProducto = $idProducto;
    }

    // Getters
    public function getIdDetallePedido(): ?int
    {
        return $this->idDetallePedido;
    }

    public function getCantidadProducto(): ?int
    {
        return $this->cantidadProducto;
    }

    public function getDiseno(): ?string
    {
        return $this->diseno;
    }

    public function getIdPedido(): ?int
    {
        return $this->idPedido;
    }

    public function getIdProducto(): ?int
    {
        return $this->idProducto;
    }

    // Setters
    public function setIdDetallePedido(?int $idDetallePedido): void
    {
        $this->idDetallePedido = $idDetallePedido;
    }

    public function setCantidadProducto(?int $cantidadProducto): void
    {
        $this->cantidadProducto = $cantidadProducto;
    }

    public function setDiseno(?string $diseno): void
    {
        $this->diseno = $diseno;
    }

    public function setIdPedido(?int $idPedido): void
    {
        $this->idPedido = $idPedido;
    }

    public function setIdProducto(?int $idProducto): void
    {
        $this->idProducto = $idProducto;
    }

    // Métodos de utilidad
    public function toArray(): array
    {
        return [
            'id_detalle_pedido' => $this->idDetallePedido,
            'cantidad_producto' => $this->cantidadProducto,
            'diseno' => $this->diseno,
            'id_pedido' => $this->idPedido,
            'id_producto' => $this->idProducto
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id_detalle_pedido'] ?? null,
            $data['cantidad_producto'] ?? 0,
            $data['diseno'] ?? null,
            $data['id_pedido'] ?? null,
            $data['id_producto'] ?? null
        );
    }

    public function isValid(): bool
    {
        return !empty($this->idPedido) && 
               !empty($this->idProducto) &&
               $this->cantidadProducto > 0;
    }

    public function tieneDiseno(): bool
    {
        return !empty($this->diseno);
    }

    public function calcularTotal(float $precioUnitario): float
    {
        return $this->cantidadProducto * $precioUnitario;
    }
}