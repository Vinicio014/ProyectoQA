<?php

namespace Proyecto\Services;

use Proyecto\Entities\PedidoEntity;
use Proyecto\Entities\DetallePedidoEntity;
use Proyecto\Entities\DetalleUniformeEntity;
use Proyecto\Repositories\PedidoRepository;
use Proyecto\Repositories\DetallePedidoRepository;
use Proyecto\Repositories\DetalleUniformeRepository;
use Proyecto\Repositories\ClienteRepository;
use Proyecto\Repositories\ProductoRepository;
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
            $cliente = $this->clienteRepository->obtenerPorId($datosPedido['cliente_id']);
            if (!$cliente) {
                return [
                    'exito' => false,
                    'mensaje' => 'Cliente no encontrado',
                    'datos' => null
                ];
            }

            // Validar productos
            $validacionProductos = $this->validarProductosPedido($datosPedido['productos']);
            if (!$validacionProductos['exito']) {
                return $validacionProductos;
            }

            // Calcular totales
            $totales = $this->calcularTotalesPedido($datosPedido['productos']);

            // Crear pedido
            $pedido = new PedidoEntity();
            $pedido->setClienteId($datosPedido['cliente_id']);
            $pedido->setFechaPedido(new \DateTime($datosPedido['fecha_pedido'] ?? 'now'));
            $pedido->setFechaEntrega(new \DateTime($datosPedido['fecha_entrega']));
            $pedido->setSubtotal($totales['subtotal']);
            $pedido->setImpuesto($totales['impuesto']);
            $pedido->setTotal($totales['total']);
            $pedido->setDescuento($datosPedido['descuento'] ?? 0);
            $pedido->setObservaciones($datosPedido['observaciones'] ?? '');
            $pedido->setEstado($datosPedido['estado'] ?? 'PENDIENTE');

            // Iniciar transacción
            $this->pedidoRepository->iniciarTransaccion();

            try {
                $pedidoCreado = $this->pedidoRepository->crear($pedido);

                // Crear detalles del pedido
                foreach ($datosPedido['productos'] as $itemProducto) {
                    $detallePedido = $this->crearDetallePedido($pedidoCreado->getId(), $itemProducto);
                    
                    // Si el producto es un uniforme, crear detalles específicos
                    if (isset($itemProducto['uniformes']) && !empty($itemProducto['uniformes'])) {
                        foreach ($itemProducto['uniformes'] as $uniforme) {
                            $this->crearDetalleUniforme($detallePedido->getId(), $uniforme);
                        }
                    }
                }

                $this->pedidoRepository->confirmarTransaccion();

                return [
                    'exito' => true,
                    'mensaje' => 'Pedido creado exitosamente',
                    'datos' => $this->formatearPedido($pedidoCreado)
                ];

            } catch (Exception $e) {
                $this->pedidoRepository->revertirTransaccion();
                throw $e;
            }

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
            $pedido = $this->pedidoRepository->obtenerPorId($id);

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
                'datos' => $this->formatearPedido($pedido)
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
     * Listar pedidos con filtros
     */
    public function listarPedidos(array $filtros = []): array
    {
        try {
            $pedidos = $this->pedidoRepository->listarConFiltros($filtros);
            
            return [
                'exito' => true,
                'mensaje' => 'Pedidos obtenidos exitosamente',
                'datos' => array_map([$this, 'formatearPedido'], $pedidos)
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
            $cliente = $this->clienteRepository->obtenerPorId($clienteId);
            if (!$cliente) {
                return [
                    'exito' => false,
                    'mensaje' => 'Cliente no encontrado',
                    'datos' => []
                ];
            }

            $pedidos = $this->pedidoRepository->obtenerPorCliente($clienteId);
            
            return [
                'exito' => true,
                'mensaje' => 'Pedidos del cliente obtenidos',
                'datos' => array_map([$this, 'formatearPedido'], $pedidos)
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
     * Actualizar estado del pedido
     */
    public function actualizarEstadoPedido(int $id, string $nuevoEstado, string $observaciones = ''): array
    {
        try {
            $pedido = $this->pedidoRepository->obtenerPorId($id);

            if (!$pedido) {
                return [
                    'exito' => false,
                    'mensaje' => 'Pedido no encontrado',
                    'datos' => null
                ];
            }

            $estadosValidos = ['PENDIENTE', 'EN_PROCESO', 'COMPLETADO', 'CANCELADO'];
            if (!in_array($nuevoEstado, $estadosValidos)) {
                return [
                    'exito' => false,
                    'mensaje' => 'Estado no válido',
                    'datos' => null
                ];
            }

            $estadoAnterior = $pedido->getEstado();
            $pedido->setEstado($nuevoEstado);
            
            if (!empty($observaciones)) {
                $observacionesActuales = $pedido->getObservaciones();
                $nuevasObservaciones = $observacionesActuales . "\n[" . date('Y-m-d H:i') . "] Estado: $estadoAnterior → $nuevoEstado. $observaciones";
                $pedido->setObservaciones($nuevasObservaciones);
            }

            $pedidoActualizado = $this->pedidoRepository->actualizar($pedido);

            return [
                'exito' => true,
                'mensaje' => 'Estado del pedido actualizado exitosamente',
                'datos' => $this->formatearPedido($pedidoActualizado)
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
    public function cancelarPedido(int $id, string $motivo = ''): array
    {
        try {
            $pedido = $this->pedidoRepository->obtenerPorId($id);

            if (!$pedido) {
                return [
                    'exito' => false,
                    'mensaje' => 'Pedido no encontrado',
                    'datos' => null
                ];
            }

            if ($pedido->getEstado() === 'COMPLETADO') {
                return [
                    'exito' => false,
                    'mensaje' => 'No se puede cancelar un pedido completado',
                    'datos' => null
                ];
            }

            if ($pedido->getEstado() === 'CANCELADO') {
                return [
                    'exito' => false,
                    'mensaje' => 'El pedido ya está cancelado',
                    'datos' => null
                ];
            }

            return $this->actualizarEstadoPedido($id, 'CANCELADO', 'CANCELADO: ' . $motivo);

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
    public function completarPedido(int $id, string $observaciones = ''): array
    {
        try {
            $pedido = $this->pedidoRepository->obtenerPorId($id);

            if (!$pedido) {
                return [
                    'exito' => false,
                    'mensaje' => 'Pedido no encontrado',
                    'datos' => null
                ];
            }

            if ($pedido->getEstado() === 'CANCELADO') {
                return [
                    'exito' => false,
                    'mensaje' => 'No se puede completar un pedido cancelado',
                    'datos' => null
                ];
            }

            if ($pedido->getEstado() === 'COMPLETADO') {
                return [
                    'exito' => false,
                    'mensaje' => 'El pedido ya está completado',
                    'datos' => null
                ];
            }

            return $this->actualizarEstadoPedido($id, 'COMPLETADO', 'COMPLETADO: ' . $observaciones);

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al completar pedido: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Obtener pedidos próximos a vencer
     */
    public function obtenerPedidosProximosVencer(int $dias = 3): array
    {
        try {
            $fechaLimite = new \DateTime();
            $fechaLimite->add(new \DateInterval("P{$dias}D"));
            
            $pedidos = $this->pedidoRepository->obtenerProximosVencer($fechaLimite);
            
            return [
                'exito' => true,
                'mensaje' => 'Pedidos próximos a vencer obtenidos',
                'datos' => array_map([$this, 'formatearPedido'], $pedidos)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener pedidos próximos a vencer: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Obtener estadísticas de pedidos
     */
    public function obtenerEstadisticasPedidos(array $filtros = []): array
    {
        try {
            $estadisticas = [
                'total_pedidos' => $this->pedidoRepository->contarPedidos($filtros),
                'pedidos_pendientes' => $this->pedidoRepository->contarPorEstado('PENDIENTE', $filtros),
                'pedidos_en_proceso' => $this->pedidoRepository->contarPorEstado('EN_PROCESO', $filtros),
                'pedidos_completados' => $this->pedidoRepository->contarPorEstado('COMPLETADO', $filtros),
                'pedidos_cancelados' => $this->pedidoRepository->contarPorEstado('CANCELADO', $filtros),
                'monto_total_pedidos' => $this->pedidoRepository->calcularMontoTotal($filtros),
                'promedio_dias_entrega' => $this->pedidoRepository->calcularPromedioDiasEntrega($filtros),
                'pedidos_por_mes' => $this->pedidoRepository->obtenerPedidosPorMes($filtros)
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
     * Validar productos del pedido
     */
    private function validarProductosPedido(array $productos): array
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
        }

        return ['exito' => true];
    }

    /**
     * Calcular totales del pedido
     */
    private function calcularTotalesPedido(array $productos): array
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
     * Crear detalle de pedido
     */
    private function crearDetallePedido(int $pedidoId, array $itemProducto): DetallePedidoEntity
    {
        $producto = $this->productoRepository->obtenerPorId($itemProducto['producto_id']);
        
        $detalle = new DetallePedidoEntity();
        $detalle->setPedidoId($pedidoId);
        $detalle->setProductoId($itemProducto['producto_id']);
        $detalle->setCantidad($itemProducto['cantidad']);
        $detalle->setPrecioUnitario($itemProducto['precio_unitario'] ?? $producto->getPrecio());
        $detalle->setSubtotal($detalle->getPrecioUnitario() * $detalle->getCantidad());

        return $this->detallePedidoRepository->crear($detalle);
    }

    /**
     * Crear detalle de uniforme
     */
    private function crearDetalleUniforme(int $detallePedidoId, array $uniforme): DetalleUniformeEntity
    {
        $detalleUniforme = new DetalleUniformeEntity();
        $detalleUniforme->setDetallePedidoId($detallePedidoId);
        $detalleUniforme->setTalla($uniforme['talla']);
        $detalleUniforme->setGenero($uniforme['genero'] ?? 'UNISEX');
        $detalleUniforme->setNumero($uniforme['numero'] ?? null);
        $detalleUniforme->setNombreJugador($uniforme['nombre_jugador'] ?? '');
        $detalleUniforme->setColorPrimario($uniforme['color_primario'] ?? '');
        $detalleUniforme->setColorSecundario($uniforme['color_secundario'] ?? '');
        $detalleUniforme->setObservaciones($uniforme['observaciones'] ?? '');

        return $this->detalleUniformeRepository->crear($detalleUniforme);
    }

    /**
     * Formatear pedido con información completa
     */
    private function formatearPedido(PedidoEntity $pedido): array
    {
        $datos = $pedido->toArray();
        
        // Agregar información del cliente
        try {
            $cliente = $this->clienteRepository->obtenerPorId($pedido->getClienteId());
            if ($cliente) {
                $datos['cliente'] = [
                    'id' => $cliente->getId(),
                    'nombre' => $cliente->getNombre() . ' ' . $cliente->getApellido(),
                    'documento' => $cliente->getDocumento(),
                    'telefono' => $cliente->getTelefono(),
                    'email' => $cliente->getEmail()
                ];
            }
        } catch (Exception $e) {
            $datos['cliente'] = null;
        }

        // Agregar detalles del pedido
        try {
            $detalles = $this->detallePedidoRepository->obtenerPorPedido($pedido->getId());
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
                        'color' => $producto->getColor(),
                        'tipo' => $producto->getTipo()
                    ];
                }
                
                // Agregar detalles de uniformes si los tiene
                try {
                    $uniformes = $this->detalleUniformeRepository->obtenerPorDetallePedido($detalle->getId());
                    $detalleArray['uniformes'] = array_map(fn($u) => $u->toArray(), $uniformes);
                } catch (Exception $e) {
                    $detalleArray['uniformes'] = [];
                }
                
                $datos['detalles'][] = $detalleArray;
            }
        } catch (Exception $e) {
            $datos['detalles'] = [];
        }

        // Calcular días para entrega
        $fechaEntrega = $pedido->getFechaEntrega();
        $hoy = new \DateTime();
        $diasParaEntrega = $hoy->diff($fechaEntrega)->days;
        $datos['dias_para_entrega'] = $fechaEntrega < $hoy ? -$diasParaEntrega : $diasParaEntrega;

        return $datos;
    }
}