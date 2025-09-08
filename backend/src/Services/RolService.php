<?php

namespace Proyecto\Services;

use Proyecto\Entities\RolEntity;
use Proyecto\Repositories\RolRepository;
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
            // Validar que el nombre del rol no exista
            if ($this->existeRolPorNombre($datosRol['nombre'])) {
                return [
                    'exito' => false,
                    'mensaje' => 'Ya existe un rol con ese nombre',
                    'datos' => null
                ];
            }

            $rol = new RolEntity();
            $rol->setNombre($datosRol['nombre']);
            $rol->setDescripcion($datosRol['descripcion'] ?? '');
            $rol->setEstado($datosRol['estado'] ?? 'ACTIVO');

            $rolCreado = $this->rolRepository->crear($rol);

            return [
                'exito' => true,
                'mensaje' => 'Rol creado exitosamente',
                'datos' => $rolCreado->toArray()
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
            $rol = $this->rolRepository->obtenerPorId($id);

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
     * Listar todos los roles activos
     */
    public function listarRolesActivos(): array
    {
        try {
            $roles = $this->rolRepository->listarPorEstado('ACTIVO');
            
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
     * Actualizar rol
     */
    public function actualizarRol(int $id, array $datosRol): array
    {
        try {
            $rol = $this->rolRepository->obtenerPorId($id);

            if (!$rol) {
                return [
                    'exito' => false,
                    'mensaje' => 'Rol no encontrado',
                    'datos' => null
                ];
            }

            // Validar nombre único si se está cambiando
            if (isset($datosRol['nombre']) && $datosRol['nombre'] !== $rol->getNombre()) {
                if ($this->existeRolPorNombre($datosRol['nombre'])) {
                    return [
                        'exito' => false,
                        'mensaje' => 'Ya existe un rol con ese nombre',
                        'datos' => null
                    ];
                }
            }

            // Actualizar campos
            if (isset($datosRol['nombre'])) {
                $rol->setNombre($datosRol['nombre']);
            }
            if (isset($datosRol['descripcion'])) {
                $rol->setDescripcion($datosRol['descripcion']);
            }
            if (isset($datosRol['estado'])) {
                $rol->setEstado($datosRol['estado']);
            }

            $rolActualizado = $this->rolRepository->actualizar($rol);

            return [
                'exito' => true,
                'mensaje' => 'Rol actualizado exitosamente',
                'datos' => $rolActualizado->toArray()
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
     * Eliminar rol (cambiar estado a INACTIVO)
     */
    public function eliminarRol(int $id): array
    {
        try {
            // Verificar que no haya usuarios con este rol
            if ($this->tieneUsuariosAsociados($id)) {
                return [
                    'exito' => false,
                    'mensaje' => 'No se puede eliminar el rol porque tiene usuarios asociados',
                    'datos' => null
                ];
            }

            $resultado = $this->rolRepository->eliminar($id);

            if ($resultado) {
                return [
                    'exito' => true,
                    'mensaje' => 'Rol eliminado exitosamente',
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
     * Verificar si existe un rol por nombre
     */
    private function existeRolPorNombre(string $nombre): bool
    {
        try {
            $rol = $this->rolRepository->obtenerPorNombre($nombre);
            return $rol !== null;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Verificar si el rol tiene usuarios asociados
     */
    private function tieneUsuariosAsociados(int $rolId): bool
    {
        try {
            // Esta lógica debe implementarse cuando tengamos el UsuarioRepository
            // Por ahora retornamos false
            return false;
        } catch (Exception $e) {
            return true; // Por seguridad, asumimos que sí tiene usuarios
        }
    }

    /**
     * Obtener roles para select/dropdown
     */
    public function obtenerRolesParaSelect(): array
    {
        try {
            $roles = $this->rolRepository->listarPorEstado('ACTIVO');
            
            $rolesSelect = [];
            foreach ($roles as $rol) {
                $rolesSelect[] = [
                    'id' => $rol->getId(),
                    'nombre' => $rol->getNombre(),
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
}