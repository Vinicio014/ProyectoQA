<?php

namespace Proyecto\Services;

use Proyecto\Entities\VentaEntity;
use Proyecto\Entities\DetalleVentaEntity;
use Proyecto\Repositories\VentaRepository;
use Proyecto\Repositories\DetalleVentaRepository;
use Proyecto\Repositories\ClienteRepository;
use Proyecto\Repositories\ProductoRepository;
use Proyecto\Services\ProductoService;
use Exception;

/**
 * Servicio para la gestión de ventas
 * Contiene la lógica de negocio para ventas y detalles de venta
 */
class VentaService
{
    private VentaRepository $ventaRepository;
    private DetalleVentaRepository $detalleVentaRepository;
    private ClienteRepository $clienteRepository;
    private ProductoRepository $productoRepository;
    private ProductoService $productoService;

    public function __construct(
        VentaRepository $ventaRepository,
        DetalleVentaRepository $detalleVentaRepository,
        ClienteRepository $clienteRepository,
        ProductoRepository $productoRepository,
        ProductoService $productoService
    ) {
        $this->ventaRepository = $ventaRepository;
        $this->detalleVentaRepository = $detalleVentaRepository;
        $this->clienteRepository = $clienteRepository;
        $this->productoRepository = $productoRepository;
        $this->productoService = $productoService;
    }

    /**
     * Crear nueva venta
     */
    public function crearVenta(array $datosVenta): array
    {
        try {
            // Validar cliente
            $cliente = $this->clienteRepository->obtenerPorId($datosVenta['cliente_id']);
            if (!$cliente) {
                return [
                    'exito' => false,
                    'mensaje' => 'Cliente no encontrado',
                    'datos' => null
                ];
            }

            // Validar productos y stock
            $validacionProductos = $this->validarProductosVenta($datosVenta['productos']);
            if (!$validacionProductos['exito']) {
                return $validacionProductos;
            }

            // Calcular totales
            $totales = $this->calcularTotalesVenta($datosVenta['productos']);

            // Crear venta
            $venta = new VentaEntity();
            $venta->setClienteId($datosVenta['cliente_id']);
            $venta->setFecha(new \DateTime($datosVenta['fecha'] ?? 'now'));
            $venta->setSubtotal($totales['subtotal']);
            $venta->setImpuesto($totales['impuesto']);
            $venta->setTotal($totales['total']);
            $venta->setDescuento($datosVenta['descuento'] ?? 0);
            $venta->setMetodoPago($datosVenta['metodo_pago'] ?? 'EFECTIVO');
            $venta->setObservaciones($datosVenta['observaciones'] ?? '');
            $venta->setEstado($datosVenta['estado'] ?? 'COMPLETADA');

            // Iniciar transacción
            $this->ventaRepository->iniciarTransaccion();

            try {
                $ventaCreada = $this->ventaRepository->crear($venta);

                // Crear detalles de venta
                foreach ($datosVenta['productos'] as $itemProducto) {
                    $this->crearDetalleVenta($ventaCreada->getId(), $itemProducto);
                    
                    // Actualizar stock del producto
                    $this->productoService->actualizarStock(
                        $itemProducto['producto_id'],
                        $itemProducto['cantidad'],
                        'SUBTRACT'
                    );
                }

                $this->ventaRepository->confirmarTransaccion();

                return [
                    'exito' => true,
                    'mensaje' => 'Venta creada exitosamente',
                    'datos' => $this->formatearVenta($ventaCreada)
                ];

            } catch (Exception $e) {
                $this->ventaRepository->revertirTransaccion();
                throw $e;
            }

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al crear venta: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Obtener venta por ID
     */
    public function obtenerVentaPorId(int $id): array
    {
        try {
            $venta = $this->ventaRepository->obtenerPorId($id);

            if (!$venta) {
                return [
                    'exito' => false,
                    'mensaje' => 'Venta no encontrada',
                    'datos' => null
                ];
            }

            return [
                'exito' => true,
                'mensaje' => 'Venta encontrada',
                'datos' => $this->formatearVenta($venta)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener venta: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Listar ventas con filtros
     */
    public function listarVentas(array $filtros = []): array
    {
        try {
            $ventas = $this->ventaRepository->listarConFiltros($filtros);
            
            return [
                'exito' => true,
                'mensaje' => 'Ventas obtenidas exitosamente',
                'datos' => array_map([$this, 'formatearVenta'], $ventas)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al listar ventas: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Obtener ventas por cliente
     */
    public function obtenerVentasPorCliente(int $clienteId): array
    {
        try {
            $cliente = $this->clienteRepository->obtenerPorId($clienteId);
            if (!$cliente) {
                return [
                    'exito' => false,
                    'mensaje' => 'Cliente no encontrado',
                    'datos' => []
                ];
            }

            $ventas = $this->ventaRepository->obtenerPorCliente($clienteId);
            
            return [
                'exito' => true,
                'mensaje' => 'Ventas del cliente obtenidas',
                'datos' => array_map([$this, 'formatearVenta'], $ventas)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener ventas del cliente: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Obtener ventas por rango de fechas
     */
    public function obtenerVentasPorFechas(\DateTime $fechaInicio, \DateTime $fechaFin): array
    {
        try {
            $ventas = $this->ventaRepository->obtenerPorRangoFechas($fechaInicio, $fechaFin);
            
            return [
                'exito' => true,
                'mensaje' => 'Ventas por fechas obtenidas',
                'datos' => array_map([$this, 'formatearVenta'], $ventas)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener ventas por fechas: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Anular venta
     */
    public function anularVenta(int $id, string $motivo = ''): array
    {
        try {
            $venta = $this->ventaRepository->obtenerPorId($id);

            if (!$venta) {
                return [
                    'exito' => false,
                    'mensaje' => 'Venta no encontrada',
                    'datos' => null
                ];
            }

            if ($venta->getEstado() === 'ANULADA') {
                return [
                    'exito' => false,
                    'mensaje' => 'La venta ya está anulada',
                    'datos' => null
                ];
            }

            // Iniciar transacción
            $this->ventaRepository->iniciarTransaccion();

            try {
                // Devolver stock de los productos
                $detalles = $this->detalleVentaRepository->obtenerPorVenta($id);
                foreach ($detalles as $detalle) {
                    $this->productoService->actualizarStock(
                        $detalle->getProductoId(),
                        $detalle->getCantidad(),
                        'ADD'
                    );
                }

                // Anular venta
                $venta->setEstado('ANULADA');
                $venta->setObservaciones($venta->getObservaciones() . ' | ANULADA: ' . $motivo);
                $ventaActualizada = $this->ventaRepository->actualizar($venta);

                $this->ventaRepository->confirmarTransaccion();

                return [
                    'exito' => true,
                    'mensaje' => 'Venta anulada exitosamente',
                    'datos' => $this->formatearVenta($ventaActualizada)
                ];

            } catch (Exception $e) {
                $this->ventaRepository->revertirTransaccion();
                throw $e;
            }

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al anular venta: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Obtener estadísticas de ventas
     */
    public function obtenerEstadisticasVentas(array $filtros = []): array
    {
        try {
            $estadisticas = [
                'total_ventas' => $this->ventaRepository->contarVentas($filtros),
                'monto_total' => $this->ventaRepository->calcularMontoTotal($filtros),
                'venta_promedio' => $this->ventaRepository->calcularPromedioVentas($filtros),
                'ventas_por_mes' => $this->ventaRepository->obtenerVentasPorMes($filtros),
                'productos_mas_vendidos' => $this->detalleVentaRepository->obtenerProductosMasVendidos(10),
                'clientes_frecuentes' => $this->ventaRepository->obtenerClientesFrecuentes(10),
                'metodos_pago' => $this->ventaRepository->obtenerEstadisticasMetodosPago($filtros)
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
     * Generar reporte de ventas
     */
    public function generarReporteVentas(array $filtros = []): array
    {
        try {
            $ventas = $this->ventaRepository->listarConFiltros($filtros);
            
            $reporte = [
                'periodo' => [
                    'fecha_inicio' => $filtros['fecha_inicio'] ?? null,
                    'fecha_fin' => $filtros['fecha_fin'] ?? null
                ],
                'resumen' => [
                    'total_ventas' => count($ventas),
                    'monto_total' => array_sum(array_map(fn($v) => $v->getTotal(), $ventas)),
                    'subtotal' => array_sum(array_map(fn($v) => $v->getSubtotal(), $ventas)),
                    'impuestos' => array_sum(array_map(fn($v) => $v->getImpuesto(), $ventas)),
                    'descuentos' => array_sum(array_map(fn($v) => $v->getDescuento(), $ventas))
                ],
                'ventas' => array_map([$this, 'formatearVenta'], $ventas)
            ];

            return [
                'exito' => true,
                'mensaje' => 'Reporte generado exitosamente',
                'datos' => $reporte
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al generar reporte: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Validar productos para la venta
     */
    private function validarProductosVenta(array $productos): array
    {
        if (empty($productos)) {
            return [
                'exito' => false,
                'mensaje' => 'Debe agregar al menos un producto',
                'datos' => null
            ];
        }

        foreach ($productos as $item) {
            $producto = $this->productoRepository->obtenerPorId($item['producto_id']);
            
            if (!$producto) {
                return [
                    'exito' => false,
                    'mensaje' => 'Producto no encontrado: ID ' . $item['producto_id'],
                    'datos' => null
                ];
            }

            if ($producto->getEstado() !== 'ACTIVO') {
                return [
                    'exito' => false,
                    'mensaje' => 'Producto inactivo: ' . $producto->getNombre(),
                    'datos' => null
                ];
            }

            if ($producto->getStock() < $item['cantidad']) {
                return [
                    'exito' => false,
                    'mensaje' => 'Stock insuficiente para: ' . $producto->getNombre() . ' (Disponible: ' . $producto->getStock() . ')',
                    'datos' => null
                ];
            }
        }

        return ['exito' => true];
    }

    /**
     * Calcular totales de la venta
     */
    private function calcularTotalesVenta(array $productos): array
    {
        $subtotal = 0;

        foreach ($productos as $item) {
            $producto = $this->productoRepository->obtenerPorId($item['producto_id']);
            $precioUnitario = $item['precio_unitario'] ?? $producto->getPrecio();
            $subtotal += $precioUnitario * $item['cantidad'];
        }

        $impuesto = $subtotal * 0.19; // 19% IVA por defecto
        $total = $subtotal + $impuesto;

        return [
            'subtotal' => $subtotal,
            'impuesto' => $impuesto,
            'total' => $total
        ];
    }

    /**
     * Crear detalle de venta
     */
    private function crearDetalleVenta(int $ventaId, array $itemProducto): DetalleVentaEntity
    {
        $producto = $this->productoRepository->obtenerPorId($itemProducto['producto_id']);
        
        $detalle = new DetalleVentaEntity();
        $detalle->setVentaId($ventaId);
        $detalle->setProductoId($itemProducto['producto_id']);
        $detalle->setCantidad($itemProducto['cantidad']);
        $detalle->setPrecioUnitario($itemProducto['precio_unitario'] ?? $producto->getPrecio());
        $detalle->setSubtotal($detalle->getPrecioUnitario() * $detalle->getCantidad());

        return $this->detalleVentaRepository->crear($detalle);
    }

    /**
     * Formatear venta con información completa
     */
    private function formatearVenta(VentaEntity $venta): array
    {
        $datos = $venta->toArray();
        
        // Agregar información del cliente
        try {
            $cliente = $this->clienteRepository->obtenerPorId($venta->getClienteId());
            if ($cliente) {
                $datos['cliente'] = [
                    'id' => $cliente->getId(),
                    'nombre' => $cliente->getNombre() . ' ' . $cliente->getApellido(),
                    'documento' => $cliente->getDocumento(),
                    'telefono' => $cliente->getTelefono()
                ];
            }
        } catch (Exception $e) {
            $datos['cliente'] = null;
        }

        // Agregar detalles de la venta
        try {
            $detalles = $this->detalleVentaRepository->obtenerPorVenta($venta->getId());
            $datos['detalles'] = [];
            
            foreach ($detalles as $detalle) {
                $detalleArray = $detalle->toArray();
                
                // Agregar información del producto
                $producto = $this->productoRepository->obtenerPorId($detalle->getProductoId());
                if ($producto) {
                    $detalleArray['producto'] = [
                        'id' => $producto->getId(),
                        'nombre' => $producto->getNombre(),
                        'codigo' => $producto->getCodigo(),
                        'marca' => $producto->getMarca(),
                        'color' => $producto->getColor()
                    ];
                }
                
                $datos['detalles'][] = $detalleArray;
            }
        } catch (Exception $e) {
            $datos['detalles'] = [];
        }

        return $datos;
    }
}