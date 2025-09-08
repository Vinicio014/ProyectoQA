<?php

namespace Proyecto\Services;

use Proyecto\Entities\UsuarioEntity;
use Proyecto\Repositories\UsuarioRepository;
use Proyecto\Repositories\RolRepository;
use Exception;

/**
 * Servicio para la gestión de usuarios del sistema
 * Contiene la lógica de negocio para usuarios
 */
class UsuarioService
{
    private UsuarioRepository $usuarioRepository;
    private RolRepository $rolRepository;

    public function __construct(UsuarioRepository $usuarioRepository, RolRepository $rolRepository)
    {
        $this->usuarioRepository = $usuarioRepository;
        $this->rolRepository = $rolRepository;
    }

    /**
     * Crear nuevo usuario
     */
    public function crearUsuario(array $datosUsuario): array
    {
        try {
            // Validar que el email no exista
            if ($this->existeUsuarioPorEmail($datosUsuario['email'])) {
                return [
                    'exito' => false,
                    'mensaje' => 'Ya existe un usuario con ese email',
                    'datos' => null
                ];
            }

            // Validar que el rol exista
            $rol = $this->rolRepository->obtenerPorId($datosUsuario['rol_id']);
            if (!$rol) {
                return [
                    'exito' => false,
                    'mensaje' => 'El rol especificado no existe',
                    'datos' => null
                ];
            }

            $usuario = new UsuarioEntity();
            $usuario->setNombre($datosUsuario['nombre']);
            $usuario->setEmail($datosUsuario['email']);
            $usuario->setPassword($datosUsuario['password']); // Se hashea automáticamente
            $usuario->setRolId($datosUsuario['rol_id']);
            $usuario->setTelefono($datosUsuario['telefono'] ?? '');
            $usuario->setEstado($datosUsuario['estado'] ?? 'ACTIVO');

            $usuarioCreado = $this->usuarioRepository->crear($usuario);

            return [
                'exito' => true,
                'mensaje' => 'Usuario creado exitosamente',
                'datos' => $this->formatearUsuario($usuarioCreado)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al crear usuario: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Autenticar usuario
     */
    public function autenticarUsuario(string $email, string $password): array
    {
        try {
            $usuario = $this->usuarioRepository->obtenerPorEmail($email);

            if (!$usuario) {
                return [
                    'exito' => false,
                    'mensaje' => 'Credenciales incorrectas',
                    'datos' => null
                ];
            }

            if (!$usuario->verificarPassword($password)) {
                return [
                    'exito' => false,
                    'mensaje' => 'Credenciales incorrectas',
                    'datos' => null
                ];
            }

            if ($usuario->getEstado() !== 'ACTIVO') {
                return [
                    'exito' => false,
                    'mensaje' => 'Usuario inactivo',
                    'datos' => null
                ];
            }

            // Actualizar último acceso
            $usuario->setUltimoAcceso(new \DateTime());
            $this->usuarioRepository->actualizar($usuario);

            return [
                'exito' => true,
                'mensaje' => 'Autenticación exitosa',
                'datos' => $this->formatearUsuario($usuario)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error en autenticación: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Obtener usuario por ID
     */
    public function obtenerUsuarioPorId(int $id): array
    {
        try {
            $usuario = $this->usuarioRepository->obtenerPorId($id);

            if (!$usuario) {
                return [
                    'exito' => false,
                    'mensaje' => 'Usuario no encontrado',
                    'datos' => null
                ];
            }

            return [
                'exito' => true,
                'mensaje' => 'Usuario encontrado',
                'datos' => $this->formatearUsuario($usuario)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener usuario: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Listar usuarios con filtros
     */
    public function listarUsuarios(array $filtros = []): array
    {
        try {
            $usuarios = $this->usuarioRepository->listarConFiltros($filtros);
            
            return [
                'exito' => true,
                'mensaje' => 'Usuarios obtenidos exitosamente',
                'datos' => array_map([$this, 'formatearUsuario'], $usuarios)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al listar usuarios: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Actualizar usuario
     */
    public function actualizarUsuario(int $id, array $datosUsuario): array
    {
        try {
            $usuario = $this->usuarioRepository->obtenerPorId($id);

            if (!$usuario) {
                return [
                    'exito' => false,
                    'mensaje' => 'Usuario no encontrado',
                    'datos' => null
                ];
            }

            // Validar email único si se está cambiando
            if (isset($datosUsuario['email']) && $datosUsuario['email'] !== $usuario->getEmail()) {
                if ($this->existeUsuarioPorEmail($datosUsuario['email'])) {
                    return [
                        'exito' => false,
                        'mensaje' => 'Ya existe un usuario con ese email',
                        'datos' => null
                    ];
                }
            }

            // Validar rol si se está cambiando
            if (isset($datosUsuario['rol_id'])) {
                $rol = $this->rolRepository->obtenerPorId($datosUsuario['rol_id']);
                if (!$rol) {
                    return [
                        'exito' => false,
                        'mensaje' => 'El rol especificado no existe',
                        'datos' => null
                    ];
                }
            }

            // Actualizar campos
            if (isset($datosUsuario['nombre'])) {
                $usuario->setNombre($datosUsuario['nombre']);
            }
            if (isset($datosUsuario['email'])) {
                $usuario->setEmail($datosUsuario['email']);
            }
            if (isset($datosUsuario['password'])) {
                $usuario->setPassword($datosUsuario['password']);
            }
            if (isset($datosUsuario['rol_id'])) {
                $usuario->setRolId($datosUsuario['rol_id']);
            }
            if (isset($datosUsuario['telefono'])) {
                $usuario->setTelefono($datosUsuario['telefono']);
            }
            if (isset($datosUsuario['estado'])) {
                $usuario->setEstado($datosUsuario['estado']);
            }

            $usuarioActualizado = $this->usuarioRepository->actualizar($usuario);

            return [
                'exito' => true,
                'mensaje' => 'Usuario actualizado exitosamente',
                'datos' => $this->formatearUsuario($usuarioActualizado)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al actualizar usuario: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Cambiar contraseña de usuario
     */
    public function cambiarPassword(int $id, string $passwordActual, string $passwordNuevo): array
    {
        try {
            $usuario = $this->usuarioRepository->obtenerPorId($id);

            if (!$usuario) {
                return [
                    'exito' => false,
                    'mensaje' => 'Usuario no encontrado',
                    'datos' => null
                ];
            }

            if (!$usuario->verificarPassword($passwordActual)) {
                return [
                    'exito' => false,
                    'mensaje' => 'Contraseña actual incorrecta',
                    'datos' => null
                ];
            }

            $usuario->setPassword($passwordNuevo);
            $this->usuarioRepository->actualizar($usuario);

            return [
                'exito' => true,
                'mensaje' => 'Contraseña cambiada exitosamente',
                'datos' => null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al cambiar contraseña: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Eliminar usuario (cambiar estado)
     */
    public function eliminarUsuario(int $id): array
    {
        try {
            $resultado = $this->usuarioRepository->eliminar($id);

            if ($resultado) {
                return [
                    'exito' => true,
                    'mensaje' => 'Usuario eliminado exitosamente',
                    'datos' => null
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo eliminar el usuario',
                'datos' => null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al eliminar usuario: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Verificar si existe usuario por email
     */
    private function existeUsuarioPorEmail(string $email): bool
    {
        try {
            $usuario = $this->usuarioRepository->obtenerPorEmail($email);
            return $usuario !== null;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Formatear usuario para respuesta (sin datos sensibles)
     */
    private function formatearUsuario(UsuarioEntity $usuario): array
    {
        $datos = $usuario->toArray();
        unset($datos['password']); // Remover password por seguridad
        
        // Obtener información del rol
        try {
            $rol = $this->rolRepository->obtenerPorId($usuario->getRolId());
            if ($rol) {
                $datos['rol'] = [
                    'id' => $rol->getId(),
                    'nombre' => $rol->getNombre(),
                    'descripcion' => $rol->getDescripcion()
                ];
            }
        } catch (Exception $e) {
            $datos['rol'] = null;
        }

        return $datos;
    }

    /**
     * Obtener perfil de usuario con estadísticas
     */
    public function obtenerPerfilUsuario(int $id): array
    {
        try {
            $usuario = $this->usuarioRepository->obtenerPorId($id);

            if (!$usuario) {
                return [
                    'exito' => false,
                    'mensaje' => 'Usuario no encontrado',
                    'datos' => null
                ];
            }

            $perfil = $this->formatearUsuario($usuario);
            
            // Agregar estadísticas adicionales
            $perfil['estadisticas'] = [
                'fecha_registro' => $usuario->getFechaCreacion()->format('d/m/Y'),
                'ultimo_acceso' => $usuario->getUltimoAcceso() ? $usuario->getUltimoAcceso()->format('d/m/Y H:i') : 'Nunca',
                'estado' => $usuario->getEstado()
            ];

            return [
                'exito' => true,
                'mensaje' => 'Perfil obtenido exitosamente',
                'datos' => $perfil
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener perfil: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }
}