<?php

namespace App\Entities;

use App\Entities\Interfaces\DetalleUniformeEntityInterface;

class DetalleUniformeEntity implements DetalleUniformeEntityInterface
{
    private ?int $idDetalleUniforme;
    private ?int $idDetallePedido;
    private ?string $talla;
    private ?string $genero;
    private ?string $nombreCamisola;
    private ?string $numeroCamisola;
    private ?string $nombreAbajo;
    private ?int $cantidad;
    private ?float $conMedidas;

    public function __construct(
        ?int $idDetalleUniforme = null,
        ?int $idDetallePedido = null,
        ?string $talla = null,
        ?string $genero = null,
        ?string $nombreCamisola = null,
        ?string $numeroC

amisola = null,
        ?string $nombreAbajo = null,
        ?int $cantidad = 0,
        ?float $conMedidas = 0.0
    ) {
        $this->idDetalleUniforme = $idDetalleUniforme;
        $this->idDetallePedido = $idDetallePedido;
        $this->talla = $talla;
        $this->genero = $genero;
        $this->nombreCamisola = $nombreCamisola;
        $this->numeroC amisola = $numeroC amisola;
        $this->nombreAbajo = $nombreAbajo;
        $this->cantidad = $cantidad ?? 0;
        $this->conMedidas = $conMedidas ?? 0.0;
    }

    // Getters
    public function getIdDetalleUniforme(): ?int
    {
        return $this->idDetalleUniforme;
    }

    public function getIdDetallePedido(): ?int
    {
        return $this->idDetallePedido;
    }

    public function getTalla(): ?string
    {
        return $this->talla;
    }

    public function getGenero(): ?string
    {
        return $this->genero;
    }

    public function getNombreCamisola(): ?string
    {
        return $this->nombreCamisola;
    }

    public function getNumeroCamisola(): ?string
    {
        return $this->numeroC amisola;
    }

    public function getNombreAbajo(): ?string
    {
        return $this->nombreAbajo;
    }

    public function getCantidad(): ?int
    {
        return $this->cantidad;
    }

    public function getConMedidas(): ?float
    {
        return $this->conMedidas;
    }

    // Setters
    public function setIdDetalleUniforme(?int $idDetalleUniforme): void
    {
        $this->idDetalleUniforme = $idDetalleUniforme;
    }

    public function setIdDetallePedido(?int $idDetallePedido): void
    {
        $this->idDetallePedido = $idDetallePedido;
    }

    public function setTalla(?string $talla): void
    {
        $this->talla = $talla;
    }

    public function setGenero(?string $genero): void
    {
        $this->genero = $genero;
    }

    public function setNombreCamisola(?string $nombreCamisola): void
    {
        $this->nombreCamisola = $nombreCamisola;
    }

    public function setNumeroCamisola(?string $numeroCamisola): void
    {
        $this->numeroCamisola = $numeroCamisola;
    }

    public function setNombreAbajo(?string $nombreAbajo): void
    {
        $this->nombreAbajo = $nombreAbajo;
    }

    public function setCantidad(?int $cantidad): void
    {
        $this->cantidad = $cantidad;
    }

    public function setConMedidas(?float $conMedidas): void
    {
        $this->conMedidas = $conMedidas;
    }

    // Métodos de utilidad
    public function toArray(): array
    {
        return [
            'idDetalle_uniforme' => $this->idDetalleUniforme,
            'id_detalle_pedido' => $this->idDetallePedido,
            'talla' => $this->talla,
            'genero' => $this->genero,
            'nombre_camisola' => $this->nombreCamisola,
            'numero_camisola' => $this->numeroCamisola,
            'nombre_abajo_numero' => $this->nombreAbajo,
            'cantidad' => $this->cantidad,
            'con_medidas' => $this->conMedidas
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['idDetalle_uniforme'] ?? null,
            $data['id_detalle_pedido'] ?? null,
            $data['talla'] ?? null,
            $data['genero'] ?? null,
            $data['nombre_camisola'] ?? null,
            $data['numero_camisola'] ?? null,
            $data['nombre_abajo_numero'] ?? null,
            $data['cantidad'] ?? 0,
            $data['con_medidas'] ?? 0.0
        );
    }

    public function isValid(): bool
    {
        $tallasValidas = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
        $generosValidos = ['M', 'F', 'MASCULINO', 'FEMENINO'];
        
        return !empty($this->idDetallePedido) &&
               !empty($this->talla) &&
               !empty($this->genero) &&
               in_array(strtoupper($this->talla), $tallasValidas) &&
               in_array(strtoupper($this->genero), $generosValidos) &&
               $this->cantidad > 0;
    }

    public function tieneCamisola(): bool
    {
        return !empty($this->nombreCamisola);
    }

    public function tieneAbajo(): bool
    {
        return !empty($this->nombreAbajo);
    }

    public function requiereMedidas(): bool
    {
        return $this->conMedidas > 0;
    }

    public function getEspecificacionesCompletas(): string
    {
        $especificaciones = [];
        
        if ($this->tieneCamisola()) {
            $especificaciones[] = "Camisola: {$this->nombreCamisola}" . 
                (!empty($this->numeroCamisola) ? " #{$this->numeroCamisola}" : "");
        }
        
        if ($this->tieneAbajo()) {
            $especificaciones[] = "Abajo: {$this->nombreAbajo}";
        }
        
        $especificaciones[] = "Talla: {$this->talla}";
        $especificaciones[] = "Género: {$this->genero}";
        $especificaciones[] = "Cantidad: {$this->cantidad}";
        
        if ($this->requiereMedidas()) {
            $especificaciones[] = "Con medidas personalizadas";
        }
        
        return implode(", ", $especificaciones);
    }
}