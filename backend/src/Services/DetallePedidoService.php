<?php

namespace App\Services;

use App\Entities\DetallePedidoEntity;
use App\Repositories\DetallePedidoRepository;
use App\Repositories\PedidoRepository;
use App\Repositories\ProductoRepository;
use Exception;

/**
 * Servicio para la gestión de detalles de pedidos
 * Contiene la lógica de negocio para los items de cada pedido
 */
class DetallePedidoService
{
    private DetallePedidoRepository $detallePedidoRepository;
    private PedidoRepository $pedidoRepository;
    private ProductoRepository $productoRepository;

    public function __construct(
        DetallePedidoRepository $detallePedidoRepository,
        PedidoRepository $pedidoRepository,
        ProductoRepository $productoRepository
    ) {
        $this->detallePedidoRepository = $detallePedidoRepository;
        $this->pedidoRepository = $pedidoRepository;
        $this->productoRepository = $productoRepository;
    }

    /**
     * Crear detalle de pedido
     */
    public function crearDetallePedido(array $datosDetalle): array
    {
        try {
            // Validar que el pedido exista
            $pedido = $this->pedidoRepository->findById($datosDetalle['idPedido']);
            if (!$pedido) {
                return [
                    'exito' => false,
                    'mensaje' => 'Pedido no encontrado',
                    'datos' => null
                ];
            }

            // Validar que el producto exista
            $producto = $this->productoRepository->findById($datosDetalle['idProducto']);
            if (!$producto) {
                return [
                    'exito' => false,
                    'mensaje' => 'Producto no encontrado',
                    'datos' => null
                ];
            }

            // Validar cantidad
            if ($datosDetalle['cantidadProducto'] <= 0) {
                return [
                    'exito' => false,
                    'mensaje' => 'La cantidad debe ser mayor que 0',
                    'datos' => null
                ];
            }

            // Validar stock disponible
            if ($producto->getStock() < $datosDetalle['cantidadProducto']) {
                return [
                    'exito' => false,
                    'mensaje' => 'Stock insuficiente. Disponible: ' . $producto->getStock(),
                    'datos' => null
                ];
            }

            $detalle = new DetallePedidoEntity();
            $detalle->setIdPedido($datosDetalle['idPedido']);
            $detalle->setIdProducto($datosDetalle['idProducto']);
            $detalle->setCantidadProducto($datosDetalle['cantidadProducto']);
            $detalle->setDiseno($datosDetalle['diseno'] ?? '');

            $detalleCreado = $this->detallePedidoRepository->create($detalle);

            // Reducir stock del producto
            if ($detalleCreado) {
                $this->productoRepository->reduceStock(
                    $datosDetalle['idProducto'], 
                    $datosDetalle['cantidadProducto']
                );
            }

            return [
                'exito' => true,
                'mensaje' => 'Detalle de pedido creado exitosamente',
                'datos' => $detalleCreado ? $detalleCreado->toArray() : null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al crear detalle de pedido: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Obtener detalle por ID
     */
    public function obtenerDetallePorId(int $id): array
    {
        try {
            $detalle = $this->detallePedidoRepository->findById($id);

            if (!$detalle) {
                return [
                    'exito' => false,
                    'mensaje' => 'Detalle de pedido no encontrado',
                    'datos' => null
                ];
            }

            return [
                'exito' => true,
                'mensaje' => 'Detalle encontrado',
                'datos' => $detalle->toArray()
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener detalle: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Obtener detalles por pedido
     */
    public function obtenerDetallesPorPedido(int $pedidoId): array
    {
        try {
            $pedido = $this->pedidoRepository->findById($pedidoId);
            if (!$pedido) {
                return [
                    'exito' => false,
                    'mensaje' => 'Pedido no encontrado',
                    'datos' => []
                ];
            }

            $detalles = $this->detallePedidoRepository->findByPedido($pedidoId);
            
            return [
                'exito' => true,
                'mensaje' => 'Detalles de pedido obtenidos',
                'datos' => array_map(fn($d) => $d->toArray(), $detalles)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener detalles: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Actualizar detalle de pedido
     */
    public function actualizarDetallePedido(int $id, array $datosDetalle): array
    {
        try {
            $detalle = $this->detallePedidoRepository->findById($id);

            if (!$detalle) {
                return [
                    'exito' => false,
                    'mensaje' => 'Detalle de pedido no encontrado',
                    'datos' => null
                ];
            }

            // Verificar que el pedido no esté completado
            $pedido = $this->pedidoRepository->findById($detalle->getIdPedido());
            if ($pedido && $pedido->getEstadoPedido() === 'Completado') {
                return [
                    'exito' => false,
                    'mensaje' => 'No se puede modificar detalle de un pedido completado',
                    'datos' => null
                ];
            }

            $cantidadAnterior = $detalle->getCantidadProducto();

            // Actualizar campos
            if (isset($datosDetalle['cantidadProducto'])) {
                if ($datosDetalle['cantidadProducto'] <= 0) {
                    return [
                        'exito' => false,
                        'mensaje' => 'La cantidad debe ser mayor que 0',
                        'datos' => null
                    ];
                }
                $detalle->setCantidadProducto($datosDetalle['cantidadProducto']);
            }

            if (isset($datosDetalle['diseno'])) {
                $detalle->setDiseno($datosDetalle['diseno']);
            }

            $resultado = $this->detallePedidoRepository->update($detalle);

            if ($resultado) {
                // Ajustar stock si cambió la cantidad
                if (isset($datosDetalle['cantidadProducto'])) {
                    $diferencia = $datosDetalle['cantidadProducto'] - $cantidadAnterior;
                    if ($diferencia > 0) {
                        $this->productoRepository->reduceStock($detalle->getIdProducto(), $diferencia);
                    } elseif ($diferencia < 0) {
                        $this->productoRepository->increaseStock($detalle->getIdProducto(), abs($diferencia));
                    }
                }

                $detalleActualizado = $this->detallePedidoRepository->findById($id);
                return [
                    'exito' => true,
                    'mensaje' => 'Detalle de pedido actualizado exitosamente',
                    'datos' => $detalleActualizado->toArray()
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo actualizar el detalle',
                'datos' => null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al actualizar detalle: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Eliminar detalle de pedido
     */
    public function eliminarDetallePedido(int $id): array
    {
        try {
            $detalle = $this->detallePedidoRepository->findById($id);

            if (!$detalle) {
                return [
                    'exito' => false,
                    'mensaje' => 'Detalle de pedido no encontrado',
                    'datos' => null
                ];
            }

            // Verificar que el pedido no esté completado
            $pedido = $this->pedidoRepository->findById($detalle->getIdPedido());
            if ($pedido && $pedido->getEstadoPedido() === 'Completado') {
                return [
                    'exito' => false,
                    'mensaje' => 'No se puede eliminar detalle de un pedido completado',
                    'datos' => null
                ];
            }

            // Devolver stock antes de eliminar
            $this->productoRepository->increaseStock(
                $detalle->getIdProducto(), 
                $detalle->getCantidadProducto()
            );

            $resultado = $this->detallePedidoRepository->delete($id);

            if ($resultado) {
                return [
                    'exito' => true,
                    'mensaje' => 'Detalle de pedido eliminado exitosamente',
                    'datos' => null
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo eliminar el detalle',
                'datos' => null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al eliminar detalle: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Validar disponibilidad para pedido
     */
    public function verificarDisponibilidadPedido(int $pedidoId): array
    {
        try {
            $pedido = $this->pedidoRepository->findById($pedidoId);
            if (!$pedido) {
                return [
                    'exito' => false,
                    'mensaje' => 'Pedido no encontrado',
                    'datos' => null
                ];
            }

            $detalles = $this->detallePedidoRepository->findByPedido($pedidoId);
            $disponibilidad = [];
            $todoDisponible = true;

            foreach ($detalles as $detalle) {
                $producto = $this->productoRepository->findById($detalle->getIdProducto());
                $disponible = $producto && $producto->getStock() >= $detalle->getCantidadProducto();
                
                if (!$disponible) {
                    $todoDisponible = false;
                }

                $disponibilidad[] = [
                    'detalle_id' => $detalle->getIdDetallePedido(),
                    'producto_id' => $producto ? $producto->getIdProducto() : null,
                    'producto_nombre' => $producto ? $producto->getNombre() : 'Producto no encontrado',
                    'cantidad_pedida' => $detalle->getCantidadProducto(),
                    'stock_disponible' => $producto ? $producto->getStock() : 0,
                    'disponible' => $disponible,
                    'faltante' => $disponible ? 0 : $detalle->getCantidadProducto() - ($producto ? $producto->getStock() : 0)
                ];
            }

            return [
                'exito' => true,
                'mensaje' => 'Disponibilidad verificada',
                'datos' => [
                    'pedido_id' => $pedidoId,
                    'todo_disponible' => $todoDisponible,
                    'detalles_disponibilidad' => $disponibilidad
                ]
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al verificar disponibilidad: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Obtener productos más pedidos
     */
    public function obtenerProductosMasPedidos(int $limite = 10): array
    {
        try {
            $productos = $this->detallePedidoRepository->getMostOrderedProducts($limite);
            
            return [
                'exito' => true,
                'mensaje' => 'Productos más pedidos obtenidos',
                'datos' => $productos
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener productos más pedidos: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Validar datos de detalle de pedido
     */
    public function validarDatosDetalle(array $datos): array
    {
        $errores = [];

        if (empty($datos['idPedido']) || $datos['idPedido'] <= 0) {
            $errores[] = 'El pedido es requerido';
        }

        if (empty($datos['idProducto']) || $datos['idProducto'] <= 0) {
            $errores[] = 'El producto es requerido';
        }

        if (!isset($datos['cantidadProducto']) || $datos['cantidadProducto'] <= 0) {
            $errores[] = 'La cantidad debe ser mayor que 0';
        }
        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }
}