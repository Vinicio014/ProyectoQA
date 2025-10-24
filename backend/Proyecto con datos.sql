-- =========================================
-- BASE DE DATOS: Proyecto QA
-- =========================================
create database if not exists Uniformes_deportivos;
use Uniformes_deportivos;

select * from pedido;
-- Tabla rol (debe crearse primero por dependencias)
CREATE TABLE rol (
    idRol INT AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(50) NOT NULL,
    esActivo bit,
    fechaRegistro DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- DATOS: Roles
INSERT INTO rol (descripcion, esActivo) VALUES
('Administrador', 1),
('Vendedor', 1),
('Cliente', 1);

-- Tabla categoria (debe crearse antes que producto)
CREATE TABLE categoria (
    idCategoria INT AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(50) NOT NULL,
    esActivo bit,
    fechaRegistro DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- DATOS: Categorías
INSERT INTO categoria (descripcion, esActivo) VALUES
('Camisetas Deportivas', 1),
('Uniformes Completos', 1),
('Accesorios Deportivos', 1);

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

-- DATOS: Clientes
INSERT INTO cliente (primer_nombre, segundo_nombre, primer_apellido, segundo_apellido, genero, direccion, telefono) VALUES
('Carlos', 'Alberto', 'Ramírez', 'López', 'M', 'Zona 1, Ciudad de Guatemala', 55512345),
('Ana', 'María', 'González', 'Pérez', 'F', 'Zona 10, Ciudad de Guatemala', 55523456),
('Pedro', 'José', 'Martínez', 'Ruiz', 'M', 'Antigua Guatemala', 55534567),
('Laura', 'Isabel', 'Hernández', 'Castro', 'F', 'Quetzaltenango', 55545678),
('Jorge', 'Luis', 'Díaz', 'Morales', 'M', 'Zona 5, Guatemala', 55556789),
('Sofía', NULL, 'Vásquez', 'Torres', 'F', 'Escuintla', 55567890);

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


SELECT 
    idUsuario,
    nombre,
    correo,
    esActivo,
    HEX(esActivo) as esActivo_hex,
    CAST(esActivo AS UNSIGNED) as esActivo_int,
    contrasenia,
    idRol
FROM usuario 
WHERE correo = 'juan.perez@uniformes.com';

-- DATOS: Usuarios (Contraseñas sin encriptar para pruebas: admin123, vendedor123, cliente123)
INSERT INTO usuario (nombre, correo, idRol, contrasenia, esActivo) VALUES
('Administrador Sistema', 'admin@tienda.com', 1, MD5('admin123'), 1),
('Juan Vendedor', 'vendedor@tienda.com', 2, MD5('vendedor123'), 1),
('María Cliente', 'maria@ejemplo.com', 3, MD5('cliente123'), 1),
('Luis Pérez', 'luis@ejemplo.com', 2, MD5('vendedor123'), 1);

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

-- DATOS: Productos (Solo Camisetas, Uniformes y Accesorios)
INSERT INTO producto (nombre, marca, descripcion, idCategoria, stock, precio_unitario, esActivo) VALUES
-- Camisetas Deportivas (idCategoria = 1)
('Camiseta Deportiva Básica', 'Nike', 'Camiseta de poliéster transpirable, ideal para entrenamientos', 1, 50, 125.00, 1),
('Camiseta Personalizada', 'Adidas', 'Camiseta con personalización de nombre y número', 1, 30, 180.00, 1),

-- Uniformes Completos (idCategoria = 2)
('Uniforme Fútbol Completo', 'Nike', 'Set: camiseta + short + medias', 2, 20, 400.00, 1),
('Uniforme Baloncesto Completo', 'Adidas', 'Set: camiseta + short', 2, 15, 380.00, 1),
('Uniforme Escolar Deportivo', 'Genérico', 'Uniforme para educación física escolar', 2, 30, 350.00, 1),

-- Accesorios Deportivos (idCategoria = 3)
('Medias Deportivas Largas', 'Nike', 'Par de medias largas deportivas', 3, 100, 35.00, 1),
('Muñequeras Par', 'Nike', 'Par de muñequeras deportivas', 3, 60, 40.00, 1),
('Rodilleras Par', 'Mizuno', 'Par de rodilleras protectoras', 3, 45, 75.00, 1),
('Banda de Capitán', 'Genérico', 'Banda elástica de capitán con colores', 3, 50, 50.00, 1),
('Guantes Portero', 'Adidas', 'Guantes profesionales para portero', 3, 22, 280.00, 1),
('Espinilleras Par', 'Nike', 'Par de espinilleras con correas', 3, 55, 95.00, 1),
('Bolsa Deportiva', 'Puma', 'Bolsa para transportar equipamiento', 3, 38, 150.00, 1),
('Gorra Deportiva', 'Under Armour', 'Gorra con protección UV', 3, 45, 85.00, 1),
('Toalla Microfibra', 'Genérico', 'Toalla deportiva de secado rápido', 3, 70, 55.00, 1);

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

-- DATOS: Ventas
INSERT INTO venta (idUsuario, idCliente, Total, impuestoTotal, fechaRegistro) VALUES
(2, 1, 525.00, 63.00, '2025-01-15 10:30:00'),
(2, 2, 780.00, 93.60, '2025-01-16 14:20:00'),
(4, 3, 400.00, 48.00, '2025-01-17 09:15:00'),
(2, 4, 950.00, 114.00, '2025-01-18 16:45:00'),
(4, 5, 310.00, 37.20, '2025-01-19 11:00:00');

-- Tabla pedido (depende de cliente)
CREATE TABLE pedido (
    idPedido INT AUTO_INCREMENT PRIMARY KEY,
    fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado_pedido VARCHAR(50) DEFAULT 'Pendiente',
    costo_total_pedido DECIMAL(10,2) NOT NULL,
    idUsuario INT NOT NULL,
    fecha_entrega DATETIME,
    monto_pagado DECIMAL(10,2) DEFAULT 0,
    estado_pago VARCHAR(45) DEFAULT 'Pendiente',
    CONSTRAINT fk_pedido_usuario 
	FOREIGN KEY (idUsuario) REFERENCES usuario(idUsuario) 
	ON DELETE RESTRICT ON UPDATE CASCADE
);



-- DATOS: Pedidos
INSERT INTO pedido (idCliente, costo_total_pedido, estado_pedido, fecha_entrega, monto_pagado, estado_pago, fecha_pedido) VALUES
(1, 2500.00, 'En Proceso', '2025-02-01', 1000.00, 'Parcial', '2025-01-20 10:00:00'),
(2, 1800.00, 'Pendiente', '2025-02-05', 0.00, 'Pendiente', '2025-01-21 14:30:00'),
(3, 3200.00, 'Completado', '2025-01-25', 3200.00, 'Pagado', '2025-01-10 09:00:00'),
(4, 1500.00, 'En Proceso', '2025-02-10', 750.00, 'Parcial', '2025-01-22 11:15:00'),
(5, 4200.00, 'Pendiente', '2025-02-15', 0.00, 'Pendiente', '2025-01-23 16:00:00');

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

-- DATOS: Detalles de Venta
INSERT INTO detalleventa (idVenta, idProducto, cantidad, sub_total) VALUES
-- Venta 1
(1, 1, 2, 250.00),
(1, 15, 5, 175.00),
(1, 17, 2, 100.00),
-- Venta 2
(2, 3, 2, 500.00),
(2, 19, 1, 280.00),
-- Venta 3
(3, 9, 1, 400.00),
-- Venta 4
(4, 4, 3, 600.00),
(4, 11, 1, 420.00),
-- Venta 5
(5, 15, 4, 140.00),
(5, 20, 2, 190.00);

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

-- DATOS: Detalle de Pedidos
INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad_producto, diseño) VALUES
-- Pedido 1
(1, 2, 10, 'Con nombre del equipo "Tigres" y número personalizado'),
(1, 9, 5, 'Colores azul y blanco'),
-- Pedido 2
(2, 1, 15, 'Color rojo básico'),
-- Pedido 3
(3, 9, 8, 'Uniformes con logo escolar personalizado'),
-- Pedido 4
(4, 3, 6, 'Diseño con rayas horizontales'),
-- Pedido 5
(5, 11, 10, 'Set completo con medias y rodilleras incluidas');

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

-- DATOS: Pagos
INSERT INTO pago (id_pedido, monto_pagado, metodo_pago, descripcion, id_venta, fecha_pago) VALUES
(1, 1000.00, 'Transferencia', 'Anticipo 40%', NULL, '2025-01-20 11:00:00'),
(3, 3200.00, 'Efectivo', 'Pago completo', NULL, '2025-01-10 15:00:00'),
(4, 750.00, 'Tarjeta', 'Anticipo 50%', NULL, '2025-01-22 12:00:00');

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

-- DATOS: Detalle de Uniformes
INSERT INTO detalle_uniforme (id_detalle_pedido, tela, genero, nombre_camisola, numero_camisola, nombre_abajo_numero, cantidad, con_medidas) VALUES
(1, 'Dry-Fit', 'M', 'GARCÍA', '10', 'CAPITÁN', 1, 42.5),
(1, 'Dry-Fit', 'M', 'LÓPEZ', '7', '', 1, 40.0),
(1, 'Dry-Fit', 'M', 'PÉREZ', '9', '', 1, 41.0),
(2, 'Poliéster', 'M', NULL, NULL, NULL, 5, NULL),
(4, 'Dry-Fit', 'F', 'MARTÍNEZ', '3', '', 3, 38.5),
(6, 'Dry-Fit', 'M', NULL, NULL, NULL, 10, NULL);

-- =========================================
-- CONSULTAS ÚTILES PARA VERIFICAR DATOS
-- =========================================

-- Ver todos los productos activos
-- SELECT * FROM producto WHERE esActivo = 1;

-- Ver ventas con detalles
-- SELECT v.*, u.nombre as vendedor, CONCAT(c.primer_nombre, ' ', c.primer_apellido) as cliente
-- FROM venta v
-- INNER JOIN usuario u ON v.idUsuario = u.idUsuario
-- INNER JOIN cliente c ON v.idCliente = c.idCliente;

-- Ver pedidos pendientes
-- SELECT * FROM pedido WHERE estado_pedido = 'Pendiente';

-- Ver productos con stock bajo
-- SELECT * FROM producto WHERE stock < 30 AND esActivo = 1;

-- Insertar datos en la tabla ROL
INSERT INTO `uniformes_deportivos`.`rol` (`descripcion`, `esActivo`, `fechaRegistro`) VALUES
('Administrador', b'1', NOW()),
('Vendedor', b'1', NOW()),
('Supervisor', b'1', NOW());

-- Insertar datos en la tabla USUARIO
INSERT INTO `uniformes_deportivos`.`usuario` (`nombre`, `correo`, `idRol`, `contrasenia`, `esActivo`, `fechaRegistro`) VALUES
('Juan Pérez', 'juan.perez@uniformes.com', 1, 'admin123', b'1', NOW()),
('María García', 'maria.garcia@uniformes.com', 2, 'vendedor123', b'1', NOW()),
('Carlos López', 'carlos.lopez@uniformes.com', 2, 'vendedor456', b'1', NOW()),
('Ana Martínez', 'ana.martinez@uniformes.com', 3, 'supervisor123', b'1', NOW());

-- Insertar datos en la tabla CATEGORIA
INSERT INTO `uniformes_deportivos`.`categoria` (`descripcion`, `esActivo`, `fechaRegistro`) VALUES
('Camisolas', b'1', NOW()),
('Shorts', b'1', NOW()),
('Medias', b'1', NOW());

-- Insertar datos en la tabla PRODUCTO
INSERT INTO `uniformes_deportivos`.`producto` (`nombre`, `marca`, `descripcion`, `idCategoria`, `stock`, `precio_unitario`, `esActivo`, `fechaRegistro`) VALUES
('Camisola Deportiva Básica', 'Nike', 'Camisola de poliéster transpirable', 1, 100, 150.00, b'1', NOW()),
('Camisola Premium', 'Adidas', 'Camisola con tecnología Climacool', 1, 75, 200.00, b'1', NOW()),
('Camisola Personalizada', 'Puma', 'Camisola con opción de sublimación', 1, 50, 180.00, b'1', NOW()),
('Short Deportivo Clásico', 'Nike', 'Short con bolsillos laterales', 2, 120, 100.00, b'1', NOW()),
('Short Portero', 'Puma', 'Short acolchado para portero', 2, 40, 150.00, b'1', NOW()),
('Medias Largas', 'Nike', 'Medias hasta la rodilla', 3, 200, 40.00, b'1', NOW()),
('Medias Cortas', 'Adidas', 'Medias deportivas tobilleras', 3, 150, 35.00, b'1', NOW()),
('Set Completo Fútbol', 'Nike', 'Camisola, short y medias', 1, 60, 350.00, b'1', NOW()),
('Set Completo Basketball', 'Adidas', 'Camisola y short de basketball', 1, 45, 320.00, b'1', NOW());

-- Insertar datos en la tabla CLIENTE
INSERT INTO `uniformes_deportivos`.`cliente` (`primer_nombre`, `segundo_nombre`, `primer_apellido`, `segundo_apellido`, `genero`, `direccion`, `telefono`) VALUES
('Roberto', 'Carlos', 'González', 'Ramírez', 'M', 'Zona 1, Ciudad Guatemala', 55551234),
('Laura', 'María', 'Hernández', 'López', 'F', 'Zona 10, Ciudad Guatemala', 55555678),
('José', 'Antonio', 'Morales', 'Castro', 'M', 'Antigua Guatemala', 55559012),
('Carmen', NULL, 'Flores', 'Ruiz', 'F', 'Quetzaltenango', 55553456),
('Miguel', 'Ángel', 'Sánchez', 'Pérez', 'M', 'Zona 5, Ciudad Guatemala', 55557890),
('Patricia', 'Elena', 'Vargas', 'Torres', 'F', 'Escuintla', 55552345);

-- Insertar datos en la tabla PEDIDO
INSERT INTO `uniformes_deportivos`.`pedido` (`fecha_pedido`, `estado_pedido`, `costo_total_pedido`, `idCliente`, `fecha_entrega`, `monto_pagado`, `estado_pago`) VALUES
(NOW(), 'Completado', 2800.00, 1, DATE_ADD(NOW(), INTERVAL 15 DAY), 2800.00, 'Pagado'),
(NOW(), 'En Proceso', 5250.00, 2, DATE_ADD(NOW(), INTERVAL 20 DAY), 2625.00, 'Parcial'),
(DATE_SUB(NOW(), INTERVAL 5 DAY), 'Completado', 1800.00, 3, DATE_ADD(NOW(), INTERVAL 10 DAY), 1800.00, 'Pagado'),
(DATE_SUB(NOW(), INTERVAL 3 DAY), 'Pendiente', 3500.00, 4, DATE_ADD(NOW(), INTERVAL 25 DAY), 0.00, 'Pendiente'),
(NOW(), 'En Proceso', 4200.00, 5, DATE_ADD(NOW(), INTERVAL 18 DAY), 1400.00, 'Parcial');

-- Insertar datos en la tabla DETALLE_PEDIDO
INSERT INTO `uniformes_deportivos`.`detalle_pedido` (`cantidad_producto`, `diseño`, `id_pedido`, `id_producto`) VALUES
(20, 'Logo del equipo en el pecho, rayas verticales azules', 1, 1),
(20, 'Sin diseño especial', 1, 4),
(30, 'Diseño sublimado con escudo del equipo', 2, 3),
(30, 'Short negro con líneas amarillas laterales', 2, 5),
(15, 'Camisola roja con número dorado', 3, 2),
(10, 'Logo grande en el centro', 4, 9),
(25, 'Diseño personalizado con nombre de la empresa', 5, 1);

-- Insertar datos en la tabla DETALLE_UNIFORME
INSERT INTO `uniformes_deportivos`.`detalle_uniforme` (`id_detalle_pedido`, `talla`, `genero`, `nombre_camisola`, `numero_camisola`, `nombre_abajo_numero`, `cantidad`, `con_medidas`) VALUES
(1, 'M', 'M', 'GONZÁLEZ', '10', 'CAPITÁN', 1, NULL),
(1, 'L', 'M', 'RAMÍREZ', '7', NULL, 1, NULL),
(1, 'S', 'M', 'LÓPEZ', '9', NULL, 2, NULL),
(2, 'M', 'M', NULL, NULL, NULL, 20, NULL),
(3, 'XL', 'M', 'HERNÁNDEZ', '1', 'PORTERO', 1, 95.5),
(3, 'L', 'F', 'TORRES', '10', NULL, 5, NULL),
(4, 'M', 'M', NULL, NULL, NULL, 30, NULL),
(5, 'L', 'M', 'MORALES', '8', NULL, 10, NULL),
(6, 'M', 'M', NULL, NULL, NULL, 10, NULL),
(7, 'S', 'F', 'EMPRESA XYZ', '1', NULL, 10, NULL);

-- Insertar datos en la tabla VENTA
INSERT INTO `uniformes_deportivos`.`venta` (`fechaRegistro`, `idUsuario`, `idCliente`, `Total`, `impuestoTotal`) VALUES
(NOW(), 2, 1, 580.00, 69.60),
(NOW(), 2, 2, 450.00, 54.00),
(DATE_SUB(NOW(), INTERVAL 2 DAY), 3, 3, 800.00, 96.00),
(DATE_SUB(NOW(), INTERVAL 1 DAY), 2, 4, 350.00, 42.00),
(NOW(), 3, 5, 920.00, 110.40);

-- Insertar datos en la tabla DETALLEVENTA
INSERT INTO `uniformes_deportivos`.`detalleventa` (`idVenta`, `idProducto`, `cantidad`, `sub_total`) VALUES
(1, 1, 2, 300.00),
(1, 7, 7, 280.00),
(2, 4, 3, 300.00),
(2, 1, 1, 150.00),
(3, 2, 4, 800.00),
(4, 9, 1, 350.00),
(5, 3, 3, 540.00),
(5, 5, 2, 260.00),
(5, 8, 4, 120.00);
select * from usuario;
-- Insertar datos en la tabla PAGO
INSERT INTO `uniformes_deportivos`.`pago` (`id_pedido`, `monto_pagado`, `fecha_pago`, `metodo_pago`, `descripcion`, `id_venta`) VALUES
(1, 1400.00, NOW(), 'Efectivo', 'Pago inicial 50%', NULL),
(1, 1400.00, DATE_ADD(NOW(), INTERVAL 7 DAY), 'Transferencia', 'Pago final', 1),
(2, 2625.00, NOW(), 'Tarjeta', 'Anticipo 50%', 2),
(3, 1800.00, DATE_SUB(NOW(), INTERVAL 3 DAY), 'Efectivo', 'Pago completo', 3),
(5, 1400.00, NOW(), 'Efectivo', 'Primer pago', 5);