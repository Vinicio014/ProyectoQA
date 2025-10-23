-- MySQL dump 10.13  Distrib 8.0.36, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: uniformes_deportivos
-- ------------------------------------------------------
-- Server version	8.0.37

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `categoria`
--

DROP TABLE IF EXISTS `categoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categoria` (
  `idCategoria` int NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(50) NOT NULL,
  `esActivo` bit(1) DEFAULT NULL,
  `fechaRegistro` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idCategoria`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categoria`
--

LOCK TABLES `categoria` WRITE;
/*!40000 ALTER TABLE `categoria` DISABLE KEYS */;
INSERT INTO `categoria` VALUES (1,'Camisetas Deportivas',_binary '','2025-10-22 12:18:52'),(2,'Uniformes Completos',_binary '','2025-10-22 12:18:52'),(3,'Accesorios Deportivos',_binary '','2025-10-22 12:18:52'),(4,'Camisolas',_binary '','2025-10-22 12:42:45'),(5,'Shorts',_binary '','2025-10-22 12:42:45'),(6,'Medias',_binary '','2025-10-22 12:42:45');
/*!40000 ALTER TABLE `categoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cliente`
--

DROP TABLE IF EXISTS `cliente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cliente` (
  `idCliente` int NOT NULL AUTO_INCREMENT,
  `primer_nombre` varchar(45) NOT NULL,
  `segundo_nombre` varchar(45) DEFAULT NULL,
  `primer_apellido` varchar(45) NOT NULL,
  `segundo_apellido` varchar(45) DEFAULT NULL,
  `genero` varchar(45) DEFAULT NULL,
  `direccion` varchar(60) DEFAULT NULL,
  `telefono` int DEFAULT NULL,
  PRIMARY KEY (`idCliente`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cliente`
--

LOCK TABLES `cliente` WRITE;
/*!40000 ALTER TABLE `cliente` DISABLE KEYS */;
INSERT INTO `cliente` VALUES (1,'Carlos','Alberto','Ramírez','López','M','Zona 1, Ciudad de Guatemala',55512345),(2,'Ana','María','González','Pérez','F','Zona 10, Ciudad de Guatemala',55523456),(3,'Pedro','José','Martínez','Ruiz','M','Antigua Guatemala',55534567),(4,'Laura','Isabel','Hernández','Castro','F','Quetzaltenango',55545678),(5,'Jorge','Luis','Díaz','Morales','M','Zona 5, Guatemala',55556789),(6,'Sofía',NULL,'Vásquez','Torres','F','Escuintla',55567890),(7,'Roberto','Carlos','González','Ramírez','M','Zona 1, Ciudad Guatemala',55551234),(8,'Laura','María','Hernández','López','F','Zona 10, Ciudad Guatemala',55555678),(9,'José','Antonio','Morales','Castro','M','Antigua Guatemala',55559012),(10,'Carmen',NULL,'Flores','Ruiz','F','Quetzaltenango',55553456),(11,'Miguel','Ángel','Sánchez','Pérez','M','Zona 5, Ciudad Guatemala',55557890),(12,'Patricia','Elena','Vargas','Torres','F','Escuintla',55552345);
/*!40000 ALTER TABLE `cliente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_pedido`
--

DROP TABLE IF EXISTS `detalle_pedido`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_pedido` (
  `id_detalle_pedido` int NOT NULL AUTO_INCREMENT,
  `cantidad_producto` int NOT NULL,
  `diseño` text,
  `id_pedido` int NOT NULL,
  `id_producto` int NOT NULL,
  PRIMARY KEY (`id_detalle_pedido`),
  KEY `fk_detalle_pedido_pedido` (`id_pedido`),
  KEY `fk_detalle_pedido_producto` (`id_producto`),
  CONSTRAINT `fk_detalle_pedido_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`idPedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_detalle_pedido_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`idProducto`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_pedido`
--

LOCK TABLES `detalle_pedido` WRITE;
/*!40000 ALTER TABLE `detalle_pedido` DISABLE KEYS */;
INSERT INTO `detalle_pedido` VALUES (1,10,'Con nombre del equipo \"Tigres\" y número personalizado',1,2),(2,5,'Colores azul y blanco',1,9),(3,15,'Color rojo básico',2,1),(4,8,'Uniformes con logo escolar personalizado',3,9),(5,6,'Diseño con rayas horizontales',4,3),(6,10,'Set completo con medias y rodilleras incluidas',5,11),(7,10,'Con nombre del equipo \"Tigres\" y número personalizado',1,2),(8,5,'Colores azul y blanco',1,9),(9,15,'Color rojo básico',2,1),(10,8,'Uniformes con logo escolar personalizado',3,9),(11,6,'Diseño con rayas horizontales',4,3),(12,10,'Set completo con medias y rodilleras incluidas',5,11),(13,20,'Logo del equipo en el pecho, rayas verticales azules',1,1),(14,20,'Sin diseño especial',1,4),(15,30,'Diseño sublimado con escudo del equipo',2,3),(16,30,'Short negro con líneas amarillas laterales',2,5),(17,15,'Camisola roja con número dorado',3,2),(18,10,'Logo grande en el centro',4,9),(19,25,'Diseño personalizado con nombre de la empresa',5,1),(22,1,'',23,9),(23,1,'Juventus',24,1),(24,1,'Juventus',25,2);
/*!40000 ALTER TABLE `detalle_pedido` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_uniforme`
--

DROP TABLE IF EXISTS `detalle_uniforme`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_uniforme` (
  `idDetalle_uniforme` int NOT NULL AUTO_INCREMENT,
  `id_detalle_pedido` int NOT NULL,
  `talla` varchar(20) DEFAULT NULL,
  `genero` char(1) DEFAULT NULL,
  `nombre_camisola` varchar(60) DEFAULT NULL,
  `numero_camisola` varchar(60) DEFAULT NULL,
  `nombre_abajo_numero` varchar(60) DEFAULT NULL,
  `cantidad` int NOT NULL,
  `con_medidas` double DEFAULT NULL,
  PRIMARY KEY (`idDetalle_uniforme`),
  KEY `fk_detalle_uniforme_detalle_pedido` (`id_detalle_pedido`),
  CONSTRAINT `fk_detalle_uniforme_detalle_pedido` FOREIGN KEY (`id_detalle_pedido`) REFERENCES `detalle_pedido` (`id_detalle_pedido`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_uniforme`
--

LOCK TABLES `detalle_uniforme` WRITE;
/*!40000 ALTER TABLE `detalle_uniforme` DISABLE KEYS */;
INSERT INTO `detalle_uniforme` VALUES (1,1,'M','M','GONZÁLEZ','10','CAPITÁN',1,NULL),(2,1,'L','M','RAMÍREZ','7',NULL,1,NULL),(3,1,'S','M','LÓPEZ','9',NULL,2,NULL),(4,2,'M','M',NULL,NULL,NULL,20,NULL),(5,3,'XL','M','HERNÁNDEZ','1','PORTERO',1,95.5),(6,3,'L','F','TORRES','10',NULL,5,NULL),(7,4,'M','M',NULL,NULL,NULL,30,NULL),(8,5,'L','M','MORALES','8',NULL,10,NULL),(9,6,'M','M',NULL,NULL,NULL,10,NULL),(10,7,'S','F','EMPRESA XYZ','1',NULL,10,NULL),(11,23,NULL,'M','Armando','10','',1,0),(12,24,'8','F','Ana','','',1,0);
/*!40000 ALTER TABLE `detalle_uniforme` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalleventa`
--

DROP TABLE IF EXISTS `detalleventa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalleventa` (
  `idDetalleVenta` int NOT NULL AUTO_INCREMENT,
  `idVenta` int NOT NULL,
  `idProducto` int NOT NULL,
  `cantidad` int NOT NULL,
  `sub_total` decimal(10,2) NOT NULL,
  PRIMARY KEY (`idDetalleVenta`),
  KEY `fk_detalleventa_venta` (`idVenta`),
  KEY `fk_detalleventa_producto` (`idProducto`),
  CONSTRAINT `fk_detalleventa_producto` FOREIGN KEY (`idProducto`) REFERENCES `producto` (`idProducto`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_detalleventa_venta` FOREIGN KEY (`idVenta`) REFERENCES `venta` (`idVenta`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalleventa`
--

LOCK TABLES `detalleventa` WRITE;
/*!40000 ALTER TABLE `detalleventa` DISABLE KEYS */;
INSERT INTO `detalleventa` VALUES (51,1,1,2,300.00),(52,1,7,7,280.00),(53,2,4,3,300.00),(54,2,1,1,150.00),(55,3,2,4,800.00),(56,4,9,1,350.00),(57,5,3,3,540.00),(58,5,5,2,260.00),(59,5,8,4,120.00),(60,21,10,1,280.00),(61,22,13,1,85.00),(62,23,1,1,100.00);
/*!40000 ALTER TABLE `detalleventa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pago`
--

DROP TABLE IF EXISTS `pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pago` (
  `idPago` int NOT NULL AUTO_INCREMENT,
  `id_pedido` int NOT NULL,
  `monto_pagado` decimal(10,2) NOT NULL,
  `fecha_pago` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `metodo_pago` varchar(45) NOT NULL,
  `descripcion` varchar(45) DEFAULT NULL,
  `id_venta` int DEFAULT NULL,
  PRIMARY KEY (`idPago`),
  KEY `fk_pago_pedido` (`id_pedido`),
  KEY `fk_pago_venta` (`id_venta`),
  CONSTRAINT `fk_pago_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`idPedido`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_pago_venta` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`idVenta`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pago`
--

LOCK TABLES `pago` WRITE;
/*!40000 ALTER TABLE `pago` DISABLE KEYS */;
INSERT INTO `pago` VALUES (1,1,1000.00,'2025-01-20 17:00:00','Transferencia','Anticipo 40%',NULL),(2,3,3200.00,'2025-01-10 21:00:00','Efectivo','Pago completo',NULL),(3,4,750.00,'2025-01-22 18:00:00','Tarjeta','Anticipo 50%',NULL),(4,1,1000.00,'2025-01-20 17:00:00','Transferencia','Anticipo 40%',NULL),(5,3,3200.00,'2025-01-10 21:00:00','Efectivo','Pago completo',NULL),(6,4,750.00,'2025-01-22 18:00:00','Tarjeta','Anticipo 50%',NULL),(7,1,1400.00,'2025-10-22 18:43:53','Efectivo','Pago inicial 50%',NULL),(8,1,1400.00,'2025-10-29 18:43:53','Transferencia','Pago final',1),(9,2,2625.00,'2025-10-22 18:43:53','Tarjeta','Anticipo 50%',2),(10,3,1800.00,'2025-10-19 18:43:53','Efectivo','Pago completo',3),(11,5,1400.00,'2025-10-22 18:43:53','Efectivo','Primer pago',5),(12,11,1500.00,'2025-10-23 17:58:57','Efectivo','ultimo pago',NULL),(13,23,20.00,'2025-10-23 18:04:14','Efectivo','sin descripcion',NULL),(14,23,30.00,'2025-10-23 18:05:09','Efectivo','ultimo pago',NULL),(15,24,50.00,'2025-10-23 18:32:29','Efectivo','Primer pago',NULL),(16,25,50.00,'2025-10-23 18:56:31','Efectivo','Primer pago',NULL);
/*!40000 ALTER TABLE `pago` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedido`
--

DROP TABLE IF EXISTS `pedido`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido` (
  `idPedido` int NOT NULL AUTO_INCREMENT,
  `fechaPedido` timestamp NULL DEFAULT NULL,
  `estado_pedido` varchar(50) DEFAULT 'Pendiente',
  `costo_total_pedido` decimal(10,2) NOT NULL,
  `idUsuario` int NOT NULL,
  `fecha_entrega` datetime DEFAULT NULL,
  `monto_pagado` decimal(10,2) DEFAULT '0.00',
  `estado_pago` varchar(45) DEFAULT 'Pendiente',
  PRIMARY KEY (`idPedido`),
  KEY `fk_pedido_usuario` (`idUsuario`),
  CONSTRAINT `fk_pedido_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuario` (`idUsuario`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido`
--

LOCK TABLES `pedido` WRITE;
/*!40000 ALTER TABLE `pedido` DISABLE KEYS */;
INSERT INTO `pedido` VALUES (1,'2025-01-20 16:00:00','En Proceso',2500.00,1,'2025-02-01 00:00:00',1000.00,'Parcial'),(2,'2025-01-21 20:30:00','Pendiente',1800.00,2,'2025-02-05 00:00:00',0.00,'Pendiente'),(3,'2025-01-10 15:00:00','Completado',3200.00,3,'2025-01-25 00:00:00',3200.00,'Pagado'),(4,'2025-01-22 17:15:00','En Proceso',1500.00,4,'2025-02-10 00:00:00',750.00,'Parcial'),(5,'2025-01-23 22:00:00','Pendiente',4200.00,5,'2025-02-15 00:00:00',0.00,'Pendiente'),(6,'2025-01-20 16:00:00','En Proceso',2500.00,1,'2025-02-01 00:00:00',1000.00,'Parcial'),(7,'2025-01-21 20:30:00','Pendiente',1800.00,2,'2025-02-05 00:00:00',0.00,'Pendiente'),(8,'2025-01-10 15:00:00','Completado',3200.00,3,'2025-01-25 00:00:00',3200.00,'Pagado'),(9,'2025-01-22 17:15:00','En Proceso',1500.00,4,'2025-02-10 00:00:00',750.00,'Parcial'),(10,'2025-01-23 22:00:00','Pendiente',4200.00,5,'2025-02-15 00:00:00',0.00,'Pendiente'),(11,'2025-01-20 16:00:00','Completado',2500.00,1,'2025-02-01 00:00:00',2500.00,'Pagado'),(12,'2025-01-21 20:30:00','Pendiente',1800.00,2,'2025-02-05 00:00:00',0.00,'Pendiente'),(13,'2025-01-10 15:00:00','Completado',3200.00,3,'2025-01-25 00:00:00',3200.00,'Pagado'),(14,'2025-01-22 17:15:00','En Proceso',1500.00,4,'2025-02-10 00:00:00',750.00,'Parcial'),(15,'2025-01-23 22:00:00','Pendiente',4200.00,5,'2025-02-15 00:00:00',0.00,'Pendiente'),(16,'2025-10-22 18:43:18','Completado',2800.00,1,'2025-11-06 12:43:18',2800.00,'Pagado'),(17,'2025-10-22 18:43:18','Cancelado',5250.00,2,'2025-11-11 12:43:18',2625.00,'Parcial'),(18,'2025-10-17 18:43:18','Completado',1800.00,3,'2025-11-01 12:43:18',1800.00,'Pagado'),(19,'2025-10-19 18:43:18','Pendiente',3500.00,4,'2025-11-16 12:43:18',0.00,'Pendiente'),(20,'2025-10-22 18:43:18','En Proceso',4200.00,5,'2025-11-09 12:43:18',1400.00,'Parcial'),(23,'2025-10-23 18:04:14','Completado',50.00,8,'2025-10-23 00:00:00',50.00,'Pagado'),(24,'2025-10-23 18:32:29','Pendiente',100.00,8,'2025-10-25 00:00:00',50.00,'Parcial'),(25,'2025-10-23 18:56:31','Pendiente',180.00,9,'2025-10-31 00:00:00',50.00,'Parcial');
/*!40000 ALTER TABLE `pedido` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producto`
--

DROP TABLE IF EXISTS `producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `producto` (
  `idProducto` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `descripcion` varchar(100) DEFAULT NULL,
  `idCategoria` int NOT NULL,
  `stock` int DEFAULT '0',
  `precio_unitario` decimal(10,2) NOT NULL,
  `esActivo` bit(1) DEFAULT NULL,
  `fechaRegistro` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idProducto`),
  KEY `fk_producto_categoria` (`idCategoria`),
  CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`idCategoria`) REFERENCES `categoria` (`idCategoria`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto`
--

LOCK TABLES `producto` WRITE;
/*!40000 ALTER TABLE `producto` DISABLE KEYS */;
INSERT INTO `producto` VALUES (1,'Camiseta Deportiva Básica','Nike','Camiseta de poliéster transpirable, ideal para entrenamientos',1,28,100.00,_binary '','2025-10-22 12:19:37'),(2,'Camiseta Personalizada','Adidas','Camiseta con personalización de nombre y número',1,29,180.00,_binary '','2025-10-22 12:19:37'),(3,'Uniforme Fútbol Completo','Nike','Set: camiseta + short + medias',2,20,400.00,_binary '','2025-10-22 12:19:37'),(4,'Uniforme Baloncesto Completo','Adidas','Set: camiseta + short',2,15,380.00,_binary '','2025-10-22 12:19:37'),(5,'Uniforme Escolar Deportivo','Genérico','Uniforme para educación física escolar',2,30,350.00,_binary '','2025-10-22 12:19:37'),(6,'Medias Deportivas Largas','Nike','Par de medias largas deportivas',3,100,35.00,_binary '','2025-10-22 12:19:37'),(7,'Muñequeras Par','Nike','Par de muñequeras deportivas',3,60,40.00,_binary '','2025-10-22 12:19:37'),(8,'Rodilleras Par','Mizuno','Par de rodilleras protectoras',3,45,75.00,_binary '','2025-10-22 12:19:37'),(9,'Banda de Capitán','Genérico','Banda elástica de capitán con colores',3,49,50.00,_binary '','2025-10-22 12:19:37'),(10,'Guantes Portero','Adidas','Guantes profesionales para portero',3,21,280.00,_binary '','2025-10-22 12:19:37'),(11,'Espinilleras Par','Nike','Par de espinilleras con correas',3,55,95.00,_binary '','2025-10-22 12:19:37'),(12,'Bolsa Deportiva','Puma','Bolsa para transportar equipamiento',3,38,150.00,_binary '','2025-10-22 12:19:37'),(13,'Gorra Deportiva','Under Armour','Gorra con protección UV',3,44,85.00,_binary '','2025-10-22 12:19:37'),(14,'Toalla Microfibra','Genérico','Toalla deportiva de secado rápido',3,70,55.00,_binary '','2025-10-22 12:19:37'),(15,'Camisola Deportiva Básica','Nike','Camisola de poliéster transpirable',1,100,150.00,_binary '','2025-10-22 12:43:07'),(16,'Camisola Premium','Adidas','Camisola con tecnología Climacool',1,75,200.00,_binary '','2025-10-22 12:43:07'),(17,'Camisola Personalizada','Puma','Camisola con opción de sublimación',1,50,180.00,_binary '','2025-10-22 12:43:07'),(18,'Short Deportivo Clásico','Nike','Short con bolsillos laterales',2,120,100.00,_binary '\0','2025-10-22 12:43:07'),(19,'Short Portero','Puma','Short acolchado para portero',2,40,150.00,_binary '','2025-10-22 12:43:07'),(20,'Medias Largas','Nike','Medias hasta la rodilla',3,200,40.00,_binary '','2025-10-22 12:43:07'),(21,'Medias Cortas','Adidas','Medias deportivas tobilleras',3,150,35.00,_binary '','2025-10-22 12:43:07'),(22,'Set Completo Fútbol','Nike','Camisola, short y medias',1,60,350.00,_binary '','2025-10-22 12:43:07'),(23,'Set Completo Basketball','Adidas','Camisola y short de basketball',1,45,320.00,_binary '\0','2025-10-22 12:43:07'),(24,'Ejemplo','ejemplo','dsfdf',5,20,12.00,_binary '','2025-10-22 23:10:46');
/*!40000 ALTER TABLE `producto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rol`
--

DROP TABLE IF EXISTS `rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rol` (
  `idRol` int NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(50) NOT NULL,
  `esActivo` bit(1) DEFAULT NULL,
  `fechaRegistro` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idRol`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rol`
--

LOCK TABLES `rol` WRITE;
/*!40000 ALTER TABLE `rol` DISABLE KEYS */;
INSERT INTO `rol` VALUES (1,'Administrador',_binary '','2025-10-22 12:18:45'),(2,'Vendedor',_binary '','2025-10-22 12:18:45'),(3,'Cliente',_binary '','2025-10-22 12:18:45');
/*!40000 ALTER TABLE `rol` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario` (
  `idUsuario` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(40) NOT NULL,
  `correo` varchar(40) NOT NULL,
  `idRol` int NOT NULL,
  `contrasenia` varchar(40) NOT NULL,
  `esActivo` bit(1) DEFAULT NULL,
  `fechaRegistro` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idUsuario`),
  UNIQUE KEY `correo` (`correo`),
  KEY `fk_usuario_rol` (`idRol`),
  CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`idRol`) REFERENCES `rol` (`idRol`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (1,'Administrador Sistema','admin@tienda.com',1,'0192023a7bbd73250516f069df18b500',_binary '','2025-10-22 12:19:18'),(2,'Juan Vendedor','vendedor@tienda.com',2,'a60c36fc7c825e68bb5371a0e08f828a',_binary '\0','2025-10-22 12:19:18'),(3,'María Cliente','maria@ejemplo.com',3,'7159bbe0c8ca2a67230a26b72dea7557',_binary '\0','2025-10-22 12:19:18'),(4,'Luis Pérez','luis@ejemplo.com',2,'a60c36fc7c825e68bb5371a0e08f828a',_binary '\0','2025-10-22 12:19:18'),(5,'Juan Pérez','juan.perez@uniformes.com',1,'admin123',_binary '','2025-10-22 12:42:37'),(6,'María García','maria.garcia@uniformes.com',2,'vendedor123',_binary '','2025-10-22 12:42:37'),(7,'Carlos López','carlos.lopez@uniformes.com',2,'vendedor456',_binary '','2025-10-22 12:42:37'),(8,'Ana Martínez','ana.martinez@uniformes.com',3,'supervisor123',_binary '','2025-10-22 12:42:37'),(9,'prueba','prueba@gmail.com',3,'5bc8c567a89112d5f408a8af4f17970d',_binary '','2025-10-22 23:36:21'),(10,'Nuevo','nuevo@gmail.com',2,'e10adc3949ba59abbe56e057f20f883e',_binary '','2025-10-22 23:48:32');
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venta`
--

DROP TABLE IF EXISTS `venta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `venta` (
  `idVenta` int NOT NULL AUTO_INCREMENT,
  `fechaRegistro` datetime DEFAULT CURRENT_TIMESTAMP,
  `idUsuarioVendedor` int NOT NULL,
  `idUsuarioCliente` int NOT NULL,
  `Total` decimal(10,2) NOT NULL,
  `impuestoTotal` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`idVenta`),
  KEY `fk_venta_vendedor` (`idUsuarioVendedor`),
  KEY `fk_venta_cliente` (`idUsuarioCliente`),
  CONSTRAINT `fk_venta_cliente` FOREIGN KEY (`idUsuarioCliente`) REFERENCES `usuario` (`idUsuario`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_venta_vendedor` FOREIGN KEY (`idUsuarioVendedor`) REFERENCES `usuario` (`idUsuario`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venta`
--

LOCK TABLES `venta` WRITE;
/*!40000 ALTER TABLE `venta` DISABLE KEYS */;
INSERT INTO `venta` VALUES (1,'2025-01-15 10:30:00',2,1,525.00,63.00),(2,'2025-01-16 14:20:00',2,2,780.00,93.60),(3,'2025-01-17 09:15:00',4,3,400.00,48.00),(4,'2025-01-18 16:45:00',2,4,950.00,114.00),(5,'2025-01-19 11:00:00',4,5,310.00,37.20),(6,'2025-01-15 10:30:00',2,1,525.00,63.00),(7,'2025-01-16 14:20:00',2,2,780.00,93.60),(8,'2025-01-17 09:15:00',4,3,400.00,48.00),(9,'2025-01-18 16:45:00',2,4,950.00,114.00),(10,'2025-01-19 11:00:00',4,5,310.00,37.20),(11,'2025-10-22 12:43:33',2,1,580.00,69.60),(12,'2025-10-22 12:43:33',2,2,450.00,54.00),(13,'2025-10-20 12:43:33',3,3,800.00,96.00),(14,'2025-10-21 12:43:33',2,4,350.00,42.00),(15,'2025-10-22 12:43:33',3,5,920.00,110.40),(16,'2025-10-22 12:43:39',2,1,580.00,69.60),(17,'2025-10-22 12:43:39',2,2,450.00,54.00),(18,'2025-10-20 12:43:39',3,3,800.00,96.00),(19,'2025-10-21 12:43:39',2,4,350.00,42.00),(20,'2025-10-22 12:43:39',3,5,920.00,110.40),(21,'2025-10-23 02:08:15',1,7,313.60,33.60),(22,'2025-10-23 08:58:22',1,10,95.20,10.20),(23,'2025-10-23 10:35:15',10,9,112.00,12.00);
/*!40000 ALTER TABLE `venta` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-23 15:50:03
