<?php

namespace Proyecto\Services;

use Proyecto\Entities\CategoriaEntity;
use Proyecto\Repositories\CategoriaRepository;
use Exception;

/**
 * Servicio para la gestión de categorías de productos
 * Contiene la lógica de negocio para categorías
 */
class CategoriaService
{
    private CategoriaRepository $categoriaRepository;

    public function __construct(CategoriaRepository $categoriaRepository)
    {
        $this->categoriaRepository = $categoriaRepository;
    }

    /**
     * Crear nueva categoría
     */
    public function crearCategoria(array $datosCategoria): array
    {
        try {
            // Validar que el nombre no exista
            if ($this->existeCategoriaPorNombre($datosCategoria['nombre'])) {
                return [
                    'exito' => false,
                    'mensaje' => 'Ya existe una categoría con ese nombre',
                    'datos' => null
                ];
            }

            $categoria = new CategoriaEntity();
            $categoria->setNombre($datosCategoria['nombre']);
            $categoria->setDescripcion($datosCategoria['descripcion'] ?? '');
            $categoria->setEstado($datosCategoria['estado'] ?? 'ACTIVO');

            $categoriaCreada = $this->categoriaRepository->crear($categoria);

            return [
                'exito' => true,
                'mensaje' => 'Categoría creada exitosamente',
                'datos' => $categoriaCreada->toArray()
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al crear categoría: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Obtener categoría por ID
     */
    public function obtenerCategoriaPorId(int $id): array
    {
        try {
            $categoria = $this->categoriaRepository->obtenerPorId($id);

            if (!$categoria) {
                return [
                    'exito' => false,
                    'mensaje' => 'Categoría no encontrada',
                    'datos' => null
                ];
            }

            $datos = $categoria->toArray();
            
            // Agregar cantidad de productos (se implementará cuando tengamos ProductoRepository)
            $datos['cantidad_productos'] = 0;

            return [
                'exito' => true,
                'mensaje' => 'Categoría encontrada',
                'datos' => $datos
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener categoría: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Listar todas las categorías activas
     */
    public function listarCategoriasActivas(): array
    {
        try {
            $categorias = $this->categoriaRepository->listarPorEstado('ACTIVO');
            
            $datos = [];
            foreach ($categorias as $categoria) {
                $categoriaArray = $categoria->toArray();
                $categoriaArray['cantidad_productos'] = 0; // Se calculará con ProductoRepository
                $datos[] = $categoriaArray;
            }

            return [
                'exito' => true,
                'mensaje' => 'Categorías obtenidas exitosamente',
                'datos' => $datos
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al listar categorías: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Listar todas las categorías con filtros
     */
    public function listarCategorias(array $filtros = []): array
    {
        try {
            $categorias = $this->categoriaRepository->listarConFiltros($filtros);
            
            $datos = [];
            foreach ($categorias as $categoria) {
                $categoriaArray = $categoria->toArray();
                $categoriaArray['cantidad_productos'] = 0; // Se calculará con ProductoRepository
                $datos[] = $categoriaArray;
            }

            return [
                'exito' => true,
                'mensaje' => 'Categorías obtenidas exitosamente',
                'datos' => $datos
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al listar categorías: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Actualizar categoría
     */
    public function actualizarCategoria(int $id, array $datosCategoria): array
    {
        try {
            $categoria = $this->categoriaRepository->obtenerPorId($id);

            if (!$categoria) {
                return [
                    'exito' => false,
                    'mensaje' => 'Categoría no encontrada',
                    'datos' => null
                ];
            }

            // Validar nombre único si se está cambiando
            if (isset($datosCategoria['nombre']) && $datosCategoria['nombre'] !== $categoria->getNombre()) {
                if ($this->existeCategoriaPorNombre($datosCategoria['nombre'])) {
                    return [
                        'exito' => false,
                        'mensaje' => 'Ya existe una categoría con ese nombre',
                        'datos' => null
                    ];
                }
            }

            // Actualizar campos
            if (isset($datosCategoria['nombre'])) {
                $categoria->setNombre($datosCategoria['nombre']);
            }
            if (isset($datosCategoria['descripcion'])) {
                $categoria->setDescripcion($datosCategoria['descripcion']);
            }
            if (isset($datosCategoria['estado'])) {
                $categoria->setEstado($datosCategoria['estado']);
            }

            $categoriaActualizada = $this->categoriaRepository->actualizar($categoria);

            return [
                'exito' => true,
                'mensaje' => 'Categoría actualizada exitosamente',
                'datos' => $categoriaActualizada->toArray()
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al actualizar categoría: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Eliminar categoría
     */
    public function eliminarCategoria(int $id): array
    {
        try {
            // Verificar que no tenga productos asociados
            if ($this->tieneProductosAsociados($id)) {
                return [
                    'exito' => false,
                    'mensaje' => 'No se puede eliminar la categoría porque tiene productos asociados',
                    'datos' => null
                ];
            }

            $resultado = $this->categoriaRepository->eliminar($id);

            if ($resultado) {
                return [
                    'exito' => true,
                    'mensaje' => 'Categoría eliminada exitosamente',
                    'datos' => null
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo eliminar la categoría',
                'datos' => null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al eliminar categoría: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Obtener categorías para select/dropdown
     */
    public function obtenerCategoriasParaSelect(): array
    {
        try {
            $categorias = $this->categoriaRepository->listarPorEstado('ACTIVO');
            
            $categoriasSelect = [];
            foreach ($categorias as $categoria) {
                $categoriasSelect[] = [
                    'id' => $categoria->getId(),
                    'nombre' => $categoria->getNombre(),
                    'descripcion' => $categoria->getDescripcion()
                ];
            }

            return [
                'exito' => true,
                'mensaje' => 'Categorías para select obtenidas',
                'datos' => $categoriasSelect
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener categorías para select: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Buscar categorías por nombre
     */
    public function buscarCategorias(string $termino): array
    {
        try {
            $categorias = $this->categoriaRepository->buscarPorNombre($termino);
            
            return [
                'exito' => true,
                'mensaje' => 'Búsqueda completada',
                'datos' => array_map(fn($categoria) => $categoria->toArray(), $categorias)
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
     * Obtener estadísticas de categorías
     */
    public function obtenerEstadisticasCategorias(): array
    {
        try {
            $total = $this->categoriaRepository->contarTotal();
            $activas = $this->categoriaRepository->contarPorEstado('ACTIVO');
            $inactivas = $this->categoriaRepository->contarPorEstado('INACTIVO');

            $estadisticas = [
                'total_categorias' => $total,
                'categorias_activas' => $activas,
                'categorias_inactivas' => $inactivas,
                'categoria_mas_productos' => null, // Se calculará con ProductoRepository
                'categoria_menos_productos' => null // Se calculará con ProductoRepository
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
     * Cambiar estado de categoría
     */
    public function cambiarEstadoCategoria(int $id, string $nuevoEstado): array
    {
        try {
            $categoria = $this->categoriaRepository->obtenerPorId($id);

            if (!$categoria) {
                return [
                    'exito' => false,
                    'mensaje' => 'Categoría no encontrada',
                    'datos' => null
                ];
            }

            // Validar que si se va a inactivar, no tenga productos activos
            if ($nuevoEstado === 'INACTIVO') {
                if ($this->tieneProductosActivos($id)) {
                    return [
                        'exito' => false,
                        'mensaje' => 'No se puede inactivar la categoría porque tiene productos activos',
                        'datos' => null
                    ];
                }
            }

            $categoria->setEstado($nuevoEstado);
            $categoriaActualizada = $this->categoriaRepository->actualizar($categoria);

            return [
                'exito' => true,
                'mensaje' => 'Estado de categoría actualizado exitosamente',
                'datos' => $categoriaActualizada->toArray()
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al cambiar estado: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Validar datos de categoría
     */
    public function validarDatosCategoria(array $datos): array
    {
        $errores = [];

        if (empty($datos['nombre'])) {
            $errores[] = 'El nombre es requerido';
        } elseif (strlen($datos['nombre']) < 2) {
            $errores[] = 'El nombre debe tener al menos 2 caracteres';
        } elseif (strlen($datos['nombre']) > 100) {
            $errores[] = 'El nombre no puede tener más de 100 caracteres';
        }

        if (isset($datos['descripcion']) && strlen($datos['descripcion']) > 500) {
            $errores[] = 'La descripción no puede tener más de 500 caracteres';
        }

        if (isset($datos['estado']) && !in_array($datos['estado'], ['ACTIVO', 'INACTIVO'])) {
            $errores[] = 'El estado debe ser ACTIVO o INACTIVO';
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }

    /**
     * Verificar si existe categoría por nombre
     */
    private function existeCategoriaPorNombre(string $nombre): bool
    {
        try {
            $categoria = $this->categoriaRepository->obtenerPorNombre($nombre);
            return $categoria !== null;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Verificar si la categoría tiene productos asociados
     */
    private function tieneProductosAsociados(int $categoriaId): bool
    {
        try {
            // Esta lógica se implementará cuando tengamos ProductoRepository
            // Por ahora retornamos false
            return false;
        } catch (Exception $e) {
            return true; // Por seguridad
        }
    }

    /**
     * Verificar si la categoría tiene productos activos
     */
    private function tieneProductosActivos(int $categoriaId): bool
    {
        try {
            // Esta lógica se implementará cuando tengamos ProductoRepository
            // Por ahora retornamos false
            return false;
        } catch (Exception $e) {
            return true; // Por seguridad
        }
    }
}