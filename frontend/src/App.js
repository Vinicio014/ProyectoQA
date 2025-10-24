import React, { useState } from 'react';
import Sidebar from './components/layout/Sidebar';
import './App.css';

function App() {
  const [vistaActual, setVistaActual] = useState('dashboard');

  const renderizarVista = () => {
    switch (vistaActual) {
      case 'dashboard':
        return (
          <div className="vista-placeholder">
            <h1>📊 Dashboard</h1>
            <p>Vista de Dashboard - Próximamente</p>
          </div>
        );
      case 'compras':
        return (
          <div className="vista-placeholder">
            <h1>🛒 Compras</h1>
            <p>Vista de Compras - Próximamente</p>
          </div>
        );
      case 'ventas':
        return (
          <div className="vista-placeholder">
            <h1>💰 Ventas</h1>
            <p>Vista de Ventas - Próximamente</p>
          </div>
        );
      case 'usuarios':
        return (
          <div className="vista-placeholder">
            <h1>👥 Usuarios</h1>
            <p>Vista de Usuarios - Próximamente</p>
          </div>
        );
      default:
        return (
          <div className="vista-placeholder">
            <h1>Bienvenido</h1>
            <p>Selecciona una opción del menú</p>
          </div>
        );
    }
  };

  return (
    <div className="App">
      <Sidebar 
        vistaActual={vistaActual} 
        onCambiarVista={setVistaActual} 
      />
      
      <main className="main-content">
        {renderizarVista()}
      </main>
    </div>
  );
}

export default App;