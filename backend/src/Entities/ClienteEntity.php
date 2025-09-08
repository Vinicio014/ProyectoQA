<?php

namespace App\Entities;

use App\Entities\Interfaces\ClienteEntityInterface;

class ClienteEntity implements ClienteEntityInterface
{
    private ?int $idCliente;
    private ?string $primerNombre;
    private ?string $segundoNombre;
    private ?string $primerApellido;
    private ?string $segundoApellido;
    private ?string $genero;
    private ?string $direccion;
    private ?int $telefono;

    public function __construct(
        ?int $idCliente = null,
        ?string $primerNombre = null,
        ?string $segundoNombre = null,
        ?string $primerApellido = null,
        ?string $segundoApellido = null,
        ?string $genero = null,
        ?string $direccion = null,
        ?int $telefono = null
    ) {
        $this->idCliente = $idCliente;
        $this->primerNombre = $primerNombre;
        $this->segundoNombre = $segundoNombre;
        $this->primerApellido = $primerApellido;
        $this->segundoApellido = $segundoApellido;
        $this->genero = $genero;
        $this->direccion = $direccion;
        $this->telefono = $telefono;
    }

    // Getters
    public function getIdCliente(): ?int
    {
        return $this->idCliente;
    }

    public function getPrimerNombre(): ?string
    {
        return $this->primerNombre;
    }

    public function getSegundoNombre(): ?string
    {
        return $this->segundoNombre;
    }

    public function getPrimerApellido(): ?string
    {
        return $this->primerApellido;
    }

    public function getSegundoApellido(): ?string
    {
        return $this->segundoApellido;
    }

    public function getGenero(): ?string
    {
        return $this->genero;
    }

    public function getDireccion(): ?string
    {
        return $this->direccion;
    }

    public function getTelefono(): ?int
    {
        return $this->telefono;
    }

    // Setters
    public function setIdCliente(?int $idCliente): void
    {
        $this->idCliente = $idCliente;
    }

    public function setPrimerNombre(?string $primerNombre): void
    {
        $this->primerNombre = $primerNombre;
    }

    public function setSegundoNombre(?string $segundoNombre): void
    {
        $this->segundoNombre = $segundoNombre;
    }

    public function setPrimerApellido(?string $primerApellido): void
    {
        $this->primerApellido = $primerApellido;
    }

    public function setSegundoApellido(?string $segundoApellido): void
    {
        $this->segundoApellido = $segundoApellido;
    }

    public function setGenero(?string $genero): void
    {
        $this->genero = $genero;
    }

    public function setDireccion(?string $direccion): void
    {
        $this->direccion = $direccion;
    }

    public function setTelefono(?int $telefono): void
    {
        $this->telefono = $telefono;
    }

    // Métodos de utilidad
    public function toArray(): array
    {
        return [
            'idCliente' => $this->idCliente,
            'primer_nombre' => $this->primerNombre,
            'segundo_nombre' => $this->segundoNombre,
            'primer_apellido' => $this->primerApellido,
            'segundo_apellido' => $this->segundoApellido,
            'genero' => $this->genero,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['idCliente'] ?? null,
            $data['primer_nombre'] ?? null,
            $data['segundo_nombre'] ?? null,
            $data['primer_apellido'] ?? null,
            $data['segundo_apellido'] ?? null,
            $data['genero'] ?? null,
            $data['direccion'] ?? null,
            $data['telefono'] ?? null
        );
    }

    public function isValid(): bool
    {
        return !empty($this->primerNombre) && 
               !empty($this->primerApellido) &&
               !empty($this->genero) &&
               in_array(strtoupper($this->genero), ['M', 'F', 'MASCULINO', 'FEMENINO']);
    }

    public function getNombreCompleto(): string
    {
        $nombres = array_filter([
            $this->primerNombre,
            $this->segundoNombre,
            $this->primerApellido,
            $this->segundoApellido
        ]);
        
        return implode(' ', $nombres);
    }
}