// ============================================
// ADMIN PANEL - JavaScript COMPLETO
// ============================================

const API_URL = 'http://localhost/ProyectoQA/backend/api/api_simple.php';
let productosCache = [];
let modalProducto;

// Verificar autenticación al cargar
document.addEventListener('DOMContentLoaded', async () => {
    console.log('🔒 Verificando autenticación...');
    
    if (!protegerPagina(true)) {
        return;
    }
    
    // Actualizar nombre del usuario
    const usuario = obtenerUsuarioActual();
    if (usuario) {
        const sidebar = document.querySelector('.admin-sidebar h3');
        if (sidebar) {
            sidebar.innerHTML = `<i class="fas fa-user-shield"></i> ${usuario.nombre}`;
        }
        console.log('👤 Usuario:', usuario.nombre, '| Rol:', usuario.rol);
    }
    
    // Inicializar modal de Bootstrap
    const modalElement = document.getElementById('modalProducto');
    if (modalElement) {
        modalProducto = new bootstrap.Modal(modalElement);
    }
    
    // Cargar estadísticas del dashboard
    if (document.getElementById('totalProductos')) {
        await cargarEstadisticas();
    }
    
    console.log('✅ Panel de admin cargado correctamente');
});

// ============================================
// ESTADÍSTICAS
// ============================================

async function cargarEstadisticas() {
    try {
        console.log('📊 Cargando estadísticas...');
        
        const response = await fetch(`${API_URL}/productos`);
        const data = await response.json();
        
        if (data.exito) {
            const totalProductos = data.datos.length;
            document.getElementById('totalProductos').textContent = totalProductos;
            console.log('✅ Total productos:', totalProductos);
        } else {
            document.getElementById('totalProductos').textContent = '0';
        }
        
    } catch (error) {
        console.error('❌ Error cargando estadísticas:', error);
        document.getElementById('totalProductos').textContent = 'Error';
    }
}

// ============================================
// GESTIÓN DE PRODUCTOS
// ============================================

async function cargarProductosAdmin(filtros = {}) {
    try {
        console.log('📦 Cargando productos...', filtros);
        
        let url = `${API_URL}/productos?todos=1`;
        
        if (filtros.buscar) {
            url += `&buscar=${encodeURIComponent(filtros.buscar)}`;
        }
        if (filtros.categoria) {
            url += `&categoria=${filtros.categoria}`;
        }
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.exito) {
            productosCache = data.datos;
            mostrarProductosAdmin(data.datos);
            console.log('✅ Productos cargados:', data.datos.length);
        } else {
            console.error('Error cargando productos:', data.mensaje);
            mostrarError('Error al cargar productos');
        }
    } catch (error) {
        console.error('❌ Error:', error);
        mostrarError('Error de conexión');
    }
}

function mostrarProductosAdmin(productos) {
    const tbody = document.getElementById('tablaProductos');
    if (!tbody) return;
    
    if (!productos || productos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4">No hay productos</td></tr>';
        return;
    }
    
    tbody.innerHTML = '';
    
    productos.forEach(producto => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${producto.idProducto}</td>
            <td>${producto.nombre}</td>
            <td>${producto.marca || 'N/A'}</td>
            <td>${producto.categoria || 'N/A'}</td>
            <td>${producto.stock}</td>
            <td>Q${parseFloat(producto.precio_unitario).toFixed(2)}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editarProducto(${producto.idProducto})" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="eliminarProducto(${producto.idProducto})" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// ============================================
// CARGAR CATEGORÍAS
// ============================================

async function cargarCategoriasSelect() {
    try {
        const response = await fetch(`${API_URL}/categorias`);
        const data = await response.json();
        
        if (data.exito) {
            // Select de filtros
            const selectFiltro = document.getElementById('filtroCategoria');
            if (selectFiltro) {
                selectFiltro.innerHTML = '<option value="">Todas las categorías</option>';
                data.datos.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.idCategoria;
                    option.textContent = cat.descripcion;
                    selectFiltro.appendChild(option);
                });
            }
            
            // Select del modal
            const selectModal = document.getElementById('productoCategoria');
            if (selectModal) {
                selectModal.innerHTML = '<option value="">Seleccione una categoría</option>';
                data.datos.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.idCategoria;
                    option.textContent = cat.descripcion;
                    selectModal.appendChild(option);
                });
            }
            
            console.log('✅ Categorías cargadas:', data.datos.length);
        }
    } catch (error) {
        console.error('❌ Error cargando categorías:', error);
    }
}

// ============================================
// FILTROS
// ============================================

function configurarFiltros() {
    const buscarInput = document.getElementById('buscarProducto');
    const filtroCategoria = document.getElementById('filtroCategoria');
    
    // Búsqueda con debounce
    let timeout;
    if (buscarInput) {
        buscarInput.addEventListener('input', (e) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                aplicarFiltros();
            }, 500);
        });
    }
    
    // Filtro de categoría
    if (filtroCategoria) {
        filtroCategoria.addEventListener('change', aplicarFiltros);
    }
}

function aplicarFiltros() {
    const filtros = {
        buscar: document.getElementById('buscarProducto')?.value || '',
        categoria: document.getElementById('filtroCategoria')?.value || ''
    };
    
    cargarProductosAdmin(filtros);
}

function limpiarFiltros() {
    document.getElementById('buscarProducto').value = '';
    document.getElementById('filtroCategoria').value = '';
    cargarProductosAdmin();
}

// ============================================
// CREAR PRODUCTO
// ============================================

function abrirModalNuevoProducto() {
    document.getElementById('modalProductoTitulo').textContent = 'Nuevo Producto';
    document.getElementById('formProducto').reset();
    document.getElementById('productoId').value = '';
    modalProducto.show();
}

// ============================================
// EDITAR PRODUCTO
// ============================================

function editarProducto(id) {
    const producto = productosCache.find(p => p.idProducto === id);
    
    if (!producto) {
        mostrarNotificacion('Producto no encontrado', 'danger');
        return;
    }
    
    document.getElementById('modalProductoTitulo').textContent = 'Editar Producto';
    document.getElementById('productoId').value = producto.idProducto;
    document.getElementById('productoNombre').value = producto.nombre;
    document.getElementById('productoMarca').value = producto.marca || '';
    document.getElementById('productoDescripcion').value = producto.descripcion || '';
    document.getElementById('productoCategoria').value = producto.idCategoria;
    document.getElementById('productoStock').value = producto.stock;
    document.getElementById('productoPrecio').value = producto.precio_unitario;
    
    modalProducto.show();
}

// ============================================
// GUARDAR PRODUCTO (CREAR O EDITAR)
// ============================================

async function guardarProducto() {
    const form = document.getElementById('formProducto');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const id = document.getElementById('productoId').value;
    const datos = {
        nombre: document.getElementById('productoNombre').value.trim(),
        marca: document.getElementById('productoMarca').value.trim(),
        descripcion: document.getElementById('productoDescripcion').value.trim(),
        idCategoria: parseInt(document.getElementById('productoCategoria').value),
        stock: parseInt(document.getElementById('productoStock').value),
        precio_unitario: parseFloat(document.getElementById('productoPrecio').value),
        esActivo: true
    };
    
    try {
        let url = `${API_URL}/productos`;
        let method = 'POST';
        
        if (id) {
            url += `/${id}`;
            method = 'PUT';
        }
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(datos)
        });
        
        const data = await response.json();
        
        if (data.exito) {
            mostrarNotificacion(
                id ? 'Producto actualizado correctamente' : 'Producto creado correctamente',
                'success'
            );
            modalProducto.hide();
            cargarProductosAdmin();
        } else {
            mostrarNotificacion('Error: ' + data.mensaje, 'danger');
        }
        
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error de conexión', 'danger');
    }
}

// ============================================
// ELIMINAR PRODUCTO
// ============================================

async function eliminarProducto(id) {
    const producto = productosCache.find(p => p.idProducto === id);
    
    if (!producto) {
        mostrarNotificacion('Producto no encontrado', 'danger');
        return;
    }
    
    if (!confirm(`¿Estás seguro de eliminar el producto:\n"${producto.nombre}"?\n\nEsta acción lo marcará como inactivo.`)) {
        return;
    }
    
    try {
        const response = await fetch(`${API_URL}/productos/${id}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.exito) {
            mostrarNotificacion('Producto eliminado correctamente', 'success');
            cargarProductosAdmin();
        } else {
            mostrarNotificacion('Error al eliminar: ' + data.mensaje, 'danger');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error de conexión', 'danger');
    }
}

// ============================================
// UTILIDADES
// ============================================

function mostrarNotificacion(mensaje, tipo = 'info') {
    document.querySelectorAll('.toast-notification').forEach(el => el.remove());
    
    const iconos = {
        success: 'fa-check-circle',
        danger: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    const colores = {
        success: '#28a745',
        danger: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };
    
    const div = document.createElement('div');
    div.className = 'toast-notification';
    div.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        border-left: 4px solid ${colores[tipo]};
        min-width: 300px;
        animation: slideIn 0.3s ease;
    `;
    
    div.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fas ${iconos[tipo]}" style="color: ${colores[tipo]}; font-size: 20px;"></i>
            <span style="flex: 1;">${mensaje}</span>
            <button onclick="this.parentElement.parentElement.remove()" 
                    style="border: none; background: none; font-size: 20px; cursor: pointer; color: #999;">
                ×
            </button>
        </div>
    `;
    
    document.body.appendChild(div);
    
    setTimeout(() => div.remove(), 4000);
}

function mostrarError(mensaje) {
    const tbody = document.getElementById('tablaProductos');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-danger py-4">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>${mensaje}</p>
                </td>
            </tr>
        `;
    }
}

// ============================================
// CERRAR SESIÓN
// ============================================

function cerrarSesion() {
    console.log('🚪 Cerrando sesión...');
    
    if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        logout();
    }
}

// ============================================
// EXPORTAR FUNCIONES GLOBALES
// ============================================

window.cargarEstadisticas = cargarEstadisticas;
window.cerrarSesion = cerrarSesion;
window.cargarProductosAdmin = cargarProductosAdmin;
window.cargarCategoriasSelect = cargarCategoriasSelect;
window.configurarFiltros = configurarFiltros;
window.aplicarFiltros = aplicarFiltros;
window.limpiarFiltros = limpiarFiltros;
window.abrirModalNuevoProducto = abrirModalNuevoProducto;
window.editarProducto = editarProducto;
window.guardarProducto = guardarProducto;
window.eliminarProducto = eliminarProducto;
window.mostrarNotificacion = mostrarNotificacion;

console.log('✅ admin.js cargado completamente');