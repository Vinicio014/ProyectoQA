<?php
/**
 * Sistema de interconexión de capas usando POO puro
 * Mantiene consistencia con la arquitectura del proyecto
 */

// Debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Autoload y configuración
if (!class_exists('Database')) {
    require_once __DIR__ . '/../conf/database.php';
}


// Incluir manualmente las clases necesarias con namespaces
require_once __DIR__ . '/../src/Entities/Interfaces/ProductoEntityInterface.php';
require_once __DIR__ . '/../src/Entities/Interfaces/CategoriaEntityInterface.php';

require_once __DIR__ . '/../src/Entities/ProductoEntity.php';
require_once __DIR__ . '/../src/Entities/CategoriaEntity.php';
require_once __DIR__ . '/../src/Repositories/ProductoRepository.php';
require_once __DIR__ . '/../src/Repositories/CategoriaRepository.php';
require_once __DIR__ . '/../src/Services/ProductoService.php';
require_once __DIR__ . '/../src/Infraestructura/ConnectionManager.php';
require_once __DIR__ . '/../src/Infraestructura/DatabaseFactory.php';

// Imports con namespace
use App\Entities\ProductoEntity;
use App\Entities\CategoriaEntity;
use App\Repositories\ProductoRepository;
use App\Repositories\CategoriaRepository;
use App\Services\ProductoService;
use App\Infraestructura\ConnectionManager;
use App\Infraestructura\DatabaseFactory;

// Headers HTTP
class HttpHeaders 
{
    public static function configurar(): void 
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Content-Type: application/json; charset=utf-8');
        
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
}


/**
 * Manejador de respuestas HTTP
 */
class ResponseHandler 
{
    public function enviarRespuesta(array $data, int $codigo = 200): void 
    {
        http_response_code($codigo);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    public function error(string $mensaje, int $codigo = 400): void 
    {
        $this->enviarRespuesta([
            'exito' => false,
            'mensaje' => $mensaje,
            'datos' => null
        ], $codigo);
    }
}

/**
 * Manejador de requests HTTP
 */
class RequestHandler 
{
    public function obtenerDatos(): array 
    {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
    
    public function obtenerParametros(): array 
    {
        return $_GET;
    }
    
    public function obtenerMetodo(): string 
    {
        return $_SERVER['REQUEST_METHOD'];
    }
    
    public function obtenerRuta(): array 
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));
        
        // Remover segmentos hasta encontrar el archivo PHP
        $phpFileIndex = -1;
        for ($i = 0; $i < count($segments); $i++) {
            if (strpos($segments[$i], '.php') !== false) {
                $phpFileIndex = $i;
                break;
            }
        }
        
        // Tomar segmentos después del archivo PHP
        if ($phpFileIndex !== -1 && isset($segments[$phpFileIndex + 1])) {
            $recurso = $segments[$phpFileIndex + 1];
            $id = $segments[$phpFileIndex + 2] ?? null;
            $accion = $segments[$phpFileIndex + 3] ?? null;
        } else {
            $recurso = '';
            $id = null;
            $accion = null;
        }
        
        return [
            'recurso' => $recurso,
            'id' => $id,
            'accion' => $accion
        ];
    }
}

/**
 * Factory para instanciar servicios con inyección de dependencias
 */
class ServiceFactory 
{
    private ConnectionManager $connectionManager;
    private array $repositories = [];
    private array $services = [];
    
    public function __construct() 
    {
        // Crear ConnectionManager usando DatabaseFactory
        $database = DatabaseFactory::getInstance();
        $this->connectionManager = new ConnectionManager($database);
    }
    
    public function crearRepository(string $nombre): object 
    {
        if (!isset($this->repositories[$nombre])) {
            switch($nombre) {
                case 'Producto':
                    $this->repositories[$nombre] = new ProductoRepository($this->connectionManager);
                    break;
                case 'Categoria':
                    $this->repositories[$nombre] = new CategoriaRepository($this->connectionManager);
                    break;
                default:
                    throw new Exception("Repository {$nombre} no encontrado");
            }
        }
        return $this->repositories[$nombre];
    }
    
    public function crearService(string $nombre): object 
    {
        if (!isset($this->services[$nombre])) {
            switch ($nombre) {
                case 'Producto':
                    $this->services[$nombre] = new ProductoService(
                        $this->crearRepository('Producto'),
                        $this->crearRepository('Categoria')
                    );
                    break;
                    
                default:
                    throw new Exception("Servicio {$nombre} no encontrado");
            }
        }
        
        return $this->services[$nombre];
    }
}

/**
 * Controller base con funcionalidades comunes
 */
abstract class BaseController 
{
    protected RequestHandler $requestHandler;
    protected ResponseHandler $responseHandler;
    protected ServiceFactory $serviceFactory;
    
    public function __construct(
        RequestHandler $requestHandler, 
        ResponseHandler $responseHandler,
        ServiceFactory $serviceFactory
    ) {
        $this->requestHandler = $requestHandler;
        $this->responseHandler = $responseHandler;
        $this->serviceFactory = $serviceFactory;
    }
    
    abstract public function manejarRequest(array $ruta): void;
    
    protected function validarId(?string $id): int 
    {
        if (!$id || !is_numeric($id) || (int)$id <= 0) {
            $this->responseHandler->error('ID inválido');
        }
        return (int)$id;
    }
}

/**
 * Controller para productos
 */
class ProductoController extends BaseController 
{
    private ProductoService $productoService;
    
    public function __construct(
        RequestHandler $requestHandler, 
        ResponseHandler $responseHandler,
        ServiceFactory $serviceFactory
    ) {
        parent::__construct($requestHandler, $responseHandler, $serviceFactory);
        $this->productoService = $this->serviceFactory->crearService('Producto');
    }
    
    public function manejarRequest(array $ruta): void 
    {
        $metodo = $this->requestHandler->obtenerMetodo();
        $id = $ruta['id'];
        $accion = $ruta['accion'];
        
        switch ($metodo) {
            case 'GET':
                $this->manejarGet($id, $accion);
                break;
            case 'POST':
                $this->manejarPost();
                break;
            case 'PUT':
                $this->manejarPut($id);
                break;
            case 'PATCH':
                $this->manejarPatch($id, $accion);
                break;
            case 'DELETE':
                $this->manejarDelete($id);
                break;
            default:
                $this->responseHandler->error('Método no permitido', 405);
        }
    }
    
    private function manejarGet(?string $id, ?string $accion): void 
    {
        $parametros = $this->requestHandler->obtenerParametros();
        
        if ($id && $accion === 'disponibilidad') {
            $idValidado = $this->validarId($id);
            $cantidad = (int)($parametros['cantidad'] ?? 1);
            $resultado = $this->productoService->verificarDisponibilidad($idValidado, $cantidad);
            $this->responseHandler->enviarRespuesta($resultado);
            
        } elseif ($id) {
            $idValidado = $this->validarId($id);
            $resultado = $this->productoService->obtenerProductoPorId($idValidado);
            $this->responseHandler->enviarRespuesta($resultado);
            
        } elseif (isset($parametros['buscar'])) {
            $resultado = $this->productoService->buscarProductos($parametros['buscar']);
            $this->responseHandler->enviarRespuesta($resultado);
            
        } elseif (isset($parametros['categoria'])) {
            $resultado = $this->productoService->obtenerProductosPorCategoria((int)$parametros['categoria']);
            $this->responseHandler->enviarRespuesta($resultado);
            
        } elseif (isset($parametros['stock_bajo'])) {
            $resultado = $this->productoService->obtenerProductosStockBajo();
            $this->responseHandler->enviarRespuesta($resultado);
            
        } elseif (isset($parametros['activos'])) {
            $resultado = $this->productoService->listarProductosActivos();
            $this->responseHandler->enviarRespuesta($resultado);
            
        } else {
            $resultado = $this->productoService->listarProductos();
            $this->responseHandler->enviarRespuesta($resultado);
        }
    }
    
    private function manejarPost(): void 
    {
        $datos = $this->requestHandler->obtenerDatos();
        $validacion = $this->productoService->validarDatosProducto($datos);
        
        if (!$validacion['valido']) {
            $this->responseHandler->error(implode(', ', $validacion['errores']));
        }
        
        $resultado = $this->productoService->crearProducto($datos);
        $codigo = $resultado['exito'] ? 201 : 400;
        $this->responseHandler->enviarRespuesta($resultado, $codigo);
    }
    
    private function manejarPut(?string $id): void 
    {
        $idValidado = $this->validarId($id);
        $datos = $this->requestHandler->obtenerDatos();
        $resultado = $this->productoService->actualizarProducto($idValidado, $datos);
        $this->responseHandler->enviarRespuesta($resultado);
    }
    
    private function manejarPatch(?string $id, ?string $accion): void 
    {
        $idValidado = $this->validarId($id);
        $datos = $this->requestHandler->obtenerDatos();
        
        if ($accion === 'stock') {
            $resultado = $this->productoService->actualizarStock(
                $idValidado,
                $datos['cantidad'] ?? 0
            );
            $this->responseHandler->enviarRespuesta($resultado);
            
        } elseif ($accion === 'activar') {
            $resultado = $this->productoService->activarProducto($idValidado);
            $this->responseHandler->enviarRespuesta($resultado);
            
        } else {
            $this->responseHandler->error('Acción no válida para PATCH');
        }
    }
    
    private function manejarDelete(?string $id): void 
    {
        $idValidado = $this->validarId($id);
        $resultado = $this->productoService->eliminarProducto($idValidado);
        $this->responseHandler->enviarRespuesta($resultado);
    }
}

/**
 * Router principal que coordina los controllers
 */
class Router 
{
    private RequestHandler $requestHandler;
    private ResponseHandler $responseHandler;
    private ServiceFactory $serviceFactory;
    private array $controllers = [];
    
    public function __construct() 
    {
        $this->requestHandler = new RequestHandler();
        $this->responseHandler = new ResponseHandler();
        $this->serviceFactory = new ServiceFactory();
        
        $this->inicializarControllers();
    }
    
    private function inicializarControllers(): void 
    {
        $this->controllers = [
            'productos' => new ProductoController(
                $this->requestHandler,
                $this->responseHandler,
                $this->serviceFactory
            )
        ];
    }
    
    public function procesar(): void 
    {
        try {
            HttpHeaders::configurar();
            
            $ruta = $this->requestHandler->obtenerRuta();
            $recurso = $ruta['recurso'];
            
            if (empty($recurso)) {
                $this->responseHandler->enviarRespuesta([
                    'exito' => true,
                    'mensaje' => 'API funcionando correctamente',
                    'version' => '1.0',
                    'recursos_disponibles' => ['productos']
                ]);
            }
            
            if (!isset($this->controllers[$recurso])) {
                $this->responseHandler->error('Recurso no encontrado', 404);
            }
            
            $this->controllers[$recurso]->manejarRequest($ruta);
            
        } catch (Exception $e) {
            $this->responseHandler->error(
                'Error interno del servidor: ' . $e->getMessage(),
                500
            );
        }
    }
}

// ============= PUNTO DE ENTRADA =============
$router = new Router();
$router->procesar();