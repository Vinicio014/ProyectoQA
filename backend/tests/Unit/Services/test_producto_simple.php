<?php
/**
 * PRUEBAS UNITARIAS SIMPLES PARA EL SERVICIO DE PRODUCTO
 * Navegador: http://localhost/ProyectoQA/backend/tests/Unit/Services/test_producto_simple.php
 */

// Cargar archivos necesarios (en orden correcto)
require_once __DIR__ . '/../../../../backend/src/Entities/Interfaces/ProductoEntityInterface.php';
require_once __DIR__ . '/../../../../backend/src/Entities/Interfaces/CategoriaEntityInterface.php';
require_once __DIR__ . '/../../../../backend/src/Entities/ProductoEntity.php';
require_once __DIR__ . '/../../../../backend/src/Entities/CategoriaEntity.php';
@require_once __DIR__ . '/../../../../backend/src/Infraestructura/ConnectionManager.php';
require_once __DIR__ . '/../../../../backend/src/Repositories/ProductoRepository.php';
require_once __DIR__ . '/../../../../backend/src/Repositories/CategoriaRepository.php';
require_once __DIR__ . '/../../../../backend/src/Services/ProductoService.php';

use App\Services\ProductoService;
use App\Repositories\ProductoRepository;
use App\Repositories\CategoriaRepository;

// ============================================
// CLASE SIMPLE PARA HACER ASSERTS
// ============================================
class SimpleTest
{
    private static $passed = 0;
    private static $failed = 0;
    private static $testName = '';

    public static function describe($name)
    {
        self::$testName = $name;
        echo "<br>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━<br>\n";
        echo "📝 TEST: $name<br>\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━<br>\n";
    }

    public static function assertTrue($condition, $message)
    {
        if ($condition) {
            self::$passed++;
            echo "✅ PASS: $message<br>\n";
        } else {
            self::$failed++;
            echo "❌ FAIL: $message<br>\n";
            echo "   Esperado: true, Obtenido: false<br>\n";
        }
    }

    public static function assertFalse($condition, $message)
    {
        if (!$condition) {
            self::$passed++;
            echo "✅ PASS: $message<br>\n";
        } else {
            self::$failed++;
            echo "❌ FAIL: $message<br>\n";
            echo "   Esperado: false, Obtenido: true<br>\n";
        }
    }

    public static function assertEquals($expected, $actual, $message)
    {
        if ($expected === $actual) {
            self::$passed++;
            echo "✅ PASS: $message<br>\n";
        } else {
            self::$failed++;
            echo "❌ FAIL: $message<br>\n";
            echo "   Esperado: " . var_export($expected, true) . "<br>\n";
            echo "   Obtenido: " . var_export($actual, true) . "<br>\n";
        }
    }

    public static function assertContains($needle, $haystack, $message)
    {
        if (strpos($haystack, $needle) !== false) {
            self::$passed++;
            echo "✅ PASS: $message<br>\n";
        } else {
            self::$failed++;
            echo "❌ FAIL: $message<br>\n";
            echo "   No se encontró '$needle' en '$haystack'<br>\n";
        }
    }

    public static function assertNotNull($value, $message)
    {
        if ($value !== null) {
            self::$passed++;
            echo "✅ PASS: $message<br>\n";
        } else {
            self::$failed++;
            echo "❌ FAIL: $message<br>\n";
            echo "   El valor es NULL<br>\n";
        }
    }

    public static function summary()
    {
        echo "<br>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━<br>\n";
        echo "📊 RESUMEN DE PRUEBAS<br>\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━<br>\n";
        echo "✅ Pasadas: " . self::$passed . "<br>\n";
        echo "❌ Fallidas: " . self::$failed . "<br>\n";
        echo "📈 Total: " . (self::$passed + self::$failed) . "<br>\n";
        
        if (self::$failed === 0) {
            echo "<br>🎉 ¡TODAS LAS PRUEBAS PASARON!<br>\n";
        } else {
            echo "<br>⚠️  Hay pruebas que fallaron<br>\n";
        }
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━<br>\n";
    }
}

// ============================================
// MOCK SIMPLE DE REPOSITORIO
// ============================================
class MockProductoRepository extends ProductoRepository
{
    private $productos = [];
    
    public function __construct()
    {
        $producto1 = new \App\Entities\ProductoEntity();
        $producto1->setIdProducto(1);
        $producto1->setNombre('Balón Nike');
        $producto1->setMarca('Nike');
        $producto1->setStock(50);
        $producto1->setPrecioUnitario(450.50);
        $producto1->setEsActivo(true);
        $producto1->setIdCategoria(1);
        
        $this->productos[1] = $producto1;
    }
    
    public function findById(int $id): ?\App\Entities\ProductoEntity
    {
        return $this->productos[$id] ?? null;
    }
    
    public function reduceStock(int $id, int $quantity): bool
    {
        if (isset($this->productos[$id])) {
            $stockActual = $this->productos[$id]->getStock();
            if ($stockActual >= $quantity) {
                $this->productos[$id]->setStock($stockActual - $quantity);
                return true;
            }
        }
        return false;
    }
    
    public function create($producto): ?\App\Entities\ProductoEntity
    {
        $nuevoId = count($this->productos) + 1;
        $producto->setIdProducto($nuevoId);
        $this->productos[$nuevoId] = $producto;
        return $producto;
    }
}

class MockCategoriaRepository extends CategoriaRepository
{
    private $categorias = [];
    
    public function __construct()
    {
        $categoria = new \App\Entities\CategoriaEntity();
        $categoria->setIdCategoria(1);
        $categoria->setDescripcion('Deportes');
        $this->categorias[1] = $categoria;
    }
    
    public function findById(int $id): ?\App\Entities\CategoriaEntity
    {
        return $this->categorias[$id] ?? null;
    }
}

// ============================================
// EJECUTAR PRUEBAS
// ============================================

// PRUEBA 1: Reducir stock exitosamente
SimpleTest::describe('Reducir stock de producto exitosamente');

$mockProductoRepo = new MockProductoRepository();
$mockCategoriaRepo = new MockCategoriaRepository();
$service = new ProductoService($mockProductoRepo, $mockCategoriaRepo);
$resultado = $service->reducirStock(1, 10);

SimpleTest::assertTrue($resultado['exito'], 'La operación debe ser exitosa');
SimpleTest::assertEquals('Stock reducido exitosamente', $resultado['mensaje'], 'El mensaje debe ser correcto');
SimpleTest::assertNotNull($resultado['datos'], 'Debe retornar datos');
SimpleTest::assertEquals(1, $resultado['datos']['producto_id'], 'El ID del producto debe ser 1');
SimpleTest::assertEquals(10, $resultado['datos']['cantidad_reducida'], 'La cantidad reducida debe ser 10');
SimpleTest::assertEquals(40, $resultado['datos']['stock_actual'], 'El stock actual debe ser 40');

// PRUEBA 2: Reducir stock insuficiente
SimpleTest::describe('Intentar reducir más stock del disponible');

$mockProductoRepo2 = new MockProductoRepository();
$mockCategoriaRepo2 = new MockCategoriaRepository();
$service2 = new ProductoService($mockProductoRepo2, $mockCategoriaRepo2);
$resultado2 = $service2->reducirStock(1, 100);

SimpleTest::assertFalse($resultado2['exito'], 'La operación debe fallar');
SimpleTest::assertContains('Stock insuficiente', $resultado2['mensaje'], 'El mensaje debe indicar stock insuficiente');
SimpleTest::assertContains('50', $resultado2['mensaje'], 'Debe mostrar el stock disponible');

// PRUEBA 3: Crear producto con categoría válida
SimpleTest::describe('Crear producto con categoría válida');

$mockProductoRepo3 = new MockProductoRepository();
$mockCategoriaRepo3 = new MockCategoriaRepository();
$service3 = new ProductoService($mockProductoRepo3, $mockCategoriaRepo3);

$datosProducto = [
    'nombre' => 'Raqueta Wilson',
    'marca' => 'Wilson',
    'idCategoria' => 1,
    'precioUnitario' => 850.00,
    'stock' => 15,
    'esActivo' => true
];

$resultado3 = $service3->crearProducto($datosProducto);

SimpleTest::assertTrue($resultado3['exito'], 'El producto debe crearse exitosamente');
SimpleTest::assertEquals('Producto creado exitosamente', $resultado3['mensaje'], 'El mensaje debe ser correcto');
SimpleTest::assertNotNull($resultado3['datos'], 'Debe retornar los datos del producto');
SimpleTest::assertEquals('Raqueta Wilson', $resultado3['datos']['nombre'], 'El nombre debe coincidir');
SimpleTest::assertEquals('Wilson', $resultado3['datos']['marca'], 'La marca debe coincidir');

// PRUEBA 4: Crear producto con categoría inexistente
SimpleTest::describe('Intentar crear producto con categoría inexistente');

$mockProductoRepo4 = new MockProductoRepository();
$mockCategoriaRepo4 = new MockCategoriaRepository();
$service4 = new ProductoService($mockProductoRepo4, $mockCategoriaRepo4);

$datosProductoInvalido = [
    'nombre' => 'Producto Test',
    'marca' => 'Test',
    'idCategoria' => 999,
    'precioUnitario' => 100.00,
    'stock' => 10
];

$resultado4 = $service4->crearProducto($datosProductoInvalido);

SimpleTest::assertFalse($resultado4['exito'], 'La operación debe fallar');
SimpleTest::assertEquals('La categoría especificada no existe', $resultado4['mensaje'], 'El mensaje debe indicar categoría inexistente');

// Mostrar resumen
SimpleTest::summary();
?>