<?php

namespace App\Services;

use App\Entities\DetalleVentaEntity;
use App\Repositories\DetalleVentaRepository;
use App\Repositories\VentaRepository;
use App\Repositories\ProductoRepository;
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
            $venta = $this->ventaRepository->findById($datosDetalle['idVenta']);
            if (!$venta) {
                return [
                    'exito' => false,
                    'mensaje' => 'Venta no encontrada',
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
            if ($datosDetalle['cantidad'] <= 0) {
                return [
                    'exito' => false,
                    'mensaje' => 'La cantidad debe ser mayor que 0',
                    'datos' => null
                ];
            }

            // Validar stock disponible
            if ($producto->getStock() < $datosDetalle['cantidad']) {
                return [
                    'exito' => false,
                    'mensaje' => 'Stock insuficiente. Disponible: ' . $producto->getStock(),
                    'datos' => null
                ];
            }

            $detalle = new DetalleVentaEntity();
            $detalle->setIdVenta($datosDetalle['idVenta']);
            $detalle->setIdProducto($datosDetalle['idProducto']);
            $detalle->setCantidad($datosDetalle['cantidad']);
            
            // Calcular subtotal (cantidad * precio del producto)
            $subtotal = $datosDetalle['cantidad'] * $producto->getPrecioUnitario();
            $detalle->setSubTotal($subtotal);

            $detalleCreado = $this->detalleVentaRepository->create($detalle);

            // Reducir stock del producto
            if ($detalleCreado) {
                $this->productoRepository->reduceStock(
                    $datosDetalle['idProducto'], 
                    $datosDetalle['cantidad']
                );
            }

            return [
                'exito' => true,
                'mensaje' => 'Detalle de venta creado exitosamente',
                'datos' => $detalleCreado ? $detalleCreado->toArray() : null
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
            $detalle = $this->detalleVentaRepository->findById($id);

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
     * Obtener detalles por venta
     */
    public function obtenerDetallesPorVenta(int $ventaId): array
    {
        try {
            $venta = $this->ventaRepository->findById($ventaId);
            if (!$venta) {
                return [
                    'exito' => false,
                    'mensaje' => 'Venta no encontrada',
                    'datos' => []
                ];
            }

            $detalles = $this->detalleVentaRepository->findByVenta($ventaId);
            
            return [
                'exito' => true,
                'mensaje' => 'Detalles de venta obtenidos',
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
     * Actualizar detalle de venta
     */
    public function actualizarDetalleVenta(int $id, array $datosDetalle): array
    {
        try {
            $detalle = $this->detalleVentaRepository->findById($id);

            if (!$detalle) {
                return [
                    'exito' => false,
                    'mensaje' => 'Detalle de venta no encontrado',
                    'datos' => null
                ];
            }

            $cantidadAnterior = $detalle->getCantidad();

            // Actualizar cantidad
            if (isset($datosDetalle['cantidad'])) {
                if ($datosDetalle['cantidad'] <= 0) {
                    return [
                        'exito' => false,
                        'mensaje' => 'La cantidad debe ser mayor que 0',
                        'datos' => null
                    ];
                }
                
                // Validar stock
                $producto = $this->productoRepository->findById($detalle->getIdProducto());
                $diferencia = $datosDetalle['cantidad'] - $cantidadAnterior;
                
                if ($diferencia > 0 && $producto->getStock() < $diferencia) {
                    return [
                        'exito' => false,
                        'mensaje' => 'Stock insuficiente para el incremento',
                        'datos' => null
                    ];
                }
                
                $detalle->setCantidad($datosDetalle['cantidad']);
                
                // Recalcular subtotal
                $detalle->setSubTotal($datosDetalle['cantidad'] * $producto->getPrecioUnitario());
            }

            $resultado = $this->detalleVentaRepository->update($detalle);

            if ($resultado) {
                // Ajustar stock si cambió la cantidad
                if (isset($datosDetalle['cantidad'])) {
                    $diferencia = $datosDetalle['cantidad'] - $cantidadAnterior;
                    if ($diferencia > 0) {
                        $this->productoRepository->reduceStock($detalle->getIdProducto(), $diferencia);
                    } elseif ($diferencia < 0) {
                        $this->productoRepository->increaseStock($detalle->getIdProducto(), abs($diferencia));
                    }
                }

                $detalleActualizado = $this->detalleVentaRepository->findById($id);
                return [
                    'exito' => true,
                    'mensaje' => 'Detalle de venta actualizado exitosamente',
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
     * Eliminar detalle de venta
     */
    public function eliminarDetalleVenta(int $id): array
    {
        try {
            $detalle = $this->detalleVentaRepository->findById($id);

            if (!$detalle) {
                return [
                    'exito' => false,
                    'mensaje' => 'Detalle de venta no encontrado',
                    'datos' => null
                ];
            }

            // Devolver stock antes de eliminar
            $this->productoRepository->increaseStock(
                $detalle->getIdProducto(), 
                $detalle->getCantidad()
            );

            $resultado = $this->detalleVentaRepository->delete($id);

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
    public function obtenerProductosMasVendidos(int $limite = 10): array
    {
        try {
            $productos = $this->detalleVentaRepository->getMostSoldProducts($limite);
            
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
     * Validar datos de detalle de venta
     */
    public function validarDatosDetalle(array $datos): array
    {
        $errores = [];

        if (empty($datos['idVenta']) || $datos['idVenta'] <= 0) {
            $errores[] = 'La venta es requerida';
        }

        if (empty($datos['idProducto']) || $datos['idProducto'] <= 0) {
            $errores[] = 'El producto es requerido';
        }

        if (!isset($datos['cantidad']) || $datos['cantidad'] <= 0) {
            $errores[] = 'La cantidad debe ser mayor que 0';
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }
}