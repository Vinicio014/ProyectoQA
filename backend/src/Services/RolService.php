<?php

namespace App\Services;

use App\Entities\RolEntity;
use App\Repositories\RolRepository;
use Exception;

/**
 * Servicio para la gestión de roles del sistema
 * Contiene la lógica de negocio para roles
 */
class RolService
{
    private RolRepository $rolRepository;

    public function __construct(RolRepository $rolRepository)
    {
        $this->rolRepository = $rolRepository;
    }

    /**
     * Crear un nuevo rol
     */
    public function crearRol(array $datosRol): array
    {
        try {
            // Validar que la descripción del rol no exista
            if ($this->existeRolPorDescripcion($datosRol['descripcion'])) {
                return [
                    'exito' => false,
                    'mensaje' => 'Ya existe un rol con esa descripción',
                    'datos' => null
                ];
            }

            $rol = new RolEntity();
            $rol->setDescripcion($datosRol['descripcion']);
            $rol->setEsActivo($datosRol['esActivo'] ?? true);

            $rolCreado = $this->rolRepository->create($rol);

            return [
                'exito' => true,
                'mensaje' => 'Rol creado exitosamente',
                'datos' => $rolCreado ? $rolCreado->toArray() : null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al crear rol: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Obtener rol por ID
     */
    public function obtenerRolPorId(int $id): array
    {
        try {
            $rol = $this->rolRepository->findById($id);

            if (!$rol) {
                return [
                    'exito' => false,
                    'mensaje' => 'Rol no encontrado',
                    'datos' => null
                ];
            }

            return [
                'exito' => true,
                'mensaje' => 'Rol encontrado',
                'datos' => $rol->toArray()
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener rol: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Listar todos los roles
     */
    public function listarRoles(): array
    {
        try {
            $roles = $this->rolRepository->findAll();
            
            return [
                'exito' => true,
                'mensaje' => 'Roles obtenidos exitosamente',
                'datos' => array_map(fn($rol) => $rol->toArray(), $roles)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al listar roles: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Listar todos los roles activos
     */
    public function listarRolesActivos(): array
    {
        try {
            $roles = $this->rolRepository->findActive();
            
            return [
                'exito' => true,
                'mensaje' => 'Roles activos obtenidos exitosamente',
                'datos' => array_map(fn($rol) => $rol->toArray(), $roles)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al listar roles activos: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Actualizar rol
     */
    public function actualizarRol(int $id, array $datosRol): array
    {
        try {
            $rol = $this->rolRepository->findById($id);

            if (!$rol) {
                return [
                    'exito' => false,
                    'mensaje' => 'Rol no encontrado',
                    'datos' => null
                ];
            }

            // Validar descripción única si se está cambiando
            if (isset($datosRol['descripcion']) && $datosRol['descripcion'] !== $rol->getDescripcion()) {
                if ($this->existeRolPorDescripcion($datosRol['descripcion'])) {
                    return [
                        'exito' => false,
                        'mensaje' => 'Ya existe un rol con esa descripción',
                        'datos' => null
                    ];
                }
            }

            // Actualizar campos
            if (isset($datosRol['descripcion'])) {
                $rol->setDescripcion($datosRol['descripcion']);
            }
            if (isset($datosRol['esActivo'])) {
                $rol->setEsActivo($datosRol['esActivo']);
            }

            $resultado = $this->rolRepository->update($rol);

            if ($resultado) {
                $rolActualizado = $this->rolRepository->findById($id);
                return [
                    'exito' => true,
                    'mensaje' => 'Rol actualizado exitosamente',
                    'datos' => $rolActualizado->toArray()
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo actualizar el rol',
                'datos' => null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al actualizar rol: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Eliminar rol (soft delete)
     */
    public function eliminarRol(int $id): array
    {
        try {
            $resultado = $this->rolRepository->delete($id);

            if ($resultado) {
                return [
                    'exito' => true,
                    'mensaje' => 'Rol desactivado exitosamente',
                    'datos' => null
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo eliminar el rol',
                'datos' => null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al eliminar rol: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Activar rol
     */
    public function activarRol(int $id): array
    {
        try {
            $resultado = $this->rolRepository->activate($id);

            if ($resultado) {
                return [
                    'exito' => true,
                    'mensaje' => 'Rol activado exitosamente',
                    'datos' => null
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo activar el rol',
                'datos' => null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al activar rol: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Verificar si existe un rol por descripción
     */
    private function existeRolPorDescripcion(string $descripcion): bool
    {
        try {
            $roles = $this->rolRepository->findAll();
            foreach ($roles as $rol) {
                if (strtolower($rol->getDescripcion()) === strtolower($descripcion)) {
                    return true;
                }
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtener roles para select/dropdown
     */
    public function obtenerRolesParaSelect(): array
    {
        try {
            $roles = $this->rolRepository->findActive();
            
            $rolesSelect = [];
            foreach ($roles as $rol) {
                $rolesSelect[] = [
                    'id' => $rol->getIdRol(),
                    'descripcion' => $rol->getDescripcion()
                ];
            }

            return [
                'exito' => true,
                'mensaje' => 'Roles para select obtenidos',
                'datos' => $rolesSelect
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener roles para select: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Validar datos de rol
     */
    public function validarDatosRol(array $datos): array
    {
        $errores = [];

        if (empty($datos['descripcion'])) {
            $errores[] = 'La descripción es requerida';
        } elseif (strlen($datos['descripcion']) < 3) {
            $errores[] = 'La descripción debe tener al menos 3 caracteres';
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }
}