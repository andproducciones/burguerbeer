-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 20-07-2025 a las 05:29:40
-- Versión del servidor: 9.1.0
-- Versión de PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `burguerbeer`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado_habitacion`
--

DROP TABLE IF EXISTS `estado_habitacion`;
CREATE TABLE IF NOT EXISTS `estado_habitacion` (
  `id_estado` int NOT NULL AUTO_INCREMENT,
  `idhabitacion` int DEFAULT NULL,
  `estado` enum('disponible','ocupada','limpieza','mantenimiento') COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_estado` datetime DEFAULT CURRENT_TIMESTAMP,
  `observacion` text COLLATE utf8mb4_general_ci,
  `usuario_id` int DEFAULT NULL,
  PRIMARY KEY (`id_estado`),
  KEY `idhabitacion` (`idhabitacion`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `habitaciones`
--

DROP TABLE IF EXISTS `habitaciones`;
CREATE TABLE IF NOT EXISTS `habitaciones` (
  `idhabitacion` int NOT NULL AUTO_INCREMENT,
  `numero` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_general_ci,
  `id_tipo` int NOT NULL,
  `capacidad` int DEFAULT '2',
  `precio` decimal(10,2) NOT NULL,
  `estado` enum('disponible','ocupada','limpieza','mantenimiento') COLLATE utf8mb4_general_ci DEFAULT 'disponible',
  `piso` int DEFAULT '1',
  `habilitada` tinyint(1) DEFAULT '1',
  `tipo_tarifa` enum('por_habitacion','por_persona') COLLATE utf8mb4_general_ci DEFAULT 'por_habitacion',
  `precio_base` decimal(10,2) DEFAULT '0.00',
  `max_personas` int DEFAULT '2',
  PRIMARY KEY (`idhabitacion`),
  UNIQUE KEY `numero` (`numero`),
  KEY `id_tipo` (`id_tipo`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lugares_tour`
--

DROP TABLE IF EXISTS `lugares_tour`;
CREATE TABLE IF NOT EXISTS `lugares_tour` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_reserva`
--

DROP TABLE IF EXISTS `pagos_reserva`;
CREATE TABLE IF NOT EXISTS `pagos_reserva` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idreserva` int NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `referencia` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas`
--

DROP TABLE IF EXISTS `reservas`;
CREATE TABLE IF NOT EXISTS `reservas` (
  `idreserva` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int NOT NULL,
  `fecha_entrada` date NOT NULL,
  `fecha_salida` date NOT NULL,
  `total` decimal(10,2) DEFAULT '0.00',
  `abono` decimal(10,2) DEFAULT '0.00',
  `saldo` decimal(10,2) GENERATED ALWAYS AS ((`total` - `abono`)) STORED,
  `estado_pago` enum('pendiente','parcial','pagado') COLLATE utf8mb4_general_ci DEFAULT 'pendiente',
  `estado` enum('pendiente','confirmada','checkin','checkout','cancelada') COLLATE utf8mb4_general_ci DEFAULT 'pendiente',
  `facturada` tinyint(1) DEFAULT '0',
  `fecha_factura` datetime DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_general_ci,
  `canal_reserva` enum('recepción','web','teléfono','agencia') COLLATE utf8mb4_general_ci DEFAULT 'recepción',
  `usuario_id` int DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `hora_checkin` datetime DEFAULT NULL,
  `usuario_checkin` int DEFAULT NULL,
  `hora_checkout` datetime DEFAULT NULL,
  `usuario_checkout` int DEFAULT NULL,
  PRIMARY KEY (`idreserva`),
  KEY `fk_reservas_cliente` (`id_cliente`),
  KEY `fk_reservas_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas_detalle`
--

DROP TABLE IF EXISTS `reservas_detalle`;
CREATE TABLE IF NOT EXISTS `reservas_detalle` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idreserva` int NOT NULL,
  `id_habitacion` int NOT NULL,
  `adultos` int DEFAULT '0',
  `ninos` int DEFAULT '0',
  `incluye_desayuno` tinyint(1) DEFAULT '0',
  `incluye_tour` tinyint(1) DEFAULT '0',
  `lugar_tour` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `precio_nino` decimal(10,2) NOT NULL,
  `precio_desayuno` decimal(10,2) DEFAULT '0.00',
  `precio_tour` decimal(10,2) DEFAULT '0.00',
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_detalle_reserva` (`idreserva`),
  KEY `fk_detalle_habitacion` (`id_habitacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas_pagos`
--

DROP TABLE IF EXISTS `reservas_pagos`;
CREATE TABLE IF NOT EXISTS `reservas_pagos` (
  `idpago` int NOT NULL AUTO_INCREMENT,
  `idreserva` int NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `referencia_pago` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `comprobante_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `origen_pago` enum('recepción','web','agente') COLLATE utf8mb4_general_ci DEFAULT 'recepción',
  `observacion` text COLLATE utf8mb4_general_ci,
  `fecha_pago` datetime DEFAULT CURRENT_TIMESTAMP,
  `usuario_id` int DEFAULT NULL,
  PRIMARY KEY (`idpago`),
  KEY `fk_pago_reserva` (`idreserva`),
  KEY `fk_pago_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tarifas_habitaciones`
--

DROP TABLE IF EXISTS `tarifas_habitaciones`;
CREATE TABLE IF NOT EXISTS `tarifas_habitaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_general_ci,
  `precio_por_persona` decimal(10,2) NOT NULL,
  `habilitada` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tarifa_extras`
--

DROP TABLE IF EXISTS `tarifa_extras`;
CREATE TABLE IF NOT EXISTS `tarifa_extras` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo_extra` enum('desayuno','tour') COLLATE utf8mb4_general_ci NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `habilitado` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_habitacion`
--

DROP TABLE IF EXISTS `tipo_habitacion`;
CREATE TABLE IF NOT EXISTS `tipo_habitacion` (
  `id_tipo` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id_tipo`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


BEGIN

    DECLARE usuarios INT;
    DECLARE clientes INT;
    DECLARE productos INT;
    DECLARE ventas INT;

    -- Conteo de registros activos
    SELECT COUNT(*) INTO usuarios FROM usuario WHERE estatus != 10;
    SELECT COUNT(*) INTO clientes FROM clientes WHERE estatus != 10;
    SELECT COUNT(*) INTO productos FROM producto WHERE estatus != 10;
    SELECT COUNT(*) INTO ventas FROM factura WHERE fecha > CURDATE() AND estatus != 10;

    -- Primer resultado: Totales
    SELECT usuarios AS total_usuarios, 
           clientes AS total_clientes, 
           productos AS total_productos, 
           ventas AS ventas_hoy;

    -- Segundo resultado: Ventas y salarios últimos 10 arqueos
    SELECT 
        DATE(fecha_fin) AS fecha,
        SUM(monto_final) AS total_ventas,
        SUM(salarios) AS total_salarios
    FROM arqueo_caja
    GROUP BY DATE(fecha_fin)
    ORDER BY fecha DESC
    LIMIT 10;

    -- Tercer resultado: 10 productos más vendidos
    SELECT 
        p.producto AS nombre_producto,
        SUM(df.cantidad) AS total_vendidos
    FROM 
        detalle_factura df
    INNER JOIN 
        producto p ON df.codproducto = p.codproducto
    WHERE 
        df.estatus_dt = 1 
        AND p.categoria NOT IN (20, 21, 22, 24, 25, 26, 27)
    GROUP BY 
        df.codproducto, p.producto
    ORDER BY 
        total_vendidos DESC
    LIMIT 10;

    -- Cuarto resultado: 20 productos menos vendidos (incluyendo sin ventas)
    SELECT 
        p.producto AS nombre_producto,
        COALESCE(SUM(df.cantidad), 0) AS total_vendidos
    FROM 
        producto p
    LEFT JOIN 
        detalle_factura df ON df.codproducto = p.codproducto AND df.estatus_dt = 1
    WHERE 
        p.categoria NOT IN (20, 21, 22, 24, 25, 26, 27)
    GROUP BY 
        p.codproducto, p.producto
    ORDER BY 
        total_vendidos ASC
    LIMIT 20;

END