<?php

namespace App\Services;

use App\Entities\PedidoEntity;
use App\Entities\DetallePedidoEntity;
use App\Entities\DetalleUniformeEntity;
use App\Repositories\PedidoRepository;
use App\Repositories\DetallePedidoRepository;
use App\Repositories\DetalleUniformeRepository;
use App\Repositories\ClienteRepository;
use App\Repositories\ProductoRepository;
use Exception;

/**
 * Servicio para la gestión de pedidos de uniformes deportivos
 * Contiene la lógica de negocio para pedidos y sus detalles
 */
class PedidoService
{
    private PedidoRepository $pedidoRepository;
    private DetallePedidoRepository $detallePedidoRepository;
    private DetalleUniformeRepository $detalleUniformeRepository;
    private ClienteRepository $clienteRepository;
    private ProductoRepository $productoRepository;

    public function __construct(
        PedidoRepository $pedidoRepository,
        DetallePedidoRepository $detallePedidoRepository,
        DetalleUniformeRepository $detalleUniformeRepository,
        ClienteRepository $clienteRepository,
        ProductoRepository $productoRepository
    ) {
        $this->pedidoRepository = $pedidoRepository;
        $this->detallePedidoRepository = $detallePedidoRepository;
        $this->detalleUniformeRepository = $detalleUniformeRepository;
        $this->clienteRepository = $clienteRepository;
        $this->productoRepository = $productoRepository;
    }

    /**
     * Crear nuevo pedido
     */
    public function crearPedido(array $datosPedido): array
    {
        try {
            // Validar cliente
            $cliente = $this->clienteRepository->findById($datosPedido['idCliente']);
            if (!$cliente) {
                return [
                    'exito' => false,
                    'mensaje' => 'Cliente no encontrado',
                    'datos' => null
                ];
            }

            // Validar productos
            if (empty($datosPedido['productos'])) {
                return [
                    'exito' => false,
                    'mensaje' => 'Debe agregar al menos un producto',
                    'datos' => null
                ];
            }

            // Crear pedido
            $pedido = new PedidoEntity();
            $pedido->setIdCliente($datosPedido['idCliente']);
            $pedido->setFechaPedido(new \DateTime($datosPedido['fechaPedido'] ?? 'now'));
            $pedido->setEstadoPedido($datosPedido['estadoPedido'] ?? 'Pendiente');

            $pedidoCreado = $this->pedidoRepository->create($pedido);

            if (!$pedidoCreado) {
                return [
                    'exito' => false,
                    'mensaje' => 'Error al crear el pedido',
                    'datos' => null
                ];
            }

            // Crear detalles del pedido
            $detallesCreados = [];
            foreach ($datosPedido['productos'] as $itemProducto) {
                $detallePedido = $this->crearDetallePedido($pedidoCreado->getIdPedido(), $itemProducto);
                
                if ($detallePedido) {
                    $detallesCreados[] = $detallePedido;
                    
                    // Si tiene uniformes, crearlos
                    if (isset($itemProducto['uniformes']) && !empty($itemProducto['uniformes'])) {
                        foreach ($itemProducto['uniformes'] as $uniforme) {
                            $this->crearDetalleUniforme($detallePedido->getIdDetallePedido(), $uniforme);
                        }
                    }
                }
            }

            return [
                'exito' => true,
                'mensaje' => 'Pedido creado exitosamente',
                'datos' => [
                    'pedido' => $pedidoCreado->toArray(),
                    'detalles' => array_map(fn($d) => $d->toArray(), $detallesCreados)
                ]
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al crear pedido: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Obtener pedido por ID
     */
    public function obtenerPedidoPorId(int $id): array
    {
        try {
            $pedido = $this->pedidoRepository->findById($id);

            if (!$pedido) {
                return [
                    'exito' => false,
                    'mensaje' => 'Pedido no encontrado',
                    'datos' => null
                ];
            }

            return [
                'exito' => true,
                'mensaje' => 'Pedido encontrado',
                'datos' => $pedido->toArray()
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener pedido: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Listar todos los pedidos
     */
    public function listarPedidos(): array
    {
        try {
            $pedidos = $this->pedidoRepository->findAll();
            
            return [
                'exito' => true,
                'mensaje' => 'Pedidos obtenidos exitosamente',
                'datos' => array_map(fn($p) => $p->toArray(), $pedidos)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al listar pedidos: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Obtener pedidos por cliente
     */
    public function obtenerPedidosPorCliente(int $clienteId): array
    {
        try {
            $cliente = $this->clienteRepository->findById($clienteId);
            if (!$cliente) {
                return [
                    'exito' => false,
                    'mensaje' => 'Cliente no encontrado',
                    'datos' => []
                ];
            }

            $pedidos = $this->pedidoRepository->findByClient($clienteId);
            
            return [
                'exito' => true,
                'mensaje' => 'Pedidos del cliente obtenidos',
                'datos' => array_map(fn($p) => $p->toArray(), $pedidos)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener pedidos del cliente: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Obtener pedidos por estado
     */
    public function obtenerPedidosPorEstado(string $estado): array
    {
        try {
            $pedidos = $this->pedidoRepository->findByStatus($estado);
            
            return [
                'exito' => true,
                'mensaje' => 'Pedidos obtenidos exitosamente',
                'datos' => array_map(fn($p) => $p->toArray(), $pedidos)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener pedidos por estado: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Actualizar estado del pedido
     */
    public function actualizarEstadoPedido(int $id, string $nuevoEstado): array
    {
        try {
            $pedido = $this->pedidoRepository->findById($id);

            if (!$pedido) {
                return [
                    'exito' => false,
                    'mensaje' => 'Pedido no encontrado',
                    'datos' => null
                ];
            }

            $estadosValidos = ['Pendiente', 'En proceso', 'Completado', 'Cancelado'];
            if (!in_array($nuevoEstado, $estadosValidos)) {
                return [
                    'exito' => false,
                    'mensaje' => 'Estado no válido. Debe ser: ' . implode(', ', $estadosValidos),
                    'datos' => null
                ];
            }

            $pedido->setEstadoPedido($nuevoEstado);
            $resultado = $this->pedidoRepository->update($pedido);

            if ($resultado) {
                $pedidoActualizado = $this->pedidoRepository->findById($id);
                return [
                    'exito' => true,
                    'mensaje' => 'Estado del pedido actualizado exitosamente',
                    'datos' => $pedidoActualizado->toArray()
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo actualizar el estado',
                'datos' => null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al actualizar estado: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Cancelar pedido
     */
    public function cancelarPedido(int $id): array
    {
        try {
            $pedido = $this->pedidoRepository->findById($id);

            if (!$pedido) {
                return [
                    'exito' => false,
                    'mensaje' => 'Pedido no encontrado',
                    'datos' => null
                ];
            }

            if ($pedido->getEstadoPedido() === 'Completado') {
                return [
                    'exito' => false,
                    'mensaje' => 'No se puede cancelar un pedido completado',
                    'datos' => null
                ];
            }

            if ($pedido->getEstadoPedido() === 'Cancelado') {
                return [
                    'exito' => false,
                    'mensaje' => 'El pedido ya está cancelado',
                    'datos' => null
                ];
            }

            return $this->actualizarEstadoPedido($id, 'Cancelado');

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al cancelar pedido: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Completar pedido
     */
    public function completarPedido(int $id): array
    {
        try {
            $pedido = $this->pedidoRepository->findById($id);

            if (!$pedido) {
                return [
                    'exito' => false,
                    'mensaje' => 'Pedido no encontrado',
                    'datos' => null
                ];
            }

            if ($pedido->getEstadoPedido() === 'Cancelado') {
                return [
                    'exito' => false,
                    'mensaje' => 'No se puede completar un pedido cancelado',
                    'datos' => null
                ];
            }

            if ($pedido->getEstadoPedido() === 'Completado') {
                return [
                    'exito' => false,
                    'mensaje' => 'El pedido ya está completado',
                    'datos' => null
                ];
            }

            return $this->actualizarEstadoPedido($id, 'Completado');

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al completar pedido: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Eliminar pedido
     */
    public function eliminarPedido(int $id): array
    {
        try {
            $resultado = $this->pedidoRepository->delete($id);

            if ($resultado) {
                return [
                    'exito' => true,
                    'mensaje' => 'Pedido eliminado exitosamente',
                    'datos' => null
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo eliminar el pedido',
                'datos' => null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al eliminar pedido: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Crear detalle de pedido
     */
    private function crearDetallePedido(int $pedidoId, array $itemProducto): ?DetallePedidoEntity
    {
        try {
            $producto = $this->productoRepository->findById($itemProducto['idProducto']);
            
            if (!$producto) {
                return null;
            }

            $detalle = new DetallePedidoEntity();
            $detalle->setIdPedido($pedidoId);
            $detalle->setIdProducto($itemProducto['idProducto']);
            $detalle->setCantidadProducto($itemProducto['cantidadProducto']);
            $detalle->setDiseno($itemProducto['diseno'] ?? '');

            return $this->detallePedidoRepository->create($detalle);
        } catch (Exception $e) {
            error_log("Error creating detalle pedido: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear detalle de uniforme
     */
    private function crearDetalleUniforme(int $detallePedidoId, array $uniforme): ?DetalleUniformeEntity
    {
        try {
            $detalleUniforme = new DetalleUniformeEntity();
            $detalleUniforme->setIdDetallePedido($detallePedidoId);
            $detalleUniforme->setTalla($uniforme['talla']);
            $detalleUniforme->setGenero($uniforme['genero'] ?? 'Masculino');
            $detalleUniforme->setNumeroCamisola($uniforme['numeroCamisola'] ?? null);

            return $this->detalleUniformeRepository->create($detalleUniforme);
        } catch (Exception $e) {
            error_log("Error creating detalle uniforme: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Validar datos de pedido
     */
    public function validarDatosPedido(array $datos): array
    {
        $errores = [];

        if (empty($datos['idCliente']) || $datos['idCliente'] <= 0) {
            $errores[] = 'El cliente es requerido';
        }

        if (empty($datos['productos']) || !is_array($datos['productos'])) {
            $errores[] = 'Debe agregar al menos un producto';
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }
}