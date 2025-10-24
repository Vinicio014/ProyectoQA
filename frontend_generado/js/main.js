// ============================================
// CONFIGURACIÓN DE LA API
// ============================================
// IMPORTANTE: Esta es la API simplificada que NO requiere toda la estructura POO
const API_URL = 'http://localhost/ProyectoQA/backend/api/api_simple.php';

const AppState = {
    carrito: JSON.parse(localStorage.getItem('carrito')) || [],
    usuario: JSON.parse(localStorage.getItem('usuario')) || null,
    token: localStorage.getItem('token') || null,
    productos: [],
    categorias: []
};

// ============================================
// FUNCIONES DE API
// ============================================

async function fetchAPI(endpoint, options = {}) {
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json'
        }
    };

    if (AppState.token) {
        defaultOptions.headers['Authorization'] = `Bearer ${AppState.token}`;
    }

    try {
        let url = API_URL + endpoint;
        
        console.log('🔗 Llamando a:', url);
        
        const response = await fetch(url, {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers
            }
        });

        const data = await response.json();
        
        console.log('📦 Respuesta recibida:', data);
        
        if (!data.exito) {
            throw new Error(data.mensaje || 'Error en la petición');
        }

        return data;
    } catch (error) {
        console.error('❌ Error en fetchAPI:', error);
        mostrarNotificacion(error.message || 'Error al conectar con el servidor', 'danger');
        return null;
    }
}

// ============================================
// GESTIÓN DE PRODUCTOS
// ============================================

async function cargarProductos(filtros = {}) {
    mostrarLoader(true);
    
    try {
        let endpoint = '/productos';
        const params = new URLSearchParams();
        
        if (filtros.busqueda) {
            params.append('buscar', filtros.busqueda);
        }
        
        if (filtros.categoria) {
            params.append('categoria', filtros.categoria);
        }
        
        if (filtros.stock_bajo) {
            params.append('stock_bajo', '1');
        }
        
        if (params.toString()) {
            endpoint += '?' + params.toString();
        }
        
        console.log('📡 Cargando productos...');

        const response = await fetchAPI(endpoint);
        
        if (response && response.exito) {
            AppState.productos = response.datos || [];
            mostrarProductos(AppState.productos);
            console.log(`✅ ${AppState.productos.length} productos cargados`);
        } else {
            console.error('❌ Error al cargar productos');
            mostrarNotificacion('No se pudieron cargar los productos', 'warning');
            mostrarProductos([]);
        }
    } catch (error) {
        console.error('❌ Error al cargar productos:', error);
        mostrarNotificacion('Error al cargar productos: ' + error.message, 'danger');
        mostrarProductos([]);
    } finally {
        mostrarLoader(false);
    }
}

function mostrarProductos(productos) {
    const container = document.getElementById('productosDestacados') || 
                     document.getElementById('productosLista');
    
    if (!container) {
        console.warn('⚠️ Contenedor de productos no encontrado');
        return;
    }
    
    if (!productos || productos.length === 0) {
        container.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                <h4>No hay productos disponibles</h4>
                <p class="text-muted">Intenta con otros filtros de búsqueda</p>
            </div>
        `;
        return;
    }

    container.innerHTML = '';
    
    productos.forEach(producto => {
        const div = document.createElement('div');
        div.className = 'col-md-6 col-lg-4 col-xl-3 mb-4';
        
        const sinStock = producto.stock <= 0;
        const stockBajo = producto.stock > 0 && producto.stock <= 5;
        
        div.innerHTML = `
            <div class="card h-100 shadow-sm product-card ${sinStock ? 'opacity-75' : ''}">
                <div class="card-img-top bg-gradient d-flex align-items-center justify-content-center position-relative" 
                     style="height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <i class="fas fa-tshirt fa-4x text-white"></i>
                    ${sinStock ? '<span class="position-absolute top-0 start-0 badge bg-danger m-2">Sin Stock</span>' : ''}
                    ${stockBajo && !sinStock ? '<span class="position-absolute top-0 start-0 badge bg-warning m-2">¡Últimas unidades!</span>' : ''}
                </div>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title text-truncate" title="${producto.nombre}">${producto.nombre}</h5>
                    <p class="card-text text-muted small mb-2" style="min-height: 40px;">${producto.descripcion || 'Sin descripción'}</p>
                    
                    <div class="mt-auto">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted">
                                <i class="fas fa-tag"></i> ${producto.marca || 'Sin marca'}
                            </small>
                            <small class="${stockBajo && !sinStock ? 'text-warning fw-bold' : 'text-muted'}">
                                <i class="fas fa-box"></i> ${producto.stock}
                            </small>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <span class="h5 mb-0 text-primary fw-bold">Q${parseFloat(producto.precio_unitario).toFixed(2)}</span>
                            <button class="btn btn-sm btn-primary ${sinStock ? 'disabled' : ''}" 
                                    onclick='agregarAlCarrito(${JSON.stringify(producto).replace(/'/g, "&apos;")})'
                                    ${sinStock ? 'disabled' : ''}>
                                <i class="fas fa-cart-plus"></i> 
                                ${sinStock ? 'Agotado' : 'Agregar'}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(div);
    });
    
    console.log(`✅ Se mostraron ${productos.length} productos en pantalla`);
}

// ============================================
// GESTIÓN DEL CARRITO
// ============================================

function agregarAlCarrito(producto) {
    const existe = AppState.carrito.find(item => item.idProducto === producto.idProducto);
    
    if (existe) {
        if (existe.cantidad < producto.stock) {
            existe.cantidad++;
            mostrarNotificacion('Cantidad actualizada en el carrito', 'success');
        } else {
            mostrarNotificacion('No hay más stock disponible', 'warning');
            return;
        }
    } else {
        AppState.carrito.push({
            ...producto,
            cantidad: 1
        });
        mostrarNotificacion('✓ Producto agregado al carrito', 'success');
    }
    
    guardarCarrito();
    actualizarCarrito();
}

function eliminarDelCarrito(productoId) {
    AppState.carrito = AppState.carrito.filter(item => item.idProducto !== productoId);
    guardarCarrito();
    actualizarCarrito();
    mostrarCarrito();
    mostrarNotificacion('Producto eliminado del carrito', 'info');
}

function actualizarCantidadCarrito(productoId, nuevaCantidad) {
    const item = AppState.carrito.find(p => p.idProducto === productoId);
    
    if (item) {
        if (nuevaCantidad <= 0) {
            eliminarDelCarrito(productoId);
        } else if (nuevaCantidad <= item.stock) {
            item.cantidad = nuevaCantidad;
            guardarCarrito();
            actualizarCarrito();
            mostrarCarrito();
        } else {
            mostrarNotificacion('No hay suficiente stock disponible', 'warning');
        }
    }
}

function guardarCarrito() {
    localStorage.setItem('carrito', JSON.stringify(AppState.carrito));
}

function actualizarCarrito() {
    const cartCount = document.getElementById('cartCount');
    if (cartCount) {
        const totalItems = AppState.carrito.reduce((sum, item) => sum + item.cantidad, 0);
        cartCount.textContent = totalItems;
        
        if (totalItems > 0) {
            cartCount.classList.add('animate__animated', 'animate__pulse');
            setTimeout(() => {
                cartCount.classList.remove('animate__animated', 'animate__pulse');
            }, 500);
        }
    }
}

function mostrarCarrito() {
    const modalEl = document.getElementById('carritoModal');
    if (!modalEl) {
        console.warn('Modal del carrito no encontrado');
        return;
    }
    
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    
    const contenido = document.getElementById('carritoContenido');
    const totalElement = document.getElementById('totalCarrito');
    
    if (AppState.carrito.length === 0) {
        contenido.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                <h5>Tu carrito está vacío</h5>
                <p class="text-muted">Agrega productos para comenzar</p>
            </div>
        `;
        totalElement.textContent = '0.00';
        return;
    }
    
    let html = '';
    let total = 0;
    
    AppState.carrito.forEach(item => {
        const subtotal = item.precio_unitario * item.cantidad;
        total += subtotal;
        
        html += `
            <div class="card mb-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h6 class="mb-1">${item.nombre}</h6>
                            <p class="text-muted mb-1 small">Q${parseFloat(item.precio_unitario).toFixed(2)} c/u</p>
                            <div class="btn-group btn-group-sm" role="group">
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="actualizarCantidadCarrito(${item.idProducto}, ${item.cantidad - 1})">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button class="btn btn-outline-secondary" disabled>${item.cantidad}</button>
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="actualizarCantidadCarrito(${item.idProducto}, ${item.cantidad + 1})">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <p class="fw-bold mb-2 text-primary">Q${subtotal.toFixed(2)}</p>
                            <button class="btn btn-sm btn-danger" 
                                    onclick="eliminarDelCarrito(${item.idProducto})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    contenido.innerHTML = html;
    totalElement.textContent = total.toFixed(2);
}

function vaciarCarrito() {
    if (confirm('¿Estás seguro de vaciar el carrito?')) {
        AppState.carrito = [];
        guardarCarrito();
        actualizarCarrito();
        mostrarCarrito();
        mostrarNotificacion('Carrito vaciado', 'info');
    }
}

function procederPago() {
    if (!AppState.usuario) {
        mostrarNotificacion('Debes iniciar sesión para continuar', 'warning');
        setTimeout(() => {
            window.location.href = 'views/usuario/login.html';
        }, 1500);
        return;
    }
    
    window.location.href = 'views/usuario/checkout.html';
}

// ============================================
// UTILIDADES
// ============================================

function mostrarNotificacion(mensaje, tipo = 'info') {
    // Remover notificaciones anteriores
    document.querySelectorAll('.custom-toast').forEach(el => el.remove());
    
    const iconos = {
        success: 'fa-check-circle',
        danger: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${tipo} alert-dismissible fade show custom-toast position-fixed shadow-lg`;
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 99999; min-width: 300px; max-width: 400px;';
    alert.innerHTML = `
        <i class="fas ${iconos[tipo]} me-2"></i>
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 150);
    }, 4000);
}

function mostrarLoader(mostrar) {
    let loader = document.getElementById('globalLoader');
    
    if (!loader) {
        loader = document.createElement('div');
        loader.id = 'globalLoader';
        loader.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 99998;
            backdrop-filter: blur(3px);
        `;
        loader.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-light" role="status" style="width: 4rem; height: 4rem;">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="text-white mt-3 fw-bold">Cargando productos...</p>
            </div>
        `;
        document.body.appendChild(loader);
    }
    
    loader.style.display = mostrar ? 'flex' : 'none';
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ============================================
// INICIALIZACIÓN
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Aplicación de Uniformes Deportivos iniciada');
    console.log('🔗 API URL:', API_URL);
    console.log('📅 Fecha:', new Date().toLocaleString());
    
    // Actualizar contador del carrito
    actualizarCarrito();
    
    // Configurar botón del carrito
    const cartBtn = document.getElementById('cartBtn');
    if (cartBtn) {
        cartBtn.addEventListener('click', (e) => {
            e.preventDefault();
            mostrarCarrito();
        });
    }
    
    // Cargar productos si estamos en la página correspondiente
    const paginaProductos = document.getElementById('productosDestacados') || 
                           document.getElementById('productosLista');
    
    if (paginaProductos) {
        console.log('📦 Cargando productos de la base de datos...');
        cargarProductos();
        
        // Configurar búsqueda
        const buscarInput = document.getElementById('buscarProducto');
        if (buscarInput) {
            buscarInput.addEventListener('input', debounce((e) => {
                const query = e.target.value.trim();
                console.log('🔍 Buscando:', query);
                cargarProductos({ busqueda: query });
            }, 500));
        }
        
        // Configurar filtro de categoría
        const filtroCategoria = document.getElementById('filtroCategoria');
        if (filtroCategoria) {
            filtroCategoria.addEventListener('change', (e) => {
                const categoria = e.target.value;
                console.log('🏷️ Filtrando por categoría:', categoria);
                cargarProductos({ categoria: categoria });
            });
        }
    }
    
    console.log('✅ Inicialización completada');
});

// Exportar funciones globalmente
window.AppState = AppState;
window.cargarProductos = cargarProductos;
window.agregarAlCarrito = agregarAlCarrito;
window.eliminarDelCarrito = eliminarDelCarrito;
window.actualizarCantidadCarrito = actualizarCantidadCarrito;
window.mostrarCarrito = mostrarCarrito;
window.vaciarCarrito = vaciarCarrito;
window.procederPago = procederPago;
window.mostrarNotificacion = mostrarNotificacion;

console.log('✅ Módulo main.js cargado correctamente');