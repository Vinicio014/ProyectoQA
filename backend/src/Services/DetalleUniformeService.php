<?php

namespace Proyecto\Services;

use Proyecto\Entities\DetalleUniformeEntity;
use Proyecto\Repositories\DetalleUniformeRepository;
use Proyecto\Repositories\DetallePedidoRepository;
use Exception;

/**
 * Servicio para la gestión de detalles específicos de uniformes
 * Contiene la lógica de negocio para personalización de uniformes deportivos
 */
class DetalleUniformeService
{
    private DetalleUniformeRepository $detalleUniformeRepository;
    private DetallePedidoRepository $detallePedidoRepository;

    public function __construct(
        DetalleUniformeRepository $detalleUniformeRepository,
        DetallePedidoRepository $detallePedidoRepository
    ) {
        $this->detalleUniformeRepository = $detalleUniformeRepository;
        $this->detallePedidoRepository = $detallePedidoRepository;
    }

    /**
     * Crear detalle de uniforme
     */
    public function crearDetalleUniforme(array $datosDetalle): array
    {
        try {
            // Validar que el detalle de pedido exista
            $detallePedido = $this->detallePedidoRepository->obtenerPorId($datosDetalle['detalle_pedido_id']);
            if (!$detallePedido) {
                return [
                    'exito' => false,
                    'mensaje' => 'Detalle de pedido no encontrado',
                    'datos' => null
                ];
            }

            // Validar talla requerida
            if (empty($datosDetalle['talla'])) {
                return [
                    'exito' => false,
                    'mensaje' => 'La talla es requerida',
                    'datos' => null
                ];
            }

            $detalle = new DetalleUniformeEntity();
            $detalle->setDetallePedidoId($datosDetalle['detalle_pedido_id']);
            $detalle->setTalla($datosDetalle['talla']);
            $detalle->setGenero($datosDetalle['genero'] ?? 'UNISEX');
            $detalle->setNumero($datosDetalle['numero'] ?? null);
            $detalle->setNombreJugador($datosDetalle['nombre_jugador'] ?? '');
            $detalle->setColorPrimario($datosDetalle['color_primario'] ?? '');
            $detalle->setColorSecundario($datosDetalle['color_secundario'] ?? '');
            $detalle->setObservaciones($datosDetalle['observaciones'] ?? '');

            $detalleCreado = $this->detalleUniformeRepository->crear($detalle);

            return [
                'exito' => true,
                'mensaje' => 'Detalle de uniforme creado exitosamente',
                'datos' => $this->formatearDetalleUniforme($detalleCreado)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al crear detalle de uniforme: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Crear múltiples detalles de uniforme para un pedido
     */
    public function crearMultiplesDetalles(int $detallePedidoId, array $uniformes): array
    {
        try {
            // Validar que el detalle de pedido exista
            $detallePedido = $this->detallePedidoRepository->obtenerPorId($detallePedidoId);
            if (!$detallePedido) {
                return [
                    'exito' => false,
                    'mensaje' => 'Detalle de pedido no encontrado',
                    'datos' => null
                ];
            }

            $detallesCreados = [];
            $errores = [];

            foreach ($uniformes as $index => $uniforme) {
                $uniforme['detalle_pedido_id'] = $detallePedidoId;
                
                $resultado = $this->crearDetalleUniforme($uniforme);
                
                if ($resultado['exito']) {
                    $detallesCreados[] = $resultado['datos'];
                } else {
                    $errores[] = "Uniforme " . ($index + 1) . ": " . $resultado['mensaje'];
                }
            }

            return [
                'exito' => empty($errores),
                'mensaje' => empty($errores) ? 'Todos los uniformes creados exitosamente' : 'Algunos uniformes no se pudieron crear',
                'datos' => [
                    'uniformes_creados' => $detallesCreados,
                    'errores' => $errores
                ]
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al crear múltiples uniformes: ' . $e->getMessage(),
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
            $detalle = $this->detalleUniformeRepository->obtenerPorId($id);

            if (!$detalle) {
                return [
                    'exito' => false,
                    'mensaje' => 'Detalle de uniforme no encontrado',
                    'datos' => null
                ];
            }

            return [
                'exito' => true,
                'mensaje' => 'Detalle encontrado',
                'datos' => $this->formatearDetalleUniforme($detalle)
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
     * Obtener detalles por detalle de pedido
     */
    public function obtenerDetallesPorDetallePedido(int $detallePedidoId): array
    {
        try {
            $detallePedido = $this->detallePedidoRepository->obtenerPorId($detallePedidoId);
            if (!$detallePedido) {
                return [
                    'exito' => false,
                    'mensaje' => 'Detalle de pedido no encontrado',
                    'datos' => []
                ];
            }

            $detalles = $this->detalleUniformeRepository->obtenerPorDetallePedido($detallePedidoId);
            
            return [
                'exito' => true,
                'mensaje' => 'Detalles de uniformes obtenidos',
                'datos' => array_map([$this, 'formatearDetalleUniforme'], $detalles)
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
     * Actualizar detalle de uniforme
     */
    public function actualizarDetalleUniforme(int $id, array $datosDetalle): array
    {
        try {
            $detalle = $this->detalleUniformeRepository->obtenerPorId($id);

            if (!$detalle) {
                return [
                    'exito' => false,
                    'mensaje' => 'Detalle de uniforme no encontrado',
                    'datos' => null
                ];
            }

            // Actualizar campos
            if (isset($datosDetalle['talla'])) {
                if (empty($datosDetalle['talla'])) {
                    return [
                        'exito' => false,
                        'mensaje' => 'La talla no puede estar vacía',
                        'datos' => null
                    ];
                }
                $detalle->setTalla($datosDetalle['talla']);
            }

            if (isset($datosDetalle['genero'])) {
                $detalle->setGenero($datosDetalle['genero']);
            }

            if (isset($datosDetalle['numero'])) {
                $detalle->setNumero($datosDetalle['numero']);
            }

            if (isset($datosDetalle['nombre_jugador'])) {
                $detalle->setNombreJugador($datosDetalle['nombre_jugador']);
            }

            if (isset($datosDetalle['color_primario'])) {
                $detalle->setColorPrimario($datosDetalle['color_primario']);
            }

            if (isset($datosDetalle['color_secundario'])) {
                $detalle->setColorSecundario($datosDetalle['color_secundario']);
            }

            if (isset($datosDetalle['observaciones'])) {
                $detalle->setObservaciones($datosDetalle['observaciones']);
            }

            $detalleActualizado = $this->detalleUniformeRepository->actualizar($detalle);

            return [
                'exito' => true,
                'mensaje' => 'Detalle de uniforme actualizado exitosamente',
                'datos' => $this->formatearDetalleUniforme($detalleActualizado)
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
     * Eliminar detalle de uniforme
     */
    public function eliminarDetalleUniforme(int $id): array
    {
        try {
            $detalle = $this->detalleUniformeRepository->obtenerPorId($id);

            if (!$detalle) {
                return [
                    'exito' => false,
                    'mensaje' => 'Detalle de uniforme no encontrado',
                    'datos' => null
                ];
            }

            $resultado = $this->detalleUniformeRepository->eliminar($id);

            if ($resultado) {
                return [
                    'exito' => true,
                    'mensaje' => 'Detalle de uniforme eliminado exitosamente',
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
     * Obtener estadísticas de uniformes
     */
    public function obtenerEstadisticasUniformes(array $filtros = []): array
    {
        try {
            $estadisticas = [
                'total_uniformes' => $this->detalleUniformeRepository->contarTotal($filtros),
                'uniformes_por_talla' => $this->detalleUniformeRepository->agruparPorTalla($filtros),
                'uniformes_por_genero' => $this->detalleUniformeRepository->agruparPorGenero($filtros),
                'uniformes_con_numero' => $this->detalleUniformeRepository->contarConNumero($filtros),
                'uniformes_con_nombre' => $this->detalleUniformeRepository->contarConNombre($filtros),
                'colores_mas_usados' => $this->detalleUniformeRepository->obtenerColoresMasUsados(10, $filtros),
                'tallas_mas_pedidas' => $this->detalleUniformeRepository->obtenerTallasMasPedidas(10, $filtros)
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
     * Buscar uniformes por jugador
     */
    public function buscarUniformesPorJugador(string $nombreJugador): array
    {
        try {
            if (empty($nombreJugador)) {
                return [
                    'exito' => false,
                    'mensaje' => 'El nombre del jugador es requerido',
                    'datos' => []
                ];
            }

            $uniformes = $this->detalleUniformeRepository->buscarPorNombreJugador($nombreJugador);
            
            return [
                'exito' => true,
                'mensaje' => 'Búsqueda completada',
                'datos' => array_map([$this, 'formatearDetalleUniforme'], $uniformes)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error en búsqueda: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Obtener uniformes por número
     */
    public function obtenerUniformesPorNumero(int $numero): array
    {
        try {
            if ($numero < 0 || $numero > 999) {
                return [
                    'exito' => false,
                    'mensaje' => 'Número de uniforme no válido',
                    'datos' => []
                ];
            }

            $uniformes = $this->detalleUniformeRepository->obtenerPorNumero($numero);
            
            return [
                'exito' => true,
                'mensaje' => 'Uniformes con número ' . $numero . ' obtenidos',
                'datos' => array_map([$this, 'formatearDetalleUniforme'], $uniformes)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener uniformes por número: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Validar disponibilidad de número en un equipo/pedido
     */
    public function validarNumeroDisponible(int $detallePedidoId, int $numero): array
    {
        try {
            $uniformesExistentes = $this->detalleUniformeRepository->obtenerPorDetallePedido($detallePedidoId);
            
            $numeroOcupado = false;
            foreach ($uniformesExistentes as $uniforme) {
                if ($uniforme->getNumero() === $numero) {
                    $numeroOcupado = true;
                    break;
                }
            }

            return [
                'disponible' => !$numeroOcupado,
                'mensaje' => $numeroOcupado ? 'El número ya está ocupado en este pedido' : 'Número disponible'
            ];

        } catch (Exception $e) {
            return [
                'disponible' => false,
                'mensaje' => 'Error al validar número: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generar reporte de uniformes por pedido
     */
    public function generarReporteUniformesPedido(int $detallePedidoId): array
    {
        try {
            $detallePedido = $this->detallePedidoRepository->obtenerPorId($detallePedidoId);
            if (!$detallePedido) {
                return [
                    'exito' => false,
                    'mensaje' => 'Detalle de pedido no encontrado',
                    'datos' => null
                ];
            }

            $uniformes = $this->detalleUniformeRepository->obtenerPorDetallePedido($detallePedidoId);
            
            $reporte = [
                'detalle_pedido' => [
                    'id' => $detallePedido->getId(),
                    'cantidad' => $detallePedido->getCantidad(),
                    'precio_unitario' => $detallePedido->getPrecioUnitario(),
                    'subtotal' => $detallePedido->getSubtotal()
                ],
                'uniformes' => array_map([$this, 'formatearDetalleUniforme'], $uniformes),
                'resumen' => [
                    'total_uniformes' => count($uniformes),
                    'por_talla' => $this->agruparPorTalla($uniformes),
                    'por_genero' => $this->agruparPorGenero($uniformes),
                    'con_numero' => count(array_filter($uniformes, fn($u) => !is_null($u->getNumero()))),
                    'con_nombre' => count(array_filter($uniformes, fn($u) => !empty($u->getNombreJugador())))
                ]
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
     * Validar datos de detalle de uniforme
     */
    public function validarDatosDetalle(array $datos): array
    {
        $errores = [];

        if (empty($datos['detalle_pedido_id']) || $datos['detalle_pedido_id'] <= 0) {
            $errores[] = 'El detalle de pedido es requerido';
        }

        if (empty($datos['talla'])) {
            $errores[] = 'La talla es requerida';
        }

        $tallasValidas = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '2', '4', '6', '8', '10', '12', '14', '16'];
        if (!empty($datos['talla']) && !in_array($datos['talla'], $tallasValidas)) {
            $errores[] = 'Talla no válida';
        }

        $generosValidos = ['MASCULINO', 'FEMENINO', 'UNISEX'];
        if (isset($datos['genero']) && !in_array($datos['genero'], $generosValidos)) {
            $errores[] = 'Género no válido';
        }

        if (isset($datos['numero']) && ($datos['numero'] < 0 || $datos['numero'] > 999)) {
            $errores[] = 'Número de uniforme debe estar entre 0 y 999';
        }

        if (isset($datos['nombre_jugador']) && strlen($datos['nombre_jugador']) > 50) {
            $errores[] = 'El nombre del jugador no puede exceder 50 caracteres';
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }

    /**
     * Formatear detalle de uniforme con información completa
     */
    private function formatearDetalleUniforme(DetalleUniformeEntity $detalle): array
    {
        $datos = $detalle->toArray();
        
        // Agregar información del detalle de pedido
        try {
            $detallePedido = $this->detallePedidoRepository->obtenerPorId($detalle->getDetallePedidoId());
            if ($detallePedido) {
                $datos['detalle_pedido'] = [
                    'id' => $detallePedido->getId(),
                    'pedido_id' => $detallePedido->getPedidoId(),
                    'producto_id' => $detallePedido->getProductoId(),
                    'cantidad' => $detallePedido->getCantidad(),
                    'precio_unitario' => $detallePedido->getPrecioUnitario()
                ];
            }
        } catch (Exception $e) {
            $datos['detalle_pedido'] = null;
        }

        // Agregar información adicional
        $datos['tiene_numero'] = !is_null($detalle->getNumero());
        $datos['tiene_nombre_jugador'] = !empty($detalle->getNombreJugador());
        $datos['tiene_colores_personalizados'] = !empty($detalle->getColorPrimario()) || !empty($detalle->getColorSecundario());

        return $datos;
    }

    /**
     * Agrupar uniformes por talla
     */
    private function agruparPorTalla(array $uniformes): array
    {
        $grupos = [];
        
        foreach ($uniformes as $uniforme) {
            $talla = $uniforme->getTalla();
            if (!isset($grupos[$talla])) {
                $grupos[$talla] = 0;
            }
            $grupos[$talla]++;
        }

        return $grupos;
    }

    /**
     * Agrupar uniformes por género
     */
    private function agruparPorGenero(array $uniformes): array
    {
        $grupos = [];
        
        foreach ($uniformes as $uniforme) {
            $genero = $uniforme->getGenero();
            if (!isset($grupos[$genero])) {
                $grupos[$genero] = 0;
            }
            $grupos[$genero]++;
        }

        return $grupos;
    }
}