<?php

namespace App\Services;

use App\Entities\VentaEntity;
use App\Entities\DetalleVentaEntity;
use App\Repositories\VentaRepository;
use App\Repositories\DetalleVentaRepository;
use App\Repositories\ClienteRepository;
use App\Repositories\ProductoRepository;
use App\Services\ProductoService;
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

    public function __construct(
        VentaRepository $ventaRepository,
        DetalleVentaRepository $detalleVentaRepository,
        ClienteRepository $clienteRepository,
        ProductoRepository $productoRepository
    ) {
        $this->ventaRepository = $ventaRepository;
        $this->detalleVentaRepository = $detalleVentaRepository;
        $this->clienteRepository = $clienteRepository;
        $this->productoRepository = $productoRepository;
    }

    /**
     * Crear nueva venta
     */
    public function crearVenta(array $datosVenta): array
    {
        try {
            // Validar cliente
            $cliente = $this->clienteRepository->findById($datosVenta['idCliente']);
            if (!$cliente) {
                return [
                    'exito' => false,
                    'mensaje' => 'Cliente no encontrado',
                    'datos' => null
                ];
            }

            // Validar que haya productos
            if (empty($datosVenta['productos'])) {
                return [
                    'exito' => false,
                    'mensaje' => 'Debe agregar al menos un producto',
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
            $venta->setIdCliente($datosVenta['idCliente']);
            $venta->setIdUsuario($datosVenta['idUsuario']);
            $venta->setFechaRegistro(new \DateTime());
            $venta->setTotal($totales['total']);
            $venta->setImpuestosTotal($totales['impuestos']);

            $ventaCreada = $this->ventaRepository->create($venta);

            if (!$ventaCreada) {
                return [
                    'exito' => false,
                    'mensaje' => 'Error al crear la venta',
                    'datos' => null
                ];
            }

            // Crear detalles de venta
            $detallesCreados = [];
            foreach ($datosVenta['productos'] as $itemProducto) {
                $detalle = $this->crearDetalleVenta($ventaCreada->getIdVenta(), $itemProducto);
                if ($detalle) {
                    $detallesCreados[] = $detalle;
                }
            }

            return [
                'exito' => true,
                'mensaje' => 'Venta creada exitosamente',
                'datos' => [
                    'venta' => $ventaCreada->toArray(),
                    'detalles' => array_map(fn($d) => $d->toArray(), $detallesCreados)
                ]
            ];

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
            $venta = $this->ventaRepository->findById($id);

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
     * Listar todas las ventas
     */
    public function listarVentas(): array
    {
        try {
            $ventas = $this->ventaRepository->findAll();
            
            return [
                'exito' => true,
                'mensaje' => 'Ventas obtenidas exitosamente',
                'datos' => array_map(fn($v) => $v->toArray(), $ventas)
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
            $cliente = $this->clienteRepository->findById($clienteId);
            if (!$cliente) {
                return [
                    'exito' => false,
                    'mensaje' => 'Cliente no encontrado',
                    'datos' => []
                ];
            }

            $ventas = $this->ventaRepository->findByClient($clienteId);
            
            return [
                'exito' => true,
                'mensaje' => 'Ventas del cliente obtenidas',
                'datos' => array_map(fn($v) => $v->toArray(), $ventas)
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
     * Obtener ventas por usuario
     */
    public function obtenerVentasPorUsuario(int $usuarioId): array
    {
        try {
            $ventas = $this->ventaRepository->findByUser($usuarioId);
            
            return [
                'exito' => true,
                'mensaje' => 'Ventas del usuario obtenidas',
                'datos' => array_map(fn($v) => $v->toArray(), $ventas)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener ventas del usuario: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Obtener ventas por fecha
     */
    public function obtenerVentasPorFecha(string $fecha): array
    {
        try {
            $ventas = $this->ventaRepository->findByDate($fecha);
            
            return [
                'exito' => true,
                'mensaje' => 'Ventas por fecha obtenidas',
                'datos' => array_map(fn($v) => $v->toArray(), $ventas)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener ventas por fecha: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Obtener ventas por rango de fechas
     */
    public function obtenerVentasPorRangoFechas(string $fechaInicio, string $fechaFin): array
    {
        try {
            $ventas = $this->ventaRepository->findByDateRange($fechaInicio, $fechaFin);
            
            return [
                'exito' => true,
                'mensaje' => 'Ventas por rango de fechas obtenidas',
                'datos' => array_map(fn($v) => $v->toArray(), $ventas)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener ventas por rango de fechas: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Actualizar venta
     */
    public function actualizarVenta(int $id, array $datosVenta): array
    {
        try {
            $venta = $this->ventaRepository->findById($id);

            if (!$venta) {
                return [
                    'exito' => false,
                    'mensaje' => 'Venta no encontrada',
                    'datos' => null
                ];
            }

            // Actualizar campos permitidos
            if (isset($datosVenta['idCliente'])) {
                $venta->setIdCliente($datosVenta['idCliente']);
            }
            if (isset($datosVenta['idUsuario'])) {
                $venta->setIdUsuario($datosVenta['idUsuario']);
            }
            if (isset($datosVenta['total'])) {
                $venta->setTotal($datosVenta['total']);
            }
            if (isset($datosVenta['impuestosTotal'])) {
                $venta->setImpuestosTotal($datosVenta['impuestosTotal']);
            }

            $resultado = $this->ventaRepository->update($venta);

            if ($resultado) {
                $ventaActualizada = $this->ventaRepository->findById($id);
                return [
                    'exito' => true,
                    'mensaje' => 'Venta actualizada exitosamente',
                    'datos' => $ventaActualizada->toArray()
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo actualizar la venta',
                'datos' => null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al actualizar venta: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Eliminar venta
     */
    public function eliminarVenta(int $id): array
    {
        try {
            // Devolver stock de los productos antes de eliminar
            $detalles = $this->detalleVentaRepository->findByVenta($id);
            foreach ($detalles as $detalle) {
                $this->productoRepository->increaseStock(
                    $detalle->getIdProducto(), 
                    $detalle->getCantidad()
                );
            }

            $resultado = $this->ventaRepository->delete($id);

            if ($resultado) {
                return [
                    'exito' => true,
                    'mensaje' => 'Venta eliminada exitosamente',
                    'datos' => null
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo eliminar la venta',
                'datos' => null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al eliminar venta: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Obtener ventas del día
     */
    public function obtenerVentasDelDia(): array
    {
        try {
            $ventas = $this->ventaRepository->getTodaySales();
            
            $totalVentas = count($ventas);
            $totalMonto = array_sum(array_map(fn($v) => $v->getTotal(), $ventas));

            return [
                'exito' => true,
                'mensaje' => 'Ventas del día obtenidas',
                'datos' => [
                    'ventas' => array_map(fn($v) => $v->toArray(), $ventas),
                    'resumen' => [
                        'total_ventas' => $totalVentas,
                        'total_monto' => $totalMonto
                    ]
                ]
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener ventas del día: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Obtener estadísticas de ventas
     */
    public function obtenerEstadisticasVentas(string $fechaInicio, string $fechaFin): array
    {
        try {
            $estadisticas = $this->ventaRepository->getSalesStats($fechaInicio, $fechaFin);
            
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
     * Obtener reporte de ventas por usuario
     */
    public function obtenerReporteVentasPorUsuario(string $fechaInicio, string $fechaFin): array
    {
        try {
            $reporte = $this->ventaRepository->getSalesReportByUser($fechaInicio, $fechaFin);
            
            return [
                'exito' => true,
                'mensaje' => 'Reporte obtenido exitosamente',
                'datos' => $reporte
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener reporte: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Validar productos para la venta
     */
    private function validarProductosVenta(array $productos): array
    {
        foreach ($productos as $item) {
            $producto = $this->productoRepository->findById($item['idProducto']);
            
            if (!$producto) {
                return [
                    'exito' => false,
                    'mensaje' => 'Producto no encontrado: ID ' . $item['idProducto'],
                    'datos' => null
                ];
            }

            if (!$producto->getEsActivo()) {
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
            $producto = $this->productoRepository->findById($item['idProducto']);
            $precioUnitario = $producto->getPrecioUnitario();
            $subtotal += $precioUnitario * $item['cantidad'];
        }

        $impuestos = $subtotal * 0.12; // 12% IVA
        $total = $subtotal + $impuestos;

        return [
            'subtotal' => $subtotal,
            'impuestos' => $impuestos,
            'total' => $total
        ];
    }

    /**
     * Crear detalle de venta
     */
    private function crearDetalleVenta(int $ventaId, array $itemProducto): ?DetalleVentaEntity
    {
        try {
            $producto = $this->productoRepository->findById($itemProducto['idProducto']);
            
            if (!$producto) {
                return null;
            }

            $detalle = new DetalleVentaEntity();
            $detalle->setIdVenta($ventaId);
            $detalle->setIdProducto($itemProducto['idProducto']);
            $detalle->setCantidad($itemProducto['cantidad']);
            
            // Calcular subtotal
            $subtotal = $producto->getPrecioUnitario() * $itemProducto['cantidad'];
            $detalle->setSubTotal($subtotal);

            $detalleCreado = $this->detalleVentaRepository->create($detalle);

            // Reducir stock
            if ($detalleCreado) {
                $this->productoRepository->reduceStock(
                    $itemProducto['idProducto'], 
                    $itemProducto['cantidad']
                );
            }

            return $detalleCreado;
        } catch (Exception $e) {
            error_log("Error creating detalle venta: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Formatear venta con información completa
     */
    private function formatearVenta(VentaEntity $venta): array
    {
        $datos = $venta->toArray();
        
        // Agregar información del cliente
        try {
            $cliente = $this->clienteRepository->findById($venta->getIdCliente());
            if ($cliente) {
                $datos['cliente'] = [
                    'id' => $cliente->getIdCliente(),
                    'nombre_completo' => $cliente->getPrimerNombre() . ' ' . $cliente->getPrimerApellido(),
                    'telefono' => $cliente->getTelefono()
                ];
            }
        } catch (Exception $e) {
            $datos['cliente'] = null;
        }

        // Agregar detalles de la venta
        try {
            $detalles = $this->detalleVentaRepository->findByVenta($venta->getIdVenta());
            $datos['detalles'] = array_map(fn($d) => $d->toArray(), $detalles);
        } catch (Exception $e) {
            $datos['detalles'] = [];
        }

        return $datos;
    }

    /**
     * Validar datos de venta
     */
    public function validarDatosVenta(array $datos): array
    {
        $errores = [];

        if (empty($datos['idCliente']) || $datos['idCliente'] <= 0) {
            $errores[] = 'El cliente es requerido';
        }

        if (empty($datos['idUsuario']) || $datos['idUsuario'] <= 0) {
            $errores[] = 'El usuario es requerido';
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