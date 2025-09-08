<?php

namespace Proyecto\Services;

use Proyecto\Entities\PagoEntity;
use Proyecto\Repositories\PagoRepository;
use Proyecto\Repositories\VentaRepository;
use Proyecto\Repositories\PedidoRepository;
use Exception;

/**
 * Servicio para la gestión de pagos
 * Contiene la lógica de negocio para pagos de ventas y pedidos
 */
class PagoService
{
    private PagoRepository $pagoRepository;
    private VentaRepository $ventaRepository;
    private PedidoRepository $pedidoRepository;

    public function __construct(
        PagoRepository $pagoRepository,
        VentaRepository $ventaRepository,
        PedidoRepository $pedidoRepository
    ) {
        $this->pagoRepository = $pagoRepository;
        $this->ventaRepository = $ventaRepository;
        $this->pedidoRepository = $pedidoRepository;
    }

    /**
     * Registrar pago de venta
     */
    public function registrarPagoVenta(array $datosPago): array
    {
        try {
            // Validar que la venta exista
            $venta = $this->ventaRepository->obtenerPorId($datosPago['venta_id']);
            if (!$venta) {
                return [
                    'exito' => false,
                    'mensaje' => 'Venta no encontrada',
                    'datos' => null
                ];
            }

            // Validar monto
            if ($datosPago['monto'] <= 0) {
                return [
                    'exito' => false,
                    'mensaje' => 'El monto debe ser mayor que 0',
                    'datos' => null
                ];
            }

            $pago = new PagoEntity();
            $pago->setVentaId($datosPago['venta_id']);
            $pago->setPedidoId(null);
            $pago->setMonto($datosPago['monto']);
            $pago->setMetodoPago($datosPago['metodo_pago']);
            $pago->setFechaPago(new \DateTime($datosPago['fecha_pago'] ?? 'now'));
            $pago->setReferencia($datosPago['referencia'] ?? '');
            $pago->setObservaciones($datosPago['observaciones'] ?? '');
            $pago->setEstado($datosPago['estado'] ?? 'COMPLETADO');

            $pagoCreado = $this->pagoRepository->crear($pago);

            return [
                'exito' => true,
                'mensaje' => 'Pago registrado exitosamente',
                'datos' => $this->formatearPago($pagoCreado)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al registrar pago: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Registrar pago de pedido
     */
    public function registrarPagoPedido(array $datosPago): array
    {
        try {
            // Validar que el pedido exista
            $pedido = $this->pedidoRepository->obtenerPorId($datosPago['pedido_id']);
            if (!$pedido) {
                return [
                    'exito' => false,
                    'mensaje' => 'Pedido no encontrado',
                    'datos' => null
                ];
            }

            // Validar monto
            if ($datosPago['monto'] <= 0) {
                return [
                    'exito' => false,
                    'mensaje' => 'El monto debe ser mayor que 0',
                    'datos' => null
                ];
            }

            $pago = new PagoEntity();
            $pago->setVentaId(null);
            $pago->setPedidoId($datosPago['pedido_id']);
            $pago->setMonto($datosPago['monto']);
            $pago->setMetodoPago($datosPago['metodo_pago']);
            $pago->setFechaPago(new \DateTime($datosPago['fecha_pago'] ?? 'now'));
            $pago->setReferencia($datosPago['referencia'] ?? '');
            $pago->setObservaciones($datosPago['observaciones'] ?? '');
            $pago->setEstado($datosPago['estado'] ?? 'COMPLETADO');

            $pagoCreado = $this->pagoRepository->crear($pago);

            return [
                'exito' => true,
                'mensaje' => 'Pago registrado exitosamente',
                'datos' => $this->formatearPago($pagoCreado)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al registrar pago: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Obtener pago por ID
     */
    public function obtenerPagoPorId(int $id): array
    {
        try {
            $pago = $this->pagoRepository->obtenerPorId($id);

            if (!$pago) {
                return [
                    'exito' => false,
                    'mensaje' => 'Pago no encontrado',
                    'datos' => null
                ];
            }

            return [
                'exito' => true,
                'mensaje' => 'Pago encontrado',
                'datos' => $this->formatearPago($pago)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener pago: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Listar pagos con filtros
     */
    public function listarPagos(array $filtros = []): array
    {
        try {
            $pagos = $this->pagoRepository->listarConFiltros($filtros);
            
            return [
                'exito' => true,
                'mensaje' => 'Pagos obtenidos exitosamente',
                'datos' => array_map([$this, 'formatearPago'], $pagos)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al listar pagos: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Obtener pagos de una venta
     */
    public function obtenerPagosVenta(int $ventaId): array
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

            $pagos = $this->pagoRepository->obtenerPorVenta($ventaId);
            
            $totalPagado = array_sum(array_map(fn($p) => $p->getMonto(), $pagos));
            $saldoPendiente = $venta->getTotal() - $totalPagado;

            return [
                'exito' => true,
                'mensaje' => 'Pagos de la venta obtenidos',
                'datos' => [
                    'venta' => [
                        'id' => $venta->getId(),
                        'total' => $venta->getTotal(),
                        'fecha' => $venta->getFecha()->format('Y-m-d')
                    ],
                    'pagos' => array_map([$this, 'formatearPago'], $pagos),
                    'resumen' => [
                        'total_pagado' => $totalPagado,
                        'saldo_pendiente' => $saldoPendiente,
                        'pagado_completo' => $saldoPendiente <= 0
                    ]
                ]
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener pagos de la venta: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Obtener pagos de un pedido
     */
    public function obtenerPagosPedido(int $pedidoId): array
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

            $pagos = $this->pagoRepository->obtenerPorPedido($pedidoId);
            
            $totalPagado = array_sum(array_map(fn($p) => $p->getMonto(), $pagos));
            $saldoPendiente = $pedido->getTotal() - $totalPagado;

            return [
                'exito' => true,
                'mensaje' => 'Pagos del pedido obtenidos',
                'datos' => [
                    'pedido' => [
                        'id' => $pedido->getId(),
                        'total' => $pedido->getTotal(),
                        'fecha_pedido' => $pedido->getFechaPedido()->format('Y-m-d'),
                        'fecha_entrega' => $pedido->getFechaEntrega()->format('Y-m-d')
                    ],
                    'pagos' => array_map([$this, 'formatearPago'], $pagos),
                    'resumen' => [
                        'total_pagado' => $totalPagado,
                        'saldo_pendiente' => $saldoPendiente,
                        'pagado_completo' => $saldoPendiente <= 0
                    ]
                ]
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener pagos del pedido: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Anular pago
     */
    public function anularPago(int $id, string $motivo = ''): array
    {
        try {
            $pago = $this->pagoRepository->obtenerPorId($id);

            if (!$pago) {
                return [
                    'exito' => false,
                    'mensaje' => 'Pago no encontrado',
                    'datos' => null
                ];
            }

            if ($pago->getEstado() === 'ANULADO') {
                return [
                    'exito' => false,
                    'mensaje' => 'El pago ya está anulado',
                    'datos' => null
                ];
            }

            $pago->setEstado('ANULADO');
            $observacionesActuales = $pago->getObservaciones();
            $nuevasObservaciones = $observacionesActuales . "\n[" . date('Y-m-d H:i') . "] ANULADO: $motivo";
            $pago->setObservaciones($nuevasObservaciones);

            $pagoActualizado = $this->pagoRepository->actualizar($pago);

            return [
                'exito' => true,
                'mensaje' => 'Pago anulado exitosamente',
                'datos' => $this->formatearPago($pagoActualizado)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al anular pago: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Procesar pago con validaciones
     */
    public function procesarPago(array $datosPago): array
    {
        try {
            // Validar método de pago
            $metodosValidos = ['EFECTIVO', 'TARJETA_CREDITO', 'TARJETA_DEBITO', 'TRANSFERENCIA', 'CHEQUE'];
            if (!in_array($datosPago['metodo_pago'], $metodosValidos)) {
                return [
                    'exito' => false,
                    'mensaje' => 'Método de pago no válido',
                    'datos' => null
                ];
            }

            // Validar referencia para métodos que la requieren
            $metodosConReferencia = ['TARJETA_CREDITO', 'TARJETA_DEBITO', 'TRANSFERENCIA', 'CHEQUE'];
            if (in_array($datosPago['metodo_pago'], $metodosConReferencia) && empty($datosPago['referencia'])) {
                return [
                    'exito' => false,
                    'mensaje' => 'La referencia es requerida para este método de pago',
                    'datos' => null
                ];
            }

            // Determinar si es pago de venta o pedido
            if (isset($datosPago['venta_id']) && !empty($datosPago['venta_id'])) {
                return $this->registrarPagoVenta($datosPago);
            } elseif (isset($datosPago['pedido_id']) && !empty($datosPago['pedido_id'])) {
                return $this->registrarPagoPedido($datosPago);
            } else {
                return [
                    'exito' => false,
                    'mensaje' => 'Debe especificar una venta o un pedido',
                    'datos' => null
                ];
            }

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al procesar pago: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Obtener estadísticas de pagos
     */
    public function obtenerEstadisticasPagos(array $filtros = []): array
    {
        try {
            $estadisticas = [
                'total_pagos' => $this->pagoRepository->contarPagos($filtros),
                'monto_total_pagos' => $this->pagoRepository->calcularMontoTotal($filtros),
                'pagos_por_metodo' => $this->pagoRepository->obtenerPagosPorMetodo($filtros),
                'pagos_por_mes' => $this->pagoRepository->obtenerPagosPorMes($filtros),
                'promedio_pago' => $this->pagoRepository->calcularPromedioPagos($filtros),
                'pagos_completados' => $this->pagoRepository->contarPorEstado('COMPLETADO', $filtros),
                'pagos_pendientes' => $this->pagoRepository->contarPorEstado('PENDIENTE', $filtros),
                'pagos_anulados' => $this->pagoRepository->contarPorEstado('ANULADO', $filtros)
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
     * Obtener pagos por rango de fechas
     */
    public function obtenerPagosPorFechas(\DateTime $fechaInicio, \DateTime $fechaFin): array
    {
        try {
            $pagos = $this->pagoRepository->obtenerPorRangoFechas($fechaInicio, $fechaFin);
            
            return [
                'exito' => true,
                'mensaje' => 'Pagos por fechas obtenidos',
                'datos' => array_map([$this, 'formatearPago'], $pagos)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener pagos por fechas: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Generar reporte de pagos
     */
    public function generarReportePagos(array $filtros = []): array
    {
        try {
            $pagos = $this->pagoRepository->listarConFiltros($filtros);
            
            $reporte = [
                'periodo' => [
                    'fecha_inicio' => $filtros['fecha_inicio'] ?? null,
                    'fecha_fin' => $filtros['fecha_fin'] ?? null
                ],
                'resumen' => [
                    'total_pagos' => count($pagos),
                    'monto_total' => array_sum(array_map(fn($p) => $p->getMonto(), $pagos)),
                    'pagos_por_metodo' => $this->agruparPagosPorMetodo($pagos),
                    'pagos_por_estado' => $this->agruparPagosPorEstado($pagos)
                ],
                'pagos' => array_map([$this, 'formatearPago'], $pagos)
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
     * Validar datos de pago
     */
    public function validarDatosPago(array $datos): array
    {
        $errores = [];

        if (!isset($datos['monto']) || $datos['monto'] <= 0) {
            $errores[] = 'El monto debe ser mayor que 0';
        }

        if (empty($datos['metodo_pago'])) {
            $errores[] = 'El método de pago es requerido';
        }

        $metodosValidos = ['EFECTIVO', 'TARJETA_CREDITO', 'TARJETA_DEBITO', 'TRANSFERENCIA', 'CHEQUE'];
        if (!empty($datos['metodo_pago']) && !in_array($datos['metodo_pago'], $metodosValidos)) {
            $errores[] = 'Método de pago no válido';
        }

        if (empty($datos['venta_id']) && empty($datos['pedido_id'])) {
            $errores[] = 'Debe especificar una venta o un pedido';
        }

        if (!empty($datos['venta_id']) && !empty($datos['pedido_id'])) {
            $errores[] = 'No puede especificar venta y pedido al mismo tiempo';
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }

    /**
     * Formatear pago con información completa
     */
    private function formatearPago(PagoEntity $pago): array
    {
        $datos = $pago->toArray();
        
        // Agregar información de venta o pedido
        if ($pago->getVentaId()) {
            try {
                $venta = $this->ventaRepository->obtenerPorId($pago->getVentaId());
                if ($venta) {
                    $datos['venta'] = [
                        'id' => $venta->getId(),
                        'fecha' => $venta->getFecha()->format('Y-m-d'),
                        'total' => $venta->getTotal(),
                        'cliente_id' => $venta->getClienteId()
                    ];
                }
            } catch (Exception $e) {
                $datos['venta'] = null;
            }
        }

        if ($pago->getPedidoId()) {
            try {
                $pedido = $this->pedidoRepository->obtenerPorId($pago->getPedidoId());
                if ($pedido) {
                    $datos['pedido'] = [
                        'id' => $pedido->getId(),
                        'fecha_pedido' => $pedido->getFechaPedido()->format('Y-m-d'),
                        'fecha_entrega' => $pedido->getFechaEntrega()->format('Y-m-d'),
                        'total' => $pedido->getTotal(),
                        'cliente_id' => $pedido->getClienteId()
                    ];
                }
            } catch (Exception $e) {
                $datos['pedido'] = null;
            }
        }

        return $datos;
    }

    /**
     * Agrupar pagos por método
     */
    private function agruparPagosPorMetodo(array $pagos): array
    {
        $grupos = [];
        
        foreach ($pagos as $pago) {
            $metodo = $pago->getMetodoPago();
            if (!isset($grupos[$metodo])) {
                $grupos[$metodo] = [
                    'cantidad' => 0,
                    'monto_total' => 0
                ];
            }
            $grupos[$metodo]['cantidad']++;
            $grupos[$metodo]['monto_total'] += $pago->getMonto();
        }

        return $grupos;
    }

    /**
     * Agrupar pagos por estado
     */
    private function agruparPagosPorEstado(array $pagos): array
    {
        $grupos = [];
        
        foreach ($pagos as $pago) {
            $estado = $pago->getEstado();
            if (!isset($grupos[$estado])) {
                $grupos[$estado] = [
                    'cantidad' => 0,
                    'monto_total' => 0
                ];
            }
            $grupos[$estado]['cantidad']++;
            $grupos[$estado]['monto_total'] += $pago->getMonto();
        }

        return $grupos;
    }
}