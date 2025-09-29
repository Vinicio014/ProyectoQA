<?php

namespace App\Services;

use App\Entities\DetalleUniformeEntity;
use App\Repositories\DetalleUniformeRepository;
use App\Repositories\DetallePedidoRepository;
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
            $detallePedido = $this->detallePedidoRepository->findById($datosDetalle['idDetallePedido']);
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
            $detalle->setIdDetallePedido($datosDetalle['idDetallePedido']);
            $detalle->setTalla($datosDetalle['talla']);
            $detalle->setGenero($datosDetalle['genero'] ?? 'Masculino');
            $detalle->setNumeroCamisola($datosDetalle['numeroCamisola'] ?? null);

            $detalleCreado = $this->detalleUniformeRepository->create($detalle);

            return [
                'exito' => true,
                'mensaje' => 'Detalle de uniforme creado exitosamente',
                'datos' => $detalleCreado ? $detalleCreado->toArray() : null
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
    public function crearMultiplesDetalles(int $idDetallePedido, array $uniformes): array
    {
        try {
            // Validar que el detalle de pedido exista
            $detallePedido = $this->detallePedidoRepository->findById($idDetallePedido);
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
                $uniforme['idDetallePedido'] = $idDetallePedido;
                
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
            $detalle = $this->detalleUniformeRepository->findById($id);

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
     * Obtener detalles por detalle de pedido
     */
    public function obtenerDetallesPorDetallePedido(int $idDetallePedido): array
    {
        try {
            $detallePedido = $this->detallePedidoRepository->findById($idDetallePedido);
            if (!$detallePedido) {
                return [
                    'exito' => false,
                    'mensaje' => 'Detalle de pedido no encontrado',
                    'datos' => []
                ];
            }

            $detalles = $this->detalleUniformeRepository->findByDetallePedido($idDetallePedido);
            
            return [
                'exito' => true,
                'mensaje' => 'Detalles de uniformes obtenidos',
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
     * Actualizar detalle de uniforme
     */
    public function actualizarDetalleUniforme(int $id, array $datosDetalle): array
    {
        try {
            $detalle = $this->detalleUniformeRepository->findById($id);

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

            if (isset($datosDetalle['numeroCamisola'])) {
                $detalle->setNumeroCamisola($datosDetalle['numeroCamisola']);
            }

            $resultado = $this->detalleUniformeRepository->update($detalle);

            if ($resultado) {
                $detalleActualizado = $this->detalleUniformeRepository->findById($id);
                return [
                    'exito' => true,
                    'mensaje' => 'Detalle de uniforme actualizado exitosamente',
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
     * Eliminar detalle de uniforme
     */
    public function eliminarDetalleUniforme(int $id): array
    {
        try {
            $detalle = $this->detalleUniformeRepository->findById($id);

            if (!$detalle) {
                return [
                    'exito' => false,
                    'mensaje' => 'Detalle de uniforme no encontrado',
                    'datos' => null
                ];
            }

            $resultado = $this->detalleUniformeRepository->delete($id);

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
     * Obtener uniformes por género
     */
    public function obtenerUniformesPorGenero(string $genero): array
    {
        try {
            if (!in_array($genero, ['Masculino', 'Femenino'])) {
                return [
                    'exito' => false,
                    'mensaje' => 'Género no válido',
                    'datos' => []
                ];
            }

            $uniformes = $this->detalleUniformeRepository->findByGender($genero);
            
            return [
                'exito' => true,
                'mensaje' => 'Uniformes por género obtenidos',
                'datos' => array_map(fn($u) => $u->toArray(), $uniformes)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener uniformes por género: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Obtener uniformes por talla
     */
    public function obtenerUniformesPorTalla(string $talla): array
    {
        try {
            $uniformes = $this->detalleUniformeRepository->findBySize($talla);
            
            return [
                'exito' => true,
                'mensaje' => 'Uniformes por talla obtenidos',
                'datos' => array_map(fn($u) => $u->toArray(), $uniformes)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener uniformes por talla: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Validar disponibilidad de número de camisola
     */
    public function validarNumeroCamisolaDisponible(int $idDetallePedido, int $numero): array
    {
        try {
            $uniformesExistentes = $this->detalleUniformeRepository->findByDetallePedido($idDetallePedido);
            
            $numeroOcupado = false;
            foreach ($uniformesExistentes as $uniforme) {
                if ($uniforme->getNumeroCamisola() === $numero) {
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
     * Validar datos de detalle de uniforme
     */
    public function validarDatosDetalle(array $datos): array
    {
        $errores = [];

        if (empty($datos['idDetallePedido']) || $datos['idDetallePedido'] <= 0) {
            $errores[] = 'El detalle de pedido es requerido';
        }

        if (empty($datos['talla'])) {
            $errores[] = 'La talla es requerida';
        }

        $tallasValidas = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '2', '4', '6', '8', '10', '12', '14', '16'];
        if (!empty($datos['talla']) && !in_array($datos['talla'], $tallasValidas)) {
            $errores[] = 'Talla no válida';
        }

        $generosValidos = ['Masculino', 'Femenino'];
        if (isset($datos['genero']) && !in_array($datos['genero'], $generosValidos)) {
            $errores[] = 'Género no válido';
        }

        if (isset($datos['numeroCamisola']) && ($datos['numeroCamisola'] < 0 || $datos['numeroCamisola'] > 999)) {
            $errores[] = 'Número de camisola debe estar entre 0 y 999';
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }
}