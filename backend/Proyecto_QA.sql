-- =========================================
-- BASE DE DATOS: Proyecto QA
-- =========================================
create database if not exists Uniformes_deportivos;
use Uniformes_deportivos;

-- Tabla rol (debe crearse primero por dependencias)
CREATE TABLE rol (
    idRol INT AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(50) NOT NULL,
    esActivo bit,
    fechaRegistro DATETIME DEFAULT CURRENT_TIMESTAMP
);
SHOW TABLES;
-- Tabla categoria (debe crearse antes que producto)
CREATE TABLE categoria (
    idCategoria INT AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(50) NOT NULL,
    esActivo bit,
    fechaRegistro DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabla cliente (independiente)
CREATE TABLE cliente (
    idCliente INT AUTO_INCREMENT PRIMARY KEY,
    primer_nombre VARCHAR(45) NOT NULL,
    segundo_nombre VARCHAR(45),
    primer_apellido VARCHAR(45) NOT NULL,
    segundo_apellido VARCHAR(45),
    genero VARCHAR(45),
    direccion VARCHAR(60),
    telefono INT
);

-- Tabla usuario (depende de rol)
CREATE TABLE usuario (
    idUsuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(40) NOT NULL,
    correo VARCHAR(40) NOT NULL UNIQUE,
    idRol INT NOT NULL,
    contrasenia VARCHAR(40) NOT NULL,
    esActivo bit,
    fechaRegistro DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_rol 
	FOREIGN KEY (idRol) REFERENCES rol(idRol) 
	ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Tabla producto (depende de categoria)
CREATE TABLE producto (
    idProducto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    marca VARCHAR(100),
    descripcion VARCHAR(100),
    idCategoria INT NOT NULL,
    stock INT DEFAULT 0,
    precio_unitario DECIMAL(10,2) NOT NULL,
    esActivo bit,
    fechaRegistro DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_producto_categoria 
	FOREIGN KEY (idCategoria) REFERENCES categoria(idCategoria) 
	ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Tabla venta (depende de usuario y cliente)
CREATE TABLE venta (
    idVenta INT AUTO_INCREMENT PRIMARY KEY,
    fechaRegistro DATETIME DEFAULT CURRENT_TIMESTAMP,
    idUsuario INT NOT NULL,
    idCliente INT NOT NULL,
    Total DECIMAL(10,2) NOT NULL,
    impuestoTotal DECIMAL(10,2) DEFAULT 0,
    CONSTRAINT fk_venta_usuario 
	FOREIGN KEY (idUsuario) REFERENCES usuario(idUsuario) 
	ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_venta_cliente 
	FOREIGN KEY (idCliente) REFERENCES cliente(idCliente) 
	ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Tabla pedido (depende de cliente)
CREATE TABLE pedido (
    idPedido INT AUTO_INCREMENT PRIMARY KEY,
    fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado_pedido VARCHAR(50) DEFAULT 'Pendiente',
    costo_total_pedido DECIMAL(10,2) NOT NULL,
    idCliente INT NOT NULL,
    fecha_entrega DATETIME,
    monto_pagado DECIMAL(10,2) DEFAULT 0,
    estado_pago VARCHAR(45) DEFAULT 'Pendiente',
    CONSTRAINT fk_pedido_cliente 
	FOREIGN KEY (idCliente) REFERENCES cliente(idCliente) 
	ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Tabla detalleventa (depende de venta y producto)
CREATE TABLE detalleventa (
    idDetalleVenta INT AUTO_INCREMENT PRIMARY KEY,
    idVenta INT NOT NULL,
    idProducto INT NOT NULL,
    cantidad INT NOT NULL,
    sub_total DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_detalleventa_venta 
	FOREIGN KEY (idVenta) REFERENCES venta(idVenta) 
	ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_detalleventa_producto 
	FOREIGN KEY (idProducto) REFERENCES producto(idProducto) 
	ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Tabla detalle_pedido (depende de pedido y producto)
CREATE TABLE detalle_pedido (
    id_detalle_pedido INT AUTO_INCREMENT PRIMARY KEY,
    cantidad_producto INT NOT NULL,
    diseño TEXT,
    id_pedido INT NOT NULL,
    id_producto INT NOT NULL,
    CONSTRAINT fk_detalle_pedido_pedido 
	FOREIGN KEY (id_pedido) REFERENCES pedido(idPedido) 
	ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_detalle_pedido_producto 
	FOREIGN KEY (id_producto) REFERENCES producto(idProducto) 
	ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Tabla pago (depende de pedido y opcionalmente de venta)
CREATE TABLE pago (
    idPago INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL,
    monto_pagado DECIMAL(10,2) NOT NULL,
    fecha_pago TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    metodo_pago VARCHAR(45) NOT NULL,
    descripcion VARCHAR(45),
    id_venta INT,
    CONSTRAINT fk_pago_pedido 
	FOREIGN KEY (id_pedido) REFERENCES pedido(idPedido) 
	ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pago_venta 
	FOREIGN KEY (id_venta) REFERENCES venta(idVenta) 
	ON DELETE SET NULL ON UPDATE CASCADE
);

-- Tabla detalle_uniforme (depende de detalle_pedido)
CREATE TABLE detalle_uniforme (
    idDetalle_uniforme INT AUTO_INCREMENT PRIMARY KEY,
    id_detalle_pedido INT NOT NULL,
    tela VARCHAR(20),
    genero CHAR(1),
    nombre_camisola VARCHAR(60),
    numero_camisola VARCHAR(60),
    nombre_abajo_numero VARCHAR(60),
    cantidad INT NOT NULL,
    con_medidas DOUBLE,
	CONSTRAINT fk_detalle_uniforme_detalle_pedido 
    FOREIGN KEY (id_detalle_pedido) REFERENCES detalle_pedido(id_detalle_pedido) 
	ON DELETE CASCADE ON UPDATE CASCADE
);
