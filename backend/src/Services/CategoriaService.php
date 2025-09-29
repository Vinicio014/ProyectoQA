<?php

namespace App\Services;

use App\Entities\CategoriaEntity;
use App\Repositories\CategoriaRepository;
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
            // Validar que la descripción no exista
            if ($this->existeCategoriaPorDescripcion($datosCategoria['descripcion'])) {
                return [
                    'exito' => false,
                    'mensaje' => 'Ya existe una categoría con esa descripción',
                    'datos' => null
                ];
            }

            $categoria = new CategoriaEntity();
            $categoria->setDescripcion($datosCategoria['descripcion']);
            $categoria->setEsActivo($datosCategoria['esActivo'] ?? true);

            $categoriaCreada = $this->categoriaRepository->create($categoria);

            return [
                'exito' => true,
                'mensaje' => 'Categoría creada exitosamente',
                'datos' => $categoriaCreada ? $categoriaCreada->toArray() : null
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
            $categoria = $this->categoriaRepository->findById($id);

            if (!$categoria) {
                return [
                    'exito' => false,
                    'mensaje' => 'Categoría no encontrada',
                    'datos' => null
                ];
            }

            return [
                'exito' => true,
                'mensaje' => 'Categoría encontrada',
                'datos' => $categoria->toArray()
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
            $categorias = $this->categoriaRepository->findActive();
            
            $datos = array_map(fn($categoria) => $categoria->toArray(), $categorias);

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
     * Listar todas las categorías
     */
    public function listarCategorias(): array
    {
        try {
            $categorias = $this->categoriaRepository->findAll();
            
            $datos = array_map(fn($categoria) => $categoria->toArray(), $categorias);

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
            $categoria = $this->categoriaRepository->findById($id);

            if (!$categoria) {
                return [
                    'exito' => false,
                    'mensaje' => 'Categoría no encontrada',
                    'datos' => null
                ];
            }

            // Validar descripción única si se está cambiando
            if (isset($datosCategoria['descripcion']) && 
                $datosCategoria['descripcion'] !== $categoria->getDescripcion()) {
                if ($this->existeCategoriaPorDescripcion($datosCategoria['descripcion'])) {
                    return [
                        'exito' => false,
                        'mensaje' => 'Ya existe una categoría con esa descripción',
                        'datos' => null
                    ];
                }
            }

            // Actualizar campos
            if (isset($datosCategoria['descripcion'])) {
                $categoria->setDescripcion($datosCategoria['descripcion']);
            }
            if (isset($datosCategoria['esActivo'])) {
                $categoria->setEsActivo($datosCategoria['esActivo']);
            }

            $resultado = $this->categoriaRepository->update($categoria);

            if ($resultado) {
                $categoriaActualizada = $this->categoriaRepository->findById($id);
                return [
                    'exito' => true,
                    'mensaje' => 'Categoría actualizada exitosamente',
                    'datos' => $categoriaActualizada->toArray()
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo actualizar la categoría',
                'datos' => null
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
     * Eliminar categoría (soft delete)
     */
    public function eliminarCategoria(int $id): array
    {
        try {
            $resultado = $this->categoriaRepository->delete($id);

            if ($resultado) {
                return [
                    'exito' => true,
                    'mensaje' => 'Categoría desactivada exitosamente',
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
     * Activar categoría
     */
    public function activarCategoria(int $id): array
    {
        try {
            $resultado = $this->categoriaRepository->activate($id);

            if ($resultado) {
                return [
                    'exito' => true,
                    'mensaje' => 'Categoría activada exitosamente',
                    'datos' => null
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo activar la categoría',
                'datos' => null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al activar categoría: ' . $e->getMessage(),
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
            $categorias = $this->categoriaRepository->findActive();
            
            $categoriasSelect = [];
            foreach ($categorias as $categoria) {
                $categoriasSelect[] = [
                    'id' => $categoria->getIdCategoria(),
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
     * Obtener estadísticas de categorías
     */
    public function obtenerEstadisticasCategorias(): array
    {
        try {
            $todas = $this->categoriaRepository->findAll();
            $activas = $this->categoriaRepository->findActive();

            $estadisticas = [
                'total_categorias' => count($todas),
                'categorias_activas' => count($activas),
                'categorias_inactivas' => count($todas) - count($activas)
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
     * Validar datos de categoría
     */
    public function validarDatosCategoria(array $datos): array
    {
        $errores = [];

        if (empty($datos['descripcion'])) {
            $errores[] = 'La descripción es requerida';
        } elseif (strlen($datos['descripcion']) < 2) {
            $errores[] = 'La descripción debe tener al menos 2 caracteres';
        } elseif (strlen($datos['descripcion']) > 100) {
            $errores[] = 'La descripción no puede tener más de 100 caracteres';
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }

    /**
     * Verificar si existe categoría por descripción
     */
    private function existeCategoriaPorDescripcion(string $descripcion): bool
    {
        try {
            $categorias = $this->categoriaRepository->findAll();
            foreach ($categorias as $categoria) {
                if (strtolower($categoria->getDescripcion()) === strtolower($descripcion)) {
                    return true;
                }
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
}