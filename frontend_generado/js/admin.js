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
        
        // Cargar productos activos
        const response = await fetch(`${API_URL}/productos`);
        const data = await response.json();
        
        if (data.exito) {
            const totalProductos = data.datos.length;
            document.getElementById('totalProductos').textContent = totalProductos;
            console.log('✅ Total productos:', totalProductos);
        } else {
            document.getElementById('totalProductos').textContent = '0';
        }
        
        // Cargar usuarios activos
        const responseUsuarios = await fetch(`${API_URL}/usuarios`);
        const dataUsuarios = await responseUsuarios.json();
        
        if (dataUsuarios.exito) {
            const totalUsuarios = dataUsuarios.datos.length;
            const elemUsuarios = document.getElementById('totalUsuarios');
            if (elemUsuarios) {
                elemUsuarios.textContent = totalUsuarios;
                console.log('✅ Total usuarios:', totalUsuarios);
            }
        } else {
            const elemUsuarios = document.getElementById('totalUsuarios');
            if (elemUsuarios) elemUsuarios.textContent = '0';
        }
        
        // Cargar pedidos pendientes
        const responsePedidos = await fetch(`${API_URL}/pedidos?estado=Pendiente`);
        const dataPedidos = await responsePedidos.json();
        
        if (dataPedidos.exito) {
            const totalPendientes = dataPedidos.datos.length;
            const elemPedidos = document.getElementById('pedidosPendientes');
            if (elemPedidos) {
                elemPedidos.textContent = totalPendientes;
                console.log('✅ Pedidos pendientes:', totalPendientes);
            }
        } else {
            const elemPedidos = document.getElementById('pedidosPendientes');
            if (elemPedidos) elemPedidos.textContent = '0';
        }
        
        // Ventas del mes (placeholder)

// Cargar ventas del mes
        const responseVentas = await fetch(`${API_URL}/ventas`);
        const dataVentas = await responseVentas.json();
        
        if (dataVentas.exito) {
            const mesActual = new Date().getMonth();
            const anioActual = new Date().getFullYear();
            
            const ventasMes = dataVentas.datos.filter(v => {
                const fechaVenta = new Date(v.fechaRegistro);
                return fechaVenta.getMonth() === mesActual && fechaVenta.getFullYear() === anioActual;
            });
            
            const totalVentasMes = ventasMes.reduce((sum, v) => sum + parseFloat(v.Total), 0);
            
            const elemVentas = document.getElementById('ventasMes');
            if (elemVentas) {
                elemVentas.textContent = `Q${totalVentasMes.toFixed(2)}`;
                console.log('✅ Ventas del mes:', totalVentasMes);
            }
        } else {
            const elemVentas = document.getElementById('ventasMes');
            if (elemVentas) elemVentas.textContent = 'Q0.00';
        }  
    } catch (error) {
        console.error('❌ Error cargando estadísticas:', error);
        document.getElementById('totalProductos').textContent = 'Error';
        const elemUsuarios = document.getElementById('totalUsuarios');
        if (elemUsuarios) elemUsuarios.textContent = 'Error';
    }
}

// ============================================
// GESTIÓN DE PRODUCTOS
// ============================================

async function cargarProductosAdmin(filtros = {}) {
    try {
        console.log('📦 Cargando productos...', filtros);
        
        // Solo productos activos (sin ?todos=1)
        let url = `${API_URL}/productos`;
        let primerParametro = true;
        
        if (filtros.buscar) {
            url += (primerParametro ? '?' : '&') + `buscar=${encodeURIComponent(filtros.buscar)}`;
            primerParametro = false;
        }
        if (filtros.categoria) {
            url += (primerParametro ? '?' : '&') + `categoria=${filtros.categoria}`;
            primerParametro = false;
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
        esActivo: 1  // CORREGIDO: Enviar 1 (int) en lugar de true (boolean)
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

// ============================================
// GESTIÓN DE USUARIOS
// ============================================

let usuariosCache = [];
let modalUsuario;

async function cargarUsuariosAdmin(filtros = {}) {
    try {
        console.log('👥 Cargando usuarios...', filtros);
        
        let url = `${API_URL}/usuarios`;
        
        if (filtros.buscar) {
            url += `?buscar=${encodeURIComponent(filtros.buscar)}`;
        }
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.exito) {
            usuariosCache = data.datos;
            mostrarUsuariosAdmin(data.datos);
            console.log('✅ Usuarios cargados:', data.datos.length);
        } else {
            console.error('Error cargando usuarios:', data.mensaje);
            mostrarErrorUsuarios('Error al cargar usuarios');
        }
    } catch (error) {
        console.error('❌ Error:', error);
        mostrarErrorUsuarios('Error de conexión');
    }
}

function mostrarUsuariosAdmin(usuarios) {
    const tbody = document.getElementById('tablaUsuarios');
    if (!tbody) return;
    
    if (!usuarios || usuarios.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">No hay usuarios</td></tr>';
        return;
    }
    
    tbody.innerHTML = '';
    
    usuarios.forEach(usuario => {
        const tr = document.createElement('tr');
        const fecha = new Date(usuario.fechaRegistro).toLocaleDateString('es-GT');
        
        tr.innerHTML = `
            <td>${usuario.idUsuario}</td>
            <td>${usuario.nombre}</td>
            <td>${usuario.correo}</td>
            <td>
                <span class="badge bg-${usuario.idRol === 1 ? 'danger' : usuario.idRol === 2 ? 'warning' : 'secondary'}">
                    ${usuario.rol}
                </span>
            </td>
            <td>${fecha}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editarUsuario(${usuario.idUsuario})" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="eliminarUsuario(${usuario.idUsuario})" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function mostrarErrorUsuarios(mensaje) {
    const tbody = document.getElementById('tablaUsuarios');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-danger py-4">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>${mensaje}</p>
                </td>
            </tr>
        `;
    }
}

// ============================================
// FILTROS USUARIOS
// ============================================

function configurarFiltrosUsuarios() {
    const buscarInput = document.getElementById('buscarUsuario');
    
    if (buscarInput) {
        let timeout;
        buscarInput.addEventListener('input', (e) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                aplicarFiltrosUsuarios();
            }, 500);
        });
    }
}

function aplicarFiltrosUsuarios() {
    const filtros = {
        buscar: document.getElementById('buscarUsuario')?.value || ''
    };
    
    cargarUsuariosAdmin(filtros);
}

function limpiarFiltrosUsuarios() {
    document.getElementById('buscarUsuario').value = '';
    cargarUsuariosAdmin();
}

// ============================================
// CREAR USUARIO
// ============================================

function abrirModalNuevoUsuario() {
    // Inicializar modal si no existe
    const modalElement = document.getElementById('modalUsuario');
    if (!modalUsuario && modalElement) {
        modalUsuario = new bootstrap.Modal(modalElement);
    }
    
    document.getElementById('modalUsuarioTitulo').textContent = 'Nuevo Usuario';
    document.getElementById('formUsuario').reset();
    document.getElementById('usuarioId').value = '';
    document.getElementById('modoEdicion').value = 'false';
    
    // Mostrar campo contraseña y hacerlo requerido
    const campoContrasenia = document.getElementById('campoContrasenia');
    const inputContrasenia = document.getElementById('usuarioContrasenia');
    campoContrasenia.style.display = 'block';
    inputContrasenia.required = true;
    
    // Correo editable al crear
    document.getElementById('usuarioCorreo').readOnly = false;
    
    modalUsuario.show();
}

// ============================================
// EDITAR USUARIO
// ============================================

function editarUsuario(id) {
    const usuario = usuariosCache.find(u => u.idUsuario === id);
    
    if (!usuario) {
        mostrarNotificacion('Usuario no encontrado', 'danger');
        return;
    }
    
    // Inicializar modal si no existe
    const modalElement = document.getElementById('modalUsuario');
    if (!modalUsuario && modalElement) {
        modalUsuario = new bootstrap.Modal(modalElement);
    }
    
    document.getElementById('modalUsuarioTitulo').textContent = 'Editar Usuario';
    document.getElementById('usuarioId').value = usuario.idUsuario;
    document.getElementById('modoEdicion').value = 'true';
    document.getElementById('usuarioNombre').value = usuario.nombre;
    document.getElementById('usuarioCorreo').value = usuario.correo;
    document.getElementById('usuarioRol').value = usuario.idRol;
    
    // Ocultar campo contraseña y hacerlo no requerido
    const campoContrasenia = document.getElementById('campoContrasenia');
    const inputContrasenia = document.getElementById('usuarioContrasenia');
    campoContrasenia.style.display = 'none';
    inputContrasenia.required = false;
    inputContrasenia.value = '';
    
    // Correo NO editable
    document.getElementById('usuarioCorreo').readOnly = true;
    
    modalUsuario.show();
}

// ============================================
// GUARDAR USUARIO (CREAR O EDITAR)
// ============================================

async function guardarUsuario() {
    const form = document.getElementById('formUsuario');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const id = document.getElementById('usuarioId').value;
    const modoEdicion = document.getElementById('modoEdicion').value === 'true';
    
    const datos = {
        nombre: document.getElementById('usuarioNombre').value.trim(),
        idRol: parseInt(document.getElementById('usuarioRol').value)
    };
    
    // Solo agregar correo y contraseña al crear
    if (!modoEdicion) {
        datos.correo = document.getElementById('usuarioCorreo').value.trim();
        datos.contrasenia = document.getElementById('usuarioContrasenia').value;
        
        // Validar contraseña
        if (datos.contrasenia.length < 6) {
            mostrarNotificacion('La contraseña debe tener al menos 6 caracteres', 'danger');
            return;
        }
    }
    
    try {
        let url = `${API_URL}/usuarios`;
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
                id ? 'Usuario actualizado correctamente' : 'Usuario creado correctamente',
                'success'
            );
            modalUsuario.hide();
            await cargarUsuariosAdmin();
            
            // Actualizar dashboard si existe
            if (document.getElementById('totalUsuarios')) {
                await cargarEstadisticas();
            }
        } else {
            mostrarNotificacion('Error: ' + data.mensaje, 'danger');
        }
        
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error de conexión', 'danger');
    }
}

// ============================================
// ELIMINAR USUARIO
// ============================================

async function eliminarUsuario(id) {
    const usuario = usuariosCache.find(u => u.idUsuario === id);
    
    if (!usuario) {
        mostrarNotificacion('Usuario no encontrado', 'danger');
        return;
    }
    
    if (!confirm(`¿Estás seguro de eliminar el usuario:\n"${usuario.nombre}" (${usuario.correo})?\n\nEsta acción lo marcará como inactivo.`)) {
        return;
    }
    
    try {
        const response = await fetch(`${API_URL}/usuarios/${id}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.exito) {
            mostrarNotificacion('Usuario eliminado correctamente', 'success');
            await cargarUsuariosAdmin();
            
            // Actualizar dashboard si existe
            if (document.getElementById('totalUsuarios')) {
                await cargarEstadisticas();
            }
        } else {
            mostrarNotificacion('Error al eliminar: ' + data.mensaje, 'danger');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error de conexión', 'danger');
    }
}

// ============================================
// EXPORTAR FUNCIONES GLOBALES DE USUARIOS
// ============================================

window.cargarUsuariosAdmin = cargarUsuariosAdmin;
window.configurarFiltrosUsuarios = configurarFiltrosUsuarios;
window.aplicarFiltrosUsuarios = aplicarFiltrosUsuarios;
window.limpiarFiltrosUsuarios = limpiarFiltrosUsuarios;
window.abrirModalNuevoUsuario = abrirModalNuevoUsuario;
window.editarUsuario = editarUsuario;
window.guardarUsuario = guardarUsuario;
window.eliminarUsuario = eliminarUsuario;

// ============================================
// ============================================
// GESTIÓN COMPLETA DE PEDIDOS
// AGREGAR AL FINAL DE admin.js
// ============================================

let pedidosCache = [];
let productosPedidoTemporales = [];
let clientePedidoSeleccionado = null;
let modalNuevoPedido, modalDetallePedido, modalPersonalizarUniforme, modalRegistrarPago;

// Inicializar modales
if (document.getElementById('modalNuevoPedido')) {
    modalNuevoPedido = new bootstrap.Modal(document.getElementById('modalNuevoPedido'));
   // modalDetallePedido = new bootstrap.Modal(document.getElementById('modalDetallePedido'));
    modalPersonalizarUniforme = new bootstrap.Modal(document.getElementById('modalPersonalizarUniforme'));
    modalRegistrarPago = new bootstrap.Modal(document.getElementById('modalRegistrarPago'));
}
if (document.getElementById('modalDetallePedido')) {
    modalDetallePedido = new bootstrap.Modal(document.getElementById('modalDetallePedido'));
}
// ==================== CARGAR Y MOSTRAR PEDIDOS ====================

async function cargarPedidosAdmin(filtros = {}) {
    try {
        console.log('📦 Cargando pedidos...', filtros);
        
        let url = `${API_URL}/pedidos`;
        let params = [];
        
        if (filtros.buscar) params.push(`buscar=${encodeURIComponent(filtros.buscar)}`);
        if (filtros.estado) params.push(`estado=${encodeURIComponent(filtros.estado)}`);
        if (filtros.estadoPago) params.push(`estadoPago=${encodeURIComponent(filtros.estadoPago)}`);
        if (filtros.fecha) params.push(`fecha=${filtros.fecha}`);
        
        if (params.length > 0) url += '?' + params.join('&');
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.exito) {
            pedidosCache = data.datos;
            mostrarPedidosAdmin(data.datos);
            console.log('✅ Pedidos cargados:', data.datos.length);
        } else {
            mostrarErrorPedidos('Error al cargar pedidos');
        }
    } catch (error) {
        console.error('❌ Error:', error);
        mostrarErrorPedidos('Error de conexión');
    }
}

function mostrarPedidosAdmin(pedidos) {
    const tbody = document.getElementById('tablaPedidos');
    if (!tbody) return;
    
    if (!pedidos || pedidos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4">No hay pedidos</td></tr>';
        return;
    }
    
    tbody.innerHTML = '';
    
    pedidos.forEach(pedido => {
        const tr = document.createElement('tr');
        
        const fechaPedido = new Date(pedido.fechaPedido).toLocaleDateString('es-GT');
        const fechaEntrega = pedido.fecha_entrega ? new Date(pedido.fecha_entrega).toLocaleDateString('es-GT') : 'Sin fecha';
        
        const total = parseFloat(pedido.costo_total_pedido);
        const pagado = parseFloat(pedido.monto_pagado);
        const saldo = total - pagado;
        
        // Colores según estado de pago
        let badgePago = '';
        if (pedido.estado_pago === 'Pagado') badgePago = 'success';
        else if (pedido.estado_pago === 'Parcial') badgePago = 'warning';
        else badgePago = 'danger';
        
        // Colores según estado de pedido
        let badgeEstado = '';
        if (pedido.estado_pedido === 'Completado') badgeEstado = 'success';
        else if (pedido.estado_pedido === 'En Proceso') badgeEstado = 'primary';
        else if (pedido.estado_pedido === 'Cancelado') badgeEstado = 'danger';
        else badgeEstado = 'secondary';
        
        tr.innerHTML = `
            <td>${pedido.idPedido}</td>
            <td>${pedido.nombreCliente || 'N/A'}</td>
            <td><small>${fechaPedido}</small></td>
            <td><small>${fechaEntrega}</small></td>
            <td class="fw-bold">Q${total.toFixed(2)}</td>
            <td class="text-success">Q${pagado.toFixed(2)}</td>
            <td class="text-danger">Q${saldo.toFixed(2)}</td>
            <td><span class="badge bg-${badgePago}">${pedido.estado_pago}</span></td>
            <td>
                <select class="form-select form-select-sm" onchange="cambiarEstadoPedidoAdmin(${pedido.idPedido}, this.value)" 
                        ${pedido.estado_pedido === 'Cancelado' || pedido.estado_pedido === 'Completado' ? 'disabled' : ''}>
                    <option value="Pendiente" ${pedido.estado_pedido === 'Pendiente' ? 'selected' : ''}>Pendiente</option>
                    <option value="En Proceso" ${pedido.estado_pedido === 'En Proceso' ? 'selected' : ''}>En Proceso</option>
                    <option value="Completado" ${pedido.estado_pedido === 'Completado' ? 'selected' : ''}>Completado</option>
                    <option value="Cancelado" ${pedido.estado_pedido === 'Cancelado' ? 'selected' : ''}>Cancelado</option>
                </select>
            </td>
            <td>
                <button class="btn btn-sm btn-info" onclick="verDetallePedidoCompleto(${pedido.idPedido})" title="Ver Detalle">
                    <i class="fas fa-eye"></i>
                </button>
                ${saldo > 0 && pedido.estado_pedido !== 'Cancelado' ? `
                <button class="btn btn-sm btn-success" onclick="abrirModalRegistrarPago(${pedido.idPedido}, ${saldo})" title="Registrar Pago">
                    <i class="fas fa-money-bill"></i>
                </button>
                ` : ''}
                ${pedido.estado_pedido !== 'Cancelado' && pedido.estado_pedido !== 'Completado' ? `
                <button class="btn btn-sm btn-danger" onclick="cancelarPedidoAdmin(${pedido.idPedido})" title="Cancelar">
                    <i class="fas fa-ban"></i>
                </button>
                ` : ''}
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function mostrarErrorPedidos(mensaje) {
    const tbody = document.getElementById('tablaPedidos');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="text-center text-danger py-4">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>${mensaje}</p>
                </td>
            </tr>
        `;
    }
}

// ==================== MODAL NUEVO PEDIDO ====================

async function abrirModalNuevoPedido() {
    // Limpiar
    productosPedidoTemporales = [];
    clientePedidoSeleccionado = null;
    document.getElementById('selectClientePedido').value = '';
    document.getElementById('productosAgregadosPedido').innerHTML = '';
    document.getElementById('fechaEntregaPedido').value = '';
    document.getElementById('montoAnticipo').value = '';
    document.getElementById('descripcionAnticipo').value = '';
    actualizarTotalesPedido();
    
    // Cargar clientes y productos
    await cargarClientesSelectPedido();
    await cargarProductosSelectPedido();
    
    modalNuevoPedido.show();
}

// Cargar clientes (rol 3) en el select
async function cargarClientesSelectPedido() {
    try {
        const response = await fetch(`${API_URL}/usuarios?rol=3`);
        const data = await response.json();
        
        const select = document.getElementById('selectClientePedido');
        select.innerHTML = '<option value="">Seleccionar cliente...</option>';
        
        if (data.exito && data.datos.length > 0) {
            data.datos.forEach(cliente => {
                const option = document.createElement('option');
                option.value = cliente.idUsuario;
                option.textContent = `${cliente.nombre} (${cliente.correo})`;
                select.appendChild(option);
            });
            
            // Al seleccionar cliente
            select.onchange = () => {
                if (select.value) {
                    const selectedOption = select.options[select.selectedIndex];
                    clientePedidoSeleccionado = {
                        idUsuario: parseInt(select.value),
                        nombre: selectedOption.textContent
                    };
                } else {
                    clientePedidoSeleccionado = null;
                }
            };
        } else {
            select.innerHTML = '<option value="">No hay clientes disponibles</option>';
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al cargar clientes');
    }
}

async function cargarProductosSelectPedido() {
    try {
        const response = await fetch(`${API_URL}/productos?todos=1`);
        const data = await response.json();
        
        const select = document.getElementById('selectProductoPedido');
        select.innerHTML = '<option value="">Seleccionar producto...</option>';
        
        if (data.exito) {
            data.datos.forEach(prod => {
                if (prod.stock > 0) {
                    const option = document.createElement('option');
                    option.value = prod.idProducto;
                    option.dataset.nombre = prod.nombre;
                    option.dataset.precio = prod.precio_unitario;
                    option.dataset.stock = prod.stock;
                    option.textContent = `${prod.nombre} - Q${prod.precio_unitario} (Stock: ${prod.stock})`;
                    select.appendChild(option);
                }
            });
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// CONTINÚA EN LA PARTE 2...
// ============================================
// GESTIÓN DE PEDIDOS - PARTE 2
// Continúa desde la PARTE 1
// ============================================

// Agregar producto al pedido
function agregarProductoAlPedidoNuevo() {
    const select = document.getElementById('selectProductoPedido');
    const cantidad = parseInt(document.getElementById('cantidadProductoPedido').value);
    const disenoGeneral = document.getElementById('disenoProductoPedido').value.trim();
    
    if (!select.value) {
        alert('Selecciona un producto');
        return;
    }
    
    if (cantidad <= 0) {
        alert('La cantidad debe ser mayor a 0');
        return;
    }
    
    const option = select.options[select.selectedIndex];
    const idProducto = parseInt(select.value);
    const nombreProducto = option.dataset.nombre;
    const precio = parseFloat(option.dataset.precio);
    const stock = parseInt(option.dataset.stock);
    
    if (cantidad > stock) {
        alert(`Stock insuficiente. Disponible: ${stock}`);
        return;
    }
    
    // Verificar si ya existe
    const existe = productosPedidoTemporales.find(p => p.idProducto === idProducto);
    if (existe) {
        alert('Este producto ya fue agregado');
        return;
    }
    
    const subtotal = precio * cantidad;
    
    productosPedidoTemporales.push({
        idProducto,
        nombreProducto,
        cantidad,
        precio,
        subtotal,
        disenoGeneral,
        detalleUniforme: null // Se llena si personaliza
    });
    
    mostrarProductosAgregadosPedido();
    actualizarTotalesPedido();
    
    // Limpiar
    select.value = '';
    document.getElementById('cantidadProductoPedido').value = 1;
    document.getElementById('disenoProductoPedido').value = '';
}

function mostrarProductosAgregadosPedido() {
    const container = document.getElementById('productosAgregadosPedido');
    
    if (productosPedidoTemporales.length === 0) {
        container.innerHTML = '<p class="text-muted text-center">No hay productos agregados</p>';
        return;
    }
    
    container.innerHTML = '';
    
    productosPedidoTemporales.forEach((prod, index) => {
        const div = document.createElement('div');
        div.className = 'producto-item';
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <strong>${prod.nombreProducto}</strong><br>
                    <small>Q${prod.precio.toFixed(2)} × ${prod.cantidad} = Q${prod.subtotal.toFixed(2)}</small>
                    ${prod.disenoGeneral ? `<br><small class="text-primary">Diseño: ${prod.disenoGeneral}</small>` : ''}
                    ${prod.detalleUniforme ? '<br><span class="badge bg-info">✓ Personalizado</span>' : ''}
                </div>
                <div>
                    <button class="btn btn-sm btn-info me-1" onclick="abrirModalPersonalizar(${index})" title="Personalizar">
                        <i class="fas fa-tshirt"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="eliminarProductoPedido(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(div);
    });
}

function eliminarProductoPedido(index) {
    productosPedidoTemporales.splice(index, 1);
    mostrarProductosAgregadosPedido();
    actualizarTotalesPedido();
}

// ==================== PERSONALIZAR UNIFORME ====================

function abrirModalPersonalizar(index) {
    document.getElementById('productoPersonalizarIndex').value = index;
    
    const producto = productosPedidoTemporales[index];
    
    // Si ya tiene personalización, cargar datos
    if (producto.detalleUniforme) {
        document.getElementById('tallaUniforme').value = producto.detalleUniforme.talla || '';
        document.getElementById('generoUniforme').value = producto.detalleUniforme.genero || 'M';
        document.getElementById('nombreCamisola').value = producto.detalleUniforme.nombreCamisola || '';
        document.getElementById('numeroCamisola').value = producto.detalleUniforme.numeroCamisola || '';
        document.getElementById('nombreAbajoNumero').value = producto.detalleUniforme.nombreAbajoNumero || '';
        document.getElementById('conMedidas').value = producto.detalleUniforme.conMedidas || '0';
    } else {
        // Limpiar
        document.getElementById('tallaUniforme').value = '';
        document.getElementById('generoUniforme').value = 'M';
        document.getElementById('nombreCamisola').value = '';
        document.getElementById('numeroCamisola').value = '';
        document.getElementById('nombreAbajoNumero').value = '';
        document.getElementById('conMedidas').value = '0';
    }
    
    modalPersonalizarUniforme.show();
}

function guardarPersonalizacion() {
    const index = parseInt(document.getElementById('productoPersonalizarIndex').value);
    
    const detalleUniforme = {
        talla: document.getElementById('tallaUniforme').value.trim(),
        genero: document.getElementById('generoUniforme').value,
        nombreCamisola: document.getElementById('nombreCamisola').value.trim(),
        numeroCamisola: document.getElementById('numeroCamisola').value.trim(),
        nombreAbajoNumero: document.getElementById('nombreAbajoNumero').value.trim(),
        conMedidas: parseFloat(document.getElementById('conMedidas').value)
    };
    console.log('🔍 DETALLE UNIFORME:', detalleUniforme);
    productosPedidoTemporales[index].detalleUniforme = detalleUniforme;
    
    mostrarProductosAgregadosPedido();
    modalPersonalizarUniforme.hide();
    
    alert('Personalización guardada');
}

// ==================== CALCULAR TOTALES ====================

function actualizarTotalesPedido() {
    const total = productosPedidoTemporales.reduce((sum, p) => sum + p.subtotal, 0);
    const anticipo = parseFloat(document.getElementById('montoAnticipo').value) || 0;
    const saldo = total - anticipo;
    
    document.getElementById('totalPedidoNuevo').textContent = `Q${total.toFixed(2)}`;
    document.getElementById('anticipoMostrar').textContent = `Q${anticipo.toFixed(2)}`;
    document.getElementById('saldoPendiente').textContent = `Q${saldo.toFixed(2)}`;
}

// Actualizar al cambiar anticipo
document.addEventListener('DOMContentLoaded', () => {
    const inputAnticipo = document.getElementById('montoAnticipo');
    if (inputAnticipo) {
        inputAnticipo.addEventListener('input', actualizarTotalesPedido);
    }
});

// ==================== CREAR PEDIDO ====================

async function crearPedidoCompleto() {
    // Validaciones
    if (!clientePedidoSeleccionado) {
        alert('Selecciona un cliente');
        return;
    }
    
    if (productosPedidoTemporales.length === 0) {
        alert('Agrega al menos un producto');
        return;
    }
    
    const fechaEntrega = document.getElementById('fechaEntregaPedido').value;
    if (!fechaEntrega) {
        alert('Selecciona una fecha de entrega');
        return;
    }
    
    const anticipo = parseFloat(document.getElementById('montoAnticipo').value) || 0;
    if (anticipo <= 0) {
        alert('El anticipo debe ser mayor a 0');
        return;
    }
    
    const metodoPago = document.getElementById('metodoPagoAnticipo').value;
    const descripcionPago = document.getElementById('descripcionAnticipo').value.trim() || 'Anticipo inicial';
    
    const total = productosPedidoTemporales.reduce((sum, p) => sum + p.subtotal, 0);
    
    if (anticipo > total) {
        alert('El anticipo no puede ser mayor al total');
        return;
    }
    
    const datosPedido = {
        idUsuario: clientePedidoSeleccionado.idUsuario,
        productos: productosPedidoTemporales.map(p => ({
            idProducto: p.idProducto,
            cantidad: p.cantidad,
            precio: p.precio,
            subtotal: p.subtotal,
            disenoGeneral: p.disenoGeneral,
            detalleUniforme: p.detalleUniforme
        })),
        fechaEntrega,
        total,
        anticipo,
        metodoPago,
        descripcionPago
    };
    
    try {
        const response = await fetch(`${API_URL}/pedidos`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datosPedido)
        });
        
        const data = await response.json();
        
        if (data.exito) {
            alert('✅ Pedido creado correctamente');
            modalNuevoPedido.hide();
            cargarPedidosAdmin();
        } else {
            alert('❌ Error: ' + data.mensaje);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error de conexión');
    }
}

// ==================== VER DETALLE PEDIDO ====================

async function verDetallePedidoCompleto(idPedido) {
    try {
        const response = await fetch(`${API_URL}/pedidos/${idPedido}`);
        const data = await response.json();
        
        if (data.exito) {
            const pedido = data.datos;
            
            document.getElementById('detallePedidoNumero').textContent = `#${pedido.idPedido}`;
            
            const total = parseFloat(pedido.costo_total_pedido);
            const pagado = parseFloat(pedido.monto_pagado);
            const saldo = total - pagado;
            
            let productosHTML = '';
            if (pedido.productos && pedido.productos.length > 0) {
                productosHTML = '<table class="table table-sm"><thead><tr><th>Producto</th><th>Cant.</th><th>Diseño</th><th>Personalización</th></tr></thead><tbody>';
                pedido.productos.forEach(p => {
                    productosHTML += `
                        <tr>
                            <td>${p.nombreProducto}</td>
                            <td>${p.cantidad_producto}</td>
                            <td>${p.diseño || '-'}</td>
                            <td>${p.detalleUniforme ? `
                                <button class="btn btn-sm btn-info" type="button" data-bs-toggle="collapse" data-bs-target="#uniforme${p.id_detalle_pedido}">
                                    <i class="fas fa-eye"></i> Ver
                                </button>
                                <div class="collapse mt-2" id="uniforme${p.id_detalle_pedido}">
                                    <div class="card card-body small">
                                        <strong>Talla:</strong> ${p.detalleUniforme.talla}<br>
                                        <strong>Género:</strong> ${p.detalleUniforme.genero}<br>
                                        <strong>Nombre camisola:</strong> ${p.detalleUniforme.nombre_camisola || 'N/A'}<br>
                                        <strong>Número:</strong> ${p.detalleUniforme.numero_camisola || 'N/A'}<br>
                                        <strong>Nombre abajo:</strong> ${p.detalleUniforme.nombre_abajo_numero || 'N/A'}<br>
                                        <strong>Con medias:</strong> ${p.detalleUniforme.con_medidas == 1 ? 'Sí' : 'No'}
                                    </div>
                                </div>
                            ` : '-'}</td>
                        </tr>
                    `;
                });
                productosHTML += '</tbody></table>';
            }
            
            let pagosHTML = '';
            if (pedido.pagos && pedido.pagos.length > 0) {
                pagosHTML = '<table class="table table-sm"><thead><tr><th>Fecha</th><th>Monto</th><th>Método</th><th>Descripción</th></tr></thead><tbody>';
                pedido.pagos.forEach(pago => {
                    pagosHTML += `
                        <tr>
                            <td>${new Date(pago.fecha_pago).toLocaleString('es-GT')}</td>
                            <td>Q${parseFloat(pago.monto_pagado).toFixed(2)}</td>
                            <td>${pago.metodo_pago}</td>
                            <td>${pago.descripcion || '-'}</td>
                        </tr>
                    `;
                });
                pagosHTML += '</tbody></table>';
            }
            
            document.getElementById('contenidoDetallePedido').innerHTML = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Cliente:</strong> ${pedido.nombreCliente}<br>
                        <strong>Fecha Pedido:</strong> ${new Date(pedido.fechaPedido).toLocaleDateString('es-GT')}<br>
                        <strong>Fecha Entrega:</strong> ${pedido.fecha_entrega ? new Date(pedido.fecha_entrega).toLocaleDateString('es-GT') : 'Sin fecha'}
                    </div>
                    <div class="col-md-6">
                        <strong>Estado Pedido:</strong> <span class="badge bg-primary">${pedido.estado_pedido}</span><br>
                        <strong>Estado Pago:</strong> <span class="badge bg-info">${pedido.estado_pago}</span>
                    </div>
                </div>
                <hr>
                <h6>Productos:</h6>
                ${productosHTML}
                <hr>
                <h6>Historial de Pagos:</h6>
                ${pagosHTML}
                <hr>
                <div class="row">
                    <div class="col-md-4"><strong>Total:</strong> Q${total.toFixed(2)}</div>
                    <div class="col-md-4"><strong>Pagado:</strong> Q${pagado.toFixed(2)}</div>
                    <div class="col-md-4"><strong>Saldo:</strong> <span class="text-danger">Q${saldo.toFixed(2)}</span></div>
                </div>
            `;
            
            modalDetallePedido.show();
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al cargar detalle');
    }
}

// ==================== REGISTRAR PAGO ====================

function abrirModalRegistrarPago(idPedido, saldo) {
    document.getElementById('idPedidoPago').value = idPedido;
    document.getElementById('saldoPendientePago').textContent = `Q${saldo.toFixed(2)}`;
    document.getElementById('montoPago').value = saldo.toFixed(2);
    document.getElementById('descripcionPago').value = '';
    
    modalRegistrarPago.show();
}

async function guardarPago() {
    const idPedido = parseInt(document.getElementById('idPedidoPago').value);
    const monto = parseFloat(document.getElementById('montoPago').value);
    const metodo = document.getElementById('metodoPago').value;
    const descripcion = document.getElementById('descripcionPago').value.trim() || 'Pago adicional';
    
    if (monto <= 0) {
        alert('El monto debe ser mayor a 0');
        return;
    }
    
    try {
        const response = await fetch(`${API_URL}/pedidos/${idPedido}/pago`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ monto, metodo, descripcion })
        });
        
        const data = await response.json();
        
        if (data.exito) {
            alert('✅ Pago registrado correctamente');
            modalRegistrarPago.hide();
            cargarPedidosAdmin();
        } else {
            alert('❌ Error: ' + data.mensaje);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error de conexión');
    }
}

// ==================== CAMBIAR Y CANCELAR ESTADO ====================

async function cambiarEstadoPedidoAdmin(idPedido, nuevoEstado) {
    try {
        const response = await fetch(`${API_URL}/pedidos/${idPedido}/estado`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ estado: nuevoEstado })
        });
        
        const data = await response.json();
        
        if (data.exito) {
            alert('✅ Estado actualizado');
            cargarPedidosAdmin();
        } else {
            alert('❌ Error: ' + data.mensaje);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error de conexión');
    }
}

async function cancelarPedidoAdmin(idPedido) {
    if (!confirm('¿Seguro que deseas cancelar este pedido?')) return;
    
    try {
        const response = await fetch(`${API_URL}/pedidos/${idPedido}/cancelar`, {
            method: 'PUT'
        });
        
        const data = await response.json();
        
        if (data.exito) {
            alert('✅ Pedido cancelado');
            cargarPedidosAdmin();
        } else {
            alert('❌ Error: ' + data.mensaje);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error de conexión');
    }
}

// ==================== FILTROS ====================

function configurarFiltrosPedidos() {
    const filtroBuscar = document.getElementById('buscarPedido');
    const filtroEstado = document.getElementById('filtroEstadoPedido');
    const filtroEstadoPago = document.getElementById('filtroEstadoPago');
    const filtroFecha = document.getElementById('filtroFechaPedido');
    
    if (filtroBuscar) filtroBuscar.addEventListener('input', aplicarFiltrosPedidos);
    if (filtroEstado) filtroEstado.addEventListener('change', aplicarFiltrosPedidos);
    if (filtroEstadoPago) filtroEstadoPago.addEventListener('change', aplicarFiltrosPedidos);
    if (filtroFecha) filtroFecha.addEventListener('change', aplicarFiltrosPedidos);
}

function aplicarFiltrosPedidos() {
    const filtros = {
        buscar: document.getElementById('buscarPedido').value,
        estado: document.getElementById('filtroEstadoPedido').value,
        estadoPago: document.getElementById('filtroEstadoPago').value,
        fecha: document.getElementById('filtroFechaPedido').value
    };
    
    cargarPedidosAdmin(filtros);
}

function limpiarFiltrosPedidos() {
    document.getElementById('buscarPedido').value = '';
    document.getElementById('filtroEstadoPedido').value = '';
    document.getElementById('filtroEstadoPago').value = '';
    document.getElementById('filtroFechaPedido').value = '';
    cargarPedidosAdmin();
}

// Exportar funciones globales
window.cargarPedidosAdmin = cargarPedidosAdmin;
window.abrirModalNuevoPedido = abrirModalNuevoPedido;
window.agregarProductoAlPedidoNuevo = agregarProductoAlPedidoNuevo;
window.eliminarProductoPedido = eliminarProductoPedido;
window.abrirModalPersonalizar = abrirModalPersonalizar;
window.guardarPersonalizacion = guardarPersonalizacion;
window.crearPedidoCompleto = crearPedidoCompleto;
window.verDetallePedidoCompleto = verDetallePedidoCompleto;
window.abrirModalRegistrarPago = abrirModalRegistrarPago;
window.guardarPago = guardarPago;
window.cambiarEstadoPedidoAdmin = cambiarEstadoPedidoAdmin;
window.cancelarPedidoAdmin = cancelarPedidoAdmin;
window.configurarFiltrosPedidos = configurarFiltrosPedidos;
window.limpiarFiltrosPedidos = limpiarFiltrosPedidos;


// ============================================
// PEDIDOS URGENTES PARA DASHBOARD
// ============================================

async function cargarPedidosUrgentes() {
    try {
        const response = await fetch(`${API_URL}/pedidos?urgentes=1&limit=10`);
        const data = await response.json();
        
        const tbody = document.querySelector('#tablaPedidosRecientes tbody');
        
        if (!data.exito || data.datos.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="10" class="text-center text-success py-4">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <p>¡No hay pedidos urgentes! Todo al día.</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = '';
        
        data.datos.forEach(pedido => {
            const fechaPedido = new Date(pedido.fechaPedido).toLocaleDateString('es-GT');
            const fechaEntrega = pedido.fecha_entrega ? new Date(pedido.fecha_entrega).toLocaleDateString('es-GT') : 'Sin fecha';
            const total = parseFloat(pedido.costo_total_pedido);
            const pagado = parseFloat(pedido.monto_pagado);
            const saldo = total - pagado;
            
            // Calcular días de atraso
            const hoy = new Date();
            const fechaPed = new Date(pedido.fechaPedido);
            const diasAtraso = Math.floor((hoy - fechaPed) / (1000 * 60 * 60 * 24));
            
            const tr = document.createElement('tr');
            
            // Resaltar si tiene más de 7 días
            if (diasAtraso > 7) {
                tr.classList.add('table-danger');
            } else if (diasAtraso > 3) {
                tr.classList.add('table-warning');
            }
            
            tr.innerHTML = `
                <td><strong>${pedido.idPedido}</strong></td>
                <td>${pedido.nombreCliente}</td>
                <td><small>${fechaPedido}</small><br><small class="text-muted">(${diasAtraso} días)</small></td>
                <td><small>${fechaEntrega}</small></td>
                <td>Q${total.toFixed(2)}</td>
                <td>Q${pagado.toFixed(2)}</td>
                <td class="${saldo > 0 ? 'text-danger' : 'text-success'}"><strong>Q${saldo.toFixed(2)}</strong></td>
                <td><span class="badge bg-${obtenerColorEstado(pedido.estado_pedido)}">${pedido.estado_pedido}</span></td>
                <td><span class="badge bg-${obtenerColorPago(pedido.estado_pago)}">${pedido.estado_pago}</span></td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="verDetallePedidoCompleto(${pedido.idPedido})" title="Ver Detalle">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            `;
            
            tbody.appendChild(tr);
        });
        
    } catch (error) {
        console.error('Error cargando pedidos urgentes:', error);
    }
}

function obtenerColorEstado(estado) {
    const colores = {
        'Pendiente': 'warning',
        'En Proceso': 'info',
        'Completado': 'success',
        'Cancelado': 'danger'
    };
    return colores[estado] || 'secondary';
}

function obtenerColorPago(estado) {
    const colores = {
        'Pendiente': 'danger',
        'Parcial': 'warning',
        'Pagado': 'success'
    };
    return colores[estado] || 'secondary';
}

// Exportar función para uso global
window.cargarPedidosUrgentes = cargarPedidosUrgentes;

// GESTIÓN DE VENTAS
// ============================================
// AGREGAR ESTE CÓDIGO AL FINAL DE admin.js

let ventasCache = [];
let productosVentaTemporales = [];
let modalNuevaVenta;

// Inicializar modal
if (document.getElementById('modalNuevaVenta')) {
    modalNuevaVenta = new bootstrap.Modal(document.getElementById('modalNuevaVenta'));
}

// ==================== CARGAR VENTAS ====================

async function cargarVentasAdmin(filtros = {}) {
    try {
        console.log('💰 Cargando ventas...', filtros);
        
        let url = `${API_URL}/ventas`;
        let params = [];
        
        if (filtros.fecha) params.push(`fecha=${filtros.fecha}`);
        if (filtros.vendedor) params.push(`vendedor=${filtros.vendedor}`);
        if (filtros.cliente) params.push(`cliente=${filtros.cliente}`);
        
        if (params.length > 0) url += '?' + params.join('&');
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.exito) {
            ventasCache = data.datos;
            mostrarVentasAdmin(data.datos);
            calcularEstadisticasVentas(data.datos);
            console.log('✅ Ventas cargadas:', data.datos.length);
        } else {
            mostrarErrorVentas('Error al cargar ventas');
        }
    } catch (error) {
        console.error('❌ Error:', error);
        mostrarErrorVentas('Error de conexión');
    }
}

function mostrarVentasAdmin(ventas) {
    const tbody = document.getElementById('tablaVentas');
    if (!tbody) return;
    
    if (!ventas || ventas.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4">No hay ventas registradas</td></tr>';
        return;
    }
    
    tbody.innerHTML = '';
    
    ventas.forEach(venta => {
        const tr = document.createElement('tr');
        const fecha = new Date(venta.fechaRegistro).toLocaleString('es-GT');
        const subtotal = venta.Total - venta.impuestoTotal;
        
        tr.innerHTML = `
            <td>${venta.idVenta}</td>
            <td>${fecha}</td>
            <td>${venta.nombreVendedor}</td>
            <td>${venta.nombreCliente}</td>
            <td>Q${subtotal.toFixed(2)}</td>
            <td>Q${parseFloat(venta.impuestoTotal).toFixed(2)}</td>
            <td class="fw-bold text-success">Q${parseFloat(venta.Total).toFixed(2)}</td>
            <td>
                <button class="btn btn-sm btn-info" onclick="verDetalleVenta(${venta.idVenta})">
                    <i class="fas fa-eye"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function mostrarErrorVentas(mensaje) {
    const tbody = document.getElementById('tablaVentas');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-danger py-4">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>${mensaje}</p>
                </td>
            </tr>
        `;
    }
}

function calcularEstadisticasVentas(ventas) {
    const hoy = new Date().toISOString().split('T')[0];
    const mesActual = new Date().getMonth();
    
    const ventasHoy = ventas.filter(v => v.fechaRegistro.startsWith(hoy));
    const ventasMes = ventas.filter(v => new Date(v.fechaRegistro).getMonth() === mesActual);
    
    const totalHoy = ventasHoy.reduce((sum, v) => sum + parseFloat(v.Total), 0);
    const totalMes = ventasMes.reduce((sum, v) => sum + parseFloat(v.Total), 0);
    
    const elemHoy = document.getElementById('ventasHoy');
    const elemMes = document.getElementById('ventasMesTotal');
    const elemCount = document.getElementById('totalVentasCount');
    
    if (elemHoy) elemHoy.textContent = `Q${totalHoy.toFixed(2)}`;
    if (elemMes) elemMes.textContent = `Q${totalMes.toFixed(2)}`;
    if (elemCount) elemCount.textContent = ventas.length;
}

// ==================== MODAL NUEVA VENTA ====================

async function abrirModalNuevaVenta() {
    productosVentaTemporales = [];
    document.getElementById('productosVentaAgregados').innerHTML = '<p class="text-muted text-center">No hay productos agregados</p>';
    actualizarTotalesVenta();
    
    // Cargar vendedores (rol 2)
    await cargarVendedoresSelect();
    
    // Cargar clientes (rol 3)
    await cargarClientesSelectVenta();
    
    // Cargar productos
    await cargarProductosSelectVenta();
    
    // Establecer vendedor actual si está logueado
    const usuario = obtenerUsuarioActual();
    if (usuario && usuario.idRol === 2) {
        document.getElementById('ventaVendedor').value = usuario.idUsuario;
    }
    
    modalNuevaVenta.show();
}

async function cargarVendedoresSelect() {
    try {
        // Cargar Administradores (rol 1) y Vendedores (rol 2)
        const responseAdmin = await fetch(`${API_URL}/usuarios?rol=1`);
        const responseVendedor = await fetch(`${API_URL}/usuarios?rol=2`);
        
        const dataAdmin = await responseAdmin.json();
        const dataVendedor = await responseVendedor.json();
        
        const select = document.getElementById('ventaVendedor');
        select.innerHTML = '<option value="">Seleccionar vendedor...</option>';
  // Agregar administradores
        if (dataAdmin.exito) {
            dataAdmin.datos.forEach(admin => {
                select.innerHTML += `<option value="${admin.idUsuario}">${admin.nombre} (Admin)</option>`;
            });
        }
// Agregar vendedores
        if (dataVendedor.exito) {
            dataVendedor.datos.forEach(vendedor => {
                select.innerHTML += `<option value="${vendedor.idUsuario}">${vendedor.nombre} (Vendedor)</option>`;
            });
        }
    } catch (error) {
        console.error('Error cargando vendedores:', error);
    }
}

async function cargarClientesSelectVenta() {
    try {
        const response = await fetch(`${API_URL}/usuarios?rol=3`);
        const data = await response.json();
        
        const select = document.getElementById('ventaCliente');
        select.innerHTML = '<option value="">Seleccionar cliente...</option>';
        
        if (data.exito) {
            data.datos.forEach(cliente => {
                select.innerHTML += `<option value="${cliente.idUsuario}">${cliente.nombre} (${cliente.correo})</option>`;
            });
        }
    } catch (error) {
        console.error('Error cargando clientes:', error);
    }
}

async function cargarProductosSelectVenta() {
    try {
        const response = await fetch(`${API_URL}/productos?todos=1`);
        const data = await response.json();
        
        const select = document.getElementById('selectProductoVenta');
        select.innerHTML = '<option value="">Seleccionar producto...</option>';
        
        if (data.exito) {
            data.datos.forEach(prod => {
                if (prod.stock > 0) {
                    select.innerHTML += `<option value="${prod.idProducto}" data-precio="${prod.precio_unitario}" data-stock="${prod.stock}">
                        ${prod.nombre} - Q${prod.precio_unitario} (Stock: ${prod.stock})
                    </option>`;
                }
            });
        }
    } catch (error) {
        console.error('Error cargando productos:', error);
    }
}

function agregarProductoAVenta() {
    const select = document.getElementById('selectProductoVenta');
    const cantidad = parseInt(document.getElementById('cantidadProductoVenta').value);
    
    if (!select.value) {
        alert('Selecciona un producto');
        return;
    }
    
    if (cantidad <= 0) {
        alert('La cantidad debe ser mayor a 0');
        return;
    }
    
    const option = select.options[select.selectedIndex];
    const idProducto = parseInt(select.value);
    const nombreProducto = option.text.split(' - ')[0];
    const precio = parseFloat(option.dataset.precio);
    const stockDisponible = parseInt(option.dataset.stock);
    
    if (cantidad > stockDisponible) {
        alert(`Stock insuficiente. Disponible: ${stockDisponible}`);
        return;
    }
    
    // Verificar si ya existe
    const existe = productosVentaTemporales.find(p => p.idProducto === idProducto);
    if (existe) {
        alert('Este producto ya fue agregado');
        return;
    }
    
    const subtotal = precio * cantidad;
    
    productosVentaTemporales.push({
        idProducto,
        nombreProducto,
        cantidad,
        precio,
        subtotal
    });
    
    mostrarProductosVentaAgregados();
    actualizarTotalesVenta();
    
    // Limpiar
    select.value = '';
    document.getElementById('cantidadProductoVenta').value = 1;
}

function mostrarProductosVentaAgregados() {
    const container = document.getElementById('productosVentaAgregados');
    
    if (productosVentaTemporales.length === 0) {
        container.innerHTML = '<p class="text-muted text-center">No hay productos agregados</p>';
        return;
    }
    
    container.innerHTML = '';
    
    productosVentaTemporales.forEach((prod, index) => {
        const div = document.createElement('div');
        div.className = 'producto-item d-flex justify-content-between align-items-center';
        div.innerHTML = `
            <div>
                <strong>${prod.nombreProducto}</strong><br>
                <small>Q${prod.precio.toFixed(2)} × ${prod.cantidad} = Q${prod.subtotal.toFixed(2)}</small>
            </div>
            <button class="btn btn-sm btn-danger" onclick="eliminarProductoVenta(${index})">
                <i class="fas fa-trash"></i>
            </button>
        `;
        container.appendChild(div);
    });
}

function eliminarProductoVenta(index) {
    productosVentaTemporales.splice(index, 1);
    mostrarProductosVentaAgregados();
    actualizarTotalesVenta();
}

function actualizarTotalesVenta() {
    const subtotal = productosVentaTemporales.reduce((sum, p) => sum + p.subtotal, 0);
    const impuestos = subtotal * 0.12; // 12% IVA
    const total = subtotal + impuestos;
    
    document.getElementById('subtotalVenta').textContent = `Q${subtotal.toFixed(2)}`;
    document.getElementById('impuestosVenta').textContent = `Q${impuestos.toFixed(2)}`;
    document.getElementById('totalVenta').textContent = `Q${total.toFixed(2)}`;
}

// ==================== CREAR VENTA ====================

async function crearVenta() {
    const vendedor = document.getElementById('ventaVendedor').value;
    const cliente = document.getElementById('ventaCliente').value;
    
    if (!vendedor) {
        alert('Selecciona un vendedor');
        return;
    }
    
    if (!cliente) {
        alert('Selecciona un cliente');
        return;
    }
    
    if (productosVentaTemporales.length === 0) {
        alert('Agrega al menos un producto');
        return;
    }
    
    const subtotal = productosVentaTemporales.reduce((sum, p) => sum + p.subtotal, 0);
    const impuestos = subtotal * 0.12;
    const total = subtotal + impuestos;
    
    const datosVenta = {
        idUsuarioVendedor: parseInt(vendedor),
        idUsuarioCliente: parseInt(cliente),
        productos: productosVentaTemporales,
        Total: total,
        impuestoTotal: impuestos
    };
    
    try {
        const response = await fetch(`${API_URL}/ventas`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datosVenta)
        });
        
        const data = await response.json();
        
        if (data.exito) {
            alert('✅ Venta registrada correctamente');
            modalNuevaVenta.hide();
            cargarVentasAdmin();
        } else {
            alert('❌ Error: ' + data.mensaje);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error de conexión');
    }
}

// ==================== VER DETALLE ====================

async function verDetalleVenta(idVenta) {
    try {
        const response = await fetch(`${API_URL}/ventas/${idVenta}`);
        const data = await response.json();
        
        if (data.exito) {
            const venta = data.datos;
            const modal = new bootstrap.Modal(document.getElementById('modalDetalleVenta'));
            
            document.getElementById('detalleVentaId').textContent = `#${venta.idVenta}`;
            
            let productosHTML = '';
            if (venta.productos && venta.productos.length > 0) {
                productosHTML = '<table class="table"><thead><tr><th>Producto</th><th>Cant.</th><th>Precio</th><th>Subtotal</th></tr></thead><tbody>';
                venta.productos.forEach(p => {
                    productosHTML += `
                        <tr>
                            <td>${p.nombreProducto}</td>
                            <td>${p.cantidad}</td>
                            <td>Q${parseFloat(p.precioUnitario || p.precio).toFixed(2)}</td>
                            <td>Q${parseFloat(p.sub_total).toFixed(2)}</td>
                        </tr>
                    `;
                });
                productosHTML += '</tbody></table>';
            }
            
            const subtotal = venta.Total - venta.impuestoTotal;
            
            document.getElementById('contenidoDetalleVenta').innerHTML = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Fecha:</strong> ${new Date(venta.fechaRegistro).toLocaleString('es-GT')}
                    </div>
                    <div class="col-md-6">
                        <strong>Vendedor:</strong> ${venta.nombreVendedor}
                    </div>
                </div>
                <div class="mb-3">
                    <strong>Cliente:</strong> ${venta.nombreCliente}
                </div>
                <hr>
                <h6>Productos:</h6>
                ${productosHTML}
                <hr>
                <div class="text-end">
                    <p><strong>Subtotal:</strong> Q${subtotal.toFixed(2)}</p>
                    <p><strong>Impuestos:</strong> Q${parseFloat(venta.impuestoTotal).toFixed(2)}</p>
                    <h5><strong>TOTAL:</strong> Q${parseFloat(venta.Total).toFixed(2)}</h5>
                </div>
            `;
            
            modal.show();
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al cargar detalle');
    }
}

// ==================== FILTROS ====================

function configurarFiltrosVentas() {
    const filtroFecha = document.getElementById('filtroFechaVenta');
    const filtroVendedor = document.getElementById('filtroVendedor');
    const filtroCliente = document.getElementById('filtroClienteVenta');
    
    if (filtroFecha) {
        filtroFecha.addEventListener('change', aplicarFiltrosVentas);
    }
    
    if (filtroVendedor) {
        cargarVendedoresFiltro();
        filtroVendedor.addEventListener('change', aplicarFiltrosVentas);
    }
    
    if (filtroCliente) {
        cargarClientesFiltro();
        filtroCliente.addEventListener('change', aplicarFiltrosVentas);
    }
}

async function cargarVendedoresFiltro() {
    try {
        const responseAdmin = await fetch(`${API_URL}/usuarios?rol=1`);
        const responseVendedor = await fetch(`${API_URL}/usuarios?rol=2`);
        
        const dataAdmin = await responseAdmin.json();
        const dataVendedor = await responseVendedor.json();
        
        const select = document.getElementById('filtroVendedor');
        // Agregar administradores
        if (dataAdmin.exito) {
            dataAdmin.datos.forEach(v => {
                select.innerHTML += `<option value="${v.idUsuario}">${v.nombre} (Admin)</option>`;
            });
        }
        // Agregar vendedores
        if (dataVendedor.exito) {
            dataVendedor.datos.forEach(v => {
                select.innerHTML += `<option value="${v.idUsuario}">${v.nombre} (Vendedor)</option>`;
    });
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function cargarClientesFiltro() {
    try {
        const response = await fetch(`${API_URL}/usuarios?rol=3`);
        const data = await response.json();
        
        const select = document.getElementById('filtroClienteVenta');
        if (data.exito) {
            data.datos.forEach(c => {
                select.innerHTML += `<option value="${c.idUsuario}">${c.nombre}</option>`;
            });
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function aplicarFiltrosVentas() {
    const filtros = {
        fecha: document.getElementById('filtroFechaVenta').value,
        vendedor: document.getElementById('filtroVendedor').value,
        cliente: document.getElementById('filtroClienteVenta').value
    };
    
    cargarVentasAdmin(filtros);
}

function limpiarFiltrosVentas() {
    document.getElementById('filtroFechaVenta').value = '';
    document.getElementById('filtroVendedor').value = '';
    document.getElementById('filtroClienteVenta').value = '';
    cargarVentasAdmin();
}

// Exportar funciones
window.cargarVentasAdmin = cargarVentasAdmin;
window.abrirModalNuevaVenta = abrirModalNuevaVenta;
window.agregarProductoAVenta = agregarProductoAVenta;
window.eliminarProductoVenta = eliminarProductoVenta;
window.crearVenta = crearVenta;
window.verDetalleVenta = verDetalleVenta;
window.configurarFiltrosVentas = configurarFiltrosVentas;
window.limpiarFiltrosVentas = limpiarFiltrosVentas;
//aqui