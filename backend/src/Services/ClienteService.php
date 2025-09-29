<?php

namespace App\Services;

use App\Entities\ClienteEntity;
use App\Repositories\ClienteRepository;
use Exception;

/**
 * Servicio para la gestión de clientes
 * Contiene la lógica de negocio para clientes
 */
class ClienteService
{
    private ClienteRepository $clienteRepository;

    public function __construct(ClienteRepository $clienteRepository)
    {
        $this->clienteRepository = $clienteRepository;
    }

    /**
     * Crear nuevo cliente
     */
    public function crearCliente(array $datosCliente): array
    {
        try {
            $cliente = new ClienteEntity();
            $cliente->setPrimerNombre($datosCliente['primer_nombre']);
            $cliente->setPrimerApellido($datosCliente['primer_apellido']);
            $cliente->setTelefono((int)$datosCliente['telefono']);

            $clienteCreado = $this->clienteRepository->create($cliente);

            return [
                'exito' => true,
                'mensaje' => 'Cliente creado exitosamente',
                'datos' => $clienteCreado ? $clienteCreado->toArray() : null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al crear cliente: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Obtener cliente por ID
     */
    public function obtenerClientePorId(int $id): array
    {
        try {
            $cliente = $this->clienteRepository->findById($id);

            if (!$cliente) {
                return [
                    'exito' => false,
                    'mensaje' => 'Cliente no encontrado',
                    'datos' => null
                ];
            }

            return [
                'exito' => true,
                'mensaje' => 'Cliente encontrado',
                'datos' => $cliente->toArray()
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener cliente: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Buscar clientes por nombre
     */
    public function buscarClientes(string $termino): array
    {
        try {
            $clientes = $this->clienteRepository->searchByName($termino);
            
            return [
                'exito' => true,
                'mensaje' => 'Búsqueda completada',
                'datos' => array_map(fn($cliente) => $cliente->toArray(), $clientes)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error en búsqueda: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Listar todos los clientes
     */
    public function listarClientes(): array
    {
        try {
            $clientes = $this->clienteRepository->findAll();
            
            return [
                'exito' => true,
                'mensaje' => 'Clientes obtenidos exitosamente',
                'datos' => array_map(fn($cliente) => $cliente->toArray(), $clientes)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al listar clientes: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Actualizar cliente
     */
    public function actualizarCliente(int $id, array $datosCliente): array
    {
        try {
            $cliente = $this->clienteRepository->findById($id);

            if (!$cliente) {
                return [
                    'exito' => false,
                    'mensaje' => 'Cliente no encontrado',
                    'datos' => null
                ];
            }

            // Actualizar campos
            if (isset($datosCliente['primer_nombre'])) {
                $cliente->setPrimerNombre($datosCliente['primer_nombre']);
            }
            if (isset($datosCliente['primer_apellido'])) {
                $cliente->setPrimerApellido($datosCliente['primer_apellido']);
            }
            if (isset($datosCliente['telefono'])) {
                $cliente->setTelefono((int)$datosCliente['telefono']);
            }

            $resultado = $this->clienteRepository->update($cliente);

            if ($resultado) {
                $clienteActualizado = $this->clienteRepository->findById($id);
                return [
                    'exito' => true,
                    'mensaje' => 'Cliente actualizado exitosamente',
                    'datos' => $clienteActualizado->toArray()
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo actualizar el cliente',
                'datos' => null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al actualizar cliente: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Eliminar cliente (hard delete)
     */
    public function eliminarCliente(int $id): array
    {
        try {
            $resultado = $this->clienteRepository->delete($id);

            if ($resultado) {
                return [
                    'exito' => true,
                    'mensaje' => 'Cliente eliminado exitosamente',
                    'datos' => null
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo eliminar el cliente',
                'datos' => null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al eliminar cliente: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Obtener clientes para select/dropdown
     */
    public function obtenerClientesParaSelect(): array
    {
        try {
            $clientes = $this->clienteRepository->findAll();
            
            $clientesSelect = [];
            foreach ($clientes as $cliente) {
                $clientesSelect[] = [
                    'id' => $cliente->getIdCliente(),
                    'nombre_completo' => $cliente->getPrimerNombre() . ' ' . $cliente->getPrimerApellido(),
                    'telefono' => $cliente->getTelefono()
                ];
            }

            return [
                'exito' => true,
                'mensaje' => 'Clientes para select obtenidos',
                'datos' => $clientesSelect
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener clientes para select: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Obtener clientes por género
     */
    public function obtenerClientesPorGenero(string $genero): array
    {
        try {
            $clientes = $this->clienteRepository->findByGender($genero);
            
            return [
                'exito' => true,
                'mensaje' => 'Clientes obtenidos exitosamente',
                'datos' => array_map(fn($cliente) => $cliente->toArray(), $clientes)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener clientes: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Validar datos de cliente
     */
    public function validarDatosCliente(array $datos): array
    {
        $errores = [];

        if (empty($datos['primer_nombre'])) {
            $errores[] = 'El primer nombre es requerido';
        }

        if (empty($datos['primer_apellido'])) {
            $errores[] = 'El primer apellido es requerido';
        }

        if (empty($datos['telefono'])) {
            $errores[] = 'El teléfono es requerido';
        } elseif (!is_numeric($datos['telefono'])) {
            $errores[] = 'El teléfono debe ser numérico';
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }
}