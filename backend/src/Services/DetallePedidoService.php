<?php

namespace Proyecto\Services;

use Proyecto\Entities\DetallePedidoEntity;
use Proyecto\Repositories\DetallePedidoRepository;
use Proyecto\Repositories\PedidoRepository;
use Proyecto\Repositories\ProductoRepository;
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
            $pedido = $this->pedidoRepository->obtenerPorId($datosDetalle['pedido_id']);
            if (!$pedido) {
                return [
                    'exito' => false,
                    'mensaje' => 'Pedido no encontrado',
                    'datos' => null
                ];
            }

            // Validar que el producto exista
            $producto = $this->productoRepository->obtenerPorId($datosDetalle['producto_id']);
            if (!$producto) {
                return [
                    'exito' => false,
                    'mensaje' => 'Producto no encontrado',
                    'datos' => null
                ];
            }

            // Validar cantidad
            if ($datosDetalle['cantidad'] <= 0) {
                return [
                    'exito' => false,
                    'mensaje' => 'La cantidad debe ser mayor que 0',
                    'datos' => null
                ];
            }

            $detalle = new DetallePedidoEntity();
            $detalle->setPedidoId($datosDetalle['pedido_id']);
            $detalle->setProductoId($datosDetalle['producto_id']);
            $detalle->setCantidad($datosDetalle['cantidad']);
            $detalle->setPrecioUnitario($datosDetalle['precio_unitario'] ?? $producto->getPrecio());
            $detalle->setSubtotal($detalle->getPrecioUnitario() * $detalle->getCantidad());

            $detalleCreado = $this->detallePedidoRepository->crear($detalle);

            return [
                'exito' => true,
                'mensaje' => 'Detalle de pedido creado exitosamente',
                'datos' => $this->formatearDetallePedido($detalleCreado)
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
            $detalle = $this->detallePedidoRepository->obtenerPorId($id);

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
                'datos' => $this->formatearDetallePedido($detalle)
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
            $pedido = $this->pedidoRepository->obtenerPorId($pedidoId);
            if (!$pedido) {
                return [
                    'exito' => false,
                    'mensaje' => 'Pedido no encontrado',
                    'datos' => []
                ];
            }

            $detalles = $this->detallePedidoRepository->obtenerPorPedido($pedidoId);
            
            return [
                'exito' => true,
                'mensaje' => 'Detalles de pedido obtenidos',
                'datos' => array_map([$this, 'formatearDetallePedido'], $detalles)
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
            $detalle = $this->detallePedidoRepository->obtenerPorId($id);

            if (!$detalle) {
                return [
                    'exito' => false,
                    'mensaje' => 'Detalle de pedido no encontrado',
                    'datos' => null
                ];
            }

            // Verificar que el pedido no esté completado
            $pedido = $this->pedidoRepository->obtenerPorId($detalle->getPedidoId());
            if ($pedido && $pedido->getEstado() === 'COMPLETADO') {
                return [
                    'exito' => false,
                    'mensaje' => 'No se puede modificar detalle de un pedido completado',
                    'datos' => null
                ];
            }

            // Actualizar campos
            if (isset($datosDetalle['cantidad'])) {
                if ($datosDetalle['cantidad'] <= 0) {
                    return [
                        'exito' => false,
                        'mensaje' => 'La cantidad debe ser mayor que 0',
                        'datos' => null
                    ];
                }
                $detalle->setCantidad($datosDetalle['cantidad']);
            }

            if (isset($datosDetalle['precio_unitario'])) {
                if ($datosDetalle['precio_unitario'] <= 0) {
                    return [
                        'exito' => false,
                        'mensaje' => 'El precio unitario debe ser mayor que 0',
                        'datos' => null
                    ];
                }
                $detalle->setPrecioUnitario($datosDetalle['precio_unitario']);
            }

            // Recalcular subtotal
            $detalle->setSubtotal($detalle->getPrecioUnitario() * $detalle->getCantidad());

            $detalleActualizado = $this->detallePedidoRepository->actualizar($detalle);

            return [
                'exito' => true,
                'mensaje' => 'Detalle de pedido actualizado exitosamente',
                'datos' => $this->formatearDetallePedido($detalleActualizado)
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
            $detalle = $this->detallePedidoRepository->obtenerPorId($id);

            if (!$detalle) {
                return [
                    'exito' => false,
                    'mensaje' => 'Detalle de pedido no encontrado',
                    'datos' => null
                ];
            }

            // Verificar que el pedido no esté completado
            $pedido = $this->pedidoRepository->obtenerPorId($detalle->getPedidoId());
            if ($pedido && $pedido->getEstado() === 'COMPLETADO') {
                return [
                    'exito' => false,
                    'mensaje' => 'No se puede eliminar detalle de un pedido completado',
                    'datos' => null
                ];
            }

            $resultado = $this->detallePedidoRepository->eliminar($id);

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
     * Obtener productos más pedidos
     */
    public function obtenerProductosMasPedidos(int $limite = 10, array $filtros = []): array
    {
        try {
            $productos = $this->detallePedidoRepository->obtenerProductosMasPedidos($limite, $filtros);
            
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
     * Obtener estadísticas de productos pedidos
     */
    public function obtenerEstadisticasProductosPedidos(array $filtros = []): array
    {
        try {
            $estadisticas = [
                'total_items_pedidos' => $this->detallePedidoRepository->contarTotalItems($filtros),
                'cantidad_total_pedida' => $this->detallePedidoRepository->calcularCantidadTotal($filtros),
                'monto_total_pedidos' => $this->detallePedidoRepository->calcularMontoTotal($filtros),
                'productos_mas_pedidos' => $this->detallePedidoRepository->obtenerProductosMasPedidos(5, $filtros),
                'productos_menos_pedidos' => $this->detallePedidoRepository->obtenerProductosMenosPedidos(5, $filtros),
                'promedio_cantidad_por_pedido' => $this->detallePedidoRepository->calcularPromedioCantidad($filtros),
                'promedio_precio_unitario' => $this->detallePedidoRepository->calcularPromedioPrecio($filtros)
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

    /**
     * Obtener pedidos de un producto específico
     */
    public function obtenerPedidosProducto(int $productoId, array $filtros = []): array
    {
        try {
            $producto = $this->productoRepository->obtenerPorId($productoId);
            if (!$producto) {
                return [
                    'exito' => false,
                    'mensaje' => 'Producto no encontrado',
                    'datos' => []
                ];
            }

            $detalles = $this->detallePedidoRepository->obtenerPorProducto($productoId, $filtros);
            
            return [
                'exito' => true,
                'mensaje' => 'Pedidos del producto obtenidos',
                'datos' => [
                    'producto' => [
                        'id' => $producto->getId(),
                        'nombre' => $producto->getNombre(),
                        'codigo' => $producto->getCodigo()
                    ],
                    'pedidos' => array_map([$this, 'formatearDetallePedido'], $detalles),
                    'resumen' => [
                        'total_pedido' => array_sum(array_map(fn($d) => $d->getCantidad(), $detalles)),
                        'monto_total' => array_sum(array_map(fn($d) => $d->getSubtotal(), $detalles)),
                        'numero_pedidos' => count($detalles)
                    ]
                ]
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener pedidos del producto: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Calcular demanda futura por producto
     */
    public function calcularDemandaFutura(int $productoId, int $diasFuturos = 30): array
    {
        try {
            $producto = $this->productoRepository->obtenerPorId($productoId);
            if (!$producto) {
                return [
                    'exito' => false,
                    'mensaje' => 'Producto no encontrado',
                    'datos' => null
                ];
            }

            $fechaLimite = new \DateTime();
            $fechaLimite->add(new \DateInterval("P{$diasFuturos}D"));

            $pedidosPendientes = $this->detallePedidoRepository->obtenerPedidosPendientes($productoId, $fechaLimite);
            $cantidadPendiente = array_sum(array_map(fn($d) => $d->getCantidad(), $pedidosPendientes));

            return [
                'exito' => true,
                'mensaje' => 'Demanda futura calculada',
                'datos' => [
                    'producto_id' => $productoId,
                    'producto_nombre' => $producto->getNombre(),
                    'stock_actual' => $producto->getStock(),
                    'cantidad_pedida_pendiente' => $cantidadPendiente,
                    'stock_disponible_futuro' => $producto->getStock() - $cantidadPendiente,
                    'necesita_restock' => ($producto->getStock() - $cantidadPendiente) <= $producto->getStockMinimo(),
                    'pedidos_pendientes' => count($pedidosPendientes),
                    'dias_proyeccion' => $diasFuturos
                ]
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al calcular demanda futura: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Obtener resumen de pedidos por estado
     */
    public function obtenerResumenPorEstado(array $filtros = []): array
    {
        try {
            $resumen = [
                'pendientes' => $this->detallePedidoRepository->obtenerPorEstadoPedido('PENDIENTE', $filtros),
                'en_proceso' => $this->detallePedidoRepository->obtenerPorEstadoPedido('EN_PROCESO', $filtros),
                'completados' => $this->detallePedidoRepository->obtenerPorEstadoPedido('COMPLETADO', $filtros),
                'cancelados' => $this->detallePedidoRepository->obtenerPorEstadoPedido('CANCELADO', $filtros)
            ];

            $estadisticas = [];
            foreach ($resumen as $estado => $detalles) {
                $estadisticas[$estado] = [
                    'cantidad_items' => count($detalles),
                    'cantidad_productos' => array_sum(array_map(fn($d) => $d->getCantidad(), $detalles)),
                    'monto_total' => array_sum(array_map(fn($d) => $d->getSubtotal(), $detalles))
                ];
            }

            return [
                'exito' => true,
                'mensaje' => 'Resumen por estado obtenido',
                'datos' => $estadisticas
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener resumen por estado: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Verificar disponibilidad para pedido
     */
    public function verificarDisponibilidadPedido(int $pedidoId): array
    {
        try {
            $pedido = $this->pedidoRepository->obtenerPorId($pedidoId);
            if (!$pedido) {
                return [
                    'exito' => false,
                    'mensaje' => 'Pedido no encontrado',
                    'datos' => null
                ];
            }

            $detalles = $this->detallePedidoRepository->obtenerPorPedido($pedidoId);
            $disponibilidad = [];
            $todoDisponible = true;

            foreach ($detalles as $detalle) {
                $producto = $this->productoRepository->obtenerPorId($detalle->getProductoId());
                $disponible = $producto && $producto->getStock() >= $detalle->getCantidad();
                
                if (!$disponible) {
                    $todoDisponible = false;
                }

                $disponibilidad[] = [
                    'detalle_id' => $detalle->getId(),
                    'producto_id' => $producto ? $producto->getId() : null,
                    'producto_nombre' => $producto ? $producto->getNombre() : 'Producto no encontrado',
                    'cantidad_pedida' => $detalle->getCantidad(),
                    'stock_disponible' => $producto ? $producto->getStock() : 0,
                    'disponible' => $disponible,
                    'faltante' => $disponible ? 0 : $detalle->getCantidad() - ($producto ? $producto->getStock() : 0)
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
     * Validar datos de detalle de pedido
     */
    public function validarDatosDetalle(array $datos): array
    {
        $errores = [];

        if (empty($datos['pedido_id']) || $datos['pedido_id'] <= 0) {
            $errores[] = 'El pedido es requerido';
        }

        if (empty($datos['producto_id']) || $datos['producto_id'] <= 0) {
            $errores[] = 'El producto es requerido';
        }

        if (!isset($datos['cantidad']) || $datos['cantidad'] <= 0) {
            $errores[] = 'La cantidad debe ser mayor que 0';
        }

        if (isset($datos['precio_unitario']) && $datos['precio_unitario'] <= 0) {
            $errores[] = 'El precio unitario debe ser mayor que 0';
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }

    /**
     * Formatear detalle de pedido con información completa
     */
    private function formatearDetallePedido(DetallePedidoEntity $detalle): array
    {
        $datos = $detalle->toArray();
        
        // Agregar información del producto
        try {
            $producto = $this->productoRepository->obtenerPorId($detalle->getProductoId());
            if ($producto) {
                $datos['producto'] = [
                    'id' => $producto->getId(),
                    'nombre' => $producto->getNombre(),
                    'codigo' => $producto->getCodigo(),
                    'marca' => $producto->getMarca(),
                    'color' => $producto->getColor(),
                    'stock_actual' => $producto->getStock(),
                    'precio_actual' => $producto->getPrecio()
                ];
                
                // Verificar disponibilidad
                $datos['stock_suficiente'] = $producto->getStock() >= $detalle->getCantidad();
                $datos['faltante'] = $datos['stock_suficiente'] ? 0 : $detalle->getCantidad() - $producto->getStock();
            }
        } catch (Exception $e) {
            $datos['producto'] = null;
        }

        // Agregar información básica del pedido
        try {
            $pedido = $this->pedidoRepository->obtenerPorId($detalle->getPedidoId());
            if ($pedido) {
                $datos['pedido'] = [
                    'id' => $pedido->getId(),
                    'fecha_pedido' => $pedido->getFechaPedido()->format('Y-m-d'),
                    'fecha_entrega' => $pedido->getFechaEntrega()->format('Y-m-d'),
                    'estado' => $pedido->getEstado(),
                    'cliente_id' => $pedido->getClienteId()
                ];
            }
        } catch (Exception $e) {
            $datos['pedido'] = null;
        }

        return $datos;
    }
}