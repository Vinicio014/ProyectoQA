<?php

namespace Proyecto\Services;

use Proyecto\Entities\ProductoEntity;
use Proyecto\Repositories\ProductoRepository;
use Proyecto\Repositories\CategoriaRepository;
use Exception;

/**
 * Servicio para la gestión de productos deportivos
 * Contiene la lógica de negocio para productos
 */
class ProductoService
{
    private ProductoRepository $productoRepository;
    private CategoriaRepository $categoriaRepository;

    public function __construct(ProductoRepository $productoRepository, CategoriaRepository $categoriaRepository)
    {
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
            $categoria = $this->categoriaRepository->obtenerPorId($datosProducto['categoria_id']);
            if (!$categoria) {
                return [
                    'exito' => false,
                    'mensaje' => 'La categoría especificada no existe',
                    'datos' => null
                ];
            }

            // Validar que el código no exista
            if ($this->existeProductoPorCodigo($datosProducto['codigo'])) {
                return [
                    'exito' => false,
                    'mensaje' => 'Ya existe un producto con ese código',
                    'datos' => null
                ];
            }

            $producto = new ProductoEntity();
            $producto->setNombre($datosProducto['nombre']);
            $producto->setDescripcion($datosProducto['descripcion'] ?? '');
            $producto->setCodigo($datosProducto['codigo']);
            $producto->setPrecio($datosProducto['precio']);
            $producto->setCosto($datosProducto['costo'] ?? 0);
            $producto->setStock($datosProducto['stock'] ?? 0);
            $producto->setStockMinimo($datosProducto['stock_minimo'] ?? 5);
            $producto->setCategoriaId($datosProducto['categoria_id']);
            $producto->setTipo($datosProducto['tipo'] ?? 'UNIFORME');
            $producto->setGenero($datosProducto['genero'] ?? 'UNISEX');
            $producto->setDeporte($datosProducto['deporte'] ?? '');
            $producto->setMarca($datosProducto['marca'] ?? '');
            $producto->setColor($datosProducto['color'] ?? '');
            $producto->setTallas($datosProducto['tallas'] ?? []);
            $producto->setImagenes($datosProducto['imagenes'] ?? []);
            $producto->setEstado($datosProducto['estado'] ?? 'ACTIVO');

            $productoCreado = $this->productoRepository->crear($producto);

            return [
                'exito' => true,
                'mensaje' => 'Producto creado exitosamente',
                'datos' => $this->formatearProducto($productoCreado)
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
            $producto = $this->productoRepository->obtenerPorId($id);

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
                'datos' => $this->formatearProducto($producto)
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
     * Listar productos con filtros
     */
    public function listarProductos(array $filtros = []): array
    {
        try {
            $productos = $this->productoRepository->listarConFiltros($filtros);
            
            return [
                'exito' => true,
                'mensaje' => 'Productos obtenidos exitosamente',
                'datos' => array_map([$this, 'formatearProducto'], $productos)
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
     * Buscar productos por término
     */
    public function buscarProductos(string $termino): array
    {
        try {
            $productos = $this->productoRepository->buscarPorTermino($termino);
            
            return [
                'exito' => true,
                'mensaje' => 'Búsqueda completada',
                'datos' => array_map([$this, 'formatearProducto'], $productos)
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
     * Actualizar producto
     */
    public function actualizarProducto(int $id, array $datosProducto): array
    {
        try {
            $producto = $this->productoRepository->obtenerPorId($id);

            if (!$producto) {
                return [
                    'exito' => false,
                    'mensaje' => 'Producto no encontrado',
                    'datos' => null
                ];
            }

            // Validar categoría si se está cambiando
            if (isset($datosProducto['categoria_id'])) {
                $categoria = $this->categoriaRepository->obtenerPorId($datosProducto['categoria_id']);
                if (!$categoria) {
                    return [
                        'exito' => false,
                        'mensaje' => 'La categoría especificada no existe',
                        'datos' => null
                    ];
                }
            }

            // Validar código único si se está cambiando
            if (isset($datosProducto['codigo']) && $datosProducto['codigo'] !== $producto->getCodigo()) {
                if ($this->existeProductoPorCodigo($datosProducto['codigo'])) {
                    return [
                        'exito' => false,
                        'mensaje' => 'Ya existe un producto con ese código',
                        'datos' => null
                    ];
                }
            }

            // Actualizar campos
            if (isset($datosProducto['nombre'])) {
                $producto->setNombre($datosProducto['nombre']);
            }
            if (isset($datosProducto['descripcion'])) {
                $producto->setDescripcion($datosProducto['descripcion']);
            }
            if (isset($datosProducto['codigo'])) {
                $producto->setCodigo($datosProducto['codigo']);
            }
            if (isset($datosProducto['precio'])) {
                $producto->setPrecio($datosProducto['precio']);
            }
            if (isset($datosProducto['costo'])) {
                $producto->setCosto($datosProducto['costo']);
            }
            if (isset($datosProducto['stock'])) {
                $producto->setStock($datosProducto['stock']);
            }
            if (isset($datosProducto['stock_minimo'])) {
                $producto->setStockMinimo($datosProducto['stock_minimo']);
            }
            if (isset($datosProducto['categoria_id'])) {
                $producto->setCategoriaId($datosProducto['categoria_id']);
            }
            if (isset($datosProducto['tipo'])) {
                $producto->setTipo($datosProducto['tipo']);
            }
            if (isset($datosProducto['genero'])) {
                $producto->setGenero($datosProducto['genero']);
            }
            if (isset($datosProducto['deporte'])) {
                $producto->setDeporte($datosProducto['deporte']);
            }
            if (isset($datosProducto['marca'])) {
                $producto->setMarca($datosProducto['marca']);
            }
            if (isset($datosProducto['color'])) {
                $producto->setColor($datosProducto['color']);
            }
            if (isset($datosProducto['tallas'])) {
                $producto->setTallas($datosProducto['tallas']);
            }
            if (isset($datosProducto['imagenes'])) {
                $producto->setImagenes($datosProducto['imagenes']);
            }
            if (isset($datosProducto['estado'])) {
                $producto->setEstado($datosProducto['estado']);
            }

            $productoActualizado = $this->productoRepository->actualizar($producto);

            return [
                'exito' => true,
                'mensaje' => 'Producto actualizado exitosamente',
                'datos' => $this->formatearProducto($productoActualizado)
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
    public function actualizarStock(int $id, int $cantidad, string $operacion = 'SET'): array
    {
        try {
            $producto = $this->productoRepository->obtenerPorId($id);

            if (!$producto) {
                return [
                    'exito' => false,
                    'mensaje' => 'Producto no encontrado',
                    'datos' => null
                ];
            }

            $stockActual = $producto->getStock();
            $nuevoStock = $stockActual;

            switch ($operacion) {
                case 'ADD':
                    $nuevoStock = $stockActual + $cantidad;
                    break;
                case 'SUBTRACT':
                    $nuevoStock = $stockActual - $cantidad;
                    if ($nuevoStock < 0) {
                        return [
                            'exito' => false,
                            'mensaje' => 'No hay suficiente stock disponible',
                            'datos' => null
                        ];
                    }
                    break;
                case 'SET':
                default:
                    $nuevoStock = $cantidad;
                    break;
            }

            $producto->setStock($nuevoStock);
            $productoActualizado = $this->productoRepository->actualizar($producto);

            return [
                'exito' => true,
                'mensaje' => 'Stock actualizado exitosamente',
                'datos' => [
                    'producto_id' => $id,
                    'stock_anterior' => $stockActual,
                    'stock_nuevo' => $nuevoStock,
                    'operacion' => $operacion
                ]
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
     * Eliminar producto
     */
    public function eliminarProducto(int $id): array
    {
        try {
            // Verificar que no tenga ventas o pedidos asociados
            if ($this->tieneTransaccionesAsociadas($id)) {
                return [
                    'exito' => false,
                    'mensaje' => 'No se puede eliminar el producto porque tiene transacciones asociadas',
                    'datos' => null
                ];
            }

            $resultado = $this->productoRepository->eliminar($id);

            if ($resultado) {
                return [
                    'exito' => true,
                    'mensaje' => 'Producto eliminado exitosamente',
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
     * Obtener productos con stock bajo
     */
    public function obtenerProductosStockBajo(): array
    {
        try {
            $productos = $this->productoRepository->obtenerProductosStockBajo();
            
            return [
                'exito' => true,
                'mensaje' => 'Productos con stock bajo obtenidos',
                'datos' => array_map([$this, 'formatearProducto'], $productos)
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
     * Obtener productos por categoría
     */
    public function obtenerProductosPorCategoria(int $categoriaId): array
    {
        try {
            $categoria = $this->categoriaRepository->obtenerPorId($categoriaId);
            if (!$categoria) {
                return [
                    'exito' => false,
                    'mensaje' => 'Categoría no encontrada',
                    'datos' => []
                ];
            }

            $productos = $this->productoRepository->obtenerPorCategoria($categoriaId);
            
            return [
                'exito' => true,
                'mensaje' => 'Productos obtenidos exitosamente',
                'datos' => array_map([$this, 'formatearProducto'], $productos)
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
     * Obtener productos para catálogo (frontend)
     */
    public function obtenerCatalogo(array $filtros = []): array
    {
        try {
            // Forzar solo productos activos para catálogo
            $filtros['estado'] = 'ACTIVO';
            $filtros['stock_mayor_que'] = 0; // Solo productos con stock

            $productos = $this->productoRepository->listarConFiltros($filtros);
            
            $catalogo = [];
            foreach ($productos as $producto) {
                $catalogo[] = [
                    'id' => $producto->getId(),
                    'nombre' => $producto->getNombre(),
                    'descripcion' => $producto->getDescripcion(),
                    'codigo' => $producto->getCodigo(),
                    'precio' => $producto->getPrecio(),
                    'stock_disponible' => $producto->getStock() > 0,
                    'categoria' => $this->obtenerNombreCategoria($producto->getCategoriaId()),
                    'tipo' => $producto->getTipo(),
                    'genero' => $producto->getGenero(),
                    'deporte' => $producto->getDeporte(),
                    'marca' => $producto->getMarca(),
                    'color' => $producto->getColor(),
                    'tallas' => $producto->getTallas(),
                    'imagenes' => $producto->getImagenes(),
                    'tiene_stock_bajo' => $producto->getStock() <= $producto->getStockMinimo()
                ];
            }

            return [
                'exito' => true,
                'mensaje' => 'Catálogo obtenido exitosamente',
                'datos' => $catalogo
            ];

        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener catálogo: ' . $e->getMessage(),
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
            $producto = $this->productoRepository->obtenerPorId($productoId);

            if (!$producto) {
                return [
                    'disponible' => false,
                    'mensaje' => 'Producto no encontrado'
                ];
            }

            if ($producto->getEstado() !== 'ACTIVO') {
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
     * Obtener estadísticas de productos
     */
    public function obtenerEstadisticasProductos(): array
    {
        try {
            $total = $this->productoRepository->contarTotal();
            $activos = $this->productoRepository->contarPorEstado('ACTIVO');
            $stockBajo = count($this->productoRepository->obtenerProductosStockBajo());

            $estadisticas = [
                'total_productos' => $total,
                'productos_activos' => $activos,
                'productos_inactivos' => $total - $activos,
                'productos_stock_bajo' => $stockBajo,
                'valor_inventario_total' => $this->calcularValorInventario(),
                'producto_mas_vendido' => null, // Se calculará con DetalleVentaRepository
                'categoria_con_mas_productos' => $this->obtenerCategoriaConMasProductos()
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
     * Validar datos de producto
     */
    public function validarDatosProducto(array $datos): array
    {
        $errores = [];

        if (empty($datos['nombre'])) {
            $errores[] = 'El nombre es requerido';
        }

        if (empty($datos['codigo'])) {
            $errores[] = 'El código es requerido';
        }

        if (!isset($datos['precio']) || $datos['precio'] <= 0) {
            $errores[] = 'El precio debe ser mayor que 0';
        }

        if (!isset($datos['categoria_id']) || $datos['categoria_id'] <= 0) {
            $errores[] = 'La categoría es requerida';
        }

        if (isset($datos['stock']) && $datos['stock'] < 0) {
            $errores[] = 'El stock no puede ser negativo';
        }

        if (isset($datos['costo']) && $datos['costo'] < 0) {
            $errores[] = 'El costo no puede ser negativo';
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }

    /**
     * Formatear producto con información completa
     */
    private function formatearProducto(ProductoEntity $producto): array
    {
        $datos = $producto->toArray();
        
        // Agregar información de la categoría
        $datos['categoria'] = $this->obtenerNombreCategoria($producto->getCategoriaId());
        
        // Agregar indicadores de estado
        $datos['tiene_stock_bajo'] = $producto->getStock() <= $producto->getStockMinimo();
        $datos['stock_disponible'] = $producto->getStock() > 0;
        
        // Calcular margen de ganancia
        if ($producto->getCosto() > 0) {
            $datos['margen_ganancia'] = (($producto->getPrecio() - $producto->getCosto()) / $producto->getCosto()) * 100;
        } else {
            $datos['margen_ganancia'] = 0;
        }

        return $datos;
    }

    /**
     * Obtener nombre de categoría
     */
    private function obtenerNombreCategoria(int $categoriaId): ?string
    {
        try {
            $categoria = $this->categoriaRepository->obtenerPorId($categoriaId);
            return $categoria ? $categoria->getNombre() : null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Verificar si existe producto por código
     */
    private function existeProductoPorCodigo(string $codigo): bool
    {
        try {
            $producto = $this->productoRepository->obtenerPorCodigo($codigo);
            return $producto !== null;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Verificar si el producto tiene transacciones asociadas
     */
    private function tieneTransaccionesAsociadas(int $productoId): bool
    {
        try {
            // Esta lógica se implementará cuando tengamos DetalleVentaRepository y DetallePedidoRepository
            return false;
        } catch (Exception $e) {
            return true; // Por seguridad
        }
    }

    /**
     * Calcular valor total del inventario
     */
    private function calcularValorInventario(): float
    {
        try {
            return $this->productoRepository->calcularValorInventario();
        } catch (Exception $e) {
            return 0.0;
        }
    }

    /**
     * Obtener categoría con más productos
     */
    private function obtenerCategoriaConMasProductos(): ?array
    {
        try {
            return $this->productoRepository->obtenerCategoriaConMasProductos();
        } catch (Exception $e) {
            return null;
        }
    }
}