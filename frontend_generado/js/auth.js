// ============================================
// SISTEMA DE AUTENTICACIÓN - CORREGIDO
// ============================================

const API_AUTH_URL = 'http://localhost/ProyectoQA/backend/api/api_auth.php';

async function login(correo, contrasenia) {
    try {
        console.log('🔐 Intentando login con:', correo);
        
        const response = await fetch(API_AUTH_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                correo: correo,
                contrasenia: contrasenia
            })
        });

        const data = await response.json();
        console.log('📥 Respuesta:', data);

        if (data.exito) {
            localStorage.setItem('usuario', JSON.stringify(data.datos));
            localStorage.setItem('token', data.datos.token);

            console.log('✅ Login exitoso. Rol:', data.datos.idRol);

            // Convertir a número
            const rolId = Number(data.datos.idRol);
            
            console.log('Redirigiendo según rol:', rolId);

            if (rolId === 1 || rolId === 2) {
                console.log('👑 Admin/Vendedor');
                window.location.href = '../admin/dashboard.html';
            } else {
                console.log('👤 Cliente');
                window.location.href = '../usuario/productos.html';
            }

            return data;
        } else {
            throw new Error(data.mensaje);
        }

    } catch (error) {
        console.error('❌ Error:', error);
        throw error;
    }
}

function logout() {
    localStorage.removeItem('usuario');
    localStorage.removeItem('token');
    localStorage.removeItem('carrito');
    window.location.href = '../../index.html';
}

function verificarAutenticacion() {
    return localStorage.getItem('usuario') !== null;
}

function obtenerUsuarioActual() {
    const usr = localStorage.getItem('usuario');
    return usr ? JSON.parse(usr) : null;
}

function esAdministrador() {
    const usuario = obtenerUsuarioActual();
    if (!usuario) return false;
    const rolId = Number(usuario.idRol);
    return rolId === 1 || rolId === 2;
}

function protegerPagina(requiereAdmin = false) {
    if (!verificarAutenticacion()) {
        window.location.href = '../usuario/login.html';
        return false;
    }
    if (requiereAdmin && !esAdministrador()) {
        alert('Acceso denegado.');
        window.location.href = '../usuario/productos.html';
        return false;
    }
    return true;
}

function actualizarInterfazUsuario() {
    const usuario = obtenerUsuarioActual();
    if (!usuario) return;
    
    const elem = document.getElementById('userName');
    if (elem) elem.textContent = usuario.nombre;
}

// Exportar
window.login = login;
window.logout = logout;
window.verificarAutenticacion = verificarAutenticacion;
window.obtenerUsuarioActual = obtenerUsuarioActual;
window.esAdministrador = esAdministrador;
window.protegerPagina = protegerPagina;
window.actualizarInterfazUsuario = actualizarInterfazUsuario;

console.log('✅ auth.js cargado');