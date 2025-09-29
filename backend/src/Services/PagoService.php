<?php

namespace App\Services;

use App\Entities\PagoEntity;
use App\Repositories\PagoRepository;
use App\Repositories\VentaRepository;
use App\Repositories\PedidoRepository;
use Exception;

/**
 * Servicio para la gestión de pagos
 * Contiene la lógica de negocio para pagos de pedidos
 */
class PagoService
{
    private PagoRepository $pagoRepository;
    private PedidoRepository $pedidoRepository;

    public function __construct(
        PagoRepository $pagoRepository,
        PedidoRepository $pedidoRepository
    ) {
        $this->pagoRepository = $pagoRepository;
        $this->pedidoRepository = $pedidoRepository;
    }

    /**
     * Registrar pago de pedido
     */
    public function registrarPagoPedido(array $datosPago): array
    {
        try {
            // Validar que el pedido exista
            $pedido = $this->pedidoRepository->findById($datosPago['nrPedido']);
            if (!$pedido) {
                return [
                    'exito' => false,
                    'mensaje' => 'Pedido no encontrado',
                    'datos' => null
                ];
            }

            // Validar monto
            if ($datosPago['montoPagado'] <= 0) {
                return [
                    'exito' => false,
                    'mensaje' => 'El monto debe ser mayor que 0',
                    'datos' => null
                ];
            }

            $pago = new PagoEntity();
            $pago->setNrPedido($datosPago['nrPedido']);
            $pago->setMontoPagado($datosPago['montoPagado']);
            $pago->setDescripcion($datosPago['descripcion'] ?? '');

            $pagoCreado = $this->pagoRepository->create($pago);

            return [
                'exito' => true,
                'mensaje' => 'Pago registrado exitosamente',
                'datos' => $pagoCreado ? $pagoCreado->toArray() : null
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
            $pago = $this->pagoRepository->findById($id);

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
                'datos' => $pago->toArray()
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
     * Listar todos los pagos
     */
    public function listarPagos(): array
    {
        try {
            $pagos = $this->pagoRepository->findAll();
            
            return [
                'exito' => true,
                'mensaje' => 'Pagos obtenidos exitosamente',
                'datos' => array_map(fn($p) => $p->toArray(), $pagos)
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
     * Obtener pagos de un pedido
     */
    public function obtenerPagosPedido(int $pedidoId): array
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

            $pagos = $this->pagoRepository->findByPedido($pedidoId);
            
            $totalPagado = array_sum(array_map(fn($p) => $p->getMontoPagado(), $pagos));

            return [
                'exito' => true,
                'mensaje' => 'Pagos del pedido obtenidos',
                'datos' => [
                    'pedido' => [
                        'id' => $pedido->getIdPedido(),
                        'fecha_pedido' => $pedido->getFechaPedido()->format('Y-m-d'),
                        'estado' => $pedido->getEstadoPedido()
                    ],
                    'pagos' => array_map(fn($p) => $p->toArray(), $pagos),
                    'resumen' => [
                        'total_pagado' => $totalPagado,
                        'cantidad_pagos' => count($pagos)
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
     * Actualizar pago
     */
    public function actualizarPago(int $id, array $datosPago): array
    {
        try {
            $pago = $this->pagoRepository->findById($id);

            if (!$pago) {
                return [
                    'exito' => false,
                    'mensaje' => 'Pago no encontrado',
                    'datos' => null
                ];
            }

            if (isset($datosPago['montoPagado'])) {
                if ($datosPago['montoPagado'] <= 0) {
                    return [
                        'exito' => false,
                        'mensaje' => 'El monto debe ser mayor que 0',
                        'datos' => null
                    ];
                }
                $pago->setMontoPagado($datosPago['montoPagado']);
            }

            if (isset($datosPago['descripcion'])) {
                $pago->setDescripcion($datosPago['descripcion']);
            }

            $resultado = $this->pagoRepository->update($pago);

            if ($resultado) {
                $pagoActualizado = $this->pagoRepository->findById($id);
                return [
                    'exito' => true,
                    'mensaje' => 'Pago actualizado exitosamente',
                    'datos' => $pagoActualizado->toArray()
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo actualizar el pago',
                'datos' => null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al actualizar pago: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Eliminar pago
     */
    public function eliminarPago(int $id): array
    {
        try {
            $resultado = $this->pagoRepository->delete($id);

            if ($resultado) {
                return [
                    'exito' => true,
                    'mensaje' => 'Pago eliminado exitosamente',
                    'datos' => null
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo eliminar el pago',
                'datos' => null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al eliminar pago: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Verificar si un pedido está completamente pagado
     */
    public function verificarPedidoPagado(int $pedidoId): array
    {
        try {
            $isPagado = $this->pagoRepository->isPedidoFullyPaid($pedidoId);
            
            return [
                'exito' => true,
                'mensaje' => $isPagado ? 'Pedido completamente pagado' : 'Pedido con saldo pendiente',
                'datos' => [
                    'pedido_id' => $pedidoId,
                    'completamente_pagado' => $isPagado
                ]
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al verificar estado de pago: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Obtener total pagado de un pedido
     */
    public function obtenerTotalPagadoPedido(int $pedidoId): array
    {
        try {
            $total = $this->pagoRepository->getTotalPaidForPedido($pedidoId);
            
            return [
                'exito' => true,
                'mensaje' => 'Total pagado obtenido',
                'datos' => [
                    'pedido_id' => $pedidoId,
                    'total_pagado' => $total
                ]
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener total pagado: ' . $e->getMessage(),
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

        if (!isset($datos['montoPagado']) || $datos['montoPagado'] <= 0) {
            $errores[] = 'El monto debe ser mayor que 0';
        }

        if (empty($datos['nrPedido']) || $datos['nrPedido'] <= 0) {
            $errores[] = 'El pedido es requerido';
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }
}