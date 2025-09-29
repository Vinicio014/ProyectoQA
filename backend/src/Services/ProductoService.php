<?php

namespace App\Services;

use App\Entities\ProductoEntity;
use App\Repositories\ProductoRepository;
use App\Repositories\CategoriaRepository;
use Exception;

/**
 * Servicio para la gestión de productos deportivos
 * Contiene la lógica de negocio para productos
 */
class ProductoService
{
    private ProductoRepository $productoRepository;
    private CategoriaRepository $categoriaRepository;

    public function __construct(
        ProductoRepository $productoRepository, 
        CategoriaRepository $categoriaRepository
    ) {
        $this->productoRepository = $productoRepository;
        $this->categoriaRepository = $categoriaRepository;
    }

    /**
     * Crear nuevo producto
     */
    public function crearProducto(array $datosProducto): array
    {
        try {
            // Validar que la categoría exista
            $categoria = $this->categoriaRepository->findById($datosProducto['idCategoria']);
            if (!$categoria) {
                return [
                    'exito' => false,
                    'mensaje' => 'La categoría especificada no existe',
                    'datos' => null
                ];
            }

            $producto = new ProductoEntity();
            $producto->setNombre($datosProducto['nombre']);
            $producto->setMarca($datosProducto['marca'] ?? '');
            $producto->setIdCategoria($datosProducto['idCategoria']);
            $producto->setPrecioUnitario($datosProducto['precioUnitario']);
            $producto->setStock($datosProducto['stock'] ?? 0);
            $producto->setEsActivo($datosProducto['esActivo'] ?? true);

            $productoCreado = $this->productoRepository->create($producto);

            return [
                'exito' => true,
                'mensaje' => 'Producto creado exitosamente',
                'datos' => $productoCreado ? $productoCreado->toArray() : null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al crear producto: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Obtener producto por ID
     */
    public function obtenerProductoPorId(int $id): array
    {
        try {
            $producto = $this->productoRepository->findById($id);

            if (!$producto) {
                return [
                    'exito' => false,
                    'mensaje' => 'Producto no encontrado',
                    'datos' => null
                ];
            }

            return [
                'exito' => true,
                'mensaje' => 'Producto encontrado',
                'datos' => $producto->toArray()
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener producto: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Listar todos los productos
     */
    public function listarProductos(): array
    {
        try {
            $productos = $this->productoRepository->findAll();
            
            return [
                'exito' => true,
                'mensaje' => 'Productos obtenidos exitosamente',
                'datos' => array_map(fn($p) => $p->toArray(), $productos)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al listar productos: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Listar productos activos
     */
    public function listarProductosActivos(): array
    {
        try {
            $productos = $this->productoRepository->findActive();
            
            return [
                'exito' => true,
                'mensaje' => 'Productos activos obtenidos exitosamente',
                'datos' => array_map(fn($p) => $p->toArray(), $productos)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al listar productos activos: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Buscar productos por nombre
     */
    public function buscarProductos(string $termino): array
    {
        try {
            $productos = $this->productoRepository->searchByName($termino);
            
            return [
                'exito' => true,
                'mensaje' => 'Búsqueda completada',
                'datos' => array_map(fn($p) => $p->toArray(), $productos)
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
     * Obtener productos por categoría
     */
    public function obtenerProductosPorCategoria(int $categoriaId): array
    {
        try {
            $categoria = $this->categoriaRepository->findById($categoriaId);
            if (!$categoria) {
                return [
                    'exito' => false,
                    'mensaje' => 'Categoría no encontrada',
                    'datos' => []
                ];
            }

            $productos = $this->productoRepository->findByCategory($categoriaId);
            
            return [
                'exito' => true,
                'mensaje' => 'Productos obtenidos exitosamente',
                'datos' => array_map(fn($p) => $p->toArray(), $productos)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener productos por categoría: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Actualizar producto
     */
    public function actualizarProducto(int $id, array $datosProducto): array
    {
        try {
            $producto = $this->productoRepository->findById($id);

            if (!$producto) {
                return [
                    'exito' => false,
                    'mensaje' => 'Producto no encontrado',
                    'datos' => null
                ];
            }

            // Validar categoría si se está cambiando
            if (isset($datosProducto['idCategoria'])) {
                $categoria = $this->categoriaRepository->findById($datosProducto['idCategoria']);
                if (!$categoria) {
                    return [
                        'exito' => false,
                        'mensaje' => 'La categoría especificada no existe',
                        'datos' => null
                    ];
                }
                $producto->setIdCategoria($datosProducto['idCategoria']);
            }

            // Actualizar campos
            if (isset($datosProducto['nombre'])) {
                $producto->setNombre($datosProducto['nombre']);
            }
            if (isset($datosProducto['marca'])) {
                $producto->setMarca($datosProducto['marca']);
            }
            if (isset($datosProducto['precioUnitario'])) {
                $producto->setPrecioUnitario($datosProducto['precioUnitario']);
            }
            if (isset($datosProducto['stock'])) {
                $producto->setStock($datosProducto['stock']);
            }
            if (isset($datosProducto['esActivo'])) {
                $producto->setEsActivo($datosProducto['esActivo']);
            }

            $resultado = $this->productoRepository->update($producto);

            if ($resultado) {
                $productoActualizado = $this->productoRepository->findById($id);
                return [
                    'exito' => true,
                    'mensaje' => 'Producto actualizado exitosamente',
                    'datos' => $productoActualizado->toArray()
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo actualizar el producto',
                'datos' => null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al actualizar producto: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Actualizar stock del producto
     */
    public function actualizarStock(int $id, int $cantidad): array
    {
        try {
            $producto = $this->productoRepository->findById($id);

            if (!$producto) {
                return [
                    'exito' => false,
                    'mensaje' => 'Producto no encontrado',
                    'datos' => null
                ];
            }

            if ($cantidad < 0) {
                return [
                    'exito' => false,
                    'mensaje' => 'El stock no puede ser negativo',
                    'datos' => null
                ];
            }

            $stockAnterior = $producto->getStock();
            $producto->setStock($cantidad);
            $resultado = $this->productoRepository->update($producto);

            if ($resultado) {
                return [
                    'exito' => true,
                    'mensaje' => 'Stock actualizado exitosamente',
                    'datos' => [
                        'producto_id' => $id,
                        'stock_anterior' => $stockAnterior,
                        'stock_nuevo' => $cantidad
                    ]
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo actualizar el stock',
                'datos' => null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al actualizar stock: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Incrementar stock
     */
    public function incrementarStock(int $id, int $cantidad): array
    {
        try {
            $resultado = $this->productoRepository->increaseStock($id, $cantidad);

            if ($resultado) {
                $producto = $this->productoRepository->findById($id);
                return [
                    'exito' => true,
                    'mensaje' => 'Stock incrementado exitosamente',
                    'datos' => [
                        'producto_id' => $id,
                        'cantidad_agregada' => $cantidad,
                        'stock_actual' => $producto->getStock()
                    ]
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo incrementar el stock',
                'datos' => null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al incrementar stock: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Reducir stock
     */
    public function reducirStock(int $id, int $cantidad): array
    {
        try {
            $producto = $this->productoRepository->findById($id);

            if (!$producto) {
                return [
                    'exito' => false,
                    'mensaje' => 'Producto no encontrado',
                    'datos' => null
                ];
            }

            if ($producto->getStock() < $cantidad) {
                return [
                    'exito' => false,
                    'mensaje' => 'Stock insuficiente. Disponible: ' . $producto->getStock(),
                    'datos' => null
                ];
            }

            $resultado = $this->productoRepository->reduceStock($id, $cantidad);

            if ($resultado) {
                $productoActualizado = $this->productoRepository->findById($id);
                return [
                    'exito' => true,
                    'mensaje' => 'Stock reducido exitosamente',
                    'datos' => [
                        'producto_id' => $id,
                        'cantidad_reducida' => $cantidad,
                        'stock_actual' => $productoActualizado->getStock()
                    ]
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo reducir el stock',
                'datos' => null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al reducir stock: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Eliminar producto (soft delete)
     */
    public function eliminarProducto(int $id): array
    {
        try {
            $resultado = $this->productoRepository->delete($id);

            if ($resultado) {
                return [
                    'exito' => true,
                    'mensaje' => 'Producto desactivado exitosamente',
                    'datos' => null
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo eliminar el producto',
                'datos' => null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al eliminar producto: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Activar producto
     */
    public function activarProducto(int $id): array
    {
        try {
            $resultado = $this->productoRepository->activate($id);

            if ($resultado) {
                return [
                    'exito' => true,
                    'mensaje' => 'Producto activado exitosamente',
                    'datos' => null
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo activar el producto',
                'datos' => null
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al activar producto: ' . $e->getMessage(),
                'datos' => null
            ];
        }
    }

    /**
     * Obtener productos con stock bajo
     */
    public function obtenerProductosStockBajo(): array
    {
        try {
            $productos = $this->productoRepository->findLowStock();
            
            return [
                'exito' => true,
                'mensaje' => 'Productos con stock bajo obtenidos',
                'datos' => array_map(fn($p) => $p->toArray(), $productos)
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener productos con stock bajo: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Verificar disponibilidad de producto
     */
    public function verificarDisponibilidad(int $productoId, int $cantidad = 1): array
    {
        try {
            $producto = $this->productoRepository->findById($productoId);

            if (!$producto) {
                return [
                    'disponible' => false,
                    'mensaje' => 'Producto no encontrado'
                ];
            }

            if (!$producto->getEsActivo()) {
                return [
                    'disponible' => false,
                    'mensaje' => 'Producto no disponible'
                ];
            }

            if ($producto->getStock() < $cantidad) {
                return [
                    'disponible' => false,
                    'mensaje' => 'Stock insuficiente',
                    'stock_disponible' => $producto->getStock()
                ];
            }

            return [
                'disponible' => true,
                'mensaje' => 'Producto disponible',
                'stock_disponible' => $producto->getStock()
            ];

        } catch (Exception $e) {
            return [
                'disponible' => false,
                'mensaje' => 'Error al verificar disponibilidad: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validar datos de producto
     */
    public function validarDatosProducto(array $datos): array
    {
        $errores = [];

        if (empty($datos['nombre'])) {
            $errores[] = 'El nombre es requerido';
        }

        if (!isset($datos['precioUnitario']) || $datos['precioUnitario'] <= 0) {
            $errores[] = 'El precio debe ser mayor que 0';
        }

        if (!isset($datos['idCategoria']) || $datos['idCategoria'] <= 0) {
            $errores[] = 'La categoría es requerida';
        }

        if (isset($datos['stock']) && $datos['stock'] < 0) {
            $errores[] = 'El stock no puede ser negativo';
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }
}