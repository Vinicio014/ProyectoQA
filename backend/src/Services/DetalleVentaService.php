<?php

namespace Proyecto\Services;

use Proyecto\Entities\DetalleVentaEntity;
use Proyecto\Repositories\DetalleVentaRepository;
use Proyecto\Repositories\VentaRepository;
use Proyecto\Repositories\ProductoRepository;
use Exception;

/**
 * Servicio para la gestión de detalles de venta
 * Contiene la lógica de negocio para los items de cada venta
 */
class DetalleVentaService
{
    private DetalleVentaRepository $detalleVentaRepository;
    private VentaRepository $ventaRepository;
    private ProductoRepository $productoRepository;

    public function __construct(
        DetalleVentaRepository $detalleVentaRepository,
        VentaRepository $ventaRepository,
        ProductoRepository $productoRepository
    ) {
        $this->detalleVentaRepository = $detalleVentaRepository;
        $this->ventaRepository = $ventaRepository;
        $this->productoRepository = $productoRepository;
    }

    /**
     * Crear detalle de venta
     */
    public function crearDetalleVenta(array $datosDetalle): array
    {
        try {
            // Validar que la venta exista
            $venta = $this->ventaRepository->obtenerPorId($datosDetalle['venta_id']);
            if (!$venta) {
                return [
                    'exito' => false,
                    'mensaje' => 'Venta no encontrada',
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

            $detalle = new DetalleVentaEntity();
            $detalle->setVentaId($datosDetalle['venta_id']);
            $detalle->setProductoId($datosDetalle['producto_id']);
            $detalle->setCantidad($datosDetalle['cantidad']);
            $detalle->setPrecioUnitario($datosDetalle['precio_unitario'] ?? $producto->getPrecio());
            $detalle->setSubtotal($detalle->getPrecioUnitario() * $detalle->getCantidad());

            $detalleCreado = $this->detalleVentaRepository->crear($detalle);

            return [
                'exito' => true,
                'mensaje' => 'Detalle de venta creado exitosamente',
                'datos' => $this->formatearDetalleVenta($detalleCreado)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al crear detalle de venta: ' . $e->getMessage(),
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
            $detalle = $this->detalleVentaRepository->obtenerPorId($id);

            if (!$detalle) {
                return [
                    'exito' => false,
                    'mensaje' => 'Detalle de venta no encontrado',
                    'datos' => null
                ];
            }

            return [
                'exito' => true,
                'mensaje' => 'Detalle encontrado',
                'datos' => $this->formatearDetalleVenta($detalle)
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
     * Obtener detalles por venta
     */
    public function obtenerDetallesPorVenta(int $ventaId): array
    {
        try {
            $venta = $this->ventaRepository->obtenerPorId($ventaId);
            if (!$venta) {
                return [
                    'exito' => false,
                    'mensaje' => 'Venta no encontrada',
                    'datos' => []
                ];
            }

            $detalles = $this->detalleVentaRepository->obtenerPorVenta($ventaId);
            
            return [
                'exito' => true,
                'mensaje' => 'Detalles de venta obtenidos',
                'datos' => array_map([$this, 'formatearDetalleVenta'], $detalles)
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
     * Actualizar detalle de venta
     */
    public function actualizarDetalleVenta(int $id, array $datosDetalle): array
    {
        try {
            $detalle = $this->detalleVentaRepository->obtenerPorId($id);

            if (!$detalle) {
                return [
                    'exito' => false,
                    'mensaje' => 'Detalle de venta no encontrado',
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

            $detalleActualizado = $this->detalleVentaRepository->actualizar($detalle);

            return [
                'exito' => true,
                'mensaje' => 'Detalle de venta actualizado exitosamente',
                'datos' => $this->formatearDetalleVenta($detalleActualizado)
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
     * Eliminar detalle de venta
     */
    public function eliminarDetalleVenta(int $id): array
    {
        try {
            $detalle = $this->detalleVentaRepository->obtenerPorId($id);

            if (!$detalle) {
                return [
                    'exito' => false,
                    'mensaje' => 'Detalle de venta no encontrado',
                    'datos' => null
                ];
            }

            // Verificar que la venta no esté completada o anulada
            $venta = $this->ventaRepository->obtenerPorId($detalle->getVentaId());
            if ($venta && in_array($venta->getEstado(), ['COMPLETADA', 'ANULADA'])) {
                return [
                    'exito' => false,
                    'mensaje' => 'No se puede eliminar detalle de una venta completada o anulada',
                    'datos' => null
                ];
            }

            $resultado = $this->detalleVentaRepository->eliminar($id);

            if ($resultado) {
                return [
                    'exito' => true,
                    'mensaje' => 'Detalle de venta eliminado exitosamente',
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
     * Obtener productos más vendidos
     */
    public function obtenerProductosMasVendidos(int $limite = 10, array $filtros = []): array
    {
        try {
            $productos = $this->detalleVentaRepository->obtenerProductosMasVendidos($limite, $filtros);
            
            return [
                'exito' => true,
                'mensaje' => 'Productos más vendidos obtenidos',
                'datos' => $productos
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener productos más vendidos: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Obtener estadísticas de productos vendidos
     */
    public function obtenerEstadisticasProductosVendidos(array $filtros = []): array
    {
        try {
            $estadisticas = [
                'total_items_vendidos' => $this->detalleVentaRepository->contarTotalItems($filtros),
                'cantidad_total_vendida' => $this->detalleVentaRepository->calcularCantidadTotal($filtros),
                'monto_total_vendido' => $this->detalleVentaRepository->calcularMontoTotal($filtros),
                'productos_mas_vendidos' => $this->detalleVentaRepository->obtenerProductosMasVendidos(5, $filtros),
                'productos_menos_vendidos' => $this->detalleVentaRepository->obtenerProductosMenosVendidos(5, $filtros),
                'promedio_cantidad_por_venta' => $this->detalleVentaRepository->calcularPromedioCantidad($filtros),
                'promedio_precio_unitario' => $this->detalleVentaRepository->calcularPromedioPrecio($filtros)
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
     * Obtener ventas de un producto específico
     */
    public function obtenerVentasProducto(int $productoId, array $filtros = []): array
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

            $detalles = $this->detalleVentaRepository->obtenerPorProducto($productoId, $filtros);
            
            return [
                'exito' => true,
                'mensaje' => 'Ventas del producto obtenidas',
                'datos' => [
                    'producto' => [
                        'id' => $producto->getId(),
                        'nombre' => $producto->getNombre(),
                        'codigo' => $producto->getCodigo()
                    ],
                    'ventas' => array_map([$this, 'formatearDetalleVenta'], $detalles),
                    'resumen' => [
                        'total_vendido' => array_sum(array_map(fn($d) => $d->getCantidad(), $detalles)),
                        'monto_total' => array_sum(array_map(fn($d) => $d->getSubtotal(), $detalles)),
                        'numero_ventas' => count($detalles)
                    ]
                ]
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener ventas del producto: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Calcular margen de ganancia por detalle
     */
    public function calcularMargenGanancia(int $detalleId): array
    {
        try {
            $detalle = $this->detalleVentaRepository->obtenerPorId($detalleId);
            
            if (!$detalle) {
                return [
                    'exito' => false,
                    'mensaje' => 'Detalle no encontrado',
                    'datos' => null
                ];
            }

            $producto = $this->productoRepository->obtenerPorId($detalle->getProductoId());
            
            if (!$producto || $producto->getCosto() <= 0) {
                return [
                    'exito' => false,
                    'mensaje' => 'No se puede calcular margen sin costo del producto',
                    'datos' => null
                ];
            }

            $costoTotal = $producto->getCosto() * $detalle->getCantidad();
            $ventaTotal = $detalle->getSubtotal();
            $ganancia = $ventaTotal - $costoTotal;
            $margenPorcentaje = ($ganancia / $costoTotal) * 100;

            return [
                'exito' => true,
                'mensaje' => 'Margen calculado exitosamente',
                'datos' => [
                    'detalle_id' => $detalleId,
                    'costo_total' => $costoTotal,
                    'venta_total' => $ventaTotal,
                    'ganancia' => $ganancia,
                    'margen_porcentaje' => round($margenPorcentaje, 2)
                ]
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al calcular margen: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Validar datos de detalle de venta
     */
    public function validarDatosDetalle(array $datos): array
    {
        $errores = [];

        if (empty($datos['venta_id']) || $datos['venta_id'] <= 0) {
            $errores[] = 'La venta es requerida';
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
     * Formatear detalle de venta con información completa
     */
    private function formatearDetalleVenta(DetalleVentaEntity $detalle): array
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
                    'costo' => $producto->getCosto()
                ];
                
                // Calcular margen si hay costo
                if ($producto->getCosto() > 0) {
                    $costoTotal = $producto->getCosto() * $detalle->getCantidad();
                    $ganancia = $detalle->getSubtotal() - $costoTotal;
                    $datos['margen_ganancia'] = ($ganancia / $costoTotal) * 100;
                    $datos['ganancia_total'] = $ganancia;
                }
            }
        } catch (Exception $e) {
            $datos['producto'] = null;
        }

        // Agregar información básica de la venta
        try {
            $venta = $this->ventaRepository->obtenerPorId($detalle->getVentaId());
            if ($venta) {
                $datos['venta'] = [
                    'id' => $venta->getId(),
                    'fecha' => $venta->getFecha()->format('Y-m-d'),
                    'estado' => $venta->getEstado(),
                    'cliente_id' => $venta->getClienteId()
                ];
            }
        } catch (Exception $e) {
            $datos['venta'] = null;
        }

        return $datos;
    }
}