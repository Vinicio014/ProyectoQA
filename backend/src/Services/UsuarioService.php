<?php

namespace App\Services;

use App\Entities\UsuarioEntity;
use App\Repositories\UsuarioRepository;
use App\Repositories\RolRepository;
use Exception;

/**
 * Servicio para la gestión de usuarios del sistema
 * Contiene la lógica de negocio para usuarios
 */
class UsuarioService
{
    private UsuarioRepository $usuarioRepository;
    private RolRepository $rolRepository;

    public function __construct(
        UsuarioRepository $usuarioRepository, 
        RolRepository $rolRepository
    ) {
        $this->usuarioRepository = $usuarioRepository;
        $this->rolRepository = $rolRepository;
    }

    /**
     * Crear nuevo usuario
     */
    public function crearUsuario(array $datosUsuario): array
    {
        try {
            // Validar que el correo no exista
            if ($this->existeUsuarioPorCorreo($datosUsuario['correo'])) {
                return [
                    'exito' => false,
                    'mensaje' => 'Ya existe un usuario con ese correo',
                    'datos' => null
                ];
            }

            // Validar que el rol exista
            $rol = $this->rolRepository->findById($datosUsuario['idRol']);
            if (!$rol) {
                return [
                    'exito' => false,
                    'mensaje' => 'El rol especificado no existe',
                    'datos' => null
                ];
            }

            $usuario = new UsuarioEntity();
            $usuario->setNombre($datosUsuario['nombre']);
            $usuario->setCorreo($datosUsuario['correo']);
            $usuario->setContrasenia($datosUsuario['contrasenia']);
            $usuario->setIdRol($datosUsuario['idRol']);
            $usuario->setEsActivo($datosUsuario['esActivo'] ?? true);

            // Hash de contraseña
            $usuario->hashContrasenia();

            $usuarioCreado = $this->usuarioRepository->create($usuario);

            return [
                'exito' => true,
                'mensaje' => 'Usuario creado exitosamente',
                'datos' => $usuarioCreado ? $this->formatearUsuario($usuarioCreado) : null
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
    public function autenticarUsuario(string $correo, string $contrasenia): array
    {
        try {
            $usuario = $this->usuarioRepository->findByCorreo($correo);

            if (!$usuario) {
                return [
                    'exito' => false,
                    'mensaje' => 'Credenciales incorrectas',
                    'datos' => null
                ];
            }

            if (!$usuario->verificarContrasenia($contrasenia)) {
                return [
                    'exito' => false,
                    'mensaje' => 'Credenciales incorrectas',
                    'datos' => null
                ];
            }

            if (!$usuario->getEsActivo()) {
                return [
                    'exito' => false,
                    'mensaje' => 'Usuario inactivo',
                    'datos' => null
                ];
            }

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
            $usuario = $this->usuarioRepository->findById($id);

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
     * Listar todos los usuarios
     */
    public function listarUsuarios(): array
    {
        try {
            $usuarios = $this->usuarioRepository->findAll();
            
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
     * Listar usuarios activos
     */
    public function listarUsuariosActivos(): array
    {
        try {
            $usuarios = $this->usuarioRepository->findActive();
            
            return [
                'exito' => true,
                'mensaje' => 'Usuarios activos obtenidos exitosamente',
                'datos' => array_map([$this, 'formatearUsuario'], $usuarios)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al listar usuarios activos: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Obtener usuarios por rol
     */
    public function obtenerUsuariosPorRol(int $rolId): array
    {
        try {
            $usuarios = $this->usuarioRepository->findByRole($rolId);
            
            return [
                'exito' => true,
                'mensaje' => 'Usuarios obtenidos exitosamente',
                'datos' => array_map([$this, 'formatearUsuario'], $usuarios)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener usuarios por rol: ' . $e->getMessage(),
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
            $usuario = $this->usuarioRepository->findById($id);

            if (!$usuario) {
                return [
                    'exito' => false,
                    'mensaje' => 'Usuario no encontrado',
                    'datos' => null
                ];
            }

            // Validar correo único si se está cambiando
            if (isset($datosUsuario['correo']) && $datosUsuario['correo'] !== $usuario->getCorreo()) {
                if ($this->existeUsuarioPorCorreo($datosUsuario['correo'], $id)) {
                    return [
                        'exito' => false,
                        'mensaje' => 'Ya existe un usuario con ese correo',
                        'datos' => null
                    ];
                }
            }

            // Validar rol si se está cambiando
            if (isset($datosUsuario['idRol'])) {
                $rol = $this->rolRepository->findById($datosUsuario['idRol']);
                if (!$rol) {
                    return [
                        'exito' => false,
                        'mensaje' => 'El rol especificado no existe',
                        'datos' => null
                    ];
                }
                $usuario->setIdRol($datosUsuario['idRol']);
            }

            // Actualizar campos
            if (isset($datosUsuario['nombre'])) {
                $usuario->setNombre($datosUsuario['nombre']);
            }
            if (isset($datosUsuario['correo'])) {
                $usuario->setCorreo($datosUsuario['correo']);
            }
            if (isset($datosUsuario['contrasenia'])) {
                $usuario->setContrasenia($datosUsuario['contrasenia']);
                $usuario->hashContrasenia();
            }
            if (isset($datosUsuario['esActivo'])) {
                $usuario->setEsActivo($datosUsuario['esActivo']);
            }

            $resultado = $this->usuarioRepository->update($usuario);

            if ($resultado) {
                $usuarioActualizado = $this->usuarioRepository->findById($id);
                return [
                    'exito' => true,
                    'mensaje' => 'Usuario actualizado exitosamente',
                    'datos' => $this->formatearUsuario($usuarioActualizado)
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo actualizar el usuario',
                'datos' => null
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
    public function cambiarContrasenia(int $id, string $contraseniaActual, string $contraseniaNueva): array
    {
        try {
            $usuario = $this->usuarioRepository->findById($id);

            if (!$usuario) {
                return [
                    'exito' => false,
                    'mensaje' => 'Usuario no encontrado',
                    'datos' => null
                ];
            }

            if (!$usuario->verificarContrasenia($contraseniaActual)) {
                return [
                    'exito' => false,
                    'mensaje' => 'Contraseña actual incorrecta',
                    'datos' => null
                ];
            }

            $resultado = $this->usuarioRepository->changePassword($id, $contraseniaNueva);

            if ($resultado) {
                return [
                    'exito' => true,
                    'mensaje' => 'Contraseña cambiada exitosamente',
                    'datos' => null
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo cambiar la contraseña',
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
     * Eliminar usuario (soft delete)
     */
    public function eliminarUsuario(int $id): array
    {
        try {
            $resultado = $this->usuarioRepository->delete($id);

            if ($resultado) {
                return [
                    'exito' => true,
                    'mensaje' => 'Usuario desactivado exitosamente',
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
     * Activar usuario
     */
    public function activarUsuario(int $id): array
    {
        try {
            $resultado = $this->usuarioRepository->activate($id);

            if ($resultado) {
                return [
                    'exito' => true,
                    'mensaje' => 'Usuario activado exitosamente',
                    'datos' => null
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo activar el usuario',
                'datos' => null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al activar usuario: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Verificar si existe usuario por correo
     */
    private function existeUsuarioPorCorreo(string $correo, ?int $excludeId = null): bool
    {
        try {
            return $this->usuarioRepository->existsByCorreo($correo, $excludeId);
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
        unset($datos['contrasenia']); // Remover contraseña por seguridad
        
        // Obtener información del rol
        try {
            $rol = $this->rolRepository->findById($usuario->getIdRol());
            if ($rol) {
                $datos['rol'] = [
                    'id' => $rol->getIdRol(),
                    'descripcion' => $rol->getDescripcion()
                ];
            }
        } catch (Exception $e) {
            $datos['rol'] = null;
        }

        return $datos;
    }

    /**
     * Validar datos de usuario
     */
    public function validarDatosUsuario(array $datos): array
    {
        $errores = [];

        if (empty($datos['nombre'])) {
            $errores[] = 'El nombre es requerido';
        }

        if (empty($datos['correo'])) {
            $errores[] = 'El correo es requerido';
        } elseif (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo no es válido';
        }

        if (empty($datos['contrasenia'])) {
            $errores[] = 'La contraseña es requerida';
        } elseif (strlen($datos['contrasenia']) < 6) {
            $errores[] = 'La contraseña debe tener al menos 6 caracteres';
        }

        if (empty($datos['idRol']) || $datos['idRol'] <= 0) {
            $errores[] = 'El rol es requerido';
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }
}