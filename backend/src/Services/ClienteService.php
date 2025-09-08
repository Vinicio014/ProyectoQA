<?php

namespace Proyecto\Services;

use Proyecto\Entities\ClienteEntity;
use Proyecto\Repositories\ClienteRepository;
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
            // Validar que el email no exista si se proporciona
            if (!empty($datosCliente['email']) && $this->existeClientePorEmail($datosCliente['email'])) {
                return [
                    'exito' => false,
                    'mensaje' => 'Ya existe un cliente con ese email',
                    'datos' => null
                ];
            }

            // Validar documento único si se proporciona
            if (!empty($datosCliente['documento']) && $this->existeClientePorDocumento($datosCliente['documento'])) {
                return [
                    'exito' => false,
                    'mensaje' => 'Ya existe un cliente con ese documento',
                    'datos' => null
                ];
            }

            $cliente = new ClienteEntity();
            $cliente->setNombre($datosCliente['nombre']);
            $cliente->setApellido($datosCliente['apellido']);
            $cliente->setEmail($datosCliente['email'] ?? '');
            $cliente->setTelefono($datosCliente['telefono'] ?? '');
            $cliente->setDocumento($datosCliente['documento'] ?? '');
            $cliente->setTipoDocumento($datosCliente['tipo_documento'] ?? 'CEDULA');
            $cliente->setDireccion($datosCliente['direccion'] ?? '');
            $cliente->setCiudad($datosCliente['ciudad'] ?? '');
            $cliente->setEstado($datosCliente['estado'] ?? 'ACTIVO');

            $clienteCreado = $this->clienteRepository->crear($cliente);

            return [
                'exito' => true,
                'mensaje' => 'Cliente creado exitosamente',
                'datos' => $clienteCreado->toArray()
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
            $cliente = $this->clienteRepository->obtenerPorId($id);

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
     * Buscar clientes por término
     */
    public function buscarClientes(string $termino): array
    {
        try {
            $clientes = $this->clienteRepository->buscarPorTermino($termino);
            
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
     * Listar clientes con filtros
     */
    public function listarClientes(array $filtros = []): array
    {
        try {
            $clientes = $this->clienteRepository->listarConFiltros($filtros);
            
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
            $cliente = $this->clienteRepository->obtenerPorId($id);

            if (!$cliente) {
                return [
                    'exito' => false,
                    'mensaje' => 'Cliente no encontrado',
                    'datos' => null
                ];
            }

            // Validar email único si se está cambiando
            if (isset($datosCliente['email']) && !empty($datosCliente['email']) && $datosCliente['email'] !== $cliente->getEmail()) {
                if ($this->existeClientePorEmail($datosCliente['email'])) {
                    return [
                        'exito' => false,
                        'mensaje' => 'Ya existe un cliente con ese email',
                        'datos' => null
                    ];
                }
            }

            // Validar documento único si se está cambiando
            if (isset($datosCliente['documento']) && !empty($datosCliente['documento']) && $datosCliente['documento'] !== $cliente->getDocumento()) {
                if ($this->existeClientePorDocumento($datosCliente['documento'])) {
                    return [
                        'exito' => false,
                        'mensaje' => 'Ya existe un cliente con ese documento',
                        'datos' => null
                    ];
                }
            }

            // Actualizar campos
            if (isset($datosCliente['nombre'])) {
                $cliente->setNombre($datosCliente['nombre']);
            }
            if (isset($datosCliente['apellido'])) {
                $cliente->setApellido($datosCliente['apellido']);
            }
            if (isset($datosCliente['email'])) {
                $cliente->setEmail($datosCliente['email']);
            }
            if (isset($datosCliente['telefono'])) {
                $cliente->setTelefono($datosCliente['telefono']);
            }
            if (isset($datosCliente['documento'])) {
                $cliente->setDocumento($datosCliente['documento']);
            }
            if (isset($datosCliente['tipo_documento'])) {
                $cliente->setTipoDocumento($datosCliente['tipo_documento']);
            }
            if (isset($datosCliente['direccion'])) {
                $cliente->setDireccion($datosCliente['direccion']);
            }
            if (isset($datosCliente['ciudad'])) {
                $cliente->setCiudad($datosCliente['ciudad']);
            }
            if (isset($datosCliente['estado'])) {
                $cliente->setEstado($datosCliente['estado']);
            }

            $clienteActualizado = $this->clienteRepository->actualizar($cliente);

            return [
                'exito' => true,
                'mensaje' => 'Cliente actualizado exitosamente',
                'datos' => $clienteActualizado->toArray()
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
     * Eliminar cliente
     */
    public function eliminarCliente(int $id): array
    {
        try {
            // Verificar que no tenga pedidos o ventas pendientes
            if ($this->tieneTransaccionesPendientes($id)) {
                return [
                    'exito' => false,
                    'mensaje' => 'No se puede eliminar el cliente porque tiene transacciones pendientes',
                    'datos' => null
                ];
            }

            $resultado = $this->clienteRepository->eliminar($id);

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
     * Obtener historial de compras del cliente
     */
    public function obtenerHistorialCompras(int $clienteId): array
    {
        try {
            $cliente = $this->clienteRepository->obtenerPorId($clienteId);

            if (!$cliente) {
                return [
                    'exito' => false,
                    'mensaje' => 'Cliente no encontrado',
                    'datos' => null
                ];
            }

            // Esta funcionalidad se implementará cuando tengamos VentaRepository y PedidoRepository
            $historial = [
                'cliente' => $cliente->toArray(),
                'ventas' => [], // Se llenará con VentaRepository
                'pedidos' => [], // Se llenará con PedidoRepository
                'estadisticas' => [
                    'total_compras' => 0,
                    'monto_total' => 0,
                    'ultima_compra' => null
                ]
            ];

            return [
                'exito' => true,
                'mensaje' => 'Historial obtenido exitosamente',
                'datos' => $historial
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener historial: ' . $e->getMessage(),
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
            $clientes = $this->clienteRepository->listarPorEstado('ACTIVO');
            
            $clientesSelect = [];
            foreach ($clientes as $cliente) {
                $clientesSelect[] = [
                    'id' => $cliente->getId(),
                    'nombre_completo' => $cliente->getNombre() . ' ' . $cliente->getApellido(),
                    'documento' => $cliente->getDocumento(),
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
     * Validar datos de cliente
     */
    public function validarDatosCliente(array $datos): array
    {
        $errores = [];

        if (empty($datos['nombre'])) {
            $errores[] = 'El nombre es requerido';
        }

        if (empty($datos['apellido'])) {
            $errores[] = 'El apellido es requerido';
        }

        if (!empty($datos['email']) && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El email no es válido';
        }

        if (!empty($datos['documento']) && strlen($datos['documento']) < 6) {
            $errores[] = 'El documento debe tener al menos 6 caracteres';
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }

    /**
     * Verificar si existe cliente por email
     */
    private function existeClientePorEmail(string $email): bool
    {
        try {
            $cliente = $this->clienteRepository->obtenerPorEmail($email);
            return $cliente !== null;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Verificar si existe cliente por documento
     */
    private function existeClientePorDocumento(string $documento): bool
    {
        try {
            $cliente = $this->clienteRepository->obtenerPorDocumento($documento);
            return $cliente !== null;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Verificar si el cliente tiene transacciones pendientes
     */
    private function tieneTransaccionesPendientes(int $clienteId): bool
    {
        try {
            // Esta lógica se implementará cuando tengamos VentaRepository y PedidoRepository
            // Por ahora retornamos false
            return false;
        } catch (Exception $e) {
            return true; // Por seguridad
        }
    }

    /**
     * Obtener estadísticas del cliente
     */
    public function obtenerEstadisticasCliente(int $clienteId): array
    {
        try {
            $cliente = $this->clienteRepository->obtenerPorId($clienteId);

            if (!$cliente) {
                return [
                    'exito' => false,
                    'mensaje' => 'Cliente no encontrado',
                    'datos' => null
                ];
            }

            $estadisticas = [
                'fecha_registro' => $cliente->getFechaCreacion()->format('d/m/Y'),
                'estado' => $cliente->getEstado(),
                'total_pedidos' => 0, // Se calculará con PedidoRepository
                'total_ventas' => 0, // Se calculará con VentaRepository
                'monto_total_compras' => 0, // Se calculará con VentaRepository
                'producto_mas_comprado' => null // Se calculará con DetalleVentaRepository
            ];

            return [
                'exito' => true,
                'mensaje' => 'Estadísticas obtenidas exitosamente',
                'datos' => $estadisticas
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener estadísticas: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }
}