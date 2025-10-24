import React, { useState, useEffect } from 'react';

// ============================================
// COMPONENTE REACT
// ============================================
const Sidebar = ({ vistaActual, onCambiarVista }) => {
  const [menuAbierto, setMenuAbierto] = useState(true);
  const [usuario, setUsuario] = useState({ nombre: 'Usuario', rol: 'Admin' });

  // Menú de navegación
  const menuItems = [
    { id: 'dashboard', nombre: 'Dashboard', icono: '📊' },
    { id: 'compras', nombre: 'Compras', icono: '🛒' },
    { id: 'ventas', nombre: 'Ventas', icono: '💰' },
    { id: 'usuarios', nombre: 'Usuarios', icono: '👥' },
  ];

  useEffect(() => {
    cargarDatosUsuario();
  }, []);

  const cargarDatosUsuario = () => {
    // Aquí conectarías con tu API o localStorage
    setUsuario({
      nombre: 'Admin Usuario',
      rol: 'Administrador',
    });
  };

  const obtenerIniciales = (nombre) => {
    const palabras = nombre.split(' ');
    if (palabras.length >= 2) {
      return palabras[0][0] + palabras[1][0];
    }
    return palabras[0][0];
  };

  const handleCerrarSesion = () => {
    if (window.confirm('¿Estás seguro de cerrar sesión?')) {
      // Aquí iría tu lógica de logout
      console.log('Cerrando sesión...');
    }
  };

  return (
    <>
      {/* ============================================ */}
      {/* ESTRUCTURA HTML DEL MENÚ */}
      {/* ============================================ */}
      <div className={`sidebar ${menuAbierto ? 'abierto' : 'cerrado'}`}>
        {/* Header del sidebar */}
        <div className="sidebar-header">
          <div className="logo-container">
            <div className="logo-icono">QA</div>
            <span className="logo-texto">ProyectoQA</span>
          </div>
          <button 
            className="btn-toggle" 
            onClick={() => setMenuAbierto(!menuAbierto)}
            aria-label="Toggle menu"
          >
            {menuAbierto ? '←' : '→'}
          </button>
        </div>

        {/* Información del usuario */}
        <div className="usuario-info">
          <div className="usuario-avatar">
            {obtenerIniciales(usuario.nombre)}
          </div>
          <div className="usuario-datos">
            <div className="usuario-nombre">{usuario.nombre}</div>
            <div className="usuario-rol">{usuario.rol}</div>
          </div>
        </div>

        {/* Menú de navegación */}
        <nav className="sidebar-nav">
          {menuItems.map((item) => (
            <button
              key={item.id}
              className={`nav-item ${vistaActual === item.id ? 'activo' : ''}`}
              onClick={() => onCambiarVista(item.id)}
            >
              <span className="nav-icono">{item.icono}</span>
              <span className="nav-texto">{item.nombre}</span>
            </button>
          ))}
        </nav>

        {/* Botón de cerrar sesión */}
        <div className="sidebar-footer">
          <button className="btn-logout" onClick={handleCerrarSesion}>
            <span className="nav-icono">🚪</span>
            <span className="nav-texto">Cerrar Sesión</span>
          </button>
        </div>
      </div>

      {/* Overlay para móviles */}
      {menuAbierto && (
        <div 
          className="sidebar-overlay" 
          onClick={() => setMenuAbierto(false)}
        />
      )}

      {/* ============================================ */}
      {/* ESTILOS CSS */}
      {/* ============================================ */}
      <style jsx>{`
        /* Variables CSS */
        :root {
          --sidebar-width: 280px;
          --sidebar-collapsed: 80px;
          --color-primary: #6366f1;
          --color-secondary: #8b5cf6;
          --color-bg: #0f172a;
          --color-bg-hover: #1e293b;
          --color-text: #e2e8f0;
          --color-text-dim: #94a3b8;
          --transition: 0.3s ease;
        }

        /* Contenedor principal del sidebar */
        .sidebar {
          position: fixed;
          left: 0;
          top: 0;
          height: 100vh;
          width: var(--sidebar-width);
          background: var(--color-bg);
          display: flex;
          flex-direction: column;
          transition: width var(--transition);
          z-index: 1000;
          box-shadow: 4px 0 10px rgba(0, 0, 0, 0.3);
        }

        .sidebar.cerrado {
          width: var(--sidebar-collapsed);
        }

        .sidebar.cerrado .logo-texto,
        .sidebar.cerrado .nav-texto,
        .sidebar.cerrado .usuario-datos {
          opacity: 0;
          width: 0;
          overflow: hidden;
        }

        /* Header con logo */
        .sidebar-header {
          padding: 20px;
          display: flex;
          align-items: center;
          justify-content: space-between;
          border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo-container {
          display: flex;
          align-items: center;
          gap: 12px;
        }

        .logo-icono {
          width: 45px;
          height: 45px;
          background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
          border-radius: 12px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-weight: bold;
          font-size: 20px;
          color: white;
          box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }

        .logo-texto {
          font-size: 22px;
          font-weight: 700;
          color: var(--color-text);
          transition: opacity var(--transition);
        }

        .btn-toggle {
          width: 32px;
          height: 32px;
          border-radius: 8px;
          border: none;
          background: var(--color-bg-hover);
          color: var(--color-text);
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;
          transition: all var(--transition);
          font-size: 18px;
        }

        .btn-toggle:hover {
          background: var(--color-primary);
          transform: scale(1.1);
        }

        /* Información del usuario */
        .usuario-info {
          padding: 20px;
          display: flex;
          align-items: center;
          gap: 12px;
          background: rgba(255, 255, 255, 0.05);
          border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .usuario-avatar {
          width: 50px;
          height: 50px;
          min-width: 50px;
          border-radius: 50%;
          background: linear-gradient(135deg, var(--color-secondary), var(--color-primary));
          display: flex;
          align-items: center;
          justify-content: center;
          font-weight: bold;
          font-size: 18px;
          color: white;
          box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
        }

        .usuario-datos {
          display: flex;
          flex-direction: column;
          gap: 4px;
          transition: opacity var(--transition);
          overflow: hidden;
        }

        .usuario-nombre {
          font-size: 15px;
          font-weight: 600;
          color: var(--color-text);
          white-space: nowrap;
        }

        .usuario-rol {
          font-size: 13px;
          color: var(--color-text-dim);
        }

        /* Navegación */
        .sidebar-nav {
          flex: 1;
          padding: 20px 10px;
          overflow-y: auto;
        }

        .nav-item {
          width: 100%;
          display: flex;
          align-items: center;
          gap: 15px;
          padding: 14px 18px;
          margin-bottom: 8px;
          background: transparent;
          border: none;
          border-radius: 12px;
          color: var(--color-text-dim);
          font-size: 15px;
          cursor: pointer;
          transition: all var(--transition);
          text-align: left;
        }

        .nav-item:hover {
          background: var(--color-bg-hover);
          color: var(--color-text);
          transform: translateX(5px);
        }

        .nav-item.activo {
          background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
          color: white;
          box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .nav-icono {
          font-size: 22px;
          min-width: 30px;
          display: flex;
          align-items: center;
          justify-content: center;
        }

        .nav-texto {
          transition: opacity var(--transition);
          white-space: nowrap;
        }

        /* Footer con logout */
        .sidebar-footer {
          padding: 20px 10px;
          border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-logout {
          width: 100%;
          display: flex;
          align-items: center;
          gap: 15px;
          padding: 14px 18px;
          background: transparent;
          border: 1px solid rgba(239, 68, 68, 0.3);
          border-radius: 12px;
          color: #ef4444;
          font-size: 15px;
          cursor: pointer;
          transition: all var(--transition);
        }

        .btn-logout:hover {
          background: #ef4444;
          color: white;
          border-color: #ef4444;
          transform: translateY(-2px);
          box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }

        /* Overlay para móviles */
        .sidebar-overlay {
          display: none;
        }

        /* Scrollbar personalizado */
        .sidebar-nav::-webkit-scrollbar {
          width: 6px;
        }

        .sidebar-nav::-webkit-scrollbar-track {
          background: transparent;
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
          background: rgba(255, 255, 255, 0.2);
          border-radius: 10px;
        }

        .sidebar-nav::-webkit-scrollbar-thumb:hover {
          background: rgba(255, 255, 255, 0.3);
        }

        /* Responsive */
        @media (max-width: 768px) {
          .sidebar {
            transform: translateX(-100%);
          }

          .sidebar.abierto {
            transform: translateX(0);
          }

          .sidebar-overlay {
            display: block;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
          }

          .btn-toggle {
            display: flex;
          }
        }
      `}</style>
    </>
  );
};

export default Sidebar;