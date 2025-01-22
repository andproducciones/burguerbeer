-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 22-01-2025 a las 03:43:56
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

DELIMITER $$
--
-- Procedimientos
--
DROP PROCEDURE IF EXISTS `actualizar_precio_producto`$$
CREATE DEFINER=`u338188990_francis2`@`127.0.0.1` PROCEDURE `actualizar_precio_producto` (IN `n_cantidad` INT, IN `n_precio` DECIMAL(10,2), IN `codigo` INT)   BEGIN
    	DECLARE nueva_existencia int;
        DECLARE nuevo_total  decimal(10,2);
        DECLARE nuevo_precio decimal(10,2);
        
        DECLARE cant_actual int;
        DECLARE pre_actual decimal(10,2);
        
        DECLARE actual_existencia int;
        DECLARE actual_precio decimal(10,2);
                
        SELECT precio,existencia INTO actual_precio,actual_existencia FROM producto WHERE codproducto = codigo;
        
        SET nueva_existencia = actual_existencia + n_cantidad;
        SET nuevo_total = (actual_existencia * actual_precio) + (n_cantidad * n_precio);
        SET nuevo_precio = nuevo_total / nueva_existencia;
        
        UPDATE producto SET existencia = nueva_existencia, precio = nuevo_precio WHERE codproducto = codigo;
        
        SELECT nueva_existencia,nuevo_precio;
        
    END$$

DROP PROCEDURE IF EXISTS `add_detalle_compra`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_detalle_compra` (IN `codigo` INT, IN `cantidad` INT, IN `fecha` DATE, IN `comedor` INT, IN `token_user` VARCHAR(50) CHARSET utf8mb4)   BEGIN
	DECLARE precio_actual decimal (10,2);
	IF comedor = 1  THEN
	SELECT precio_oficiales INTO precio_actual FROM producto WHERE codproducto = codigo;
	END IF;
	IF comedor = 2 THEN
	SELECT precio_aerotecnicos INTO precio_actual FROM producto WHERE codproducto = codigo;
	END IF;
    IF comedor = 3 THEN
	SELECT precio_dual INTO precio_actual FROM producto WHERE codproducto = codigo;
	END IF;
	INSERT INTO detalle_temp(token_user,codproducto,cantidad,precio_venta,comedor,fecha) VALUES(token_user,codigo,cantidad,precio_actual,comedor,fecha);
	SELECT tmp.correlativo, tmp.codproducto, p.producto, tmp.cantidad, tmp.precio_venta, tmp.fecha, tmp.comedor, c.comedor as comedor1 FROM detalle_temp tmp
	INNER JOIN 	producto p
	ON tmp.codproducto = p.codproducto
    INNER JOIN tipo_comedor c 
    ON tmp.comedor = c.id
	WHERE tmp.token_user= token_user;
    END$$

DROP PROCEDURE IF EXISTS `add_detalle_temp`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_detalle_temp` (IN `codigo` INT, IN `cantidad` INT, IN `token_user` VARCHAR(50), IN `mesa` INT, IN `si` INT)   BEGIN
	DECLARE precio_actual decimal (10,2);
    DECLARE codatributos2 VARCHAR (50);

	SELECT precio INTO precio_actual FROM producto WHERE codproducto = codigo;
    SELECT codatributos INTO codatributos2 FROM producto WHERE codproducto = codigo;
  
  INSERT INTO detalle_temp(token_user,codproducto,cantidad,precio_venta,mesa,codatributos,estatus_atributos) 		VALUES(token_user,codigo,cantidad,precio_actual,mesa,codatributos2,si);
     
    SELECT tmp.correlativo, tmp.codproducto, p.producto, tmp.cantidad, tmp.precio_venta, tmp.mesa, m.numero, tmp.preparar, tmp.estatus_atributos,tmp.observaciones FROM detalle_temp tmp
	INNER JOIN 	producto p ON tmp.codproducto = p.codproducto
    INNER JOIN 	mesas m ON tmp.mesa = m.id
	WHERE tmp.token_user= token_user AND tmp.mesa = mesa ORDER BY tmp.correlativo DESC;
END$$

DROP PROCEDURE IF EXISTS `anular_factura`$$
CREATE DEFINER=`u338188990_francis2`@`127.0.0.1` PROCEDURE `anular_factura` (IN `no_factura` INT)   BEGIN
   		DECLARE existe_factura int;
		DECLARE	registros int;
		DECLARE a int;
    
    	DECLARE cod_producto int;
		DECLARE cant_producto int;
        DECLARE existencia_actual int; 
		DECLARE nueva_existencia int;
		
	
	SET existe_factura = (SELECT COUNT(*) FROM factura WHERE nofactura = no_factura and estatus = 1);

	IF existe_factura > 0 THEN
	
	CREATE TEMPORARY TABLE tbl_tmp(
        id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
		cod_prod BIGINT,
		cant_prod INT);
	
	SET a = 1;
	SET registros = (SELECT COUNT(*) FROM detalle_factura WHERE nofactura = no_factura);
	
	IF registros > 0 THEN
	INSERT INTO tbl_tmp(cod_prod,cant_prod) SELECT codproducto,cantidad FROM detalle_factura WHERE nofactura = no_factura;
	
	WHILE a <= registros DO
		SELECT cod_prod,cant_prod INTO cod_producto,cant_producto FROM tbl_tmp WHERE id = a;	
		SELECT existencia INTO existencia_actual FROM producto WHERE codproducto = cod_producto;
		SET nueva_existencia = existencia_actual + cant_producto;
		UPDATE producto SET existencia = nueva_existencia WHERE codproducto = cod_producto;
	
		SET a = a + 1;	
	
	END WHILE;

	UPDATE factura SET estatus = 2 WHERE nofactura = no_factura;
	DROP TABLE tbl_tmp;
	SELECT * FROM factura WHERE nofactura = no_factura;

	END IF;
	
	ELSE

	SELECT 0 factura;
	 
	END IF;
END$$

DROP PROCEDURE IF EXISTS `dataDashboard`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `dataDashboard` ()   BEGIN
	
    DECLARE usuarios int;
    DECLARE clientes int;
    DECLARE productos int;
    DECLARE ventas int;
	
    SELECT COUNT(*) INTO usuarios FROM usuario WHERE estatus !=10;
    SELECT COUNT(*) INTO clientes FROM clientes WHERE estatus !=10;
    SELECT COUNT(*) INTO productos FROM producto WHERE estatus !=10;
    SELECT COUNT(*) INTO ventas FROM factura WHERE fecha > CURDATE() AND estatus !=10;

    
    SELECT usuarios,clientes,productos,ventas;
END$$

DROP PROCEDURE IF EXISTS `dataPanelControl`$$
CREATE DEFINER=`u338188990_francis2`@`127.0.0.1` PROCEDURE `dataPanelControl` (IN `fecha` DATE)   BEGIN
	

    DECLARE ventas int;
    DECLARE recargas int;
    DECLARE dinero decimal(10,2);
    DECLARE dineroO decimal(10,2);
    DECLARE dineroA decimal(10,2);

    SELECT COUNT(*) INTO ventas FROM factura WHERE fecha = fecha AND estatus !=10;
    SELECT COUNT(*) INTO recargas FROM factura_credito WHERE fecha = fecha AND estatus !=10;
    SELECT SUM(credito) INTO dinero FROM clientes WHERE estatus = 2;
    SELECT SUM(credito) INTO dineroO FROM clientes WHERE tipo_user = 1 AND estatus = 2;
    SELECT SUM(credito) INTO dineroA FROM clientes WHERE tipo_user = 2 AND estatus = 2;
    
    SELECT ventas,recargas, dinero, dineroO, dineroA;
END$$

DROP PROCEDURE IF EXISTS `del_credito_detalle`$$
CREATE DEFINER=`u338188990_francis2`@`127.0.0.1` PROCEDURE `del_credito_detalle` (IN `id_detalle` INT(10), IN `token` VARCHAR(100) CHARSET utf8mb4)  NO SQL BEGIN
        DELETE FROM detalle_temp_credito WHERE correlativo = id_detalle; 
        SELECT correlativo,cantidad_credito FROM detalle_temp_credito
       	WHERE token_user = token;
    END$$

DROP PROCEDURE IF EXISTS `del_detalle_temp`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `del_detalle_temp` (IN `id_detalle` INT, IN `token` VARCHAR(50), IN `mesa` INT)   BEGIN
        DELETE FROM detalle_temp WHERE correlativo = id_detalle; 
        SELECT tmp.correlativo,tmp.codproducto,p.producto,tmp.cantidad,tmp.precio_venta, tmp.mesa,m.numero, tmp.preparar, tmp.estatus_atributos,tmp.observaciones 			FROM detalle_temp tmp
        INNER JOIN producto p ON tmp.codproducto = p.codproducto
        INNER JOIN mesas m ON tmp.mesa = m.id
        WHERE tmp.token_user = token AND tmp.mesa = mesa ORDER BY tmp.correlativo DESC;
    END$$

DROP PROCEDURE IF EXISTS `procesar_factura_seleccionada`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `procesar_factura_seleccionada` (IN `mesa_id` INT, IN `productosSeleccionados` TEXT, IN `token` VARCHAR(100), IN `cod_usuario` BIGINT, IN `cod_cliente` BIGINT, IN `id_cupon` INT, IN `pago` INT, IN `codigopago` VARCHAR(20), IN `caja` INT)   BEGIN
    DECLARE no_factura INT;
    DECLARE registros INT;
    DECLARE subtotal DECIMAL(10,2);
    DECLARE total DECIMAL(10,2);
    DECLARE nueva_existencia INT;
    DECLARE existencia_actual INT;  
    DECLARE tmp_cod_producto BIGINT;
    DECLARE tmp_cant_producto INT;
    DECLARE tmp_observaciones TEXT;
    DECLARE tipo_cupon INT DEFAULT 0;
    DECLARE dinero_1 DECIMAL(10,2) DEFAULT 0;
    DECLARE porcentaje_1 INT DEFAULT 0;
    DECLARE descuento_1 DECIMAL(10,2) DEFAULT 0;
    DECLARE codigo_cupon VARCHAR(20) DEFAULT NULL;

    IF cod_usuario = 0 THEN
        SET cod_usuario = 1;
    END IF;

    CREATE TEMPORARY TABLE tbl_tmp_tokenuser (
        id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        cod_prod BIGINT,
        cant_prod INT,
        observaciones TEXT,
        mesa INT
    );

    SET registros = (SELECT COUNT(*) FROM detalle_temp WHERE FIND_IN_SET(correlativo, productosSeleccionados));      

    IF registros > 0 THEN
        INSERT INTO tbl_tmp_tokenuser (cod_prod, cant_prod, observaciones)
        SELECT codproducto, cantidad, observaciones FROM detalle_temp WHERE FIND_IN_SET(correlativo, productosSeleccionados);

        IF id_cupon > 0 THEN
            SELECT codigo INTO codigo_cupon FROM codigos_promocionales WHERE id = id_cupon;
        END IF;

        INSERT INTO factura (usuario, codcliente, mesa, tipopago, codigopago, cupon, caja) 
        VALUES (cod_usuario, cod_cliente, mesa_id, pago, codigopago, codigo_cupon, caja);

        SET no_factura = LAST_INSERT_ID();

        INSERT INTO detalle_factura (nofactura, codproducto, cantidad, precio_venta, observaciones) 
        SELECT no_factura, codproducto, cantidad, precio_venta, observaciones 
        FROM detalle_temp WHERE FIND_IN_SET(correlativo, productosSeleccionados);

        UPDATE producto p
        JOIN tbl_tmp_tokenuser t
        ON p.codproducto = t.cod_prod
        SET p.existencia = p.existencia - t.cant_prod;

        SET subtotal = (SELECT SUM(cantidad * precio_venta) FROM detalle_temp WHERE FIND_IN_SET(correlativo, productosSeleccionados));

        IF id_cupon > 0 THEN
            SELECT tipo INTO tipo_cupon FROM codigos_promocionales WHERE id = id_cupon;

            IF tipo_cupon = 1 THEN
                SELECT dinero INTO dinero_1 FROM codigos_promocionales WHERE id = id_cupon;
                SET total = subtotal - dinero_1;
            ELSEIF tipo_cupon = 2 THEN
                SELECT porcentaje INTO porcentaje_1 FROM codigos_promocionales WHERE id = id_cupon;
                SET descuento_1 = subtotal * porcentaje_1 / 100;
                SET total = subtotal - descuento_1;
            END IF;
        ELSE
            SET total = subtotal;
        END IF;

        UPDATE factura SET totalfactura = total WHERE nofactura = no_factura;

        DELETE FROM detalle_temp WHERE FIND_IN_SET(correlativo, productosSeleccionados);
        DROP TEMPORARY TABLE IF EXISTS tbl_tmp_tokenuser;

        SELECT cod_cliente, no_factura FROM factura WHERE nofactura = no_factura;
    ELSE
        SELECT 0;
    END IF;
END$$

DROP PROCEDURE IF EXISTS `procesar_venta`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `procesar_venta` (IN `cod_usuario` BIGINT, IN `cod_cliente` BIGINT, IN `token` VARCHAR(100), IN `mesa_id` INT, IN `pago` INT, IN `codigopago` VARCHAR(20), IN `id_cupon` INT, IN `caja` INT)   BEGIN
	DECLARE no_factura INT;
	DECLARE	registros INT;
	DECLARE subtotal DECIMAL(10,2);
   DECLARE total DECIMAL(10,2);
	DECLARE nueva_existencia int;
	DECLARE existencia_actual int;  
	DECLARE tmp_cod_producto int;
	DECLARE tmp_cant_producto int;
   DECLARE tmp_observaciones TEXT;
	DECLARE precio_venta_prev DECIMAL(10,2);
    DECLARE a INT;
    DECLARE stock_actual INT;
    DECLARE tipo_cupon INT;
    DECLARE dinero_1 DECIMAL(10,2);
    DECLARE porcentaje_1 INT;
    DECLARE descuento_1 DECIMAL(10,2);
    DECLARE descuento_2 DECIMAL(10,2);
    DECLARE codigo_cupon VARCHAR(20);

    

	IF cod_usuario = 0 THEN
	SET cod_usuario = 1;
	END IF;
    
	SET a = 1;
	
	CREATE TEMPORARY TABLE tbl_tmp_tokenuser(
	id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	cod_prod BIGINT,
	cant_prod INT,
   observaciones TEXT);

	SET registros = (SELECT COUNT(*) FROM detalle_temp WHERE token_user = token AND mesa = mesa_id);      
    	
        IF registros > 0 THEN
		INSERT INTO tbl_tmp_tokenuser(cod_prod,cant_prod,observaciones) SELECT codproducto,cantidad,observaciones FROM detalle_temp WHERE token_user = token AND mesa = mesa_id;

		 SELECT codigo INTO codigo_cupon FROM codigos_promocionales WHERE id = id_cupon;
         
        INSERT INTO factura(usuario,codcliente,mesa,tipopago,codigopago,cupon,caja) VALUES(cod_usuario,cod_cliente,mesa_id,pago,codigopago,codigo_cupon,caja);
		
		SET no_factura= LAST_INSERT_ID();
				
		INSERT INTO detalle_factura(nofactura,codproducto,cantidad,precio_venta,observaciones) SELECT (no_factura) as nofactura, codproducto,cantidad,precio_venta,observaciones FROM detalle_temp WHERE token_user = token AND mesa = mesa_id;
       
		WHILE a <= registros DO
		
		SELECT cod_prod,cant_prod,observaciones INTO tmp_cod_producto,tmp_cant_producto,tmp_observaciones FROM tbl_tmp_tokenuser WHERE id = a;	
		
        SELECT existencia INTO existencia_actual FROM producto WHERE codproducto = tmp_cod_producto;
		
		SET nueva_existencia = existencia_actual - tmp_cant_producto;
		UPDATE producto SET existencia = nueva_existencia WHERE codproducto = tmp_cod_producto;
		
		SET a = a+1;
		
		END WHILE;
                
        SET subtotal = (SELECT SUM(cantidad * precio_venta) FROM detalle_temp WHERE token_user = token AND mesa = mesa_id);
     
        IF id_cupon <> 0 THEN
        
        SELECT tipo INTO tipo_cupon FROM codigos_promocionales WHERE id = id_cupon;
        
           IF tipo_cupon = 1 THEN
           SELECT dinero INTO dinero_1 FROM codigos_promocionales WHERE id = id_cupon;
           SET total = subtotal - dinero_1;
           END IF;
        
           IF tipo_cupon = 2 THEN
           SELECT porcentaje INTO porcentaje_1 FROM codigos_promocionales WHERE id = id_cupon;
           SET descuento_1 = subtotal * porcentaje_1;
           SET descuento_2 = descuento_1/100;
           SET total = subtotal - descuento_2;
           END IF;
 
        ELSE
        
        SET total = subtotal;
        
        END IF;
        
        UPDATE factura SET totalfactura = total WHERE nofactura = no_factura;
		
        DELETE FROM detalle_temp WHERE token_user = token AND mesa = mesa_id;
		TRUNCATE TABLE  tbl_tmp_tokenuser;
		SELECT cod_cliente,no_factura FROM factura WHERE nofactura = no_factura;
		
		ELSE

			SELECT 0;

		END IF;


    END$$

DROP PROCEDURE IF EXISTS `procesar_venta_credito`$$
CREATE DEFINER=`u338188990_francis2`@`127.0.0.1` PROCEDURE `procesar_venta_credito` (IN `cod_usuario` BIGINT(20), IN `cod_cliente` BIGINT(20), IN `token` VARCHAR(50) CHARSET utf8mb4)   BEGIN
	DECLARE factura INT;
	DECLARE	registros INT;
	DECLARE nueva_existencia decimal(10,2);
	DECLARE existencia_actual decimal(10,2);  
	DECLARE tmp_recargar_credito decimal(10,2);
	DECLARE a INT;
	SET a = 1;
	
	CREATE TEMPORARY TABLE tbl_tmp_tokenuser(
	id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	valor_recargar DECIMAL(10,2));

	SET registros = (SELECT COUNT(*) FROM detalle_temp_credito WHERE token_user = token); 
	
	IF registros > 0 THEN 
		
		INSERT INTO tbl_tmp_tokenuser(valor_recargar) SELECT cantidad_credito FROM detalle_temp_credito WHERE token_user = token;

		INSERT INTO factura_credito(usuario,codcliente) VALUES(cod_usuario,cod_cliente);
		
		SET factura= LAST_INSERT_ID();
	
		INSERT INTO detalle_credito(nofactura,precio_venta) SELECT (factura) as nofactura, cantidad_credito FROM detalle_temp_credito WHERE token_user = token;
		
		WHILE a <= registros DO
		
		SELECT valor_recargar INTO tmp_recargar_credito FROM tbl_tmp_tokenuser WHERE id = a;	
		SELECT credito INTO existencia_actual FROM clientes WHERE usuario = cod_cliente;
		
		SET nueva_existencia = existencia_actual + tmp_recargar_credito;
		
		UPDATE clientes SET credito = nueva_existencia WHERE usuario = cod_cliente;
		
		SET a=a+1;
		
		END WHILE;

		
		UPDATE factura_credito SET totalfactura = tmp_recargar_credito WHERE nofactura = factura;
		DELETE FROM detalle_temp_credito WHERE token_user = token;
		TRUNCATE TABLE  tbl_tmp_tokenuser;
		
		SELECT * FROM factura_credito WHERE nofactura = factura;


		ELSE

			SELECT 0;
		END IF;
	
    END$$

DROP PROCEDURE IF EXISTS `procesar_venta_credito_menos`$$
CREATE DEFINER=`u338188990_francis2`@`127.0.0.1` PROCEDURE `procesar_venta_credito_menos` (IN `cod_usuario` INT, IN `cod_cliente` INT, IN `token` VARCHAR(50) CHARSET utf8mb4)   BEGIN
	DECLARE factura INT;
	DECLARE	registros INT;
	DECLARE total DECIMAL(10,2);
	DECLARE credito_actual DECIMAL(10,2);
	DECLARE	nuevo_credito DECIMAL(10,2);
	DECLARE nueva_existencia int;
	DECLARE existencia_actual int;  
	DECLARE tmp_cod_producto int;
	DECLARE tmp_cant_producto int;
	DECLARE a INT;
	SET a = 1;
	
	CREATE TEMPORARY TABLE tbl_tmp_tokenuser(
	id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	cod_prod BIGINT,
	cant_prod INT);

	SET registros = (SELECT COUNT(*) FROM detalle_temp WHERE token_user = token);
    
    SET credito_actual = (SELECT credito FROM cliente WHERE usuario_c = cod_cliente);
	
	IF credito_actual > 0 THEN
    
    IF registros > 0 THEN 
    
		INSERT INTO tbl_tmp_tokenuser(cod_prod,cant_prod) SELECT codproducto,cantidad FROM detalle_temp WHERE token_user = token;

		INSERT INTO factura(usuario,codcliente) VALUES(cod_usuario,cod_cliente);
		
		SET factura= LAST_INSERT_ID();
		
		
 
		INSERT INTO detalle_factura(nofactura,codproducto,cantidad,precio_venta) SELECT (factura) as nofactura, codproducto,cantidad,precio_venta FROM detalle_temp WHERE token_user = token;
		
		WHILE a <= registros DO
		SELECT cod_prod,cant_prod INTO tmp_cod_producto,tmp_cant_producto FROM tbl_tmp_tokenuser WHERE id = a;	
		SELECT existencia INTO existencia_actual FROM producto WHERE codproducto = tmp_cod_producto;
		
		SET nueva_existencia = existencia_actual - tmp_cant_producto;
		UPDATE producto SET existencia = nueva_existencia WHERE codproducto = tmp_cod_producto;
		
		SET a=a+1;
		
		END WHILE;

		SET total = (SELECT SUM(cantidad * precio_venta) FROM detalle_temp WHERE token_user = token);
		UPDATE factura SET totalfactura = total WHERE nofactura = factura;
		
		SET nuevo_credito = credito_actual - total;
		UPDATE cliente SET credito = nuevo_credito WHERE usuario_c = cod_cliente;

		DELETE FROM detalle_temp WHERE token_user = token;
		TRUNCATE TABLE  tbl_tmp_tokenuser;
		SELECT * FROM factura WHERE nofactura = factura;
		
		ELSE

			SELECT 0;

		END IF;

		ELSE

			SELECT 1;
		
        END IF;
	


    END$$

DROP PROCEDURE IF EXISTS `procesar_venta_ok`$$
CREATE DEFINER=`u338188990_francis2`@`127.0.0.1` PROCEDURE `procesar_venta_ok` (IN `cod_usuario` BIGINT, IN `cod_cliente` BIGINT, IN `token` VARCHAR(50) CHARSET utf8mb4)   BEGIN
	DECLARE no_factura INT;
	DECLARE	registros INT;
	DECLARE total DECIMAL(10,2);
	DECLARE credito_actual DECIMAL(10,2);
	DECLARE	nuevo_credito DECIMAL(10,2);
	DECLARE nueva_existencia int;
	DECLARE existencia_actual int;  
	DECLARE tmp_cod_producto int;
	DECLARE tmp_cant_producto int;
	DECLARE precio_venta_prev DECIMAL(10,2);
    DECLARE a INT;

	IF cod_usuario = 0 THEN
	SET cod_usuario = 1;
	END IF;
	
	SET a = 1;
	
	CREATE TEMPORARY TABLE tbl_tmp_tokenuser(
	id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	cod_prod BIGINT,
	cant_prod INT,
	comedor_tmp INT,
	fecha_tmp DATE);

	SET registros = (SELECT COUNT(*) FROM detalle_temp WHERE token_user = token);
    
    SET credito_actual = (SELECT credito FROM clientes WHERE usuario = cod_cliente);
	
	SET precio_venta_prev = (SELECT SUM(cantidad * precio_venta) FROM detalle_temp WHERE token_user = token);
	
	IF credito_actual >= precio_venta_prev THEN
    
    	IF registros > 0 THEN
		INSERT INTO tbl_tmp_tokenuser(cod_prod,cant_prod,comedor_tmp,fecha_tmp) SELECT codproducto,cantidad,comedor,fecha FROM detalle_temp WHERE token_user = token;

		INSERT INTO factura(usuario,codcliente) VALUES(cod_usuario,cod_cliente);
		
		SET no_factura= LAST_INSERT_ID();
				
		INSERT INTO detalle_factura(nofactura,codproducto,cantidad,precio_venta,comedor,fecha) SELECT (no_factura) as nofactura, codproducto,cantidad,precio_venta,comedor,fecha FROM detalle_temp WHERE token_user = token;
		
		WHILE a <= registros DO
		SELECT cod_prod,cant_prod INTO tmp_cod_producto,tmp_cant_producto FROM tbl_tmp_tokenuser WHERE id = a;	
		
        SELECT existencia INTO existencia_actual FROM producto WHERE codproducto = tmp_cod_producto;
		
		SET nueva_existencia = existencia_actual - tmp_cant_producto;
		UPDATE producto SET existencia = nueva_existencia WHERE codproducto = tmp_cod_producto;
		
		SET a = a+1;
		
		END WHILE;

		SET total = (SELECT SUM(cantidad * precio_venta) FROM detalle_temp WHERE token_user = token);
		UPDATE factura SET totalfactura = total WHERE nofactura = no_factura;
		
		SET nuevo_credito = credito_actual - total;
		UPDATE clientes SET credito = nuevo_credito WHERE usuario = cod_cliente;

		DELETE FROM detalle_temp WHERE token_user = token;
		TRUNCATE TABLE  tbl_tmp_tokenuser;
		SELECT * FROM factura WHERE nofactura = no_factura;
		
		ELSE
			SELECT 0;
		END IF;

		ELSE
			SELECT 1;
		END IF;

    END$$

DROP PROCEDURE IF EXISTS `stock`$$
CREATE DEFINER=`u338188990_francis2`@`127.0.0.1` PROCEDURE `stock` (IN `fecha_b` DATE)   BEGIN
	
    DECLARE desayuno_s int;
    DECLARE almuerzo_s int;
    DECLARE merienda_s int;
    DECLARE desayuno_v int;
    DECLARE almuerzo_v int;
    DECLARE merienda_v int;
    DECLARE desayuno_s_2 int;
    DECLARE almuerzo_s_2 int;
    DECLARE merienda_s_2 int;
    DECLARE desayuno_v_2 int;
    DECLARE almuerzo_v_2 int;
    DECLARE merienda_v_2 int;
    DECLARE desayuno_s_3 int;
    DECLARE almuerzo_s_3 int;
    DECLARE merienda_s_3 int;
    DECLARE desayuno_v_3 int;
    DECLARE almuerzo_v_3 int;
    DECLARE merienda_v_3 int;
    
    SELECT SUM(existencia) INTO desayuno_s FROM existencias WHERE codproducto = 1 AND fecha = fecha_b AND comedor = 1;
    SELECT SUM(existencia) INTO almuerzo_s FROM existencias WHERE codproducto = 2  AND fecha = fecha_b AND comedor = 1;
    SELECT SUM(existencia) INTO merienda_s FROM existencias WHERE codproducto = 3 AND fecha = fecha_b AND comedor = 1;    
    SELECT SUM(cantidad) INTO desayuno_v FROM detalle_factura WHERE codproducto = 1 AND fecha = fecha_b AND comedor = 1;
    SELECT SUM(cantidad) INTO almuerzo_v FROM detalle_factura WHERE codproducto = 2  AND fecha = fecha_b AND comedor = 1;
    SELECT SUM(cantidad) INTO merienda_v FROM detalle_factura WHERE codproducto = 3 AND fecha = fecha_b AND comedor = 1;
    
    
    SELECT SUM(existencia) INTO desayuno_s_2 FROM existencias WHERE codproducto = 1 AND fecha = fecha_b AND comedor = 2;
    SELECT SUM(existencia) INTO almuerzo_s_2 FROM existencias WHERE codproducto = 2  AND fecha = fecha_b AND comedor = 2;
    SELECT SUM(existencia) INTO merienda_s_2 FROM existencias WHERE codproducto = 3 AND fecha = fecha_b AND comedor = 2;
    SELECT SUM(cantidad) INTO desayuno_v_2 FROM detalle_factura WHERE codproducto = 1 AND fecha = fecha_b AND comedor = 2;
    SELECT SUM(cantidad) INTO almuerzo_v_2 FROM detalle_factura WHERE codproducto = 2  AND fecha = fecha_b AND comedor = 2;
    SELECT SUM(cantidad) INTO merienda_v_2 FROM detalle_factura WHERE codproducto = 3 AND fecha = fecha_b AND comedor = 2;
    
    
    SELECT SUM(existencia) INTO desayuno_s_3 FROM existencias WHERE codproducto = 1 AND fecha = fecha_b AND comedor = 3;
    SELECT SUM(existencia) INTO almuerzo_s_3 FROM existencias WHERE codproducto = 2  AND fecha = fecha_b AND comedor = 3;
    SELECT SUM(existencia) INTO merienda_s_3 FROM existencias WHERE codproducto = 3 AND fecha = fecha_b AND comedor = 3;
    SELECT SUM(cantidad) INTO desayuno_v_3 FROM detalle_factura WHERE codproducto = 1 AND fecha = fecha_b AND comedor = 3;
    SELECT SUM(cantidad) INTO almuerzo_v_3 FROM detalle_factura WHERE codproducto = 2  AND fecha = fecha_b AND comedor = 3;
    SELECT SUM(cantidad) INTO merienda_v_3 FROM detalle_factura WHERE codproducto = 3 AND fecha = fecha_b AND comedor = 3;
  
    
   
    
    SELECT desayuno_s,almuerzo_s,merienda_s,desayuno_v,almuerzo_v,merienda_v,desayuno_s_2,almuerzo_s_2,merienda_s_2,desayuno_v_2,almuerzo_v_2,merienda_v_2, desayuno_s_3,almuerzo_s_3,merienda_s_3,desayuno_v_3,almuerzo_v_3,merienda_v_3;
END$$

DROP PROCEDURE IF EXISTS `turnos`$$
CREATE DEFINER=`u338188990_francis2`@`127.0.0.1` PROCEDURE `turnos` ()   BEGIN
	
    DECLARE maxsemanagym int;
    DECLARE maxdiagym int;
    DECLARE maxturnogym int;
    DECLARE maxsemanapelu int;
    DECLARE maxdiapelu int;
    DECLARE maxturnopelu int;
	
    SELECT maxsemana INTO maxsemanagym FROM config_turnos WHERE id = 1; 
    SELECT maxdia INTO maxdiagym FROM config_turnos WHERE id = 1; 
    SELECT maxturno INTO maxturnogym FROM config_turnos WHERE id = 1;
    
    SELECT maxsemana INTO maxsemanapelu FROM config_turnos WHERE id = 2; 
    SELECT maxdia INTO maxdiapelu FROM config_turnos WHERE id = 2; 
    SELECT maxturno INTO maxturnopelu FROM config_turnos WHERE id = 2;
    
    SELECT maxsemanagym,maxdiagym,maxturnogym,maxsemanapelu,maxdiapelu,maxturnopelu;  
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `archivos`
--

DROP TABLE IF EXISTS `archivos`;
CREATE TABLE IF NOT EXISTS `archivos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cedula` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `c_depen` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `placa` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` int NOT NULL,
  `archivo` mediumblob NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `archivos_personales`
--

DROP TABLE IF EXISTS `archivos_personales`;
CREATE TABLE IF NOT EXISTS `archivos_personales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cedula` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` int NOT NULL,
  `ubicacion` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `archivos_personales`
--

INSERT INTO `archivos_personales` (`id`, `cedula`, `tipo`, `ubicacion`) VALUES
(2, '1234', 1, '16541282321.pdf'),
(3, '1234', 2, '16541282461.pdf'),
(4, '1234', 3, '16539611271.pdf'),
(5, '', 3, '16541148551.pdf'),
(6, '431341234', 3, '16541111451.pdf'),
(7, '234234234', 3, '16541148751.pdf'),
(8, '123123', 3, '16541153871.pdf'),
(9, '3123123', 3, '16541155411.pdf'),
(10, '12312333', 3, '16541275481.pdf'),
(11, '1234567898', 3, '16603430951.pdf'),
(12, '8577466475', 3, '16608648921.pdf');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `arqueo_caja`
--

DROP TABLE IF EXISTS `arqueo_caja`;
CREATE TABLE IF NOT EXISTS `arqueo_caja` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_caja` int NOT NULL,
  `id_usuario` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `monto_inicial` decimal(10,2) NOT NULL,
  `monto_final` decimal(10,2) DEFAULT NULL,
  `total_ventas` int DEFAULT NULL,
  `total_cash` decimal(10,2) DEFAULT NULL,
  `efectivo` decimal(10,2) DEFAULT NULL,
  `transferencia` decimal(10,2) DEFAULT NULL,
  `deuna` decimal(10,0) NOT NULL,
  `tarjeta` decimal(10,2) DEFAULT NULL,
  `salida` int NOT NULL,
  `estatus` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=231 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `arqueo_caja`
--

INSERT INTO `arqueo_caja` (`id`, `id_caja`, `id_usuario`, `fecha_inicio`, `fecha_fin`, `monto_inicial`, `monto_final`, `total_ventas`, `total_cash`, `efectivo`, `transferencia`, `deuna`, `tarjeta`, `salida`, `estatus`) VALUES
(19, 1, '1803641420', '2023-11-03 20:47:42', '2023-11-04 19:57:17', 10.00, 138.00, 11, 128.31, NULL, NULL, 0, NULL, 0, 2),
(20, 1, '1850108166', '2023-11-04 20:03:58', '2023-11-04 20:07:08', 15.00, 20.00, 1, 9.97, NULL, NULL, 0, NULL, 0, 2),
(21, 1, '302433214', '2023-11-04 20:07:25', '2023-11-04 20:09:54', 20.00, 30.00, 1, 9.99, NULL, NULL, 0, NULL, 0, 2),
(22, 1, '1850108166', '2023-11-04 20:10:09', '2023-11-04 20:35:54', 30.00, 200.00, 3, 225.51, NULL, NULL, 0, NULL, 0, 2),
(23, 1, '1234', '2023-11-04 20:38:41', '2023-11-04 20:44:05', 50.00, 10.00, 1, 6.49, NULL, NULL, 0, NULL, 0, 2),
(24, 1, '302433214', '2023-11-04 20:44:36', '2023-11-17 20:08:53', 10.00, 11.00, 1, 1.25, NULL, NULL, 0, NULL, 0, 2),
(25, 2, '1234', '2023-11-12 00:48:23', '2023-11-17 20:18:45', 10.00, 42.00, 3, 32.44, NULL, NULL, 0, NULL, 0, 2),
(26, 3, '1850108166', '2023-11-17 20:08:13', '2023-11-17 20:18:12', 15.00, 23.00, 1, 7.97, NULL, NULL, 0, NULL, 0, 2),
(27, 1, '1850108166', '2023-11-17 20:19:33', '2023-12-03 12:36:10', 15.00, 50.00, 2, 35.96, NULL, NULL, 0, NULL, 0, 2),
(28, 1, '302433214', '2023-12-03 12:36:28', '2023-12-03 12:38:43', 10.00, 10.00, 1, 27.94, NULL, NULL, 0, NULL, 0, 2),
(29, 1, '302433214', '2023-12-03 12:40:13', '2023-12-03 12:41:31', 15.00, 19.00, 1, 3.99, NULL, NULL, 0, NULL, 0, 2),
(30, 1, '1850108166', '2023-12-03 13:27:57', '2024-02-03 11:42:25', 10.00, 23.00, 1, 13.97, NULL, NULL, 0, NULL, 0, 2),
(31, 3, '302433214', '2023-12-08 21:00:44', '2024-02-03 11:42:07', 67.00, 10.00, 1, 6.49, NULL, NULL, 0, NULL, 0, 2),
(32, 1, '1234', '2024-02-03 11:42:45', '2024-02-03 18:41:42', 1.00, 16.00, 2, 15.96, NULL, NULL, 0, NULL, 0, 2),
(33, 1, '302433214', '2024-02-03 18:44:16', '2024-02-04 12:10:41', 18.00, 228.00, 8, 210.42, NULL, NULL, 0, NULL, 0, 2),
(34, 1, '1234', '2024-02-04 12:11:43', '2024-02-04 12:23:42', 1.00, 22.00, 1, 21.99, NULL, NULL, 0, NULL, 0, 2),
(35, 1, '1234', '2024-02-06 21:49:03', '2024-02-07 22:47:45', 10.00, 10.00, 1, 6.97, 0.00, 10.00, 0, 0.00, 0, 2),
(36, 1, '302433214', '2024-02-07 22:48:12', '2024-02-07 22:55:07', 10.00, 23.47, 1, 13.47, 23.47, 0.00, 0, 0.00, 0, 2),
(37, 1, '302433214', '2024-02-07 22:56:18', '2024-02-07 23:00:34', 10.00, 26.46, 1, 16.46, 26.46, 0.00, 0, 0.00, 0, 2),
(38, 1, '302433214', '2024-02-07 23:03:13', '2024-02-07 23:04:33', 10.00, 34.00, 0, 0.00, 34.00, 0.00, 0, 0.00, 0, 2),
(39, 1, '302433214', '2024-02-07 23:07:47', '2024-02-07 23:19:36', 20.00, 33.39, 1, 13.98, 20.00, 13.39, 0, 0.00, 0, 2),
(40, 1, '302433214', '2024-02-07 23:20:51', '2024-02-08 20:29:50', 25.00, 300.00, 3, 59.14, 300.00, 0.00, 0, 0.00, 0, 2),
(41, 1, '302433214', '2024-02-08 20:30:10', '2024-02-08 20:34:40', 27.40, 50.37, 1, 22.97, 50.37, 0.00, 0, 0.00, 0, 2),
(42, 1, '1850108166', '2024-02-08 20:35:53', '2024-02-08 20:39:31', 27.40, 64.86, 1, 37.46, 64.86, 0.00, 0, 0.00, 0, 2),
(43, 2, '302433214', '2024-02-08 20:40:40', '2024-02-08 20:42:19', 12.00, 12.00, 0, 0.00, 12.00, 0.00, 0, 0.00, 0, 2),
(44, 1, '302433214', '2024-02-08 20:49:15', '2024-02-08 20:53:56', 27.40, 65.60, 2, 38.20, 65.60, 0.00, 0, 0.00, 0, 2),
(45, 1, '302433214', '2024-02-09 18:11:45', '2024-02-10 00:02:08', 27.15, 62.84, 6, 35.69, 62.84, 0.00, 0, 0.00, 0, 2),
(46, 1, '302433214', '2024-02-10 08:58:02', '2024-02-10 08:58:23', 28.80, 28.80, 0, 0.00, 28.80, 0.00, 0, 0.00, 0, 2),
(47, 1, '302433214', '2024-02-10 08:58:49', '2024-02-10 11:02:35', 28.80, 28.80, 0, 0.00, 28.80, 0.00, 0, 0.00, 0, 2),
(48, 1, '302433214', '2024-02-10 17:58:39', '2024-02-10 18:34:00', 10.00, 32.96, 1, 22.96, 32.96, 0.00, 0, 0.00, 0, 2),
(49, 1, '302433214', '2024-02-10 19:01:46', '2024-02-10 19:01:58', 1.00, 1.00, 0, 0.00, 1.00, 0.00, 0, 0.00, 0, 2),
(50, 1, '302433214', '2024-02-10 19:04:06', '2024-02-10 19:04:18', 10.00, 10.00, 0, 0.00, 10.00, 0.00, 0, 0.00, 0, 2),
(51, 1, '302433214', '2024-02-10 19:25:54', '2024-02-10 19:26:02', 10.00, 10.00, 0, 0.00, 10.00, 0.00, 0, 0.00, 0, 2),
(52, 1, '302433214', '2024-02-10 19:26:45', '2024-02-10 19:26:55', 10.00, 10.00, 0, 0.00, 5.00, 2.00, 0, 3.00, 0, 2),
(53, 1, '302433214', '2024-02-10 19:32:44', '2024-02-10 19:32:54', 10.00, 4.00, 0, 0.00, 1.00, 1.00, 0, 2.00, 0, 2),
(54, 1, '302433214', '2024-02-10 19:37:11', '2024-02-10 19:37:18', 10.00, 1.00, 0, 0.00, 0.00, 0.00, 0, 1.00, 0, 2),
(55, 1, '302433214', '2024-02-10 19:37:57', '2024-02-10 19:38:05', 1.00, 11.00, 0, 0.00, 11.00, 0.00, 0, 0.00, 0, 2),
(56, 1, '302433214', '2024-02-10 19:40:50', '2024-02-10 19:40:58', 2.00, 22.00, 0, 0.00, 0.00, 22.00, 0, 0.00, 0, 2),
(57, 1, '302433214', '2024-02-10 19:42:32', '2024-02-10 19:42:39', 10.00, 33.00, 0, 0.00, 0.00, 0.00, 0, 33.00, 0, 2),
(58, 1, '302433214', '2024-02-10 19:44:42', '2024-02-10 19:44:49', 10.00, 22.00, 0, 0.00, 0.00, 22.00, 0, 0.00, 0, 2),
(59, 1, '302433214', '2024-02-10 19:49:22', '2024-02-10 19:49:32', 1.00, 333.00, 0, 0.00, 333.00, 0.00, 0, 0.00, 0, 2),
(60, 1, '302433214', '2024-02-10 19:51:19', '2024-02-11 00:17:42', 26.80, 400.00, 26, 371.26, 400.00, 0.00, 0, 0.00, 0, 2),
(61, 1, '302433214', '2024-02-11 00:19:00', '2024-02-11 01:09:52', 1.00, 16.47, 4, 15.47, 16.47, 0.00, 0, 0.00, 0, 2),
(62, 1, '302433214', '2024-02-11 10:09:21', '2024-02-11 10:31:08', 1.00, 105.00, 9, 103.81, 105.00, 0.00, 0, 0.00, 0, 2),
(63, 1, '302433214', '2024-02-11 10:33:11', '2024-02-11 10:33:20', 10.00, 10.00, 0, 0.00, 0.00, 0.00, 0, 10.00, 0, 2),
(64, 1, '302433214', '2024-02-11 10:35:27', '2024-02-11 18:01:51', 1.00, 117.82, 7, 116.82, 117.82, 0.00, 0, 0.00, 0, 2),
(65, 1, '302433214', '2024-02-11 18:07:55', '2024-02-11 18:42:27', 5.85, 5.85, 0, 0.00, 5.85, 0.00, 0, 0.00, 0, 2),
(66, 1, '302433214', '2024-02-11 18:42:42', '2024-02-12 01:16:19', 25.85, 493.02, 64, 647.77, 493.02, 0.00, 0, 0.00, 0, 2),
(67, 1, '302433214', '2024-02-12 16:30:06', '2024-02-12 16:38:00', 2.29, 91.36, 1, 91.36, 91.36, 0.00, 0, 0.00, 0, 2),
(68, 1, '302433214', '2024-02-12 16:38:21', '2024-02-12 17:57:45', 1.00, 24.22, 2, 23.22, 24.22, 0.00, 0, 0.00, 0, 2),
(69, 1, '302433214', '2024-02-12 18:00:10', '2024-02-13 00:47:45', 8.25, 234.13, 30, 265.83, 234.13, 0.00, 0, 0.00, 0, 2),
(70, 1, '302433214', '2024-02-13 00:55:33', '2024-02-13 01:12:28', 30.00, 31.98, 1, 1.98, 31.98, 0.00, 0, 0.00, 0, 2),
(71, 1, '302433214', '2024-02-13 18:17:37', '2024-02-13 23:04:36', 24.00, 105.82, 10, 81.82, 105.82, 0.00, 0, 0.00, 0, 2),
(72, 1, '302433214', '2024-02-15 18:09:14', '2024-02-15 18:12:17', 12.00, 29.96, 2, 17.96, 29.96, 0.00, 0, 0.00, 0, 2),
(73, 1, '302433214', '2024-02-15 18:21:49', '2024-02-15 23:57:20', 13.60, 42.04, 4, 28.44, 42.04, 0.00, 0, 0.00, 0, 2),
(74, 1, '302433214', '2024-02-16 08:10:28', '2024-02-16 23:57:23', 12.60, 55.21, 10, 42.61, 55.21, 0.00, 0, 0.00, 0, 2),
(75, 1, '302433214', '2024-02-17 18:06:36', '2024-02-18 00:19:32', 19.75, 153.73, 18, 133.98, 153.73, 0.00, 0, 0.00, 0, 2),
(76, 1, '302433214', '2024-02-18 08:01:49', '2024-02-18 23:53:06', 7.00, 60.66, 4, 53.66, 60.66, 0.00, 0, 0.00, 0, 2),
(77, 2, '302433214', '2024-02-18 19:47:20', '2024-02-18 19:47:47', 7.00, 7.00, 0, 0.00, 7.00, 0.00, 0, 0.00, 0, 2),
(78, 1, '1850108166', '2024-02-21 18:12:44', '2024-02-21 23:39:47', 14.30, 46.23, 3, 31.93, 46.23, 0.00, 0, 0.00, 0, 2),
(79, 1, '1850108166', '2024-02-22 18:04:04', '2024-02-22 23:42:06', 18.80, 39.78, 3, 20.98, 39.78, 0.00, 0, 0.00, 0, 2),
(80, 1, '302433214', '2024-02-23 19:54:39', '2024-02-23 23:50:27', 34.70, 52.70, 1, 17.99, 52.70, 0.00, 0, 0.00, 0, 2),
(81, 1, '302433214', '2024-02-24 18:17:36', '2024-02-24 20:26:26', 48.70, 61.48, 1, 12.48, 61.48, 0.00, 0, 0.00, 0, 2),
(82, 1, '302433214', '2024-02-24 20:26:55', '2024-02-24 23:50:50', 48.70, 94.13, 4, 45.43, 94.13, 0.00, 0, 0.00, 0, 2),
(83, 1, '302433214', '2024-02-25 18:07:14', '2024-02-25 18:58:56', 18.80, 18.80, 0, 0.00, 18.80, 0.00, 0, 0.00, 0, 2),
(84, 1, '302433214', '2024-02-25 18:59:40', '2024-02-25 23:40:43', 13.80, 94.36, 7, 80.56, 94.36, 0.00, 0, 0.00, 0, 2),
(85, 1, '1850108166', '2024-02-28 18:02:38', '2024-02-29 08:27:12', 9.10, 63.74, 5, 54.64, 63.74, 0.00, 0, 0.00, 0, 2),
(86, 1, '302433214', '2024-02-29 18:12:03', '2024-03-01 18:07:13', 12.15, 22.88, 3, 10.73, 22.88, 0.00, 0, 0.00, 0, 2),
(87, 1, '302433214', '2024-03-01 18:07:35', '2024-03-01 23:54:37', 21.90, 86.05, 5, 64.15, 86.05, 0.00, 0, 0.00, 0, 2),
(88, 1, '302433214', '2024-03-02 20:58:06', '2024-03-03 00:01:28', 5.45, 55.62, 7, 39.69, 45.14, 10.48, 0, 0.00, 0, 2),
(89, 1, '302433214', '2024-03-03 19:20:51', '2024-03-04 00:00:48', 4.90, 62.82, 4, 57.92, 62.82, 0.00, 0, 0.00, 0, 2),
(90, 1, '302433214', '2024-03-06 18:53:58', '2024-03-07 19:18:09', 16.85, 106.37, 18, 89.52, 106.37, 0.00, 0, 0.00, 0, 2),
(91, 1, '302433214', '2024-03-07 19:21:12', '2024-03-07 19:31:03', 10.35, 10.35, 0, 0.00, 10.35, 0.00, 0, 0.00, 0, 2),
(92, 1, '302433214', '2024-03-07 19:32:14', '2024-03-07 19:35:14', 16.30, 16.30, 0, 0.00, 16.30, 0.00, 0, 0.00, 0, 2),
(93, 1, '302433214', '2024-03-07 19:35:33', '2024-03-07 19:36:13', 16.30, 16.30, 0, 0.00, 16.30, 0.00, 0, 0.00, 0, 2),
(94, 1, '302433214', '2024-03-07 19:36:35', '2024-03-07 23:54:05', 16.30, 27.75, 5, 11.45, 27.75, 0.00, 0, 0.00, 0, 2),
(95, 1, '302433214', '2024-03-08 07:58:37', '2024-03-08 08:05:53', 14.75, 14.75, 0, 0.00, 14.75, 0.00, 0, 0.00, 0, 2),
(96, 1, '302433214', '2024-03-08 18:12:03', '2024-03-08 23:56:04', 58.45, 163.03, 14, 104.58, 163.03, 0.00, 0, 0.00, 0, 2),
(97, 1, '302433214', '2024-03-09 18:21:11', '2024-03-10 00:00:09', 13.40, 122.72, 13, 109.32, 122.72, 0.00, 0, 0.00, 0, 2),
(98, 1, '302433214', '2024-03-10 18:16:59', '2024-03-10 23:03:58', 10.15, 35.61, 3, 25.46, 35.61, 0.00, 0, 0.00, 0, 2),
(99, 1, '302433214', '2024-03-13 19:57:37', '2024-03-13 23:27:46', 15.10, 60.99, 4, 45.89, 60.99, 0.00, 0, 0.00, 0, 2),
(100, 1, '302433214', '2024-03-14 18:25:47', '2024-03-14 23:55:24', 17.60, 74.96, 4, 57.36, 74.96, 0.00, 0, 0.00, 0, 2),
(101, 1, '302433214', '2024-03-15 20:06:28', '2024-03-16 00:01:18', 76.80, 184.15, 12, 85.34, 158.20, 0.00, 0, 25.95, 0, 2),
(102, 1, '302433214', '2024-03-16 00:06:28', '2024-03-17 19:28:59', 1.00, 50.00, 4, 40.45, 50.00, 0.00, 0, 0.00, 0, 2),
(103, 1, '302433214', '2024-03-17 19:34:13', '2024-03-17 23:24:56', 3.55, 144.09, 12, 140.54, 144.09, 0.00, 0, 0.00, 0, 2),
(104, 1, '1234', '2024-03-18 13:12:48', '2024-03-20 09:20:01', 10.00, 9284.00, 12, 82.84, 9284.00, 0.00, 0, 0.00, 0, 2),
(105, 1, '1850108166', '2024-03-20 09:22:31', '2024-03-21 00:03:09', 2.00, 58.47, 5, 58.47, 12.70, 45.77, 0, 0.00, 0, 2),
(106, 1, '1850108166', '2024-03-21 08:04:43', '2024-03-21 18:12:21', 14.45, 28.43, 1, 13.98, 28.43, 0.00, 0, 0.00, 0, 2),
(107, 2, '1850108166', '2024-03-21 18:12:52', '2024-03-21 18:13:12', 27.45, 27.45, 0, 0.00, 27.45, 0.00, 0, 0.00, 0, 2),
(108, 1, '1850108166', '2024-03-21 18:13:35', '2024-03-22 18:38:05', 27.45, 153.43, 10, 125.98, 153.43, 0.00, 0, 0.00, 0, 2),
(109, 1, '1850108166', '2024-03-22 18:39:40', '2024-03-22 23:20:36', 13.50, 13.50, 0, 0.00, 13.50, 0.00, 0, 0.00, 0, 2),
(110, 1, '302433214', '2024-03-22 23:23:21', '2024-03-22 23:23:34', 10.00, 10.00, 0, 0.00, 10.00, 0.00, 0, 0.00, 0, 2),
(111, 1, '302433214', '2024-03-22 23:31:59', '2024-03-23 09:42:09', 10.00, 1.00, 0, 0.00, 1.00, 0.00, 0, 0.00, 0, 2),
(112, 1, '1234', '2024-03-23 09:42:19', '2024-03-23 09:46:48', 10.00, 10.00, 1, 3.99, 10.00, 0.00, 0, 0.00, 0, 2),
(113, 1, '302433214', '2024-03-23 09:49:18', '2024-03-23 12:04:32', 4.00, 38.15, 4, 34.15, 38.15, 0.00, 0, 0.00, 0, 2),
(114, 1, '302433214', '2024-03-23 15:55:34', '2024-03-23 18:03:35', 4.75, 19.98, 2, 15.23, 19.98, 0.00, 0, 0.00, 0, 2),
(115, 1, '302433214', '2024-03-23 18:10:09', '2024-03-23 23:52:16', 22.95, 159.09, 31, 239.76, 159.09, 0.00, 0, 0.00, 0, 2),
(116, 1, '302433214', '2024-03-24 00:13:42', '2024-03-24 00:35:10', 1.00, 10.00, 1, 8.99, 10.00, 0.00, 0, 0.00, 0, 2),
(117, 1, '302433214', '2024-03-24 08:06:21', '2024-03-24 18:14:37', 20.70, 47.14, 3, 26.45, 47.14, 0.00, 0, 0.00, 0, 2),
(118, 1, '302433214', '2024-03-24 18:14:49', '2024-03-24 23:01:40', 32.70, 90.20, 5, 64.90, 90.20, 0.00, 0, 0.00, 0, 2),
(119, 1, '1850108166', '2024-03-27 18:38:09', '2024-03-27 23:26:40', 9.45, 81.57, 8, 81.57, 56.38, 25.19, 0, 0.00, 0, 2),
(120, 1, '1850108166', '2024-03-28 07:59:30', '2024-03-28 23:02:23', 16.20, 14.49, 2, 14.49, 14.49, 0.00, 0, 0.00, 0, 2),
(121, 1, '1850108166', '2024-03-29 09:30:34', '2024-03-29 18:30:54', 23.00, 44.49, 1, 21.49, 44.49, 0.00, 0, 0.00, 0, 2),
(122, 1, '302433214', '2024-03-29 18:35:49', '2024-03-30 00:01:19', 14.00, 174.00, 16, 174.02, 160.00, 14.00, 0, 0.00, 0, 2),
(123, 1, '302433214', '2024-03-30 07:15:10', '2024-03-30 23:43:22', 7.00, 199.36, 21, 192.36, 142.42, 56.94, 0, 0.00, 0, 2),
(124, 1, '302433214', '2024-03-31 01:09:38', '2024-03-31 07:02:29', 2.00, 11.75, 1, 9.75, 11.75, 0.00, 0, 0.00, 0, 2),
(125, 2, '302433214', '2024-03-31 07:01:30', '2024-03-31 07:02:03', 4.45, 4.45, 0, 0.00, 4.45, 0.00, 0, 0.00, 0, 2),
(126, 1, '302433214', '2024-03-31 07:02:48', '2024-03-31 17:58:08', 4.45, 57.38, 8, 52.93, 57.38, 0.00, 0, 0.00, 0, 2),
(127, 1, '302433214', '2024-03-31 17:58:39', '2024-03-31 22:19:45', 5.75, 26.22, 3, 20.47, 26.22, 0.00, 0, 0.00, 0, 2),
(128, 1, '1850108166', '2024-04-03 08:09:24', '2024-04-03 23:34:05', 5.00, 50.88, 4, 50.88, 50.88, 0.00, 0, 0.00, 0, 2),
(129, 1, '1850108166', '2024-04-04 08:09:24', '2024-04-04 23:54:31', 19.00, 73.19, 5, 54.18, 59.19, 14.00, 0, 0.00, 0, 2),
(130, 1, '302433214', '2024-04-05 08:02:25', '2024-04-05 23:58:28', 11.25, 55.94, 5, 44.69, 55.94, 0.00, 0, 0.00, 0, 2),
(131, 1, '302433214', '2024-04-06 07:05:13', '2024-04-06 11:25:54', 9.95, 109.33, 11, 99.38, 109.33, 0.00, 0, 0.00, 0, 2),
(132, 1, '302433214', '2024-04-06 18:03:35', '2024-04-06 23:19:34', 8.50, 195.20, 16, 186.70, 195.20, 0.00, 0, 0.00, 0, 2),
(133, 1, '302433214', '2024-04-07 06:55:49', '2024-04-07 23:06:12', 16.20, 84.63, 8, 68.43, 84.63, 0.00, 0, 0.00, 0, 2),
(134, 1, '1850108166', '2024-04-10 17:47:42', '2024-04-10 23:35:51', 16.40, 61.17, 7, 61.17, 61.17, 0.00, 0, 0.00, 0, 2),
(135, 1, '302433214', '2024-04-11 19:06:01', '2024-04-11 23:59:39', 20.00, 37.48, 2, 17.48, 37.48, 0.00, 0, 0.00, 0, 2),
(136, 1, '302433214', '2024-04-12 08:00:54', '2024-04-12 23:56:53', 9.60, 134.89, 17, 134.89, 128.89, 6.00, 0, 0.00, 0, 2),
(137, 1, '302433214', '2024-04-13 06:59:26', '2024-04-14 00:05:27', 20.00, 224.47, 20, 260.38, 224.47, 0.00, 0, 0.00, 0, 2),
(138, 1, '302433214', '2024-04-14 01:05:17', '2024-04-14 07:09:25', 1.00, 20.95, 1, 19.95, 1.00, 19.95, 0, 0.00, 0, 2),
(139, 1, '1850108166', '2024-04-14 08:17:08', '2024-04-17 18:07:45', 1.00, 98.19, 7, 98.19, 98.19, 0.00, 0, 0.00, 0, 2),
(140, 1, '1850108166', '2024-04-17 18:14:15', '2024-04-19 10:13:53', 22.00, 22.00, 0, 0.00, 22.00, 0.00, 0, 0.00, 0, 2),
(141, 1, '1850108166', '2024-04-24 08:07:18', '2024-04-24 08:38:26', 4.60, 4.60, 0, 0.00, 4.60, 0.00, 0, 0.00, 0, 2),
(142, 1, '1850108166', '2024-04-24 08:40:10', '2024-04-24 23:46:49', 13.10, 84.36, 4, 84.36, 84.36, 0.00, 0, 0.00, 0, 2),
(143, 1, '1850108166', '2024-04-25 09:50:47', '2024-04-25 23:52:52', 8.00, 65.88, 6, 65.88, 65.88, 0.00, 0, 0.00, 0, 2),
(144, 1, '1850108166', '2024-04-26 08:48:43', '2024-04-27 00:01:19', 17.00, 73.00, 7, 73.98, 73.00, 0.00, 0, 0.00, 0, 2),
(145, 1, '1850108166', '2024-04-27 08:44:36', '2024-04-27 18:33:25', 17.10, 70.42, 5, 70.42, 57.95, 12.47, 0, 0.00, 0, 2),
(146, 1, '1850108166', '2024-04-27 18:37:53', '2024-04-27 23:59:03', 24.05, 40.53, 3, 16.48, 40.53, 0.00, 0, 0.00, 0, 2),
(147, 1, '302433214', '2024-04-28 07:10:48', '2024-04-28 23:25:30', 20.60, 228.02, 24, 228.02, 215.54, 12.48, 0, 0.00, 0, 2),
(148, 1, '1850108166', '2024-05-01 08:10:57', '2024-05-01 23:43:22', 4.00, 38.95, 3, 38.95, 19.97, 18.98, 0, 0.00, 0, 2),
(149, 1, '302433214', '2024-05-02 19:46:57', '2024-05-04 00:02:10', 13.00, 31.18, 9, 31.18, 31.18, 0.00, 0, 0.00, 0, 2),
(150, 1, '302433214', '2024-05-04 07:12:52', '2024-05-04 19:23:55', 3.75, 0.99, 1, 0.99, 0.99, 0.00, 0, 0.00, 0, 2),
(151, 1, '302433214', '2024-05-04 19:24:18', '2024-05-04 23:56:23', 14.25, 44.14, 11, 68.10, 44.14, 0.00, 0, 0.00, 0, 2),
(152, 1, '302433214', '2024-05-05 08:10:54', '2024-05-05 11:47:50', 17.00, 112.18, 12, 122.67, 112.18, 0.00, 0, 0.00, 0, 2),
(153, 1, '302433214', '2024-05-05 20:27:55', '2024-05-05 23:11:55', 0.50, 23.92, 3, 23.92, 23.92, 0.00, 0, 0.00, 0, 2),
(154, 1, '1850108166', '2024-05-08 08:04:11', '2024-05-09 18:17:07', 9.65, 5.50, 2, 27.50, 5.50, 0.00, 0, 0.00, 0, 2),
(155, 1, '1850108166', '2024-05-09 18:18:35', '2024-05-09 20:29:52', 16.15, 16.15, 0, 0.00, 16.15, 0.00, 0, 0.00, 0, 2),
(156, 1, '1850108166', '2024-05-09 20:30:18', '2024-05-10 20:09:28', 31.15, 31.15, 0, 0.00, 31.15, 0.00, 0, 0.00, 0, 2),
(157, 1, '302433214', '2024-05-10 20:09:55', '2024-05-12 09:03:57', 31.15, 97.35, 7, 97.35, 97.35, 0.00, 0, 0.00, 0, 2),
(158, 1, '302433214', '2024-05-12 09:09:30', '2024-05-12 22:44:55', 17.65, 20.95, 3, 20.95, 20.95, 0.00, 0, 0.00, 0, 2),
(159, 1, '1850108166', '2024-05-15 10:10:49', '2024-05-16 00:01:15', 3.00, 54.70, 4, 54.70, 54.70, 0.00, 0, 0.00, 0, 2),
(160, 1, '1850108166', '2024-05-16 08:40:13', '2024-05-17 21:07:03', 15.00, 7.00, 1, 7.00, 7.00, 0.00, 0, 0.00, 0, 2),
(161, 1, '302433214', '2024-05-17 21:08:18', '2024-05-18 23:56:42', 10.55, 129.77, 9, 129.77, 129.77, 0.00, 0, 0.00, 0, 2),
(162, 1, '302433214', '2024-05-18 23:58:51', '2024-05-19 23:30:21', 10.00, 83.63, 7, 83.63, 83.63, 0.00, 0, 0.00, 0, 2),
(163, 1, '302433214', '2024-05-23 18:20:13', '2024-05-24 00:00:56', 10.70, 25.96, 3, 42.95, 25.96, 0.00, 0, 0.00, 0, 2),
(164, 1, '302433214', '2024-05-24 08:05:26', '2024-05-25 00:02:20', 3.70, 72.36, 8, 72.36, 72.36, 0.00, 0, 0.00, 0, 2),
(165, 1, '302433214', '2024-05-25 07:05:51', '2024-05-26 00:07:08', 8.25, 150.75, 17, 155.74, 150.75, 0.00, 0, 0.00, 0, 2),
(166, 1, '302433214', '2024-05-26 07:09:11', '2024-05-27 00:02:18', 17.95, 86.51, 12, 86.51, 86.51, 0.00, 0, 0.00, 0, 2),
(167, 1, '1850108166', '2024-05-29 09:30:11', '2024-05-29 23:55:01', 9.80, 5.50, 1, 5.50, 5.50, 0.00, 0, 0.00, 0, 2),
(168, 1, '1850108166', '2024-05-30 07:59:48', '2024-05-30 18:14:19', 14.30, 22.00, 2, 22.00, 22.00, 0.00, 0, 0.00, 0, 2),
(169, 1, '1850108166', '2024-05-30 18:17:11', '2024-05-31 08:05:00', 20.05, 16.96, 1, 16.96, 16.96, 0.00, 0, 0.00, 0, 2),
(170, 1, '302433214', '2024-05-31 08:05:43', '2024-05-31 23:54:24', 37.00, 61.44, 7, 61.44, 61.44, 0.00, 0, 0.00, 0, 2),
(171, 1, '302433214', '2024-06-01 22:15:15', '2024-06-02 23:53:09', 10.74, 43.91, 9, 46.90, 43.91, 0.00, 0, 0.00, 0, 2),
(172, 1, '1850108166', '2024-06-05 08:17:04', '2024-06-07 08:07:04', 1.50, 66.58, 10, 66.58, 66.58, 0.00, 0, 0.00, 0, 2),
(173, 1, '302433214', '2024-06-07 08:14:13', '2024-06-08 00:14:55', 5.00, 32.68, 6, 59.39, 32.68, 0.00, 0, 0.00, 0, 2),
(174, 1, '302433214', '2024-06-08 07:44:31', '2024-06-08 23:55:21', 2.00, 102.74, 8, 118.71, 102.74, 0.00, 0, 0.00, 0, 2),
(175, 1, '302433214', '2024-06-09 09:52:04', '2024-06-09 23:27:08', 4.85, 130.81, 15, 159.23, 130.81, 0.00, 0, 0.00, 0, 2),
(176, 1, '1850108166', '2024-06-12 20:43:37', '2024-06-13 08:07:52', 1.00, 21.94, 2, 21.94, 21.94, 0.00, 0, 0.00, 0, 2),
(177, 1, '1850108166', '2024-06-13 08:12:34', '2024-06-15 00:00:20', 1.00, 34.10, 5, 44.65, 34.10, 0.00, 0, 0.00, 0, 2),
(178, 1, '1850108166', '2024-06-15 10:06:43', '2024-06-15 18:17:40', 10.50, 28.95, 2, 28.95, 10.49, 18.46, 0, 0.00, 0, 2),
(179, 1, '1850108166', '2024-06-15 18:18:23', '2024-06-16 00:02:00', 10.65, 23.72, 2, 23.72, 23.72, 0.00, 0, 0.00, 0, 2),
(180, 1, '1850108166', '2024-06-16 07:07:36', '2024-06-16 23:23:44', 7.50, 76.78, 10, 76.78, 57.33, 0.00, 0, 19.45, 0, 2),
(181, 1, '1850108166', '2024-06-19 18:02:37', '2024-06-22 07:03:23', 6.00, 5.99, 1, 5.99, 5.99, 0.00, 0, 0.00, 0, 2),
(182, 1, '1850108166', '2024-06-22 07:03:59', '2024-06-22 23:59:50', 5.60, 32.68, 6, 32.68, 26.69, 5.99, 0, 0.00, 0, 2),
(183, 1, '302433214', '2024-06-23 19:29:12', '2024-06-24 00:02:30', 1.00, 74.37, 7, 74.37, 63.40, 10.97, 0, 0.00, 0, 2),
(184, 1, '1850108166', '2024-06-28 08:18:39', '2024-06-29 00:03:34', 5.35, 22.46, 2, 22.46, 22.46, 0.00, 0, 0.00, 0, 2),
(185, 1, '1850108166', '2024-06-29 07:00:12', '2024-06-30 00:02:10', 2.30, 58.64, 6, 58.64, 58.64, 0.00, 0, 0.00, 0, 2),
(186, 1, '1850108166', '2024-06-30 07:02:16', '2024-06-30 23:20:59', 2.50, 35.22, 3, 35.22, 35.22, 0.00, 0, 0.00, 0, 2),
(187, 1, '1850108166', '2024-07-05 08:04:59', '2024-07-06 00:08:11', 2.75, 47.67, 5, 47.67, 41.68, 5.99, 0, 0.00, 0, 2),
(188, 1, '1850108166', '2024-07-06 07:06:44', '2024-07-06 23:58:59', 3.00, 26.21, 5, 26.21, 26.21, 0.00, 0, 0.00, 0, 2),
(189, 1, '1850108166', '2024-07-07 06:59:49', '2024-07-07 23:12:22', 10.00, 16.50, 2, 16.50, 16.50, 0.00, 0, 0.00, 0, 2),
(190, 1, '1850108166', '2024-07-12 08:02:09', '2024-07-12 23:56:48', 6.50, 49.41, 7, 49.41, 49.41, 0.00, 0, 0.00, 0, 2),
(191, 1, '1850108166', '2024-07-13 07:00:42', '2024-07-13 23:31:32', 3.50, 72.67, 7, 72.67, 66.44, 6.23, 0, 0.00, 0, 2),
(192, 1, '1850108166', '2024-07-14 09:58:37', '2024-07-14 22:27:59', 6.40, 51.62, 4, 51.62, 26.92, 0.00, 0, 24.70, 0, 2),
(193, 1, '1850108166', '2024-07-19 08:02:20', '2024-07-20 00:04:45', 16.35, 25.18, 5, 25.18, 25.18, 0.00, 0, 0.00, 0, 2),
(194, 1, '1850108166', '2024-07-20 07:22:02', '2024-07-21 22:52:18', 10.00, 73.37, 4, 73.37, 48.41, 24.96, 0, 0.00, 0, 2),
(195, 1, '1850108166', '2024-07-25 18:33:30', '2024-08-23 23:48:54', 9.80, 1.00, 1, 23.96, 1.00, 0.00, 0, 0.00, 0, 2),
(196, 1, '1756269757', '2024-08-23 23:50:58', '2024-08-23 23:57:45', 10.00, 21.98, 2, 11.98, 11.98, 10.00, 0, 0.00, 0, 2),
(197, 1, '1756269757', '2024-08-23 23:59:31', '2024-08-24 00:03:10', 12.00, 21.97, 1, 9.97, 19.00, 2.97, 0, 0.00, 0, 2),
(198, 1, '1756269757', '2024-08-24 07:37:50', '2024-08-25 00:39:04', 15.75, 256.19, 12, 240.44, 256.19, 0.00, 0, 0.00, 0, 2),
(199, 1, '1756269757', '2024-08-25 08:35:05', '2024-08-25 23:08:21', 45.00, 221.62, 6, 221.62, 221.62, 0.00, 0, 0.00, 0, 2),
(200, 1, '1850108166', '2024-08-29 20:07:55', '2024-08-29 23:55:34', 4.10, 25.94, 2, 25.94, 7.97, 17.97, 0, 0.00, 0, 2),
(201, 1, '1756269757', '2024-08-30 08:00:01', '2024-08-31 00:00:01', 2.30, 119.24, 13, 119.24, 112.27, 6.97, 0, 0.00, 0, 2),
(202, 1, '1756269757', '2024-08-31 07:20:15', '2024-08-31 10:52:49', 7.80, 167.73, 19, 167.72, 157.73, 10.00, 0, 0.00, 0, 2),
(203, 1, '1756269757', '2024-08-31 18:02:41', '2024-09-01 00:12:41', 15.00, 134.00, 12, 134.00, 98.83, 35.17, 0, 0.00, 0, 2),
(204, 1, '1234', '2024-09-01 00:16:21', '2024-09-01 00:55:59', 10.00, 24.98, 1, 14.98, 24.98, 0.00, 0, 0.00, 0, 2),
(205, 1, '1234', '2024-09-01 00:56:40', '2024-09-01 01:17:06', 1.00, 666.00, 9, 95.84, 666.00, 0.00, 0, 0.00, 0, 2),
(206, 1, '1234', '2024-09-01 09:52:42', '2024-09-01 11:14:18', 1.00, 143.65, 5, 140.92, 135.65, 8.00, 0, 0.00, 0, 2),
(207, 1, '1756269757', '2024-09-01 18:23:03', '2024-09-01 23:14:55', 17.80, 23.50, 5, 23.48, 23.50, 0.00, 0, 0.00, 0, 2),
(208, 1, '1850108166', '2024-09-05 08:15:06', '2024-09-05 23:08:22', 33.75, 13.47, 3, 13.47, 5.98, 7.49, 0, 0.00, 0, 2),
(209, 1, '1850108166', '2024-09-06 08:04:33', '2024-09-07 00:04:51', 33.75, 100.74, 14, 100.74, 91.77, 8.97, 0, 0.00, 0, 2),
(210, 1, '1756269757', '2024-09-07 08:01:02', '2024-09-07 18:11:37', 16.75, 69.38, 6, 69.38, 34.96, 34.42, 0, 0.00, 0, 2),
(211, 1, '1756269757', '2024-09-07 18:15:31', '2024-09-07 23:57:46', 11.50, 116.59, 9, 116.59, 63.17, 53.42, 0, 0.00, 0, 2),
(212, 1, '1756269757', '2024-09-08 07:48:51', '2024-09-08 11:47:31', 11.75, 124.08, 7, 119.80, 81.97, 42.11, 0, 0.00, 12, 2),
(213, 1, '1756269757', '2024-09-12 18:17:45', '2024-09-12 23:56:00', 25.00, 100.00, 10, 100.00, 81.05, 12.97, 6, 0.00, 0, 2),
(214, 1, '1756269757', '2024-09-13 09:12:07', '2024-09-14 00:05:51', 15.00, 160.00, 11, 160.00, 160.00, 0.00, 0, 0.00, 4, 2),
(215, 1, '1756269757', '2024-09-14 08:19:51', '2024-09-14 11:33:13', 32.00, 172.50, 9, 142.92, 126.00, 46.50, 0, 0.00, 3, 2),
(216, 1, '1756269757', '2024-09-14 11:34:21', '2024-09-15 00:04:22', 26.20, 91.65, 9, 80.57, 65.20, 26.45, 0, 0.00, 15, 2),
(217, 1, '1850108166', '2024-12-31 11:31:46', '2025-01-02 16:14:20', 1.00, 570.00, 16, 285.55, 12.00, 312.00, 123, 123.00, 13, 2),
(218, 1, '1850108166', '2025-01-02 18:12:01', '2025-01-03 08:06:51', 18.00, 367.91, 20, 367.91, 295.00, 61.91, 0, 11.00, 0, 2),
(219, 1, '1850108166', '2025-01-03 08:07:36', '2025-01-03 08:08:57', 1.00, 0.00, 2, 23.94, 0.00, 0.00, 0, 0.00, 0, 2),
(220, 1, '1756269757', '2025-01-03 09:51:05', '2025-01-03 18:35:27', 1.00, 183.76, 13, 183.76, 148.83, 26.97, 0, 7.96, 6, 2),
(221, 1, '1756269757', '2025-01-03 18:36:22', '2025-01-04 00:22:31', 13.35, 166.37, 19, 326.72, 166.37, 0.00, 0, 0.00, 15, 2),
(222, 1, '1756269757', '2025-01-04 08:09:51', '2025-01-05 00:28:17', 1.30, 195.03, 18, 195.03, 68.80, 106.28, 20, 0.00, 0, 2),
(223, 1, '1756269757', '2025-01-05 08:38:39', '2025-01-05 11:33:38', 10.95, 108.94, 11, 108.84, 29.98, 78.96, 0, 0.00, 3, 2),
(224, 1, '1756269757', '2025-01-05 18:23:25', '2025-01-05 22:48:38', 0.55, 12.51, 1, 11.96, 0.55, 0.00, 0, 11.96, 0, 2),
(225, 1, '1850108166', '2025-01-10 20:29:37', '2025-01-11 00:01:37', 20.00, 9.48, 2, 9.48, 9.48, 0.00, 0, 0.00, 0, 2),
(226, 1, '1850108166', '2025-01-11 18:14:15', '2025-01-12 00:00:30', 15.50, 20.96, 1, 20.96, 0.00, 20.96, 0, 0.00, 0, 2),
(227, 1, '302433214', '2025-01-12 11:18:30', '2025-01-13 00:14:14', 1.00, 56.44, 3, 56.44, 56.44, 0.00, 0, 0.00, 9, 2),
(228, 1, '1850108166', '2025-01-18 19:13:28', '2025-01-19 00:32:34', 52.20, 111.73, 4, 111.73, 79.32, 0.00, 32, 0.00, 0, 2),
(229, 1, '1850108166', '2025-01-19 08:01:47', '2025-01-19 23:06:58', 1.70, 29.43, 5, 29.43, 11.47, 0.00, 18, 0.00, 0, 2),
(230, 1, '1756269757', '2025-01-20 14:11:55', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `atributos_productos`
--

DROP TABLE IF EXISTS `atributos_productos`;
CREATE TABLE IF NOT EXISTS `atributos_productos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codproducto` int NOT NULL,
  `cantidad` int NOT NULL,
  `atributo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estatus` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `atributos_productos`
--

INSERT INTO `atributos_productos` (`id`, `codproducto`, `cantidad`, `atributo`, `estatus`) VALUES
(1, 1, 1, 'Bebida_Caliente', 1),
(2, 1, 1, 'Bebida_Fria', 1),
(3, 1, 1, 'Huevos', 1),
(4, 1, 1, 'Vegetales', 1),
(5, 1, 1, 'Mayonesa', 1),
(6, 1, 1, 'Salsa_de_tomate', 1),
(7, 1, 1, 'BBQ', 1),
(8, 1, 1, 'Queso_Liquido', 1),
(9, 1, 1, 'Estado_Bebida', 1),
(10, 1, 1, 'Sabor_gaseosa', 1),
(11, 1, 1, 'Chili', 1),
(12, 1, 1, 'Pico_de_gallo', 1),
(13, 1, 1, 'Guacamole', 1),
(14, 1, 1, 'Chili_Sabor', 1),
(15, 1, 1, 'Adereso_ensalada', 1),
(16, 1, 1, 'Queso_cheddar', 1),
(17, 1, 1, 'Queso_hierbas', 1),
(18, 1, 1, 'Tocino', 1),
(19, 1, 1, 'Cebolla_caramelizada', 1),
(20, 1, 1, 'Salchicha', 1),
(21, 1, 1, 'Pickles', 1),
(22, 1, 1, 'Sabor_alitas', 1),
(23, 1, 1, 'Champinones', 1),
(24, 1, 1, 'Termino_carne', 1),
(25, 1, 1, 'Estado_verde', 1),
(26, 1, 1, 'Arroz', 1),
(27, 1, 1, 'Papas_fritas', 1),
(28, 1, 1, 'Patacones', 1),
(29, 1, 1, 'Maduritos', 1),
(30, 1, 1, 'Ensalada', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aviones`
--

DROP TABLE IF EXISTS `aviones`;
CREATE TABLE IF NOT EXISTS `aviones` (
  `id` int NOT NULL,
  `cola` int NOT NULL,
  `n_boletin` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `detalles` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha` date NOT NULL,
  `peso` decimal(10,3) NOT NULL,
  `brazo` decimal(10,3) NOT NULL,
  `momento` decimal(10,3) NOT NULL,
  `estatus` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `aviones`
--

INSERT INTO `aviones` (`id`, `cola`, `n_boletin`, `detalles`, `fecha`, `peso`, `brazo`, `momento`, `estatus`) VALUES
(1, 1010, 'PESO INICIAL AVION', '', '0000-00-00', 3272.250, 4.109, 13444.510, 1),
(2, 1022, '', '', '0000-00-00', 3253.250, 4.082, 13281.250, 1),
(1, 1010, 'PESO INICIAL AVION', '', '0000-00-00', 3272.250, 4.109, 13444.510, 1),
(2, 1022, '', '', '0000-00-00', 3253.250, 4.082, 13281.250, 1),
(1, 1010, 'PESO INICIAL AVION', '', '0000-00-00', 3272.250, 4.109, 13444.510, 1),
(2, 1022, '', '', '0000-00-00', 3253.250, 4.082, 13281.250, 1),
(1, 1010, 'PESO INICIAL AVION', '', '0000-00-00', 3272.250, 4.109, 13444.510, 1),
(2, 1022, '', '', '0000-00-00', 3253.250, 4.082, 13281.250, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `boletines`
--

DROP TABLE IF EXISTS `boletines`;
CREATE TABLE IF NOT EXISTS `boletines` (
  `id` int NOT NULL,
  `avion` int NOT NULL,
  `n_boletin` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha` date NOT NULL,
  `detalles` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `peso` decimal(10,3) NOT NULL,
  `brazo` decimal(10,3) NOT NULL,
  `momento` decimal(10,3) NOT NULL,
  `estatus` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `boletines`
--

INSERT INTO `boletines` (`id`, `avion`, `n_boletin`, `fecha`, `detalles`, `peso`, `brazo`, `momento`, `estatus`) VALUES
(1, 1010, 'BALLAST', '2010-01-15', 'asdasdasdfasdf', 9.650, 0.945, 9.120, 1),
(2, 1010, 'BALLAST', '2010-01-15', 'asd', 14.090, 1.000, 14.090, 1),
(3, 1010, 'BALLAST', '2010-01-15', 'asd', 1.650, 1.000, 1.650, 1),
(4, 1010, 'SB314STD-53-0002-00', '2011-10-20', 'asd', 6.810, 3.724, 25.360, 1),
(5, 1010, 'SB314STD-27-0008-02', '2014-12-05', 'asd', 0.156, 3.141, 0.490, 2),
(9, 1022, 'asdasdasd', '2021-06-18', 'asdasdlkmasv					\r\n				', 2.000, 3.000, 4.000, 1),
(10, 1010, 'SB314ECU-27-0002', '2011-10-28', '					\r\n				', 0.120, 5.167, 0.620, 1),
(11, 1010, 'SB314STD-32-0002-02', '2011-10-28', '					\r\n				', 1.700, 4.859, 8.260, 1),
(12, 1010, 'SB314STD-53-0004-00', '2011-10-28', '					\r\n				', 3.600, 1.000, 3.600, 1),
(13, 1010, 'SB314STD-97-0002-01', '2011-10-28', '					\r\n				', 0.160, 6.188, 0.990, 1),
(14, 1010, 'SB314STD-97-0002-02', '2012-01-16', '					\r\n				', 1.160, 1.716, 1.990, 1),
(15, 1010, 'SB314STD-27-0004-03 REV. 3 PART I', '2013-02-04', '					\r\n				', 0.053, 4.151, 0.220, 1),
(16, 1010, 'SB314STD-27-0004-03 REV. 3 PART II', '2013-02-13', '					\r\n				', 0.080, 4.000, 0.320, 1),
(17, 1010, 'SB314STD-35-0001-03', '2014-10-23', '					\r\n				', 1.851, 6.958, 12.880, 1),
(18, 1010, 'SB314STD-27-0005-02', '2014-12-09', '					\r\n				', 0.719, 4.200, 3.020, 1),
(19, 1010, 'SB314STD-32-0010-01', '2015-07-17', '					\r\n				', 0.077, 4.416, 0.340, 1),
(20, 1010, 'SB314STD-28-0004-00', '2016-06-16', '					\r\n				', 0.055, 4.000, 0.220, 1),
(21, 1010, 'SB314STD-27-0006-01', '2016-06-22', '					\r\n				', 0.291, 10.172, 2.960, 1),
(22, 1010, 'SB314STD-28-0017-02', '2019-03-14', '					\r\n				', 0.004, 5.000, 0.020, 2),
(23, 1010, 'SB314ECU-55-0003-00', '2019-04-10', '					\r\n				', 0.270, 10.296, 2.780, 1),
(24, 1010, 'SB314STD-32-0011-01', '2019-04-11', '					\r\n				', 1.310, 3.916, 5.130, 1),
(25, 1010, 'SB314ECU-55-0004-00', '2019-04-11', '					\r\n				', 0.040, 10.750, 0.430, 1),
(26, 1010, 'SB314STD-95-0001-03', '2019-04-11', '					\r\n				', 0.834, 4.400, 3.670, 1),
(27, 1010, 'SB314STD-33-0004-00', '2020-01-07', '					\r\n				', 0.003, 4.000, 0.012, 2),
(28, 1010, 'SB314STD-34-0001-01', '2020-07-22', '					\r\n				', 13.811, 6.228, 86.010, 1),
(1, 1010, 'BALLAST', '2010-01-15', 'asdasdasdfasdf', 9.650, 0.945, 9.120, 1),
(2, 1010, 'BALLAST', '2010-01-15', 'asd', 14.090, 1.000, 14.090, 1),
(3, 1010, 'BALLAST', '2010-01-15', 'asd', 1.650, 1.000, 1.650, 1),
(4, 1010, 'SB314STD-53-0002-00', '2011-10-20', 'asd', 6.810, 3.724, 25.360, 1),
(5, 1010, 'SB314STD-27-0008-02', '2014-12-05', 'asd', 0.156, 3.141, 0.490, 2),
(9, 1022, 'asdasdasd', '2021-06-18', 'asdasdlkmasv					\r\n				', 2.000, 3.000, 4.000, 1),
(10, 1010, 'SB314ECU-27-0002', '2011-10-28', '					\r\n				', 0.120, 5.167, 0.620, 1),
(11, 1010, 'SB314STD-32-0002-02', '2011-10-28', '					\r\n				', 1.700, 4.859, 8.260, 1),
(12, 1010, 'SB314STD-53-0004-00', '2011-10-28', '					\r\n				', 3.600, 1.000, 3.600, 1),
(13, 1010, 'SB314STD-97-0002-01', '2011-10-28', '					\r\n				', 0.160, 6.188, 0.990, 1),
(14, 1010, 'SB314STD-97-0002-02', '2012-01-16', '					\r\n				', 1.160, 1.716, 1.990, 1),
(15, 1010, 'SB314STD-27-0004-03 REV. 3 PART I', '2013-02-04', '					\r\n				', 0.053, 4.151, 0.220, 1),
(16, 1010, 'SB314STD-27-0004-03 REV. 3 PART II', '2013-02-13', '					\r\n				', 0.080, 4.000, 0.320, 1),
(17, 1010, 'SB314STD-35-0001-03', '2014-10-23', '					\r\n				', 1.851, 6.958, 12.880, 1),
(18, 1010, 'SB314STD-27-0005-02', '2014-12-09', '					\r\n				', 0.719, 4.200, 3.020, 1),
(19, 1010, 'SB314STD-32-0010-01', '2015-07-17', '					\r\n				', 0.077, 4.416, 0.340, 1),
(20, 1010, 'SB314STD-28-0004-00', '2016-06-16', '					\r\n				', 0.055, 4.000, 0.220, 1),
(21, 1010, 'SB314STD-27-0006-01', '2016-06-22', '					\r\n				', 0.291, 10.172, 2.960, 1),
(22, 1010, 'SB314STD-28-0017-02', '2019-03-14', '					\r\n				', 0.004, 5.000, 0.020, 2),
(23, 1010, 'SB314ECU-55-0003-00', '2019-04-10', '					\r\n				', 0.270, 10.296, 2.780, 1),
(24, 1010, 'SB314STD-32-0011-01', '2019-04-11', '					\r\n				', 1.310, 3.916, 5.130, 1),
(25, 1010, 'SB314ECU-55-0004-00', '2019-04-11', '					\r\n				', 0.040, 10.750, 0.430, 1),
(26, 1010, 'SB314STD-95-0001-03', '2019-04-11', '					\r\n				', 0.834, 4.400, 3.670, 1),
(27, 1010, 'SB314STD-33-0004-00', '2020-01-07', '					\r\n				', 0.003, 4.000, 0.012, 2),
(28, 1010, 'SB314STD-34-0001-01', '2020-07-22', '					\r\n				', 13.811, 6.228, 86.010, 1),
(1, 1010, 'BALLAST', '2010-01-15', 'asdasdasdfasdf', 9.650, 0.945, 9.120, 1),
(2, 1010, 'BALLAST', '2010-01-15', 'asd', 14.090, 1.000, 14.090, 1),
(3, 1010, 'BALLAST', '2010-01-15', 'asd', 1.650, 1.000, 1.650, 1),
(4, 1010, 'SB314STD-53-0002-00', '2011-10-20', 'asd', 6.810, 3.724, 25.360, 1),
(5, 1010, 'SB314STD-27-0008-02', '2014-12-05', 'asd', 0.156, 3.141, 0.490, 2),
(9, 1022, 'asdasdasd', '2021-06-18', 'asdasdlkmasv					\r\n				', 2.000, 3.000, 4.000, 1),
(10, 1010, 'SB314ECU-27-0002', '2011-10-28', '					\r\n				', 0.120, 5.167, 0.620, 1),
(11, 1010, 'SB314STD-32-0002-02', '2011-10-28', '					\r\n				', 1.700, 4.859, 8.260, 1),
(12, 1010, 'SB314STD-53-0004-00', '2011-10-28', '					\r\n				', 3.600, 1.000, 3.600, 1),
(13, 1010, 'SB314STD-97-0002-01', '2011-10-28', '					\r\n				', 0.160, 6.188, 0.990, 1),
(14, 1010, 'SB314STD-97-0002-02', '2012-01-16', '					\r\n				', 1.160, 1.716, 1.990, 1),
(15, 1010, 'SB314STD-27-0004-03 REV. 3 PART I', '2013-02-04', '					\r\n				', 0.053, 4.151, 0.220, 1),
(16, 1010, 'SB314STD-27-0004-03 REV. 3 PART II', '2013-02-13', '					\r\n				', 0.080, 4.000, 0.320, 1),
(17, 1010, 'SB314STD-35-0001-03', '2014-10-23', '					\r\n				', 1.851, 6.958, 12.880, 1),
(18, 1010, 'SB314STD-27-0005-02', '2014-12-09', '					\r\n				', 0.719, 4.200, 3.020, 1),
(19, 1010, 'SB314STD-32-0010-01', '2015-07-17', '					\r\n				', 0.077, 4.416, 0.340, 1),
(20, 1010, 'SB314STD-28-0004-00', '2016-06-16', '					\r\n				', 0.055, 4.000, 0.220, 1),
(21, 1010, 'SB314STD-27-0006-01', '2016-06-22', '					\r\n				', 0.291, 10.172, 2.960, 1),
(22, 1010, 'SB314STD-28-0017-02', '2019-03-14', '					\r\n				', 0.004, 5.000, 0.020, 2),
(23, 1010, 'SB314ECU-55-0003-00', '2019-04-10', '					\r\n				', 0.270, 10.296, 2.780, 1),
(24, 1010, 'SB314STD-32-0011-01', '2019-04-11', '					\r\n				', 1.310, 3.916, 5.130, 1),
(25, 1010, 'SB314ECU-55-0004-00', '2019-04-11', '					\r\n				', 0.040, 10.750, 0.430, 1),
(26, 1010, 'SB314STD-95-0001-03', '2019-04-11', '					\r\n				', 0.834, 4.400, 3.670, 1),
(27, 1010, 'SB314STD-33-0004-00', '2020-01-07', '					\r\n				', 0.003, 4.000, 0.012, 2),
(28, 1010, 'SB314STD-34-0001-01', '2020-07-22', '					\r\n				', 13.811, 6.228, 86.010, 1),
(1, 1010, 'BALLAST', '2010-01-15', 'asdasdasdfasdf', 9.650, 0.945, 9.120, 1),
(2, 1010, 'BALLAST', '2010-01-15', 'asd', 14.090, 1.000, 14.090, 1),
(3, 1010, 'BALLAST', '2010-01-15', 'asd', 1.650, 1.000, 1.650, 1),
(4, 1010, 'SB314STD-53-0002-00', '2011-10-20', 'asd', 6.810, 3.724, 25.360, 1),
(5, 1010, 'SB314STD-27-0008-02', '2014-12-05', 'asd', 0.156, 3.141, 0.490, 2),
(9, 1022, 'asdasdasd', '2021-06-18', 'asdasdlkmasv					\r\n				', 2.000, 3.000, 4.000, 1),
(10, 1010, 'SB314ECU-27-0002', '2011-10-28', '					\r\n				', 0.120, 5.167, 0.620, 1),
(11, 1010, 'SB314STD-32-0002-02', '2011-10-28', '					\r\n				', 1.700, 4.859, 8.260, 1),
(12, 1010, 'SB314STD-53-0004-00', '2011-10-28', '					\r\n				', 3.600, 1.000, 3.600, 1),
(13, 1010, 'SB314STD-97-0002-01', '2011-10-28', '					\r\n				', 0.160, 6.188, 0.990, 1),
(14, 1010, 'SB314STD-97-0002-02', '2012-01-16', '					\r\n				', 1.160, 1.716, 1.990, 1),
(15, 1010, 'SB314STD-27-0004-03 REV. 3 PART I', '2013-02-04', '					\r\n				', 0.053, 4.151, 0.220, 1),
(16, 1010, 'SB314STD-27-0004-03 REV. 3 PART II', '2013-02-13', '					\r\n				', 0.080, 4.000, 0.320, 1),
(17, 1010, 'SB314STD-35-0001-03', '2014-10-23', '					\r\n				', 1.851, 6.958, 12.880, 1),
(18, 1010, 'SB314STD-27-0005-02', '2014-12-09', '					\r\n				', 0.719, 4.200, 3.020, 1),
(19, 1010, 'SB314STD-32-0010-01', '2015-07-17', '					\r\n				', 0.077, 4.416, 0.340, 1),
(20, 1010, 'SB314STD-28-0004-00', '2016-06-16', '					\r\n				', 0.055, 4.000, 0.220, 1),
(21, 1010, 'SB314STD-27-0006-01', '2016-06-22', '					\r\n				', 0.291, 10.172, 2.960, 1),
(22, 1010, 'SB314STD-28-0017-02', '2019-03-14', '					\r\n				', 0.004, 5.000, 0.020, 2),
(23, 1010, 'SB314ECU-55-0003-00', '2019-04-10', '					\r\n				', 0.270, 10.296, 2.780, 1),
(24, 1010, 'SB314STD-32-0011-01', '2019-04-11', '					\r\n				', 1.310, 3.916, 5.130, 1),
(25, 1010, 'SB314ECU-55-0004-00', '2019-04-11', '					\r\n				', 0.040, 10.750, 0.430, 1),
(26, 1010, 'SB314STD-95-0001-03', '2019-04-11', '					\r\n				', 0.834, 4.400, 3.670, 1),
(27, 1010, 'SB314STD-33-0004-00', '2020-01-07', '					\r\n				', 0.003, 4.000, 0.012, 2),
(28, 1010, 'SB314STD-34-0001-01', '2020-07-22', '					\r\n				', 13.811, 6.228, 86.010, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cajas`
--

DROP TABLE IF EXISTS `cajas`;
CREATE TABLE IF NOT EXISTS `cajas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_caja` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `lugar` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estatus` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cajas`
--

INSERT INTO `cajas` (`id`, `id_caja`, `lugar`, `estatus`) VALUES
(1, 'abcd1234', 'BurguerBeer', 1),
(2, 'abcd', 'Cañalimeña', 2),
(3, '', 'Movil 1', 2),
(4, '', 'Movil 2', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calf_g`
--

DROP TABLE IF EXISTS `calf_g`;
CREATE TABLE IF NOT EXISTS `calf_g` (
  `id` int NOT NULL,
  `calificacion` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `calf_g`
--

INSERT INTO `calf_g` (`id`, `calificacion`) VALUES
(1, 'Bajo Promedio'),
(2, 'Promedio'),
(3, 'Sobre Promedio'),
(1, 'Bajo Promedio'),
(2, 'Promedio'),
(3, 'Sobre Promedio'),
(1, 'Bajo Promedio'),
(2, 'Promedio'),
(3, 'Sobre Promedio'),
(1, 'Bajo Promedio'),
(2, 'Promedio'),
(3, 'Sobre Promedio');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificacion`
--

DROP TABLE IF EXISTS `calificacion`;
CREATE TABLE IF NOT EXISTS `calificacion` (
  `id` bigint NOT NULL,
  `user_calificador` int NOT NULL,
  `user_calificado` int NOT NULL,
  `fecha` date NOT NULL,
  `mision` int NOT NULL,
  `c_general` int NOT NULL,
  `maniobra` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `criterio` int NOT NULL,
  `agresividad` int NOT NULL,
  `comprension` int NOT NULL,
  `conciencia` int NOT NULL,
  `comunicacion` int NOT NULL,
  `autocontrol` int NOT NULL,
  `n_autocontrol` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `recomendaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `dateadd` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estatus` int NOT NULL DEFAULT '1',
  `n_criterio` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `n_agresividad` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `n_comprension` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `n_conciencia` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `n_comunicacion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nuevo` int NOT NULL DEFAULT '1',
  `comentarios` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `f_aceptado` datetime NOT NULL,
  `sup` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `posicion` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `f_creado` datetime NOT NULL,
  `simope` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `calificacion`
--

INSERT INTO `calificacion` (`id`, `user_calificador`, `user_calificado`, `fecha`, `mision`, `c_general`, `maniobra`, `observaciones`, `criterio`, `agresividad`, `comprension`, `conciencia`, `comunicacion`, `autocontrol`, `n_autocontrol`, `recomendaciones`, `dateadd`, `estatus`, `n_criterio`, `n_agresividad`, `n_comprension`, `n_conciencia`, `n_comunicacion`, `nuevo`, `comentarios`, `f_aceptado`, `sup`, `posicion`, `f_creado`, `simope`) VALUES
(16, 1234, 401021746, '2021-03-11', 5, 1, 'NO HA TENIDO BUN CONTROL', 'SIGA CON  ÑEQUE', 2, 2, 2, 2, 2, 2, '', 'asdfasdfasdfasdf', '2021-03-10 17:22:36', 2, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(17, 1234, 602566473, '2021-03-16', 1, 2, '', '', 2, 2, 2, 2, 2, 2, '', '123124', '2021-03-16 19:34:42', 4, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(18, 401021746, 602566473, '2021-03-16', 1, 2, '', '', 2, 2, 2, 2, 2, 2, '', 'asdfasdfasdfasdf', '2021-03-16 19:37:47', 4, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(19, 401021746, 602566473, '2021-03-16', 1, 2, '', '', 2, 2, 2, 2, 2, 2, '', 'asdfasdfasdfasdf', '2021-03-16 19:37:49', 4, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(20, 1234, 602566473, '2021-03-24', 7, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-03-16 21:32:33', 4, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(21, 1234, 602566473, '2021-03-01', 2, 2, '', '', 2, 2, 2, 2, 2, 2, '', 'asdfasdf', '2021-03-22 21:17:52', 4, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(22, 1234, 401021746, '2021-03-03', 2, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-03-22 21:22:40', 4, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(23, 1234, 602566473, '2021-03-02', 4, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-03-22 21:23:08', 4, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(24, 1234, 602566473, '2021-05-01', 3, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-05-29 14:48:58', 4, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(25, 1234, 401021746, '2021-06-11', 2, 2, '', '', 2, 2, 2, 2, 2, 2, '', 'iusahdhfbuasuydf', '2021-06-16 14:49:38', 1, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(26, 602566473, 1234, '2021-08-16', 2, 1, 'BUEN CONTROL', 'SIGA', 2, 2, 2, 2, 2, 2, '', 'werwer', '2021-06-16 21:19:05', 2, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 2),
(27, 1234, 602566473, '2021-07-01', 1, 1, '1', '2', 1, 1, 1, 1, 1, 1, '8', '10', '2021-07-01 14:54:19', 2, '3', '4', '5', '6', '7', 2, '9', '2021-08-24 14:22:28', '1234', '', '0000-00-00 00:00:00', 1),
(28, 1234, 602566473, '2021-07-02', 2, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-07-01 15:02:03', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(29, 401021746, 1234, '2021-08-10', 2, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-08-10 10:00:24', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(30, 401021746, 1234, '2021-08-20', 1, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-08-19 17:50:31', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(31, 1234, 401021746, '2021-08-24', 1, 2, '', '', 1, 1, 1, 1, 1, 1, '6', '8', '2021-08-24 04:42:23', 1, '1', '2', '3', '4', '5', 2, '7', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(32, 1234, 602566473, '2021-08-24', 1, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-08-24 04:45:39', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(33, 401021746, 602566473, '2021-08-25', 1, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-08-25 21:47:16', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(34, 401021746, 602566473, '2021-08-25', 1, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-08-25 21:51:51', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', 'Lider', '2021-08-25 11:21:51', 1),
(35, 401021746, 602566473, '2021-09-08', 1, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-09-09 03:18:28', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', 'Solo', '2021-09-08 16:48:28', 1),
(16, 1234, 401021746, '2021-03-11', 5, 1, 'NO HA TENIDO BUN CONTROL', 'SIGA CON  ÑEQUE', 2, 2, 2, 2, 2, 2, '', 'asdfasdfasdfasdf', '2021-03-10 17:22:36', 2, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(17, 1234, 602566473, '2021-03-16', 1, 2, '', '', 2, 2, 2, 2, 2, 2, '', '123124', '2021-03-16 19:34:42', 4, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(18, 401021746, 602566473, '2021-03-16', 1, 2, '', '', 2, 2, 2, 2, 2, 2, '', 'asdfasdfasdfasdf', '2021-03-16 19:37:47', 4, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(19, 401021746, 602566473, '2021-03-16', 1, 2, '', '', 2, 2, 2, 2, 2, 2, '', 'asdfasdfasdfasdf', '2021-03-16 19:37:49', 4, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(20, 1234, 602566473, '2021-03-24', 7, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-03-16 21:32:33', 4, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(21, 1234, 602566473, '2021-03-01', 2, 2, '', '', 2, 2, 2, 2, 2, 2, '', 'asdfasdf', '2021-03-22 21:17:52', 4, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(22, 1234, 401021746, '2021-03-03', 2, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-03-22 21:22:40', 4, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(23, 1234, 602566473, '2021-03-02', 4, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-03-22 21:23:08', 4, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(24, 1234, 602566473, '2021-05-01', 3, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-05-29 14:48:58', 4, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(25, 1234, 401021746, '2021-06-11', 2, 2, '', '', 2, 2, 2, 2, 2, 2, '', 'iusahdhfbuasuydf', '2021-06-16 14:49:38', 1, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(26, 602566473, 1234, '2021-08-16', 2, 1, 'BUEN CONTROL', 'SIGA', 2, 2, 2, 2, 2, 2, '', 'werwer', '2021-06-16 21:19:05', 2, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 2),
(27, 1234, 602566473, '2021-07-01', 1, 1, '1', '2', 1, 1, 1, 1, 1, 1, '8', '10', '2021-07-01 14:54:19', 2, '3', '4', '5', '6', '7', 2, '9', '2021-08-24 14:22:28', '1234', '', '0000-00-00 00:00:00', 1),
(28, 1234, 602566473, '2021-07-02', 2, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-07-01 15:02:03', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(29, 401021746, 1234, '2021-08-10', 2, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-08-10 10:00:24', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(30, 401021746, 1234, '2021-08-20', 1, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-08-19 17:50:31', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(31, 1234, 401021746, '2021-08-24', 1, 2, '', '', 1, 1, 1, 1, 1, 1, '6', '8', '2021-08-24 04:42:23', 1, '1', '2', '3', '4', '5', 2, '7', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(32, 1234, 602566473, '2021-08-24', 1, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-08-24 04:45:39', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(33, 401021746, 602566473, '2021-08-25', 1, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-08-25 21:47:16', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(34, 401021746, 602566473, '2021-08-25', 1, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-08-25 21:51:51', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', 'Lider', '2021-08-25 11:21:51', 1),
(35, 401021746, 602566473, '2021-09-08', 1, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-09-09 03:18:28', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', 'Solo', '2021-09-08 16:48:28', 1),
(16, 1234, 401021746, '2021-03-11', 5, 1, 'NO HA TENIDO BUN CONTROL', 'SIGA CON  ÑEQUE', 2, 2, 2, 2, 2, 2, '', 'asdfasdfasdfasdf', '2021-03-10 17:22:36', 2, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(17, 1234, 602566473, '2021-03-16', 1, 2, '', '', 2, 2, 2, 2, 2, 2, '', '123124', '2021-03-16 19:34:42', 4, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(18, 401021746, 602566473, '2021-03-16', 1, 2, '', '', 2, 2, 2, 2, 2, 2, '', 'asdfasdfasdfasdf', '2021-03-16 19:37:47', 4, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(19, 401021746, 602566473, '2021-03-16', 1, 2, '', '', 2, 2, 2, 2, 2, 2, '', 'asdfasdfasdfasdf', '2021-03-16 19:37:49', 4, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(20, 1234, 602566473, '2021-03-24', 7, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-03-16 21:32:33', 4, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(21, 1234, 602566473, '2021-03-01', 2, 2, '', '', 2, 2, 2, 2, 2, 2, '', 'asdfasdf', '2021-03-22 21:17:52', 4, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(22, 1234, 401021746, '2021-03-03', 2, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-03-22 21:22:40', 4, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(23, 1234, 602566473, '2021-03-02', 4, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-03-22 21:23:08', 4, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(24, 1234, 602566473, '2021-05-01', 3, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-05-29 14:48:58', 4, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(25, 1234, 401021746, '2021-06-11', 2, 2, '', '', 2, 2, 2, 2, 2, 2, '', 'iusahdhfbuasuydf', '2021-06-16 14:49:38', 1, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(26, 602566473, 1234, '2021-08-16', 2, 1, 'BUEN CONTROL', 'SIGA', 2, 2, 2, 2, 2, 2, '', 'werwer', '2021-06-16 21:19:05', 2, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 2),
(27, 1234, 602566473, '2021-07-01', 1, 1, '1', '2', 1, 1, 1, 1, 1, 1, '8', '10', '2021-07-01 14:54:19', 2, '3', '4', '5', '6', '7', 2, '9', '2021-08-24 14:22:28', '1234', '', '0000-00-00 00:00:00', 1),
(28, 1234, 602566473, '2021-07-02', 2, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-07-01 15:02:03', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(29, 401021746, 1234, '2021-08-10', 2, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-08-10 10:00:24', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(30, 401021746, 1234, '2021-08-20', 1, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-08-19 17:50:31', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(31, 1234, 401021746, '2021-08-24', 1, 2, '', '', 1, 1, 1, 1, 1, 1, '6', '8', '2021-08-24 04:42:23', 1, '1', '2', '3', '4', '5', 2, '7', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(32, 1234, 602566473, '2021-08-24', 1, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-08-24 04:45:39', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(33, 401021746, 602566473, '2021-08-25', 1, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-08-25 21:47:16', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(34, 401021746, 602566473, '2021-08-25', 1, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-08-25 21:51:51', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', 'Lider', '2021-08-25 11:21:51', 1),
(35, 401021746, 602566473, '2021-09-08', 1, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-09-09 03:18:28', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', 'Solo', '2021-09-08 16:48:28', 1),
(16, 1234, 401021746, '2021-03-11', 5, 1, 'NO HA TENIDO BUN CONTROL', 'SIGA CON  ÑEQUE', 2, 2, 2, 2, 2, 2, '', 'asdfasdfasdfasdf', '2021-03-10 17:22:36', 2, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(17, 1234, 602566473, '2021-03-16', 1, 2, '', '', 2, 2, 2, 2, 2, 2, '', '123124', '2021-03-16 19:34:42', 4, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(18, 401021746, 602566473, '2021-03-16', 1, 2, '', '', 2, 2, 2, 2, 2, 2, '', 'asdfasdfasdfasdf', '2021-03-16 19:37:47', 4, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(19, 401021746, 602566473, '2021-03-16', 1, 2, '', '', 2, 2, 2, 2, 2, 2, '', 'asdfasdfasdfasdf', '2021-03-16 19:37:49', 4, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(20, 1234, 602566473, '2021-03-24', 7, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-03-16 21:32:33', 4, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(21, 1234, 602566473, '2021-03-01', 2, 2, '', '', 2, 2, 2, 2, 2, 2, '', 'asdfasdf', '2021-03-22 21:17:52', 4, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(22, 1234, 401021746, '2021-03-03', 2, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-03-22 21:22:40', 4, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(23, 1234, 602566473, '2021-03-02', 4, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-03-22 21:23:08', 4, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(24, 1234, 602566473, '2021-05-01', 3, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-05-29 14:48:58', 4, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(25, 1234, 401021746, '2021-06-11', 2, 2, '', '', 2, 2, 2, 2, 2, 2, '', 'iusahdhfbuasuydf', '2021-06-16 14:49:38', 1, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(26, 602566473, 1234, '2021-08-16', 2, 1, 'BUEN CONTROL', 'SIGA', 2, 2, 2, 2, 2, 2, '', 'werwer', '2021-06-16 21:19:05', 2, '', '', '', '', '', 2, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 2),
(27, 1234, 602566473, '2021-07-01', 1, 1, '1', '2', 1, 1, 1, 1, 1, 1, '8', '10', '2021-07-01 14:54:19', 2, '3', '4', '5', '6', '7', 2, '9', '2021-08-24 14:22:28', '1234', '', '0000-00-00 00:00:00', 1),
(28, 1234, 602566473, '2021-07-02', 2, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-07-01 15:02:03', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(29, 401021746, 1234, '2021-08-10', 2, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-08-10 10:00:24', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(30, 401021746, 1234, '2021-08-20', 1, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-08-19 17:50:31', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(31, 1234, 401021746, '2021-08-24', 1, 2, '', '', 1, 1, 1, 1, 1, 1, '6', '8', '2021-08-24 04:42:23', 1, '1', '2', '3', '4', '5', 2, '7', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(32, 1234, 602566473, '2021-08-24', 1, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-08-24 04:45:39', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(33, 401021746, 602566473, '2021-08-25', 1, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-08-25 21:47:16', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', '', '0000-00-00 00:00:00', 1),
(34, 401021746, 602566473, '2021-08-25', 1, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-08-25 21:51:51', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', 'Lider', '2021-08-25 11:21:51', 1),
(35, 401021746, 602566473, '2021-09-08', 1, 0, '', '', 0, 0, 0, 0, 0, 0, '', '', '2021-09-09 03:18:28', 1, '', '', '', '', '', 1, '', '0000-00-00 00:00:00', '1234', 'Solo', '2021-09-08 16:48:28', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `capa`
--

DROP TABLE IF EXISTS `capa`;
CREATE TABLE IF NOT EXISTS `capa` (
  `id` int NOT NULL,
  `capa` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `capa`
--

INSERT INTO `capa` (`id`, `capa`) VALUES
(1, 'Primera'),
(2, 'Segunda'),
(3, 'Tercera'),
(4, 'Ametralladora'),
(5, 'Flir'),
(6, 'Combustible'),
(1, 'Primera'),
(2, 'Segunda'),
(3, 'Tercera'),
(4, 'Ametralladora'),
(5, 'Flir'),
(6, 'Combustible'),
(1, 'Primera'),
(2, 'Segunda'),
(3, 'Tercera'),
(4, 'Ametralladora'),
(5, 'Flir'),
(6, 'Combustible'),
(1, 'Primera'),
(2, 'Segunda'),
(3, 'Tercera'),
(4, 'Ametralladora'),
(5, 'Flir'),
(6, 'Combustible');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

DROP TABLE IF EXISTS `categorias`;
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `categoria` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `foto` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estatus` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `categoria`, `foto`, `estatus`) VALUES
(1, 'Hamburguesas', '', 1),
(2, 'Papas en cono', 'papasCono.jpg', 1),
(13, 'Entradas', '', 1),
(14, 'Ensaladas', 'ensalada.jpg', 1),
(15, 'Platos Fuertes', '', 1),
(16, 'Hot Dogs', '', 1),
(17, 'Alitas', 'alitas.jpg', 1),
(18, 'Sanduches', 'sanduches.jpg', 1),
(19, 'Postres', '', 1),
(20, 'Bebidas Frias', 'bebidas_frias.jpg', 1),
(21, 'Bebidas Calientes', '', 1),
(22, 'Bebidas Alcoholicas', '', 1),
(23, 'Desayunos', '', 1),
(24, 'Extras Desayunos', '', 1),
(25, 'Extra Burguer', '', 1),
(26, 'Desechables', '', 1),
(27, 'Envio a Domicilio', '', 1),
(36, 'Platos Grill', '', 1),
(37, 'Promociones', '', 1),
(38, 'VERDE', '', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

DROP TABLE IF EXISTS `clientes`;
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `p_apellido` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `s_apellido` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `correo_c` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `direccion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `dateadd` datetime NOT NULL,
  `estatus` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `usuario`, `nombre`, `p_apellido`, `s_apellido`, `correo_c`, `direccion`, `telefono`, `dateadd`, `estatus`) VALUES
(2, '1', 'Consumidor', 'Final', '', '', '', '', '2023-09-10 16:52:14', 1),
(3, '1803641420', 'Francis', 'Fiallos', '', 'asd@gmial.com', 'Espejo y  16 de dicembre', '0984452560', '0000-00-00 00:00:00', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `codigos_promocionales`
--

DROP TABLE IF EXISTS `codigos_promocionales`;
CREATE TABLE IF NOT EXISTS `codigos_promocionales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` int NOT NULL,
  `descripcion` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `porcentaje` int NOT NULL,
  `dinero` decimal(10,0) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estatus` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `codigos_promocionales`
--

INSERT INTO `codigos_promocionales` (`id`, `codigo`, `tipo`, `descripcion`, `porcentaje`, `dinero`, `fecha_inicio`, `fecha_fin`, `estatus`) VALUES
(1, 'NODESCUENT', 1, 'cero', 0, 0, '2023-09-04', '2023-09-07', 1),
(2, 'ABC12345', 1, '1 dolar', 0, 1, '2023-09-04', '2023-09-07', 1),
(3, 'ABC123456', 2, '10% DE DESCUENTO', 10, 0, '2023-09-04', '2023-09-07', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

DROP TABLE IF EXISTS `configuracion`;
CREATE TABLE IF NOT EXISTS `configuracion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nit` int NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `razon_social` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` int NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `direccion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `iva` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`id`, `nit`, `nombre`, `razon_social`, `telefono`, `email`, `direccion`, `iva`) VALUES
(1, 1801096106, 'Yolanda Silva', 'Burguer Beer', 984452560, 'francis_andre94@hotmail.com', 'Baños, Espejo y 16 de Diciembre', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `config_sistema`
--

DROP TABLE IF EXISTS `config_sistema`;
CREATE TABLE IF NOT EXISTS `config_sistema` (
  `id` int NOT NULL AUTO_INCREMENT,
  `permiso` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estatus` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `config_sistema`
--

INSERT INTO `config_sistema` (`id`, `permiso`, `estatus`) VALUES
(1, 'Actualización de Datos', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cualitativa`
--

DROP TABLE IF EXISTS `cualitativa`;
CREATE TABLE IF NOT EXISTS `cualitativa` (
  `id` int NOT NULL,
  `calificacion` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cualitativa`
--

INSERT INTO `cualitativa` (`id`, `calificacion`) VALUES
(1, 'Elevada'),
(2, 'Adecuada'),
(3, 'Deficiente'),
(1, 'Elevada'),
(2, 'Adecuada'),
(3, 'Deficiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dependencia`
--

DROP TABLE IF EXISTS `dependencia`;
CREATE TABLE IF NOT EXISTS `dependencia` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dependencia` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `dependencia`
--

INSERT INTO `dependencia` (`id`, `dependencia`) VALUES
(1, 'Administrador Full'),
(2, 'Rancho'),
(3, 'Gimnasio'),
(4, 'TOSS'),
(5, 'Academicas'),
(6, 'SIN'),
(7, 'Piscina'),
(8, 'Administrador Full'),
(9, 'Usuario'),
(10, 'Gimnasio'),
(11, 'TOSS'),
(12, 'Academicas'),
(13, 'SIN'),
(14, 'Piscina');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dependencia_ala`
--

DROP TABLE IF EXISTS `dependencia_ala`;
CREATE TABLE IF NOT EXISTS `dependencia_ala` (
  `id` int NOT NULL AUTO_INCREMENT,
  `d_ala` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `dependencia_ala`
--

INSERT INTO `dependencia_ala` (`id`, `d_ala`) VALUES
(1, 'Comando14'),
(2, 'Inteligencia1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_credito`
--

DROP TABLE IF EXISTS `detalle_credito`;
CREATE TABLE IF NOT EXISTS `detalle_credito` (
  `correlativo` bigint NOT NULL AUTO_INCREMENT,
  `nofactura` bigint DEFAULT NULL,
  `precio_venta` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`correlativo`),
  KEY `nofactura` (`nofactura`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_credito`
--

INSERT INTO `detalle_credito` (`correlativo`, `nofactura`, `precio_venta`) VALUES
(1, 2, 5.00),
(2, 3, 10.00),
(3, 4, 5.00),
(4, 5, 4.00),
(5, 6, 4.00),
(6, 9, 10.00),
(7, 10, 4.00),
(8, 11, 1.00),
(9, 12, 20.00),
(10, 13, 19.00),
(11, 14, 1.00),
(12, 15, 1.00),
(13, 16, 10.00),
(14, 17, 10.00),
(15, 18, 100.00),
(16, 19, 100.00),
(17, 20, 10.00),
(18, 21, 10.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_factura`
--

DROP TABLE IF EXISTS `detalle_factura`;
CREATE TABLE IF NOT EXISTS `detalle_factura` (
  `correlativo` bigint NOT NULL AUTO_INCREMENT,
  `nofactura` bigint DEFAULT NULL,
  `codproducto` int DEFAULT NULL,
  `cantidad` int DEFAULT NULL,
  `precio_venta` decimal(10,2) DEFAULT NULL,
  `mesa` int NOT NULL,
  `atributos` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estatus_dt` int NOT NULL DEFAULT '1',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`correlativo`)
) ENGINE=InnoDB AUTO_INCREMENT=5426 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_factura`
--

INSERT INTO `detalle_factura` (`correlativo`, `nofactura`, `codproducto`, `cantidad`, `precio_venta`, `mesa`, `atributos`, `estatus_dt`, `observaciones`) VALUES
(305, 302, 1, 1, 3.99, 0, '', 1, ''),
(306, 303, 4, 1, 5.50, 0, '', 1, ''),
(307, 303, 56, 1, 5.99, 0, '', 1, ''),
(309, 304, 23, 1, 3.99, 0, '', 1, ''),
(310, 304, 24, 1, 4.99, 0, '', 1, ''),
(311, 304, 25, 1, 4.99, 0, '', 1, ''),
(312, 304, 26, 1, 5.99, 0, '', 1, ''),
(313, 304, 28, 1, 3.50, 0, '', 1, ''),
(314, 304, 27, 1, 5.99, 0, '', 1, ''),
(315, 304, 1, 1, 3.99, 0, '', 1, ''),
(316, 305, 25, 1, 4.99, 0, '', 1, ''),
(317, 305, 24, 1, 4.99, 0, '', 1, ''),
(318, 305, 58, 1, 4.99, 0, '', 1, ''),
(319, 305, 64, 1, 0.99, 0, '', 1, ''),
(323, 306, 24, 1, 4.99, 0, '', 1, ''),
(324, 306, 56, 1, 5.99, 0, '', 1, ''),
(325, 306, 64, 1, 0.99, 0, '', 1, ''),
(326, 307, 2, 1, 4.99, 0, '', 1, ''),
(327, 307, 39, 1, 2.50, 0, '', 1, ''),
(329, 308, 7, 1, 3.50, 0, '', 1, ''),
(330, 308, 3, 1, 5.99, 0, '', 1, ''),
(331, 308, 8, 1, 2.99, 0, '', 1, ''),
(332, 309, 4, 1, 5.50, 0, '', 1, ''),
(333, 310, 4, 1, 5.50, 0, '', 1, ''),
(334, 311, 4, 1, 5.50, 0, '', 1, ''),
(335, 311, 1, 1, 3.99, 0, '', 1, ''),
(336, 311, 4, 1, 5.50, 0, '', 1, ''),
(337, 312, 4, 1, 5.50, 0, '', 1, ''),
(338, 313, 24, 1, 4.99, 0, '', 1, ''),
(339, 313, 42, 1, 3.99, 0, '', 1, ''),
(340, 313, 64, 1, 0.99, 0, '', 1, ''),
(341, 314, 6, 1, 3.99, 0, '', 1, ''),
(342, 314, 21, 1, 2.50, 0, '', 1, ''),
(343, 314, 28, 1, 3.50, 0, '', 1, ''),
(344, 315, 23, 1, 3.99, 0, '', 1, ''),
(345, 315, 12, 1, 1.99, 0, '', 1, ''),
(346, 315, 12, 1, 1.99, 0, '', 1, ''),
(347, 315, 12, 1, 1.99, 0, '', 1, ''),
(348, 315, 12, 1, 1.99, 0, '', 1, ''),
(349, 315, 12, 1, 1.99, 0, '', 1, ''),
(350, 315, 11, 1, 0.99, 0, '', 1, ''),
(351, 315, 11, 1, 0.99, 0, '', 1, ''),
(352, 315, 10, 1, 0.99, 0, '', 1, ''),
(353, 315, 10, 1, 0.99, 0, '', 1, ''),
(354, 315, 10, 1, 0.99, 0, '', 1, ''),
(355, 315, 10, 1, 0.99, 0, '', 1, ''),
(356, 315, 9, 1, 0.99, 0, '', 1, ''),
(357, 315, 9, 1, 0.99, 0, '', 1, ''),
(358, 315, 7, 1, 3.50, 0, '', 1, ''),
(359, 315, 7, 1, 3.50, 0, '', 1, ''),
(360, 315, 7, 1, 3.50, 0, '', 1, ''),
(361, 315, 8, 1, 2.99, 0, '', 1, ''),
(362, 315, 8, 1, 2.99, 0, '', 1, ''),
(363, 315, 10, 1, 0.99, 0, '', 1, ''),
(364, 315, 10, 1, 0.99, 0, '', 1, ''),
(365, 315, 11, 1, 0.99, 0, '', 1, ''),
(366, 315, 34, 1, 1.50, 0, '', 1, ''),
(367, 315, 34, 1, 1.50, 0, '', 1, ''),
(368, 315, 35, 1, 1.50, 0, '', 1, ''),
(369, 315, 35, 1, 1.50, 0, '', 1, ''),
(370, 315, 45, 1, 3.99, 0, '', 1, ''),
(371, 315, 45, 1, 3.99, 0, '', 1, ''),
(372, 315, 39, 1, 2.50, 0, '', 1, ''),
(373, 315, 39, 1, 2.50, 0, '', 1, ''),
(374, 315, 40, 1, 2.99, 0, '', 1, ''),
(375, 315, 40, 1, 2.99, 0, '', 1, ''),
(376, 315, 46, 1, 3.99, 0, '', 1, ''),
(377, 315, 46, 1, 3.99, 0, '', 1, ''),
(378, 315, 47, 1, 3.50, 0, '', 1, ''),
(379, 315, 47, 1, 3.50, 0, '', 1, ''),
(380, 315, 47, 1, 3.50, 0, '', 1, ''),
(381, 315, 46, 1, 3.99, 0, '', 1, ''),
(382, 315, 44, 1, 4.99, 0, '', 1, ''),
(383, 315, 44, 1, 4.99, 0, '', 1, ''),
(384, 315, 44, 1, 4.99, 0, '', 1, ''),
(385, 315, 58, 1, 4.99, 0, '', 1, ''),
(386, 315, 57, 1, 4.99, 0, '', 1, ''),
(387, 315, 59, 1, 2.99, 0, '', 1, ''),
(388, 315, 59, 1, 2.99, 0, '', 1, ''),
(389, 315, 59, 1, 2.99, 0, '', 1, ''),
(390, 315, 56, 1, 5.99, 0, '', 1, ''),
(391, 315, 56, 1, 5.99, 0, '', 1, ''),
(392, 315, 55, 1, 27.99, 0, '', 1, ''),
(393, 315, 55, 1, 27.99, 0, '', 1, ''),
(394, 315, 65, 1, 1.99, 0, '', 1, ''),
(395, 315, 65, 1, 1.99, 0, '', 1, ''),
(396, 315, 64, 1, 0.99, 0, '', 1, ''),
(397, 315, 64, 1, 0.99, 0, '', 1, ''),
(398, 315, 64, 1, 0.99, 0, '', 1, ''),
(399, 315, 63, 1, 2.50, 0, '', 1, ''),
(400, 315, 63, 1, 2.50, 0, '', 1, ''),
(401, 315, 58, 1, 4.99, 0, '', 1, ''),
(407, 316, 1, 1, 3.99, 0, '', 1, ''),
(408, 316, 48, 1, 2.99, 0, '', 1, ''),
(409, 316, 59, 1, 2.99, 0, '', 1, ''),
(410, 316, 6, 1, 3.99, 0, '', 1, ''),
(414, 317, 1, 1, 3.99, 0, '', 1, ''),
(415, 318, 23, 1, 3.99, 0, '', 1, ''),
(416, 318, 39, 1, 2.50, 0, '', 1, ''),
(417, 319, 1, 1, 3.99, 0, '', 1, ''),
(418, 319, 2, 1, 4.99, 0, '', 1, ''),
(419, 319, 4, 1, 5.50, 0, '', 1, ''),
(420, 320, 7, 1, 3.50, 0, '', 1, ''),
(421, 320, 10, 1, 0.99, 0, '', 1, ''),
(422, 320, 9, 1, 0.99, 0, '', 1, ''),
(423, 321, 27, 1, 5.99, 0, '', 1, ''),
(424, 321, 42, 1, 3.99, 0, '', 1, ''),
(425, 321, 21, 1, 2.50, 0, '', 1, ''),
(426, 322, 66, 1, 1.25, 0, '', 1, ''),
(427, 323, 1, 1, 3.99, 0, '', 1, ''),
(428, 323, 8, 1, 2.99, 0, '', 1, ''),
(429, 323, 68, 1, 0.99, 0, '', 1, ''),
(430, 324, 1, 1, 3.99, 0, '', 1, ''),
(431, 324, 17, 1, 4.99, 0, '', 1, ''),
(432, 324, 55, 1, 25.99, 0, '', 1, ''),
(433, 325, 9, 1, 0.99, 0, '', 1, ''),
(434, 326, 16, 1, 4.99, 0, '', 1, ''),
(435, 326, 1, 1, 3.99, 0, '', 1, ''),
(436, 326, 2, 1, 4.99, 0, '', 1, ''),
(437, 326, 6, 1, 3.99, 0, '', 1, ''),
(438, 326, 6, 1, 3.99, 0, '', 1, ''),
(439, 326, 5, 1, 5.99, 0, '', 1, ''),
(440, 327, 1, 1, 3.99, 0, '', 1, ''),
(441, 328, 40, 1, 2.99, 0, '', 1, ''),
(442, 328, 25, 1, 4.99, 0, '', 1, ''),
(443, 328, 51, 1, 5.99, 0, '', 1, ''),
(444, 329, 1, 1, 3.99, 0, '', 1, ''),
(445, 329, 77, 1, 2.50, 0, '', 1, ''),
(446, 330, 1, 1, 3.99, 0, '', 1, ''),
(447, 331, 1, 1, 3.99, 0, '', 1, ''),
(448, 331, 1, 1, 3.99, 0, '', 1, ''),
(449, 331, 6, 1, 3.99, 0, '', 1, ''),
(450, 332, 42, 1, 3.99, 0, '', 1, ''),
(451, 332, 45, 1, 3.99, 0, '', 1, ''),
(452, 332, 50, 1, 3.50, 0, '', 1, ''),
(453, 332, 65, 1, 1.75, 0, '', 1, ''),
(457, 333, 51, 1, 5.99, 0, '', 1, ''),
(458, 333, 28, 1, 3.50, 0, '', 1, ''),
(460, 334, 39, 1, 2.50, 0, '', 1, ''),
(461, 334, 39, 1, 2.50, 0, '', 1, ''),
(462, 334, 39, 1, 2.50, 0, '', 1, ''),
(463, 334, 39, 1, 2.50, 0, '', 1, ''),
(467, 335, 42, 1, 3.99, 0, '', 1, ''),
(468, 335, 50, 1, 3.50, 0, '', 1, ''),
(469, 335, 23, 1, 3.99, 0, '', 1, ''),
(470, 335, 40, 1, 2.99, 0, '', 1, ''),
(471, 335, 78, 1, 1.50, 0, '', 1, ''),
(472, 335, 78, 1, 1.50, 0, '', 1, ''),
(473, 335, 78, 1, 1.50, 0, '', 1, ''),
(474, 335, 78, 1, 1.50, 0, '', 1, ''),
(475, 335, 78, 1, 1.50, 0, '', 1, ''),
(476, 335, 64, 1, 0.99, 0, '', 1, ''),
(477, 335, 95, 1, 0.99, 0, '', 1, ''),
(482, 336, 28, 1, 3.50, 0, '', 1, ''),
(483, 336, 28, 1, 3.50, 0, '', 1, ''),
(484, 336, 96, 1, 2.00, 0, '', 1, ''),
(485, 336, 71, 1, 1.99, 0, '', 1, ''),
(486, 336, 71, 1, 1.99, 0, '', 1, ''),
(487, 336, 27, 1, 5.99, 0, '', 1, ''),
(488, 336, 44, 1, 5.50, 0, '', 1, ''),
(489, 336, 81, 1, 1.25, 0, '', 1, ''),
(490, 336, 81, 1, 1.25, 0, '', 1, ''),
(491, 336, 81, 1, 1.25, 0, '', 1, ''),
(492, 336, 79, 1, 2.50, 0, '', 1, ''),
(493, 336, 79, 1, 2.50, 0, '', 1, ''),
(494, 336, 50, 1, 3.50, 0, '', 1, ''),
(495, 336, 26, 1, 5.99, 0, '', 1, ''),
(496, 336, 26, 1, 5.99, 0, '', 1, ''),
(497, 336, 24, 1, 4.99, 0, '', 1, ''),
(498, 336, 71, 1, 1.99, 0, '', 1, ''),
(499, 336, 97, 1, 3.50, 0, '', 1, ''),
(500, 336, 25, 1, 4.99, 0, '', 1, ''),
(501, 336, 25, 1, 4.99, 0, '', 1, ''),
(502, 336, 28, 1, 3.50, 0, '', 1, ''),
(503, 336, 28, 1, 3.50, 0, '', 1, ''),
(504, 336, 23, 1, 3.99, 0, '', 1, ''),
(505, 336, 71, 1, 1.99, 0, '', 1, ''),
(506, 336, 71, 1, 1.99, 0, '', 1, ''),
(507, 336, 71, 1, 1.99, 0, '', 1, ''),
(508, 336, 71, 1, 1.99, 0, '', 1, ''),
(513, 337, 51, 1, 5.99, 0, '', 1, ''),
(514, 337, 40, 1, 2.99, 0, '', 1, ''),
(515, 337, 46, 1, 3.99, 0, '', 1, ''),
(516, 338, 50, 1, 3.50, 0, '', 1, ''),
(517, 339, 26, 1, 5.99, 0, '', 1, ''),
(518, 339, 26, 1, 5.99, 0, '', 1, ''),
(519, 339, 24, 1, 4.99, 0, '', 1, ''),
(520, 339, 42, 1, 3.99, 0, '', 1, ''),
(521, 339, 25, 1, 4.99, 0, '', 1, ''),
(522, 339, 25, 1, 4.99, 0, '', 1, ''),
(523, 339, 50, 1, 3.50, 0, '', 1, ''),
(524, 339, 81, 1, 1.25, 0, '', 1, ''),
(525, 339, 79, 1, 2.50, 0, '', 1, ''),
(526, 339, 99, 1, 5.99, 0, '', 1, ''),
(527, 339, 100, 1, 4.99, 0, '', 1, ''),
(528, 340, 1, 1, 3.99, 0, '', 1, ''),
(529, 340, 78, 1, 1.50, 0, '', 1, ''),
(530, 340, 3, 1, 5.50, 0, '', 1, ''),
(531, 340, 3, 1, 5.50, 0, '', 1, ''),
(532, 340, 3, 1, 5.50, 0, '', 1, ''),
(533, 341, 9, 1, 0.99, 0, '', 1, ''),
(534, 341, 95, 1, 0.99, 0, '', 1, ''),
(535, 341, 4, 1, 4.99, 0, '', 1, ''),
(536, 342, 23, 1, 3.99, 0, '', 1, ''),
(537, 342, 25, 1, 4.99, 0, '', 1, ''),
(538, 342, 41, 1, 2.99, 0, '', 1, ''),
(539, 342, 61, 1, 1.50, 0, '', 1, ''),
(543, 343, 23, 1, 3.99, 0, '', 1, ''),
(544, 343, 41, 1, 2.99, 0, '', 1, ''),
(545, 343, 56, 1, 5.50, 0, '', 1, ''),
(546, 343, 71, 1, 1.99, 0, '', 1, ''),
(547, 343, 71, 1, 1.99, 0, '', 1, ''),
(550, 344, 25, 1, 4.99, 0, '', 1, ''),
(551, 344, 41, 1, 2.99, 0, '', 1, ''),
(552, 344, 50, 1, 3.50, 0, '', 1, ''),
(553, 344, 63, 1, 2.50, 0, '', 1, ''),
(557, 345, 24, 1, 4.99, 0, '', 1, ''),
(558, 345, 40, 1, 2.99, 0, '', 1, ''),
(559, 345, 57, 1, 3.99, 0, '', 1, ''),
(560, 345, 14, 1, 5.50, 0, '', 1, ''),
(561, 345, 62, 1, 1.99, 0, '', 1, ''),
(562, 345, 39, 1, 2.50, 0, '', 1, ''),
(564, 346, 1, 1, 3.99, 0, '', 1, ''),
(565, 346, 2, 1, 4.99, 0, '', 1, ''),
(566, 346, 3, 1, 5.50, 0, '', 1, ''),
(567, 346, 101, 1, 0.25, 0, '', 1, ''),
(568, 346, 9, 1, 0.99, 0, '', 1, ''),
(569, 346, 12, 1, 1.99, 0, '', 1, ''),
(571, 347, 39, 1, 2.50, 0, '', 1, ''),
(572, 347, 40, 1, 2.99, 0, '', 1, ''),
(573, 347, 45, 1, 3.99, 0, '', 1, ''),
(574, 347, 46, 1, 3.99, 0, '', 1, ''),
(575, 347, 79, 1, 2.50, 0, '', 1, ''),
(576, 347, 80, 1, 3.50, 0, '', 1, ''),
(578, 348, 23, 1, 3.99, 0, '', 1, ''),
(579, 348, 24, 1, 4.99, 0, '', 1, ''),
(580, 348, 27, 1, 5.99, 0, '', 1, ''),
(581, 348, 39, 1, 2.50, 0, '', 1, ''),
(582, 348, 44, 1, 5.50, 0, '', 1, ''),
(585, 349, 56, 1, 5.50, 0, '', 1, ''),
(586, 349, 48, 1, 2.99, 0, '', 1, ''),
(587, 349, 55, 1, 25.99, 0, '', 1, ''),
(588, 349, 62, 1, 1.99, 0, '', 1, ''),
(589, 349, 67, 1, 0.99, 0, '', 1, ''),
(590, 350, 50, 1, 3.50, 0, '', 1, ''),
(591, 350, 14, 1, 5.50, 0, '', 1, ''),
(592, 350, 81, 1, 1.25, 0, '', 1, ''),
(593, 350, 6, 1, 3.99, 0, '', 1, ''),
(594, 350, 51, 1, 5.99, 0, '', 1, ''),
(595, 350, 61, 1, 1.50, 0, '', 1, ''),
(596, 350, 62, 1, 1.99, 0, '', 1, ''),
(597, 351, 3, 1, 5.50, 0, '', 1, ''),
(598, 351, 2, 1, 4.99, 0, '', 1, ''),
(599, 351, 1, 1, 3.99, 0, '', 1, ''),
(600, 352, 50, 1, 3.50, 0, '', 1, ''),
(601, 353, 24, 1, 4.99, 0, '', 1, ''),
(602, 354, 28, 1, 3.50, 0, '', 1, ''),
(603, 354, 28, 1, 3.50, 0, '', 1, ''),
(604, 354, 65, 1, 1.75, 0, '', 1, ''),
(605, 355, 42, 1, 3.99, 0, '', 1, ''),
(606, 355, 43, 1, 3.99, 0, '', 1, ''),
(607, 355, 71, 1, 1.99, 0, '', 1, ''),
(608, 355, 74, 1, 1.50, 0, '', 1, ''),
(612, 356, 51, 1, 5.99, 0, '', 1, ''),
(613, 357, 64, 1, 0.99, 0, '', 1, ''),
(614, 358, 5, 1, 5.99, 0, '', 1, ''),
(615, 358, 5, 1, 5.99, 0, '', 1, ''),
(616, 358, 4, 1, 4.99, 0, '', 1, ''),
(617, 358, 5, 1, 5.99, 0, '', 1, ''),
(618, 359, 27, 1, 5.99, 0, '', 1, ''),
(619, 359, 23, 1, 3.99, 0, '', 1, ''),
(620, 359, 69, 1, 1.50, 0, '', 1, ''),
(621, 360, 51, 1, 5.99, 0, '', 1, ''),
(622, 360, 42, 1, 3.99, 0, '', 1, ''),
(623, 360, 71, 1, 1.99, 0, '', 1, ''),
(624, 360, 71, 1, 1.99, 0, '', 1, ''),
(628, 361, 28, 1, 3.50, 0, '', 1, ''),
(629, 361, 29, 1, 0.99, 0, '', 1, ''),
(630, 361, 40, 1, 2.99, 0, '', 1, ''),
(631, 361, 69, 1, 1.50, 0, '', 1, ''),
(632, 361, 69, 1, 1.50, 0, '', 1, ''),
(633, 361, 67, 1, 0.99, 0, '', 1, ''),
(634, 361, 45, 1, 3.99, 0, '', 1, ''),
(635, 362, 40, 1, 2.99, 0, '', 1, ''),
(636, 362, 64, 1, 0.99, 0, '', 1, ''),
(638, 363, 44, 1, 5.50, 0, '', 1, ''),
(639, 363, 44, 1, 5.50, 0, '', 1, ''),
(640, 363, 42, 1, 3.99, 0, '', 1, ''),
(641, 363, 65, 1, 1.75, 0, '', 1, ''),
(642, 363, 71, 1, 1.99, 0, '', 1, ''),
(645, 364, 25, 1, 4.99, 0, '', 1, ''),
(646, 364, 33, 1, 0.99, 0, '', 1, ''),
(647, 364, 27, 1, 5.99, 0, '', 1, ''),
(648, 364, 65, 1, 1.75, 0, '', 1, ''),
(652, 365, 15, 1, 5.50, 0, '', 1, ''),
(653, 365, 15, 1, 5.50, 0, '', 1, ''),
(654, 365, 77, 1, 2.50, 0, '', 1, ''),
(655, 365, 77, 1, 2.50, 0, '', 1, ''),
(659, 366, 24, 1, 4.99, 0, '', 1, ''),
(660, 366, 64, 1, 0.99, 0, '', 1, ''),
(661, 366, 51, 1, 5.99, 0, '', 1, ''),
(662, 366, 77, 1, 2.50, 0, '', 1, ''),
(663, 366, 39, 1, 2.50, 0, '', 1, ''),
(664, 366, 77, 1, 2.50, 0, '', 1, ''),
(665, 366, 79, 1, 2.50, 0, '', 1, ''),
(666, 366, 39, 1, 2.50, 0, '', 1, ''),
(667, 366, 71, 1, 1.99, 0, '', 1, ''),
(668, 366, 104, 1, 8.99, 0, '', 1, ''),
(674, 367, 27, 1, 5.99, 0, '', 1, ''),
(675, 367, 27, 1, 5.99, 0, '', 1, ''),
(677, 368, 25, 1, 4.99, 0, '', 1, ''),
(678, 368, 25, 1, 4.99, 0, '', 1, ''),
(679, 368, 25, 1, 4.99, 0, '', 1, ''),
(680, 368, 64, 1, 0.99, 0, '', 1, ''),
(681, 368, 64, 1, 0.99, 0, '', 1, ''),
(682, 368, 67, 1, 0.99, 0, '', 1, ''),
(683, 368, 68, 1, 0.99, 0, '', 1, ''),
(684, 369, 46, 1, 3.99, 0, '', 1, ''),
(685, 369, 26, 1, 5.99, 0, '', 1, ''),
(686, 369, 42, 1, 3.99, 0, '', 1, ''),
(687, 369, 105, 1, 5.99, 0, '', 1, ''),
(688, 369, 39, 1, 2.50, 0, '', 1, ''),
(689, 369, 41, 1, 2.99, 0, '', 1, ''),
(690, 369, 101, 1, 0.25, 0, '', 1, ''),
(691, 369, 101, 1, 0.25, 0, '', 1, ''),
(699, 370, 60, 1, 1.99, 0, '', 1, ''),
(700, 370, 101, 1, 0.25, 0, '', 1, ''),
(701, 370, 101, 1, 0.25, 0, '', 1, ''),
(702, 370, 101, 1, 0.25, 0, '', 1, ''),
(706, 371, 43, 1, 3.99, 0, '', 1, ''),
(707, 371, 27, 1, 5.99, 0, '', 1, ''),
(708, 371, 51, 1, 5.99, 0, '', 1, ''),
(709, 371, 61, 1, 1.50, 0, '', 1, ''),
(710, 371, 101, 1, 0.25, 0, '', 1, ''),
(713, 372, 42, 1, 3.99, 0, '', 1, ''),
(714, 372, 69, 1, 1.50, 0, '', 1, ''),
(716, 373, 42, 1, 3.99, 0, '', 1, ''),
(717, 373, 64, 1, 0.99, 0, '', 1, ''),
(719, 374, 40, 1, 2.99, 0, '', 1, ''),
(720, 374, 50, 1, 3.50, 0, '', 1, ''),
(721, 374, 42, 1, 3.99, 0, '', 1, ''),
(722, 374, 100, 1, 4.99, 0, '', 1, ''),
(723, 374, 42, 1, 3.99, 0, '', 1, ''),
(726, 375, 39, 1, 2.50, 0, '', 1, ''),
(727, 375, 27, 1, 5.99, 0, '', 1, ''),
(728, 375, 64, 1, 0.99, 0, '', 1, ''),
(729, 375, 67, 1, 0.99, 0, '', 1, ''),
(733, 376, 27, 1, 5.99, 0, '', 1, ''),
(734, 376, 27, 1, 5.99, 0, '', 1, ''),
(735, 376, 44, 1, 5.50, 0, '', 1, ''),
(736, 376, 44, 1, 5.50, 0, '', 1, ''),
(737, 376, 64, 1, 0.99, 0, '', 1, ''),
(738, 376, 71, 1, 1.99, 0, '', 1, ''),
(739, 376, 71, 1, 1.99, 0, '', 1, ''),
(740, 377, 96, 1, 2.00, 0, '', 1, ''),
(741, 378, 51, 1, 5.99, 0, '', 1, ''),
(742, 378, 51, 1, 5.99, 0, '', 1, ''),
(743, 378, 51, 1, 5.99, 0, '', 1, ''),
(744, 378, 64, 1, 0.99, 0, '', 1, ''),
(745, 378, 64, 1, 0.99, 0, '', 1, ''),
(746, 378, 64, 1, 0.99, 0, '', 1, ''),
(747, 378, 79, 1, 2.50, 0, '', 1, ''),
(748, 378, 81, 1, 1.25, 0, '', 1, ''),
(756, 379, 52, 1, 8.99, 0, '', 1, ''),
(757, 379, 58, 1, 4.50, 0, '', 1, ''),
(758, 379, 61, 1, 1.50, 0, '', 1, ''),
(759, 379, 71, 1, 1.99, 0, '', 1, ''),
(763, 380, 27, 1, 5.99, 0, '', 1, ''),
(764, 380, 51, 1, 5.99, 0, '', 1, ''),
(765, 380, 64, 1, 0.99, 0, '', 1, ''),
(766, 380, 67, 1, 0.99, 0, '', 1, ''),
(770, 381, 27, 1, 5.99, 0, '', 1, ''),
(771, 381, 23, 1, 3.99, 0, '', 1, ''),
(772, 381, 69, 1, 1.50, 0, '', 1, ''),
(773, 382, 51, 1, 5.99, 0, '', 1, ''),
(774, 382, 42, 1, 3.99, 0, '', 1, ''),
(775, 382, 71, 1, 1.99, 0, '', 1, ''),
(776, 382, 71, 1, 1.99, 0, '', 1, ''),
(780, 383, 77, 1, 2.50, 0, '', 1, ''),
(781, 383, 77, 1, 2.50, 0, '', 1, ''),
(783, 384, 50, 1, 3.50, 0, '', 1, ''),
(784, 384, 18, 1, 3.99, 0, '', 1, ''),
(785, 384, 66, 1, 1.25, 0, '', 1, ''),
(786, 385, 60, 1, 2.50, 0, '', 1, ''),
(787, 385, 64, 1, 0.99, 0, '', 1, ''),
(789, 386, 60, 1, 2.50, 0, '', 1, ''),
(790, 387, 42, 1, 3.99, 0, '', 1, ''),
(791, 388, 43, 1, 3.99, 0, '', 1, ''),
(792, 388, 66, 1, 1.25, 0, '', 1, ''),
(793, 388, 101, 1, 0.25, 0, '', 1, ''),
(794, 389, 5, 1, 5.99, 0, '', 1, ''),
(795, 389, 5, 1, 5.99, 0, '', 1, ''),
(796, 389, 4, 1, 4.99, 0, '', 1, ''),
(797, 390, 4, 1, 4.99, 0, '', 1, ''),
(798, 390, 4, 1, 4.99, 0, '', 1, ''),
(800, 391, 5, 1, 5.99, 0, '', 1, ''),
(801, 391, 5, 1, 5.99, 0, '', 1, ''),
(803, 392, 5, 1, 5.99, 0, '', 1, ''),
(804, 392, 5, 1, 5.99, 0, '', 1, ''),
(806, 393, 4, 1, 4.99, 0, '', 1, ''),
(807, 393, 4, 1, 4.99, 0, '', 1, ''),
(809, 394, 5, 1, 5.99, 0, '', 1, ''),
(810, 394, 5, 1, 5.99, 0, '', 1, ''),
(811, 394, 10, 1, 0.99, 0, '', 1, ''),
(812, 395, 5, 1, 5.99, 0, '', 1, ''),
(813, 395, 5, 1, 5.99, 0, '', 1, ''),
(815, 396, 5, 1, 5.99, 0, '', 1, ''),
(816, 396, 5, 1, 5.99, 0, '', 1, ''),
(818, 397, 5, 1, 5.99, 0, '', 1, ''),
(819, 398, 2, 1, 4.99, 0, '', 1, ''),
(820, 398, 1, 1, 3.99, 0, '', 1, ''),
(821, 398, 1, 1, 3.99, 0, '', 1, ''),
(822, 398, 3, 1, 5.50, 0, '', 1, ''),
(823, 398, 3, 1, 5.50, 0, '', 1, ''),
(824, 398, 3, 1, 5.50, 0, '', 1, ''),
(825, 398, 3, 1, 5.50, 0, '', 1, ''),
(826, 398, 4, 1, 4.99, 0, '', 1, ''),
(834, 399, 1, 1, 3.99, 0, '', 1, ''),
(835, 400, 1, 1, 3.99, 0, '', 1, ''),
(836, 400, 1, 1, 3.99, 0, '', 1, ''),
(838, 401, 4, 1, 4.99, 0, '', 1, ''),
(839, 401, 4, 1, 4.99, 0, '', 1, ''),
(841, 402, 5, 1, 5.99, 0, '', 1, 'Sin huevos'),
(842, 403, 4, 1, 4.99, 0, '', 1, ''),
(843, 403, 4, 1, 4.99, 0, '', 1, ''),
(844, 403, 4, 1, 4.99, 0, '', 1, ''),
(845, 403, 5, 1, 5.99, 0, '', 1, ''),
(846, 403, 5, 1, 5.99, 0, '', 1, ''),
(847, 403, 3, 1, 5.50, 0, '', 1, ''),
(849, 404, 2, 1, 4.99, 0, '', 1, ''),
(850, 404, 1, 1, 3.99, 0, '', 1, ''),
(851, 404, 6, 1, 3.99, 0, '', 1, ''),
(852, 404, 7, 1, 3.50, 0, '', 1, 'hdhdhhd'),
(856, 405, 42, 1, 3.99, 0, '', 1, ''),
(857, 405, 25, 1, 4.99, 0, '', 1, ''),
(858, 405, 65, 1, 1.75, 0, '', 1, ''),
(859, 406, 46, 1, 3.99, 0, '', 1, ''),
(860, 406, 42, 1, 3.99, 0, '', 1, ''),
(861, 406, 42, 1, 3.99, 0, '', 1, ''),
(862, 406, 27, 1, 5.99, 0, '', 1, ''),
(863, 406, 101, 1, 0.25, 0, '', 1, ''),
(864, 406, 101, 1, 0.25, 0, '', 1, ''),
(866, 407, 42, 1, 3.99, 0, '', 1, ''),
(867, 407, 71, 1, 1.99, 0, '', 1, ''),
(868, 407, 51, 1, 5.99, 0, '', 1, '3bbq , mostaza y miel'),
(869, 407, 44, 1, 5.50, 0, '', 1, ''),
(870, 407, 19, 1, 4.50, 0, '', 1, ''),
(873, 408, 58, 1, 4.50, 0, '', 1, ''),
(874, 408, 66, 1, 1.25, 0, '', 1, ''),
(876, 409, 25, 1, 4.99, 0, '', 1, 'sin pikles'),
(877, 409, 64, 1, 0.99, 0, '', 1, 'sprite'),
(879, 410, 65, 1, 1.75, 0, '', 1, ''),
(880, 411, 64, 1, 0.99, 0, '', 1, ''),
(881, 412, 51, 1, 5.99, 0, '', 1, 'bbq'),
(882, 412, 74, 1, 1.50, 0, '', 1, ''),
(883, 412, 74, 1, 1.50, 0, '', 1, ''),
(884, 413, 28, 1, 3.50, 0, '', 1, ''),
(885, 413, 39, 1, 2.50, 0, '', 1, ''),
(886, 413, 39, 1, 2.50, 0, '', 1, ''),
(887, 414, 67, 1, 0.99, 0, '', 1, ''),
(888, 415, 28, 1, 3.50, 0, '', 1, ''),
(889, 415, 41, 1, 2.99, 0, '', 1, ''),
(891, 416, 104, 1, 8.99, 0, '', 1, ''),
(892, 416, 78, 1, 1.50, 0, '', 1, ''),
(894, 417, 55, 1, 25.99, 0, '', 1, 'bbq ,mostaza y miel , maracura 10cada una'),
(895, 417, 25, 1, 4.99, 0, '', 1, ''),
(896, 417, 96, 1, 2.00, 0, '', 1, ''),
(897, 417, 25, 1, 4.99, 0, '', 1, ''),
(898, 417, 65, 1, 1.75, 0, '', 1, 'sprite'),
(901, 418, 44, 1, 5.50, 0, '', 1, ''),
(902, 418, 42, 1, 3.99, 0, '', 1, ''),
(903, 418, 27, 1, 5.99, 0, '', 1, ''),
(904, 418, 27, 1, 5.99, 0, '', 1, ''),
(905, 418, 27, 1, 5.99, 0, '', 1, ''),
(906, 418, 26, 1, 5.99, 0, '', 1, ''),
(907, 418, 65, 1, 1.75, 0, '', 1, ''),
(908, 418, 43, 1, 3.99, 0, '', 1, ''),
(909, 418, 14, 1, 5.50, 0, '', 1, ''),
(910, 418, 74, 1, 1.50, 0, '', 1, ''),
(911, 418, 69, 1, 1.50, 0, '', 1, ''),
(916, 419, 78, 1, 1.50, 0, '', 1, 'cafe en lehe'),
(917, 420, 78, 1, 1.50, 0, '', 1, 'cafe americano'),
(918, 421, 60, 1, 2.50, 0, '', 1, ''),
(919, 422, 65, 1, 1.75, 0, '', 1, ''),
(920, 423, 65, 1, 1.75, 0, '', 1, ''),
(921, 424, 101, 1, 0.25, 0, '', 1, ''),
(922, 425, 51, 1, 5.99, 0, '', 1, 'bbq'),
(923, 425, 64, 1, 0.99, 0, '', 1, 'coca cola'),
(925, 426, 51, 1, 5.99, 0, '', 1, '3bbq 3mostaza y miel'),
(926, 427, 27, 1, 5.99, 0, '', 1, ''),
(927, 428, 27, 1, 5.99, 0, '', 1, ''),
(928, 428, 52, 1, 8.99, 0, '', 1, '3 mostaza y miel 3 maracuya 3 bbq'),
(929, 428, 64, 1, 0.99, 0, '', 1, 'personal coca'),
(930, 429, 15, 1, 5.50, 0, '', 1, ''),
(931, 429, 77, 1, 2.50, 0, '', 1, ''),
(932, 429, 51, 1, 5.99, 0, '', 1, '3mostaza y miel 3maracuya'),
(933, 429, 79, 1, 2.50, 0, '', 1, ''),
(934, 429, 81, 1, 1.25, 0, '', 1, ''),
(935, 429, 39, 1, 2.50, 0, '', 1, 'sin bbq'),
(936, 429, 62, 1, 1.99, 0, '', 1, 'mora'),
(937, 430, 15, 1, 5.50, 0, '', 1, ''),
(938, 430, 77, 1, 2.50, 0, '', 1, ''),
(939, 430, 63, 1, 2.50, 0, '', 1, 'fresa'),
(940, 431, 41, 1, 2.99, 0, '', 1, ''),
(941, 432, 28, 1, 3.50, 0, '', 1, ''),
(942, 433, 25, 1, 4.99, 0, '', 1, ''),
(943, 433, 42, 1, 3.99, 0, '', 1, ''),
(945, 434, 42, 1, 3.99, 0, '', 1, ''),
(946, 435, 50, 1, 3.50, 0, '', 1, ''),
(947, 436, 40, 1, 2.99, 0, '', 1, ''),
(948, 436, 40, 1, 2.99, 0, '', 1, ''),
(950, 437, 51, 1, 5.99, 0, '', 1, '3mostaza y miek 3 bbq'),
(951, 438, 42, 1, 3.99, 0, '', 1, ''),
(952, 439, 51, 1, 5.99, 0, '', 1, '3 moataza y miel 3 bbq picante'),
(953, 439, 27, 1, 5.99, 0, '', 1, 'sin cebolla caramelizada'),
(954, 439, 64, 1, 0.99, 0, '', 1, 'fiora'),
(955, 439, 64, 1, 0.99, 0, '', 1, 'coca cola'),
(959, 440, 27, 1, 5.99, 0, '', 1, ''),
(960, 440, 27, 1, 5.99, 0, '', 1, ''),
(961, 440, 27, 1, 5.99, 0, '', 1, ''),
(962, 440, 27, 1, 5.99, 0, '', 1, ''),
(963, 440, 50, 1, 3.50, 0, '', 1, 'bbq'),
(964, 440, 46, 1, 3.99, 0, '', 1, 'picante'),
(965, 440, 50, 1, 3.50, 0, '', 1, 'bbq picante'),
(966, 440, 65, 1, 1.75, 0, '', 1, 'fuze tea'),
(967, 440, 67, 1, 0.99, 0, '', 1, ''),
(968, 440, 67, 1, 0.99, 0, '', 1, ''),
(969, 440, 64, 1, 0.99, 0, '', 1, 'coca'),
(974, 441, 43, 1, 3.99, 0, '', 1, ''),
(975, 441, 51, 1, 5.99, 0, '', 1, '3bbq 3 mostaza y miel'),
(976, 441, 64, 1, 0.99, 0, '', 1, 'sprite'),
(977, 441, 64, 1, 0.99, 0, '', 1, 'coca'),
(981, 442, 80, 1, 3.50, 0, '', 1, ''),
(982, 442, 80, 1, 3.50, 0, '', 1, ''),
(984, 443, 67, 1, 0.99, 0, '', 1, ''),
(985, 443, 67, 1, 0.99, 0, '', 1, ''),
(987, 444, 104, 1, 8.99, 0, '', 1, 'lomoo fino ....no pollo'),
(988, 444, 67, 1, 0.99, 0, '', 1, ''),
(989, 444, 69, 1, 1.50, 0, '', 1, ''),
(990, 445, 40, 1, 2.99, 0, '', 1, 'en plato'),
(991, 445, 67, 1, 0.99, 0, '', 1, ''),
(992, 445, 64, 1, 0.99, 0, '', 1, 'inka'),
(993, 445, 46, 1, 3.99, 0, '', 1, 'para llevar'),
(994, 445, 101, 1, 0.25, 0, '', 1, ''),
(995, 445, 68, 1, 0.99, 0, '', 1, ''),
(996, 445, 68, 1, 0.99, 0, '', 1, ''),
(997, 446, 27, 1, 5.99, 0, '', 1, ''),
(998, 446, 14, 1, 5.50, 0, '', 1, ''),
(999, 446, 69, 1, 1.50, 0, '', 1, ''),
(1000, 447, 64, 1, 0.99, 0, '', 1, ''),
(1001, 447, 66, 1, 1.25, 0, '', 1, ''),
(1002, 447, 66, 1, 1.25, 0, '', 1, ''),
(1003, 448, 43, 1, 3.99, 0, '', 1, ''),
(1004, 448, 42, 1, 3.99, 0, '', 1, ''),
(1005, 448, 40, 1, 2.99, 0, '', 1, 'plato'),
(1006, 448, 46, 1, 3.99, 0, '', 1, 'picante'),
(1007, 448, 25, 1, 4.99, 0, '', 1, ''),
(1008, 448, 25, 1, 4.99, 0, '', 1, ''),
(1009, 448, 25, 1, 4.99, 0, '', 1, ''),
(1010, 448, 27, 1, 5.99, 0, '', 1, ''),
(1011, 448, 28, 1, 3.50, 0, '', 1, ''),
(1012, 448, 44, 1, 5.50, 0, '', 1, ''),
(1013, 448, 65, 1, 1.75, 0, '', 1, 'coca'),
(1014, 448, 65, 1, 1.75, 0, '', 1, 'sprit'),
(1018, 449, 80, 1, 3.50, 0, '', 1, 'club'),
(1019, 449, 80, 1, 3.50, 0, '', 1, 'corona'),
(1021, 450, 44, 1, 5.50, 0, '', 1, ''),
(1022, 450, 65, 1, 1.75, 0, '', 1, ''),
(1024, 451, 28, 1, 3.50, 0, '', 1, ''),
(1025, 451, 71, 1, 1.99, 0, '', 1, ''),
(1026, 451, 71, 1, 1.99, 0, '', 1, ''),
(1027, 451, 40, 1, 2.99, 0, '', 1, ''),
(1028, 451, 60, 1, 2.50, 0, '', 1, ''),
(1029, 451, 64, 1, 0.99, 0, '', 1, ''),
(1030, 451, 64, 1, 0.99, 0, '', 1, ''),
(1031, 451, 64, 1, 0.99, 0, '', 1, ''),
(1039, 452, 27, 1, 5.99, 0, '', 1, ''),
(1040, 452, 40, 1, 2.99, 0, '', 1, ''),
(1041, 452, 64, 1, 0.99, 0, '', 1, ''),
(1042, 452, 64, 1, 0.99, 0, '', 1, ''),
(1043, 452, 71, 1, 1.99, 0, '', 1, ''),
(1044, 452, 71, 1, 1.99, 0, '', 1, ''),
(1046, 453, 45, 1, 3.99, 0, '', 1, 'suprema'),
(1047, 453, 39, 1, 2.50, 0, '', 1, ''),
(1048, 453, 66, 1, 1.25, 0, '', 1, ''),
(1049, 453, 66, 1, 1.25, 0, '', 1, ''),
(1053, 454, 43, 1, 3.99, 0, '', 1, ''),
(1054, 455, 25, 1, 4.99, 0, '', 1, ''),
(1055, 455, 39, 1, 2.50, 0, '', 1, 'en plato'),
(1056, 455, 64, 1, 0.99, 0, '', 1, 'coca'),
(1057, 456, 25, 1, 4.99, 0, '', 1, ''),
(1058, 456, 42, 1, 3.99, 0, '', 1, ''),
(1059, 456, 71, 1, 1.99, 0, '', 1, ''),
(1060, 456, 71, 1, 1.99, 0, '', 1, ''),
(1064, 457, 28, 1, 3.50, 0, '', 1, ''),
(1065, 458, 104, 1, 8.99, 0, '', 1, 'lomo fino cocido'),
(1066, 458, 67, 1, 0.99, 0, '', 1, ''),
(1067, 458, 66, 1, 1.25, 0, '', 1, ''),
(1068, 458, 51, 1, 5.99, 0, '', 1, '3bbq 3 mostaza y miel'),
(1072, 459, 25, 1, 4.99, 0, '', 1, 'sin vegetales'),
(1073, 459, 25, 1, 4.99, 0, '', 1, ''),
(1074, 459, 40, 1, 2.99, 0, '', 1, ''),
(1075, 459, 78, 1, 1.50, 0, '', 1, 'te aromatico'),
(1076, 459, 24, 1, 4.99, 0, '', 1, ''),
(1079, 460, 40, 1, 2.99, 0, '', 1, 'en plato'),
(1080, 460, 24, 1, 4.99, 0, '', 1, ''),
(1081, 460, 67, 1, 0.99, 0, '', 1, ''),
(1082, 460, 64, 1, 0.99, 0, '', 1, 'coca cola'),
(1086, 461, 28, 1, 3.50, 0, '', 1, ''),
(1087, 461, 64, 1, 0.99, 0, '', 1, ''),
(1089, 462, 23, 1, 3.99, 0, '', 1, ''),
(1090, 462, 64, 1, 0.99, 0, '', 1, 'coca'),
(1092, 463, 24, 1, 4.99, 0, '', 1, ''),
(1093, 463, 27, 1, 5.99, 0, '', 1, ''),
(1095, 464, 41, 1, 2.99, 0, '', 1, 'en plato'),
(1096, 464, 40, 1, 2.99, 0, '', 1, 'en plato'),
(1097, 464, 67, 1, 0.99, 0, '', 1, ''),
(1098, 465, 45, 1, 3.99, 0, '', 1, 'suprema de tocino'),
(1099, 465, 42, 1, 3.99, 0, '', 1, ''),
(1100, 465, 25, 1, 4.99, 0, '', 1, 'sin vegetales'),
(1101, 465, 67, 1, 0.99, 0, '', 1, ''),
(1102, 465, 65, 1, 1.75, 0, '', 1, 'coca'),
(1105, 466, 66, 1, 1.25, 0, '', 1, ''),
(1106, 466, 66, 1, 1.25, 0, '', 1, ''),
(1108, 467, 64, 1, 0.99, 0, '', 1, ''),
(1109, 468, 25, 1, 4.99, 0, '', 1, ''),
(1110, 469, 25, 1, 4.99, 0, '', 1, ''),
(1111, 469, 25, 1, 4.99, 0, '', 1, ''),
(1112, 469, 27, 1, 5.99, 0, '', 1, ''),
(1113, 469, 27, 1, 5.99, 0, '', 1, ''),
(1114, 469, 27, 1, 5.99, 0, '', 1, ''),
(1115, 469, 53, 1, 13.99, 0, '', 1, ''),
(1116, 469, 41, 1, 2.99, 0, '', 1, ''),
(1117, 469, 106, 1, 9.99, 0, '', 1, ''),
(1118, 469, 51, 1, 5.99, 0, '', 1, ''),
(1119, 469, 44, 1, 5.50, 0, '', 1, ''),
(1120, 469, 52, 1, 8.99, 0, '', 1, ''),
(1121, 469, 100, 1, 4.99, 0, '', 1, ''),
(1122, 469, 100, 1, 4.99, 0, '', 1, ''),
(1123, 469, 100, 1, 4.99, 0, '', 1, ''),
(1124, 469, 67, 1, 0.99, 0, '', 1, ''),
(1125, 470, 50, 1, 3.50, 0, '', 1, 'BBQ'),
(1126, 470, 44, 1, 5.50, 0, '', 1, ''),
(1127, 470, 45, 1, 3.99, 0, '', 1, ''),
(1128, 470, 105, 1, 5.99, 0, '', 1, ''),
(1132, 471, 42, 1, 3.99, 0, '', 1, ''),
(1133, 471, 101, 1, 0.25, 0, '', 1, ''),
(1135, 472, 66, 1, 1.25, 0, '', 1, ''),
(1136, 473, 44, 1, 5.50, 0, '', 1, ''),
(1137, 473, 44, 1, 5.50, 0, '', 1, ''),
(1138, 473, 25, 1, 4.99, 0, '', 1, ''),
(1139, 473, 25, 1, 4.99, 0, '', 1, ''),
(1143, 474, 101, 1, 0.25, 0, '', 1, ''),
(1144, 474, 101, 1, 0.25, 0, '', 1, ''),
(1145, 474, 101, 1, 0.25, 0, '', 1, ''),
(1146, 474, 101, 1, 0.25, 0, '', 1, ''),
(1150, 475, 51, 1, 5.99, 0, '', 1, 'BBQ'),
(1151, 475, 25, 1, 4.99, 0, '', 1, ''),
(1152, 475, 65, 1, 1.75, 0, '', 1, 'SPRITE'),
(1153, 476, 50, 1, 3.50, 0, '', 1, 'MOSTAZA Y MIEL'),
(1154, 477, 65, 1, 1.75, 0, '', 1, ''),
(1155, 478, 42, 1, 3.99, 0, '', 1, 'NATURAL'),
(1156, 478, 42, 1, 3.99, 0, '', 1, ''),
(1157, 478, 46, 1, 3.99, 0, '', 1, ''),
(1158, 479, 46, 1, 3.99, 0, '', 1, 'NATURAL'),
(1159, 479, 67, 1, 0.99, 0, '', 1, ''),
(1161, 480, 44, 1, 5.50, 0, '', 1, ''),
(1162, 480, 66, 1, 1.25, 0, '', 1, ''),
(1164, 481, 58, 1, 4.50, 0, '', 1, ''),
(1165, 481, 58, 1, 4.50, 0, '', 1, ''),
(1166, 481, 42, 1, 3.99, 0, '', 1, ''),
(1167, 481, 42, 1, 3.99, 0, '', 1, ''),
(1168, 481, 65, 1, 1.75, 0, '', 1, ''),
(1169, 481, 71, 1, 1.99, 0, '', 1, ''),
(1171, 482, 27, 1, 5.99, 0, '', 1, ''),
(1172, 482, 65, 1, 1.75, 0, '', 1, 'COCA'),
(1173, 482, 60, 1, 2.50, 0, '', 1, ''),
(1174, 483, 50, 1, 3.50, 0, '', 1, 'BBQ'),
(1175, 483, 24, 1, 4.99, 0, '', 1, ''),
(1176, 483, 24, 1, 4.99, 0, '', 1, ''),
(1177, 484, 64, 1, 0.99, 0, '', 1, ''),
(1178, 484, 64, 1, 0.99, 0, '', 1, ''),
(1180, 485, 64, 1, 0.99, 0, '', 1, ''),
(1181, 486, 28, 1, 3.50, 0, '', 1, 'SIN VEGETALES , SIN PIKLES'),
(1182, 486, 28, 1, 3.50, 0, '', 1, ''),
(1183, 486, 42, 1, 3.99, 0, '', 1, ''),
(1184, 486, 66, 1, 1.25, 0, '', 1, ''),
(1185, 486, 81, 1, 1.25, 0, '', 1, ''),
(1186, 486, 80, 1, 3.50, 0, '', 1, 'CLUB'),
(1188, 487, 43, 1, 3.99, 0, '', 1, ''),
(1189, 487, 42, 1, 3.99, 0, '', 1, ''),
(1190, 487, 44, 1, 5.50, 0, '', 1, ''),
(1191, 487, 51, 1, 5.99, 0, '', 1, '3 BBQ 3 MOSTAZA Y MIEL'),
(1192, 487, 65, 1, 1.75, 0, '', 1, 'COCA'),
(1193, 487, 67, 1, 0.99, 0, '', 1, ''),
(1195, 488, 25, 1, 4.99, 0, '', 1, ''),
(1196, 488, 24, 1, 4.99, 0, '', 1, ''),
(1197, 488, 23, 1, 3.99, 0, '', 1, ''),
(1198, 488, 26, 1, 5.99, 0, '', 1, ''),
(1199, 488, 65, 1, 1.75, 0, '', 1, ''),
(1202, 489, 67, 1, 0.99, 0, '', 1, ''),
(1203, 489, 67, 1, 0.99, 0, '', 1, ''),
(1205, 490, 110, 1, 1.50, 0, '', 1, ''),
(1206, 491, 42, 1, 3.99, 0, '', 1, ''),
(1207, 492, 28, 1, 3.50, 0, '', 1, ''),
(1208, 492, 64, 1, 0.99, 0, '', 1, ''),
(1210, 493, 60, 1, 2.50, 0, '', 1, ''),
(1211, 494, 28, 1, 3.50, 0, '', 1, ''),
(1212, 495, 42, 1, 3.99, 0, '', 1, ''),
(1213, 496, 26, 1, 5.99, 0, '', 1, ''),
(1214, 496, 71, 1, 1.99, 0, '', 1, ''),
(1216, 497, 51, 1, 5.99, 0, '', 1, ''),
(1217, 497, 64, 1, 0.99, 0, '', 1, ''),
(1219, 498, 28, 1, 3.50, 0, '', 1, ''),
(1220, 498, 51, 1, 5.99, 0, '', 1, 'BBQ'),
(1221, 498, 65, 1, 1.75, 0, '', 1, 'COCA'),
(1222, 499, 43, 1, 3.99, 0, '', 1, 'PLATO, SALSA APARTE'),
(1223, 499, 39, 1, 2.50, 0, '', 1, 'PLATO , SALSA APARTE'),
(1224, 499, 28, 1, 3.50, 0, '', 1, ''),
(1225, 499, 50, 1, 3.50, 0, '', 1, 'BBQ APARTE, SIN SALSA'),
(1226, 499, 24, 1, 4.99, 0, '', 1, ''),
(1227, 499, 65, 1, 1.75, 0, '', 1, 'FIORA'),
(1229, 500, 51, 1, 5.99, 0, '', 1, '3MOSTAZA Y MIEL 3 BBQ'),
(1230, 500, 51, 1, 5.99, 0, '', 1, '3MOSTAZA Y MIEL 3 BBQ'),
(1231, 500, 50, 1, 3.50, 0, '', 1, 'BBQ'),
(1232, 500, 81, 1, 1.25, 0, '', 1, ''),
(1233, 500, 79, 1, 2.50, 0, '', 1, 'PILSENER'),
(1236, 501, 25, 1, 4.99, 0, '', 1, ''),
(1237, 502, 67, 1, 0.99, 0, '', 1, ''),
(1238, 502, 64, 1, 0.99, 0, '', 1, ''),
(1239, 503, 108, 1, 9.99, 0, '', 1, 'BBQ'),
(1240, 503, 67, 1, 0.99, 0, '', 1, ''),
(1241, 503, 25, 1, 4.99, 0, '', 1, ''),
(1242, 503, 69, 1, 1.50, 0, '', 1, ''),
(1243, 503, 74, 1, 1.50, 0, '', 1, ''),
(1246, 504, 25, 1, 4.99, 0, '', 1, ''),
(1247, 505, 28, 1, 3.50, 0, '', 1, ''),
(1248, 505, 33, 1, 0.99, 0, '', 1, ''),
(1249, 505, 64, 1, 0.99, 0, '', 1, 'FANTA'),
(1250, 505, 64, 1, 0.99, 0, '', 1, 'FANTA'),
(1251, 505, 42, 1, 3.99, 0, '', 1, 'SOLO QUESO'),
(1254, 506, 64, 1, 0.99, 0, '', 1, ''),
(1255, 506, 96, 1, 2.00, 0, '', 1, ''),
(1257, 507, 58, 1, 4.50, 0, '', 1, ''),
(1258, 507, 58, 1, 4.50, 0, '', 1, ''),
(1260, 508, 58, 1, 4.50, 0, '', 1, ''),
(1261, 508, 25, 1, 4.99, 0, '', 1, ''),
(1262, 508, 24, 1, 4.99, 0, '', 1, ''),
(1263, 508, 42, 1, 3.99, 0, '', 1, 'plato'),
(1264, 508, 66, 1, 1.25, 0, '', 1, ''),
(1267, 509, 64, 1, 0.99, 0, '', 1, 'coca plastico'),
(1268, 509, 66, 1, 1.25, 0, '', 1, ''),
(1269, 509, 69, 1, 1.50, 0, '', 1, ''),
(1270, 509, 67, 1, 0.99, 0, '', 1, ''),
(1274, 510, 69, 1, 1.50, 0, '', 1, ''),
(1275, 510, 64, 1, 0.99, 0, '', 1, 'coca plastico'),
(1276, 510, 66, 1, 1.25, 0, '', 1, ''),
(1277, 510, 67, 1, 0.99, 0, '', 1, ''),
(1281, 511, 64, 1, 0.99, 0, '', 1, ''),
(1282, 511, 67, 1, 0.99, 0, '', 1, ''),
(1283, 511, 66, 1, 1.25, 0, '', 1, ''),
(1284, 511, 69, 1, 1.50, 0, '', 1, ''),
(1288, 512, 69, 1, 1.50, 0, '', 1, ''),
(1289, 513, 1, 1, 3.99, 0, '', 1, ''),
(1290, 514, 4, 1, 4.99, 0, '', 1, ''),
(1291, 514, 2, 1, 4.99, 0, '', 1, ''),
(1292, 514, 1, 1, 3.99, 0, '', 1, ''),
(1293, 515, 41, 1, 2.99, 0, '', 1, ''),
(1294, 515, 71, 1, 1.99, 0, '', 1, ''),
(1296, 516, 27, 1, 5.99, 0, '', 1, 'sin cebolla , sin nada de salsa '),
(1297, 516, 67, 1, 0.99, 0, '', 1, ''),
(1298, 516, 27, 1, 5.99, 0, '', 1, ''),
(1299, 516, 64, 1, 0.99, 0, '', 1, ''),
(1303, 517, 28, 1, 3.50, 0, '', 1, ''),
(1304, 518, 39, 1, 2.50, 0, '', 1, ''),
(1305, 518, 28, 1, 3.50, 0, '', 1, ''),
(1306, 519, 71, 1, 1.99, 0, '', 1, ''),
(1307, 520, 64, 1, 0.99, 0, '', 1, ''),
(1308, 521, 42, 1, 3.99, 0, '', 1, ''),
(1309, 521, 64, 1, 0.99, 0, '', 1, ''),
(1311, 522, 64, 1, 0.99, 0, '', 1, ''),
(1312, 522, 64, 1, 0.99, 0, '', 1, ''),
(1314, 523, 50, 1, 3.50, 0, '', 1, ''),
(1315, 523, 64, 1, 0.99, 0, '', 1, ''),
(1317, 524, 15, 1, 5.50, 0, '', 1, ''),
(1318, 524, 64, 1, 0.99, 0, '', 1, ''),
(1320, 525, 64, 1, 0.99, 0, '', 1, ''),
(1321, 526, 27, 1, 5.99, 0, '', 1, ''),
(1322, 526, 24, 1, 4.99, 0, '', 1, ''),
(1323, 526, 25, 1, 4.99, 0, '', 1, ''),
(1324, 526, 65, 1, 1.75, 0, '', 1, 'sprite'),
(1328, 527, 64, 1, 0.99, 0, '', 1, ''),
(1329, 528, 71, 1, 1.99, 0, '', 1, ''),
(1330, 529, 44, 1, 5.50, 0, '', 1, ''),
(1331, 529, 66, 1, 1.25, 0, '', 1, ''),
(1333, 530, 44, 1, 5.50, 0, '', 1, ''),
(1334, 530, 44, 1, 5.50, 0, '', 1, ''),
(1335, 530, 26, 1, 5.99, 0, '', 1, ''),
(1336, 530, 74, 1, 1.50, 0, '', 1, ''),
(1337, 530, 64, 1, 0.99, 0, '', 1, ''),
(1338, 530, 64, 1, 0.99, 0, '', 1, ''),
(1340, 531, 64, 1, 0.99, 0, '', 1, ''),
(1341, 532, 24, 1, 4.99, 0, '', 1, ''),
(1342, 532, 64, 1, 0.99, 0, '', 1, ''),
(1344, 533, 27, 1, 5.99, 0, '', 1, ''),
(1345, 533, 25, 1, 4.99, 0, '', 1, ''),
(1346, 533, 64, 1, 0.99, 0, '', 1, 'coca'),
(1347, 534, 43, 1, 3.99, 0, '', 1, ''),
(1348, 534, 64, 1, 0.99, 0, '', 1, 'COCA'),
(1350, 535, 24, 1, 4.99, 0, '', 1, ''),
(1351, 535, 51, 1, 5.99, 0, '', 1, 'MOSTAZA Y MIEL 3 BBQ'),
(1352, 535, 39, 1, 2.50, 0, '', 1, ''),
(1353, 535, 65, 1, 1.75, 0, '', 1, 'COCA'),
(1357, 536, 25, 1, 4.99, 0, '', 1, 'SIN SALSAS , SIN PAPAS , SIN PICKLES , SIN VEGETALES '),
(1358, 537, 50, 1, 3.50, 0, '', 1, 'BBQ PICANTE'),
(1359, 537, 101, 1, 0.25, 0, '', 1, ''),
(1361, 538, 64, 1, 0.99, 0, '', 1, ''),
(1362, 539, 50, 1, 3.50, 0, '', 1, 'bbq'),
(1363, 539, 50, 1, 3.50, 0, '', 1, 'bbq'),
(1364, 539, 18, 1, 3.99, 0, '', 1, ''),
(1365, 539, 71, 1, 1.99, 0, '', 1, ''),
(1369, 540, 27, 1, 5.99, 0, '', 1, ''),
(1370, 541, 26, 1, 5.99, 0, '', 1, ''),
(1371, 542, 28, 1, 3.50, 0, '', 1, ''),
(1372, 542, 64, 1, 0.99, 0, '', 1, ''),
(1374, 543, 50, 1, 3.50, 0, '', 1, ''),
(1375, 543, 64, 1, 0.99, 0, '', 1, 'fiora manzana'),
(1377, 544, 42, 1, 3.99, 0, '', 1, ''),
(1378, 544, 64, 1, 0.99, 0, '', 1, ''),
(1380, 545, 23, 1, 3.99, 0, '', 1, ''),
(1381, 545, 64, 1, 0.99, 0, '', 1, 'sporade'),
(1383, 546, 51, 1, 5.99, 0, '', 1, 'bbq'),
(1384, 546, 26, 1, 5.99, 0, '', 1, ''),
(1385, 546, 101, 1, 0.25, 0, '', 1, ''),
(1386, 546, 65, 1, 1.75, 0, '', 1, 'fiora'),
(1390, 547, 41, 1, 2.99, 0, '', 1, ''),
(1391, 547, 42, 1, 3.99, 0, '', 1, ''),
(1392, 547, 69, 1, 1.50, 0, '', 1, ''),
(1393, 547, 42, 1, 3.99, 0, '', 1, ''),
(1394, 547, 25, 1, 4.99, 0, '', 1, ''),
(1397, 548, 58, 1, 4.50, 0, '', 1, ''),
(1398, 548, 58, 1, 4.50, 0, '', 1, ''),
(1399, 548, 78, 1, 1.50, 0, '', 1, ''),
(1400, 548, 78, 1, 1.50, 0, '', 1, ''),
(1401, 548, 81, 1, 1.25, 0, '', 1, ''),
(1402, 548, 79, 1, 2.50, 0, '', 1, ''),
(1404, 549, 43, 1, 3.99, 0, '', 1, ''),
(1405, 549, 43, 1, 3.99, 0, '', 1, ''),
(1406, 549, 42, 1, 3.99, 0, '', 1, ''),
(1407, 550, 46, 1, 3.99, 0, '', 1, ''),
(1408, 550, 46, 1, 3.99, 0, '', 1, ''),
(1409, 550, 101, 1, 0.25, 0, '', 1, ''),
(1410, 550, 101, 1, 0.25, 0, '', 1, ''),
(1411, 551, 46, 1, 3.99, 0, '', 1, ''),
(1412, 551, 41, 1, 2.99, 0, '', 1, ''),
(1413, 551, 71, 1, 1.99, 0, '', 1, ''),
(1414, 551, 64, 1, 0.99, 0, '', 1, ''),
(1418, 552, 53, 1, 13.99, 0, '', 1, ''),
(1419, 552, 100, 1, 4.99, 0, '', 1, ''),
(1421, 553, 40, 1, 2.99, 0, '', 1, ''),
(1422, 554, 109, 1, 2.50, 0, '', 1, ''),
(1423, 555, 52, 1, 8.99, 0, '', 1, ''),
(1424, 555, 69, 1, 1.50, 0, '', 1, ''),
(1425, 555, 64, 1, 0.99, 0, '', 1, ''),
(1426, 556, 28, 1, 3.50, 0, '', 1, ''),
(1427, 556, 28, 1, 3.50, 0, '', 1, ''),
(1429, 557, 54, 1, 17.99, 0, '', 1, ''),
(1430, 558, 44, 1, 5.50, 0, '', 1, ''),
(1431, 558, 51, 1, 5.99, 0, '', 1, 'mostaza , bbq picante'),
(1432, 558, 64, 1, 0.99, 0, '', 1, 'sprite'),
(1433, 559, 44, 1, 5.50, 0, '', 1, ''),
(1434, 560, 44, 1, 5.50, 0, '', 1, ''),
(1435, 560, 51, 1, 5.99, 0, '', 1, '3bbq picanta 3 mostaza y miel'),
(1436, 560, 64, 1, 0.99, 0, '', 1, ''),
(1437, 561, 41, 1, 2.99, 0, '', 1, ''),
(1438, 561, 28, 1, 3.50, 0, '', 1, ''),
(1440, 562, 28, 1, 3.50, 0, '', 1, ''),
(1441, 562, 28, 1, 3.50, 0, '', 1, ''),
(1442, 562, 39, 1, 2.50, 0, '', 1, ''),
(1443, 562, 40, 1, 2.99, 0, '', 1, ''),
(1444, 562, 70, 1, 1.99, 0, '', 1, ''),
(1445, 562, 70, 1, 1.99, 0, '', 1, ''),
(1446, 562, 70, 1, 1.99, 0, '', 1, ''),
(1447, 562, 79, 1, 2.50, 0, '', 1, ''),
(1455, 563, 53, 1, 13.99, 0, '', 1, 'bbq , mostaza y miel , maracuya'),
(1456, 563, 111, 1, 2.00, 0, '', 1, ''),
(1458, 564, 40, 1, 2.99, 0, '', 1, ''),
(1459, 564, 64, 1, 0.99, 0, '', 1, ''),
(1461, 565, 51, 1, 5.99, 0, '', 1, ''),
(1462, 565, 64, 1, 0.99, 0, '', 1, ''),
(1464, 566, 44, 1, 5.50, 0, '', 1, 'sin bbq'),
(1465, 566, 101, 1, 0.25, 0, '', 1, ''),
(1466, 566, 67, 1, 0.99, 0, '', 1, ''),
(1467, 566, 64, 1, 0.99, 0, '', 1, 'coca'),
(1471, 567, 25, 1, 4.99, 0, '', 1, ''),
(1472, 567, 25, 1, 4.99, 0, '', 1, ''),
(1473, 567, 64, 1, 0.99, 0, '', 1, 'coca\r\n'),
(1474, 567, 64, 1, 0.99, 0, '', 1, 'sprite'),
(1475, 567, 101, 1, 0.25, 0, '', 1, ''),
(1476, 567, 101, 1, 0.25, 0, '', 1, ''),
(1478, 568, 52, 1, 8.99, 0, '', 1, 'mostaza y miel ,bbq'),
(1479, 568, 27, 1, 5.99, 0, '', 1, ''),
(1480, 568, 101, 1, 0.25, 0, '', 1, ''),
(1481, 568, 101, 1, 0.25, 0, '', 1, ''),
(1482, 568, 64, 1, 0.99, 0, '', 1, ''),
(1483, 568, 64, 1, 0.99, 0, '', 1, ''),
(1485, 569, 26, 1, 5.99, 0, '', 1, ''),
(1486, 569, 51, 1, 5.99, 0, '', 1, 'bbq, mostaza y miel'),
(1487, 569, 70, 1, 1.99, 0, '', 1, ''),
(1488, 569, 71, 1, 1.99, 0, '', 1, ''),
(1492, 570, 49, 1, 2.99, 0, '', 1, 'MOSTAZA Y MIEL'),
(1493, 570, 66, 1, 1.25, 0, '', 1, ''),
(1495, 571, 39, 1, 2.50, 0, '', 1, ''),
(1496, 571, 39, 1, 2.50, 0, '', 1, ''),
(1497, 571, 25, 1, 4.99, 0, '', 1, ''),
(1498, 571, 27, 1, 5.99, 0, '', 1, ''),
(1499, 571, 80, 1, 3.50, 0, '', 1, ''),
(1502, 572, 40, 1, 2.99, 0, '', 1, ''),
(1503, 572, 40, 1, 2.99, 0, '', 1, ''),
(1504, 572, 100, 1, 4.99, 0, '', 1, ''),
(1505, 572, 26, 1, 5.99, 0, '', 1, ''),
(1509, 573, 27, 1, 5.99, 0, '', 1, ''),
(1510, 573, 28, 1, 3.50, 0, '', 1, ''),
(1512, 574, 67, 1, 0.99, 0, '', 1, ''),
(1513, 574, 67, 1, 0.99, 0, '', 1, ''),
(1514, 574, 67, 1, 0.99, 0, '', 1, ''),
(1515, 574, 74, 1, 1.50, 0, '', 1, ''),
(1519, 575, 49, 1, 2.99, 0, '', 1, 'mostaza y miel'),
(1520, 575, 66, 1, 1.25, 0, '', 1, ''),
(1522, 576, 49, 1, 2.99, 0, '', 1, ''),
(1523, 577, 28, 1, 3.50, 0, '', 1, ''),
(1524, 578, 51, 1, 5.99, 0, '', 1, 'maracuya'),
(1525, 578, 27, 1, 5.99, 0, '', 1, ''),
(1526, 578, 64, 1, 0.99, 0, '', 1, ''),
(1527, 578, 64, 1, 0.99, 0, '', 1, ''),
(1531, 579, 51, 1, 5.99, 0, '', 1, '3  bbq  3 maracuya aparte la salsa'),
(1532, 579, 51, 1, 5.99, 0, '', 1, '3 bbq  3 maracuya aparte la salsa'),
(1533, 579, 51, 1, 5.99, 0, '', 1, 'bbq picante  salsa aparte'),
(1534, 579, 24, 1, 4.99, 0, '', 1, ''),
(1535, 579, 65, 1, 1.75, 0, '', 1, 'coca cola'),
(1538, 580, 53, 1, 13.99, 0, '', 1, '5 bbq `picante , 5 mostaza y miel 5 maracuya'),
(1539, 580, 101, 1, 0.25, 0, '', 1, ''),
(1540, 580, 101, 1, 0.25, 0, '', 1, ''),
(1541, 581, 51, 1, 5.99, 0, '', 1, 'bbq '),
(1542, 581, 101, 1, 0.25, 0, '', 1, ''),
(1544, 582, 50, 1, 3.50, 0, '', 1, ''),
(1545, 582, 66, 1, 1.25, 0, '', 1, ''),
(1547, 583, 45, 1, 3.99, 0, '', 1, 'suprema tocino'),
(1548, 583, 67, 1, 0.99, 0, '', 1, ''),
(1550, 584, 50, 1, 3.50, 0, '', 1, 'mostaza y miel'),
(1551, 584, 64, 1, 0.99, 0, '', 1, 'sprite'),
(1553, 585, 27, 1, 5.99, 0, '', 1, ''),
(1554, 586, 25, 1, 4.99, 0, '', 1, ''),
(1555, 587, 64, 1, 0.99, 0, '', 1, ''),
(1556, 588, 50, 1, 3.50, 0, '', 1, 'mostaza y miel aparte de  las alitas'),
(1557, 588, 50, 1, 3.50, 0, '', 1, 'mostaza y miel'),
(1558, 588, 50, 1, 3.50, 0, '', 1, 'bbq'),
(1559, 588, 50, 1, 3.50, 0, '', 1, 'bbq'),
(1560, 588, 65, 1, 1.75, 0, '', 1, 'coca'),
(1563, 589, 39, 1, 2.50, 0, '', 1, ''),
(1564, 590, 50, 1, 3.50, 0, '', 1, ''),
(1565, 590, 40, 1, 2.99, 0, '', 1, ''),
(1567, 591, 50, 1, 3.50, 0, '', 1, ''),
(1568, 591, 64, 1, 0.99, 0, '', 1, ''),
(1570, 592, 44, 1, 5.50, 0, '', 1, ''),
(1571, 592, 44, 1, 5.50, 0, '', 1, ''),
(1572, 592, 40, 1, 2.99, 0, '', 1, ''),
(1573, 592, 40, 1, 2.99, 0, '', 1, ''),
(1574, 592, 39, 1, 2.50, 0, '', 1, ''),
(1575, 592, 27, 1, 5.99, 0, '', 1, ''),
(1576, 592, 27, 1, 5.99, 0, '', 1, ''),
(1577, 592, 27, 1, 5.99, 0, '', 1, ''),
(1578, 592, 65, 1, 1.75, 0, '', 1, ''),
(1579, 592, 65, 1, 1.75, 0, '', 1, ''),
(1585, 593, 26, 1, 5.99, 0, '', 1, ''),
(1586, 594, 39, 1, 2.50, 0, '', 1, ''),
(1587, 594, 71, 1, 1.99, 0, '', 1, ''),
(1588, 594, 71, 1, 1.99, 0, '', 1, ''),
(1589, 594, 49, 1, 2.99, 0, '', 1, ''),
(1593, 595, 64, 1, 0.99, 0, '', 1, ''),
(1594, 596, 27, 1, 5.99, 0, '', 1, ''),
(1595, 596, 61, 1, 1.50, 0, '', 1, ''),
(1597, 597, 49, 1, 2.99, 0, '', 1, ''),
(1598, 597, 101, 1, 0.25, 0, '', 1, ''),
(1600, 598, 28, 1, 3.50, 0, '', 1, ''),
(1601, 598, 64, 1, 0.99, 0, '', 1, ''),
(1603, 599, 56, 1, 5.50, 0, '', 1, ''),
(1604, 599, 74, 1, 1.50, 0, '', 1, ''),
(1606, 600, 49, 1, 2.99, 0, '', 1, 'bbq picante'),
(1607, 600, 74, 1, 1.50, 0, '', 1, ''),
(1609, 601, 27, 1, 5.99, 0, '', 1, ''),
(1610, 601, 46, 1, 3.99, 0, '', 1, ''),
(1611, 601, 101, 1, 0.25, 0, '', 1, ''),
(1612, 601, 101, 1, 0.25, 0, '', 1, ''),
(1616, 602, 43, 1, 3.99, 0, '', 1, ''),
(1617, 602, 74, 1, 1.50, 0, '', 1, ''),
(1619, 603, 41, 1, 2.99, 0, '', 1, ''),
(1620, 603, 74, 1, 1.50, 0, '', 1, ''),
(1622, 604, 41, 1, 2.99, 0, '', 1, ''),
(1623, 605, 49, 1, 2.99, 0, '', 1, ''),
(1624, 605, 74, 1, 1.50, 0, '', 1, ''),
(1626, 606, 78, 1, 1.50, 0, '', 1, 'te'),
(1627, 606, 40, 1, 2.99, 0, '', 1, ''),
(1629, 607, 40, 1, 2.99, 0, '', 1, ''),
(1630, 608, 49, 1, 2.99, 0, '', 1, 'bbq'),
(1631, 608, 49, 1, 2.99, 0, '', 1, 'bbq'),
(1632, 608, 111, 1, 2.00, 0, '', 1, ''),
(1633, 609, 67, 1, 0.99, 0, '', 1, ''),
(1634, 609, 67, 1, 0.99, 0, '', 1, ''),
(1636, 610, 49, 1, 2.99, 0, '', 1, 'bbq'),
(1637, 610, 41, 1, 2.99, 0, '', 1, ''),
(1639, 611, 67, 1, 0.99, 0, '', 1, ''),
(1640, 612, 64, 1, 0.99, 0, '', 1, ''),
(1641, 613, 49, 1, 2.99, 0, '', 1, 'mostaza y miel'),
(1642, 614, 39, 1, 2.50, 0, '', 1, ''),
(1643, 614, 64, 1, 0.99, 0, '', 1, ''),
(1645, 615, 49, 1, 2.99, 0, '', 1, ''),
(1646, 616, 67, 1, 0.99, 0, '', 1, ''),
(1647, 617, 27, 1, 5.99, 0, '', 1, ''),
(1648, 617, 44, 1, 5.50, 0, '', 1, 'SIN SALSA '),
(1649, 617, 35, 1, 1.50, 0, '', 1, ''),
(1650, 617, 66, 1, 1.25, 0, '', 1, ''),
(1654, 618, 24, 1, 4.99, 0, '', 1, ''),
(1655, 618, 25, 1, 4.99, 0, '', 1, ''),
(1657, 619, 39, 1, 2.50, 0, '', 1, ''),
(1658, 619, 64, 1, 0.99, 0, '', 1, ''),
(1660, 620, 51, 1, 5.99, 0, '', 1, 'BBQ PICANTE 3 MOSTAZA Y MIEL'),
(1661, 620, 27, 1, 5.99, 0, '', 1, ''),
(1662, 620, 111, 1, 2.00, 0, '', 1, ''),
(1663, 621, 28, 1, 3.50, 0, '', 1, ''),
(1664, 621, 98, 1, 0.99, 0, '', 1, ''),
(1665, 621, 71, 1, 1.99, 0, '', 1, ''),
(1666, 622, 27, 1, 5.99, 0, '', 1, ''),
(1667, 622, 64, 1, 0.99, 0, '', 1, ''),
(1669, 623, 39, 1, 2.50, 0, '', 1, ''),
(1670, 623, 64, 1, 0.99, 0, '', 1, ''),
(1672, 624, 14, 1, 5.50, 0, '', 1, ''),
(1673, 624, 102, 1, 0.50, 0, '', 1, ''),
(1674, 624, 111, 1, 2.00, 0, '', 1, ''),
(1675, 624, 111, 1, 2.00, 0, '', 1, ''),
(1676, 624, 44, 1, 5.50, 0, '', 1, ''),
(1677, 624, 43, 1, 3.99, 0, '', 1, ''),
(1678, 624, 39, 1, 2.50, 0, '', 1, ''),
(1679, 624, 25, 1, 4.99, 0, '', 1, ''),
(1687, 625, 67, 1, 0.99, 0, '', 1, ''),
(1688, 626, 110, 1, 1.50, 0, '', 1, ''),
(1689, 627, 28, 1, 3.50, 0, '', 1, ''),
(1690, 627, 64, 1, 0.99, 0, '', 1, ''),
(1691, 627, 64, 1, 0.99, 0, '', 1, ''),
(1692, 628, 28, 1, 3.50, 0, '', 1, ''),
(1693, 628, 78, 1, 1.50, 0, '', 1, 'TE'),
(1694, 628, 78, 1, 1.50, 0, '', 1, 'TE'),
(1695, 629, 64, 1, 0.99, 0, '', 1, ''),
(1696, 630, 28, 1, 3.50, 0, '', 1, ''),
(1697, 631, 28, 1, 3.50, 0, '', 1, ''),
(1698, 631, 28, 1, 3.50, 0, '', 1, ''),
(1699, 631, 28, 1, 3.50, 0, '', 1, ''),
(1700, 632, 24, 1, 4.99, 0, '', 1, ''),
(1701, 632, 25, 1, 4.99, 0, '', 1, ''),
(1703, 633, 28, 1, 3.50, 0, '', 1, ''),
(1704, 634, 64, 1, 0.99, 0, '', 1, ''),
(1705, 635, 40, 1, 2.99, 0, '', 1, ''),
(1706, 635, 46, 1, 3.99, 0, '', 1, 'PICANTE'),
(1707, 635, 50, 1, 3.50, 0, '', 1, 'BBQ'),
(1708, 635, 111, 1, 2.00, 0, '', 1, ''),
(1712, 636, 27, 1, 5.99, 0, '', 1, ''),
(1713, 636, 69, 1, 1.50, 0, '', 1, ''),
(1714, 636, 25, 1, 4.99, 0, '', 1, ''),
(1715, 636, 71, 1, 1.99, 0, '', 1, ''),
(1716, 636, 18, 1, 3.99, 0, '', 1, ''),
(1719, 637, 40, 1, 2.99, 0, '', 1, ''),
(1720, 637, 23, 1, 3.99, 0, '', 1, ''),
(1722, 638, 27, 1, 5.99, 0, '', 1, ''),
(1723, 638, 23, 1, 3.99, 0, '', 1, ''),
(1725, 639, 25, 1, 4.99, 0, '', 1, ''),
(1726, 639, 25, 1, 4.99, 0, '', 1, ''),
(1727, 639, 28, 1, 3.50, 0, '', 1, ''),
(1728, 640, 50, 1, 3.50, 0, '', 1, 'mostaza y miel'),
(1729, 640, 40, 1, 2.99, 0, '', 1, ''),
(1731, 641, 78, 1, 1.50, 0, '', 1, ''),
(1732, 642, 28, 1, 3.50, 0, '', 1, ''),
(1733, 643, 18, 1, 3.99, 0, '', 1, ''),
(1734, 643, 18, 1, 3.99, 0, '', 1, ''),
(1735, 643, 28, 1, 3.50, 0, '', 1, ''),
(1736, 644, 44, 1, 5.50, 0, '', 1, ''),
(1737, 644, 64, 1, 0.99, 0, '', 1, ''),
(1739, 645, 64, 1, 0.99, 0, '', 1, ''),
(1740, 646, 27, 1, 5.99, 0, '', 1, ''),
(1741, 646, 52, 1, 8.99, 0, '', 1, 'bbq\r\nmostaza y miel\r\nmaracuya'),
(1742, 646, 69, 1, 1.50, 0, '', 1, ''),
(1743, 646, 69, 1, 1.50, 0, '', 1, ''),
(1744, 647, 49, 1, 2.99, 0, '', 1, ''),
(1745, 647, 105, 1, 5.99, 0, '', 1, ''),
(1746, 647, 28, 1, 3.50, 0, '', 1, ''),
(1747, 647, 34, 1, 1.50, 0, '', 1, ''),
(1748, 647, 49, 1, 2.99, 0, '', 1, ''),
(1749, 647, 40, 1, 2.99, 0, '', 1, ''),
(1751, 648, 68, 1, 0.99, 0, '', 1, ''),
(1752, 649, 42, 1, 3.99, 0, '', 1, ''),
(1753, 649, 28, 1, 3.50, 0, '', 1, ''),
(1754, 649, 51, 1, 5.99, 0, '', 1, '3 mostaza y miel y 3 bbq'),
(1755, 649, 74, 1, 1.50, 0, '', 1, ''),
(1756, 649, 71, 1, 1.99, 0, '', 1, 'sin hielo'),
(1757, 649, 62, 1, 1.99, 0, '', 1, 'mora'),
(1758, 649, 62, 1, 1.99, 0, '', 1, 'mora'),
(1759, 649, 74, 1, 1.50, 0, '', 1, 'sanduche de queso mozarella mas mantequilla'),
(1760, 649, 67, 1, 0.99, 0, '', 1, ''),
(1767, 650, 74, 1, 1.50, 0, '', 1, 'sanduche queso y mantequilla'),
(1768, 651, 65, 1, 1.75, 0, '', 1, ''),
(1769, 651, 71, 1, 1.99, 0, '', 1, ''),
(1771, 652, 46, 1, 3.99, 0, '', 1, ''),
(1772, 652, 44, 1, 5.50, 0, '', 1, ''),
(1773, 652, 64, 1, 0.99, 0, '', 1, 'COCA'),
(1774, 652, 64, 1, 0.99, 0, '', 1, 'COCA'),
(1778, 653, 40, 1, 2.99, 0, '', 1, ''),
(1779, 653, 46, 1, 3.99, 0, '', 1, 'NATURAL'),
(1780, 653, 27, 1, 5.99, 0, '', 1, ''),
(1781, 653, 81, 1, 1.25, 0, '', 1, ''),
(1782, 653, 80, 1, 3.50, 0, '', 1, ''),
(1783, 653, 64, 1, 0.99, 0, '', 1, 'SPRITE PLASTICO'),
(1784, 653, 67, 1, 0.99, 0, '', 1, ''),
(1785, 654, 27, 1, 5.99, 0, '', 1, ''),
(1786, 654, 27, 1, 5.99, 0, '', 1, ''),
(1787, 654, 27, 1, 5.99, 0, '', 1, ''),
(1788, 654, 39, 1, 2.50, 0, '', 1, 'PLATO'),
(1789, 654, 64, 1, 0.99, 0, '', 1, 'FIORA VIDRIO'),
(1790, 654, 64, 1, 0.99, 0, '', 1, 'SPRITE VIDRIO'),
(1791, 655, 24, 1, 4.99, 0, '', 1, ''),
(1792, 656, 39, 1, 2.50, 0, '', 1, ''),
(1793, 656, 39, 1, 2.50, 0, '', 1, ''),
(1794, 656, 40, 1, 2.99, 0, '', 1, ''),
(1795, 656, 40, 1, 2.99, 0, '', 1, ''),
(1799, 657, 64, 1, 0.99, 0, '', 1, ''),
(1800, 658, 41, 1, 2.99, 0, '', 1, ''),
(1801, 659, 41, 1, 2.99, 0, '', 1, ''),
(1802, 659, 79, 1, 2.50, 0, '', 1, ''),
(1803, 660, 40, 1, 2.99, 0, '', 1, 'sin salsas'),
(1804, 660, 64, 1, 0.99, 0, '', 1, 'coca'),
(1805, 660, 39, 1, 2.50, 0, '', 1, ''),
(1806, 660, 67, 1, 0.99, 0, '', 1, ''),
(1810, 661, 39, 1, 2.50, 0, '', 1, ''),
(1811, 662, 39, 1, 2.50, 0, '', 1, 'solo mayonesa'),
(1812, 662, 67, 1, 0.99, 0, '', 1, ''),
(1814, 663, 44, 1, 5.50, 0, '', 1, ''),
(1815, 664, 40, 1, 2.99, 0, '', 1, 'sin salsa'),
(1816, 664, 20, 1, 5.50, 0, '', 1, 'natural sin pico de gallo'),
(1817, 664, 110, 1, 1.50, 0, '', 1, ''),
(1818, 664, 40, 1, 2.99, 0, '', 1, ''),
(1822, 665, 58, 1, 4.50, 0, '', 1, ''),
(1823, 665, 58, 1, 4.50, 0, '', 1, ''),
(1824, 665, 71, 1, 1.99, 0, '', 1, ''),
(1825, 665, 71, 1, 1.99, 0, '', 1, ''),
(1826, 665, 42, 1, 3.99, 0, '', 1, ''),
(1829, 666, 28, 1, 3.50, 0, '', 1, ''),
(1830, 666, 28, 1, 3.50, 0, '', 1, ''),
(1831, 666, 46, 1, 3.99, 0, '', 1, ''),
(1832, 667, 5, 1, 5.99, 0, '', 1, 'yutfhgg'),
(1833, 667, 5, 1, 5.99, 0, '', 1, 'hfchgg'),
(1834, 667, 5, 1, 5.99, 0, '', 1, ''),
(1835, 667, 5, 1, 5.99, 0, '', 1, ''),
(1839, 668, 78, 1, 1.50, 0, '', 1, ''),
(1840, 669, 27, 1, 5.99, 0, '', 1, ''),
(1841, 669, 39, 1, 2.50, 0, '', 1, ''),
(1843, 670, 78, 1, 1.50, 0, '', 1, ''),
(1844, 670, 82, 1, 5.00, 0, '', 1, ''),
(1845, 671, 26, 1, 5.99, 0, '', 1, ''),
(1846, 671, 26, 1, 5.99, 0, '', 1, ''),
(1847, 671, 27, 1, 5.99, 0, '', 1, ''),
(1848, 671, 65, 1, 1.75, 0, '', 1, ''),
(1849, 671, 65, 1, 1.75, 0, '', 1, ''),
(1850, 671, 42, 1, 3.99, 0, '', 1, ''),
(1851, 671, 26, 1, 5.99, 0, '', 1, ''),
(1852, 671, 26, 1, 5.99, 0, '', 1, ''),
(1853, 671, 25, 1, 4.99, 0, '', 1, ''),
(1860, 672, 44, 1, 5.50, 0, '', 1, ''),
(1861, 672, 26, 1, 5.99, 0, '', 1, ''),
(1862, 672, 65, 1, 1.75, 0, '', 1, ''),
(1863, 673, 81, 1, 1.25, 0, '', 1, ''),
(1864, 673, 80, 1, 3.50, 0, '', 1, ''),
(1866, 674, 42, 1, 3.99, 0, '', 1, ''),
(1867, 674, 64, 1, 0.99, 0, '', 1, 'Sprite'),
(1869, 675, 28, 1, 3.50, 0, '', 1, ''),
(1870, 675, 71, 1, 1.99, 0, '', 1, ''),
(1872, 676, 27, 1, 5.99, 0, '', 1, ''),
(1873, 677, 64, 1, 0.99, 0, '', 1, ''),
(1874, 678, 44, 1, 5.50, 0, '', 1, ''),
(1875, 678, 53, 1, 13.99, 0, '', 1, '4 salsa'),
(1876, 678, 65, 1, 1.75, 0, '', 1, ''),
(1877, 679, 58, 1, 4.99, 0, '', 1, ''),
(1878, 679, 44, 1, 5.50, 0, '', 1, ''),
(1879, 679, 51, 1, 5.99, 0, '', 1, 'bbq'),
(1880, 679, 64, 1, 0.99, 0, '', 1, 'coca'),
(1881, 679, 64, 1, 0.99, 0, '', 1, 'fiora'),
(1882, 679, 39, 1, 2.50, 0, '', 1, ''),
(1884, 680, 15, 1, 5.50, 0, '', 1, ''),
(1885, 680, 43, 1, 3.99, 0, '', 1, ''),
(1886, 680, 64, 1, 0.99, 0, '', 1, 'coca'),
(1887, 680, 64, 1, 0.99, 0, '', 1, 'coca'),
(1891, 681, 80, 1, 3.50, 0, '', 1, ''),
(1892, 681, 80, 1, 3.50, 0, '', 1, ''),
(1894, 682, 111, 1, 2.00, 0, '', 1, ''),
(1895, 683, 5, 1, 5.99, 0, '', 1, ''),
(1896, 683, 5, 1, 5.99, 0, '', 1, ''),
(1897, 683, 5, 1, 5.99, 0, '', 1, ''),
(1898, 683, 5, 1, 5.99, 0, '', 1, ''),
(1902, 684, 5, 1, 5.99, 0, '', 1, ''),
(1903, 685, 10, 1, 0.99, 0, '', 1, ''),
(1904, 686, 5, 1, 5.99, 0, '', 1, ''),
(1905, 687, 1, 1, 3.99, 0, '', 1, 'asdasd'),
(1906, 688, 5, 1, 5.99, 0, '', 1, ''),
(1907, 689, 5, 1, 5.99, 0, '', 1, 'sdfsdfsdf'),
(1908, 690, 5, 1, 5.99, 0, '', 1, 'sdfsdf'),
(1909, 691, 5, 1, 5.99, 0, '', 1, 'sdfsdf'),
(1910, 692, 1, 1, 3.99, 0, '', 1, '{\"Huevos\":\"Revueltos\",\"Bebida_Caliente\":\"Cafe\",\"ob\":\"\"}'),
(1911, 692, 2, 1, 4.99, 0, '', 1, ''),
(1913, 693, 1, 1, 3.99, 0, '', 1, '{\"Huevos\":\"Revueltos\",\"Bebida_Caliente\":\"Cafe\",\"ob\":\"\"}'),
(1914, 694, 2, 1, 4.99, 0, '', 1, '{\"Huevos\":\"Frito Duro\",\"ob\":\"\"}'),
(1915, 695, 1, 1, 3.99, 0, '', 1, '{\"ob\":\"\"}'),
(1916, 695, 1, 1, 3.99, 0, '', 1, ''),
(1917, 695, 1, 1, 3.99, 0, '', 1, ''),
(1918, 695, 3, 1, 5.50, 0, '', 1, ''),
(1919, 695, 3, 1, 5.50, 0, '', 1, ''),
(1920, 695, 95, 1, 0.35, 0, '', 1, ''),
(1921, 695, 78, 1, 1.50, 0, '', 1, ''),
(1922, 696, 45, 1, 3.99, 0, '', 1, '{\"ob\":\"\"}'),
(1923, 696, 45, 1, 3.99, 0, '', 1, ''),
(1924, 696, 28, 1, 3.50, 0, '', 1, ''),
(1925, 696, 28, 1, 3.50, 0, '', 1, ''),
(1926, 696, 71, 1, 1.99, 0, '', 1, ''),
(1927, 696, 71, 1, 1.99, 0, '', 1, ''),
(1928, 696, 71, 1, 1.99, 0, '', 1, ''),
(1929, 697, 40, 1, 2.99, 0, '', 1, '{\"ob\":\"\"}'),
(1930, 697, 40, 1, 2.99, 0, '', 1, ''),
(1931, 697, 40, 1, 2.99, 0, '', 1, ''),
(1932, 697, 65, 1, 1.75, 0, '', 1, ''),
(1936, 698, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"\"}'),
(1937, 699, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"\"}'),
(1938, 700, 15, 1, 5.50, 0, '', 1, '{\"ob\":\"\"}'),
(1939, 700, 16, 1, 4.99, 0, '', 1, ''),
(1940, 700, 62, 1, 1.99, 0, '', 1, ''),
(1941, 700, 110, 1, 1.50, 0, '', 1, ''),
(1945, 701, 28, 1, 3.50, 0, '', 1, ''),
(1946, 701, 64, 1, 0.99, 0, '', 1, ''),
(1948, 702, 28, 1, 3.50, 0, '', 1, '{\"ob\":\"\"}'),
(1949, 702, 64, 1, 0.99, 0, '', 1, ''),
(1950, 702, 64, 1, 0.99, 0, '', 1, ''),
(1951, 703, 2, 1, 4.99, 0, '', 1, '{\"ob\":\"\"}'),
(1952, 703, 4, 1, 4.99, 0, '', 1, ''),
(1953, 703, 3, 1, 5.50, 0, '', 1, ''),
(1954, 703, 98, 1, 0.99, 0, '', 1, ''),
(1955, 703, 2, 1, 4.99, 0, '', 1, ''),
(1958, 704, 95, 1, 0.35, 0, '', 1, '{\"ob\":\"\"}'),
(1959, 704, 95, 1, 0.35, 0, '', 1, ''),
(1960, 704, 1, 1, 3.99, 0, '', 1, ''),
(1961, 704, 1, 1, 3.99, 0, '', 1, ''),
(1962, 704, 1, 1, 3.99, 0, '', 1, ''),
(1963, 704, 1, 1, 3.99, 0, '', 1, ''),
(1964, 704, 1, 1, 3.99, 0, '', 1, ''),
(1965, 705, 1, 1, 3.99, 0, '', 1, '{\"ob\":\"\"}'),
(1966, 705, 3, 1, 5.50, 0, '', 1, ''),
(1967, 705, 74, 1, 1.50, 0, '', 1, ''),
(1968, 706, 3, 1, 5.50, 0, '', 1, '{\"ob\":\"\"}'),
(1969, 706, 8, 1, 2.99, 0, '', 1, ''),
(1971, 707, 3, 1, 5.50, 0, '', 1, '{\"ob\":\"\"}'),
(1972, 707, 3, 1, 5.50, 0, '', 1, ''),
(1974, 708, 4, 1, 4.99, 0, '', 1, '{\"ob\":\"\"}'),
(1975, 708, 13, 1, 0.99, 0, '', 1, '');
INSERT INTO `detalle_factura` (`correlativo`, `nofactura`, `codproducto`, `cantidad`, `precio_venta`, `mesa`, `atributos`, `estatus_dt`, `observaciones`) VALUES
(1976, 708, 3, 1, 5.50, 0, '', 1, ''),
(1977, 709, 6, 1, 3.99, 0, '', 1, '{\"ob\":\"\"}'),
(1978, 709, 74, 1, 1.50, 0, '', 1, ''),
(1979, 709, 16, 1, 4.99, 0, '', 1, ''),
(1980, 710, 2, 1, 4.99, 0, '', 1, '{\"ob\":\"\"}'),
(1981, 710, 1, 1, 3.99, 0, '', 1, ''),
(1982, 710, 1, 1, 3.99, 0, '', 1, ''),
(1983, 710, 8, 1, 2.99, 0, '', 1, ''),
(1984, 710, 3, 1, 5.50, 0, '', 1, ''),
(1985, 711, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe\",\"Bebida_Fria\":\"Papaya\",\"Huevos\":\"Frito Duro\",\"ob\":\"\"}'),
(1986, 712, 78, 1, 1.50, 0, '', 1, '{\"ob\":\"TE\"}'),
(1987, 712, 95, 1, 0.35, 0, '', 1, ''),
(1988, 712, 95, 1, 0.35, 0, '', 1, ''),
(1989, 713, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Agua\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(1990, 713, 8, 1, 2.99, 0, '', 1, ''),
(1991, 713, 2, 1, 4.99, 0, '', 1, '{\"Huevos\":\"Revuelto normal\",\"Bebida_Caliente\":\"Agua\",\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(1992, 713, 2, 1, 4.99, 0, '', 1, '{\"Huevos\":\"Revuelto normal\",\"Bebida_Caliente\":\"Agua\",\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(1996, 714, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(1997, 714, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(1999, 715, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2000, 716, 24, 1, 4.99, 0, '', 1, '{\"ob\":\"Si vegetales \"}'),
(2001, 716, 66, 1, 1.25, 0, '', 1, ''),
(2002, 716, 25, 1, 4.99, 0, '', 1, '{\"ob\":\"Si vegetales\"}'),
(2003, 716, 69, 1, 1.50, 0, '', 1, ''),
(2007, 717, 39, 1, 2.50, 0, '', 1, ''),
(2008, 718, 41, 1, 2.99, 0, '', 1, ''),
(2009, 718, 63, 1, 2.50, 0, '', 1, '{\"ob\":\"mora\"}'),
(2011, 719, 50, 1, 3.50, 0, '', 1, '{\"ob\":\"bbq normal\"}'),
(2012, 719, 50, 1, 3.50, 0, '', 1, ''),
(2013, 719, 65, 1, 1.75, 0, '', 1, ''),
(2014, 720, 40, 1, 2.99, 0, '', 1, ''),
(2015, 721, 50, 1, 3.50, 0, '', 1, '{\"ob\":\"bbq\"}'),
(2016, 721, 26, 1, 5.99, 0, '', 1, ''),
(2017, 721, 113, 1, 0.99, 0, '', 1, ''),
(2018, 722, 58, 1, 4.99, 0, '', 1, ''),
(2019, 723, 67, 1, 0.99, 0, '', 1, ''),
(2020, 724, 24, 1, 4.99, 0, '', 1, ''),
(2021, 724, 67, 1, 0.99, 0, '', 1, ''),
(2023, 725, 26, 1, 5.99, 0, '', 1, ''),
(2024, 725, 113, 1, 0.99, 0, '', 1, ''),
(2026, 726, 27, 1, 5.99, 0, '', 1, '{\"ob\":\"SIN CEBOLLAS\"}'),
(2027, 726, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"SPRITE\"}'),
(2029, 727, 25, 1, 4.99, 0, '', 1, ''),
(2030, 728, 51, 1, 5.99, 0, '', 1, '{\"ob\":\"MOSTAZA Y MIEL\"}'),
(2031, 729, 42, 1, 3.99, 0, '', 1, ''),
(2032, 730, 24, 1, 4.99, 0, '', 1, ''),
(2033, 730, 25, 1, 4.99, 0, '', 1, ''),
(2035, 731, 64, 1, 0.99, 0, '', 1, ''),
(2036, 732, 27, 1, 5.99, 0, '', 1, ''),
(2037, 732, 67, 1, 0.99, 0, '', 1, ''),
(2039, 733, 26, 1, 5.99, 0, '', 1, ''),
(2040, 733, 25, 1, 4.99, 0, '', 1, ''),
(2041, 733, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Sprite\",\"ob\":\"\"}'),
(2042, 733, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(2046, 734, 24, 1, 4.99, 0, '', 1, ''),
(2047, 734, 25, 1, 4.99, 0, '', 1, ''),
(2049, 735, 45, 1, 3.99, 0, '', 1, ''),
(2050, 735, 81, 1, 1.25, 0, '', 1, ''),
(2051, 735, 79, 1, 2.50, 0, '', 1, ''),
(2052, 735, 25, 1, 4.99, 0, '', 1, ''),
(2053, 735, 41, 1, 2.99, 0, '', 1, '{\"Salsa_de_tomate\":\"Sin salsa de tomate\",\"ob\":\"\"}'),
(2054, 735, 40, 1, 2.99, 0, '', 1, ''),
(2055, 735, 41, 1, 2.99, 0, '', 1, '{\"Mayonesa\":\"Sin mayonesa\",\"Salsa_de_tomate\":\"Sin salsa de tomate\",\"BBQ\":\"Sin BBQ\",\"ob\":\"\"}'),
(2056, 735, 58, 1, 4.99, 0, '', 1, ''),
(2057, 735, 58, 1, 4.99, 0, '', 1, '{\"ob\":\"sin queso\"}'),
(2058, 735, 28, 1, 3.50, 0, '', 1, ''),
(2059, 735, 110, 1, 1.50, 0, '', 1, ''),
(2060, 735, 67, 1, 0.99, 0, '', 1, ''),
(2061, 735, 100, 1, 4.99, 0, '', 1, ''),
(2064, 736, 20, 1, 5.50, 0, '', 1, ''),
(2065, 737, 51, 1, 5.99, 0, '', 1, '{\"ob\":\"mostaza y miel\"}'),
(2066, 738, 64, 1, 0.99, 0, '', 1, ''),
(2067, 739, 26, 1, 5.99, 0, '', 1, ''),
(2068, 740, 24, 1, 4.99, 0, '', 1, ''),
(2069, 741, 27, 1, 5.99, 0, '', 1, ''),
(2070, 741, 27, 1, 5.99, 0, '', 1, ''),
(2071, 741, 25, 1, 4.99, 0, '', 1, ''),
(2072, 741, 23, 1, 3.99, 0, '', 1, ''),
(2076, 742, 25, 1, 4.99, 0, '', 1, ''),
(2077, 743, 43, 1, 3.99, 0, '', 1, ''),
(2078, 744, 111, 1, 2.00, 0, '', 1, ''),
(2079, 745, 28, 1, 3.50, 0, '', 1, ''),
(2080, 745, 28, 1, 3.50, 0, '', 1, ''),
(2081, 745, 28, 1, 3.50, 0, '', 1, ''),
(2082, 745, 28, 1, 3.50, 0, '', 1, ''),
(2086, 746, 65, 1, 1.75, 0, '', 1, ''),
(2087, 747, 62, 1, 1.99, 0, '', 1, ''),
(2088, 747, 79, 1, 2.50, 0, '', 1, ''),
(2090, 748, 24, 1, 4.99, 0, '', 1, ''),
(2091, 748, 33, 1, 0.99, 0, '', 1, '{\"ob\":\"frito duro en la hamburgesa\"}'),
(2092, 748, 51, 1, 5.99, 0, '', 1, '{\"ob\":\"3 bbq  3 mostaza y miel\"}'),
(2093, 749, 52, 1, 8.99, 0, '', 1, '{\"ob\":\"menos maracuya\"}'),
(2094, 750, 16, 1, 4.99, 0, '', 1, '{\"ob\":\"solo jamon\"}'),
(2095, 750, 74, 1, 1.50, 0, '', 1, '{\"ob\":\"cafe\"}'),
(2097, 751, 2, 1, 4.99, 0, '', 1, '{\"Huevos\":\"Revuelto normal\",\"Bebida_Caliente\":\"Cafe en leche\",\"Bebida_Fria\":\"Jugo Papaya\",\"ob\":\"\"}'),
(2098, 752, 2, 1, 4.99, 0, '', 1, '{\"Huevos\":\"Revuelto normal\",\"Bebida_Caliente\":\"Cafe en leche\",\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(2099, 752, 2, 1, 4.99, 0, '', 1, '{\"Huevos\":\"Revuelto normal\",\"Bebida_Caliente\":\"Cafe en leche\",\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(2100, 752, 2, 1, 4.99, 0, '', 1, '{\"Huevos\":\"Revuelto normal\",\"Bebida_Caliente\":\"Agua\",\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(2101, 753, 42, 1, 3.99, 0, '', 1, '{\"BBQ\":\"Sin BBQ\",\"ob\":\"\"}'),
(2102, 753, 42, 1, 3.99, 0, '', 1, '{\"ob\":\"solo queso chedar\"}'),
(2103, 753, 51, 1, 5.99, 0, '', 1, '{\"ob\":\"4 mostaza y miel 2 maracuya\"}'),
(2104, 754, 105, 1, 5.99, 0, '', 1, ''),
(2105, 755, 26, 1, 5.99, 0, '', 1, ''),
(2106, 755, 27, 1, 5.99, 0, '', 1, ''),
(2107, 755, 64, 1, 0.99, 0, '', 1, ''),
(2108, 756, 28, 1, 3.50, 0, '', 1, ''),
(2109, 756, 28, 1, 3.50, 0, '', 1, ''),
(2110, 756, 28, 1, 3.50, 0, '', 1, ''),
(2111, 756, 28, 1, 3.50, 0, '', 1, ''),
(2112, 756, 25, 1, 4.99, 0, '', 1, ''),
(2113, 756, 24, 1, 4.99, 0, '', 1, ''),
(2114, 756, 114, 1, 1.50, 0, '', 1, ''),
(2115, 757, 28, 1, 3.50, 0, '', 1, ''),
(2116, 757, 96, 1, 2.00, 0, '', 1, ''),
(2117, 757, 64, 1, 0.99, 0, '', 1, ''),
(2118, 758, 49, 1, 2.99, 0, '', 1, ''),
(2119, 758, 40, 1, 2.99, 0, '', 1, ''),
(2120, 758, 71, 1, 1.99, 0, '', 1, ''),
(2121, 758, 71, 1, 1.99, 0, '', 1, ''),
(2125, 759, 25, 1, 4.99, 0, '', 1, '{\"Vegetales\":\"Sin vegetales\",\"ob\":\"\"}'),
(2126, 759, 25, 1, 4.99, 0, '', 1, '{\"Vegetales\":\"Sin vegetales\",\"ob\":\"\"}'),
(2127, 759, 39, 1, 2.50, 0, '', 1, ''),
(2128, 759, 39, 1, 2.50, 0, '', 1, ''),
(2129, 759, 64, 1, 0.99, 0, '', 1, ''),
(2130, 759, 64, 1, 0.99, 0, '', 1, ''),
(2131, 759, 66, 1, 1.25, 0, '', 1, ''),
(2132, 760, 54, 1, 17.99, 0, '', 1, ''),
(2133, 760, 64, 1, 0.99, 0, '', 1, ''),
(2134, 760, 64, 1, 0.99, 0, '', 1, ''),
(2135, 761, 15, 1, 5.50, 0, '', 1, ''),
(2136, 761, 96, 1, 2.00, 0, '', 1, ''),
(2137, 761, 64, 1, 0.99, 0, '', 1, ''),
(2138, 761, 25, 1, 4.99, 0, '', 1, '{\"ob\":\"SIN PIKLET\"}'),
(2139, 761, 64, 1, 0.99, 0, '', 1, ''),
(2142, 762, 39, 1, 2.50, 0, '', 1, ''),
(2143, 762, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"INCA\"}'),
(2145, 763, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"\"}'),
(2146, 763, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"COCA\"}'),
(2148, 764, 15, 1, 5.50, 0, '', 1, ''),
(2149, 764, 64, 1, 0.99, 0, '', 1, ''),
(2151, 765, 96, 1, 2.00, 0, '', 1, ''),
(2152, 766, 3, 1, 5.50, 0, '', 1, ''),
(2153, 767, 52, 1, 8.99, 0, '', 1, '{\"ob\":\"todas las salsas\"}'),
(2154, 768, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2155, 768, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2156, 768, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2157, 768, 4, 1, 4.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"ob\":\"\"}'),
(2161, 769, 74, 1, 1.50, 0, '', 1, ''),
(2162, 769, 74, 1, 1.50, 0, '', 1, ''),
(2164, 770, 53, 1, 13.99, 0, '', 1, '{\"Sabor_alitas\":\"BBQ Picante\",\"ob\":\"5BBQ PICANTE , 5MOLSTAZA Y MIEL  5 MARACUYA\"}'),
(2165, 770, 81, 1, 1.25, 0, '', 1, ''),
(2166, 770, 81, 1, 1.25, 0, '', 1, ''),
(2167, 770, 81, 1, 1.25, 0, '', 1, ''),
(2168, 770, 79, 1, 2.50, 0, '', 1, ''),
(2169, 770, 79, 1, 2.50, 0, '', 1, ''),
(2170, 770, 79, 1, 2.50, 0, '', 1, ''),
(2171, 770, 24, 1, 4.99, 0, '', 1, ''),
(2179, 771, 42, 1, 3.99, 0, '', 1, ''),
(2180, 771, 42, 1, 3.99, 0, '', 1, ''),
(2181, 771, 65, 1, 1.75, 0, '', 1, ''),
(2182, 772, 27, 1, 5.99, 0, '', 1, '{\"ob\":\"SIN CEBOLLA\"}'),
(2183, 772, 71, 1, 1.99, 0, '', 1, ''),
(2184, 772, 71, 1, 1.99, 0, '', 1, ''),
(2185, 773, 71, 1, 1.99, 0, '', 1, ''),
(2186, 774, 110, 1, 1.50, 0, '', 1, ''),
(2187, 775, 42, 1, 3.99, 0, '', 1, ''),
(2188, 775, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"\"}'),
(2190, 776, 42, 1, 3.99, 0, '', 1, '{\"BBQ\":\"Sin BBQ\",\"ob\":\"\"}'),
(2191, 776, 42, 1, 3.99, 0, '', 1, '{\"BBQ\":\"Sin BBQ\",\"ob\":\"\"}'),
(2192, 776, 25, 1, 4.99, 0, '', 1, ''),
(2193, 777, 39, 1, 2.50, 0, '', 1, '{\"ob\":\"EN PLATO\"}'),
(2194, 777, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"\"}'),
(2195, 777, 110, 1, 1.50, 0, '', 1, ''),
(2196, 778, 42, 1, 3.99, 0, '', 1, '{\"Mayonesa\":\"Sin mayonesa\",\"ob\":\"\"}'),
(2197, 778, 27, 1, 5.99, 0, '', 1, '{\"ob\":\"SIN VEGETALES\"}'),
(2198, 778, 64, 1, 0.99, 0, '', 1, '{\"Estado_Bebida\":\"Frio\",\"ob\":\"COCA\"}'),
(2199, 778, 67, 1, 0.99, 0, '', 1, ''),
(2203, 779, 55, 1, 25.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"MARACUYA  BBQ\"}'),
(2204, 779, 65, 1, 1.75, 0, '', 1, '{\"ob\":\"SPRITE\"}'),
(2206, 780, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"\"}'),
(2207, 780, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"BBQ \"}'),
(2208, 780, 39, 1, 2.50, 0, '', 1, '{\"ob\":\"PLATO\"}'),
(2209, 780, 58, 1, 4.99, 0, '', 1, ''),
(2210, 780, 100, 1, 4.99, 0, '', 1, ''),
(2213, 781, 53, 1, 13.99, 0, '', 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"MOSTAZA Y MARACUYA\"}'),
(2214, 782, 78, 1, 1.50, 0, '', 1, '{\"ob\":\"TE\"}'),
(2215, 782, 78, 1, 1.50, 0, '', 1, '{\"ob\":\"TE\"}'),
(2217, 783, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"MOSTAZA\"}'),
(2218, 783, 71, 1, 1.99, 0, '', 1, ''),
(2220, 784, 78, 1, 1.50, 0, '', 1, ''),
(2221, 784, 78, 1, 1.50, 0, '', 1, ''),
(2222, 785, 3, 1, 5.50, 0, '', 1, ''),
(2223, 785, 113, 1, 0.99, 0, '', 1, ''),
(2225, 786, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2226, 786, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2227, 786, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2228, 786, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo queso\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2229, 786, 2, 1, 4.99, 0, '', 1, '{\"Huevos\":\"Revuelto normal\",\"Bebida_Caliente\":\"Cafe en leche\",\"ob\":\"\"}'),
(2232, 787, 24, 1, 4.99, 0, '', 1, ''),
(2233, 787, 27, 1, 5.99, 0, '', 1, ''),
(2234, 787, 39, 1, 2.50, 0, '', 1, '{\"BBQ\":\"Sin BBQ\",\"ob\":\"EN PLato\"}'),
(2235, 787, 111, 1, 2.00, 0, '', 1, ''),
(2239, 788, 42, 1, 3.99, 0, '', 1, '{\"ob\":\"sin salsas ( en cono)\"}'),
(2240, 788, 24, 1, 4.99, 0, '', 1, ''),
(2241, 788, 58, 1, 4.99, 0, '', 1, '{\"ob\":\"sin queso \"}'),
(2242, 789, 111, 1, 2.00, 0, '', 1, ''),
(2243, 790, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"BBQ Picante\",\"ob\":\"mostaza\"}'),
(2244, 790, 67, 1, 0.99, 0, '', 1, ''),
(2246, 791, 27, 1, 5.99, 0, '', 1, ''),
(2247, 791, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"fanta\"}'),
(2249, 792, 27, 1, 5.99, 0, '', 1, ''),
(2250, 792, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"bbq\"}'),
(2251, 792, 64, 1, 0.99, 0, '', 1, '{\"Estado_Bebida\":\"Frio\",\"ob\":\"coca\"}'),
(2252, 792, 113, 1, 0.99, 0, '', 1, ''),
(2256, 793, 28, 1, 3.50, 0, '', 1, ''),
(2257, 793, 30, 1, 0.99, 0, '', 1, ''),
(2258, 793, 32, 1, 0.99, 0, '', 1, ''),
(2259, 794, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"BBQ Picante\",\"ob\":\"maracuya\"}'),
(2260, 794, 71, 1, 1.99, 0, '', 1, ''),
(2262, 795, 20, 1, 5.50, 0, '', 1, ''),
(2263, 795, 78, 1, 1.50, 0, '', 1, '{\"ob\":\"te\"}'),
(2264, 795, 78, 1, 1.50, 0, '', 1, '{\"ob\":\"te\"}'),
(2265, 796, 20, 1, 5.50, 0, '', 1, ''),
(2266, 796, 25, 1, 4.99, 0, '', 1, ''),
(2267, 796, 78, 1, 1.50, 0, '', 1, '{\"ob\":\"te\"}'),
(2268, 796, 78, 1, 1.50, 0, '', 1, '{\"ob\":\"te \"}'),
(2272, 797, 20, 1, 5.50, 0, '', 1, ''),
(2273, 797, 41, 1, 2.99, 0, '', 1, '{\"BBQ\":\"Sin BBQ\",\"ob\":\"en plato\"}'),
(2274, 797, 69, 1, 1.50, 0, '', 1, ''),
(2275, 797, 61, 1, 1.50, 0, '', 1, ''),
(2279, 798, 9, 1, 0.99, 0, '', 1, ''),
(2280, 798, 74, 1, 1.50, 0, '', 1, ''),
(2281, 798, 101, 1, 0.25, 0, '', 1, ''),
(2282, 798, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"coca\"}'),
(2286, 799, 42, 1, 3.99, 0, '', 1, '{\"BBQ\":\"Sin BBQ\",\"ob\":\"\"}'),
(2287, 799, 52, 1, 8.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"maracuya\"}'),
(2288, 799, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"coca\"}'),
(2289, 799, 74, 1, 1.50, 0, '', 1, ''),
(2293, 800, 43, 1, 3.99, 0, '', 1, ''),
(2294, 800, 40, 1, 2.99, 0, '', 1, '{\"ob\":\"en cono\"}'),
(2295, 800, 69, 1, 1.50, 0, '', 1, ''),
(2296, 800, 71, 1, 1.99, 0, '', 1, ''),
(2297, 800, 9, 1, 0.99, 0, '', 1, ''),
(2300, 801, 96, 1, 2.00, 0, '', 1, ''),
(2301, 802, 26, 1, 5.99, 0, '', 1, ''),
(2302, 802, 71, 1, 1.99, 0, '', 1, ''),
(2303, 802, 71, 1, 1.99, 0, '', 1, ''),
(2304, 803, 58, 1, 4.99, 0, '', 1, ''),
(2305, 803, 69, 1, 1.50, 0, '', 1, ''),
(2306, 803, 71, 1, 1.99, 0, '', 1, ''),
(2307, 803, 110, 1, 1.50, 0, '', 1, ''),
(2311, 804, 79, 1, 2.50, 0, '', 1, ''),
(2312, 805, 71, 1, 1.99, 0, '', 1, ''),
(2313, 806, 44, 1, 5.50, 0, '', 1, ''),
(2314, 806, 101, 1, 0.25, 0, '', 1, ''),
(2315, 806, 114, 1, 1.50, 0, '', 1, ''),
(2316, 806, 77, 1, 2.50, 0, '', 1, ''),
(2320, 807, 4, 1, 4.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"ob\":\"\"}'),
(2321, 807, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Bolon mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(2323, 808, 74, 1, 1.50, 0, '', 1, ''),
(2324, 808, 14, 1, 5.50, 0, '', 1, ''),
(2326, 809, 4, 1, 4.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"ob\":\"\"}'),
(2327, 809, 2, 1, 4.99, 0, '', 1, '{\"Huevos\":\"Revuelto normal\",\"Bebida_Caliente\":\"Agua\",\"ob\":\"\"}'),
(2329, 810, 16, 1, 4.99, 0, '', 1, ''),
(2330, 810, 9, 1, 0.99, 0, '', 1, ''),
(2332, 811, 14, 1, 5.50, 0, '', 1, ''),
(2333, 811, 64, 1, 0.99, 0, '', 1, ''),
(2334, 811, 64, 1, 0.99, 0, '', 1, ''),
(2335, 812, 25, 1, 4.99, 0, '', 1, ''),
(2336, 812, 9, 1, 0.99, 0, '', 1, ''),
(2338, 813, 2, 1, 4.99, 0, '', 1, ''),
(2339, 813, 9, 1, 0.99, 0, '', 1, ''),
(2341, 814, 115, 1, 0.01, 0, '', 1, '{\"ob\":\"Agua, tibios\"}'),
(2342, 814, 115, 1, 0.01, 0, '', 1, '{\"ob\":\"Leche tibios \"}'),
(2343, 814, 115, 1, 0.01, 0, '', 1, '{\"ob\":\"Leche tibios\"}'),
(2344, 814, 115, 1, 0.01, 0, '', 1, '{\"ob\":\"Tibio, leche, cobrar\"}'),
(2345, 815, 39, 1, 2.50, 0, '', 1, ''),
(2346, 816, 44, 1, 5.50, 0, '', 1, ''),
(2347, 816, 44, 1, 5.50, 0, '', 1, '{\"ob\":\"en cono\"}'),
(2348, 816, 64, 1, 0.99, 0, '', 1, ''),
(2349, 816, 64, 1, 0.99, 0, '', 1, ''),
(2353, 817, 25, 1, 4.99, 0, '', 1, ''),
(2354, 818, 67, 1, 0.99, 0, '', 1, ''),
(2355, 819, 27, 1, 5.99, 0, '', 1, ''),
(2356, 819, 27, 1, 5.99, 0, '', 1, ''),
(2357, 819, 79, 1, 2.50, 0, '', 1, ''),
(2358, 819, 68, 1, 0.99, 0, '', 1, ''),
(2359, 819, 33, 1, 0.99, 0, '', 1, ''),
(2362, 820, 45, 1, 3.99, 0, '', 1, ''),
(2363, 820, 28, 1, 3.50, 0, '', 1, ''),
(2364, 820, 33, 1, 0.99, 0, '', 1, ''),
(2365, 820, 71, 1, 1.99, 0, '', 1, ''),
(2366, 820, 71, 1, 1.99, 0, '', 1, ''),
(2369, 821, 27, 1, 5.99, 0, '', 1, ''),
(2370, 821, 24, 1, 4.99, 0, '', 1, ''),
(2371, 821, 43, 1, 3.99, 0, '', 1, ''),
(2372, 821, 79, 1, 2.50, 0, '', 1, ''),
(2373, 821, 80, 1, 3.50, 0, '', 1, ''),
(2376, 822, 49, 1, 2.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"aparte\"}'),
(2377, 822, 16, 1, 4.99, 0, '', 1, ''),
(2378, 822, 41, 1, 2.99, 0, '', 1, '{\"BBQ\":\"Sin BBQ\",\"ob\":\"\"}'),
(2379, 822, 43, 1, 3.99, 0, '', 1, '{\"BBQ\":\"Sin BBQ\",\"ob\":\"\"}'),
(2380, 822, 71, 1, 1.99, 0, '', 1, ''),
(2383, 823, 101, 1, 0.25, 0, '', 1, ''),
(2384, 823, 44, 1, 5.50, 0, '', 1, ''),
(2385, 823, 26, 1, 5.99, 0, '', 1, ''),
(2386, 824, 65, 1, 1.75, 0, '', 1, ''),
(2387, 825, 44, 1, 5.50, 0, '', 1, ''),
(2388, 825, 39, 1, 2.50, 0, '', 1, ''),
(2389, 825, 65, 1, 1.75, 0, '', 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(2390, 826, 53, 1, 13.99, 0, '', 1, '{\"Sabor_alitas\":\"BBQ Picante\",\"ob\":\"bbq y mostaza\"}'),
(2391, 827, 112, 1, 3.00, 0, '', 1, ''),
(2392, 828, 71, 1, 1.99, 0, '', 1, ''),
(2393, 829, 40, 1, 2.99, 0, '', 1, ''),
(2394, 829, 40, 1, 2.99, 0, '', 1, ''),
(2395, 829, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"\"}'),
(2396, 829, 40, 1, 2.99, 0, '', 1, ''),
(2397, 829, 57, 1, 4.50, 0, '', 1, ''),
(2398, 829, 65, 1, 1.75, 0, '', 1, ''),
(2400, 830, 28, 1, 3.50, 0, '', 1, ''),
(2401, 831, 28, 1, 3.50, 0, '', 1, ''),
(2402, 831, 28, 1, 3.50, 0, '', 1, ''),
(2403, 831, 43, 1, 3.99, 0, '', 1, ''),
(2404, 831, 101, 1, 0.25, 0, '', 1, ''),
(2405, 831, 101, 1, 0.25, 0, '', 1, ''),
(2406, 831, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"\"}'),
(2407, 832, 1, 1, 3.99, 0, '', 1, ''),
(2408, 833, 2, 1, 4.99, 0, '', 1, '{\"Huevos\":\"Revuelto normal\",\"Bebida_Caliente\":\"Leche\",\"ob\":\"\"}'),
(2409, 833, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(2411, 834, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2412, 834, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2414, 835, 3, 1, 5.50, 0, '', 1, ''),
(2415, 835, 3, 1, 5.50, 0, '', 1, ''),
(2416, 835, 1, 1, 3.99, 0, '', 1, ''),
(2417, 835, 6, 1, 3.99, 0, '', 1, ''),
(2421, 836, 6, 1, 3.99, 0, '', 1, ''),
(2422, 836, 8, 1, 2.99, 0, '', 1, ''),
(2423, 836, 8, 1, 2.99, 0, '', 1, ''),
(2424, 836, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"ob\":\"\"}'),
(2425, 836, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"ob\":\"\"}'),
(2426, 836, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Leche\",\"ob\":\"\"}'),
(2428, 837, 1, 1, 3.99, 0, '', 1, ''),
(2429, 838, 74, 1, 1.50, 0, '', 1, ''),
(2430, 839, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(2431, 839, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(2433, 840, 98, 1, 0.99, 0, '', 1, ''),
(2434, 841, 112, 1, 3.00, 0, '', 1, ''),
(2435, 841, 112, 1, 3.00, 0, '', 1, ''),
(2436, 841, 112, 1, 3.00, 0, '', 1, ''),
(2437, 841, 112, 1, 3.00, 0, '', 1, ''),
(2438, 841, 112, 1, 3.00, 0, '', 1, ''),
(2441, 842, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Huevos\":\"Frito Suave\",\"ob\":\"te\"}'),
(2442, 843, 25, 1, 4.99, 0, '', 1, ''),
(2443, 843, 28, 1, 3.50, 0, '', 1, '{\"ob\":\"extra huevo sin lechuga\"}'),
(2444, 843, 28, 1, 3.50, 0, '', 1, '{\"ob\":\"extra de huevo\"}'),
(2445, 843, 33, 1, 0.99, 0, '', 1, ''),
(2446, 843, 33, 1, 0.99, 0, '', 1, ''),
(2447, 843, 23, 1, 3.99, 0, '', 1, ''),
(2448, 843, 65, 1, 1.75, 0, '', 1, '{\"ob\":\"sprite\"}'),
(2449, 844, 24, 1, 4.99, 0, '', 1, ''),
(2450, 844, 45, 1, 3.99, 0, '', 1, ''),
(2451, 844, 44, 1, 5.50, 0, '', 1, ''),
(2452, 845, 50, 1, 3.50, 0, '', 1, '{\"ob\":\"aparte\"}'),
(2453, 845, 71, 1, 1.99, 0, '', 1, '{\"ob\":\"sin hielo\"}'),
(2454, 845, 24, 1, 4.99, 0, '', 1, '{\"ob\":\"sin salsa sin lechuga\"}'),
(2455, 846, 44, 1, 5.50, 0, '', 1, ''),
(2456, 846, 25, 1, 4.99, 0, '', 1, ''),
(2458, 847, 44, 1, 5.50, 0, '', 1, ''),
(2459, 847, 42, 1, 3.99, 0, '', 1, ''),
(2460, 847, 45, 1, 3.99, 0, '', 1, ''),
(2461, 848, 25, 1, 4.99, 0, '', 1, ''),
(2462, 848, 65, 1, 1.75, 0, '', 1, '{\"Sabor_gaseosa\":\"Fiora Fresa\",\"ob\":\"\"}'),
(2463, 848, 65, 1, 1.75, 0, '', 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(2464, 848, 42, 1, 3.99, 0, '', 1, ''),
(2468, 849, 43, 1, 3.99, 0, '', 1, ''),
(2469, 849, 69, 1, 1.50, 0, '', 1, ''),
(2470, 849, 28, 1, 3.50, 0, '', 1, ''),
(2471, 849, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"fiora\"}'),
(2475, 850, 106, 1, 9.99, 0, '', 1, '{\"ob\":\"bien cocido\"}'),
(2476, 850, 28, 1, 3.50, 0, '', 1, '{\"ob\":\"sin pickles\"}'),
(2477, 850, 28, 1, 3.50, 0, '', 1, '{\"ob\":\"sin pickles\"}'),
(2478, 850, 113, 1, 0.99, 0, '', 1, ''),
(2479, 850, 113, 1, 0.99, 0, '', 1, ''),
(2480, 850, 113, 1, 0.99, 0, '', 1, ''),
(2482, 851, 28, 1, 3.50, 0, '', 1, ''),
(2483, 851, 64, 1, 0.99, 0, '', 1, ''),
(2485, 852, 53, 1, 13.99, 0, '', 1, '{\"Sabor_alitas\":\"BBQ Picante\",\"ob\":\"mostaza y maracuya\"}'),
(2486, 852, 67, 1, 0.99, 0, '', 1, '{\"ob\":\"al clima\"}'),
(2488, 853, 96, 1, 2.00, 0, '', 1, ''),
(2489, 854, 42, 1, 3.99, 0, '', 1, ''),
(2490, 854, 41, 1, 2.99, 0, '', 1, ''),
(2491, 854, 28, 1, 3.50, 0, '', 1, ''),
(2492, 854, 79, 1, 2.50, 0, '', 1, ''),
(2493, 854, 79, 1, 2.50, 0, '', 1, ''),
(2494, 854, 81, 1, 1.25, 0, '', 1, ''),
(2495, 854, 81, 1, 1.25, 0, '', 1, ''),
(2496, 854, 39, 1, 2.50, 0, '', 1, ''),
(2504, 855, 40, 1, 2.99, 0, '', 1, ''),
(2505, 855, 38, 1, 1.50, 0, '', 1, ''),
(2507, 856, 28, 1, 3.50, 0, '', 1, ''),
(2508, 856, 71, 1, 1.99, 0, '', 1, ''),
(2510, 857, 26, 1, 5.99, 0, '', 1, ''),
(2511, 857, 39, 1, 2.50, 0, '', 1, ''),
(2512, 857, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"BBQ Picante\",\"ob\":\"MOSTAZA Y MIEL\"}'),
(2513, 857, 25, 1, 4.99, 0, '', 1, ''),
(2514, 857, 65, 1, 1.75, 0, '', 1, '{\"ob\":\"COCA\"}'),
(2515, 857, 69, 1, 1.50, 0, '', 1, ''),
(2517, 858, 64, 1, 0.99, 0, '', 1, ''),
(2518, 859, 112, 1, 3.00, 0, '', 1, ''),
(2519, 859, 101, 1, 0.25, 0, '', 1, ''),
(2520, 859, 74, 1, 1.50, 0, '', 1, ''),
(2521, 860, 101, 1, 0.25, 0, '', 1, ''),
(2522, 860, 74, 1, 1.50, 0, '', 1, ''),
(2524, 861, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Frito Duro\",\"ob\":\"\"}'),
(2525, 861, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Frito Duro\",\"ob\":\"\"}'),
(2526, 861, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(2527, 861, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(2528, 861, 15, 1, 5.50, 0, '', 1, ''),
(2531, 862, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2532, 862, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2534, 863, 42, 1, 3.99, 0, '', 1, ''),
(2535, 863, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"coca\"}'),
(2537, 864, 28, 1, 3.50, 0, '', 1, ''),
(2538, 864, 24, 1, 4.99, 0, '', 1, ''),
(2539, 864, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"coca\"}'),
(2540, 865, 28, 1, 3.50, 0, '', 1, ''),
(2541, 866, 26, 1, 5.99, 0, '', 1, ''),
(2542, 866, 96, 1, 2.00, 0, '', 1, ''),
(2543, 866, 67, 1, 0.99, 0, '', 1, ''),
(2544, 866, 101, 1, 0.25, 0, '', 1, ''),
(2545, 866, 101, 1, 0.25, 0, '', 1, ''),
(2546, 866, 68, 1, 0.99, 0, '', 1, ''),
(2547, 867, 24, 1, 4.99, 0, '', 1, '{\"Vegetales\":\"Sin vegetales\",\"ob\":\"\"}'),
(2548, 867, 25, 1, 4.99, 0, '', 1, '{\"Vegetales\":\"Sin vegetales\",\"ob\":\"\"}'),
(2550, 868, 65, 1, 1.75, 0, '', 1, ''),
(2551, 869, 78, 1, 1.50, 0, '', 1, ''),
(2552, 869, 78, 1, 1.50, 0, '', 1, ''),
(2554, 870, 44, 1, 5.50, 0, '', 1, ''),
(2555, 870, 24, 1, 4.99, 0, '', 1, ''),
(2557, 871, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"\"}'),
(2558, 871, 24, 1, 4.99, 0, '', 1, ''),
(2559, 871, 107, 1, 8.99, 0, '', 1, ''),
(2560, 871, 100, 1, 4.99, 0, '', 1, ''),
(2564, 872, 104, 1, 8.99, 0, '', 1, ''),
(2565, 873, 96, 1, 2.00, 0, '', 1, ''),
(2566, 874, 44, 1, 5.50, 0, '', 1, ''),
(2567, 874, 44, 1, 5.50, 0, '', 1, ''),
(2568, 874, 64, 1, 0.99, 0, '', 1, ''),
(2569, 874, 64, 1, 0.99, 0, '', 1, ''),
(2573, 875, 74, 1, 1.50, 0, '', 1, ''),
(2574, 875, 78, 1, 1.50, 0, '', 1, ''),
(2575, 875, 78, 1, 1.50, 0, '', 1, ''),
(2576, 876, 1, 1, 3.99, 0, '', 1, ''),
(2577, 876, 95, 1, 0.35, 0, '', 1, ''),
(2579, 877, 112, 1, 3.00, 0, '', 1, ''),
(2580, 877, 112, 1, 3.00, 0, '', 1, ''),
(2582, 878, 112, 1, 3.00, 0, '', 1, ''),
(2583, 878, 112, 1, 3.00, 0, '', 1, ''),
(2584, 878, 112, 1, 3.00, 0, '', 1, ''),
(2585, 878, 112, 1, 3.00, 0, '', 1, ''),
(2586, 878, 112, 1, 3.00, 0, '', 1, ''),
(2589, 879, 3, 1, 5.50, 0, '', 1, ''),
(2590, 879, 3, 1, 5.50, 0, '', 1, ''),
(2592, 880, 26, 1, 5.99, 0, '', 1, ''),
(2593, 880, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"fanta\"}'),
(2595, 881, 96, 1, 2.00, 0, '', 1, ''),
(2596, 882, 40, 1, 2.99, 0, '', 1, '{\"ob\":\"sin salsas\"}'),
(2597, 882, 74, 1, 1.50, 0, '', 1, ''),
(2598, 882, 74, 1, 1.50, 0, '', 1, ''),
(2599, 882, 79, 1, 2.50, 0, '', 1, '{\"ob\":\"club\"}'),
(2600, 882, 62, 1, 1.99, 0, '', 1, ''),
(2601, 882, 58, 1, 4.99, 0, '', 1, ''),
(2603, 883, 40, 1, 2.99, 0, '', 1, ''),
(2604, 884, 28, 1, 3.50, 0, '', 1, ''),
(2605, 884, 33, 1, 0.99, 0, '', 1, ''),
(2606, 884, 64, 1, 0.99, 0, '', 1, ''),
(2607, 885, 74, 1, 1.50, 0, '', 1, ''),
(2608, 885, 20, 1, 5.50, 0, '', 1, ''),
(2610, 886, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"maracuya\"}'),
(2611, 886, 68, 1, 0.99, 0, '', 1, ''),
(2613, 887, 41, 1, 2.99, 0, '', 1, ''),
(2614, 888, 16, 1, 4.99, 0, '', 1, ''),
(2615, 888, 62, 1, 1.99, 0, '', 1, ''),
(2616, 888, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"sprite\"}'),
(2617, 889, 27, 1, 5.99, 0, '', 1, ''),
(2618, 889, 27, 1, 5.99, 0, '', 1, '{\"ob\":\"no tocino si huevo\"}'),
(2619, 889, 97, 1, 3.50, 0, '', 1, '{\"ob\":\"\"}'),
(2620, 890, 27, 1, 5.99, 0, '', 1, ''),
(2621, 890, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"bbq\"}'),
(2622, 890, 69, 1, 1.50, 0, '', 1, ''),
(2623, 891, 27, 1, 5.99, 0, '', 1, ''),
(2624, 891, 69, 1, 1.50, 0, '', 1, ''),
(2626, 892, 43, 1, 3.99, 0, '', 1, ''),
(2627, 892, 101, 1, 0.25, 0, '', 1, ''),
(2629, 893, 112, 1, 3.00, 0, '', 1, ''),
(2630, 893, 95, 1, 0.35, 0, '', 1, ''),
(2632, 894, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Agua\",\"ob\":\"\"}'),
(2633, 894, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"ob\":\"\"}'),
(2635, 895, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2636, 895, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2637, 895, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2638, 896, 2, 1, 4.99, 0, '', 1, '{\"Huevos\":\"Revuelto normal\",\"Bebida_Caliente\":\"Cafe en leche\",\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(2639, 896, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Cocinado Tibio\",\"ob\":\"\"}'),
(2641, 897, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"\"}'),
(2642, 897, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"Maracuya\",\"ob\":\"\"}'),
(2643, 897, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"Maracuya\",\"ob\":\"\"}'),
(2644, 897, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"Maracuya\",\"ob\":\"\"}'),
(2645, 897, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"\"}'),
(2646, 897, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"\"}'),
(2647, 897, 39, 1, 2.50, 0, '', 1, '{\"ob\":\"en cono\"}'),
(2648, 897, 58, 1, 4.99, 0, '', 1, '{\"ob\":\"salsa de la casa\"}'),
(2649, 897, 46, 1, 3.99, 0, '', 1, '{\"ob\":\"picante\"}'),
(2650, 897, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"\"}'),
(2651, 897, 28, 1, 3.50, 0, '', 1, '{\"ob\":\"\"}'),
(2652, 897, 58, 1, 4.99, 0, '', 1, '{\"ob\":\"Salsa de la casa\"}'),
(2656, 898, 16, 1, 4.99, 0, '', 1, ''),
(2657, 898, 62, 1, 1.99, 0, '', 1, ''),
(2658, 898, 74, 1, 1.50, 0, '', 1, ''),
(2659, 898, 42, 1, 3.99, 0, '', 1, ''),
(2660, 898, 79, 1, 2.50, 0, '', 1, '{\"ob\":\"club\"}'),
(2663, 899, 39, 1, 2.50, 0, '', 1, ''),
(2664, 900, 14, 1, 5.50, 0, '', 1, ''),
(2665, 900, 69, 1, 1.50, 0, '', 1, ''),
(2667, 901, 67, 1, 0.99, 0, '', 1, ''),
(2668, 902, 42, 1, 3.99, 0, '', 1, '{\"BBQ\":\"Sin BBQ\",\"ob\":\"\"}'),
(2669, 902, 43, 1, 3.99, 0, '', 1, ''),
(2670, 902, 39, 1, 2.50, 0, '', 1, ''),
(2671, 902, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"Cocacola\"}'),
(2672, 902, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"Sprite\"}'),
(2673, 902, 66, 1, 1.25, 0, '', 1, ''),
(2674, 902, 67, 1, 0.99, 0, '', 1, ''),
(2675, 902, 69, 1, 1.50, 0, '', 1, '{\"ob\":\"Sin hielo\"}'),
(2683, 903, 23, 1, 3.99, 0, '', 1, ''),
(2684, 903, 52, 1, 8.99, 0, '', 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"\"}'),
(2685, 903, 40, 1, 2.99, 0, '', 1, ''),
(2686, 903, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"Cocacola\"}'),
(2687, 903, 69, 1, 1.50, 0, '', 1, '{\"ob\":\"Al clima\"}'),
(2690, 904, 58, 1, 4.99, 0, '', 1, ''),
(2691, 904, 58, 1, 4.99, 0, '', 1, ''),
(2692, 904, 58, 1, 4.99, 0, '', 1, ''),
(2693, 904, 58, 1, 4.99, 0, '', 1, ''),
(2694, 904, 58, 1, 4.99, 0, '', 1, ''),
(2695, 904, 70, 1, 1.99, 0, '', 1, ''),
(2696, 904, 71, 1, 1.99, 0, '', 1, ''),
(2697, 904, 70, 1, 1.99, 0, '', 1, ''),
(2705, 905, 56, 1, 5.50, 0, '', 1, ''),
(2706, 905, 27, 1, 5.99, 0, '', 1, ''),
(2707, 905, 27, 1, 5.99, 0, '', 1, ''),
(2708, 905, 44, 1, 5.50, 0, '', 1, ''),
(2709, 905, 42, 1, 3.99, 0, '', 1, ''),
(2710, 905, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"sprite\"}'),
(2711, 905, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"sprite\"}'),
(2712, 905, 71, 1, 1.99, 0, '', 1, ''),
(2713, 905, 71, 1, 1.99, 0, '', 1, ''),
(2714, 905, 71, 1, 1.99, 0, '', 1, ''),
(2715, 905, 67, 1, 0.99, 0, '', 1, ''),
(2720, 906, 71, 1, 1.99, 0, '', 1, ''),
(2721, 906, 71, 1, 1.99, 0, '', 1, ''),
(2722, 906, 101, 1, 0.25, 0, '', 1, ''),
(2723, 906, 101, 1, 0.25, 0, '', 1, ''),
(2727, 907, 71, 1, 1.99, 0, '', 1, ''),
(2728, 907, 101, 1, 0.25, 0, '', 1, ''),
(2730, 908, 71, 1, 1.99, 0, '', 1, ''),
(2731, 909, 78, 1, 1.50, 0, '', 1, '{\"ob\":\"te \"}'),
(2732, 909, 15, 1, 5.50, 0, '', 1, ''),
(2733, 909, 27, 1, 5.99, 0, '', 1, ''),
(2734, 909, 113, 1, 0.99, 0, '', 1, ''),
(2738, 910, 39, 1, 2.50, 0, '', 1, ''),
(2739, 910, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"coca\"}'),
(2741, 911, 78, 1, 1.50, 0, '', 1, '{\"ob\":\"te\"}'),
(2742, 911, 106, 1, 9.99, 0, '', 1, ''),
(2743, 911, 27, 1, 5.99, 0, '', 1, ''),
(2744, 911, 113, 1, 0.99, 0, '', 1, ''),
(2748, 912, 27, 1, 5.99, 0, '', 1, ''),
(2749, 912, 27, 1, 5.99, 0, '', 1, ''),
(2750, 912, 39, 1, 2.50, 0, '', 1, ''),
(2751, 912, 65, 1, 1.75, 0, '', 1, ''),
(2752, 912, 101, 1, 0.25, 0, '', 1, ''),
(2753, 912, 101, 1, 0.25, 0, '', 1, ''),
(2754, 912, 101, 1, 0.25, 0, '', 1, ''),
(2755, 913, 27, 1, 5.99, 0, '', 1, ''),
(2756, 913, 27, 1, 5.99, 0, '', 1, ''),
(2757, 913, 51, 1, 5.99, 0, '', 1, ''),
(2758, 913, 64, 1, 0.99, 0, '', 1, ''),
(2759, 913, 64, 1, 0.99, 0, '', 1, ''),
(2762, 914, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Frutilla\",\"Huevos\":\"Cocinado Duro\",\"ob\":\"\"}'),
(2763, 914, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Leche\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2764, 914, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Leche\",\"Bebida_Fria\":\"Jugo Frutilla\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2765, 914, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2766, 914, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Leche\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2769, 915, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Agua\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2770, 915, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Agua\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2772, 916, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2773, 916, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2774, 916, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Bolon mixto\",\"Bebida_Caliente\":\"Agua\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2775, 916, 2, 1, 4.99, 0, '', 1, '{\"Huevos\":\"Frito Suave\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(2779, 917, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Frito Duro\",\"ob\":\"\"}'),
(2780, 917, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Frito Duro\",\"ob\":\"\"}'),
(2781, 917, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Frito Duro\",\"ob\":\"\"}'),
(2782, 917, 2, 1, 4.99, 0, '', 1, '{\"Huevos\":\"Frito Duro\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(2786, 918, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Frito Duro\",\"ob\":\"\"}'),
(2787, 918, 32, 1, 0.99, 0, '', 1, ''),
(2789, 919, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2790, 919, 5, 1, 5.99, 0, '', 1, '{\"Bebida_Caliente\":\"Agua\",\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(2792, 920, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2793, 920, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2794, 920, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2795, 920, 95, 1, 0.35, 0, '', 1, ''),
(2796, 921, 2, 1, 4.99, 0, '', 1, ''),
(2797, 921, 3, 1, 5.50, 0, '', 1, ''),
(2798, 921, 3, 1, 5.50, 0, '', 1, ''),
(2799, 922, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"\"}'),
(2800, 922, 43, 1, 3.99, 0, '', 1, ''),
(2801, 922, 78, 1, 1.50, 0, '', 1, '{\"ob\":\"CAFE LECHE\"}'),
(2802, 923, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"BBQ Picante\",\"ob\":\"ENSALADA\"}'),
(2803, 923, 58, 1, 4.99, 0, '', 1, ''),
(2804, 923, 78, 1, 1.50, 0, '', 1, '{\"ob\":\"AMERICANO\r\n\"}'),
(2805, 924, 27, 1, 5.99, 0, '', 1, ''),
(2806, 924, 106, 1, 9.99, 0, '', 1, '{\"ob\":\"TERMINO MEDIO\"}'),
(2807, 924, 106, 1, 9.99, 0, '', 1, ''),
(2808, 924, 107, 1, 8.99, 0, '', 1, ''),
(2809, 924, 27, 1, 5.99, 0, '', 1, '{\"ob\":\"CHAMPIÑON\"}'),
(2810, 924, 62, 1, 1.99, 0, '', 1, '{\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(2811, 924, 67, 1, 0.99, 0, '', 1, ''),
(2812, 924, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"COCA\"}'),
(2813, 924, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"SPRITE\"}'),
(2814, 924, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"SPRITE\"}'),
(2815, 925, 25, 1, 4.99, 0, '', 1, ''),
(2816, 925, 26, 1, 5.99, 0, '', 1, ''),
(2817, 925, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"cocacola\"}'),
(2818, 925, 78, 1, 1.50, 0, '', 1, '{\"ob\":\"cafe negro\"}'),
(2822, 926, 25, 1, 4.99, 0, '', 1, ''),
(2823, 926, 111, 1, 2.00, 0, '', 1, ''),
(2825, 927, 26, 1, 5.99, 0, '', 1, ''),
(2826, 927, 27, 1, 5.99, 0, '', 1, ''),
(2828, 928, 40, 1, 2.99, 0, '', 1, ''),
(2829, 929, 42, 1, 3.99, 0, '', 1, ''),
(2830, 929, 43, 1, 3.99, 0, '', 1, ''),
(2831, 929, 44, 1, 5.50, 0, '', 1, ''),
(2832, 929, 71, 1, 1.99, 0, '', 1, ''),
(2833, 929, 100, 1, 4.99, 0, '', 1, ''),
(2834, 929, 96, 1, 2.00, 0, '', 1, ''),
(2836, 930, 78, 1, 1.50, 0, '', 1, ''),
(2837, 930, 78, 1, 1.50, 0, '', 1, ''),
(2838, 930, 25, 1, 4.99, 0, '', 1, ''),
(2839, 931, 1, 1, 3.99, 0, '', 1, ''),
(2840, 931, 2, 1, 4.99, 0, '', 1, ''),
(2841, 931, 3, 1, 5.50, 0, '', 1, ''),
(2842, 931, 4, 1, 4.99, 0, '', 1, ''),
(2843, 931, 95, 1, 0.35, 0, '', 1, ''),
(2846, 932, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2847, 932, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2848, 932, 2, 1, 4.99, 0, '', 1, '{\"Huevos\":\"Frito Duro\",\"Bebida_Caliente\":\"Leche\",\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(2849, 933, 27, 1, 5.99, 0, '', 1, ''),
(2850, 933, 97, 1, 3.50, 0, '', 1, ''),
(2852, 934, 28, 1, 3.50, 0, '', 1, ''),
(2853, 934, 71, 1, 1.99, 0, '', 1, ''),
(2854, 934, 33, 1, 0.99, 0, '', 1, ''),
(2855, 935, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"BBQ Picante\",\"ob\":\"moztaza y miel\"}'),
(2856, 936, 27, 1, 5.99, 0, '', 1, ''),
(2857, 936, 43, 1, 3.99, 0, '', 1, ''),
(2858, 936, 65, 1, 1.75, 0, '', 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(2859, 937, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"\"}'),
(2860, 938, 2, 1, 4.99, 0, '', 1, '{\"Huevos\":\"Revuelto normal\",\"Bebida_Caliente\":\"Cafe en leche\",\"ob\":\"\"}'),
(2861, 938, 2, 1, 4.99, 0, '', 1, '{\"Huevos\":\"Revuelto normal\",\"Bebida_Caliente\":\"Cafe en leche\",\"ob\":\"\"}'),
(2862, 938, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2863, 938, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2864, 938, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2865, 938, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2866, 938, 6, 1, 3.99, 0, '', 1, ''),
(2867, 939, 8, 1, 2.99, 0, '', 1, ''),
(2868, 940, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2869, 940, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2870, 940, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2871, 940, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2872, 940, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2873, 940, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2875, 941, 3, 1, 5.50, 0, '', 1, ''),
(2876, 941, 62, 1, 1.99, 0, '', 1, '{\"ob\":\"PIÑA\"}'),
(2877, 941, 116, 1, 3.50, 0, '', 1, ''),
(2878, 942, 116, 1, 3.50, 0, '', 1, ''),
(2879, 942, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"COCA\"}'),
(2880, 942, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Cocinado Duro\",\"ob\":\"PIÑA JUGO\"}'),
(2881, 942, 6, 1, 3.99, 0, '', 1, ''),
(2882, 943, 64, 1, 0.99, 0, '', 1, ''),
(2883, 944, 79, 1, 2.50, 0, '', 1, ''),
(2884, 944, 79, 1, 2.50, 0, '', 1, ''),
(2885, 944, 79, 1, 2.50, 0, '', 1, ''),
(2886, 944, 79, 1, 2.50, 0, '', 1, ''),
(2887, 944, 79, 1, 2.50, 0, '', 1, ''),
(2890, 945, 40, 1, 2.99, 0, '', 1, ''),
(2891, 946, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(2892, 946, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2894, 947, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2895, 947, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2897, 948, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2898, 948, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2900, 949, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Frito Duro\",\"ob\":\"\"}'),
(2901, 949, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(2902, 949, 2, 1, 4.99, 0, '', 1, '{\"Huevos\":\"Revuelto normal\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"ob\":\"\"}'),
(2903, 949, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2907, 950, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2908, 950, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2909, 950, 6, 1, 3.99, 0, '', 1, '{\"ob\":\"SIN PAPAYA\"}'),
(2910, 951, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo chicharron\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Frito Suave\",\"ob\":\"JUGO DE PIÑA\"}'),
(2911, 952, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2912, 952, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo queso\",\"Bebida_Caliente\":\"Agua\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Cocinado Duro\",\"ob\":\"\"}'),
(2914, 953, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Leche\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2915, 953, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Chocolate\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2916, 953, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Leche\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(2917, 954, 98, 1, 0.99, 0, '', 1, ''),
(2918, 955, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe en leche\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Frito Duro\",\"ob\":\"\"}'),
(2919, 955, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Frito Duro\",\"ob\":\"\"}'),
(2921, 956, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Leche\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Cocinado Duro\",\"ob\":\"\"}'),
(2922, 956, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Cocinado Duro\",\"ob\":\"\"}'),
(2924, 957, 95, 1, 0.35, 0, '', 1, ''),
(2925, 958, 97, 1, 3.50, 0, '', 1, ''),
(2926, 958, 97, 1, 3.50, 0, '', 1, ''),
(2928, 959, 3, 1, 5.50, 0, '', 1, ''),
(2929, 960, 53, 1, 13.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"MARACUYA Y BBQ\"}'),
(2930, 960, 18, 1, 3.99, 0, '', 1, ''),
(2931, 960, 109, 1, 2.25, 0, '', 1, ''),
(2932, 961, 50, 1, 3.50, 0, '', 1, ''),
(2933, 961, 41, 1, 2.99, 0, '', 1, ''),
(2935, 962, 42, 1, 3.99, 0, '', 1, ''),
(2936, 962, 43, 1, 3.99, 0, '', 1, ''),
(2937, 962, 65, 1, 1.75, 0, '', 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(2938, 963, 46, 1, 3.99, 0, '', 1, ''),
(2939, 964, 46, 1, 3.99, 0, '', 1, ''),
(2940, 964, 27, 1, 5.99, 0, '', 1, ''),
(2941, 964, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"INKA\"}'),
(2942, 965, 44, 1, 5.50, 0, '', 1, ''),
(2943, 965, 26, 1, 5.99, 0, '', 1, ''),
(2944, 965, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"COCACOLA\"}'),
(2945, 965, 71, 1, 1.99, 0, '', 1, ''),
(2949, 966, 27, 1, 5.99, 0, '', 1, ''),
(2950, 966, 15, 1, 5.50, 0, '', 1, ''),
(2951, 966, 28, 1, 3.50, 0, '', 1, ''),
(2952, 966, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"INKA\"}'),
(2953, 966, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"FIORA\"}'),
(2956, 967, 25, 1, 4.99, 0, '', 1, ''),
(2957, 967, 44, 1, 5.50, 0, '', 1, ''),
(2958, 967, 71, 1, 1.99, 0, '', 1, ''),
(2959, 968, 28, 1, 3.50, 0, '', 1, ''),
(2960, 969, 52, 1, 8.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"bbq y maracuya\"}'),
(2961, 969, 64, 1, 0.99, 0, '', 1, ''),
(2962, 969, 64, 1, 0.99, 0, '', 1, ''),
(2963, 970, 51, 1, 5.99, 0, '', 1, '{\"Mayonesa\":\"Sin mayonesa\",\"ob\":\"bbq picante \r\n\"}'),
(2964, 971, 108, 1, 9.99, 0, '', 1, ''),
(2965, 971, 43, 1, 3.99, 0, '', 1, ''),
(2967, 972, 108, 1, 9.99, 0, '', 1, ''),
(2968, 972, 52, 1, 8.99, 0, '', 1, ''),
(2970, 973, 96, 1, 2.00, 0, '', 1, ''),
(2971, 974, 40, 1, 2.99, 0, '', 1, ''),
(2972, 974, 64, 1, 0.99, 0, '', 1, ''),
(2974, 975, 74, 1, 1.50, 0, '', 1, ''),
(2975, 975, 101, 1, 0.25, 0, '', 1, ''),
(2977, 976, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Frito Duro\",\"ob\":\"\"}'),
(2978, 976, 74, 1, 1.50, 0, '', 1, ''),
(2980, 977, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"ob\":\"\"}'),
(2981, 978, 64, 1, 0.99, 0, '', 1, ''),
(2982, 979, 71, 1, 1.99, 0, '', 1, ''),
(2983, 979, 71, 1, 1.99, 0, '', 1, ''),
(2985, 980, 42, 1, 3.99, 0, '', 1, ''),
(2986, 981, 71, 1, 1.99, 0, '', 1, ''),
(2987, 982, 9, 1, 0.99, 0, '', 1, ''),
(2988, 983, 42, 1, 3.99, 0, '', 1, ''),
(2989, 983, 64, 1, 0.99, 0, '', 1, ''),
(2991, 984, 52, 1, 8.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"3 bbq  3 maracuya\"}'),
(2992, 985, 24, 1, 4.99, 0, '', 1, ''),
(2993, 985, 79, 1, 2.50, 0, '', 1, ''),
(2995, 986, 28, 1, 3.50, 0, '', 1, ''),
(2996, 986, 40, 1, 2.99, 0, '', 1, ''),
(2997, 986, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(2998, 987, 25, 1, 4.99, 0, '', 1, ''),
(2999, 987, 97, 1, 3.50, 0, '', 1, ''),
(3001, 988, 79, 1, 2.50, 0, '', 1, ''),
(3002, 989, 36, 1, 0.99, 0, '', 1, ''),
(3003, 989, 35, 1, 1.50, 0, '', 1, ''),
(3005, 990, 35, 1, 1.50, 0, '', 1, ''),
(3006, 991, 46, 1, 3.99, 0, '', 1, ''),
(3007, 991, 101, 1, 0.25, 0, '', 1, ''),
(3009, 992, 24, 1, 4.99, 0, '', 1, ''),
(3010, 992, 25, 1, 4.99, 0, '', 1, ''),
(3011, 992, 25, 1, 4.99, 0, '', 1, ''),
(3012, 992, 64, 1, 0.99, 0, '', 1, ''),
(3013, 992, 64, 1, 0.99, 0, '', 1, ''),
(3014, 992, 67, 1, 0.99, 0, '', 1, ''),
(3016, 993, 96, 1, 2.00, 0, '', 1, ''),
(3017, 994, 2, 1, 4.99, 0, '', 1, '{\"Huevos\":\"Revuelto normal\",\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(3018, 994, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(3020, 995, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo queso\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(3021, 996, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(3022, 996, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(3023, 996, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo queso\",\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(3024, 996, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3025, 996, 9, 1, 0.99, 0, '', 1, ''),
(3028, 997, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo queso\",\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Cocinado Duro\",\"ob\":\"\"}'),
(3029, 997, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3030, 997, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Cocinado Duro\",\"ob\":\"\"}'),
(3031, 998, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3032, 998, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3033, 998, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3034, 998, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3035, 998, 98, 1, 0.99, 0, '', 1, '{\"Huevos\":\"Cocinado Duro\",\"ob\":\"\"}'),
(3036, 998, 98, 1, 0.99, 0, '', 1, '{\"Huevos\":\"Cocinado Duro\",\"ob\":\"\"}'),
(3038, 999, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(3039, 999, 4, 1, 4.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"ob\":\"\"}'),
(3040, 999, 6, 1, 3.99, 0, '', 1, ''),
(3041, 1000, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Frito Duro\",\"ob\":\"\"}'),
(3042, 1000, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3043, 1000, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3044, 1000, 6, 1, 3.99, 0, '', 1, ''),
(3048, 1001, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3049, 1001, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3050, 1001, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(3051, 1002, 115, 1, 0.01, 0, '', 1, '{\"Bebida_Caliente\":\"Sin Bebida\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Cocinado Duro\",\"ob\":\"\"}'),
(3052, 1002, 115, 1, 0.01, 0, '', 1, '{\"Bebida_Caliente\":\"Agua\",\"Bebida_Fria\":\"Sin bebida\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3053, 1002, 115, 1, 0.01, 0, '', 1, '{\"Bebida_Caliente\":\"Agua\",\"Bebida_Fria\":\"Sin bebida\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}');
INSERT INTO `detalle_factura` (`correlativo`, `nofactura`, `codproducto`, `cantidad`, `precio_venta`, `mesa`, `atributos`, `estatus_dt`, `observaciones`) VALUES
(3054, 1002, 115, 1, 0.01, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3058, 1003, 4, 1, 4.99, 0, '', 1, ''),
(3059, 1004, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3060, 1004, 14, 1, 5.50, 0, '', 1, ''),
(3061, 1004, 78, 1, 1.50, 0, '', 1, '{\"ob\":\"leche\"}'),
(3062, 1005, 9, 1, 0.99, 0, '', 1, ''),
(3063, 1005, 9, 1, 0.99, 0, '', 1, ''),
(3064, 1005, 101, 1, 0.25, 0, '', 1, ''),
(3065, 1006, 42, 1, 3.99, 0, '', 1, ''),
(3066, 1006, 46, 1, 3.99, 0, '', 1, ''),
(3067, 1006, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"bbq\"}'),
(3068, 1006, 113, 1, 0.99, 0, '', 1, ''),
(3069, 1006, 113, 1, 0.99, 0, '', 1, ''),
(3072, 1007, 64, 1, 0.99, 0, '', 1, ''),
(3073, 1008, 27, 1, 5.99, 0, '', 1, ''),
(3074, 1008, 33, 1, 0.99, 0, '', 1, ''),
(3075, 1009, 3, 1, 5.50, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(3076, 1010, 3, 1, 5.50, 0, '', 1, ''),
(3077, 1010, 3, 1, 5.50, 0, '', 1, ''),
(3078, 1010, 3, 1, 5.50, 0, '', 1, ''),
(3079, 1010, 3, 1, 5.50, 0, '', 1, ''),
(3083, 1011, 40, 1, 2.99, 0, '', 1, ''),
(3084, 1011, 41, 1, 2.99, 0, '', 1, ''),
(3086, 1012, 44, 1, 5.50, 0, '', 1, ''),
(3087, 1012, 78, 1, 1.50, 0, '', 1, '{\"ob\":\"te\"}'),
(3088, 1012, 64, 1, 0.99, 0, '', 1, ''),
(3089, 1013, 28, 1, 3.50, 0, '', 1, ''),
(3090, 1013, 3, 1, 5.50, 0, '', 1, ''),
(3091, 1013, 3, 1, 5.50, 0, '', 1, ''),
(3092, 1013, 64, 1, 0.99, 0, '', 1, ''),
(3096, 1014, 24, 1, 4.99, 0, '', 1, ''),
(3097, 1014, 24, 1, 4.99, 0, '', 1, ''),
(3098, 1014, 46, 1, 3.99, 0, '', 1, ''),
(3099, 1014, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(3100, 1014, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Inca\",\"ob\":\"\"}'),
(3101, 1014, 96, 1, 2.00, 0, '', 1, ''),
(3102, 1014, 29, 1, 0.99, 0, '', 1, ''),
(3103, 1015, 27, 1, 5.99, 0, '', 1, ''),
(3104, 1015, 27, 1, 5.99, 0, '', 1, ''),
(3105, 1015, 109, 1, 2.25, 0, '', 1, ''),
(3106, 1016, 44, 1, 5.50, 0, '', 1, ''),
(3107, 1016, 44, 1, 5.50, 0, '', 1, ''),
(3108, 1016, 26, 1, 5.99, 0, '', 1, ''),
(3109, 1016, 26, 1, 5.99, 0, '', 1, ''),
(3110, 1016, 74, 1, 1.50, 0, '', 1, ''),
(3111, 1016, 80, 1, 3.50, 0, '', 1, '{\"ob\":\"corona\"}'),
(3112, 1016, 74, 1, 1.50, 0, '', 1, ''),
(3113, 1017, 25, 1, 4.99, 0, '', 1, ''),
(3114, 1017, 101, 1, 0.25, 0, '', 1, ''),
(3116, 1018, 112, 1, 3.00, 0, '', 1, ''),
(3117, 1018, 112, 1, 3.00, 0, '', 1, ''),
(3119, 1019, 67, 1, 0.99, 0, '', 1, ''),
(3120, 1020, 26, 1, 5.99, 0, '', 1, ''),
(3121, 1020, 26, 1, 5.99, 0, '', 1, ''),
(3122, 1020, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Fiora Manzana\",\"ob\":\"\"}'),
(3123, 1020, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Sprite\",\"ob\":\"\"}'),
(3124, 1021, 2, 1, 4.99, 0, '', 1, '{\"Huevos\":\"Revuelto normal\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(3125, 1021, 4, 1, 4.99, 0, '', 1, '{\"ob\":\"\"}'),
(3127, 1022, 14, 1, 5.50, 0, '', 1, '{\"ob\":\"piklet\"}'),
(3128, 1022, 28, 1, 3.50, 0, '', 1, ''),
(3129, 1022, 96, 1, 2.00, 0, '', 1, ''),
(3130, 1022, 64, 1, 0.99, 0, '', 1, ''),
(3134, 1023, 28, 1, 3.50, 0, '', 1, ''),
(3135, 1023, 28, 1, 3.50, 0, '', 1, ''),
(3136, 1023, 28, 1, 3.50, 0, '', 1, ''),
(3137, 1023, 65, 1, 1.75, 0, '', 1, ''),
(3138, 1023, 65, 1, 1.75, 0, '', 1, ''),
(3141, 1024, 106, 1, 9.99, 0, '', 1, ''),
(3142, 1024, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"\"}'),
(3143, 1024, 78, 1, 1.50, 0, '', 1, ''),
(3144, 1024, 66, 1, 1.25, 0, '', 1, ''),
(3148, 1025, 3, 1, 5.50, 0, '', 1, ''),
(3149, 1025, 74, 1, 1.50, 0, '', 1, ''),
(3150, 1026, 25, 1, 4.99, 0, '', 1, ''),
(3151, 1026, 64, 1, 0.99, 0, '', 1, ''),
(3153, 1027, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Agua\",\"Huevos\":\"Frito Duro\",\"ob\":\"\"}'),
(3154, 1027, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Agua\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(3155, 1027, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(3156, 1027, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3160, 1028, 25, 1, 4.99, 0, '', 1, ''),
(3161, 1028, 58, 1, 4.99, 0, '', 1, ''),
(3162, 1028, 64, 1, 0.99, 0, '', 1, ''),
(3163, 1029, 44, 1, 5.50, 0, '', 1, ''),
(3164, 1029, 107, 1, 8.99, 0, '', 1, ''),
(3165, 1029, 52, 1, 8.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"bbq , maracuya\"}'),
(3166, 1029, 32, 1, 0.99, 0, '', 1, ''),
(3167, 1029, 29, 1, 0.99, 0, '', 1, ''),
(3168, 1029, 74, 1, 1.50, 0, '', 1, ''),
(3169, 1029, 101, 1, 0.25, 0, '', 1, ''),
(3170, 1029, 65, 1, 1.75, 0, '', 1, ''),
(3178, 1030, 46, 1, 3.99, 0, '', 1, ''),
(3179, 1030, 27, 1, 5.99, 0, '', 1, ''),
(3180, 1030, 24, 1, 4.99, 0, '', 1, ''),
(3181, 1030, 64, 1, 0.99, 0, '', 1, ''),
(3182, 1030, 64, 1, 0.99, 0, '', 1, ''),
(3185, 1031, 53, 1, 13.99, 0, '', 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"\"}'),
(3186, 1031, 64, 1, 0.99, 0, '', 1, ''),
(3187, 1031, 64, 1, 0.99, 0, '', 1, ''),
(3188, 1032, 23, 1, 3.99, 0, '', 1, '{\"ob\":\"no bbq\"}'),
(3189, 1032, 27, 1, 5.99, 0, '', 1, ''),
(3190, 1032, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(3191, 1032, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(3195, 1033, 40, 1, 2.99, 0, '', 1, ''),
(3196, 1034, 53, 1, 13.99, 0, '', 1, ''),
(3197, 1035, 5, 1, 5.99, 0, '', 1, ''),
(3198, 1035, 4, 1, 4.99, 0, '', 1, ''),
(3199, 1035, 3, 1, 5.50, 0, '', 1, ''),
(3200, 1036, 15, 1, 5.50, 0, '', 1, ''),
(3201, 1036, 62, 1, 1.99, 0, '', 1, ''),
(3202, 1036, 78, 1, 1.50, 0, '', 1, '{\"ob\":\"cafe en leche\"}'),
(3203, 1036, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo queso\",\"Bebida_Caliente\":\"Cafe en leche\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3204, 1036, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"ob\":\"\"}'),
(3205, 1036, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"ob\":\"\"}'),
(3206, 1037, 27, 1, 5.99, 0, '', 1, ''),
(3207, 1037, 64, 1, 0.99, 0, '', 1, ''),
(3209, 1038, 28, 1, 3.50, 0, '', 1, ''),
(3210, 1039, 28, 1, 3.50, 0, '', 1, ''),
(3211, 1039, 52, 1, 8.99, 0, '', 1, ''),
(3212, 1039, 32, 1, 0.99, 0, '', 1, ''),
(3213, 1039, 29, 1, 0.99, 0, '', 1, ''),
(3214, 1039, 44, 1, 5.50, 0, '', 1, ''),
(3215, 1039, 109, 1, 2.25, 0, '', 1, ''),
(3217, 1040, 96, 1, 2.00, 0, '', 1, ''),
(3218, 1040, 64, 1, 0.99, 0, '', 1, ''),
(3220, 1041, 42, 1, 3.99, 0, '', 1, ''),
(3221, 1041, 63, 1, 2.50, 0, '', 1, ''),
(3222, 1041, 63, 1, 2.50, 0, '', 1, ''),
(3223, 1042, 15, 1, 5.50, 0, '', 1, ''),
(3224, 1043, 49, 1, 2.99, 0, '', 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"\"}'),
(3225, 1043, 44, 1, 5.50, 0, '', 1, ''),
(3226, 1043, 69, 1, 1.50, 0, '', 1, ''),
(3227, 1043, 69, 1, 1.50, 0, '', 1, ''),
(3231, 1044, 62, 1, 1.99, 0, '', 1, ''),
(3232, 1044, 62, 1, 1.99, 0, '', 1, ''),
(3233, 1044, 44, 1, 5.50, 0, '', 1, ''),
(3234, 1044, 104, 1, 8.99, 0, '', 1, ''),
(3235, 1044, 26, 1, 5.99, 0, '', 1, ''),
(3236, 1044, 35, 1, 1.50, 0, '', 1, ''),
(3238, 1045, 112, 1, 3.00, 0, '', 1, ''),
(3239, 1045, 112, 1, 3.00, 0, '', 1, ''),
(3240, 1045, 6, 1, 3.99, 0, '', 1, ''),
(3241, 1045, 112, 1, 3.00, 0, '', 1, ''),
(3242, 1045, 112, 1, 3.00, 0, '', 1, ''),
(3245, 1046, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"\"}'),
(3246, 1046, 24, 1, 4.99, 0, '', 1, ''),
(3247, 1046, 64, 1, 0.99, 0, '', 1, ''),
(3248, 1047, 64, 1, 0.99, 0, '', 1, ''),
(3249, 1048, 24, 1, 4.99, 0, '', 1, ''),
(3250, 1048, 101, 1, 0.25, 0, '', 1, ''),
(3251, 1048, 28, 1, 3.50, 0, '', 1, ''),
(3252, 1049, 27, 1, 5.99, 0, '', 1, '{\"ob\":\"SIN CEBOLLA\"}'),
(3253, 1049, 113, 1, 0.99, 0, '', 1, ''),
(3255, 1050, 27, 1, 5.99, 0, '', 1, ''),
(3256, 1050, 113, 1, 0.99, 0, '', 1, ''),
(3258, 1051, 28, 1, 3.50, 0, '', 1, ''),
(3259, 1051, 71, 1, 1.99, 0, '', 1, ''),
(3260, 1051, 80, 1, 3.50, 0, '', 1, ''),
(3261, 1051, 81, 1, 1.25, 0, '', 1, ''),
(3262, 1051, 25, 1, 4.99, 0, '', 1, ''),
(3263, 1051, 42, 1, 3.99, 0, '', 1, ''),
(3264, 1051, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Inca\",\"ob\":\"\"}'),
(3265, 1052, 40, 1, 2.99, 0, '', 1, ''),
(3266, 1053, 9, 1, 0.99, 0, '', 1, ''),
(3267, 1054, 9, 1, 0.99, 0, '', 1, ''),
(3268, 1055, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Frito Duro\",\"ob\":\"\"}'),
(3269, 1055, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3271, 1056, 4, 1, 4.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"ob\":\"\"}'),
(3272, 1056, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"ob\":\"\"}'),
(3273, 1056, 112, 1, 3.00, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"ob\":\"\"}'),
(3274, 1057, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Bolon chicharron\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3275, 1057, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Bolon queso\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3276, 1057, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3277, 1057, 33, 1, 0.99, 0, '', 1, '{\"ob\":\"FRITO DURO\"}'),
(3281, 1058, 74, 1, 1.50, 0, '', 1, ''),
(3282, 1058, 74, 1, 1.50, 0, '', 1, ''),
(3283, 1058, 9, 1, 0.99, 0, '', 1, ''),
(3284, 1058, 98, 1, 0.99, 0, '', 1, ''),
(3285, 1058, 51, 1, 5.99, 0, '', 1, ''),
(3286, 1058, 101, 1, 0.25, 0, '', 1, ''),
(3287, 1058, 101, 1, 0.25, 0, '', 1, ''),
(3288, 1059, 40, 1, 2.99, 0, '', 1, ''),
(3289, 1059, 40, 1, 2.99, 0, '', 1, ''),
(3290, 1059, 113, 1, 0.99, 0, '', 1, ''),
(3291, 1060, 25, 1, 4.99, 0, '', 1, ''),
(3292, 1060, 113, 1, 0.99, 0, '', 1, ''),
(3294, 1061, 113, 1, 0.99, 0, '', 1, ''),
(3295, 1062, 107, 1, 8.99, 0, '', 1, ''),
(3296, 1062, 52, 1, 8.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"bbq , maracuyA\"}'),
(3298, 1063, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"BBQ\"}'),
(3299, 1063, 42, 1, 3.99, 0, '', 1, '{\"BBQ\":\"Sin BBQ\",\"ob\":\"\"}'),
(3300, 1063, 65, 1, 1.75, 0, '', 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(3301, 1064, 25, 1, 4.99, 0, '', 1, '{\"BBQ\":\"Sin BBQ\",\"ob\":\"SIN PICKLES\"}'),
(3302, 1065, 44, 1, 5.50, 0, '', 1, ''),
(3303, 1065, 26, 1, 5.99, 0, '', 1, ''),
(3304, 1065, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Fanta\",\"ob\":\"\"}'),
(3305, 1066, 78, 1, 1.50, 0, '', 1, '{\"ob\":\"LECHE\"}'),
(3306, 1066, 78, 1, 1.50, 0, '', 1, '{\"ob\":\"LECHE\"}'),
(3307, 1066, 16, 1, 4.99, 0, '', 1, ''),
(3308, 1066, 33, 1, 0.99, 0, '', 1, ''),
(3312, 1067, 28, 1, 3.50, 0, '', 1, ''),
(3313, 1067, 41, 1, 2.99, 0, '', 1, '{\"ob\":\"SIN SALSAS\"}'),
(3314, 1067, 41, 1, 2.99, 0, '', 1, '{\"ob\":\"SIN SALSAS\"}'),
(3315, 1068, 46, 1, 3.99, 0, '', 1, ''),
(3316, 1069, 65, 1, 1.75, 0, '', 1, ''),
(3317, 1069, 54, 1, 17.99, 0, '', 1, ''),
(3318, 1069, 114, 1, 1.50, 0, '', 1, ''),
(3319, 1069, 101, 1, 0.25, 0, '', 1, ''),
(3320, 1069, 101, 1, 0.25, 0, '', 1, ''),
(3323, 1070, 112, 1, 3.00, 0, '', 1, ''),
(3324, 1070, 112, 1, 3.00, 0, '', 1, ''),
(3325, 1070, 78, 1, 1.50, 0, '', 1, ''),
(3326, 1070, 95, 1, 0.35, 0, '', 1, ''),
(3330, 1071, 95, 1, 0.35, 0, '', 1, ''),
(3331, 1072, 10, 1, 0.99, 0, '', 1, ''),
(3332, 1072, 98, 1, 0.99, 0, '', 1, ''),
(3333, 1072, 98, 1, 0.99, 0, '', 1, ''),
(3334, 1072, 9, 1, 0.99, 0, '', 1, ''),
(3335, 1072, 110, 1, 1.50, 0, '', 1, ''),
(3336, 1072, 67, 1, 0.99, 0, '', 1, ''),
(3338, 1073, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Frito Duro\",\"ob\":\"\"}'),
(3339, 1073, 4, 1, 4.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"ob\":\"\"}'),
(3340, 1073, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3341, 1073, 98, 1, 0.99, 0, '', 1, '{\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(3345, 1074, 4, 1, 4.99, 0, '', 1, ''),
(3346, 1075, 46, 1, 3.99, 0, '', 1, ''),
(3347, 1075, 26, 1, 5.99, 0, '', 1, ''),
(3348, 1075, 64, 1, 0.99, 0, '', 1, ''),
(3349, 1075, 64, 1, 0.99, 0, '', 1, ''),
(3353, 1076, 53, 1, 13.99, 0, '', 1, '{\"ob\":\"10 maracuya   5 mostaza\"}'),
(3354, 1076, 64, 1, 0.99, 0, '', 1, ''),
(3355, 1076, 64, 1, 0.99, 0, '', 1, ''),
(3356, 1077, 51, 1, 5.99, 0, '', 1, ''),
(3357, 1077, 101, 1, 0.25, 0, '', 1, ''),
(3359, 1078, 51, 1, 5.99, 0, '', 1, ''),
(3360, 1079, 25, 1, 4.99, 0, '', 1, ''),
(3361, 1079, 79, 1, 2.50, 0, '', 1, ''),
(3363, 1080, 79, 1, 2.50, 0, '', 1, ''),
(3364, 1081, 81, 1, 1.25, 0, '', 1, ''),
(3365, 1082, 3, 1, 5.50, 0, '', 1, ''),
(3366, 1083, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Bolon mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Frutilla\",\"Huevos\":\"Frito Duro\",\"ob\":\"\"}'),
(3367, 1084, 3, 1, 5.50, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Bebida_Fria\":\"Jugo Frutilla\",\"Huevos\":\"Frito Duro\",\"ob\":\"\"}'),
(3368, 1084, 3, 1, 5.50, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(3369, 1084, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3370, 1085, 26, 1, 5.99, 0, '', 1, '{\"ob\":\"SIN PIÑA\"}'),
(3371, 1085, 52, 1, 8.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"BBQ MARACUYA\"}'),
(3372, 1085, 113, 1, 0.99, 0, '', 1, ''),
(3373, 1085, 64, 1, 0.99, 0, '', 1, ''),
(3377, 1086, 112, 1, 3.00, 0, '', 1, ''),
(3378, 1087, 55, 1, 25.99, 0, '', 1, ''),
(3379, 1087, 65, 1, 1.75, 0, '', 1, ''),
(3380, 1087, 65, 1, 1.75, 0, '', 1, ''),
(3381, 1087, 96, 1, 2.00, 0, '', 1, ''),
(3382, 1087, 96, 1, 2.00, 0, '', 1, ''),
(3385, 1088, 40, 1, 2.99, 0, '', 1, ''),
(3386, 1089, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"\"}'),
(3387, 1089, 15, 1, 5.50, 0, '', 1, ''),
(3389, 1090, 24, 1, 4.99, 0, '', 1, ''),
(3390, 1090, 24, 1, 4.99, 0, '', 1, ''),
(3392, 1091, 64, 1, 0.99, 0, '', 1, ''),
(3393, 1092, 71, 1, 1.99, 0, '', 1, ''),
(3394, 1093, 100, 1, 4.99, 0, '', 1, ''),
(3395, 1094, 67, 1, 0.99, 0, '', 1, ''),
(3396, 1094, 67, 1, 0.99, 0, '', 1, ''),
(3398, 1095, 46, 1, 3.99, 0, '', 1, ''),
(3399, 1095, 36, 1, 0.99, 0, '', 1, ''),
(3401, 1096, 112, 1, 3.00, 0, '', 1, ''),
(3402, 1096, 112, 1, 3.00, 0, '', 1, ''),
(3403, 1096, 112, 1, 3.00, 0, '', 1, ''),
(3404, 1097, 40, 1, 2.99, 0, '', 1, ''),
(3405, 1098, 40, 1, 2.99, 0, '', 1, ''),
(3406, 1099, 46, 1, 3.99, 0, '', 1, ''),
(3407, 1099, 97, 1, 3.50, 0, '', 1, ''),
(3409, 1100, 42, 1, 3.99, 0, '', 1, ''),
(3410, 1101, 25, 1, 4.99, 0, '', 1, ''),
(3411, 1101, 28, 1, 3.50, 0, '', 1, ''),
(3412, 1102, 52, 1, 8.99, 0, '', 1, ''),
(3413, 1102, 101, 1, 0.25, 0, '', 1, ''),
(3415, 1103, 28, 1, 3.50, 0, '', 1, '{\"ob\":\"lechuga tomate y salsa de tomate  sin queso \"}'),
(3416, 1103, 58, 1, 4.99, 0, '', 1, ''),
(3417, 1103, 96, 1, 2.00, 0, '', 1, ''),
(3418, 1103, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(3422, 1104, 7, 1, 3.50, 0, '', 1, ''),
(3423, 1104, 67, 1, 0.99, 0, '', 1, ''),
(3425, 1105, 24, 1, 4.99, 0, '', 1, ''),
(3426, 1105, 67, 1, 0.99, 0, '', 1, ''),
(3428, 1106, 58, 1, 4.99, 0, '', 1, '{\"ob\":\"sin \"}'),
(3429, 1106, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(3431, 1107, 49, 1, 2.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"\"}'),
(3432, 1107, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(3434, 1108, 67, 1, 0.99, 0, '', 1, ''),
(3435, 1108, 58, 1, 4.99, 0, '', 1, ''),
(3437, 1109, 28, 1, 3.50, 0, '', 1, ''),
(3438, 1109, 96, 1, 2.00, 0, '', 1, ''),
(3439, 1109, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Sprite\",\"ob\":\"\"}'),
(3440, 1110, 71, 1, 1.99, 0, '', 1, ''),
(3441, 1110, 23, 1, 3.99, 0, '', 1, ''),
(3443, 1111, 26, 1, 5.99, 0, '', 1, ''),
(3444, 1111, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(3445, 1112, 112, 1, 3.00, 0, '', 1, ''),
(3446, 1112, 112, 1, 3.00, 0, '', 1, ''),
(3448, 1113, 112, 1, 3.00, 0, '', 1, ''),
(3449, 1114, 45, 1, 3.99, 0, '', 1, ''),
(3450, 1114, 26, 1, 5.99, 0, '', 1, ''),
(3451, 1114, 66, 1, 1.25, 0, '', 1, ''),
(3452, 1115, 26, 1, 5.99, 0, '', 1, ''),
(3453, 1115, 44, 1, 5.50, 0, '', 1, ''),
(3454, 1115, 68, 1, 0.99, 0, '', 1, ''),
(3455, 1116, 24, 1, 4.99, 0, '', 1, ''),
(3456, 1116, 24, 1, 4.99, 0, '', 1, ''),
(3457, 1116, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(3458, 1116, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Fanta\",\"ob\":\"\"}'),
(3462, 1117, 18, 1, 3.99, 0, '', 1, '{\"ob\":\"bbq\"}'),
(3463, 1117, 40, 1, 2.99, 0, '', 1, ''),
(3464, 1117, 51, 1, 5.99, 0, '', 1, ''),
(3465, 1117, 65, 1, 1.75, 0, '', 1, ''),
(3469, 1118, 4, 1, 4.99, 0, '', 1, ''),
(3470, 1118, 3, 1, 5.50, 0, '', 1, ''),
(3471, 1118, 95, 1, 0.35, 0, '', 1, ''),
(3472, 1119, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe en leche\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(3473, 1119, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo chicharron\",\"Bebida_Caliente\":\"Leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3474, 1119, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(3475, 1119, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(3476, 1119, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(3479, 1120, 79, 1, 2.50, 0, '', 1, ''),
(3480, 1120, 79, 1, 2.50, 0, '', 1, ''),
(3481, 1120, 79, 1, 2.50, 0, '', 1, ''),
(3482, 1120, 79, 1, 2.50, 0, '', 1, ''),
(3483, 1120, 3, 1, 5.50, 0, '', 1, ''),
(3484, 1120, 3, 1, 5.50, 0, '', 1, ''),
(3486, 1121, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"maracuya\"}'),
(3487, 1121, 71, 1, 1.99, 0, '', 1, ''),
(3489, 1122, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"bbq\"}'),
(3490, 1122, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"\"}'),
(3491, 1122, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(3492, 1122, 113, 1, 0.99, 0, '', 1, ''),
(3496, 1123, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"bbq\"}'),
(3497, 1123, 52, 1, 8.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"bbq 3 sin  nada\"}'),
(3498, 1123, 113, 1, 0.99, 0, '', 1, ''),
(3499, 1124, 27, 1, 5.99, 0, '', 1, ''),
(3500, 1124, 25, 1, 4.99, 0, '', 1, ''),
(3501, 1124, 64, 1, 0.99, 0, '', 1, ''),
(3502, 1124, 113, 1, 0.99, 0, '', 1, ''),
(3506, 1125, 79, 1, 2.50, 0, '', 1, ''),
(3507, 1125, 79, 1, 2.50, 0, '', 1, ''),
(3508, 1125, 50, 1, 3.50, 0, '', 1, ''),
(3509, 1126, 112, 1, 3.00, 0, '', 1, ''),
(3510, 1126, 67, 1, 0.99, 0, '', 1, ''),
(3511, 1126, 67, 1, 0.99, 0, '', 1, ''),
(3512, 1126, 67, 1, 0.99, 0, '', 1, ''),
(3516, 1127, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(3517, 1127, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(3518, 1127, 64, 1, 0.99, 0, '', 1, ''),
(3519, 1127, 64, 1, 0.99, 0, '', 1, ''),
(3520, 1127, 67, 1, 0.99, 0, '', 1, ''),
(3523, 1128, 112, 1, 3.00, 0, '', 1, ''),
(3524, 1128, 112, 1, 3.00, 0, '', 1, ''),
(3525, 1128, 112, 1, 3.00, 0, '', 1, ''),
(3526, 1128, 112, 1, 3.00, 0, '', 1, ''),
(3530, 1129, 3, 1, 5.50, 0, '', 1, ''),
(3531, 1129, 3, 1, 5.50, 0, '', 1, ''),
(3532, 1129, 67, 1, 0.99, 0, '', 1, ''),
(3533, 1130, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(3534, 1130, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(3535, 1130, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(3536, 1130, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo chicharron\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(3537, 1130, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3538, 1130, 14, 1, 5.50, 0, '', 1, ''),
(3539, 1130, 78, 1, 1.50, 0, '', 1, '{\"ob\":\"cafe en leche\"}'),
(3540, 1131, 27, 1, 5.99, 0, '', 1, ''),
(3541, 1131, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(3542, 1131, 96, 1, 2.00, 0, '', 1, ''),
(3543, 1132, 23, 1, 3.99, 0, '', 1, ''),
(3544, 1132, 33, 1, 0.99, 0, '', 1, ''),
(3545, 1132, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Fanta\",\"ob\":\"\"}'),
(3546, 1133, 45, 1, 3.99, 0, '', 1, ''),
(3547, 1133, 71, 1, 1.99, 0, '', 1, ''),
(3549, 1134, 42, 1, 3.99, 0, '', 1, '{\"ob\":\"solo queso\"}'),
(3550, 1134, 35, 1, 1.50, 0, '', 1, '{\"ob\":\"pollo\"}'),
(3551, 1134, 113, 1, 0.99, 0, '', 1, ''),
(3552, 1135, 42, 1, 3.99, 0, '', 1, '{\"ob\":\"solo bbq\"}'),
(3553, 1135, 27, 1, 5.99, 0, '', 1, ''),
(3554, 1135, 79, 1, 2.50, 0, '', 1, ''),
(3555, 1136, 42, 1, 3.99, 0, '', 1, ''),
(3556, 1136, 42, 1, 3.99, 0, '', 1, ''),
(3558, 1137, 46, 1, 3.99, 0, '', 1, '{\"ob\":\"picante\"}'),
(3559, 1137, 69, 1, 1.50, 0, '', 1, ''),
(3560, 1137, 69, 1, 1.50, 0, '', 1, ''),
(3561, 1138, 39, 1, 2.50, 0, '', 1, ''),
(3562, 1139, 27, 1, 5.99, 0, '', 1, ''),
(3563, 1139, 24, 1, 4.99, 0, '', 1, ''),
(3564, 1139, 40, 1, 2.99, 0, '', 1, ''),
(3565, 1139, 39, 1, 2.50, 0, '', 1, ''),
(3566, 1139, 100, 1, 4.99, 0, '', 1, ''),
(3569, 1140, 67, 1, 0.99, 0, '', 1, ''),
(3570, 1140, 68, 1, 0.99, 0, '', 1, ''),
(3571, 1141, 25, 1, 4.99, 0, '', 1, ''),
(3572, 1141, 49, 1, 2.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"\"}'),
(3573, 1141, 71, 1, 1.99, 0, '', 1, ''),
(3574, 1141, 71, 1, 1.99, 0, '', 1, ''),
(3578, 1142, 27, 1, 5.99, 0, '', 1, ''),
(3579, 1142, 42, 1, 3.99, 0, '', 1, ''),
(3580, 1143, 27, 1, 5.99, 0, '', 1, ''),
(3581, 1143, 42, 1, 3.99, 0, '', 1, ''),
(3583, 1144, 42, 1, 3.99, 0, '', 1, ''),
(3584, 1145, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"bbq\"}'),
(3585, 1145, 113, 1, 0.99, 0, '', 1, ''),
(3586, 1145, 42, 1, 3.99, 0, '', 1, ''),
(3587, 1146, 96, 1, 2.00, 0, '', 1, ''),
(3588, 1146, 35, 1, 1.50, 0, '', 1, ''),
(3590, 1147, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"\"}'),
(3591, 1147, 24, 1, 4.99, 0, '', 1, ''),
(3592, 1147, 113, 1, 0.99, 0, '', 1, ''),
(3593, 1147, 113, 1, 0.99, 0, '', 1, ''),
(3597, 1148, 42, 1, 3.99, 0, '', 1, ''),
(3598, 1148, 25, 1, 4.99, 0, '', 1, ''),
(3599, 1148, 25, 1, 4.99, 0, '', 1, ''),
(3600, 1148, 65, 1, 1.75, 0, '', 1, ''),
(3604, 1149, 4, 1, 4.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"con poca lecheel cafe\"}'),
(3605, 1149, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3607, 1150, 2, 1, 4.99, 0, '', 1, ''),
(3608, 1150, 5, 1, 5.99, 0, '', 1, ''),
(3609, 1150, 3, 1, 5.50, 0, '', 1, ''),
(3610, 1150, 98, 1, 0.99, 0, '', 1, ''),
(3611, 1150, 10, 1, 0.99, 0, '', 1, ''),
(3614, 1151, 65, 1, 1.75, 0, '', 1, ''),
(3615, 1151, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"\"}'),
(3616, 1151, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"\"}'),
(3617, 1151, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"\"}'),
(3618, 1151, 27, 1, 5.99, 0, '', 1, ''),
(3621, 1152, 40, 1, 2.99, 0, '', 1, ''),
(3622, 1153, 27, 1, 5.99, 0, '', 1, ''),
(3623, 1153, 27, 1, 5.99, 0, '', 1, ''),
(3625, 1154, 42, 1, 3.99, 0, '', 1, '{\"ob\":\"sin queso\"}'),
(3626, 1154, 42, 1, 3.99, 0, '', 1, ''),
(3627, 1154, 43, 1, 3.99, 0, '', 1, ''),
(3628, 1154, 23, 1, 3.99, 0, '', 1, ''),
(3632, 1155, 110, 1, 1.50, 0, '', 1, ''),
(3633, 1155, 113, 1, 0.99, 0, '', 1, ''),
(3634, 1155, 64, 1, 0.99, 0, '', 1, ''),
(3635, 1156, 44, 1, 5.50, 0, '', 1, ''),
(3636, 1156, 44, 1, 5.50, 0, '', 1, ''),
(3637, 1156, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(3638, 1156, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Sprite\",\"ob\":\"\"}'),
(3642, 1157, 27, 1, 5.99, 0, '', 1, ''),
(3643, 1157, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Fanta\",\"ob\":\"\"}'),
(3645, 1158, 44, 1, 5.50, 0, '', 1, ''),
(3646, 1158, 64, 1, 0.99, 0, '', 1, ''),
(3648, 1159, 25, 1, 4.99, 0, '', 1, ''),
(3649, 1159, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Sprite\",\"ob\":\"\"}'),
(3651, 1160, 43, 1, 3.99, 0, '', 1, ''),
(3652, 1160, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Sprite\",\"ob\":\"\"}'),
(3654, 1161, 46, 1, 3.99, 0, '', 1, ''),
(3655, 1161, 64, 1, 0.99, 0, '', 1, ''),
(3657, 1162, 32, 1, 0.99, 0, '', 1, ''),
(3658, 1162, 32, 1, 0.99, 0, '', 1, ''),
(3659, 1162, 32, 1, 0.99, 0, '', 1, ''),
(3660, 1163, 27, 1, 5.99, 0, '', 1, ''),
(3661, 1164, 27, 1, 5.99, 0, '', 1, ''),
(3662, 1165, 41, 1, 2.99, 0, '', 1, ''),
(3663, 1166, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"\"}'),
(3664, 1166, 65, 1, 1.75, 0, '', 1, '{\"Sabor_gaseosa\":\"Fanta\",\"ob\":\"\"}'),
(3665, 1166, 26, 1, 5.99, 0, '', 1, '{\"ob\":\"sin piña con tocino\"}'),
(3666, 1167, 26, 1, 5.99, 0, '', 1, ''),
(3667, 1168, 64, 1, 0.99, 0, '', 1, ''),
(3668, 1169, 41, 1, 2.99, 0, '', 1, ''),
(3669, 1170, 71, 1, 1.99, 0, '', 1, ''),
(3670, 1171, 51, 1, 5.99, 0, '', 1, ''),
(3671, 1171, 27, 1, 5.99, 0, '', 1, ''),
(3672, 1171, 64, 1, 0.99, 0, '', 1, ''),
(3673, 1172, 43, 1, 3.99, 0, '', 1, '{\"BBQ\":\"Sin BBQ\",\"ob\":\"\"}'),
(3674, 1172, 45, 1, 3.99, 0, '', 1, ''),
(3675, 1172, 58, 1, 4.99, 0, '', 1, ''),
(3676, 1172, 111, 1, 2.00, 0, '', 1, ''),
(3680, 1173, 104, 1, 8.99, 0, '', 1, ''),
(3681, 1173, 52, 1, 8.99, 0, '', 1, ''),
(3682, 1173, 100, 1, 4.99, 0, '', 1, ''),
(3683, 1174, 46, 1, 3.99, 0, '', 1, ''),
(3684, 1174, 69, 1, 1.50, 0, '', 1, ''),
(3686, 1175, 28, 1, 3.50, 0, '', 1, ''),
(3687, 1175, 64, 1, 0.99, 0, '', 1, ''),
(3688, 1175, 98, 1, 0.99, 0, '', 1, ''),
(3689, 1176, 28, 1, 3.50, 0, '', 1, ''),
(3690, 1176, 28, 1, 3.50, 0, '', 1, ''),
(3691, 1176, 28, 1, 3.50, 0, '', 1, ''),
(3692, 1177, 27, 1, 5.99, 0, '', 1, ''),
(3693, 1177, 64, 1, 0.99, 0, '', 1, ''),
(3695, 1178, 82, 1, 5.00, 0, '', 1, ''),
(3696, 1178, 20, 1, 5.50, 0, '', 1, ''),
(3697, 1178, 42, 1, 3.99, 0, '', 1, ''),
(3698, 1178, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Fanta\",\"ob\":\"\"}'),
(3702, 1179, 2, 1, 4.99, 0, '', 1, ''),
(3703, 1180, 2, 1, 4.99, 0, '', 1, ''),
(3704, 1181, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"bbq\"}'),
(3705, 1181, 101, 1, 0.25, 0, '', 1, ''),
(3707, 1182, 26, 1, 5.99, 0, '', 1, '{\"ob\":\"Sin piña\"}'),
(3708, 1182, 27, 1, 5.99, 0, '', 1, ''),
(3709, 1182, 40, 1, 2.99, 0, '', 1, '{\"BBQ\":\"Sin BBQ\",\"ob\":\"\"}'),
(3710, 1182, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"Y mostaza y miel\"}'),
(3711, 1182, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(3712, 1182, 82, 1, 5.00, 0, '', 1, ''),
(3713, 1182, 71, 1, 1.99, 0, '', 1, ''),
(3714, 1182, 71, 1, 1.99, 0, '', 1, ''),
(3722, 1183, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"\"}'),
(3723, 1183, 28, 1, 3.50, 0, '', 1, ''),
(3725, 1184, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"\"}'),
(3726, 1184, 113, 1, 0.99, 0, '', 1, ''),
(3728, 1185, 65, 1, 1.75, 0, '', 1, ''),
(3729, 1186, 44, 1, 5.50, 0, '', 1, ''),
(3730, 1186, 44, 1, 5.50, 0, '', 1, ''),
(3731, 1186, 51, 1, 5.99, 0, '', 1, ''),
(3732, 1186, 100, 1, 4.99, 0, '', 1, ''),
(3733, 1186, 28, 1, 3.50, 0, '', 1, ''),
(3736, 1187, 28, 1, 3.50, 0, '', 1, ''),
(3737, 1187, 28, 1, 3.50, 0, '', 1, ''),
(3738, 1187, 64, 1, 0.99, 0, '', 1, ''),
(3739, 1188, 24, 1, 4.99, 0, '', 1, ''),
(3740, 1188, 62, 1, 1.99, 0, '', 1, '{\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(3741, 1188, 62, 1, 1.99, 0, '', 1, '{\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(3742, 1188, 61, 1, 1.50, 0, '', 1, ''),
(3746, 1189, 28, 1, 3.50, 0, '', 1, ''),
(3747, 1189, 71, 1, 1.99, 0, '', 1, ''),
(3748, 1189, 96, 1, 2.00, 0, '', 1, ''),
(3749, 1190, 40, 1, 2.99, 0, '', 1, ''),
(3750, 1190, 43, 1, 3.99, 0, '', 1, ''),
(3751, 1190, 66, 1, 1.25, 0, '', 1, ''),
(3752, 1190, 66, 1, 1.25, 0, '', 1, ''),
(3756, 1191, 50, 1, 3.50, 0, '', 1, ''),
(3757, 1191, 50, 1, 3.50, 0, '', 1, ''),
(3758, 1191, 50, 1, 3.50, 0, '', 1, ''),
(3759, 1191, 65, 1, 1.75, 0, '', 1, ''),
(3760, 1191, 71, 1, 1.99, 0, '', 1, ''),
(3763, 1192, 26, 1, 5.99, 0, '', 1, ''),
(3764, 1193, 28, 1, 3.50, 0, '', 1, ''),
(3765, 1193, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Fanta\",\"ob\":\"\"}'),
(3766, 1193, 96, 1, 2.00, 0, '', 1, ''),
(3767, 1194, 25, 1, 4.99, 0, '', 1, ''),
(3768, 1194, 64, 1, 0.99, 0, '', 1, ''),
(3770, 1195, 39, 1, 2.50, 0, '', 1, ''),
(3771, 1195, 27, 1, 5.99, 0, '', 1, ''),
(3772, 1195, 78, 1, 1.50, 0, '', 1, ''),
(3773, 1196, 65, 1, 1.75, 0, '', 1, ''),
(3774, 1197, 111, 1, 2.00, 0, '', 1, ''),
(3775, 1198, 28, 1, 3.50, 0, '', 1, ''),
(3776, 1198, 28, 1, 3.50, 0, '', 1, '{\"ob\":\"cebolla acaramelisada y queso chedar \"}'),
(3777, 1198, 96, 1, 2.00, 0, '', 1, ''),
(3778, 1198, 96, 1, 2.00, 0, '', 1, ''),
(3779, 1198, 96, 1, 2.00, 0, '', 1, ''),
(3782, 1199, 28, 1, 3.50, 0, '', 1, ''),
(3783, 1200, 3, 1, 5.50, 0, '', 1, ''),
(3784, 1200, 3, 1, 5.50, 0, '', 1, ''),
(3786, 1201, 27, 1, 5.99, 0, '', 1, '{\"ob\":\"mas tres alitas  bbq\"}'),
(3787, 1202, 39, 1, 2.50, 0, '', 1, ''),
(3788, 1203, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"MOSTAZA Y MIEL MAS 3 ALITAS\"}'),
(3789, 1204, 24, 1, 4.99, 0, '', 1, ''),
(3790, 1204, 24, 1, 4.99, 0, '', 1, ''),
(3791, 1204, 62, 1, 1.99, 0, '', 1, '{\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(3792, 1204, 62, 1, 1.99, 0, '', 1, '{\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(3796, 1205, 27, 1, 5.99, 0, '', 1, '{\"ob\":\"no bbq\"}'),
(3797, 1205, 110, 1, 1.50, 0, '', 1, ''),
(3798, 1205, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(3799, 1206, 64, 1, 0.99, 0, '', 1, ''),
(3800, 1206, 41, 1, 2.99, 0, '', 1, ''),
(3802, 1207, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Frito Duro\",\"ob\":\"NO VISTE DE CARNE \"}'),
(3803, 1207, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3805, 1208, 27, 1, 5.99, 0, '', 1, ''),
(3806, 1209, 74, 1, 1.50, 0, '', 1, ''),
(3807, 1210, 51, 1, 5.99, 0, '', 1, ''),
(3808, 1210, 42, 1, 3.99, 0, '', 1, ''),
(3809, 1210, 101, 1, 0.25, 0, '', 1, ''),
(3810, 1210, 101, 1, 0.25, 0, '', 1, ''),
(3811, 1210, 96, 1, 2.00, 0, '', 1, ''),
(3814, 1211, 3, 1, 5.50, 0, '', 1, ''),
(3815, 1211, 3, 1, 5.50, 0, '', 1, ''),
(3816, 1211, 3, 1, 5.50, 0, '', 1, ''),
(3817, 1211, 3, 1, 5.50, 0, '', 1, ''),
(3818, 1211, 50, 1, 3.50, 0, '', 1, ''),
(3819, 1211, 78, 1, 1.50, 0, '', 1, ''),
(3820, 1211, 23, 1, 3.99, 0, '', 1, ''),
(3821, 1211, 96, 1, 2.00, 0, '', 1, ''),
(3829, 1212, 46, 1, 3.99, 0, '', 1, ''),
(3830, 1212, 66, 1, 1.25, 0, '', 1, ''),
(3831, 1212, 36, 1, 0.99, 0, '', 1, ''),
(3832, 1213, 46, 1, 3.99, 0, '', 1, ''),
(3833, 1214, 1, 1, 3.99, 0, '', 1, ''),
(3834, 1214, 1, 1, 3.99, 0, '', 1, ''),
(3835, 1214, 1, 1, 3.99, 0, '', 1, ''),
(3836, 1214, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Bolon chicharron\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(3840, 1215, 1, 1, 3.99, 0, '', 1, ''),
(3841, 1215, 1, 1, 3.99, 0, '', 1, ''),
(3842, 1215, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Bolon queso\",\"Bebida_Caliente\":\"Cafe negrisimo\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Cocinado Duro\",\"ob\":\"\"}'),
(3843, 1215, 8, 1, 2.99, 0, '', 1, ''),
(3844, 1215, 74, 1, 1.50, 0, '', 1, ''),
(3845, 1215, 9, 1, 0.99, 0, '', 1, ''),
(3847, 1216, 40, 1, 2.99, 0, '', 1, ''),
(3848, 1216, 40, 1, 2.99, 0, '', 1, ''),
(3849, 1216, 64, 1, 0.99, 0, '', 1, ''),
(3850, 1216, 64, 1, 0.99, 0, '', 1, ''),
(3854, 1217, 24, 1, 4.99, 0, '', 1, ''),
(3855, 1217, 66, 1, 1.25, 0, '', 1, ''),
(3856, 1217, 33, 1, 0.99, 0, '', 1, ''),
(3857, 1218, 41, 1, 2.99, 0, '', 1, '{\"ob\":\"mas hamburguesa \"}'),
(3858, 1219, 25, 1, 4.99, 0, '', 1, ''),
(3859, 1219, 46, 1, 3.99, 0, '', 1, ''),
(3860, 1219, 46, 1, 3.99, 0, '', 1, ''),
(3861, 1219, 65, 1, 1.75, 0, '', 1, ''),
(3865, 1220, 71, 1, 1.99, 0, '', 1, ''),
(3866, 1221, 28, 1, 3.50, 0, '', 1, ''),
(3867, 1221, 64, 1, 0.99, 0, '', 1, ''),
(3869, 1222, 64, 1, 0.99, 0, '', 1, ''),
(3870, 1223, 23, 1, 3.99, 0, '', 1, ''),
(3871, 1223, 23, 1, 3.99, 0, '', 1, ''),
(3872, 1223, 41, 1, 2.99, 0, '', 1, ''),
(3873, 1223, 51, 1, 5.99, 0, '', 1, ''),
(3874, 1223, 71, 1, 1.99, 0, '', 1, ''),
(3875, 1223, 71, 1, 1.99, 0, '', 1, ''),
(3876, 1223, 74, 1, 1.50, 0, '', 1, ''),
(3877, 1223, 74, 1, 1.50, 0, '', 1, ''),
(3885, 1224, 42, 1, 3.99, 0, '', 1, ''),
(3886, 1224, 27, 1, 5.99, 0, '', 1, ''),
(3887, 1224, 50, 1, 3.50, 0, '', 1, ''),
(3888, 1224, 39, 1, 2.50, 0, '', 1, ''),
(3889, 1224, 65, 1, 1.75, 0, '', 1, ''),
(3890, 1224, 67, 1, 0.99, 0, '', 1, ''),
(3892, 1225, 27, 1, 5.99, 0, '', 1, '{\"ob\":\"LLEVAR\"}'),
(3893, 1225, 101, 1, 0.25, 0, '', 1, ''),
(3895, 1226, 27, 1, 5.99, 0, '', 1, ''),
(3896, 1226, 24, 1, 4.99, 0, '', 1, ''),
(3897, 1226, 3, 1, 5.50, 0, '', 1, ''),
(3898, 1226, 2, 1, 4.99, 0, '', 1, ''),
(3899, 1226, 112, 1, 3.00, 0, '', 1, ''),
(3900, 1227, 119, 1, 5.99, 0, '', 1, ''),
(3901, 1227, 117, 1, 1.50, 0, '', 1, ''),
(3902, 1227, 44, 1, 5.50, 0, '', 1, ''),
(3903, 1227, 51, 1, 5.99, 0, '', 1, ''),
(3904, 1227, 118, 1, 1.50, 0, '', 1, ''),
(3905, 1227, 64, 1, 0.99, 0, '', 1, ''),
(3906, 1227, 64, 1, 0.99, 0, '', 1, ''),
(3907, 1227, 74, 1, 1.50, 0, '', 1, ''),
(3908, 1228, 23, 1, 3.99, 0, '', 1, ''),
(3909, 1228, 39, 1, 2.50, 0, '', 1, ''),
(3910, 1228, 61, 1, 1.50, 0, '', 1, ''),
(3911, 1229, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe Pasado\",\"Bebida_Fria\":\"Jugo Frutilla\",\"Huevos\":\"Revuelto normal\",\"ob\":\"No poner azucar\"}'),
(3912, 1230, 4, 1, 4.99, 0, '', 1, ''),
(3913, 1230, 46, 1, 3.99, 0, '', 1, '{\"Mayonesa\":\"Sin mayonesa\",\"Salsa_de_tomate\":\"Sin salsa de tomate\",\"ob\":\"sin bbq\"}'),
(3914, 1230, 68, 1, 0.99, 0, '', 1, ''),
(3915, 1231, 112, 1, 3.00, 0, '', 1, ''),
(3916, 1231, 112, 1, 3.00, 0, '', 1, ''),
(3917, 1231, 112, 1, 3.00, 0, '', 1, ''),
(3918, 1231, 74, 1, 1.50, 0, '', 1, ''),
(3919, 1231, 95, 1, 0.35, 0, '', 1, ''),
(3920, 1231, 95, 1, 0.35, 0, '', 1, ''),
(3921, 1231, 95, 1, 0.35, 0, '', 1, ''),
(3922, 1232, 4, 1, 4.99, 0, '', 1, ''),
(3923, 1232, 5, 1, 5.99, 0, '', 1, ''),
(3924, 1232, 148, 1, 5.50, 0, '', 1, ''),
(3925, 1232, 2, 1, 4.99, 0, '', 1, ''),
(3926, 1232, 2, 1, 4.99, 0, '', 1, ''),
(3929, 1233, 119, 1, 5.99, 0, '', 1, ''),
(3930, 1233, 119, 1, 5.99, 0, '', 1, ''),
(3932, 1234, 64, 1, 0.99, 0, '', 1, ''),
(3933, 1235, 64, 1, 0.99, 0, '', 1, ''),
(3934, 1236, 27, 1, 5.99, 0, '', 1, ''),
(3935, 1236, 64, 1, 0.99, 0, '', 1, ''),
(3936, 1236, 64, 1, 0.99, 0, '', 1, ''),
(3937, 1236, 28, 1, 3.50, 0, '', 1, ''),
(3941, 1237, 27, 1, 5.99, 0, '', 1, ''),
(3942, 1237, 27, 1, 5.99, 0, '', 1, ''),
(3943, 1237, 64, 1, 0.99, 0, '', 1, ''),
(3944, 1237, 64, 1, 0.99, 0, '', 1, ''),
(3945, 1238, 27, 1, 5.99, 0, '', 1, ''),
(3946, 1238, 133, 1, 13.99, 0, '', 1, ''),
(3947, 1238, 134, 1, 24.99, 0, '', 1, ''),
(3948, 1238, 39, 1, 2.50, 0, '', 1, ''),
(3949, 1238, 39, 1, 2.50, 0, '', 1, ''),
(3950, 1238, 40, 1, 2.99, 0, '', 1, ''),
(3951, 1238, 40, 1, 2.99, 0, '', 1, ''),
(3952, 1238, 67, 1, 0.99, 0, '', 1, ''),
(3953, 1238, 101, 1, 0.25, 0, '', 1, ''),
(3954, 1238, 101, 1, 0.25, 0, '', 1, ''),
(3955, 1238, 101, 1, 0.25, 0, '', 1, ''),
(3956, 1238, 101, 1, 0.25, 0, '', 1, ''),
(3957, 1238, 101, 1, 0.25, 0, '', 1, ''),
(3960, 1239, 54, 1, 17.99, 0, '', 1, '{\"Sabor_alitas\":\"Maracuya\",\"ob\":\"BBQ PICANTE\r\n MOSTAZA Y MIEL\"}'),
(3961, 1239, 119, 1, 5.99, 0, '', 1, '{\"Arroz\":\"Arroz y menestra\",\"Maduritos\":\"Maduritos\",\"Ensalada\":\"Fresca\",\"ob\":\"\"}'),
(3962, 1239, 136, 1, 5.99, 0, '', 1, ''),
(3963, 1239, 97, 1, 3.99, 0, '', 1, ''),
(3964, 1239, 97, 1, 3.99, 0, '', 1, ''),
(3965, 1239, 96, 1, 2.00, 0, '', 1, ''),
(3967, 1240, 78, 1, 1.50, 0, '', 1, ''),
(3968, 1241, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"Maracuya\",\"ob\":\"\"}'),
(3969, 1241, 24, 1, 4.99, 0, '', 1, ''),
(3970, 1241, 65, 1, 1.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(3971, 1241, 67, 1, 0.99, 0, '', 1, '{\"ob\":\"Al clima\"}'),
(3972, 1241, 19, 1, 4.50, 0, '', 1, ''),
(3973, 1241, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"\"}'),
(3974, 1241, 78, 1, 1.50, 0, '', 1, '{\"ob\":\"Manzanilla\"}'),
(3975, 1241, 51, 1, 5.99, 0, '', 1, '{\"ob\":\"Miel y mostaza y BBQ \"}'),
(3976, 1241, 80, 1, 3.50, 0, '', 1, ''),
(3977, 1241, 80, 1, 3.50, 0, '', 1, ''),
(3978, 1241, 58, 1, 4.99, 0, '', 1, ''),
(3983, 1242, 80, 1, 3.50, 0, '', 1, ''),
(3984, 1242, 80, 1, 3.50, 0, '', 1, ''),
(3985, 1242, 147, 1, 1.99, 0, '', 1, ''),
(3986, 1242, 67, 1, 0.99, 0, '', 1, ''),
(3987, 1242, 67, 1, 0.99, 0, '', 1, ''),
(3988, 1242, 78, 1, 1.50, 0, '', 1, ''),
(3989, 1242, 80, 1, 3.50, 0, '', 1, ''),
(3990, 1242, 80, 1, 3.50, 0, '', 1, ''),
(3991, 1243, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"ob\":\"\"}'),
(3992, 1243, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"ob\":\"\"}'),
(3993, 1243, 142, 1, 4.99, 0, '', 1, ''),
(3994, 1243, 142, 1, 4.99, 0, '', 1, ''),
(3995, 1243, 143, 1, 2.99, 0, '', 1, '{\"ob\":\"MIXTO\"}'),
(3996, 1243, 143, 1, 2.99, 0, '', 1, '{\"ob\":\"MIXTO\"}'),
(3997, 1243, 143, 1, 2.99, 0, '', 1, '{\"ob\":\"MIXTO\"}'),
(3998, 1243, 4, 1, 4.99, 0, '', 1, '{\"ob\":\"\"}'),
(3999, 1243, 4, 1, 4.99, 0, '', 1, '{\"ob\":\"NO POLLO ,CON HUEVO REVUELTOS\"}'),
(4000, 1243, 4, 1, 4.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe Pasado\",\"ob\":\"NO POLLO , HUEVO REVUELTO\"}'),
(4001, 1243, 149, 1, 5.50, 0, '', 1, '{\"ob\":\"CAFE EN AGUA Y POLLO\"}'),
(4002, 1243, 148, 1, 5.50, 0, '', 1, '{\"ob\":\"CAFE EN AGUA \r\n MIXTO \"}'),
(4003, 1243, 2, 1, 4.99, 0, '', 1, ''),
(4004, 1243, 2, 1, 4.99, 0, '', 1, ''),
(4005, 1243, 2, 1, 4.99, 0, '', 1, ''),
(4006, 1243, 2, 1, 4.99, 0, '', 1, ''),
(4007, 1243, 2, 1, 4.99, 0, '', 1, ''),
(4008, 1243, 2, 1, 4.99, 0, '', 1, ''),
(4009, 1243, 2, 1, 4.99, 0, '', 1, ''),
(4010, 1243, 3, 1, 5.50, 0, '', 1, ''),
(4011, 1243, 5, 1, 5.99, 0, '', 1, ''),
(4012, 1243, 112, 1, 3.00, 0, '', 1, ''),
(4013, 1243, 1, 1, 3.99, 0, '', 1, ''),
(4014, 1243, 1, 1, 3.99, 0, '', 1, ''),
(4015, 1243, 1, 1, 3.99, 0, '', 1, ''),
(4016, 1243, 1, 1, 3.99, 0, '', 1, ''),
(4017, 1243, 1, 1, 3.99, 0, '', 1, ''),
(4018, 1243, 1, 1, 3.99, 0, '', 1, ''),
(4019, 1243, 1, 1, 3.99, 0, '', 1, ''),
(4020, 1243, 1, 1, 3.99, 0, '', 1, ''),
(4021, 1243, 1, 1, 3.99, 0, '', 1, ''),
(4022, 1243, 1, 1, 3.99, 0, '', 1, ''),
(4023, 1243, 112, 1, 3.00, 0, '', 1, ''),
(4024, 1243, 112, 1, 3.00, 0, '', 1, ''),
(4025, 1243, 112, 1, 3.00, 0, '', 1, ''),
(4026, 1243, 112, 1, 3.00, 0, '', 1, ''),
(4027, 1243, 112, 1, 3.00, 0, '', 1, ''),
(4028, 1243, 112, 1, 3.00, 0, '', 1, ''),
(4029, 1243, 112, 1, 3.00, 0, '', 1, ''),
(4030, 1243, 5, 1, 5.99, 0, '', 1, ''),
(4054, 1244, 5, 1, 5.99, 0, '', 1, ''),
(4055, 1244, 5, 1, 5.99, 0, '', 1, ''),
(4057, 1245, 141, 1, 5.99, 0, '', 1, ''),
(4058, 1245, 61, 1, 1.50, 0, '', 1, ''),
(4060, 1246, 51, 1, 5.99, 0, '', 1, '{\"ob\":\"Y MOSTAZA Y MIEL\"}'),
(4061, 1246, 25, 1, 4.99, 0, '', 1, ''),
(4062, 1246, 108, 1, 8.99, 0, '', 1, '{\"Arroz\":\"Arroz Moro\",\"Papas_fritas\":\"Papas Fritas\",\"Ensalada\":\"Cesar\",\"ob\":\"\"}'),
(4063, 1246, 111, 1, 1.99, 0, '', 1, ''),
(4067, 1247, 28, 1, 3.50, 0, '', 1, ''),
(4068, 1247, 28, 1, 3.50, 0, '', 1, '{\"ob\":\"SIN VEGETALES\"}'),
(4070, 1248, 49, 1, 2.99, 0, '', 1, '{\"Sabor_alitas\":\"Maracuya\",\"ob\":\"NO TANTAS PAPAS\"}'),
(4071, 1248, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Fanta\",\"ob\":\"\"}'),
(4073, 1249, 27, 1, 5.99, 0, '', 1, ''),
(4074, 1249, 64, 1, 0.99, 0, '', 1, ''),
(4075, 1249, 64, 1, 0.99, 0, '', 1, ''),
(4076, 1250, 27, 1, 5.99, 0, '', 1, ''),
(4077, 1250, 27, 1, 5.99, 0, '', 1, ''),
(4078, 1250, 26, 1, 5.99, 0, '', 1, ''),
(4079, 1251, 144, 1, 2.50, 0, '', 1, ''),
(4080, 1251, 144, 1, 2.50, 0, '', 1, ''),
(4081, 1251, 144, 1, 2.50, 0, '', 1, ''),
(4082, 1251, 144, 1, 2.50, 0, '', 1, ''),
(4083, 1251, 74, 1, 1.50, 0, '', 1, ''),
(4084, 1251, 74, 1, 1.50, 0, '', 1, ''),
(4085, 1251, 74, 1, 1.50, 0, '', 1, ''),
(4086, 1251, 74, 1, 1.50, 0, '', 1, ''),
(4094, 1252, 46, 1, 3.99, 0, '', 1, '{\"ob\":\"\"}'),
(4095, 1252, 46, 1, 3.99, 0, '', 1, ''),
(4097, 1253, 78, 1, 1.50, 0, '', 1, ''),
(4098, 1254, 119, 1, 5.99, 0, '', 1, '{\"Arroz\":\"Arroz Moro\",\"Papas_fritas\":\"Papas Fritas\",\"ob\":\"\"}'),
(4099, 1254, 119, 1, 5.99, 0, '', 1, '{\"Maduritos\":\"Maduritos\",\"ob\":\"\"}'),
(4100, 1254, 69, 1, 1.50, 0, '', 1, ''),
(4101, 1254, 64, 1, 0.99, 0, '', 1, ''),
(4102, 1254, 151, 1, 1.50, 0, '', 1, '{\"ob\":\"ARROZ MENESTRA\"}'),
(4105, 1255, 125, 1, 4.99, 0, '', 1, ''),
(4106, 1255, 33, 1, 0.99, 0, '', 1, ''),
(4107, 1255, 64, 1, 0.99, 0, '', 1, ''),
(4108, 1256, 42, 1, 3.99, 0, '', 1, ''),
(4109, 1256, 42, 1, 3.99, 0, '', 1, ''),
(4110, 1256, 113, 1, 0.99, 0, '', 1, ''),
(4111, 1256, 71, 1, 1.99, 0, '', 1, ''),
(4115, 1257, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"3 MOSTAZA Y MIEL\"}'),
(4116, 1258, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"\"}'),
(4117, 1258, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"\"}'),
(4118, 1258, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"\"}'),
(4119, 1258, 51, 1, 5.99, 0, '', 1, '{\"Sabor_alitas\":\"Parmesano\",\"ob\":\"BBQ\"}'),
(4123, 1259, 25, 1, 4.99, 0, '', 1, ''),
(4124, 1260, 45, 1, 3.99, 0, '', 1, ''),
(4125, 1260, 27, 1, 5.99, 0, '', 1, ''),
(4126, 1260, 67, 1, 0.99, 0, '', 1, ''),
(4127, 1260, 67, 1, 0.99, 0, '', 1, ''),
(4128, 1260, 66, 1, 0.99, 0, '', 1, ''),
(4131, 1261, 40, 1, 2.99, 0, '', 1, ''),
(4132, 1262, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"3MOSTAZA Y MIEL\"}'),
(4133, 1262, 66, 1, 0.99, 0, '', 1, ''),
(4134, 1262, 64, 1, 0.99, 0, '', 1, ''),
(4135, 1262, 126, 1, 5.99, 0, '', 1, ''),
(4139, 1263, 46, 1, 3.99, 0, '', 1, ''),
(4140, 1263, 64, 1, 0.99, 0, '', 1, ''),
(4142, 1264, 74, 1, 1.50, 0, '', 1, ''),
(4143, 1264, 101, 1, 0.25, 0, '', 1, ''),
(4144, 1264, 62, 1, 1.99, 0, '', 1, ''),
(4145, 1265, 141, 1, 5.99, 0, '', 1, ''),
(4146, 1266, 112, 1, 3.00, 0, '', 1, ''),
(4147, 1267, 67, 1, 0.99, 0, '', 1, ''),
(4148, 1268, 74, 1, 1.50, 0, '', 1, ''),
(4149, 1268, 144, 1, 2.50, 0, '', 1, ''),
(4150, 1268, 144, 1, 2.50, 0, '', 1, ''),
(4151, 1268, 144, 1, 2.50, 0, '', 1, ''),
(4152, 1268, 144, 1, 2.50, 0, '', 1, ''),
(4155, 1269, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Bolon mixto\",\"Bebida_Caliente\":\"Cafe Pasado\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(4156, 1269, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Agua\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Frito Suave\",\"ob\":\"\"}'),
(4158, 1270, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Leche Pura\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(4159, 1270, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Leche Pura\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(4160, 1270, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Leche Pura\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Cocinado Tibio\",\"ob\":\"\"}'),
(4161, 1270, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Agua\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Cocinado Tibio\",\"ob\":\"\"}'),
(4165, 1271, 1, 1, 3.99, 0, '', 1, ''),
(4166, 1271, 1, 1, 3.99, 0, '', 1, ''),
(4167, 1271, 63, 1, 1.99, 0, '', 1, ''),
(4168, 1271, 147, 1, 1.99, 0, '', 1, ''),
(4172, 1272, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Leche Pura\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(4173, 1273, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe Pasado\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(4174, 1273, 3, 1, 5.50, 0, '', 1, '{\"Estado_verde\":\"Tigrillo mixto\",\"Bebida_Caliente\":\"Cafe Pasado\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(4176, 1274, 15, 1, 5.50, 0, '', 1, ''),
(4177, 1274, 74, 1, 1.50, 0, '', 1, ''),
(4178, 1274, 62, 1, 1.99, 0, '', 1, ''),
(4179, 1275, 143, 1, 2.99, 0, '', 1, ''),
(4180, 1275, 74, 1, 1.50, 0, '', 1, ''),
(4181, 1275, 12, 1, 1.99, 0, '', 1, ''),
(4182, 1276, 141, 1, 5.99, 0, '', 1, ''),
(4183, 1276, 101, 1, 0.25, 0, '', 1, ''),
(4184, 1276, 74, 1, 1.50, 0, '', 1, ''),
(4185, 1277, 144, 1, 2.50, 0, '', 1, ''),
(4186, 1277, 144, 1, 2.50, 0, '', 1, ''),
(4187, 1277, 74, 1, 1.50, 0, '', 1, ''),
(4188, 1277, 74, 1, 1.50, 0, '', 1, ''),
(4189, 1277, 12, 1, 1.99, 0, '', 1, ''),
(4192, 1278, 1, 1, 3.99, 0, '', 1, ''),
(4193, 1278, 143, 1, 2.99, 0, '', 1, ''),
(4194, 1278, 141, 1, 5.99, 0, '', 1, ''),
(4195, 1278, 2, 1, 4.99, 0, '', 1, ''),
(4196, 1278, 74, 1, 1.50, 0, '', 1, ''),
(4197, 1278, 74, 1, 1.50, 0, '', 1, ''),
(4198, 1278, 62, 1, 1.99, 0, '', 1, ''),
(4199, 1279, 67, 1, 0.99, 0, '', 1, ''),
(4200, 1280, 62, 1, 1.99, 0, '', 1, ''),
(4201, 1281, 141, 1, 5.99, 0, '', 1, ''),
(4202, 1281, 62, 1, 1.99, 0, '', 1, ''),
(4203, 1281, 74, 1, 1.50, 0, '', 1, ''),
(4204, 1281, 148, 1, 5.50, 0, '', 1, ''),
(4208, 1282, 4, 1, 4.99, 0, '', 1, '{\"Bebida_Caliente\":\"Leche Pura\",\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"CERNIR LA NATA DE LALECHE\"}'),
(4209, 1282, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe Pasado\",\"Bebida_Fria\":\"Jugo Papaya\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(4210, 1282, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe Pasado\",\"Bebida_Fria\":\"Jugo Papaya\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(4211, 1283, 60, 1, 2.50, 0, '', 1, ''),
(4212, 1283, 79, 1, 2.50, 0, '', 1, ''),
(4214, 1284, 60, 1, 2.50, 0, '', 1, ''),
(4215, 1285, 79, 1, 2.50, 0, '', 1, ''),
(4216, 1285, 79, 1, 2.50, 0, '', 1, ''),
(4217, 1285, 79, 1, 2.50, 0, '', 1, ''),
(4218, 1285, 79, 1, 2.50, 0, '', 1, ''),
(4219, 1285, 25, 1, 4.99, 0, '', 1, ''),
(4220, 1285, 60, 1, 2.50, 0, '', 1, ''),
(4221, 1285, 79, 1, 2.50, 0, '', 1, ''),
(4222, 1286, 25, 1, 4.99, 0, '', 1, ''),
(4223, 1286, 64, 1, 0.99, 0, '', 1, ''),
(4225, 1287, 64, 1, 0.99, 0, '', 1, ''),
(4226, 1287, 64, 1, 0.99, 0, '', 1, ''),
(4227, 1287, 64, 1, 0.99, 0, '', 1, ''),
(4228, 1287, 64, 1, 0.99, 0, '', 1, ''),
(4229, 1287, 64, 1, 0.99, 0, '', 1, ''),
(4230, 1287, 39, 1, 2.50, 0, '', 1, ''),
(4231, 1287, 39, 1, 2.50, 0, '', 1, ''),
(4232, 1287, 39, 1, 2.50, 0, '', 1, ''),
(4233, 1287, 79, 1, 2.50, 0, '', 1, ''),
(4234, 1287, 79, 1, 2.50, 0, '', 1, ''),
(4235, 1287, 79, 1, 2.50, 0, '', 1, ''),
(4236, 1287, 68, 1, 0.99, 0, '', 1, ''),
(4237, 1287, 127, 1, 1.99, 0, '', 1, ''),
(4238, 1287, 79, 1, 2.50, 0, '', 1, ''),
(4240, 1288, 27, 1, 5.99, 0, '', 1, ''),
(4241, 1288, 64, 1, 0.99, 0, '', 1, ''),
(4243, 1289, 42, 1, 3.99, 0, '', 1, ''),
(4244, 1290, 27, 1, 5.99, 0, '', 1, ''),
(4245, 1290, 46, 1, 3.99, 0, '', 1, ''),
(4247, 1291, 50, 1, 3.50, 0, '', 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"\"}'),
(4248, 1291, 66, 1, 0.99, 0, '', 1, ''),
(4249, 1291, 15, 1, 5.50, 0, '', 1, ''),
(4250, 1291, 68, 1, 0.99, 0, '', 1, ''),
(4251, 1291, 109, 1, 2.25, 0, '', 1, ''),
(4252, 1291, 40, 1, 2.99, 0, '', 1, ''),
(4253, 1291, 40, 1, 2.99, 0, '', 1, ''),
(4254, 1292, 26, 1, 5.99, 0, '', 1, ''),
(4255, 1292, 118, 1, 1.50, 0, '', 1, ''),
(4257, 1293, 74, 1, 1.50, 0, '', 1, ''),
(4258, 1294, 58, 1, 4.99, 0, '', 1, ''),
(4259, 1294, 24, 1, 4.99, 0, '', 1, ''),
(4260, 1294, 125, 1, 4.99, 0, '', 1, ''),
(4261, 1294, 71, 1, 1.99, 0, '', 1, ''),
(4262, 1294, 71, 1, 1.99, 0, '', 1, ''),
(4263, 1294, 81, 1, 3.50, 0, '', 1, ''),
(4264, 1294, 81, 1, 3.50, 0, '', 1, ''),
(4265, 1295, 39, 1, 2.50, 0, '', 1, ''),
(4266, 1295, 39, 1, 2.50, 0, '', 1, ''),
(4267, 1295, 74, 1, 1.50, 0, '', 1, ''),
(4268, 1295, 64, 1, 0.99, 0, '', 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(4269, 1295, 67, 1, 0.99, 0, '', 1, ''),
(4270, 1295, 39, 1, 2.50, 0, '', 1, ''),
(4271, 1295, 39, 1, 2.50, 0, '', 1, ''),
(4272, 1295, 74, 1, 1.50, 0, '', 1, ''),
(4280, 1296, 5, 1, 5.99, 0, '', 1, ''),
(4281, 1296, 5, 1, 5.99, 0, '', 1, ''),
(4283, 1297, 5, 1, 5.99, 0, '', 1, ''),
(4284, 1297, 5, 1, 5.99, 0, '', 1, ''),
(4286, 1298, 5, 1, 5.99, 0, '', 1, ''),
(4287, 1298, 5, 1, 5.99, 0, '', 1, ''),
(4289, 1299, 5, 1, 5.99, 0, '', 1, ''),
(4290, 1299, 5, 1, 5.99, 0, '', 1, ''),
(4292, 1300, 5, 1, 5.99, 0, '', 1, ''),
(4293, 1301, 5, 1, 5.99, 0, '', 1, ''),
(4294, 1301, 5, 1, 5.99, 0, '', 1, ''),
(4296, 1302, 5, 1, 5.99, 0, '', 1, ''),
(4297, 1302, 5, 1, 5.99, 0, '', 1, ''),
(4299, 1303, 5, 1, 5.99, 0, '', 1, ''),
(4300, 1303, 5, 1, 5.99, 0, '', 1, ''),
(4302, 1304, 5, 1, 5.99, 0, '', 1, '{\"Bebida_Caliente\":\"Cafe Pasado\",\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(4303, 1305, 112, 1, 3.00, 0, '', 1, ''),
(4304, 1305, 112, 1, 3.00, 0, '', 1, ''),
(4305, 1305, 112, 1, 3.00, 0, '', 1, ''),
(4306, 1305, 112, 1, 3.00, 0, '', 1, ''),
(4307, 1305, 112, 1, 3.00, 0, '', 1, ''),
(4308, 1305, 148, 1, 5.50, 0, '', 1, ''),
(4309, 1305, 2, 1, 4.99, 0, '', 1, ''),
(4310, 1306, 141, 1, 5.99, 0, '', 1, '{\"ob\":\"Tigrillo mixto \"}'),
(4311, 1306, 141, 1, 5.99, 0, '', 1, '{\"ob\":\"Tigrillo mixto \"}'),
(4312, 1306, 141, 1, 5.99, 0, '', 1, '{\"ob\":\"Tigrillo mixto \"}'),
(4313, 1306, 141, 1, 5.99, 0, '', 1, '{\"ob\":\"Tigrillo mixto\"}'),
(4314, 1306, 1, 1, 3.99, 0, '', 1, '{\"Bebida_Caliente\":\"Leche Pura\",\"Bebida_Fria\":\"Jugo Mora\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(4315, 1306, 141, 1, 5.99, 0, '', 1, '{\"ob\":\"Tigrillo solo queso \"}'),
(4316, 1306, 141, 1, 5.99, 0, '', 1, '{\"ob\":\"Tigrillo mixto \"}'),
(4317, 1306, 141, 1, 5.99, 0, '', 1, '{\"ob\":\"Tigrillo mixto\"}'),
(4318, 1306, 78, 1, 1.50, 0, '', 1, ''),
(4319, 1306, 78, 1, 1.50, 0, '', 1, ''),
(4320, 1306, 78, 1, 1.50, 0, '', 1, ''),
(4321, 1306, 78, 1, 1.50, 0, '', 1, ''),
(4322, 1306, 78, 1, 1.50, 0, '', 1, ''),
(4323, 1306, 74, 1, 1.50, 0, '', 1, '{\"ob\":\"Taza pequeña\"}'),
(4324, 1306, 77, 1, 2.50, 0, '', 1, ''),
(4325, 1306, 143, 1, 2.99, 0, '', 1, '{\"ob\":\"Huevo yema suave\"}'),
(4326, 1306, 62, 1, 1.99, 0, '', 1, '{\"Bebida_Fria\":\"Jugo Mora\",\"Estado_Bebida\":\"Con Hielo\",\"ob\":\"\"}'),
(4327, 1306, 147, 1, 1.99, 0, '', 1, ''),
(4328, 1306, 2, 1, 4.99, 0, '', 1, ''),
(4329, 1306, 98, 1, 0.99, 0, '', 1, ''),
(4330, 1306, 98, 1, 0.99, 0, '', 1, ''),
(4331, 1306, 64, 1, 0.99, 0, '', 1, ''),
(4332, 1306, 64, 1, 0.99, 0, '', 1, ''),
(4333, 1306, 98, 1, 0.99, 0, '', 1, ''),
(4334, 1306, 95, 1, 0.35, 0, '', 1, ''),
(4335, 1306, 95, 1, 0.35, 0, '', 1, '');
INSERT INTO `detalle_factura` (`correlativo`, `nofactura`, `codproducto`, `cantidad`, `precio_venta`, `mesa`, `atributos`, `estatus_dt`, `observaciones`) VALUES
(4336, 1306, 12, 1, 1.99, 0, '', 1, ''),
(4337, 1306, 67, 1, 0.99, 0, '', 1, ''),
(4341, 1307, 78, 1, 1.50, 0, '', 1, ''),
(4342, 1307, 101, 1, 0.25, 0, '', 1, ''),
(4344, 1308, 148, 1, 5.50, 0, '', 1, ''),
(4345, 1308, 148, 1, 5.50, 0, '', 1, ''),
(4346, 1308, 148, 1, 5.50, 0, '', 1, ''),
(4347, 1308, 149, 1, 5.50, 0, '', 1, ''),
(4348, 1308, 4, 1, 4.99, 0, '', 1, ''),
(4349, 1308, 95, 1, 0.35, 0, '', 1, ''),
(4350, 1308, 95, 1, 0.35, 0, '', 1, ''),
(4351, 1309, 1, 1, 3.99, 0, '', 1, ''),
(4352, 1309, 1, 1, 3.99, 0, '', 1, ''),
(4354, 1310, 44, 1, 5.50, 0, '', 1, ''),
(4355, 1310, 101, 1, 0.25, 0, '', 1, ''),
(4357, 1311, 127, 1, 1.99, 0, '', 1, ''),
(4358, 1311, 101, 1, 0.25, 0, '', 1, ''),
(4360, 1312, 74, 1, 1.50, 0, '', 1, ''),
(4361, 1312, 74, 1, 1.50, 0, '', 1, ''),
(4363, 1313, 50, 1, 3.50, 0, '', 1, '{\"ob\":\"MOSTAZA Y MIEL\"}'),
(4364, 1313, 50, 1, 3.50, 0, '', 1, '{\"ob\":\"BBQ\"}'),
(4365, 1313, 64, 1, 0.99, 0, '', 1, ''),
(4366, 1314, 120, 1, 4.50, 0, '', 1, ''),
(4367, 1315, 127, 1, 1.99, 0, '', 1, ''),
(4368, 1316, 127, 1, 1.99, 0, '', 1, ''),
(4369, 1316, 96, 1, 2.00, 0, '', 1, ''),
(4371, 1317, 119, 1, 5.99, 0, '', 1, ''),
(4372, 1317, 138, 1, 1.50, 0, '', 1, ''),
(4374, 1318, 65, 1, 1.99, 0, '', 1, ''),
(4375, 1319, 42, 1, 3.99, 0, '', 1, ''),
(4376, 1319, 42, 1, 3.99, 0, '', 1, ''),
(4377, 1319, 64, 1, 0.99, 0, '', 1, ''),
(4378, 1319, 64, 1, 0.99, 0, '', 1, ''),
(4382, 1320, 44, 1, 5.50, 0, '', 1, ''),
(4383, 1320, 101, 1, 0.25, 0, '', 1, ''),
(4385, 1321, 19, 1, 4.50, 0, '', 1, ''),
(4386, 1321, 28, 1, 3.50, 0, '', 1, ''),
(4388, 1322, 126, 1, 5.99, 0, '', 1, ''),
(4389, 1322, 96, 1, 2.00, 0, '', 1, ''),
(4390, 1322, 64, 1, 0.99, 0, '', 1, ''),
(4391, 1322, 64, 1, 0.99, 0, '', 1, ''),
(4392, 1322, 24, 1, 4.99, 0, '', 1, ''),
(4393, 1322, 64, 1, 0.99, 0, '', 1, ''),
(4394, 1322, 64, 1, 0.99, 0, '', 1, ''),
(4395, 1322, 32, 1, 0.99, 0, '', 1, ''),
(4403, 1323, 64, 1, 0.99, 0, '', 1, '{\"ob\":\"SPRITE\"}'),
(4404, 1323, 126, 1, 5.99, 0, '', 1, ''),
(4406, 1324, 71, 1, 1.99, 0, '', 1, ''),
(4407, 1324, 42, 1, 3.99, 0, '', 1, ''),
(4409, 1325, 42, 1, 3.99, 0, '', 1, ''),
(4410, 1326, 24, 1, 4.99, 0, '', 1, ''),
(4411, 1326, 113, 1, 0.99, 0, '', 1, ''),
(4412, 1326, 42, 1, 3.99, 0, '', 1, ''),
(4413, 1326, 78, 1, 1.50, 0, '', 1, ''),
(4417, 1327, 121, 1, 3.50, 0, '', 1, ''),
(4418, 1327, 66, 1, 0.99, 0, '', 1, ''),
(4420, 1328, 119, 1, 5.99, 0, '', 1, ''),
(4421, 1328, 121, 1, 3.50, 0, '', 1, ''),
(4422, 1328, 65, 1, 1.99, 0, '', 1, ''),
(4423, 1329, 4, 1, 4.99, 0, '', 1, ''),
(4424, 1329, 4, 1, 4.99, 0, '', 1, ''),
(4425, 1329, 101, 1, 0.25, 0, '', 1, ''),
(4426, 1330, 35, 1, 1.50, 0, '', 1, ''),
(4427, 1331, 64, 1, 0.99, 0, '', 1, ''),
(4428, 1332, 1, 1, 3.99, 0, '', 1, ''),
(4429, 1332, 112, 1, 3.00, 0, '', 1, ''),
(4431, 1333, 1, 1, 3.99, 0, '', 1, ''),
(4432, 1333, 101, 1, 0.25, 0, '', 1, ''),
(4433, 1333, 101, 1, 0.25, 0, '', 1, ''),
(4434, 1333, 101, 1, 0.25, 0, '', 1, ''),
(4438, 1334, 74, 1, 1.50, 0, '', 1, ''),
(4439, 1334, 101, 1, 0.25, 0, '', 1, ''),
(4440, 1335, 148, 1, 5.50, 0, '', 1, ''),
(4441, 1335, 144, 1, 2.50, 0, '', 1, ''),
(4442, 1335, 144, 1, 2.50, 0, '', 1, ''),
(4443, 1335, 62, 1, 1.99, 0, '', 1, ''),
(4447, 1336, 1, 1, 3.99, 0, '', 1, ''),
(4448, 1336, 148, 1, 5.50, 0, '', 1, ''),
(4449, 1336, 149, 1, 5.50, 0, '', 1, ''),
(4450, 1336, 68, 1, 0.99, 0, '', 1, ''),
(4454, 1337, 4, 1, 4.99, 0, '', 1, ''),
(4455, 1337, 4, 1, 4.99, 0, '', 1, ''),
(4456, 1337, 141, 1, 5.99, 0, '', 1, ''),
(4457, 1337, 1, 1, 3.99, 0, '', 1, ''),
(4458, 1337, 1, 1, 3.99, 0, '', 1, ''),
(4459, 1337, 33, 1, 0.99, 0, '', 1, ''),
(4460, 1337, 74, 1, 1.50, 0, '', 1, ''),
(4461, 1337, 9, 1, 0.99, 0, '', 1, ''),
(4469, 1338, 15, 1, 5.50, 0, '', 1, ''),
(4470, 1338, 28, 1, 3.50, 0, '', 1, ''),
(4471, 1338, 111, 1, 1.99, 0, '', 1, ''),
(4472, 1338, 15, 1, 5.50, 0, '', 1, ''),
(4473, 1338, 101, 1, 0.25, 0, '', 1, ''),
(4474, 1338, 101, 1, 0.25, 0, '', 1, ''),
(4475, 1338, 101, 1, 0.25, 0, '', 1, ''),
(4476, 1339, 28, 1, 3.50, 0, '', 1, ''),
(4477, 1339, 64, 1, 0.99, 0, '', 1, ''),
(4478, 1339, 98, 1, 0.99, 0, '', 1, ''),
(4479, 1340, 145, 1, 1.99, 0, '', 1, ''),
(4480, 1340, 119, 1, 5.99, 0, '', 1, ''),
(4481, 1340, 119, 1, 5.99, 0, '', 1, ''),
(4482, 1340, 119, 1, 5.99, 0, '', 1, ''),
(4483, 1340, 119, 1, 5.99, 0, '', 1, ''),
(4484, 1340, 54, 1, 17.99, 0, '', 1, ''),
(4485, 1340, 111, 1, 1.99, 0, '', 1, ''),
(4486, 1340, 78, 1, 1.50, 0, '', 1, ''),
(4487, 1340, 78, 1, 1.50, 0, '', 1, ''),
(4488, 1340, 78, 1, 1.50, 0, '', 1, ''),
(4489, 1340, 101, 1, 0.25, 0, '', 1, ''),
(4490, 1340, 101, 1, 0.25, 0, '', 1, ''),
(4491, 1340, 101, 1, 0.25, 0, '', 1, ''),
(4492, 1340, 101, 1, 0.25, 0, '', 1, ''),
(4493, 1340, 111, 1, 1.99, 0, '', 1, ''),
(4494, 1341, 56, 1, 5.50, 0, '', 1, ''),
(4495, 1341, 72, 1, 1.50, 0, '', 1, ''),
(4496, 1341, 61, 1, 1.50, 0, '', 1, ''),
(4497, 1341, 72, 1, 1.50, 0, '', 1, ''),
(4501, 1342, 119, 1, 5.99, 0, '', 1, ''),
(4502, 1342, 119, 1, 5.99, 0, '', 1, ''),
(4503, 1342, 63, 1, 1.99, 0, '', 1, ''),
(4504, 1342, 78, 1, 1.50, 0, '', 1, ''),
(4505, 1342, 78, 1, 1.50, 0, '', 1, ''),
(4506, 1342, 101, 1, 0.25, 0, '', 1, ''),
(4508, 1343, 120, 1, 4.50, 0, '', 1, ''),
(4509, 1344, 28, 1, 3.50, 0, '', 1, ''),
(4510, 1344, 101, 1, 0.25, 0, '', 1, ''),
(4512, 1345, 46, 1, 3.99, 0, '', 1, ''),
(4513, 1346, 68, 1, 0.99, 0, '', 1, ''),
(4514, 1347, 112, 1, 3.00, 0, '', 1, ''),
(4515, 1347, 112, 1, 3.00, 0, '', 1, ''),
(4516, 1347, 112, 1, 3.00, 0, '', 1, ''),
(4517, 1347, 112, 1, 3.00, 0, '', 1, ''),
(4521, 1348, 1, 1, 3.99, 0, '', 1, ''),
(4522, 1348, 144, 1, 2.50, 0, '', 1, ''),
(4523, 1348, 62, 1, 1.99, 0, '', 1, ''),
(4524, 1348, 141, 1, 5.99, 0, '', 1, ''),
(4525, 1348, 150, 1, 1.99, 0, '', 1, ''),
(4526, 1348, 62, 1, 1.99, 0, '', 1, ''),
(4527, 1348, 148, 1, 5.50, 0, '', 1, ''),
(4528, 1349, 148, 1, 5.50, 0, '', 1, ''),
(4529, 1349, 148, 1, 5.50, 0, '', 1, ''),
(4530, 1349, 148, 1, 5.50, 0, '', 1, ''),
(4531, 1349, 148, 1, 5.50, 0, '', 1, ''),
(4532, 1349, 63, 1, 1.99, 0, '', 1, ''),
(4533, 1349, 65, 1, 1.99, 0, '', 1, ''),
(4535, 1350, 1, 1, 3.99, 0, '', 1, ''),
(4536, 1350, 1, 1, 3.99, 0, '', 1, ''),
(4538, 1351, 63, 1, 1.99, 0, '', 1, ''),
(4539, 1351, 63, 1, 1.99, 0, '', 1, ''),
(4540, 1351, 147, 1, 1.99, 0, '', 1, ''),
(4541, 1351, 147, 1, 1.99, 0, '', 1, ''),
(4545, 1352, 142, 1, 4.99, 0, '', 1, ''),
(4546, 1352, 16, 1, 4.99, 0, '', 1, ''),
(4547, 1352, 69, 1, 1.50, 0, '', 1, ''),
(4548, 1352, 63, 1, 1.99, 0, '', 1, ''),
(4549, 1352, 74, 1, 1.50, 0, '', 1, ''),
(4552, 1353, 33, 1, 0.99, 0, '', 1, ''),
(4553, 1353, 9, 1, 0.99, 0, '', 1, ''),
(4554, 1353, 141, 1, 5.99, 0, '', 1, ''),
(4555, 1353, 62, 1, 1.99, 0, '', 1, ''),
(4556, 1353, 60, 1, 2.50, 0, '', 1, ''),
(4557, 1353, 60, 1, 2.50, 0, '', 1, ''),
(4558, 1353, 112, 1, 3.00, 0, '', 1, ''),
(4559, 1353, 112, 1, 3.00, 0, '', 1, ''),
(4560, 1353, 112, 1, 3.00, 0, '', 1, ''),
(4561, 1353, 112, 1, 3.00, 0, '', 1, ''),
(4562, 1354, 6, 1, 3.99, 0, '', 1, ''),
(4563, 1354, 143, 1, 2.99, 0, '', 1, ''),
(4565, 1355, 66, 1, 0.99, 0, '', 1, ''),
(4566, 1355, 67, 1, 0.99, 0, '', 1, ''),
(4568, 1356, 111, 1, 1.99, 0, '', 1, ''),
(4569, 1356, 125, 1, 4.99, 0, '', 1, ''),
(4571, 1357, 25, 1, 4.99, 0, '', 1, ''),
(4572, 1357, 64, 1, 0.99, 0, '', 1, ''),
(4574, 1358, 27, 1, 5.99, 0, '', 1, ''),
(4575, 1359, 101, 1, 0.25, 0, '', 1, ''),
(4576, 1360, 134, 1, 24.99, 0, '', 1, ''),
(4577, 1360, 69, 1, 1.50, 0, '', 1, ''),
(4578, 1360, 67, 1, 0.99, 0, '', 1, ''),
(4579, 1361, 150, 1, 1.99, 0, '', 1, ''),
(4580, 1361, 150, 1, 1.99, 0, '', 1, ''),
(4581, 1361, 66, 1, 0.99, 0, '', 1, ''),
(4582, 1362, 78, 1, 1.50, 0, '', 1, ''),
(4583, 1362, 78, 1, 1.50, 0, '', 1, ''),
(4584, 1362, 78, 1, 1.50, 0, '', 1, ''),
(4585, 1362, 154, 1, 1.50, 0, '', 1, ''),
(4586, 1362, 24, 1, 4.99, 0, '', 1, ''),
(4587, 1362, 32, 1, 0.99, 0, '', 1, ''),
(4588, 1362, 15, 1, 5.50, 0, '', 1, ''),
(4589, 1362, 150, 1, 1.99, 0, '', 1, ''),
(4590, 1362, 150, 1, 1.99, 0, '', 1, ''),
(4591, 1362, 150, 1, 1.99, 0, '', 1, ''),
(4592, 1362, 150, 1, 1.99, 0, '', 1, ''),
(4593, 1362, 122, 1, 4.50, 0, '', 1, ''),
(4594, 1362, 150, 1, 1.99, 0, '', 1, ''),
(4595, 1362, 64, 1, 0.99, 0, '', 1, ''),
(4596, 1362, 64, 1, 0.99, 0, '', 1, ''),
(4597, 1363, 28, 1, 3.50, 0, '', 1, ''),
(4598, 1363, 33, 1, 0.99, 0, '', 1, ''),
(4599, 1363, 64, 1, 0.99, 0, '', 1, ''),
(4600, 1364, 112, 1, 3.00, 0, '', 1, ''),
(4601, 1364, 1, 1, 3.99, 0, '', 1, ''),
(4603, 1365, 144, 1, 2.50, 0, '', 1, ''),
(4604, 1365, 144, 1, 2.50, 0, '', 1, ''),
(4605, 1365, 144, 1, 2.50, 0, '', 1, ''),
(4606, 1365, 144, 1, 2.50, 0, '', 1, ''),
(4607, 1365, 144, 1, 2.50, 0, '', 1, ''),
(4608, 1365, 33, 1, 0.99, 0, '', 1, ''),
(4609, 1365, 145, 1, 1.99, 0, '', 1, ''),
(4610, 1365, 145, 1, 1.99, 0, '', 1, ''),
(4611, 1365, 98, 1, 0.99, 0, '', 1, ''),
(4612, 1365, 98, 1, 0.99, 0, '', 1, ''),
(4613, 1365, 78, 1, 1.50, 0, '', 1, ''),
(4614, 1365, 78, 1, 1.50, 0, '', 1, ''),
(4618, 1366, 144, 1, 2.50, 0, '', 1, ''),
(4619, 1366, 144, 1, 2.50, 0, '', 1, ''),
(4620, 1366, 78, 1, 1.50, 0, '', 1, ''),
(4621, 1366, 78, 1, 1.50, 0, '', 1, ''),
(4622, 1366, 15, 1, 5.50, 0, '', 1, ''),
(4623, 1366, 63, 1, 1.99, 0, '', 1, ''),
(4624, 1366, 63, 1, 1.99, 0, '', 1, ''),
(4625, 1366, 148, 1, 5.50, 0, '', 1, ''),
(4626, 1366, 2, 1, 4.99, 0, '', 1, ''),
(4627, 1366, 101, 1, 0.25, 0, '', 1, ''),
(4633, 1367, 54, 1, 17.99, 0, '', 1, ''),
(4634, 1367, 101, 1, 0.25, 0, '', 1, ''),
(4635, 1367, 101, 1, 0.25, 0, '', 1, ''),
(4636, 1368, 42, 1, 3.99, 0, '', 1, ''),
(4637, 1368, 42, 1, 3.99, 0, '', 1, ''),
(4638, 1368, 28, 1, 3.50, 0, '', 1, ''),
(4639, 1368, 28, 1, 3.50, 0, '', 1, ''),
(4640, 1368, 96, 1, 2.00, 0, '', 1, ''),
(4641, 1368, 96, 1, 2.00, 0, '', 1, ''),
(4642, 1368, 65, 1, 1.99, 0, '', 1, ''),
(4643, 1369, 45, 1, 3.99, 0, '', 1, ''),
(4644, 1369, 40, 1, 2.99, 0, '', 1, ''),
(4645, 1369, 27, 1, 5.99, 0, '', 1, ''),
(4646, 1369, 26, 1, 5.99, 0, '', 1, ''),
(4647, 1369, 65, 1, 1.99, 0, '', 1, ''),
(4650, 1370, 40, 1, 2.99, 0, '', 1, ''),
(4651, 1370, 123, 1, 4.99, 0, '', 1, ''),
(4652, 1370, 71, 1, 1.99, 0, '', 1, ''),
(4653, 1370, 27, 1, 5.99, 0, '', 1, ''),
(4654, 1370, 27, 1, 5.99, 0, '', 1, ''),
(4655, 1370, 74, 1, 1.50, 0, '', 1, ''),
(4656, 1370, 65, 1, 1.99, 0, '', 1, ''),
(4657, 1371, 39, 1, 2.50, 0, '', 1, ''),
(4658, 1371, 39, 1, 2.50, 0, '', 1, ''),
(4659, 1371, 101, 1, 0.25, 0, '', 1, ''),
(4660, 1371, 101, 1, 0.25, 0, '', 1, ''),
(4661, 1371, 69, 1, 1.50, 0, '', 1, ''),
(4662, 1371, 101, 1, 0.25, 0, '', 1, ''),
(4664, 1372, 121, 1, 3.50, 0, '', 1, ''),
(4665, 1373, 69, 1, 1.50, 0, '', 1, ''),
(4666, 1373, 101, 1, 0.25, 0, '', 1, ''),
(4668, 1374, 46, 1, 3.99, 0, '', 1, ''),
(4669, 1375, 147, 1, 1.99, 0, '', 1, ''),
(4670, 1375, 143, 1, 2.99, 0, '', 1, '{\"ob\":\"mixtos\"}'),
(4671, 1375, 143, 1, 2.99, 0, '', 1, '{\"ob\":\"mixto\"}'),
(4672, 1375, 143, 1, 2.99, 0, '', 1, '{\"ob\":\"huevos fritos 3 tigrilos\"}'),
(4673, 1375, 98, 1, 0.99, 0, '', 1, '{\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(4674, 1375, 63, 1, 1.99, 0, '', 1, '{\"ob\":\"mora\"}'),
(4675, 1375, 63, 1, 1.99, 0, '', 1, '{\"ob\":\"mora\"}'),
(4676, 1375, 63, 1, 1.99, 0, '', 1, '{\"ob\":\"mora\"}'),
(4684, 1376, 98, 1, 0.99, 0, '', 1, ''),
(4685, 1376, 98, 1, 0.99, 0, '', 1, ''),
(4686, 1376, 98, 1, 0.99, 0, '', 1, ''),
(4687, 1377, 68, 1, 0.99, 0, '', 1, ''),
(4688, 1378, 148, 1, 5.50, 0, '', 1, '{\"ob\":\"tigriillo con queso\"}'),
(4689, 1378, 148, 1, 5.50, 0, '', 1, '{\"ob\":\"cafe en agua los dos\"}'),
(4691, 1379, 148, 1, 5.50, 0, NULL, 1, NULL),
(4692, 1379, 142, 1, 4.99, 0, NULL, 1, NULL),
(4693, 1379, 78, 1, 1.50, 0, NULL, 1, NULL),
(4694, 1379, 78, 1, 1.50, 0, NULL, 1, NULL),
(4695, 1379, 78, 1, 1.50, 0, NULL, 1, NULL),
(4696, 1379, 78, 1, 1.50, 0, NULL, 1, NULL),
(4697, 1379, 78, 1, 1.50, 0, NULL, 1, NULL),
(4698, 1379, 78, 1, 1.50, 0, NULL, 1, NULL),
(4699, 1379, 143, 1, 2.99, 0, NULL, 1, NULL),
(4700, 1379, 148, 1, 5.50, 0, NULL, 1, NULL),
(4701, 1379, 141, 1, 5.99, 0, NULL, 1, NULL),
(4702, 1379, 141, 1, 5.99, 0, NULL, 1, NULL),
(4703, 1379, 144, 1, 2.50, 0, NULL, 1, NULL),
(4704, 1379, 98, 1, 0.99, 0, NULL, 1, NULL),
(4705, 1379, 98, 1, 0.99, 0, NULL, 1, NULL),
(4706, 1379, 98, 1, 0.99, 0, NULL, 1, NULL),
(4707, 1379, 64, 1, 0.99, 0, NULL, 1, NULL),
(4722, 1380, 67, 1, 0.99, 0, NULL, 1, NULL),
(4723, 1381, 101, 1, 0.25, 0, NULL, 1, NULL),
(4724, 1382, 155, 1, 2.50, 0, NULL, 1, NULL),
(4725, 1382, 155, 1, 2.50, 0, NULL, 1, NULL),
(4726, 1382, 148, 1, 5.50, 0, NULL, 1, NULL),
(4727, 1382, 148, 1, 5.50, 0, NULL, 1, NULL),
(4728, 1382, 148, 1, 5.50, 0, NULL, 1, NULL),
(4729, 1382, 95, 1, 0.35, 0, NULL, 1, NULL),
(4731, 1383, 74, 1, 1.50, 0, NULL, 1, NULL),
(4732, 1383, 144, 1, 2.50, 0, NULL, 1, NULL),
(4733, 1383, 62, 1, 1.99, 0, NULL, 1, NULL),
(4734, 1383, 151, 1, 1.50, 0, NULL, 1, NULL),
(4735, 1383, 98, 1, 0.99, 0, NULL, 1, NULL),
(4736, 1383, 98, 1, 0.99, 0, NULL, 1, NULL),
(4737, 1383, 62, 1, 1.99, 0, NULL, 1, NULL),
(4738, 1383, 143, 1, 2.99, 0, NULL, 1, NULL),
(4739, 1383, 98, 1, 0.99, 0, NULL, 1, NULL),
(4740, 1383, 69, 1, 1.50, 0, NULL, 1, NULL),
(4741, 1383, 143, 1, 2.99, 0, NULL, 1, NULL),
(4742, 1383, 98, 1, 0.99, 0, NULL, 1, NULL),
(4743, 1383, 62, 1, 1.99, 0, NULL, 1, NULL),
(4744, 1383, 1, 1, 3.99, 0, NULL, 1, NULL),
(4745, 1383, 74, 1, 1.50, 0, NULL, 1, NULL),
(4746, 1383, 98, 1, 0.99, 0, NULL, 1, NULL),
(4747, 1383, 62, 1, 1.99, 0, NULL, 1, NULL),
(4748, 1383, 95, 1, 0.35, 0, NULL, 1, NULL),
(4749, 1383, 95, 1, 0.35, 0, NULL, 1, NULL),
(4750, 1383, 150, 1, 1.99, 0, NULL, 1, NULL),
(4751, 1383, 67, 1, 0.99, 0, NULL, 1, NULL),
(4752, 1383, 74, 1, 1.50, 0, NULL, 1, NULL),
(4753, 1383, 150, 1, 1.99, 0, NULL, 1, NULL),
(4754, 1383, 152, 1, 0.99, 0, NULL, 1, NULL),
(4755, 1383, 98, 1, 0.99, 0, NULL, 1, NULL),
(4762, 1384, 68, 1, 0.99, 0, NULL, 1, NULL),
(4763, 1385, 78, 1, 1.50, 0, NULL, 1, '{\"ob\":\"cafe en leche\"}'),
(4764, 1386, 27, 1, 5.99, 0, NULL, 1, NULL),
(4765, 1386, 64, 1, 0.99, 0, NULL, 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(4767, 1387, 101, 1, 0.25, 0, NULL, 1, NULL),
(4768, 1388, 42, 1, 3.99, 0, NULL, 1, NULL),
(4769, 1388, 42, 1, 3.99, 0, NULL, 1, '{\"ob\":\"solo queso\"}'),
(4770, 1388, 66, 1, 0.99, 0, NULL, 1, NULL),
(4771, 1388, 66, 1, 0.99, 0, NULL, 1, NULL),
(4775, 1389, 120, 1, 4.50, 0, NULL, 1, '{\"Arroz\":\"Arroz Moro\",\"Papas_fritas\":\"Papas Fritas\",\"Ensalada\":\"Fresca\",\"ob\":\"\"}'),
(4776, 1389, 120, 1, 4.50, 0, NULL, 1, '{\"Arroz\":\"Arroz Moro\",\"Papas_fritas\":\"Papas Fritas\",\"Ensalada\":\"Fresca\",\"ob\":\"\"}'),
(4777, 1389, 64, 1, 0.99, 0, NULL, 1, NULL),
(4778, 1389, 69, 1, 1.50, 0, NULL, 1, NULL),
(4779, 1389, 24, 1, 4.99, 0, NULL, 1, NULL),
(4780, 1389, 69, 1, 1.50, 0, NULL, 1, NULL),
(4781, 1389, 69, 1, 1.50, 0, NULL, 1, NULL),
(4782, 1389, 69, 1, 1.50, 0, NULL, 1, NULL),
(4783, 1389, 150, 1, 1.99, 0, NULL, 1, NULL),
(4784, 1389, 33, 1, 0.99, 0, NULL, 1, NULL),
(4785, 1389, 69, 1, 1.50, 0, NULL, 1, NULL),
(4786, 1389, 67, 1, 0.99, 0, NULL, 1, NULL),
(4790, 1390, 26, 1, 5.99, 0, NULL, 1, NULL),
(4791, 1390, 120, 1, 4.50, 0, NULL, 1, '{\"Arroz\":\"Arroz Moro\",\"Papas_fritas\":\"Papas Fritas\",\"Ensalada\":\"Fresca\",\"ob\":\"\"}'),
(4792, 1390, 130, 1, 6.50, 0, NULL, 1, '{\"ob\":\"En plato\"}'),
(4793, 1390, 150, 1, 1.99, 0, NULL, 1, NULL),
(4794, 1390, 65, 1, 1.99, 0, NULL, 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(4795, 1390, 64, 1, 0.99, 0, NULL, 1, NULL),
(4796, 1390, 65, 1, 1.99, 0, NULL, 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(4797, 1390, 101, 1, 0.25, 0, NULL, 1, NULL),
(4805, 1391, 28, 1, 3.50, 0, NULL, 1, NULL),
(4806, 1391, 66, 1, 0.99, 0, NULL, 1, NULL),
(4808, 1392, 56, 1, 5.50, 0, NULL, 1, NULL),
(4809, 1392, 101, 1, 0.25, 0, NULL, 1, NULL),
(4811, 1393, 6, 1, 3.99, 0, NULL, 1, '{\"ob\":\"fasdasdasd\"}'),
(4812, 1393, 6, 1, 3.99, 0, NULL, 1, '{\"ob\":\"dasdasd\"}'),
(4814, 1394, 119, 1, 7.50, 0, NULL, 1, NULL),
(4815, 1394, 119, 1, 7.50, 0, NULL, 1, NULL),
(4817, 1395, 119, 1, 7.50, 0, NULL, 1, NULL),
(4818, 1395, 118, 1, 1.50, 0, NULL, 1, NULL),
(4820, 1396, 119, 1, 7.50, 0, NULL, 1, NULL),
(4821, 1396, 118, 1, 1.50, 0, NULL, 1, NULL),
(4822, 1396, 64, 1, 0.99, 0, NULL, 1, '{\"Estado_Bebida\":\"Frio\",\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(4823, 1397, 6, 1, 3.99, 0, NULL, 1, '{\"ob\":\"asdasd\"}'),
(4824, 1397, 3, 1, 5.50, 0, NULL, 1, NULL),
(4825, 1397, 5, 1, 5.99, 0, NULL, 1, NULL),
(4826, 1398, 5, 1, 5.99, 0, NULL, 1, NULL),
(4827, 1398, 4, 1, 4.99, 0, NULL, 1, NULL),
(4828, 1398, 4, 1, 4.99, 0, NULL, 1, NULL),
(4829, 1399, 5, 1, 5.99, 0, NULL, 1, NULL),
(4830, 1399, 4, 1, 4.99, 0, NULL, 1, NULL),
(4831, 1399, 5, 1, 5.99, 0, NULL, 1, NULL),
(4832, 1399, 3, 1, 5.50, 0, NULL, 1, '{\"Estado_verde\":\"Bolon mixto\",\"ob\":\"\"}'),
(4836, 1400, 12, 1, 1.99, 0, NULL, 1, NULL),
(4837, 1400, 12, 1, 1.99, 0, NULL, 1, NULL),
(4838, 1400, 12, 1, 1.99, 0, NULL, 1, NULL),
(4839, 1400, 12, 1, 1.99, 0, NULL, 1, NULL),
(4843, 1401, 11, 1, 0.99, 0, NULL, 1, NULL),
(4844, 1401, 11, 1, 0.99, 0, NULL, 1, NULL),
(4845, 1401, 11, 1, 0.99, 0, NULL, 1, NULL),
(4846, 1402, 5, 1, 5.99, 0, NULL, 1, NULL),
(4847, 1402, 5, 1, 5.99, 0, NULL, 1, NULL),
(4848, 1402, 4, 1, 4.99, 0, NULL, 1, NULL),
(4849, 1403, 5, 1, 5.99, 0, NULL, 1, NULL),
(4850, 1403, 5, 1, 5.99, 0, NULL, 1, NULL),
(4852, 1404, 5, 1, 5.99, 0, NULL, 1, NULL),
(4853, 1404, 5, 1, 5.99, 0, NULL, 1, NULL),
(4854, 1404, 5, 1, 5.99, 0, NULL, 1, '{\"Bebida_Caliente\":\"Cafe Pasado\",\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(4855, 1404, 5, 1, 5.99, 0, NULL, 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Bebida_Fria\":\"Jugo Frutilla\",\"ob\":\"\"}'),
(4856, 1404, 5, 1, 5.99, 0, NULL, 1, NULL),
(4859, 1405, 5, 1, 5.99, 0, NULL, 1, NULL),
(4860, 1405, 5, 1, 5.99, 0, NULL, 1, NULL),
(4862, 1406, 10, 1, 0.99, 0, NULL, 1, NULL),
(4863, 1407, 5, 1, 5.99, 0, NULL, 1, NULL),
(4864, 1408, 5, 1, 5.99, 0, NULL, 1, NULL),
(4865, 1409, 144, 1, 2.50, 0, NULL, 1, NULL),
(4866, 1410, 112, 1, 3.00, 0, NULL, 1, NULL),
(4867, 1410, 1, 1, 3.99, 0, NULL, 1, NULL),
(4868, 1410, 1, 1, 3.99, 0, NULL, 1, NULL),
(4869, 1411, 143, 1, 2.99, 0, NULL, 1, NULL),
(4870, 1411, 143, 1, 2.99, 0, NULL, 1, NULL),
(4871, 1411, 74, 1, 1.50, 0, NULL, 1, NULL),
(4872, 1411, 74, 1, 1.50, 0, NULL, 1, NULL),
(4876, 1412, 148, 1, 5.50, 0, NULL, 1, NULL),
(4877, 1412, 148, 1, 5.50, 0, NULL, 1, NULL),
(4879, 1413, 123, 1, 4.99, 0, NULL, 1, NULL),
(4880, 1413, 16, 1, 4.99, 0, NULL, 1, NULL),
(4881, 1413, 96, 1, 2.00, 0, NULL, 1, NULL),
(4882, 1413, 65, 1, 1.99, 0, NULL, 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(4886, 1414, 50, 1, 3.50, 0, NULL, 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"\"}'),
(4887, 1414, 50, 1, 3.50, 0, NULL, 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"\"}'),
(4888, 1414, 123, 1, 4.99, 0, NULL, 1, '{\"ob\":\"SIN PICKLES\"}'),
(4889, 1414, 120, 1, 4.99, 0, NULL, 1, '{\"Patacones\":\"Patacones\",\"Ensalada\":\"Fresca\",\"ob\":\"SIN ARROZ Y MENESTRA\"}'),
(4890, 1414, 124, 1, 4.99, 0, NULL, 1, NULL),
(4891, 1414, 105, 1, 5.99, 0, NULL, 1, NULL),
(4893, 1415, 51, 1, 5.99, 0, NULL, 1, '{\"Sabor_alitas\":\"Moztaza y miel\",\"ob\":\"Y MARACUYA\"}'),
(4894, 1415, 126, 1, 5.99, 0, NULL, 1, NULL),
(4895, 1415, 62, 1, 1.99, 0, NULL, 1, '{\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(4896, 1415, 62, 1, 1.99, 0, NULL, 1, '{\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(4897, 1415, 71, 1, 1.99, 0, NULL, 1, NULL),
(4898, 1415, 24, 1, 4.99, 0, NULL, 1, NULL),
(4899, 1415, 64, 1, 0.99, 0, NULL, 1, '{\"Sabor_gaseosa\":\"Fanta\",\"ob\":\"\"}'),
(4900, 1415, 15, 1, 5.50, 0, NULL, 1, NULL),
(4901, 1415, 53, 1, 13.99, 0, NULL, 1, '{\"ob\":\"miely mostaza, maracuya, bbq picante\"}'),
(4902, 1415, 62, 1, 1.99, 0, NULL, 1, '{\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(4903, 1415, 62, 1, 1.99, 0, NULL, 1, '{\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(4904, 1415, 62, 1, 1.99, 0, NULL, 1, '{\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(4905, 1415, 62, 1, 1.99, 0, NULL, 1, '{\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(4906, 1415, 62, 1, 1.99, 0, NULL, 1, '{\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(4907, 1415, 74, 1, 1.50, 0, NULL, 1, NULL),
(4908, 1415, 28, 1, 3.50, 0, NULL, 1, '{\"ob\":\"pan queso mayonesa y salsa de tomate\"}'),
(4909, 1415, 127, 1, 1.99, 0, NULL, 1, NULL),
(4910, 1415, 62, 1, 1.99, 0, NULL, 1, '{\"ob\":\"mora\"}'),
(4911, 1415, 62, 1, 1.99, 0, NULL, 1, '{\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"mora\"}'),
(4912, 1415, 15, 1, 5.50, 0, NULL, 1, NULL),
(4913, 1415, 121, 1, 3.50, 0, NULL, 1, NULL),
(4924, 1416, 50, 1, 3.50, 0, NULL, 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"\"}'),
(4925, 1416, 101, 1, 0.25, 0, NULL, 1, NULL),
(4927, 1417, 133, 1, 13.99, 0, NULL, 1, '{\"ob\":\"6 ALITAS BBQ, HAMBURGUESA CLASICA, PAPAS SUPREMA DE TOCINO, EBIDAS TE\"}'),
(4928, 1417, 58, 1, 4.99, 0, NULL, 1, NULL),
(4929, 1417, 74, 1, 1.50, 0, NULL, 1, NULL),
(4930, 1417, 74, 1, 1.50, 0, NULL, 1, NULL),
(4931, 1417, 28, 1, 3.50, 0, NULL, 1, NULL),
(4932, 1417, 45, 1, 3.99, 0, NULL, 1, NULL),
(4933, 1417, 21, 1, 1.99, 0, NULL, 1, NULL),
(4934, 1418, 44, 1, 5.50, 0, NULL, 1, NULL),
(4935, 1418, 38, 1, 1.99, 0, NULL, 1, NULL),
(4936, 1418, 126, 1, 5.99, 0, NULL, 1, NULL),
(4937, 1418, 31, 1, 1.50, 0, NULL, 1, NULL),
(4938, 1418, 71, 1, 1.99, 0, NULL, 1, NULL),
(4939, 1418, 71, 1, 1.99, 0, NULL, 1, NULL),
(4941, 1419, 58, 1, 4.99, 0, NULL, 1, NULL),
(4942, 1419, 74, 1, 1.50, 0, NULL, 1, NULL),
(4943, 1419, 74, 1, 1.50, 0, NULL, 1, NULL),
(4944, 1419, 100, 1, 4.99, 0, NULL, 1, NULL),
(4945, 1419, 26, 1, 5.99, 0, NULL, 1, NULL),
(4946, 1419, 120, 1, 4.99, 0, NULL, 1, NULL),
(4947, 1419, 121, 1, 3.50, 0, NULL, 1, NULL),
(4948, 1420, 146, 1, 1.50, 0, NULL, 1, NULL),
(4949, 1420, 60, 1, 2.50, 0, NULL, 1, NULL),
(4950, 1420, 40, 1, 2.99, 0, NULL, 1, NULL),
(4951, 1420, 40, 1, 2.99, 0, NULL, 1, NULL),
(4952, 1420, 62, 1, 1.99, 0, NULL, 1, NULL),
(4953, 1420, 62, 1, 1.99, 0, NULL, 1, NULL),
(4954, 1420, 62, 1, 1.99, 0, NULL, 1, NULL),
(4955, 1420, 74, 1, 1.50, 0, NULL, 1, NULL),
(4956, 1420, 74, 1, 1.50, 0, NULL, 1, NULL),
(4957, 1420, 74, 1, 1.50, 0, NULL, 1, NULL),
(4958, 1420, 27, 1, 5.99, 0, NULL, 1, NULL),
(4959, 1420, 61, 1, 1.50, 0, NULL, 1, NULL),
(4960, 1420, 119, 1, 7.50, 0, NULL, 1, NULL),
(4961, 1420, 126, 1, 5.99, 0, NULL, 1, NULL),
(4963, 1421, 28, 1, 3.50, 0, NULL, 1, NULL),
(4964, 1421, 28, 1, 3.50, 0, NULL, 1, NULL),
(4965, 1421, 101, 1, 0.25, 0, NULL, 1, NULL),
(4966, 1421, 101, 1, 0.25, 0, NULL, 1, NULL),
(4970, 1422, 45, 1, 3.99, 0, NULL, 1, NULL),
(4971, 1422, 74, 1, 1.50, 0, NULL, 1, NULL),
(4972, 1422, 26, 1, 5.99, 0, NULL, 1, NULL),
(4973, 1423, 130, 1, 6.50, 0, NULL, 1, NULL),
(4974, 1423, 26, 1, 5.99, 0, NULL, 1, NULL),
(4975, 1423, 64, 1, 0.99, 0, NULL, 1, NULL),
(4976, 1423, 64, 1, 0.99, 0, NULL, 1, NULL),
(4980, 1424, 51, 1, 5.99, 0, NULL, 1, NULL),
(4981, 1424, 61, 1, 1.50, 0, NULL, 1, NULL),
(4983, 1425, 51, 1, 5.99, 0, NULL, 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"\"}'),
(4984, 1426, 101, 1, 0.25, 0, NULL, 1, NULL),
(4985, 1427, 101, 1, 0.25, 0, NULL, 1, NULL),
(4986, 1427, 101, 1, 0.25, 0, NULL, 1, NULL),
(4987, 1427, 28, 1, 3.50, 0, NULL, 1, NULL),
(4988, 1428, 133, 1, 13.99, 0, NULL, 1, NULL),
(4989, 1428, 133, 1, 13.99, 0, NULL, 1, NULL),
(4990, 1428, 133, 1, 13.99, 0, NULL, 1, NULL),
(4991, 1428, 64, 1, 0.99, 0, NULL, 1, NULL),
(4992, 1428, 68, 1, 0.99, 0, NULL, 1, NULL),
(4993, 1428, 64, 1, 0.99, 0, NULL, 1, NULL),
(4995, 1429, 119, 1, 7.50, 0, NULL, 1, NULL),
(4996, 1429, 119, 1, 7.50, 0, NULL, 1, NULL),
(4997, 1429, 40, 1, 2.99, 0, NULL, 1, NULL),
(4998, 1429, 40, 1, 2.99, 0, NULL, 1, NULL),
(4999, 1429, 122, 1, 4.50, 0, NULL, 1, NULL),
(5000, 1429, 120, 1, 4.99, 0, NULL, 1, NULL),
(5001, 1429, 120, 1, 4.99, 0, NULL, 1, NULL),
(5002, 1429, 65, 1, 1.99, 0, NULL, 1, NULL),
(5003, 1429, 65, 1, 1.99, 0, NULL, 1, NULL),
(5004, 1429, 101, 1, 0.25, 0, NULL, 1, NULL),
(5005, 1429, 68, 1, 0.99, 0, NULL, 1, NULL),
(5010, 1430, 46, 1, 3.99, 0, NULL, 1, NULL),
(5011, 1430, 68, 1, 0.99, 0, NULL, 1, NULL),
(5013, 1431, 44, 1, 5.50, 0, NULL, 1, NULL),
(5014, 1431, 38, 1, 1.99, 0, NULL, 1, NULL),
(5015, 1431, 126, 1, 5.99, 0, NULL, 1, NULL),
(5016, 1431, 31, 1, 1.50, 0, NULL, 1, NULL),
(5017, 1431, 71, 1, 1.99, 0, NULL, 1, NULL),
(5018, 1431, 71, 1, 1.99, 0, NULL, 1, NULL),
(5020, 1432, 1, 1, 3.99, 0, NULL, 1, NULL),
(5021, 1432, 1, 1, 3.99, 0, NULL, 1, NULL),
(5022, 1432, 2, 1, 4.99, 0, NULL, 1, NULL),
(5023, 1432, 6, 1, 3.99, 0, NULL, 1, NULL),
(5024, 1432, 60, 1, 2.50, 0, NULL, 1, NULL),
(5025, 1432, 60, 1, 2.50, 0, NULL, 1, NULL),
(5026, 1432, 60, 1, 2.50, 0, NULL, 1, NULL),
(5027, 1433, 4, 1, 4.99, 0, NULL, 1, NULL),
(5028, 1433, 4, 1, 4.99, 0, NULL, 1, NULL),
(5029, 1433, 103, 1, 3.00, 0, NULL, 1, NULL),
(5030, 1433, 143, 1, 3.50, 0, NULL, 1, NULL),
(5031, 1433, 78, 1, 1.50, 0, NULL, 1, NULL),
(5032, 1433, 78, 1, 1.50, 0, NULL, 1, NULL),
(5033, 1433, 112, 1, 3.00, 0, NULL, 1, NULL),
(5034, 1434, 103, 1, 3.00, 0, NULL, 1, NULL),
(5035, 1434, 148, 1, 5.50, 0, NULL, 1, NULL),
(5036, 1434, 78, 1, 1.50, 0, NULL, 1, NULL),
(5037, 1434, 112, 1, 3.00, 0, NULL, 1, NULL),
(5038, 1434, 112, 1, 3.00, 0, NULL, 1, NULL),
(5039, 1434, 112, 1, 3.00, 0, NULL, 1, NULL),
(5041, 1435, 1, 1, 3.99, 0, NULL, 1, NULL),
(5042, 1435, 1, 1, 3.99, 0, NULL, 1, NULL),
(5043, 1435, 148, 1, 5.50, 0, NULL, 1, NULL),
(5044, 1436, 141, 1, 5.99, 0, NULL, 1, NULL),
(5045, 1436, 1, 1, 3.99, 0, NULL, 1, NULL),
(5046, 1436, 78, 1, 1.50, 0, NULL, 1, NULL),
(5047, 1437, 4, 1, 4.99, 0, NULL, 1, NULL),
(5048, 1437, 148, 1, 5.50, 0, NULL, 1, NULL),
(5049, 1437, 66, 1, 0.99, 0, NULL, 1, NULL),
(5050, 1438, 4, 1, 4.99, 0, NULL, 1, NULL),
(5051, 1438, 4, 1, 4.99, 0, NULL, 1, NULL),
(5052, 1438, 112, 1, 3.00, 0, NULL, 1, NULL),
(5053, 1438, 147, 1, 1.99, 0, NULL, 1, NULL),
(5057, 1439, 149, 1, 5.50, 0, NULL, 1, '{\"ob\":\"Pollo, Arroz moro\"}'),
(5058, 1439, 149, 1, 5.50, 0, NULL, 1, '{\"ob\":\"Estofado Carne, Arroz moro\"}'),
(5059, 1439, 149, 1, 5.50, 0, NULL, 1, '{\"ob\":\"Estofado Carne, Arroz moro\"}'),
(5060, 1439, 145, 1, 1.99, 0, NULL, 1, '{\"ob\":\"Queso y sal prieta a parte\"}'),
(5061, 1439, 65, 1, 1.99, 0, NULL, 1, NULL),
(5062, 1439, 143, 1, 3.50, 0, NULL, 1, NULL),
(5063, 1439, 145, 1, 1.99, 0, NULL, 1, NULL),
(5064, 1440, 149, 1, 5.50, 0, NULL, 1, '{\"ob\":\"Estofado Carne, Arroz moro\"}'),
(5065, 1440, 4, 1, 4.99, 0, NULL, 1, NULL),
(5066, 1440, 103, 1, 3.00, 0, NULL, 1, '{\"ob\":\"Mixto, Huevo Frito\"}'),
(5067, 1441, 148, 1, 5.50, 0, NULL, 1, '{\"ob\":\"Trigrillo mixto, te, jugo de mora, frito duro\"}'),
(5068, 1441, 74, 1, 1.50, 0, NULL, 1, NULL),
(5069, 1441, 15, 1, 5.50, 0, NULL, 1, NULL),
(5070, 1442, 110, 1, 1.50, 0, NULL, 1, NULL),
(5071, 1442, 110, 1, 1.50, 0, NULL, 1, NULL),
(5072, 1442, 110, 1, 1.50, 0, NULL, 1, NULL),
(5073, 1443, 41, 1, 2.99, 0, NULL, 1, NULL),
(5074, 1443, 41, 1, 2.99, 0, NULL, 1, NULL),
(5075, 1443, 64, 1, 0.99, 0, NULL, 1, NULL),
(5076, 1443, 66, 1, 0.99, 0, NULL, 1, NULL),
(5077, 1444, 65, 1, 1.99, 0, NULL, 1, NULL),
(5078, 1445, 25, 1, 4.99, 0, NULL, 1, NULL),
(5079, 1445, 25, 1, 4.99, 0, NULL, 1, NULL),
(5080, 1445, 65, 1, 1.99, 0, NULL, 1, NULL),
(5081, 1445, 39, 1, 2.50, 0, NULL, 1, NULL),
(5082, 1445, 24, 1, 4.99, 0, NULL, 1, NULL),
(5083, 1445, 37, 1, 0.99, 0, NULL, 1, NULL),
(5084, 1445, 37, 1, 0.99, 0, NULL, 1, NULL),
(5085, 1446, 51, 1, 5.99, 0, NULL, 1, '{\"ob\":\"bbq y maracuya\"}'),
(5086, 1446, 119, 1, 7.50, 0, NULL, 1, '{\"Arroz\":\"Arroz Moro\",\"Patacones\":\"Patacones\",\"ob\":\"\"}'),
(5087, 1446, 62, 1, 1.99, 0, NULL, 1, '{\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(5088, 1446, 63, 1, 1.99, 0, NULL, 1, '{\"ob\":\"de fresa\"}'),
(5089, 1446, 63, 1, 1.99, 0, NULL, 1, '{\"ob\":\"de fresa\"}'),
(5090, 1446, 24, 1, 4.99, 0, NULL, 1, NULL),
(5092, 1447, 133, 1, 13.99, 0, NULL, 1, '{\"ob\":\"hamburguesa clasica,papas suprema de carne,6 alitas maracuya,2 tè helado\"}'),
(5093, 1448, 24, 1, 4.99, 0, NULL, 1, NULL),
(5094, 1448, 43, 1, 3.99, 0, NULL, 1, NULL),
(5095, 1448, 42, 1, 3.99, 0, NULL, 1, NULL),
(5096, 1448, 39, 1, 2.50, 0, NULL, 1, NULL),
(5097, 1448, 71, 1, 1.99, 0, NULL, 1, NULL),
(5098, 1448, 82, 1, 4.99, 0, NULL, 1, NULL),
(5099, 1448, 69, 1, 1.50, 0, NULL, 1, NULL),
(5100, 1449, 120, 1, 4.99, 0, NULL, 1, NULL),
(5101, 1449, 120, 1, 4.99, 0, NULL, 1, NULL),
(5102, 1449, 120, 1, 4.99, 0, NULL, 1, NULL),
(5103, 1449, 120, 1, 4.99, 0, NULL, 1, NULL),
(5104, 1449, 121, 1, 3.50, 0, NULL, 1, NULL),
(5105, 1449, 26, 1, 5.99, 0, NULL, 1, NULL),
(5107, 1449, 64, 1, 0.99, 0, NULL, 1, NULL),
(5108, 1449, 64, 1, 0.99, 0, NULL, 1, NULL),
(5109, 1449, 64, 1, 0.99, 0, NULL, 1, NULL),
(5110, 1449, 64, 1, 0.99, 0, NULL, 1, NULL),
(5111, 1449, 64, 1, 0.99, 0, NULL, 1, NULL),
(5112, 1449, 64, 1, 0.99, 0, NULL, 1, NULL),
(5113, 1449, 64, 1, 0.99, 0, NULL, 1, NULL),
(5114, 1449, 62, 1, 1.99, 0, NULL, 1, NULL),
(5115, 1449, 119, 1, 7.50, 0, NULL, 1, NULL),
(5116, 1449, 120, 1, 4.99, 0, NULL, 1, NULL),
(5117, 1449, 25, 1, 4.99, 0, NULL, 1, NULL),
(5118, 1449, 42, 1, 3.99, 0, NULL, 1, '{\"ob\":\"suprema detocino\"}'),
(5131, 1450, 71, 1, 1.99, 0, NULL, 1, NULL),
(5132, 1450, 71, 1, 1.99, 0, NULL, 1, NULL),
(5133, 1450, 125, 1, 4.99, 0, NULL, 1, NULL),
(5134, 1450, 39, 1, 2.50, 0, NULL, 1, NULL),
(5135, 1450, 64, 1, 0.99, 0, NULL, 1, NULL),
(5136, 1450, 119, 1, 7.50, 0, NULL, 1, NULL),
(5137, 1450, 64, 1, 0.99, 0, NULL, 1, NULL),
(5138, 1451, 71, 1, 1.99, 0, NULL, 1, NULL),
(5139, 1451, 71, 1, 1.99, 0, NULL, 1, NULL),
(5140, 1451, 71, 1, 1.99, 0, NULL, 1, NULL),
(5141, 1451, 41, 1, 2.99, 0, NULL, 1, NULL),
(5142, 1451, 120, 1, 4.99, 0, NULL, 1, NULL),
(5143, 1451, 120, 1, 4.99, 0, NULL, 1, NULL),
(5145, 1452, 65, 1, 1.99, 0, NULL, 1, NULL),
(5146, 1453, 67, 1, 0.99, 0, NULL, 1, NULL),
(5147, 1454, 50, 1, 3.50, 0, NULL, 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"\"}'),
(5148, 1454, 25, 1, 4.99, 0, NULL, 1, NULL),
(5149, 1454, 120, 1, 4.99, 0, NULL, 1, '{\"Arroz\":\"Arroz y menestra\",\"Papas_fritas\":\"Papas Fritas\",\"Ensalada\":\"Fresca\",\"ob\":\"\"}'),
(5150, 1454, 109, 1, 2.25, 0, NULL, 1, NULL),
(5154, 1455, 24, 1, 4.99, 0, NULL, 1, NULL),
(5155, 1455, 44, 1, 5.50, 0, NULL, 1, NULL),
(5156, 1455, 122, 1, 4.99, 0, NULL, 1, '{\"Arroz\":\"Arroz Moro\",\"Papas_fritas\":\"Papas Fritas\",\"Ensalada\":\"Fresca\",\"ob\":\"\"}'),
(5157, 1456, 133, 1, 13.99, 0, NULL, 1, '{\"ob\":\"PAPA SUPREMA DE POLLO,6 ALITAS MOSTAZA Y MIEL Y MARACUYA, 2 COCA COLA\"}'),
(5158, 1456, 24, 1, 4.99, 0, NULL, 1, NULL),
(5159, 1456, 41, 1, 2.99, 0, NULL, 1, '{\"ob\":\"EN PLATOCON ENSALADA\"}'),
(5160, 1456, 126, 1, 5.99, 0, NULL, 1, NULL),
(5161, 1456, 65, 1, 1.99, 0, NULL, 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(5162, 1456, 150, 1, 1.99, 0, NULL, 1, NULL),
(5164, 1457, 125, 1, 4.99, 0, NULL, 1, NULL),
(5165, 1457, 15, 1, 5.50, 0, NULL, 1, NULL),
(5166, 1457, 64, 1, 0.99, 0, NULL, 1, '{\"Sabor_gaseosa\":\"Fanta\",\"ob\":\"\"}'),
(5167, 1458, 121, 1, 3.99, 0, NULL, 1, '{\"Patacones\":\"Patacones\",\"ob\":\"\"}'),
(5168, 1458, 126, 1, 5.99, 0, NULL, 1, NULL),
(5169, 1458, 51, 1, 5.99, 0, NULL, 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"\"}'),
(5170, 1458, 109, 1, 2.25, 0, NULL, 1, NULL),
(5174, 1459, 121, 1, 3.99, 0, NULL, 1, '{\"Patacones\":\"Patacones\",\"ob\":\"\"}'),
(5175, 1459, 51, 1, 5.99, 0, NULL, 1, '{\"Sabor_alitas\":\"BBQ Picante\",\"ob\":\"MOSTAZA Y MIEL\"}'),
(5176, 1459, 63, 1, 1.99, 0, NULL, 1, '{\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(5177, 1459, 63, 1, 1.99, 0, NULL, 1, '{\"Bebida_Fria\":\"Jugo Frutilla\",\"ob\":\"\"}'),
(5181, 1460, 43, 1, 3.99, 0, NULL, 1, NULL),
(5182, 1460, 27, 1, 5.99, 0, NULL, 1, NULL),
(5183, 1460, 64, 1, 0.99, 0, NULL, 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(5184, 1460, 64, 1, 0.99, 0, NULL, 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(5188, 1461, 78, 1, 1.50, 0, NULL, 1, '{\"ob\":\"CAFE LECHE \"}'),
(5189, 1462, 40, 1, 2.99, 0, NULL, 1, NULL),
(5190, 1462, 101, 1, 0.45, 0, NULL, 1, NULL),
(5192, 1463, 165, 1, 3.99, 0, NULL, 1, '{\"ob\":\"SIN BBQ\"}'),
(5193, 1463, 52, 1, 8.99, 0, NULL, 1, '{\"Sabor_alitas\":\"BBQ Picante\",\"ob\":\"BBQ NORMAL 5, BBQ PICANTE 4\"}'),
(5194, 1463, 79, 1, 2.50, 0, NULL, 1, '{\"ob\":\"CLUB\"}'),
(5195, 1463, 67, 1, 0.99, 0, NULL, 1, NULL),
(5199, 1464, 1, 1, 3.99, 0, NULL, 1, '{\"Bebida_Caliente\":\"Cafe Pasado\",\"ob\":\"\"}'),
(5200, 1464, 1, 1, 3.99, 0, NULL, 1, '{\"Bebida_Caliente\":\"Cafe Pasado\",\"ob\":\"\"}'),
(5202, 1465, 74, 1, 1.50, 0, NULL, 1, NULL),
(5203, 1465, 74, 1, 1.50, 0, NULL, 1, NULL),
(5204, 1465, 101, 1, 0.45, 0, NULL, 1, NULL),
(5205, 1466, 149, 1, 5.50, 0, NULL, 1, '{\"ob\":\"te\"}'),
(5206, 1466, 149, 1, 5.50, 0, NULL, 1, '{\"ob\":\"arriz moro \"}'),
(5208, 1467, 79, 1, 2.50, 0, NULL, 1, NULL),
(5209, 1468, 1, 1, 3.99, 0, NULL, 1, NULL),
(5210, 1468, 141, 1, 5.99, 0, NULL, 1, NULL),
(5211, 1468, 74, 1, 1.50, 0, NULL, 1, NULL),
(5212, 1468, 110, 1, 1.50, 0, NULL, 1, NULL),
(5213, 1468, 98, 1, 0.99, 0, NULL, 1, NULL),
(5216, 1469, 28, 1, 3.50, 0, NULL, 1, NULL),
(5217, 1469, 62, 1, 1.99, 0, NULL, 1, NULL),
(5219, 1470, 27, 1, 5.99, 0, NULL, 1, NULL),
(5220, 1471, 27, 1, 5.99, 0, NULL, 1, NULL),
(5221, 1472, 120, 1, 4.99, 0, NULL, 1, '{\"Arroz\":\"Arroz y menestra\",\"Patacones\":\"Patacones\",\"ob\":\"pollo\"}'),
(5222, 1472, 120, 1, 4.99, 0, NULL, 1, '{\"Arroz\":\"Arroz y menestra\",\"Patacones\":\"Patacones\",\"ob\":\"arroz con menestra\"}'),
(5223, 1472, 150, 1, 1.99, 0, NULL, 1, NULL),
(5224, 1472, 150, 1, 1.99, 0, NULL, 1, NULL),
(5225, 1472, 65, 1, 1.99, 0, NULL, 1, '{\"Sabor_gaseosa\":\"Fiora Fresa\",\"ob\":\"\"}'),
(5228, 1473, 27, 1, 5.99, 0, NULL, 1, NULL),
(5229, 1473, 64, 1, 0.99, 0, NULL, 1, NULL),
(5230, 1473, 96, 1, 2.00, 0, NULL, 1, NULL),
(5231, 1473, 64, 1, 0.99, 0, NULL, 1, NULL),
(5235, 1474, 121, 1, 3.99, 0, NULL, 1, NULL),
(5236, 1475, 52, 1, 8.99, 0, NULL, 1, '{\"Sabor_alitas\":\"BBQ Picante\",\"ob\":\"en plato aparte\"}'),
(5237, 1475, 65, 1, 1.99, 0, NULL, 1, NULL),
(5238, 1475, 58, 1, 4.99, 0, NULL, 1, NULL),
(5239, 1475, 108, 1, 8.99, 0, NULL, 1, '{\"Arroz\":\"Arroz Moro\",\"Papas_fritas\":\"Papas Fritas\",\"Ensalada\":\"Cesar\",\"ob\":\"\"}'),
(5240, 1475, 27, 1, 5.99, 0, NULL, 1, NULL),
(5241, 1475, 25, 1, 4.99, 0, NULL, 1, '{\"ob\":\"no `piña\"}'),
(5242, 1475, 29, 1, 0.99, 0, NULL, 1, NULL),
(5243, 1475, 62, 1, 1.99, 0, NULL, 1, '{\"Bebida_Fria\":\"Jugo Mora\",\"ob\":\"\"}'),
(5244, 1475, 39, 1, 2.50, 0, NULL, 1, NULL),
(5245, 1475, 27, 1, 5.99, 0, NULL, 1, NULL),
(5246, 1475, 64, 1, 0.99, 0, NULL, 1, NULL),
(5251, 1476, 52, 1, 8.99, 0, NULL, 1, '{\"ob\":\"3 picantes bbq\r\n2 bbq \r\n4 maracuya\"}'),
(5252, 1476, 64, 1, 0.99, 0, NULL, 1, '{\"Sabor_gaseosa\":\"Sprite\",\"ob\":\"\"}'),
(5254, 1477, 51, 1, 5.99, 0, NULL, 1, '{\"Sabor_alitas\":\"BBQ\",\"ob\":\"3 mostaza y miel \"}'),
(5255, 1477, 66, 1, 0.99, 0, NULL, 1, NULL),
(5257, 1478, 28, 1, 3.50, 0, NULL, 1, NULL),
(5258, 1479, 24, 1, 4.99, 0, NULL, 1, NULL),
(5259, 1479, 27, 1, 5.99, 0, NULL, 1, NULL),
(5260, 1479, 46, 1, 3.99, 0, NULL, 1, NULL),
(5261, 1479, 64, 1, 0.99, 0, NULL, 1, NULL),
(5265, 1480, 41, 1, 2.99, 0, NULL, 1, NULL),
(5266, 1480, 27, 1, 5.99, 0, NULL, 1, NULL),
(5267, 1480, 64, 1, 0.99, 0, NULL, 1, NULL),
(5268, 1480, 60, 1, 2.50, 0, NULL, 1, NULL),
(5272, 1481, 24, 1, 4.99, 0, NULL, 1, NULL),
(5273, 1481, 28, 1, 3.50, 0, NULL, 1, NULL),
(5274, 1481, 67, 1, 0.99, 0, NULL, 1, NULL),
(5275, 1481, 67, 1, 0.99, 0, NULL, 1, NULL),
(5276, 1481, 67, 1, 0.99, 0, NULL, 1, NULL),
(5279, 1482, 2, 1, 4.99, 0, NULL, 1, '{\"Huevos\":\"Revuelto normal\",\"Bebida_Caliente\":\"Agua\",\"ob\":\"\"}'),
(5280, 1482, 144, 1, 2.50, 0, NULL, 1, '{\"ob\":\"de queso\"}'),
(5282, 1483, 148, 1, 5.50, 0, NULL, 1, '{\"Bebida_Caliente\":\"Cafe Pasado\",\"Estado_verde\":\"Tigrillo mixto\",\"ob\":\"\"}'),
(5283, 1484, 144, 1, 2.50, 0, NULL, 1, '{\"ob\":\"mixto\"}'),
(5284, 1484, 78, 1, 1.50, 0, NULL, 1, '{\"ob\":\"cafe en agua\"}'),
(5286, 1485, 148, 1, 5.50, 0, NULL, 1, '{\"Bebida_Caliente\":\"Cafe Pasado\",\"Estado_verde\":\"Bolon mixto\",\"ob\":\"\"}'),
(5287, 1486, 1, 1, 3.99, 0, NULL, 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Revuelto normal\",\"ob\":\"\"}'),
(5288, 1487, 144, 1, 2.50, 0, NULL, 1, '{\"ob\":\"mixto\"}'),
(5289, 1487, 74, 1, 1.50, 0, NULL, 1, '{\"ob\":\"cafe en leche \"}'),
(5291, 1488, 148, 1, 5.50, 0, NULL, 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"Huevos\":\"Revuelto normal\",\"Estado_verde\":\"Tigrillo mixto\",\"ob\":\"\"}'),
(5292, 1489, 148, 1, 5.50, 0, NULL, 1, '{\"Bebida_Caliente\":\"Cafe Pasado\",\"Huevos\":\"Revuelto normal\",\"Estado_verde\":\"Tigrillo mixto\",\"ob\":\"\"}'),
(5293, 1489, 2, 1, 4.99, 0, NULL, 1, '{\"Huevos\":\"Revuelto normal\",\"ob\":\"te \"}'),
(5295, 1490, 143, 1, 3.50, 0, NULL, 1, NULL),
(5296, 1490, 143, 1, 3.50, 0, NULL, 1, NULL),
(5297, 1490, 143, 1, 3.50, 0, NULL, 1, NULL),
(5298, 1490, 143, 1, 3.50, 0, NULL, 1, NULL),
(5299, 1490, 33, 1, 0.99, 0, NULL, 1, NULL),
(5300, 1490, 33, 1, 0.99, 0, NULL, 1, NULL),
(5301, 1490, 33, 1, 0.99, 0, NULL, 1, NULL),
(5302, 1490, 33, 1, 0.99, 0, NULL, 1, NULL),
(5303, 1490, 103, 1, 3.00, 0, NULL, 1, NULL),
(5304, 1490, 141, 1, 5.99, 0, NULL, 1, NULL),
(5305, 1490, 74, 1, 1.50, 0, NULL, 1, NULL),
(5306, 1490, 74, 1, 1.50, 0, NULL, 1, NULL),
(5307, 1490, 74, 1, 1.50, 0, NULL, 1, NULL),
(5308, 1490, 74, 1, 1.50, 0, NULL, 1, NULL),
(5309, 1490, 74, 1, 1.50, 0, NULL, 1, NULL),
(5310, 1490, 62, 1, 1.99, 0, NULL, 1, NULL),
(5311, 1490, 33, 1, 0.99, 0, NULL, 1, '{\"ob\":\"huevos revueltos\"}'),
(5312, 1490, 62, 1, 1.99, 0, NULL, 1, NULL),
(5326, 1491, 4, 1, 4.99, 0, NULL, 1, '{\"Bebida_Caliente\":\"Cafe en leche\",\"ob\":\"fruta solo papapya \"}'),
(5327, 1491, 98, 1, 0.99, 0, NULL, 1, '{\"Huevos\":\"Cocinado Duro\",\"ob\":\"\"}'),
(5328, 1491, 4, 1, 4.99, 0, NULL, 1, NULL),
(5329, 1491, 4, 1, 4.99, 0, NULL, 1, NULL),
(5333, 1492, 112, 1, 3.00, 0, NULL, 1, NULL),
(5334, 1492, 1, 1, 3.99, 0, NULL, 1, NULL),
(5336, 1493, 24, 1, 4.99, 0, NULL, 1, NULL),
(5337, 1493, 125, 1, 4.99, 0, NULL, 1, NULL),
(5338, 1493, 64, 1, 0.99, 0, NULL, 1, '{\"Sabor_gaseosa\":\"Coca Cola\",\"ob\":\"\"}'),
(5339, 1493, 66, 1, 0.99, 0, NULL, 1, NULL),
(5343, 1494, 71, 1, 1.99, 0, NULL, 1, NULL),
(5344, 1495, 39, 1, 2.50, 0, NULL, 1, NULL),
(5345, 1495, 25, 1, 4.99, 0, NULL, 1, NULL),
(5347, 1496, 121, 1, 3.99, 0, NULL, 1, '{\"Patacones\":\"Patacones\",\"ob\":\"\"}'),
(5348, 1496, 74, 1, 1.50, 0, NULL, 1, NULL),
(5349, 1496, 139, 1, 2.99, 0, NULL, 1, NULL),
(5350, 1496, 58, 1, 4.99, 0, NULL, 1, NULL),
(5351, 1496, 15, 1, 5.50, 0, NULL, 1, NULL),
(5352, 1496, 71, 1, 1.99, 0, NULL, 1, NULL),
(5354, 1497, 112, 1, 3.00, 0, NULL, 1, NULL),
(5355, 1497, 112, 1, 3.00, 0, NULL, 1, NULL),
(5356, 1497, 1, 1, 3.99, 0, NULL, 1, NULL),
(5357, 1497, 5, 1, 5.99, 0, NULL, 1, NULL),
(5358, 1497, 2, 1, 4.99, 0, NULL, 1, NULL),
(5361, 1498, 81, 1, 3.50, 0, NULL, 1, '{\"ob\":\"CLUB\"}'),
(5362, 1498, 79, 1, 2.50, 0, NULL, 1, '{\"ob\":\"CLUB\"}'),
(5363, 1498, 106, 1, 8.99, 0, NULL, 1, '{\"Arroz\":\"Arroz Moro\",\"Papas_fritas\":\"Papas Fritas\",\"ob\":\"MENESTRA ADICIONAL\"}'),
(5364, 1498, 27, 1, 5.99, 0, NULL, 1, '{\"ob\":\"POLLO EN VEZ DE UNA CARNE\"}'),
(5368, 1499, 131, 1, 3.99, 0, NULL, 1, NULL),
(5369, 1499, 15, 1, 5.50, 0, NULL, 1, NULL),
(5370, 1499, 118, 1, 1.50, 0, NULL, 1, NULL),
(5371, 1499, 81, 1, 3.50, 0, NULL, 1, NULL),
(5372, 1500, 121, 1, 3.99, 0, NULL, 1, NULL),
(5373, 1500, 28, 1, 3.50, 0, NULL, 1, NULL),
(5374, 1500, 69, 1, 1.50, 0, NULL, 1, NULL),
(5375, 1501, 120, 1, 4.99, 0, NULL, 1, NULL),
(5376, 1501, 120, 1, 4.99, 0, NULL, 1, NULL),
(5377, 1501, 120, 1, 4.99, 0, NULL, 1, NULL),
(5378, 1501, 120, 1, 4.99, 0, NULL, 1, NULL),
(5379, 1501, 120, 1, 4.99, 0, NULL, 1, NULL),
(5380, 1501, 62, 1, 1.99, 0, NULL, 1, NULL),
(5381, 1501, 64, 1, 0.99, 0, NULL, 1, NULL),
(5382, 1501, 64, 1, 0.99, 0, NULL, 1, NULL),
(5383, 1501, 66, 1, 0.99, 0, NULL, 1, NULL),
(5384, 1501, 43, 1, 3.99, 0, NULL, 1, NULL),
(5390, 1502, 43, 1, 3.99, 0, NULL, 1, NULL),
(5391, 1502, 44, 1, 5.50, 0, NULL, 1, NULL),
(5392, 1502, 24, 1, 4.99, 0, NULL, 1, NULL),
(5393, 1502, 120, 1, 4.99, 0, NULL, 1, NULL),
(5394, 1502, 62, 1, 1.99, 0, NULL, 1, NULL),
(5395, 1502, 63, 1, 1.99, 0, NULL, 1, NULL),
(5396, 1502, 64, 1, 0.99, 0, NULL, 1, NULL),
(5397, 1502, 62, 1, 1.99, 0, NULL, 1, NULL),
(5398, 1502, 74, 1, 1.50, 0, NULL, 1, NULL),
(5399, 1502, 146, 1, 1.50, 0, NULL, 1, NULL),
(5400, 1502, 150, 1, 1.99, 0, NULL, 1, NULL),
(5401, 1502, 67, 1, 0.99, 0, NULL, 1, NULL),
(5405, 1503, 51, 1, 5.99, 0, NULL, 1, NULL),
(5406, 1503, 119, 1, 7.50, 0, NULL, 1, NULL),
(5407, 1503, 120, 1, 4.99, 0, NULL, 1, NULL),
(5408, 1503, 121, 1, 3.99, 0, NULL, 1, NULL),
(5409, 1503, 121, 1, 3.99, 0, NULL, 1, NULL),
(5410, 1503, 40, 1, 2.99, 0, NULL, 1, NULL),
(5411, 1503, 124, 1, 4.99, 0, NULL, 1, NULL),
(5412, 1503, 65, 1, 1.99, 0, NULL, 1, NULL),
(5413, 1504, 15, 1, 5.50, 0, NULL, 1, NULL),
(5414, 1504, 61, 1, 1.50, 0, NULL, 1, NULL),
(5416, 1505, 123, 1, 4.99, 0, NULL, 1, NULL),
(5417, 1506, 123, 1, 4.99, 0, NULL, 1, NULL),
(5418, 1506, 66, 1, 0.99, 0, NULL, 1, NULL),
(5420, 1507, 66, 1, 0.99, 0, NULL, 1, NULL),
(5421, 1507, 42, 1, 3.99, 0, NULL, 1, NULL),
(5423, 1508, 33, 1, 0.99, 0, NULL, 1, NULL),
(5424, 1508, 71, 1, 1.99, 0, NULL, 1, NULL),
(5425, 1508, 28, 1, 3.50, 0, NULL, 1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_temp`
--

DROP TABLE IF EXISTS `detalle_temp`;
CREATE TABLE IF NOT EXISTS `detalle_temp` (
  `correlativo` int NOT NULL AUTO_INCREMENT,
  `token_user` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `codproducto` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio_venta` decimal(10,2) NOT NULL,
  `mesa` int DEFAULT NULL,
  `preparar` int NOT NULL DEFAULT '1',
  `atributos` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `codatributos` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estatus_atributos` int NOT NULL DEFAULT '2',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`correlativo`)
) ENGINE=InnoDB AUTO_INCREMENT=6521 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_temp`
--

INSERT INTO `detalle_temp` (`correlativo`, `token_user`, `codproducto`, `cantidad`, `precio_venta`, `mesa`, `preparar`, `atributos`, `codatributos`, `estatus_atributos`, `observaciones`) VALUES
(6506, '1cb872fc7d8ac155368c4c1125ef0716', 5, 1, 5.99, 1, 1, NULL, '1,2', 2, NULL),
(6507, '1cb872fc7d8ac155368c4c1125ef0716', 4, 1, 4.99, 1, 1, NULL, '1,2', 2, NULL),
(6510, '1cb872fc7d8ac155368c4c1125ef0716', 2, 1, 4.99, 1, 1, NULL, '3,1,2', 2, NULL),
(6511, '1cb872fc7d8ac155368c4c1125ef0716', 6, 1, 3.99, 1, 1, NULL, '', 2, NULL),
(6512, '1cb872fc7d8ac155368c4c1125ef0716', 7, 1, 3.50, 1, 1, NULL, '', 2, NULL),
(6513, '1cb872fc7d8ac155368c4c1125ef0716', 13, 1, 0.99, 1, 1, NULL, '', 2, NULL),
(6514, '1cb872fc7d8ac155368c4c1125ef0716', 13, 1, 0.99, 1, 1, NULL, '', 2, NULL),
(6515, '1cb872fc7d8ac155368c4c1125ef0716', 13, 1, 0.99, 1, 1, NULL, '', 2, NULL),
(6516, '1cb872fc7d8ac155368c4c1125ef0716', 5, 1, 5.99, 1, 1, NULL, '1,2', 2, NULL),
(6517, '1cb872fc7d8ac155368c4c1125ef0716', 6, 1, 3.99, 1, 1, NULL, '', 2, NULL),
(6518, '1cb872fc7d8ac155368c4c1125ef0716', 7, 1, 3.50, 1, 1, NULL, '', 2, NULL),
(6519, '1cb872fc7d8ac155368c4c1125ef0716', 12, 1, 1.99, 1, 1, NULL, '', 2, NULL),
(6520, '1cb872fc7d8ac155368c4c1125ef0716', 4, 5, 4.99, 1, 1, NULL, '1,2', 2, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_temp_compra`
--

DROP TABLE IF EXISTS `detalle_temp_compra`;
CREATE TABLE IF NOT EXISTS `detalle_temp_compra` (
  `correlativo` int NOT NULL AUTO_INCREMENT,
  `token_user` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `codproducto` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio_venta` decimal(10,2) NOT NULL,
  `comedor` int NOT NULL,
  `fecha` date NOT NULL,
  PRIMARY KEY (`correlativo`),
  KEY `codproducto` (`codproducto`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_temp_compra`
--

INSERT INTO `detalle_temp_compra` (`correlativo`, `token_user`, `codproducto`, `cantidad`, `precio_venta`, `comedor`, `fecha`) VALUES
(1, '94d03b599a7b59c0534835de1cc2be27', 1, 1, 8.00, 2, '2020-08-05'),
(2, '', 1, 1, 8.00, 2, '2020-05-08'),
(3, '94d03b599a7b59c0534835de1cc2be27', 1, 4, 8.00, 2, '2020-05-05'),
(4, '94d03b599a7b59c0534835de1cc2be27', 1, 23, 8.00, 2, '2020-05-05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_temp_credito`
--

DROP TABLE IF EXISTS `detalle_temp_credito`;
CREATE TABLE IF NOT EXISTS `detalle_temp_credito` (
  `correlativo` int NOT NULL AUTO_INCREMENT,
  `token_user` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cantidad_credito` decimal(10,2) NOT NULL,
  PRIMARY KEY (`correlativo`)
) ENGINE=InnoDB AUTO_INCREMENT=141 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_temp_credito`
--

INSERT INTO `detalle_temp_credito` (`correlativo`, `token_user`, `cantidad_credito`) VALUES
(87, '94d03b599a7b59c0534835de1cc2be27', 50.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dia_festivo`
--

DROP TABLE IF EXISTS `dia_festivo`;
CREATE TABLE IF NOT EXISTS `dia_festivo` (
  `id` int NOT NULL,
  `nombre` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha` date NOT NULL,
  `fecha_add` date NOT NULL,
  `user_add` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `dia_festivo`
--

INSERT INTO `dia_festivo` (`id`, `nombre`, `fecha`, `fecha_add`, `user_add`) VALUES
(1, 'Entrega banderin Ala 23', '2022-03-15', '2022-03-15', '1234'),
(2, 'Autos', '2022-03-15', '2022-03-15', '1234');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entradas`
--

DROP TABLE IF EXISTS `entradas`;
CREATE TABLE IF NOT EXISTS `entradas` (
  `correlativo` int NOT NULL AUTO_INCREMENT,
  `codproducto` int NOT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cantidad` int NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `usuario_id` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`correlativo`),
  KEY `codproducto` (`codproducto`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `entradas`
--

INSERT INTO `entradas` (`correlativo`, `codproducto`, `fecha`, `cantidad`, `precio`, `usuario_id`) VALUES
(2, 2, '2020-05-07 19:41:47', 150, 110.00, 1803641420),
(3, 1, '2020-05-09 14:44:04', 0, 1.00, 1234),
(4, 3, '2020-05-10 00:27:53', 0, 110.10, 1234),
(6, 2, '2020-07-31 00:11:24', 100, 100.00, 1234),
(7, 1, '2020-07-31 00:14:05', 100, 1.00, 1234),
(8, 2, '2020-07-31 00:48:18', 1, 10.00, 1234),
(9, 2, '2020-07-31 00:48:51', 10, 10.00, 1234),
(10, 2, '2020-07-31 00:49:37', 10, 10.00, 1234),
(11, 2, '2020-07-31 00:51:54', 10, 10.00, 1234),
(12, 2, '2020-07-31 00:52:24', 10, 10.00, 1234),
(13, 2, '2020-07-31 00:53:22', 10, 10.00, 1234),
(14, 2, '2020-07-31 00:54:16', 10, 100.00, 1234),
(15, 1, '2020-07-31 00:54:39', 10, 1.00, 1234),
(16, 2, '2020-07-31 01:06:32', 100, 100.00, 1234),
(17, 2, '2020-07-31 01:07:56', 10, 100.00, 1234),
(25, 26, '2022-01-16 17:14:15', 0, 0.00, 1234);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `escd`
--

DROP TABLE IF EXISTS `escd`;
CREATE TABLE IF NOT EXISTS `escd` (
  `id` int NOT NULL,
  `escd` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `escd`
--

INSERT INTO `escd` (`id`, `escd`) VALUES
(1, 2313),
(2, 2323);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `especialidad`
--

DROP TABLE IF EXISTS `especialidad`;
CREATE TABLE IF NOT EXISTS `especialidad` (
  `id` int NOT NULL AUTO_INCREMENT,
  `n_especialidad` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `especialidad`
--

INSERT INTO `especialidad` (`id`, `n_especialidad`) VALUES
(1, 'asdasd1'),
(3, 'asdasd'),
(4, 'asdsad');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventoscalendar`
--

DROP TABLE IF EXISTS `eventoscalendar`;
CREATE TABLE IF NOT EXISTS `eventoscalendar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `evento` varchar(250) DEFAULT NULL,
  `color_evento` varchar(20) DEFAULT NULL,
  `fecha_inicio` varchar(20) DEFAULT NULL,
  `fecha_fin` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb3;

--
-- Volcado de datos para la tabla `eventoscalendar`
--

INSERT INTO `eventoscalendar` (`id`, `evento`, `color_evento`, `fecha_inicio`, `fecha_fin`) VALUES
(53, 'Mi Tercera Prueba', 'orange', '2021-07-03', '2021-07-04'),
(66, '8', '#2196F3', '2022-03-29', '2022-03-30'),
(65, '6', '#009688', '2022-03-29', '2022-03-30'),
(64, '3', '#FFC107', '2022-03-29', '2022-03-30'),
(67, '2', '#FF5722', '2022-04-26', '2022-04-27'),
(68, '3', '#2196F3', '2022-04-26', '2022-04-29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `existencias`
--

DROP TABLE IF EXISTS `existencias`;
CREATE TABLE IF NOT EXISTS `existencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codproducto` int NOT NULL,
  `existencia` int NOT NULL,
  `fecha` date NOT NULL,
  `comedor` int NOT NULL,
  `register` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_ingreso` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `existencias`
--

INSERT INTO `existencias` (`id`, `codproducto`, `existencia`, `fecha`, `comedor`, `register`, `fecha_ingreso`) VALUES
(10, 3, 100, '2022-01-16', 2, '1234', '2022-01-16 10:57:35'),
(11, 1, 10, '2022-02-22', 1, '1234', '2022-01-17 01:09:57'),
(12, 3, 20, '2022-03-06', 1, '1234', '2022-03-05 04:40:04'),
(13, 2, 20, '2022-03-05', 1, '1234', '2022-03-05 04:40:15'),
(14, 1, 20, '2022-03-04', 1, '1234', '2022-03-05 04:41:28'),
(15, 2, 20, '2022-03-04', 1, '1234', '2022-03-05 04:41:36'),
(16, 3, 20, '2022-03-04', 1, '1234', '2022-03-05 04:41:48'),
(17, 1, 20, '2022-03-04', 2, '1234', '2022-03-05 04:42:35'),
(18, 2, 20, '2022-03-04', 2, '1234', '2022-03-05 04:42:45'),
(19, 3, 20, '2022-03-04', 2, '1234', '2022-03-05 04:42:54'),
(20, 1, 20, '2022-03-05', 0, '1234', '2022-03-05 10:49:06'),
(21, 2, 10, '2022-03-06', 1, '1234', '2022-03-05 10:50:39'),
(22, 1, 11, '2022-03-06', 1, '1234', '2022-03-05 11:04:28'),
(23, 1, 10, '2022-03-10', 1, '1234', '2022-03-10 06:46:23'),
(24, 2, 10, '2022-03-10', 1, '1234', '2022-03-10 06:46:31'),
(25, 3, 10, '2022-03-10', 1, '1234', '2022-03-10 06:46:59'),
(26, 1, 10, '2022-03-10', 2, '1234', '2022-03-10 06:47:38'),
(27, 2, 10, '2022-03-10', 2, '1234', '2022-03-10 06:48:10'),
(28, 3, 10, '2022-03-10', 2, '1234', '2022-03-10 06:48:18'),
(29, 1, 100, '2022-07-24', 1, '1234', '2022-07-24 09:59:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `factura`
--

DROP TABLE IF EXISTS `factura`;
CREATE TABLE IF NOT EXISTS `factura` (
  `nofactura` bigint NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario` int DEFAULT NULL,
  `codcliente` int DEFAULT NULL,
  `mesa` int NOT NULL,
  `totalfactura` decimal(10,2) DEFAULT NULL,
  `tipopago` int NOT NULL,
  `codigopago` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cupon` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `caja` int DEFAULT NULL,
  `id_cierre` int DEFAULT NULL,
  `estatus` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`nofactura`)
) ENGINE=InnoDB AUTO_INCREMENT=1509 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `factura`
--

INSERT INTO `factura` (`nofactura`, `fecha`, `usuario`, `codcliente`, `mesa`, `totalfactura`, `tipopago`, `codigopago`, `cupon`, `caja`, `id_cierre`, `estatus`) VALUES
(302, '2023-11-04 18:18:21', 1803641420, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 19, 4),
(303, '2023-11-04 18:38:49', 1803641420, 1, 2, 11.49, 1, '1', 'NODESCUENT', 1, 19, 4),
(304, '2023-11-04 18:40:36', 1803641420, 1, 1, 33.44, 1, '1', 'NODESCUENT', 1, 19, 4),
(305, '2023-11-04 18:44:29', 1803641420, 1, 1, 15.96, 1, '1', 'NODESCUENT', 1, 19, 4),
(306, '2023-11-04 18:45:49', 1803641420, 1, 2, 11.97, 1, '1', 'NODESCUENT', 1, 19, 4),
(307, '2023-11-04 18:47:51', 1803641420, 1, 1, 7.49, 1, '1', 'NODESCUENT', 1, 19, 4),
(308, '2023-11-04 18:50:31', 1803641420, 1, 2, 12.48, 1, '1', 'NODESCUENT', 1, 19, 4),
(309, '2023-11-04 19:29:02', 1803641420, 1, 1, 5.50, 1, '1', 'NODESCUENT', 1, 19, 4),
(310, '2023-11-04 19:46:13', 1803641420, 1, 2, 5.50, 1, '1', 'NODESCUENT', 1, 19, 4),
(311, '2023-11-04 19:51:28', 1803641420, 1, 1, 14.99, 1, '1', 'NODESCUENT', 1, 19, 4),
(312, '2023-11-04 19:52:08', 1803641420, 1, 1, 5.50, 1, '1', 'NODESCUENT', 1, 19, 4),
(313, '2023-11-04 20:05:15', 1850108166, 1, 1, 9.97, 1, '1', 'NODESCUENT', 1, 20, 4),
(314, '2023-11-04 20:08:53', 302433214, 1, 1, 9.99, 1, '1', 'NODESCUENT', 1, 21, 4),
(315, '2023-11-04 20:19:28', 1850108166, 1, 1, 207.56, 1, '1', 'NODESCUENT', 1, 22, 4),
(316, '2023-11-04 20:23:32', 1850108166, 1, 2, 13.96, 1, '1', 'NODESCUENT', 1, 22, 4),
(317, '2023-11-04 20:35:33', 1850108166, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 22, 4),
(318, '2023-11-04 20:43:33', 1234, 1, 1, 6.49, 1, '1', 'NODESCUENT', 1, 23, 4),
(319, '2023-11-12 00:49:28', 1234, 1, 1, 14.48, 1, '1', 'NODESCUENT', 2, 25, 4),
(320, '2023-11-12 00:50:29', 1234, 1, 2, 5.48, 1, '1', 'NODESCUENT', 2, 25, 4),
(321, '2023-11-12 00:51:31', 1234, 1, 1, 12.48, 1, '1', 'NODESCUENT', 2, 25, 4),
(322, '2023-11-17 20:01:00', 302433214, 1, 2, 1.25, 1, '1', 'NODESCUENT', 1, 24, 4),
(323, '2023-11-17 20:10:22', 1850108166, 1, 1, 7.97, 1, '1', 'NODESCUENT', 3, 26, 4),
(324, '2023-11-17 20:20:51', 1850108166, 1, 1, 34.97, 1, '1', 'NODESCUENT', 1, 27, 4),
(325, '2023-11-17 20:23:06', 1850108166, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 27, 4),
(326, '2023-12-03 12:37:38', 302433214, 1, 1, 27.94, 1, '1', 'NODESCUENT', 1, 28, 4),
(327, '2023-12-03 12:40:46', 302433214, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 29, 4),
(328, '2023-12-03 13:35:15', 1850108166, 1, 2, 13.97, 1, '1', 'NODESCUENT', 1, 30, 4),
(329, '2024-01-27 19:16:35', 302433214, 1, 2, 6.49, 1, '1', 'NODESCUENT', 3, 31, 4),
(330, '2024-02-03 11:43:16', 1234, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 32, 4),
(331, '2024-02-03 11:55:42', 1234, 1, 1, 11.97, 1, '1', 'NODESCUENT', 1, 32, 4),
(332, '2024-02-03 18:53:01', 302433214, 1, 29, 13.23, 1, '1', 'NODESCUENT', 1, 33, 4),
(333, '2024-02-03 20:19:22', 302433214, 1, 1, 9.49, 1, '1', 'NODESCUENT', 1, 33, 4),
(334, '2024-02-03 20:37:23', 302433214, 1, 38, 10.00, 1, '1', 'NODESCUENT', 1, 33, 4),
(335, '2024-02-03 22:15:41', 302433214, 1, 34, 23.95, 1, '1', 'NODESCUENT', 1, 33, 4),
(336, '2024-02-03 22:50:25', 302433214, 1, 1, 88.11, 1, '1', 'NODESCUENT', 1, 33, 4),
(337, '2024-02-04 00:03:43', 302433214, 1, 1, 12.97, 1, '1', 'NODESCUENT', 1, 33, 4),
(338, '2024-02-04 00:10:41', 302433214, 1, 2, 3.50, 1, '1', 'NODESCUENT', 1, 33, 4),
(339, '2024-02-04 00:10:52', 302433214, 1, 1, 49.17, 1, '1', 'NODESCUENT', 1, 33, 4),
(340, '2024-02-04 12:18:57', 1234, 1, 1, 21.99, 1, '1', 'NODESCUENT', 1, 34, 4),
(341, '2024-02-06 22:20:43', 1234, 1, 1, 6.97, 1, '1', 'NODESCUENT', 1, 35, 4),
(342, '2024-02-07 22:49:18', 302433214, 1, 1, 13.47, 1, '1', 'NODESCUENT', 1, 36, 4),
(343, '2024-02-07 22:59:03', 302433214, 1, 1, 16.46, 1, '1', 'NODESCUENT', 1, 37, 4),
(344, '2024-02-07 23:09:02', 302433214, 1, 27, 13.98, 1, '1', 'NODESCUENT', 1, 39, 4),
(345, '2024-02-07 23:22:04', 302433214, 1, 2, 21.96, 3, '1234', 'NODESCUENT', 1, 40, 4),
(346, '2024-02-07 23:25:04', 302433214, 1, 1, 17.71, 1, '1', 'NODESCUENT', 1, 40, 4),
(347, '2024-02-07 23:30:28', 302433214, 1, 1, 19.47, 1, '1', 'NODESCUENT', 1, 40, 4),
(348, '2024-02-08 20:32:38', 302433214, 1, 27, 22.97, 1, '1', 'NODESCUENT', 1, 41, 4),
(349, '2024-02-08 20:38:15', 1850108166, 1, 29, 37.46, 1, '1', 'NODESCUENT', 1, 42, 4),
(350, '2024-02-08 20:50:35', 302433214, 1, 2, 23.72, 1, '1', 'NODESCUENT', 1, 44, 4),
(351, '2024-02-08 20:52:08', 302433214, 1, 1, 14.48, 3, '', 'NODESCUENT', 1, 44, 4),
(352, '2024-02-09 19:04:00', 302433214, 1, 1, 3.50, 1, '1', 'NODESCUENT', 1, 45, 4),
(353, '2024-02-09 19:07:07', 302433214, 1, 1, 4.99, 1, '1', 'NODESCUENT', 1, 45, 4),
(354, '2024-02-09 19:08:46', 302433214, 1, 1, 8.75, 1, '1', 'NODESCUENT', 1, 45, 4),
(355, '2024-02-09 19:56:31', 302433214, 1, 1, 11.47, 1, '1', 'NODESCUENT', 1, 45, 4),
(356, '2024-02-09 20:22:07', 302433214, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 45, 4),
(357, '2024-02-09 23:12:45', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 45, 4),
(358, '2024-02-10 18:32:43', 302433214, 1, 1, 22.96, 1, '1', 'NODESCUENT', 1, 48, 4),
(359, '2024-02-10 19:55:30', 302433214, 1, 1, 11.48, 1, '1', 'NODESCUENT', 1, 60, 4),
(360, '2024-02-10 19:57:02', 302433214, 1, 1, 13.96, 1, '1', 'NODESCUENT', 1, 60, 4),
(361, '2024-02-10 20:00:36', 302433214, 1, 1, 15.46, 1, '1', 'NODESCUENT', 1, 60, 4),
(362, '2024-02-10 20:09:19', 302433214, 1, 2, 3.98, 1, '1', 'NODESCUENT', 1, 60, 4),
(363, '2024-02-10 20:13:10', 302433214, 1, 2, 18.73, 1, '1', 'NODESCUENT', 1, 60, 4),
(364, '2024-02-10 20:17:39', 302433214, 1, 2, 13.72, 3, '111', 'NODESCUENT', 1, 60, 4),
(365, '2024-02-10 20:18:57', 302433214, 1, 2, 16.00, 1, '1', 'NODESCUENT', 1, 60, 4),
(366, '2024-02-10 20:23:55', 302433214, 1, 1, 35.45, 1, '1', 'NODESCUENT', 1, 60, 4),
(367, '2024-02-10 20:46:19', 302433214, 1, 1, 11.98, 1, '1', 'NODESCUENT', 1, 60, 4),
(368, '2024-02-10 20:54:43', 302433214, 1, 1, 18.93, 1, '1', 'NODESCUENT', 1, 60, 4),
(369, '2024-02-10 21:36:56', 302433214, 1, 2, 25.95, 1, '1', 'NODESCUENT', 1, 60, 4),
(370, '2024-02-10 21:37:27', 302433214, 1, 27, 2.74, 1, '1', 'NODESCUENT', 1, 60, 4),
(371, '2024-02-10 21:48:50', 302433214, 1, 1, 17.72, 1, '1', 'NODESCUENT', 1, 60, 4),
(372, '2024-02-10 21:51:02', 302433214, 1, 2, 5.49, 1, '1', 'NODESCUENT', 1, 60, 4),
(373, '2024-02-10 21:53:46', 302433214, 1, 2, 4.98, 1, '1', 'NODESCUENT', 1, 60, 4),
(374, '2024-02-10 21:57:29', 302433214, 1, 30, 19.46, 1, '1', 'NODESCUENT', 1, 60, 4),
(375, '2024-02-10 22:00:24', 302433214, 1, 1, 10.47, 1, '1', 'NODESCUENT', 1, 60, 4),
(376, '2024-02-10 22:44:57', 302433214, 1, 1, 27.95, 1, '1', 'NODESCUENT', 1, 60, 4),
(377, '2024-02-10 22:50:16', 302433214, 1, 1, 2.00, 1, '1', 'NODESCUENT', 1, 60, 4),
(378, '2024-02-11 00:02:40', 302433214, 1, 1, 24.69, 1, '1', 'NODESCUENT', 1, 60, 4),
(379, '2024-02-11 00:02:50', 302433214, 1, 2, 16.98, 1, '1', 'NODESCUENT', 1, 60, 4),
(380, '2024-02-11 00:03:24', 302433214, 1, 28, 13.96, 1, '1', 'NODESCUENT', 1, 60, 4),
(381, '2024-02-11 00:05:22', 302433214, 1, 1, 11.48, 1, '1', 'NODESCUENT', 1, 60, 4),
(382, '2024-02-11 00:05:48', 302433214, 1, 1, 13.96, 1, '1', 'NODESCUENT', 1, 60, 4),
(383, '2024-02-11 00:10:39', 302433214, 1, 1, 5.00, 3, '1', 'NODESCUENT', 1, 60, 4),
(384, '2024-02-11 00:13:26', 302433214, 1, 1, 8.74, 1, '1', 'NODESCUENT', 1, 60, 4),
(385, '2024-02-11 00:33:59', 302433214, 1, 1, 3.49, 1, '1', 'NODESCUENT', 1, 61, 4),
(386, '2024-02-11 00:36:12', 302433214, 1, 1, 2.50, 1, '1', 'NODESCUENT', 1, 61, 4),
(387, '2024-02-11 00:38:37', 302433214, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 61, 4),
(388, '2024-02-11 01:00:43', 302433214, 1, 1, 5.49, 1, '1', 'NODESCUENT', 1, 61, 4),
(389, '2024-02-11 10:11:55', 302433214, 1, 1, 16.97, 1, '1', 'NODESCUENT', 1, NULL, 1),
(390, '2024-02-11 10:12:42', 302433214, 1, 1, 9.98, 1, '1', 'NODESCUENT', 1, NULL, 1),
(391, '2024-02-11 10:13:17', 302433214, 1, 1, 11.98, 1, '1', 'NODESCUENT', 1, NULL, 1),
(392, '2024-02-11 10:15:11', 302433214, 1, 1, 11.98, 1, '1', 'NODESCUENT', 1, NULL, 1),
(393, '2024-02-11 10:16:00', 302433214, 1, 1, 9.98, 1, '1', 'NODESCUENT', 1, NULL, 1),
(394, '2024-02-11 10:22:14', 302433214, 1, 1, 12.97, 1, '1', 'NODESCUENT', 1, NULL, 1),
(395, '2024-02-11 10:24:42', 302433214, 1, 1, 11.98, 1, '1', 'NODESCUENT', 1, NULL, 1),
(396, '2024-02-11 10:25:03', 302433214, 1, 1, 11.98, 1, '1', 'NODESCUENT', 1, NULL, 1),
(397, '2024-02-11 10:26:28', 302433214, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, NULL, 1),
(398, '2024-02-11 10:41:55', 302433214, 1, 1, 39.96, 1, '1', 'NODESCUENT', 1, 64, 4),
(399, '2024-02-11 11:27:45', 302433214, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 64, 4),
(400, '2024-02-11 11:47:04', 302433214, 1, 1, 7.98, 1, '1', 'NODESCUENT', 1, 64, 4),
(401, '2024-02-11 16:32:43', 302433214, 1, 1, 9.98, 1, '1', 'NODESCUENT', 1, 64, 4),
(402, '2024-02-11 16:33:00', 302433214, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 64, 4),
(403, '2024-02-11 17:58:54', 302433214, 1, 1, 32.45, 1, '1', 'NODESCUENT', 1, 64, 4),
(404, '2024-02-11 18:00:40', 302433214, 1, 1, 16.47, 1, '1', 'NODESCUENT', 1, 64, 4),
(405, '2024-02-11 18:51:04', 302433214, 1, 1, 10.73, 1, '1', 'NODESCUENT', 1, 66, 4),
(406, '2024-02-11 18:56:47', 302433214, 1, 1, 18.46, 1, '1', 'NODESCUENT', 1, 66, 4),
(407, '2024-02-11 19:03:35', 302433214, 1, 1, 21.97, 1, '1', 'NODESCUENT', 1, 66, 4),
(408, '2024-02-11 19:10:28', 302433214, 1, 1, 5.75, 1, '1', 'NODESCUENT', 1, 66, 4),
(409, '2024-02-11 19:13:26', 302433214, 1, 1, 5.98, 1, '1', 'NODESCUENT', 1, 66, 4),
(410, '2024-02-11 19:18:36', 302433214, 1, 1, 1.75, 1, '1', 'NODESCUENT', 1, 66, 4),
(411, '2024-02-11 19:22:55', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 66, 4),
(412, '2024-02-11 19:30:20', 302433214, 1, 1, 8.99, 1, '1', 'NODESCUENT', 1, 66, 4),
(413, '2024-02-11 19:32:15', 302433214, 1, 1, 8.50, 1, '1', 'NODESCUENT', 1, 66, 4),
(414, '2024-02-11 19:43:46', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 66, 4),
(415, '2024-02-11 20:02:10', 302433214, 1, 1, 6.49, 1, '1', 'NODESCUENT', 1, 66, 4),
(416, '2024-02-11 20:31:29', 302433214, 1, 1, 10.49, 1, '1', 'NODESCUENT', 1, 66, 4),
(417, '2024-02-11 20:36:09', 302433214, 1, 1, 39.72, 1, '1', 'NODESCUENT', 1, 66, 4),
(418, '2024-02-11 20:41:14', 302433214, 1, 1, 47.69, 3, '', 'NODESCUENT', 1, 66, 4),
(419, '2024-02-11 20:43:14', 302433214, 1, 1, 1.50, 1, '1', 'NODESCUENT', 1, 66, 4),
(420, '2024-02-11 20:46:01', 302433214, 1, 1, 1.50, 1, '1', 'NODESCUENT', 1, 66, 4),
(421, '2024-02-11 20:51:02', 302433214, 1, 1, 2.50, 1, '1', 'NODESCUENT', 1, 66, 4),
(422, '2024-02-11 21:09:32', 302433214, 1, 1, 1.75, 1, '1', 'NODESCUENT', 1, 66, 4),
(423, '2024-02-11 21:12:22', 302433214, 1, 1, 1.75, 1, '1', 'NODESCUENT', 1, 66, 4),
(424, '2024-02-11 21:22:27', 302433214, 1, 1, 0.25, 1, '1', 'NODESCUENT', 1, 66, 4),
(425, '2024-02-11 21:43:52', 302433214, 1, 1, 6.98, 1, '1', 'NODESCUENT', 1, 66, 4),
(426, '2024-02-11 21:45:24', 302433214, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 66, 4),
(427, '2024-02-11 21:46:35', 302433214, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 66, 4),
(428, '2024-02-11 21:49:47', 302433214, 1, 1, 15.97, 1, '1', 'NODESCUENT', 1, 66, 4),
(429, '2024-02-11 22:10:18', 302433214, 1, 1, 22.23, 3, '', 'NODESCUENT', 1, 66, 4),
(430, '2024-02-11 22:12:31', 302433214, 1, 1, 10.50, 1, '1', 'NODESCUENT', 1, 66, 4),
(431, '2024-02-11 22:15:15', 302433214, 1, 1, 2.99, 1, '1', 'NODESCUENT', 1, 66, 4),
(432, '2024-02-11 22:16:47', 302433214, 1, 1, 3.50, 1, '1', 'NODESCUENT', 1, 66, 4),
(433, '2024-02-11 22:22:48', 302433214, 1, 1, 8.98, 1, '1', 'NODESCUENT', 1, 66, 4),
(434, '2024-02-11 22:25:12', 302433214, 1, 1, 3.99, 3, '', 'NODESCUENT', 1, 66, 4),
(435, '2024-02-11 22:26:16', 302433214, 1, 1, 3.50, 1, '1', 'NODESCUENT', 1, 66, 4),
(436, '2024-02-11 22:30:27', 302433214, 1, 1, 5.98, 1, '1', 'NODESCUENT', 1, 66, 4),
(437, '2024-02-11 22:36:52', 302433214, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 66, 4),
(438, '2024-02-11 22:38:10', 302433214, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 66, 4),
(439, '2024-02-11 22:47:11', 302433214, 1, 1, 13.96, 1, '1', 'NODESCUENT', 1, 66, 4),
(440, '2024-02-11 22:51:31', 302433214, 1, 1, 39.67, 1, '1', 'NODESCUENT', 1, 66, 4),
(441, '2024-02-11 22:54:05', 302433214, 1, 1, 11.96, 1, '1', 'NODESCUENT', 1, 66, 4),
(442, '2024-02-11 22:56:47', 302433214, 1, 1, 7.00, 1, '1', 'NODESCUENT', 1, 66, 4),
(443, '2024-02-11 22:59:17', 302433214, 1, 1, 1.98, 1, '1', 'NODESCUENT', 1, 66, 4),
(444, '2024-02-11 23:05:43', 302433214, 1, 1, 11.48, 1, '1', 'NODESCUENT', 1, 66, 4),
(445, '2024-02-11 23:10:34', 302433214, 1, 1, 11.19, 1, '1', 'NODESCUENT', 1, 66, 4),
(446, '2024-02-11 23:12:41', 302433214, 1, 1, 12.99, 1, '1', 'NODESCUENT', 1, 66, 4),
(447, '2024-02-11 23:13:37', 302433214, 1, 1, 3.49, 1, '1', 'NODESCUENT', 1, 66, 4),
(448, '2024-02-11 23:25:26', 302433214, 1, 1, 48.42, 3, '', 'NODESCUENT', 1, 66, 4),
(449, '2024-02-11 23:28:54', 302433214, 1, 1, 7.00, 1, '1', 'NODESCUENT', 1, 66, 4),
(450, '2024-02-11 23:30:42', 302433214, 1, 1, 7.25, 1, '1', 'NODESCUENT', 1, 66, 4),
(451, '2024-02-11 23:45:06', 302433214, 1, 1, 15.94, 1, '1', 'NODESCUENT', 1, 66, 4),
(452, '2024-02-11 23:47:11', 302433214, 1, 1, 14.94, 1, '1', 'NODESCUENT', 1, 66, 4),
(453, '2024-02-11 23:58:08', 302433214, 1, 1, 8.99, 1, '1', 'NODESCUENT', 1, 66, 4),
(454, '2024-02-11 23:58:40', 302433214, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 66, 4),
(455, '2024-02-12 00:02:47', 302433214, 1, 1, 8.48, 3, '', 'NODESCUENT', 1, 66, 4),
(456, '2024-02-12 00:05:15', 302433214, 1, 1, 12.96, 3, '', 'NODESCUENT', 1, 66, 4),
(457, '2024-02-12 00:09:38', 302433214, 1, 1, 3.50, 1, '1', 'NODESCUENT', 1, 66, 4),
(458, '2024-02-12 00:13:34', 302433214, 1, 1, 17.22, 1, '1', 'NODESCUENT', 1, 66, 4),
(459, '2024-02-12 00:17:34', 302433214, 1, 1, 19.46, 1, '1', 'NODESCUENT', 1, 66, 4),
(460, '2024-02-12 00:23:29', 302433214, 1, 1, 9.96, 1, '1', 'NODESCUENT', 1, 66, 4),
(461, '2024-02-12 00:26:40', 302433214, 1, 1, 4.49, 1, '1', 'NODESCUENT', 1, 66, 4),
(462, '2024-02-12 00:28:17', 302433214, 1, 1, 4.98, 1, '1', 'NODESCUENT', 1, 66, 4),
(463, '2024-02-12 00:30:33', 302433214, 1, 1, 10.98, 3, '', 'NODESCUENT', 1, 66, 4),
(464, '2024-02-12 00:33:42', 302433214, 1, 1, 6.97, 1, '1', 'NODESCUENT', 1, 66, 4),
(465, '2024-02-12 00:37:26', 302433214, 1, 1, 15.71, 1, '1', 'NODESCUENT', 1, 66, 4),
(466, '2024-02-12 00:43:49', 302433214, 1, 1, 2.50, 1, '1', 'NODESCUENT', 1, 66, 4),
(467, '2024-02-12 00:54:44', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 66, 4),
(468, '2024-02-12 00:57:20', 302433214, 1, 1, 4.99, 1, '1', 'NODESCUENT', 1, 66, 4),
(469, '2024-02-12 16:34:09', 302433214, 1, 1, 91.36, 1, '1', 'NODESCUENT', 1, 67, 4),
(470, '2024-02-12 16:46:20', 302433214, 1, 2, 18.98, 3, '', 'NODESCUENT', 1, 68, 4),
(471, '2024-02-12 17:39:37', 302433214, 1, 1, 4.24, 1, '1', 'NODESCUENT', 1, 68, 4),
(472, '2024-02-12 18:00:27', 302433214, 1, 1, 1.25, 1, '1', 'NODESCUENT', 1, 69, 4),
(473, '2024-02-12 18:51:37', 302433214, 1, 1, 20.98, 1, '1', 'NODESCUENT', 1, 69, 4),
(474, '2024-02-12 18:52:40', 302433214, 1, 1, 1.00, 1, '1', 'NODESCUENT', 1, 69, 4),
(475, '2024-02-12 19:00:09', 302433214, 1, 1, 12.73, 1, '1', 'NODESCUENT', 1, 69, 4),
(476, '2024-02-12 19:02:07', 302433214, 1, 1, 3.50, 1, '1', 'NODESCUENT', 1, 69, 4),
(477, '2024-02-12 19:47:10', 302433214, 1, 1, 1.75, 1, '1', 'NODESCUENT', 1, 69, 4),
(478, '2024-02-12 21:23:53', 302433214, 1, 1, 11.97, 1, '1', 'NODESCUENT', 1, 69, 4),
(479, '2024-02-12 21:25:11', 302433214, 1, 1, 4.98, 1, '1', 'NODESCUENT', 1, 69, 4),
(480, '2024-02-12 21:42:57', 302433214, 1, 1, 6.75, 1, '1', 'NODESCUENT', 1, 69, 4),
(481, '2024-02-12 21:45:36', 302433214, 1, 1, 20.72, 1, '1', 'NODESCUENT', 1, 69, 4),
(482, '2024-02-12 21:50:26', 302433214, 1, 1, 10.24, 1, '1', 'NODESCUENT', 1, 69, 4),
(483, '2024-02-12 21:53:11', 302433214, 1, 1, 13.48, 3, '', 'NODESCUENT', 1, 69, 4),
(484, '2024-02-12 21:54:28', 302433214, 1, 1, 1.98, 1, '1', 'NODESCUENT', 1, 69, 4),
(485, '2024-02-12 22:06:32', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 69, 4),
(486, '2024-02-12 22:10:52', 302433214, 1, 1, 16.99, 1, '1', 'NODESCUENT', 1, 69, 4),
(487, '2024-02-12 22:52:11', 302433214, 1, 1, 22.21, 1, '1', 'NODESCUENT', 1, 69, 4),
(488, '2024-02-12 22:54:38', 302433214, 1, 1, 21.71, 1, '1', 'NODESCUENT', 1, 69, 4),
(489, '2024-02-12 22:56:32', 302433214, 1, 1, 1.98, 1, '1', 'NODESCUENT', 1, 69, 4),
(490, '2024-02-12 23:03:40', 302433214, 1, 1, 1.50, 1, '1', 'NODESCUENT', 1, 69, 4),
(491, '2024-02-12 23:19:38', 302433214, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 69, 4),
(492, '2024-02-12 23:20:51', 302433214, 1, 1, 4.49, 1, '1', 'NODESCUENT', 1, 69, 4),
(493, '2024-02-12 23:47:56', 302433214, 1, 1, 2.50, 1, '1', 'NODESCUENT', 1, 69, 4),
(494, '2024-02-12 23:59:49', 302433214, 1, 1, 3.50, 1, '1', 'NODESCUENT', 1, 69, 4),
(495, '2024-02-13 00:00:16', 302433214, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 69, 4),
(496, '2024-02-13 00:02:25', 302433214, 1, 1, 7.98, 1, '1', 'NODESCUENT', 1, 69, 4),
(497, '2024-02-13 00:03:19', 302433214, 1, 1, 6.98, 3, '', 'NODESCUENT', 1, 69, 4),
(498, '2024-02-13 00:07:24', 302433214, 1, 1, 11.24, 3, '', 'NODESCUENT', 1, 69, 4),
(499, '2024-02-13 00:10:41', 302433214, 1, 1, 20.23, 1, '1', 'NODESCUENT', 1, 69, 4),
(500, '2024-02-13 00:14:15', 302433214, 1, 1, 19.23, 1, '1', 'NODESCUENT', 1, 69, 4),
(501, '2024-02-13 00:29:30', 302433214, 1, 1, 4.99, 1, '1', 'NODESCUENT', 1, 69, 4),
(502, '2024-02-13 00:58:38', 302433214, 1, 1, 1.98, 1, '1', 'NODESCUENT', 1, 70, 4),
(503, '2024-02-13 19:23:17', 302433214, 1, 1, 18.97, 1, '1', 'NODESCUENT', 1, 71, 4),
(504, '2024-02-13 19:30:11', 302433214, 1, 1, 4.99, 1, '1', 'NODESCUENT', 1, 71, 4),
(505, '2024-02-13 19:41:15', 302433214, 1, 1, 10.46, 1, '1', 'NODESCUENT', 1, 71, 4),
(506, '2024-02-13 20:08:05', 302433214, 1, 1, 2.99, 1, '1', 'NODESCUENT', 1, 71, 4),
(507, '2024-02-13 20:15:04', 302433214, 1, 1, 9.00, 1, '1', 'NODESCUENT', 1, 71, 4),
(508, '2024-02-13 20:19:42', 302433214, 1, 1, 19.72, 1, '1', 'NODESCUENT', 1, 71, 4),
(509, '2024-02-13 20:23:35', 302433214, 1, 1, 4.73, 1, '1', 'NODESCUENT', 1, 71, 4),
(510, '2024-02-13 20:24:37', 302433214, 1, 1, 4.73, 1, '1', 'NODESCUENT', 1, 71, 4),
(511, '2024-02-13 20:28:07', 302433214, 1, 1, 4.73, 1, '1', 'NODESCUENT', 1, 71, 4),
(512, '2024-02-13 20:50:38', 302433214, 1, 1, 1.50, 1, '1', 'NODESCUENT', 1, 71, 4),
(513, '2024-02-15 18:09:34', 302433214, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 72, 4),
(514, '2024-02-15 18:10:35', 302433214, 1, 1, 13.97, 1, '1', 'NODESCUENT', 1, 72, 4),
(515, '2024-02-15 18:23:54', 302433214, 1, 1, 4.98, 1, '1', 'NODESCUENT', 1, 73, 4),
(516, '2024-02-15 21:34:22', 302433214, 1, 1, 13.96, 1, '1', 'NODESCUENT', 1, 73, 4),
(517, '2024-02-15 21:40:07', 302433214, 1, 1, 3.50, 1, '1', 'NODESCUENT', 1, 73, 4),
(518, '2024-02-15 21:43:45', 302433214, 1, 1, 6.00, 1, '1', 'NODESCUENT', 1, 73, 4),
(519, '2024-02-16 18:58:42', 302433214, 1, 1, 1.99, 1, '1', 'NODESCUENT', 1, 74, 4),
(520, '2024-02-16 20:12:23', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 74, 4),
(521, '2024-02-16 20:17:50', 302433214, 1, 2, 4.98, 1, '1', 'NODESCUENT', 1, 74, 4),
(522, '2024-02-16 20:27:20', 302433214, 1, 1, 1.98, 1, '1', 'NODESCUENT', 1, 74, 4),
(523, '2024-02-16 20:41:41', 302433214, 1, 1, 4.49, 1, '1', 'NODESCUENT', 1, 74, 4),
(524, '2024-02-16 21:14:01', 302433214, 1, 1, 6.49, 1, '1', 'NODESCUENT', 1, 74, 4),
(525, '2024-02-16 22:16:27', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 74, 4),
(526, '2024-02-16 22:21:30', 302433214, 1, 1, 17.72, 1, '1', 'NODESCUENT', 1, 74, 4),
(527, '2024-02-16 22:41:20', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 74, 4),
(528, '2024-02-16 23:54:34', 302433214, 1, 1, 1.99, 1, '1', 'NODESCUENT', 1, 74, 4),
(529, '2024-02-17 18:08:27', 302433214, 1, 2, 6.75, 1, '1', 'NODESCUENT', 1, 75, 4),
(530, '2024-02-17 18:11:34', 302433214, 1, 1, 20.47, 1, '1', 'NODESCUENT', 1, 75, 4),
(531, '2024-02-17 18:49:55', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 75, 4),
(532, '2024-02-17 20:12:13', 302433214, 1, 1, 5.98, 1, '1', 'NODESCUENT', 1, 75, 4),
(533, '2024-02-17 20:30:05', 302433214, 1, 1, 11.97, 1, '1', 'NODESCUENT', 1, 75, 4),
(534, '2024-02-17 20:31:18', 302433214, 1, 1, 4.98, 1, '1', 'NODESCUENT', 1, 75, 4),
(535, '2024-02-17 20:33:10', 302433214, 1, 1, 15.23, 1, '1', 'NODESCUENT', 1, 75, 4),
(536, '2024-02-17 20:36:32', 302433214, 1, 1, 4.99, 1, '1', 'NODESCUENT', 1, 75, 4),
(537, '2024-02-17 20:47:24', 302433214, 1, 1, 3.75, 1, '1', 'NODESCUENT', 1, 75, 4),
(538, '2024-02-17 21:27:44', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 75, 4),
(539, '2024-02-17 22:09:08', 302433214, 1, 1, 12.98, 1, '1', 'NODESCUENT', 1, 75, 4),
(540, '2024-02-17 22:40:52', 302433214, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 75, 4),
(541, '2024-02-17 22:41:25', 302433214, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 75, 4),
(542, '2024-02-17 23:42:46', 302433214, 1, 1, 4.49, 1, '1', 'NODESCUENT', 1, 75, 4),
(543, '2024-02-17 23:45:59', 302433214, 1, 1, 4.49, 1, '1', 'NODESCUENT', 1, 75, 4),
(544, '2024-02-17 23:59:07', 302433214, 1, 1, 4.98, 1, '1', 'NODESCUENT', 1, 75, 4),
(545, '2024-02-18 00:03:54', 302433214, 1, 1, 4.98, 1, '1', 'NODESCUENT', 1, 75, 4),
(546, '2024-02-18 00:05:48', 302433214, 1, 1, 13.98, 1, '1', 'NODESCUENT', 1, 75, 4),
(547, '2024-02-18 20:07:37', 302433214, 1, 1, 17.46, 1, '1', 'NODESCUENT', 1, 76, 4),
(548, '2024-02-18 20:46:15', 302433214, 1, 1, 15.75, 1, '1', 'NODESCUENT', 1, 76, 4),
(549, '2024-02-18 21:28:56', 302433214, 1, 1, 11.97, 1, '1', 'NODESCUENT', 1, 76, 4),
(550, '2024-02-18 22:35:58', 302433214, 1, 1, 8.48, 1, '1', 'NODESCUENT', 1, 76, 4),
(551, '2024-02-21 20:13:00', 1850108166, 1, 2, 9.96, 1, '1', 'NODESCUENT', 1, 78, 4),
(552, '2024-02-21 20:58:01', 1850108166, 1, 2, 18.98, 1, '1', 'NODESCUENT', 1, 78, 4),
(553, '2024-02-21 22:08:01', 1850108166, 1, 1, 2.99, 1, '1', 'NODESCUENT', 1, 78, 4),
(554, '2024-02-22 19:17:37', 1850108166, 1, 1, 2.50, 1, '1', 'NODESCUENT', 1, 79, 4),
(555, '2024-02-22 20:26:04', 1850108166, 1, 2, 11.48, 1, '1', 'NODESCUENT', 1, 79, 4),
(556, '2024-02-22 21:41:33', 1850108166, 1, 1, 7.00, 1, '1', 'NODESCUENT', 1, 79, 4),
(557, '2024-02-23 21:17:15', 302433214, 1, 1, 17.99, 1, '1', 'NODESCUENT', 1, 80, 4),
(558, '2024-02-24 20:22:59', 302433214, 1, 1, 12.48, 1, '1', 'NODESCUENT', 1, 81, 4),
(559, '2024-02-24 20:27:23', 302433214, 1, 1, 5.50, 3, '', 'NODESCUENT', 1, 82, 4),
(560, '2024-02-24 20:29:09', 302433214, 1, 1, 12.48, 3, '', 'NODESCUENT', 1, 82, 4),
(561, '2024-02-24 20:36:03', 302433214, 1, 1, 6.49, 1, '1', 'NODESCUENT', 1, 82, 4),
(562, '2024-02-24 20:47:52', 302433214, 1, 1, 20.96, 1, '1', 'NODESCUENT', 1, 82, 4),
(563, '2024-02-25 19:00:04', 302433214, 1, 1, 15.99, 1, '1', 'NODESCUENT', 1, 84, 4),
(564, '2024-02-25 19:07:02', 302433214, 1, 1, 3.98, 1, '1', 'NODESCUENT', 1, 84, 4),
(565, '2024-02-25 19:35:50', 302433214, 1, 1, 6.98, 3, '', 'NODESCUENT', 1, 84, 4),
(566, '2024-02-25 19:51:09', 302433214, 1, 1, 7.73, 3, '', 'NODESCUENT', 1, 84, 4),
(567, '2024-02-25 19:53:19', 302433214, 1, 1, 12.46, 3, '', 'NODESCUENT', 1, 84, 4),
(568, '2024-02-25 19:56:59', 302433214, 1, 1, 17.46, 1, '1', 'NODESCUENT', 1, 84, 4),
(569, '2024-02-25 22:17:08', 302433214, 1, 1, 15.96, 1, '1', 'NODESCUENT', 1, 84, 4),
(570, '2024-02-28 20:29:20', 1850108166, 1, 1, 4.24, 1, '1', 'NODESCUENT', 1, 85, 4),
(571, '2024-02-28 22:46:51', 1850108166, 1, 2, 19.48, 1, '1', 'NODESCUENT', 1, 85, 4),
(572, '2024-02-28 22:52:16', 1850108166, 1, 2, 16.96, 1, '1', 'NODESCUENT', 1, 85, 4),
(573, '2024-02-28 23:16:34', 1850108166, 1, 27, 9.49, 1, '1', 'NODESCUENT', 1, 85, 4),
(574, '2024-02-28 23:40:26', 1850108166, 1, 28, 4.47, 1, '1', 'NODESCUENT', 1, 85, 4),
(575, '2024-02-29 20:41:08', 302433214, 1, 1, 4.24, 1, '1', 'NODESCUENT', 1, 86, 4),
(576, '2024-02-29 21:21:07', 302433214, 1, 1, 2.99, 1, '1', 'NODESCUENT', 1, 86, 4),
(577, '2024-02-29 21:54:13', 302433214, 1, 1, 3.50, 1, '1', 'NODESCUENT', 1, 86, 4),
(578, '2024-03-01 20:17:52', 302433214, 1, 1, 13.96, 3, '', 'NODESCUENT', 1, 87, 4),
(579, '2024-03-01 20:21:56', 302433214, 1, 1, 24.71, 3, '', 'NODESCUENT', 1, 87, 4),
(580, '2024-03-01 20:46:25', 302433214, 1, 1, 14.49, 3, '', 'NODESCUENT', 1, 87, 4),
(581, '2024-03-01 20:58:48', 302433214, 1, 1, 6.24, 1, '1', 'NODESCUENT', 1, 87, 4),
(582, '2024-03-01 21:50:42', 302433214, 1, 1, 4.75, 1, '1', 'NODESCUENT', 1, 87, 4),
(583, '2024-03-02 21:01:08', 302433214, 1, 1, 4.98, 1, '1', 'NODESCUENT', 1, 88, 4),
(584, '2024-03-02 21:02:41', 302433214, 1, 1, 4.49, 3, '', 'NODESCUENT', 1, 88, 4),
(585, '2024-03-02 21:03:52', 302433214, 1, 1, 5.99, 3, '', 'NODESCUENT', 1, 88, 4),
(586, '2024-03-02 21:04:50', 302433214, 1, 1, 4.99, 1, '1', 'NODESCUENT', 1, 88, 4),
(587, '2024-03-02 21:19:02', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 88, 4),
(588, '2024-03-02 22:06:51', 302433214, 1, 1, 15.75, 1, '1', 'NODESCUENT', 1, 88, 4),
(589, '2024-03-02 23:27:35', 302433214, 1, 1, 2.50, 1, '1', 'NODESCUENT', 1, 88, 4),
(590, '2024-03-03 19:21:52', 302433214, 1, 1, 6.49, 1, '1', 'NODESCUENT', 1, 89, 4),
(591, '2024-03-03 20:01:28', 302433214, 1, 1, 4.49, 1, '1', 'NODESCUENT', 1, 89, 4),
(592, '2024-03-03 20:59:23', 302433214, 1, 1, 40.95, 1, '1', 'NODESCUENT', 1, 89, 4),
(593, '2024-03-03 21:25:47', 302433214, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 89, 4),
(594, '2024-03-06 20:37:56', 302433214, 1, 1, 9.47, 1, '1', 'NODESCUENT', 1, 90, 4),
(595, '2024-03-06 20:45:10', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 90, 4),
(596, '2024-03-06 20:55:50', 302433214, 1, 1, 7.49, 1, '1', 'NODESCUENT', 1, 90, 4),
(597, '2024-03-06 21:24:20', 302433214, 1, 1, 3.24, 1, '1', 'NODESCUENT', 1, 90, 4),
(598, '2024-03-06 22:07:39', 302433214, 1, 1, 4.49, 1, '1', 'NODESCUENT', 1, 90, 4),
(599, '2024-03-06 22:28:18', 302433214, 1, 1, 7.00, 1, '1', 'NODESCUENT', 1, 90, 4),
(600, '2024-03-06 22:31:07', 302433214, 1, 1, 4.49, 1, '1', 'NODESCUENT', 1, 90, 4),
(601, '2024-03-06 22:37:38', 302433214, 1, 1, 10.48, 1, '1', 'NODESCUENT', 1, 90, 4),
(602, '2024-03-06 22:55:15', 302433214, 1, 1, 5.49, 1, '1', 'NODESCUENT', 1, 90, 4),
(603, '2024-03-06 22:58:18', 302433214, 1, 1, 4.49, 1, '1', 'NODESCUENT', 1, 90, 4),
(604, '2024-03-06 22:58:52', 302433214, 1, 1, 2.99, 1, '1', 'NODESCUENT', 1, 90, 4),
(605, '2024-03-06 22:59:49', 302433214, 1, 1, 4.49, 1, '1', 'NODESCUENT', 1, 90, 4),
(606, '2024-03-06 23:03:22', 302433214, 1, 1, 4.49, 1, '1', 'NODESCUENT', 1, 90, 4),
(607, '2024-03-06 23:10:39', 302433214, 1, 1, 2.99, 1, '1', 'NODESCUENT', 1, 90, 4),
(608, '2024-03-06 23:29:56', 302433214, 1, 1, 7.98, 1, '1', 'NODESCUENT', 1, 90, 4),
(609, '2024-03-06 23:32:03', 302433214, 1, 1, 1.98, 1, '1', 'NODESCUENT', 1, 90, 4),
(610, '2024-03-06 23:35:21', 302433214, 1, 1, 5.98, 1, '1', 'NODESCUENT', 1, 90, 4),
(611, '2024-03-06 23:43:34', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 90, 4),
(612, '2024-03-07 20:21:17', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 94, 4),
(613, '2024-03-07 20:32:19', 302433214, 1, 1, 2.99, 1, '1', 'NODESCUENT', 1, 94, 4),
(614, '2024-03-07 20:40:14', 302433214, 1, 1, 3.49, 1, '1', 'NODESCUENT', 1, 94, 4),
(615, '2024-03-07 21:05:15', 302433214, 1, 1, 2.99, 1, '1', 'NODESCUENT', 1, 94, 4),
(616, '2024-03-07 22:07:27', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 94, 4),
(617, '2024-03-08 19:36:34', 302433214, 1, 1, 14.24, 1, '1', 'NODESCUENT', 1, 96, 4),
(618, '2024-03-08 19:54:22', 302433214, 1, 1, 9.98, 3, '', 'NODESCUENT', 1, 96, 4),
(619, '2024-03-08 19:56:43', 302433214, 1, 1, 3.49, 1, '1', 'NODESCUENT', 1, 96, 4),
(620, '2024-03-08 20:08:21', 302433214, 1, 1, 13.98, 1, '1', 'NODESCUENT', 1, 96, 4),
(621, '2024-03-08 20:15:51', 302433214, 1, 1, 6.48, 1, '1', 'NODESCUENT', 1, 96, 4),
(622, '2024-03-08 20:48:57', 302433214, 1, 1, 6.98, 1, '1', 'NODESCUENT', 1, 96, 4),
(623, '2024-03-08 21:23:58', 302433214, 1, 1, 3.49, 1, '1', 'NODESCUENT', 1, 96, 4),
(624, '2024-03-08 21:42:28', 302433214, 1, 1, 26.98, 1, '1', 'NODESCUENT', 1, 96, 4),
(625, '2024-03-08 22:30:07', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 96, 4),
(626, '2024-03-08 22:57:15', 302433214, 1, 1, 1.50, 1, '1', 'NODESCUENT', 1, 96, 4),
(627, '2024-03-08 23:18:55', 302433214, 1, 1, 5.48, 1, '1', 'NODESCUENT', 1, 96, 4),
(628, '2024-03-08 23:30:50', 302433214, 1, 1, 6.50, 1, '1', 'NODESCUENT', 1, 96, 4),
(629, '2024-03-08 23:35:53', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 96, 4),
(630, '2024-03-08 23:43:22', 302433214, 1, 1, 3.50, 1, '1', 'NODESCUENT', 1, 96, 4),
(631, '2024-03-09 18:22:07', 302433214, 1, 1, 10.50, 1, '1', 'NODESCUENT', 1, 97, 4),
(632, '2024-03-09 20:03:26', 302433214, 1, 1, 9.98, 1, '1', 'NODESCUENT', 1, 97, 4),
(633, '2024-03-09 20:05:22', 302433214, 1, 1, 3.50, 1, '1', 'NODESCUENT', 1, 97, 4),
(634, '2024-03-09 20:22:17', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 97, 4),
(635, '2024-03-09 20:48:27', 302433214, 1, 1, 12.48, 1, '1', 'NODESCUENT', 1, 97, 4),
(636, '2024-03-09 21:58:05', 302433214, 1, 1, 18.46, 1, '1', 'NODESCUENT', 1, 97, 4),
(637, '2024-03-09 22:31:47', 302433214, 1, 1, 6.98, 1, '1', 'NODESCUENT', 1, 97, 4),
(638, '2024-03-09 22:33:20', 302433214, 1, 1, 9.98, 1, '1', 'NODESCUENT', 1, 97, 4),
(639, '2024-03-09 22:35:24', 302433214, 1, 1, 13.48, 1, '1', 'NODESCUENT', 1, 97, 4),
(640, '2024-03-09 22:40:10', 302433214, 1, 1, 6.49, 1, '1', 'NODESCUENT', 1, 97, 4),
(641, '2024-03-09 23:03:13', 302433214, 1, 1, 1.50, 1, '1', 'NODESCUENT', 1, 97, 4),
(642, '2024-03-09 23:16:06', 302433214, 1, 2, 3.50, 1, '1', 'NODESCUENT', 1, 97, 4),
(643, '2024-03-09 23:49:08', 302433214, 1, 1, 11.48, 1, '1', 'NODESCUENT', 1, 97, 4),
(644, '2024-03-10 21:32:06', 302433214, 1, 1, 6.49, 1, '1', 'NODESCUENT', 1, 98, 4),
(645, '2024-03-10 21:52:58', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 98, 4),
(646, '2024-03-10 22:04:36', 302433214, 1, 1, 17.98, 3, '', 'NODESCUENT', 1, 98, 4),
(647, '2024-03-13 20:03:09', 302433214, 1, 1, 19.96, 1, '1', 'NODESCUENT', 1, 99, 4),
(648, '2024-03-13 20:08:06', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 99, 4),
(649, '2024-03-13 20:47:35', 302433214, 1, 1, 23.44, 1, '1', 'NODESCUENT', 1, 99, 4),
(650, '2024-03-13 21:18:12', 302433214, 1, 1, 1.50, 1, '1', 'NODESCUENT', 1, 99, 4),
(651, '2024-03-14 21:26:52', 302433214, 1, 1, 3.74, 1, '1', 'NODESCUENT', 1, 100, 4),
(652, '2024-03-14 22:26:44', 302433214, 1, 1, 11.47, 1, '1', 'NODESCUENT', 1, 100, 4),
(653, '2024-03-14 22:31:18', 302433214, 1, 1, 19.70, 1, '1', 'NODESCUENT', 1, 100, 4),
(654, '2024-03-14 22:34:39', 302433214, 1, 1, 22.45, 1, '1', 'NODESCUENT', 1, 100, 4),
(655, '2024-03-15 20:06:54', 302433214, 1, 1, 4.99, 1, '1', 'NODESCUENT', 1, 101, 4),
(656, '2024-03-15 20:17:32', 302433214, 1, 1, 10.98, 1, '1', 'NODESCUENT', 1, 101, 4),
(657, '2024-03-15 21:10:20', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 101, 4),
(658, '2024-03-15 21:41:36', 302433214, 1, 1, 2.99, 1, '1', 'NODESCUENT', 1, 101, 4),
(659, '2024-03-15 22:22:31', 302433214, 1, 1, 5.49, 3, '', 'NODESCUENT', 1, 101, 4),
(660, '2024-03-15 22:44:14', 302433214, 1, 1, 7.47, 1, '1', 'NODESCUENT', 1, 101, 4),
(661, '2024-03-15 22:45:57', 302433214, 1, 1, 2.50, 1, '1', 'NODESCUENT', 1, 101, 4),
(662, '2024-03-15 22:47:11', 302433214, 1, 1, 3.49, 3, '', 'NODESCUENT', 1, 101, 4),
(663, '2024-03-15 23:04:56', 302433214, 1, 2, 5.50, 1, '1', 'NODESCUENT', 1, 101, 4),
(664, '2024-03-15 23:11:58', 302433214, 1, 27, 12.98, 1, '1', 'NODESCUENT', 1, 101, 4),
(665, '2024-03-15 23:23:30', 302433214, 1, 1, 16.97, 3, '', 'NODESCUENT', 1, 101, 4),
(666, '2024-03-15 23:50:19', 302433214, 1, 1, 10.99, 1, '1', 'NODESCUENT', 1, 101, 4),
(667, '2024-03-16 00:09:24', 302433214, 1, 1, 23.96, 3, '', 'NODESCUENT', 1, 102, 4),
(668, '2024-03-16 00:22:59', 302433214, 1, 1, 1.50, 1, '1', 'NODESCUENT', 1, 102, 4),
(669, '2024-03-16 00:32:05', 302433214, 1, 1, 8.49, 1, '1', 'NODESCUENT', 1, 102, 4),
(670, '2024-03-16 01:18:54', 302433214, 1, 1, 6.50, 1, '1', 'NODESCUENT', 1, 102, 4),
(671, '2024-03-17 19:38:15', 302433214, 1, 1, 42.43, 1, '1', 'NODESCUENT', 1, 103, 4),
(672, '2024-03-17 19:41:07', 302433214, 1, 1, 13.24, 1, '1', 'NODESCUENT', 1, 103, 4),
(673, '2024-03-17 19:47:53', 302433214, 1, 1, 4.75, 1, '1', 'NODESCUENT', 1, 103, 4),
(674, '2024-03-17 20:04:09', 302433214, 1, 1, 4.98, 1, '1', 'NODESCUENT', 1, 103, 4),
(675, '2024-03-17 20:51:53', 302433214, 1, 1, 5.49, 1, '1', 'NODESCUENT', 1, 103, 4),
(676, '2024-03-17 21:22:34', 302433214, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 103, 4),
(677, '2024-03-17 21:28:45', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 103, 4),
(678, '2024-03-17 21:37:36', 302433214, 1, 1, 21.24, 1, '1', 'NODESCUENT', 1, 103, 4),
(679, '2024-03-17 21:42:22', 302433214, 1, 1, 20.96, 1, '1', 'NODESCUENT', 1, 103, 4),
(680, '2024-03-17 21:47:25', 302433214, 1, 1, 11.47, 1, '1', 'NODESCUENT', 1, 103, 4),
(681, '2024-03-17 22:01:56', 302433214, 1, 1, 7.00, 1, '1', 'NODESCUENT', 1, 103, 4),
(682, '2024-03-17 22:15:18', 302433214, 1, 1, 2.00, 1, '1', 'NODESCUENT', 1, 103, 4),
(683, '2024-03-18 13:14:46', 1234, 1, 1, 23.96, 1, '1', 'NODESCUENT', 1, 104, 4),
(684, '2024-03-18 13:33:20', 1234, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 104, 4),
(685, '2024-03-18 13:35:07', 1234, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 104, 4),
(686, '2024-03-18 13:35:42', 1234, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 104, 4),
(687, '2024-03-18 13:37:07', 1234, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 104, 4),
(688, '2024-03-18 13:38:08', 1234, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 104, 4),
(689, '2024-03-18 13:38:24', 1234, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 104, 4),
(690, '2024-03-18 13:39:15', 1234, 1, 2, 5.99, 1, '1', 'NODESCUENT', 1, 104, 4),
(691, '2024-03-18 13:40:11', 1234, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 104, 4),
(692, '2024-03-18 14:13:26', 1234, 1, 1, 8.98, 1, '1', 'NODESCUENT', 1, 104, 4),
(693, '2024-03-18 14:14:40', 1234, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 104, 4),
(694, '2024-03-18 14:17:25', 1234, 1, 1, 4.99, 1, '1', 'NODESCUENT', 1, 104, 4),
(695, '2024-03-20 09:47:40', 1850108166, 1, 1, 24.82, 3, '', 'NODESCUENT', 1, 105, 4),
(696, '2024-03-20 20:16:22', 1850108166, 1, 1, 20.95, 3, '', 'NODESCUENT', 1, 105, 4),
(697, '2024-03-20 20:18:02', 1850108166, 1, 1, 10.72, 1, '1', 'NODESCUENT', 1, 105, 4),
(698, '2024-03-20 21:06:33', 1850108166, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 105, 4),
(699, '2024-03-21 00:01:07', 1850108166, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 105, 4),
(700, '2024-03-21 09:49:15', 1850108166, 1, 1, 13.98, 1, '1', 'NODESCUENT', 1, 106, 4),
(701, '2024-03-21 21:28:46', 1850108166, 1, 1, 4.49, 1, '1', 'NODESCUENT', 1, 108, 4),
(702, '2024-03-21 21:30:32', 1850108166, 1, 1, 5.48, 1, '1', 'NODESCUENT', 1, 108, 4),
(703, '2024-03-22 09:12:50', 1850108166, 1, 2, 21.46, 1, '1', 'NODESCUENT', 1, 108, 4),
(704, '2024-03-22 09:13:51', 1850108166, 1, 1, 20.65, 1, '1', 'NODESCUENT', 1, 108, 4),
(705, '2024-03-22 09:15:55', 1850108166, 1, 1, 10.99, 1, '1', 'NODESCUENT', 1, 108, 4),
(706, '2024-03-22 09:18:16', 1850108166, 1, 1, 8.49, 1, '1', 'NODESCUENT', 1, 108, 4),
(707, '2024-03-22 10:06:40', 1850108166, 1, 1, 11.00, 1, '1', 'NODESCUENT', 1, 108, 4),
(708, '2024-03-22 10:10:45', 1850108166, 1, 1, 11.48, 1, '1', 'NODESCUENT', 1, 108, 4),
(709, '2024-03-22 10:30:41', 1850108166, 1, 1, 10.48, 3, '', 'NODESCUENT', 1, 108, 4),
(710, '2024-03-22 11:23:13', 1850108166, 1, 1, 21.46, 1, '1', 'NODESCUENT', 1, 108, 4),
(711, '2024-03-23 09:46:24', 1234, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 112, 4),
(712, '2024-03-23 09:57:21', 302433214, 1, 1, 2.20, 1, '1', 'NODESCUENT', 1, 113, 4),
(713, '2024-03-23 10:08:19', 302433214, 1, 1, 16.96, 1, '1', 'NODESCUENT', 1, 113, 4),
(714, '2024-03-23 10:22:04', 302433214, 1, 1, 11.00, 1, '1', 'NODESCUENT', 1, 113, 4),
(715, '2024-03-23 11:07:35', 302433214, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 113, 4),
(716, '2024-03-23 15:58:38', 302433214, 1, 1, 12.73, 1, '1', 'NODESCUENT', 1, 114, 4),
(717, '2024-03-23 16:50:08', 302433214, 1, 1, 2.50, 1, '1', 'NODESCUENT', 1, 114, 4),
(718, '2024-03-23 18:11:11', 302433214, 1, 1, 5.49, 1, '1', 'NODESCUENT', 1, 115, 4),
(719, '2024-03-23 18:14:38', 302433214, 1, 1, 8.75, 1, '1', 'NODESCUENT', 1, 115, 4),
(720, '2024-03-23 18:15:27', 302433214, 1, 1, 2.99, 1, '1', 'NODESCUENT', 1, 115, 4),
(721, '2024-03-23 19:16:45', 302433214, 1, 1, 10.48, 3, '', 'NODESCUENT', 1, 115, 4),
(722, '2024-03-23 19:47:44', 302433214, 1, 1, 4.99, 1, '1', 'NODESCUENT', 1, 115, 4),
(723, '2024-03-23 19:52:58', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 115, 4),
(724, '2024-03-23 19:56:12', 302433214, 1, 1, 5.98, 1, '1', 'NODESCUENT', 1, 115, 4),
(725, '2024-03-23 19:58:07', 302433214, 1, 1, 6.98, 1, '1', 'NODESCUENT', 1, 115, 4),
(726, '2024-03-23 19:59:52', 302433214, 1, 1, 6.98, 1, '1', 'NODESCUENT', 1, 115, 4),
(727, '2024-03-23 20:00:18', 302433214, 1, 1, 4.99, 1, '1', 'NODESCUENT', 1, 115, 4),
(728, '2024-03-23 20:01:09', 302433214, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 115, 4),
(729, '2024-03-23 20:02:58', 302433214, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 115, 4),
(730, '2024-03-23 20:03:55', 302433214, 1, 1, 9.98, 1, '1', 'NODESCUENT', 1, 115, 4),
(731, '2024-03-23 20:09:58', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 115, 4),
(732, '2024-03-23 20:28:20', 302433214, 1, 1, 6.98, 1, '1', 'NODESCUENT', 1, 115, 4),
(733, '2024-03-23 20:29:51', 302433214, 1, 1, 12.96, 1, '1', 'NODESCUENT', 1, 115, 4),
(734, '2024-03-23 20:38:13', 302433214, 1, 1, 9.98, 1, '1', 'NODESCUENT', 1, 115, 4),
(735, '2024-03-23 20:44:26', 302433214, 1, 1, 42.66, 3, '', 'NODESCUENT', 1, 115, 4),
(736, '2024-03-23 20:45:41', 302433214, 1, 1, 5.50, 1, '1', 'NODESCUENT', 1, 115, 4),
(737, '2024-03-23 20:58:04', 302433214, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 115, 4),
(738, '2024-03-23 21:09:35', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 115, 4),
(739, '2024-03-23 21:25:14', 302433214, 1, 1, 5.99, 3, '', 'NODESCUENT', 1, 115, 4),
(740, '2024-03-23 21:32:31', 302433214, 1, 1, 4.99, 1, '1', 'NODESCUENT', 1, 115, 4),
(741, '2024-03-23 21:46:00', 302433214, 1, 1, 20.96, 3, '', 'NODESCUENT', 1, 115, 4),
(742, '2024-03-23 22:02:01', 302433214, 1, 1, 4.99, 1, '1', 'NODESCUENT', 1, 115, 4),
(743, '2024-03-23 22:03:18', 302433214, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 115, 4),
(744, '2024-03-23 22:09:41', 302433214, 1, 1, 2.00, 1, '1', 'NODESCUENT', 1, 115, 4),
(745, '2024-03-23 22:52:52', 302433214, 1, 1, 14.00, 1, '1', 'NODESCUENT', 1, 115, 4),
(746, '2024-03-23 22:58:03', 302433214, 1, 1, 1.75, 1, '1', 'NODESCUENT', 1, 115, 4),
(747, '2024-03-23 23:25:30', 302433214, 1, 1, 4.49, 1, '1', 'NODESCUENT', 1, 115, 4),
(748, '2024-03-23 23:45:11', 302433214, 1, 1, 11.97, 1, '1', 'NODESCUENT', 1, 115, 4),
(749, '2024-03-24 00:16:00', 302433214, 1, 1, 8.99, 1, '1', 'NODESCUENT', 1, 116, 4),
(750, '2024-03-24 08:27:08', 302433214, 1, 1, 6.49, 3, '', 'NODESCUENT', 1, 117, 4),
(751, '2024-03-24 08:54:11', 302433214, 1, 1, 4.99, 1, '1', 'NODESCUENT', 1, 117, 4),
(752, '2024-03-24 09:34:23', 302433214, 1, 1, 14.97, 1, '1', 'NODESCUENT', 1, 117, 4),
(753, '2024-03-24 20:54:41', 302433214, 1, 1, 13.97, 1, '1', 'NODESCUENT', 1, 118, 4),
(754, '2024-03-24 20:56:01', 302433214, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 118, 4),
(755, '2024-03-24 21:15:18', 302433214, 1, 1, 12.97, 1, '1', 'NODESCUENT', 1, 118, 4),
(756, '2024-03-24 22:14:15', 302433214, 1, 1, 25.48, 3, '', 'NODESCUENT', 1, 118, 4),
(757, '2024-03-24 22:31:36', 302433214, 1, 1, 6.49, 1, '1', 'NODESCUENT', 1, 118, 4),
(758, '2024-03-27 18:40:28', 1850108166, 1, 1, 9.96, 1, '1', 'NODESCUENT', 1, 119, 4),
(759, '2024-03-27 19:29:33', 1850108166, 1, 1, 18.21, 3, '', 'NODESCUENT', 1, 119, 4),
(760, '2024-03-27 19:30:54', 1850108166, 1, 2, 19.97, 1, '1', 'NODESCUENT', 1, 119, 4),
(761, '2024-03-27 19:34:20', 1850108166, 1, 1, 14.47, 1, '1', 'NODESCUENT', 1, 119, 4),
(762, '2024-03-27 20:15:27', 1850108166, 1, 1, 3.49, 1, '1', 'NODESCUENT', 1, 119, 4),
(763, '2024-03-27 20:19:22', 1850108166, 1, 2, 6.98, 3, '', 'NODESCUENT', 1, 119, 4),
(764, '2024-03-27 20:20:20', 1850108166, 1, 1, 6.49, 1, '1', 'NODESCUENT', 1, 119, 4),
(765, '2024-03-27 20:21:55', 1850108166, 1, 1, 2.00, 1, '1', 'NODESCUENT', 1, 119, 4),
(766, '2024-03-28 08:18:32', 1850108166, 1, 1, 5.50, 1, '1', 'NODESCUENT', 1, 120, 4),
(767, '2024-03-28 22:12:31', 1850108166, 1, 1, 8.99, 1, '1', 'NODESCUENT', 1, 120, 4),
(768, '2024-03-29 09:34:50', 1850108166, 1, 1, 21.49, 3, '', 'NODESCUENT', 1, 121, 4),
(769, '2024-03-29 18:36:53', 302433214, 1, 1, 3.00, 1, '1', 'NODESCUENT', 1, 122, 4),
(770, '2024-03-29 18:53:34', 302433214, 1, 1, 30.23, 1, '1', 'NODESCUENT', 1, 122, 4),
(771, '2024-03-29 19:24:50', 302433214, 1, 1, 9.73, 1, '1', 'NODESCUENT', 1, 122, 4),
(772, '2024-03-29 19:26:00', 302433214, 1, 1, 9.97, 1, '1', 'NODESCUENT', 1, 122, 4),
(773, '2024-03-29 19:26:50', 302433214, 1, 1, 1.99, 1, '1', 'NODESCUENT', 1, 122, 4),
(774, '2024-03-29 20:05:10', 302433214, 1, 2, 1.50, 1, '1', 'NODESCUENT', 1, 122, 4),
(775, '2024-03-29 20:47:38', 302433214, 1, 1, 7.49, 1, '1', 'NODESCUENT', 1, 122, 4),
(776, '2024-03-29 21:05:14', 302433214, 1, 1, 12.97, 1, '1', 'NODESCUENT', 1, 122, 4),
(777, '2024-03-29 21:07:42', 302433214, 1, 1, 7.50, 1, '1', 'NODESCUENT', 1, 122, 4),
(778, '2024-03-29 21:16:04', 302433214, 1, 1, 11.96, 1, '1', 'NODESCUENT', 1, 122, 4),
(779, '2024-03-29 21:49:35', 302433214, 1, 1, 27.74, 1, '1', 'NODESCUENT', 1, 122, 4),
(780, '2024-03-29 22:24:05', 302433214, 1, 1, 21.97, 1, '1', 'NODESCUENT', 1, 122, 4),
(781, '2024-03-29 22:42:27', 302433214, 1, 1, 13.99, 3, '', 'NODESCUENT', 1, 122, 4),
(782, '2024-03-29 22:44:13', 302433214, 1, 1, 3.00, 1, '1', 'NODESCUENT', 1, 122, 4),
(783, '2024-03-29 22:46:33', 302433214, 1, 1, 7.98, 1, '1', 'NODESCUENT', 1, 122, 4),
(784, '2024-03-29 23:30:13', 302433214, 1, 1, 3.00, 1, '1', 'NODESCUENT', 1, 122, 4),
(785, '2024-03-30 08:17:10', 302433214, 1, 1, 6.49, 1, '1', 'NODESCUENT', 1, 123, 4),
(786, '2024-03-30 08:59:23', 302433214, 1, 1, 22.46, 1, '1', 'NODESCUENT', 1, 123, 4),
(787, '2024-03-30 18:28:26', 302433214, 1, 1, 15.48, 3, '', 'NODESCUENT', 1, 123, 4),
(788, '2024-03-30 18:43:47', 302433214, 1, 1, 13.97, 1, '1', 'NODESCUENT', 1, 123, 4),
(789, '2024-03-30 18:44:31', 302433214, 1, 1, 2.00, 1, '1', 'NODESCUENT', 1, 123, 4),
(790, '2024-03-30 20:12:59', 302433214, 1, 1, 6.98, 1, '1', 'NODESCUENT', 1, 123, 4),
(791, '2024-03-30 20:14:46', 302433214, 1, 1, 6.98, 1, '1', 'NODESCUENT', 1, 123, 4),
(792, '2024-03-30 20:38:01', 302433214, 1, 1, 13.96, 1, '1', 'NODESCUENT', 1, 123, 4),
(793, '2024-03-30 20:49:57', 302433214, 1, 1, 5.48, 1, '1', 'NODESCUENT', 1, 123, 4),
(794, '2024-03-30 20:53:40', 302433214, 1, 1, 7.98, 3, '', 'NODESCUENT', 1, 123, 4),
(795, '2024-03-30 21:04:43', 302433214, 1, 1, 8.50, 3, '', 'NODESCUENT', 1, 123, 4),
(796, '2024-03-30 21:09:54', 302433214, 1, 1, 13.49, 3, '', 'NODESCUENT', 1, 123, 4),
(797, '2024-03-30 21:36:13', 302433214, 1, 1, 11.49, 3, '', 'NODESCUENT', 1, 123, 4),
(798, '2024-03-30 21:43:06', 302433214, 1, 1, 3.73, 1, '1', 'NODESCUENT', 1, 123, 4),
(799, '2024-03-30 21:48:30', 302433214, 1, 1, 15.47, 1, '1', 'NODESCUENT', 1, 123, 4),
(800, '2024-03-30 21:52:30', 302433214, 1, 1, 11.46, 1, '1', 'NODESCUENT', 1, 123, 4),
(801, '2024-03-30 22:28:25', 302433214, 1, 1, 2.00, 1, '1', 'NODESCUENT', 1, 123, 4),
(802, '2024-03-30 22:40:53', 302433214, 1, 1, 9.97, 1, '1', 'NODESCUENT', 1, 123, 4),
(803, '2024-03-30 23:04:03', 302433214, 1, 1, 9.98, 1, '1', 'NODESCUENT', 1, 123, 4),
(804, '2024-03-30 23:07:19', 302433214, 1, 1, 2.50, 1, '1', 'NODESCUENT', 1, 123, 4),
(805, '2024-03-30 23:20:35', 302433214, 1, 1, 1.99, 1, '1', 'NODESCUENT', 1, 123, 4),
(806, '2024-03-31 01:10:30', 302433214, 1, 1, 9.75, 1, '1', 'NODESCUENT', 1, 124, 4),
(807, '2024-03-31 07:42:04', 302433214, 1, 1, 10.49, 1, '1', 'NODESCUENT', 1, 126, 4),
(808, '2024-03-31 08:09:33', 302433214, 1, 1, 7.00, 1, '1', 'NODESCUENT', 1, 126, 4),
(809, '2024-03-31 08:11:18', 302433214, 1, 1, 9.98, 1, '1', 'NODESCUENT', 1, 126, 4),
(810, '2024-03-31 09:00:53', 302433214, 1, 1, 5.98, 1, '1', 'NODESCUENT', 1, 126, 4),
(811, '2024-03-31 09:01:56', 302433214, 1, 1, 7.48, 1, '1', 'NODESCUENT', 1, 126, 4),
(812, '2024-03-31 09:02:31', 302433214, 1, 1, 5.98, 1, '1', 'NODESCUENT', 1, 126, 4),
(813, '2024-03-31 09:26:25', 302433214, 1, 1, 5.98, 1, '1', 'NODESCUENT', 1, 126, 4),
(814, '2024-03-31 09:42:20', 302433214, 1, 1, 0.04, 1, '1', 'NODESCUENT', 1, 126, 4),
(815, '2024-03-31 19:40:03', 302433214, 1, 1, 2.50, 1, '1', 'NODESCUENT', 1, 127, 4),
(816, '2024-03-31 20:20:55', 302433214, 1, 1, 12.98, 1, '1', 'NODESCUENT', 1, 127, 4),
(817, '2024-03-31 22:15:30', 302433214, 1, 1, 4.99, 1, '1', 'NODESCUENT', 1, 127, 4),
(818, '2024-04-03 18:32:02', 1850108166, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 128, 4),
(819, '2024-04-03 20:52:06', 1850108166, 1, 1, 16.46, 1, '1', 'NODESCUENT', 1, 128, 4),
(820, '2024-04-03 20:54:03', 1850108166, 1, 2, 12.46, 1, '1', 'NODESCUENT', 1, 128, 4),
(821, '2024-04-03 20:57:07', 1850108166, 1, 1, 20.97, 1, '1', 'NODESCUENT', 1, 128, 4),
(822, '2024-04-04 19:37:51', 1850108166, 1, 1, 16.95, 1, '1', 'NODESCUENT', 1, 129, 4),
(823, '2024-04-04 20:29:52', 1850108166, 1, 1, 11.74, 1, '1', 'NODESCUENT', 1, 129, 4),
(824, '2024-04-04 20:53:19', 1850108166, 1, 1, 1.75, 1, '1', 'NODESCUENT', 1, 129, 4),
(825, '2024-04-04 22:27:57', 1850108166, 1, 1, 9.75, 1, '1', 'NODESCUENT', 1, 129, 4),
(826, '2024-04-04 22:36:01', 1850108166, 1, 1, 13.99, 3, '', 'NODESCUENT', 1, 129, 4),
(827, '2024-04-05 18:40:58', 302433214, 1, 1, 3.00, 1, '1', 'NODESCUENT', 1, 130, 4),
(828, '2024-04-05 22:20:03', 302433214, 1, 2, 1.99, 1, '1', 'NODESCUENT', 1, 130, 4),
(829, '2024-04-05 22:56:13', 302433214, 1, 1, 21.21, 1, '1', 'NODESCUENT', 1, 130, 4),
(830, '2024-04-05 23:01:06', 302433214, 1, 2, 3.50, 1, '1', 'NODESCUENT', 1, 130, 4),
(831, '2024-04-05 23:32:47', 302433214, 1, 1, 14.99, 1, '1', 'NODESCUENT', 1, 130, 4),
(832, '2024-04-06 07:49:44', 302433214, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 131, 4),
(833, '2024-04-06 08:20:14', 302433214, 1, 1, 10.49, 1, '1', 'NODESCUENT', 1, 131, 4),
(834, '2024-04-06 08:29:19', 302433214, 1, 1, 6.00, 1, '1', 'NODESCUENT', 1, 131, 4),
(835, '2024-04-06 08:47:37', 302433214, 1, 1, 18.98, 1, '1', 'NODESCUENT', 1, 131, 4),
(836, '2024-04-06 09:10:11', 302433214, 1, 1, 21.94, 1, '1', 'NODESCUENT', 1, 131, 4),
(837, '2024-04-06 09:27:39', 302433214, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 131, 4),
(838, '2024-04-06 09:40:51', 302433214, 1, 1, 1.50, 1, '1', 'NODESCUENT', 1, 131, 4),
(839, '2024-04-06 09:46:55', 302433214, 1, 1, 11.00, 1, '1', 'NODESCUENT', 1, 131, 4),
(840, '2024-04-06 09:48:56', 302433214, 1, 2, 0.99, 1, '1', 'NODESCUENT', 1, 131, 4),
(841, '2024-04-06 10:38:30', 302433214, 1, 1, 15.00, 1, '1', 'NODESCUENT', 1, 131, 4),
(842, '2024-04-06 11:01:35', 302433214, 1, 1, 5.50, 1, '1', 'NODESCUENT', 1, 131, 4),
(843, '2024-04-06 18:08:42', 302433214, 1, 1, 19.71, 1, '1', 'NODESCUENT', 1, 132, 4),
(844, '2024-04-06 18:12:08', 302433214, 1, 1, 14.48, 1, '1', 'NODESCUENT', 1, 132, 4),
(845, '2024-04-06 19:28:57', 302433214, 1, 1, 10.48, 1, '1', 'NODESCUENT', 1, 132, 4),
(846, '2024-04-06 19:43:40', 302433214, 1, 1, 10.49, 1, '1', 'NODESCUENT', 1, 132, 4),
(847, '2024-04-06 19:47:52', 302433214, 1, 27, 13.48, 1, '1', 'NODESCUENT', 1, 132, 4),
(848, '2024-04-06 19:50:05', 302433214, 1, 1, 12.48, 1, '1', 'NODESCUENT', 1, 132, 4),
(849, '2024-04-06 19:51:43', 302433214, 1, 1, 9.98, 1, '1', 'NODESCUENT', 1, 132, 4),
(850, '2024-04-06 19:53:49', 302433214, 1, 1, 19.96, 1, '1', 'NODESCUENT', 1, 132, 4),
(851, '2024-04-06 20:31:24', 302433214, 1, 1, 4.49, 1, '1', 'NODESCUENT', 1, 132, 4),
(852, '2024-04-06 20:45:25', 302433214, 1, 1, 14.98, 1, '1', 'NODESCUENT', 1, 132, 4),
(853, '2024-04-06 20:54:09', 302433214, 1, 1, 2.00, 1, '1', 'NODESCUENT', 1, 132, 4),
(854, '2024-04-06 21:00:44', 302433214, 1, 1, 20.48, 1, '1', 'NODESCUENT', 1, 132, 4),
(855, '2024-04-06 21:11:09', 302433214, 1, 1, 4.49, 1, '1', 'NODESCUENT', 1, 132, 4),
(856, '2024-04-06 21:17:59', 302433214, 1, 1, 5.49, 1, '1', 'NODESCUENT', 1, 132, 4),
(857, '2024-04-06 21:48:19', 302433214, 1, 1, 22.72, 1, '1', 'NODESCUENT', 1, 132, 4),
(858, '2024-04-06 21:57:20', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 132, 4),
(859, '2024-04-07 08:41:38', 302433214, 1, 1, 4.75, 1, '1', 'NODESCUENT', 1, 133, 4),
(860, '2024-04-07 08:42:20', 302433214, 1, 1, 1.75, 1, '1', 'NODESCUENT', 1, 133, 4),
(861, '2024-04-07 09:13:57', 302433214, 1, 1, 27.50, 1, '1', 'NODESCUENT', 1, 133, 4),
(862, '2024-04-07 09:28:01', 302433214, 1, 1, 6.00, 1, '1', 'NODESCUENT', 1, 133, 4),
(863, '2024-04-07 18:13:46', 302433214, 1, 1, 4.98, 1, '1', 'NODESCUENT', 1, 133, 4),
(864, '2024-04-07 20:22:49', 302433214, 1, 1, 9.48, 1, '1', 'NODESCUENT', 1, 133, 4),
(865, '2024-04-07 20:47:53', 302433214, 1, 1, 3.50, 1, '1', 'NODESCUENT', 1, 133, 4),
(866, '2024-04-07 22:38:11', 302433214, 1, 1, 10.47, 1, '1', 'NODESCUENT', 1, 133, 4),
(867, '2024-04-10 19:07:36', 1850108166, 1, 1, 9.98, 1, '1', 'NODESCUENT', 1, 134, 4),
(868, '2024-04-10 19:37:12', 1850108166, 1, 1, 1.75, 1, '1', 'NODESCUENT', 1, 134, 4),
(869, '2024-04-10 20:56:23', 1850108166, 1, 1, 3.00, 1, '1', 'NODESCUENT', 1, 134, 4),
(870, '2024-04-10 21:13:39', 1850108166, 1, 2, 10.49, 1, '1', 'NODESCUENT', 1, 134, 4),
(871, '2024-04-10 21:23:47', 1850108166, 1, 1, 24.96, 1, '1', 'NODESCUENT', 1, 134, 4),
(872, '2024-04-10 21:59:47', 1850108166, 1, 1, 8.99, 1, '1', 'NODESCUENT', 1, 134, 4),
(873, '2024-04-10 22:12:30', 1850108166, 1, 1, 2.00, 1, '1', 'NODESCUENT', 1, 134, 4),
(874, '2024-04-11 21:15:12', 302433214, 1, 2, 12.98, 1, '1', 'NODESCUENT', 1, 135, 4),
(875, '2024-04-11 21:28:55', 302433214, 1, 1, 4.50, 1, '1', 'NODESCUENT', 1, 135, 4),
(876, '2024-04-12 08:56:17', 302433214, 1, 1, 4.34, 1, '1', 'NODESCUENT', 1, 136, 4),
(877, '2024-04-12 10:26:16', 302433214, 1, 1, 6.00, 3, '', 'NODESCUENT', 1, 136, 4),
(878, '2024-04-12 10:27:03', 302433214, 1, 1, 15.00, 1, '1', 'NODESCUENT', 1, 136, 4),
(879, '2024-04-12 10:27:34', 302433214, 1, 1, 11.00, 1, '1', 'NODESCUENT', 1, 136, 4),
(880, '2024-04-12 19:06:34', 302433214, 1, 1, 6.98, 1, '1', 'NODESCUENT', 1, 136, 4),
(881, '2024-04-12 19:11:41', 302433214, 1, 1, 2.00, 1, '1', 'NODESCUENT', 1, 136, 4),
(882, '2024-04-12 20:05:54', 302433214, 1, 1, 15.47, 1, '1', 'NODESCUENT', 1, 136, 4),
(883, '2024-04-12 20:06:47', 302433214, 1, 1, 2.99, 1, '1', 'NODESCUENT', 1, 136, 4),
(884, '2024-04-12 20:32:32', 302433214, 1, 1, 5.48, 1, '1', 'NODESCUENT', 1, 136, 4),
(885, '2024-04-12 20:42:50', 302433214, 1, 1, 7.00, 1, '1', 'NODESCUENT', 1, 136, 4),
(886, '2024-04-12 20:47:26', 302433214, 1, 1, 6.98, 1, '1', 'NODESCUENT', 1, 136, 4),
(887, '2024-04-12 21:21:06', 302433214, 1, 1, 2.99, 1, '1', 'NODESCUENT', 1, 136, 4),
(888, '2024-04-12 21:28:22', 302433214, 1, 1, 7.97, 1, '1', 'NODESCUENT', 1, 136, 4),
(889, '2024-04-12 21:59:36', 302433214, 1, 1, 15.48, 1, '1', 'NODESCUENT', 1, 136, 4),
(890, '2024-04-12 22:01:52', 302433214, 1, 1, 13.48, 1, '1', 'NODESCUENT', 1, 136, 4),
(891, '2024-04-12 22:03:20', 302433214, 1, 2, 7.49, 1, '1', 'NODESCUENT', 1, 136, 4),
(892, '2024-04-12 23:15:02', 302433214, 1, 1, 4.24, 1, '1', 'NODESCUENT', 1, 136, 4),
(893, '2024-04-13 08:03:20', 302433214, 1, 1, 3.35, 1, '1', 'NODESCUENT', 1, 137, 4),
(894, '2024-04-13 08:15:01', 302433214, 1, 1, 6.99, 1, '1', 'NODESCUENT', 1, 137, 4),
(895, '2024-04-13 08:58:06', 302433214, 1, 1, 9.00, 1, '1', 'NODESCUENT', 1, 137, 4),
(896, '2024-04-13 09:21:16', 302433214, 1, 1, 7.99, 1, '1', 'NODESCUENT', 1, 137, 4);
INSERT INTO `factura` (`nofactura`, `fecha`, `usuario`, `codcliente`, `mesa`, `totalfactura`, `tipopago`, `codigopago`, `cupon`, `caja`, `id_cierre`, `estatus`) VALUES
(897, '2024-04-13 17:54:12', 302433214, 1, 1, 44.47, 1, '1', 'NODESCUENT', 1, 137, 4),
(898, '2024-04-13 19:56:36', 302433214, 1, 1, 14.97, 1, '1', 'NODESCUENT', 1, 137, 4),
(899, '2024-04-13 20:19:41', 302433214, 1, 1, 2.50, 1, '1', 'NODESCUENT', 1, 137, 4),
(900, '2024-04-13 20:20:53', 302433214, 1, 1, 7.00, 1, '1', 'NODESCUENT', 1, 137, 4),
(901, '2024-04-13 20:30:25', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 137, 4),
(902, '2024-04-13 20:49:00', 302433214, 1, 1, 16.20, 1, '1', 'NODESCUENT', 1, 137, 4),
(903, '2024-04-13 21:00:25', 302433214, 1, 1, 18.46, 1, '1', 'NODESCUENT', 1, 137, 4),
(904, '2024-04-13 21:08:12', 302433214, 1, 1, 30.92, 1, '1', 'NODESCUENT', 1, 137, 4),
(905, '2024-04-13 21:41:23', 302433214, 1, 1, 35.91, 3, '', 'NODESCUENT', 1, 137, 4),
(906, '2024-04-13 22:17:27', 302433214, 1, 1, 4.48, 1, '1', 'NODESCUENT', 1, 137, 4),
(907, '2024-04-13 22:38:07', 302433214, 1, 1, 2.24, 1, '1', 'NODESCUENT', 1, 137, 4),
(908, '2024-04-13 22:46:29', 302433214, 1, 1, 1.99, 1, '1', 'NODESCUENT', 1, 137, 4),
(909, '2024-04-13 22:50:43', 302433214, 1, 1, 13.98, 1, '1', 'NODESCUENT', 1, 137, 4),
(910, '2024-04-13 22:52:01', 302433214, 1, 1, 3.49, 1, '1', 'NODESCUENT', 1, 137, 4),
(911, '2024-04-13 23:41:53', 302433214, 1, 1, 18.47, 1, '1', 'NODESCUENT', 1, 137, 4),
(912, '2024-04-13 23:51:44', 302433214, 1, 1, 16.98, 1, '1', 'NODESCUENT', 1, 137, 4),
(913, '2024-04-14 01:06:16', 302433214, 1, 1, 19.95, 3, '', 'NODESCUENT', 1, 138, 4),
(914, '2024-04-14 08:19:57', 1850108166, 1, 1, 19.95, 1, '1', 'NODESCUENT', 1, 139, 4),
(915, '2024-04-14 08:43:13', 1850108166, 1, 1, 7.98, 1, '1', 'NODESCUENT', 1, 139, 4),
(916, '2024-04-14 09:05:14', 1850108166, 1, 1, 21.49, 1, '1', 'NODESCUENT', 1, 139, 4),
(917, '2024-04-14 09:12:39', 1850108166, 1, 1, 21.49, 1, '1', 'NODESCUENT', 1, 139, 4),
(918, '2024-04-14 09:17:03', 1850108166, 1, 1, 4.98, 1, '1', 'NODESCUENT', 1, 139, 4),
(919, '2024-04-14 09:19:45', 1850108166, 1, 1, 9.98, 1, '1', 'NODESCUENT', 1, 139, 4),
(920, '2024-04-14 09:44:33', 1850108166, 1, 1, 12.32, 1, '1', 'NODESCUENT', 1, 139, 4),
(921, '2024-04-24 18:01:57', 1850108166, 1, 1, 15.99, 1, '1', 'NODESCUENT', 1, 142, 4),
(922, '2024-04-24 19:30:00', 1850108166, 1, 1, 11.48, 1, '1', 'NODESCUENT', 1, 142, 4),
(923, '2024-04-24 19:51:04', 1850108166, 1, 1, 9.99, 1, '1', 'NODESCUENT', 1, 142, 4),
(924, '2024-04-24 21:28:29', 1850108166, 1, 1, 46.90, 1, '1', 'NODESCUENT', 1, 142, 4),
(925, '2024-04-25 18:15:34', 1850108166, 1, 1, 13.47, 1, '1', 'NODESCUENT', 1, 143, 4),
(926, '2024-04-25 18:35:56', 1850108166, 1, 1, 6.99, 1, '1', 'NODESCUENT', 1, 143, 4),
(927, '2024-04-25 19:11:26', 1850108166, 1, 1, 11.98, 1, '1', 'NODESCUENT', 1, 143, 4),
(928, '2024-04-25 19:53:08', 1850108166, 1, 1, 2.99, 1, '1', 'NODESCUENT', 1, 143, 4),
(929, '2024-04-25 20:04:31', 1850108166, 1, 1, 22.46, 1, '1', 'NODESCUENT', 1, 143, 4),
(930, '2024-04-25 22:46:34', 1850108166, 1, 1, 7.99, 1, '1', 'NODESCUENT', 1, 143, 4),
(931, '2024-04-26 09:51:46', 1850108166, 1, 1, 19.82, 1, '1', 'NODESCUENT', 1, 144, 4),
(932, '2024-04-26 10:11:02', 1850108166, 1, 2, 14.48, 1, '1', 'NODESCUENT', 1, 144, 4),
(933, '2024-04-26 20:06:34', 1850108166, 1, 1, 9.49, 1, '1', 'NODESCUENT', 1, 144, 4),
(934, '2024-04-26 20:28:05', 1850108166, 1, 1, 6.48, 1, '1', 'NODESCUENT', 1, 144, 4),
(935, '2024-04-26 21:29:38', 1850108166, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 144, 4),
(936, '2024-04-26 23:41:05', 1850108166, 1, 1, 11.73, 1, '1', 'NODESCUENT', 1, 144, 4),
(937, '2024-04-26 23:42:13', 1850108166, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 144, 4),
(938, '2024-04-27 08:49:55', 1850108166, 1, 1, 25.97, 1, '1', 'NODESCUENT', 1, 145, 4),
(939, '2024-04-27 08:52:23', 1850108166, 1, 1, 2.99, 1, '1', 'NODESCUENT', 1, 145, 4),
(940, '2024-04-27 09:29:05', 1850108166, 1, 1, 18.00, 1, '1', 'NODESCUENT', 1, 145, 4),
(941, '2024-04-27 11:15:27', 1850108166, 1, 1, 10.99, 1, '1', 'NODESCUENT', 1, 145, 4),
(942, '2024-04-27 11:19:51', 1850108166, 1, 1, 12.47, 3, '', 'NODESCUENT', 1, 145, 4),
(943, '2024-04-27 18:38:29', 1850108166, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 146, 4),
(944, '2024-04-27 23:22:51', 1850108166, 1, 1, 12.50, 1, '1', 'NODESCUENT', 1, 146, 4),
(945, '2024-04-27 23:54:42', 1850108166, 1, 1, 2.99, 1, '1', 'NODESCUENT', 1, 146, 4),
(946, '2024-04-28 07:31:52', 302433214, 1, 1, 9.49, 1, '1', 'NODESCUENT', 1, 147, 4),
(947, '2024-04-28 07:32:52', 302433214, 1, 1, 7.98, 1, '1', 'NODESCUENT', 1, 147, 4),
(948, '2024-04-28 09:12:40', 302433214, 1, 1, 6.00, 1, '1', 'NODESCUENT', 1, 147, 4),
(949, '2024-04-28 09:21:12', 302433214, 1, 1, 19.98, 1, '1', 'NODESCUENT', 1, 147, 4),
(950, '2024-04-28 09:35:38', 302433214, 1, 1, 13.48, 1, '1', 'NODESCUENT', 1, 147, 4),
(951, '2024-04-28 09:53:57', 302433214, 1, 1, 5.50, 1, '1', 'NODESCUENT', 1, 147, 4),
(952, '2024-04-28 09:56:08', 302433214, 1, 1, 11.00, 1, '1', 'NODESCUENT', 1, 147, 4),
(953, '2024-04-28 09:59:32', 302433214, 1, 1, 11.97, 1, '1', 'NODESCUENT', 1, 147, 4),
(954, '2024-04-28 10:01:37', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 147, 4),
(955, '2024-04-28 10:03:42', 302433214, 1, 1, 11.00, 1, '1', 'NODESCUENT', 1, 147, 4),
(956, '2024-04-28 10:08:07', 302433214, 1, 1, 7.98, 1, '1', 'NODESCUENT', 1, 147, 4),
(957, '2024-04-28 10:51:07', 302433214, 1, 1, 0.35, 1, '1', 'NODESCUENT', 1, 147, 4),
(958, '2024-04-28 11:17:57', 302433214, 1, 1, 7.00, 1, '1', 'NODESCUENT', 1, 147, 4),
(959, '2024-04-28 11:25:12', 302433214, 1, 1, 5.50, 1, '1', 'NODESCUENT', 1, 147, 4),
(960, '2024-04-28 19:16:34', 302433214, 1, 1, 20.23, 1, '1', 'NODESCUENT', 1, 147, 4),
(961, '2024-04-28 19:19:33', 302433214, 1, 1, 6.49, 1, '1', 'NODESCUENT', 1, 147, 4),
(962, '2024-04-28 19:27:15', 302433214, 1, 1, 9.73, 1, '1', 'NODESCUENT', 1, 147, 4),
(963, '2024-04-28 19:28:43', 302433214, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 147, 4),
(964, '2024-04-28 20:06:58', 302433214, 1, 2, 10.97, 1, '1', 'NODESCUENT', 1, 147, 4),
(965, '2024-04-28 20:07:18', 302433214, 1, 1, 14.47, 1, '1', 'NODESCUENT', 1, 147, 4),
(966, '2024-04-28 20:09:31', 302433214, 1, 1, 16.97, 1, '1', 'NODESCUENT', 1, 147, 4),
(967, '2024-04-28 20:10:40', 302433214, 1, 1, 12.48, 3, '', 'NODESCUENT', 1, 147, 4),
(968, '2024-04-28 20:55:57', 302433214, 1, 1, 3.50, 1, '1', 'NODESCUENT', 1, 147, 4),
(969, '2024-04-28 22:09:47', 302433214, 1, 1, 10.97, 1, '1', 'NODESCUENT', 1, 147, 4),
(970, '2024-05-01 21:20:46', 1850108166, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 148, 4),
(971, '2024-05-01 21:47:15', 1850108166, 1, 1, 13.98, 1, '1', 'NODESCUENT', 1, 148, 4),
(972, '2024-05-01 21:48:19', 1850108166, 1, 2, 18.98, 3, '', 'NODESCUENT', 1, 148, 4),
(973, '2024-05-02 20:41:51', 302433214, 1, 1, 2.00, 1, '1', 'NODESCUENT', 1, 149, 4),
(974, '2024-05-02 21:18:05', 302433214, 1, 1, 3.98, 1, '1', 'NODESCUENT', 1, 149, 4),
(975, '2024-05-03 09:12:42', 302433214, 1, 1, 1.75, 1, '1', 'NODESCUENT', 1, 149, 4),
(976, '2024-05-03 09:15:54', 302433214, 1, 1, 7.00, 1, '1', 'NODESCUENT', 1, 149, 4),
(977, '2024-05-03 09:33:23', 302433214, 1, 2, 5.50, 1, '1', 'NODESCUENT', 1, 149, 4),
(978, '2024-05-03 20:10:55', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 149, 4),
(979, '2024-05-03 23:07:05', 302433214, 1, 2, 3.98, 1, '1', 'NODESCUENT', 1, 149, 4),
(980, '2024-05-03 23:13:51', 302433214, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 149, 4),
(981, '2024-05-03 23:20:40', 302433214, 1, 1, 1.99, 1, '1', 'NODESCUENT', 1, 149, 4),
(982, '2024-05-04 08:06:47', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 150, 4),
(983, '2024-05-04 19:53:27', 302433214, 1, 1, 4.98, 3, '', 'NODESCUENT', 1, 151, 4),
(984, '2024-05-04 19:55:28', 302433214, 1, 1, 8.99, 3, '', 'NODESCUENT', 1, 151, 4),
(985, '2024-05-04 19:58:17', 302433214, 1, 1, 7.49, 3, '', 'NODESCUENT', 1, 151, 4),
(986, '2024-05-04 20:00:46', 302433214, 1, 1, 7.48, 1, '1', 'NODESCUENT', 1, 151, 4),
(987, '2024-05-04 20:02:36', 302433214, 1, 1, 8.49, 1, '1', 'NODESCUENT', 1, 151, 4),
(988, '2024-05-04 20:32:06', 302433214, 1, 1, 2.50, 3, '', 'NODESCUENT', 1, 151, 4),
(989, '2024-05-04 21:36:15', 302433214, 1, 1, 2.49, 1, '1', 'NODESCUENT', 1, 151, 4),
(990, '2024-05-04 21:49:11', 302433214, 1, 1, 1.50, 1, '1', 'NODESCUENT', 1, 151, 4),
(991, '2024-05-04 22:51:12', 302433214, 1, 1, 4.24, 1, '1', 'NODESCUENT', 1, 151, 4),
(992, '2024-05-04 23:11:15', 302433214, 1, 1, 17.94, 1, '1', 'NODESCUENT', 1, 151, 4),
(993, '2024-05-04 23:27:39', 302433214, 1, 1, 2.00, 1, '1', 'NODESCUENT', 1, 151, 4),
(994, '2024-05-05 08:12:12', 302433214, 1, 1, 10.49, 3, '', 'NODESCUENT', 1, 152, 4),
(995, '2024-05-05 08:33:59', 302433214, 1, 1, 5.50, 1, '1', 'NODESCUENT', 1, 152, 4),
(996, '2024-05-05 08:36:48', 302433214, 1, 1, 21.48, 1, '1', 'NODESCUENT', 1, 152, 4),
(997, '2024-05-05 08:53:22', 302433214, 1, 1, 11.50, 1, '1', 'NODESCUENT', 1, 152, 4),
(998, '2024-05-05 09:14:07', 302433214, 1, 1, 16.48, 1, '1', 'NODESCUENT', 1, 152, 4),
(999, '2024-05-05 09:16:06', 302433214, 1, 1, 14.48, 1, '1', 'NODESCUENT', 1, 152, 4),
(1000, '2024-05-05 09:18:12', 302433214, 1, 1, 15.49, 1, '1', 'NODESCUENT', 1, 152, 4),
(1001, '2024-05-05 09:19:44', 302433214, 1, 1, 9.00, 1, '1', 'NODESCUENT', 1, 152, 4),
(1002, '2024-05-05 09:30:24', 302433214, 1, 1, 0.04, 1, '1', 'NODESCUENT', 1, 152, 4),
(1003, '2024-05-05 09:43:18', 302433214, 1, 2, 4.99, 1, '1', 'NODESCUENT', 1, 152, 4),
(1004, '2024-05-05 10:04:11', 302433214, 1, 2, 10.99, 1, '1', 'NODESCUENT', 1, 152, 4),
(1005, '2024-05-05 10:12:07', 302433214, 1, 1, 2.23, 1, '1', 'NODESCUENT', 1, 152, 4),
(1006, '2024-05-05 20:30:57', 302433214, 1, 1, 15.95, 1, '1', 'NODESCUENT', 1, 153, 4),
(1007, '2024-05-05 20:55:14', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 153, 4),
(1008, '2024-05-05 21:49:05', 302433214, 1, 1, 6.98, 1, '1', 'NODESCUENT', 1, 153, 4),
(1009, '2024-05-08 08:23:27', 1850108166, 1, 1, 5.50, 1, '1', 'NODESCUENT', 1, 154, 4),
(1010, '2024-05-08 09:35:57', 1850108166, 1, 1, 22.00, 3, '', 'NODESCUENT', 1, 154, 4),
(1011, '2024-05-10 20:11:27', 302433214, 1, 1, 5.98, 1, '1', 'NODESCUENT', 1, 157, 4),
(1012, '2024-05-10 22:32:13', 302433214, 1, 1, 7.99, 1, '1', 'NODESCUENT', 1, 157, 4),
(1013, '2024-05-11 09:53:43', 302433214, 1, 1, 15.49, 1, '1', 'NODESCUENT', 1, 157, 4),
(1014, '2024-05-11 20:05:52', 302433214, 1, 1, 18.94, 1, '1', 'NODESCUENT', 1, 157, 4),
(1015, '2024-05-11 20:18:49', 302433214, 1, 1, 14.23, 1, '1', 'NODESCUENT', 1, 157, 4),
(1016, '2024-05-11 20:33:29', 302433214, 1, 1, 29.48, 1, '1', 'NODESCUENT', 1, 157, 4),
(1017, '2024-05-11 21:54:47', 302433214, 1, 1, 5.24, 1, '1', 'NODESCUENT', 1, 157, 4),
(1018, '2024-05-12 09:38:10', 302433214, 1, 1, 6.00, 1, '1', 'NODESCUENT', 1, 158, 4),
(1019, '2024-05-12 15:32:14', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 158, 4),
(1020, '2024-05-12 22:09:08', 302433214, 1, 1, 13.96, 1, '1', 'NODESCUENT', 1, 158, 4),
(1021, '2024-05-15 10:13:36', 1850108166, 1, 1, 9.98, 1, '1', 'NODESCUENT', 1, 159, 4),
(1022, '2024-05-15 19:51:44', 1850108166, 1, 1, 11.99, 1, '1', 'NODESCUENT', 1, 159, 4),
(1023, '2024-05-15 21:01:50', 1850108166, 1, 1, 14.00, 1, '1', 'NODESCUENT', 1, 159, 4),
(1024, '2024-05-15 23:01:57', 1850108166, 1, 1, 18.73, 1, '1', 'NODESCUENT', 1, 159, 4),
(1025, '2024-05-16 09:14:02', 1850108166, 1, 1, 7.00, 1, '1', 'NODESCUENT', 1, 160, 4),
(1026, '2024-05-17 21:19:45', 302433214, 1, 1, 5.98, 1, '1', 'NODESCUENT', 1, 161, 4),
(1027, '2024-05-18 09:28:27', 302433214, 1, 1, 22.00, 1, '1', 'NODESCUENT', 1, 161, 4),
(1028, '2024-05-18 19:04:58', 302433214, 1, 1, 10.97, 1, '1', 'NODESCUENT', 1, 161, 4),
(1029, '2024-05-18 19:42:50', 302433214, 1, 1, 28.96, 1, '1', 'NODESCUENT', 1, 161, 4),
(1030, '2024-05-18 19:58:33', 302433214, 1, 1, 16.95, 1, '1', 'NODESCUENT', 1, 161, 4),
(1031, '2024-05-18 19:59:34', 302433214, 1, 1, 15.97, 1, '1', 'NODESCUENT', 1, 161, 4),
(1032, '2024-05-18 20:24:53', 302433214, 1, 1, 11.96, 1, '1', 'NODESCUENT', 1, 161, 4),
(1033, '2024-05-18 20:56:57', 302433214, 1, 1, 2.99, 1, '1', 'NODESCUENT', 1, 161, 4),
(1034, '2024-05-18 21:49:25', 302433214, 1, 1, 13.99, 1, '1', 'NODESCUENT', 1, 161, 4),
(1035, '2024-05-19 08:54:00', 302433214, 1, 1, 16.48, 1, '1', 'NODESCUENT', 1, 162, 4),
(1036, '2024-05-19 09:21:15', 302433214, 1, 1, 22.47, 1, '1', 'NODESCUENT', 1, 162, 4),
(1037, '2024-05-19 19:45:26', 302433214, 1, 1, 6.98, 1, '1', 'NODESCUENT', 1, 162, 4),
(1038, '2024-05-19 20:11:41', 302433214, 1, 1, 3.50, 1, '1', 'NODESCUENT', 1, 162, 4),
(1039, '2024-05-19 21:23:23', 302433214, 1, 1, 22.22, 1, '1', 'NODESCUENT', 1, 162, 4),
(1040, '2024-05-19 22:00:29', 302433214, 1, 1, 2.99, 1, '1', 'NODESCUENT', 1, 162, 4),
(1041, '2024-05-19 22:39:29', 302433214, 1, 1, 8.99, 1, '1', 'NODESCUENT', 1, 162, 4),
(1042, '2024-05-23 19:30:25', 302433214, 1, 1, 5.50, 3, '', 'NODESCUENT', 1, 163, 4),
(1043, '2024-05-23 20:25:21', 302433214, 1, 1, 11.49, 3, '', 'NODESCUENT', 1, 163, 4),
(1044, '2024-05-23 22:07:59', 302433214, 1, 1, 25.96, 1, '1', 'NODESCUENT', 1, 163, 4),
(1045, '2024-05-24 10:25:06', 302433214, 1, 1, 15.99, 1, '1', 'NODESCUENT', 1, 164, 4),
(1046, '2024-05-24 19:57:14', 302433214, 1, 1, 9.48, 1, '1', 'NODESCUENT', 1, 164, 4),
(1047, '2024-05-24 20:09:32', 302433214, 1, 2, 0.99, 1, '1', 'NODESCUENT', 1, 164, 4),
(1048, '2024-05-24 21:29:00', 302433214, 1, 1, 8.74, 1, '1', 'NODESCUENT', 1, 164, 4),
(1049, '2024-05-24 22:37:04', 302433214, 1, 2, 6.98, 1, '1', 'NODESCUENT', 1, 164, 4),
(1050, '2024-05-24 23:11:23', 302433214, 1, 2, 6.98, 1, '1', 'NODESCUENT', 1, 164, 4),
(1051, '2024-05-24 23:22:23', 302433214, 1, 1, 20.21, 1, '1', 'NODESCUENT', 1, 164, 4),
(1052, '2024-05-24 23:29:52', 302433214, 1, 1, 2.99, 1, '1', 'NODESCUENT', 1, 164, 4),
(1053, '2024-05-25 08:02:14', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 165, 4),
(1054, '2024-05-25 08:06:30', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 165, 4),
(1055, '2024-05-25 08:33:47', 302433214, 1, 1, 8.50, 1, '1', 'NODESCUENT', 1, 165, 4),
(1056, '2024-05-25 08:37:20', 302433214, 1, 1, 10.99, 1, '1', 'NODESCUENT', 1, 165, 4),
(1057, '2024-05-25 10:33:18', 302433214, 1, 1, 17.49, 1, '1', 'NODESCUENT', 1, 165, 4),
(1058, '2024-05-25 18:25:57', 302433214, 1, 1, 11.47, 1, '1', 'NODESCUENT', 1, 165, 4),
(1059, '2024-05-25 18:49:08', 302433214, 1, 1, 6.97, 1, '1', 'NODESCUENT', 1, 165, 4),
(1060, '2024-05-25 18:53:46', 302433214, 1, 1, 5.98, 1, '1', 'NODESCUENT', 1, 165, 4),
(1061, '2024-05-25 19:13:59', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 165, 4),
(1062, '2024-05-25 20:53:29', 302433214, 1, 1, 17.98, 1, '1', 'NODESCUENT', 1, 165, 4),
(1063, '2024-05-25 20:56:30', 302433214, 1, 1, 11.73, 1, '1', 'NODESCUENT', 1, 165, 4),
(1064, '2024-05-25 20:58:57', 302433214, 1, 1, 4.99, 3, '', 'NODESCUENT', 1, 165, 4),
(1065, '2024-05-25 21:02:05', 302433214, 1, 1, 12.48, 1, '1', 'NODESCUENT', 1, 165, 4),
(1066, '2024-05-25 21:32:34', 302433214, 1, 1, 8.98, 1, '1', 'NODESCUENT', 1, 165, 4),
(1067, '2024-05-25 21:59:36', 302433214, 1, 1, 9.48, 1, '1', 'NODESCUENT', 1, 165, 4),
(1068, '2024-05-25 22:39:23', 302433214, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 165, 4),
(1069, '2024-05-25 23:46:56', 302433214, 1, 1, 21.74, 1, '1', 'NODESCUENT', 1, 165, 4),
(1070, '2024-05-26 08:27:26', 302433214, 1, 1, 7.85, 1, '1', 'NODESCUENT', 1, 166, 4),
(1071, '2024-05-26 09:08:10', 302433214, 1, 1, 0.35, 1, '1', 'NODESCUENT', 1, 166, 4),
(1072, '2024-05-26 09:54:45', 302433214, 1, 1, 6.45, 1, '1', 'NODESCUENT', 1, 166, 4),
(1073, '2024-05-26 10:16:50', 302433214, 1, 2, 15.47, 1, '1', 'NODESCUENT', 1, 166, 4),
(1074, '2024-05-26 10:26:34', 302433214, 1, 1, 4.99, 1, '1', 'NODESCUENT', 1, 166, 4),
(1075, '2024-05-26 18:26:11', 302433214, 1, 1, 11.96, 1, '1', 'NODESCUENT', 1, 166, 4),
(1076, '2024-05-26 19:09:02', 302433214, 1, 1, 15.97, 1, '1', 'NODESCUENT', 1, 166, 4),
(1077, '2024-05-26 19:58:42', 302433214, 1, 2, 6.24, 1, '1', 'NODESCUENT', 1, 166, 4),
(1078, '2024-05-26 20:46:46', 302433214, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 166, 4),
(1079, '2024-05-26 23:24:19', 302433214, 1, 1, 7.49, 1, '1', 'NODESCUENT', 1, 166, 4),
(1080, '2024-05-26 23:25:59', 302433214, 1, 2, 2.50, 1, '1', 'NODESCUENT', 1, 166, 4),
(1081, '2024-05-26 23:29:34', 302433214, 1, 1, 1.25, 1, '1', 'NODESCUENT', 1, 166, 4),
(1082, '2024-05-29 09:33:35', 1850108166, 1, 1, 5.50, 1, '1', 'NODESCUENT', 1, 167, 4),
(1083, '2024-05-30 08:33:11', 1850108166, 1, 1, 5.50, 1, '1', 'NODESCUENT', 1, 168, 4),
(1084, '2024-05-30 09:19:48', 1850108166, 1, 1, 16.50, 1, '1', 'NODESCUENT', 1, 168, 4),
(1085, '2024-05-30 21:14:14', 1850108166, 1, 1, 16.96, 1, '1', 'NODESCUENT', 1, 169, 4),
(1086, '2024-05-31 10:02:52', 302433214, 1, 1, 3.00, 1, '1', 'NODESCUENT', 1, 170, 4),
(1087, '2024-05-31 18:56:30', 302433214, 1, 1, 33.49, 1, '1', 'NODESCUENT', 1, 170, 4),
(1088, '2024-05-31 20:33:36', 302433214, 1, 1, 2.99, 1, '1', 'NODESCUENT', 1, 170, 4),
(1089, '2024-05-31 22:03:36', 302433214, 1, 1, 9.00, 1, '1', 'NODESCUENT', 1, 170, 4),
(1090, '2024-05-31 22:55:49', 302433214, 1, 1, 9.98, 1, '1', 'NODESCUENT', 1, 170, 4),
(1091, '2024-05-31 22:56:14', 302433214, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 170, 4),
(1092, '2024-05-31 23:05:56', 302433214, 1, 2, 1.99, 1, '1', 'NODESCUENT', 1, 170, 4),
(1093, '2024-06-01 23:00:04', 302433214, 1, 1, 4.99, 1, '1', 'NODESCUENT', 1, 171, 4),
(1094, '2024-06-01 23:31:53', 302433214, 1, 2, 1.98, 1, '1', 'NODESCUENT', 1, 171, 4),
(1095, '2024-06-02 00:07:56', 302433214, 1, 1, 4.98, 1, '1', 'NODESCUENT', 1, 171, 4),
(1096, '2024-06-02 09:52:05', 302433214, 1, 1, 9.00, 1, '1', 'NODESCUENT', 1, 171, 4),
(1097, '2024-06-02 20:07:12', 302433214, 1, 1, 2.99, 3, '', 'NODESCUENT', 1, 171, 4),
(1098, '2024-06-02 20:38:13', 302433214, 1, 28, 2.99, 1, '1', 'NODESCUENT', 1, 171, 4),
(1099, '2024-06-02 20:39:47', 302433214, 1, 1, 7.49, 1, '1', 'NODESCUENT', 1, 171, 4),
(1100, '2024-06-02 20:41:32', 302433214, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 171, 4),
(1101, '2024-06-02 22:43:54', 302433214, 1, 1, 8.49, 1, '1', 'NODESCUENT', 1, 171, 4),
(1102, '2024-06-06 20:28:10', 1850108166, 1, 1, 9.24, 1, '1', 'NODESCUENT', 1, 172, 4),
(1103, '2024-06-06 22:33:25', 1850108166, 1, 1, 11.48, 1, '1', 'NODESCUENT', 1, 172, 4),
(1104, '2024-06-06 22:35:58', 1850108166, 1, 1, 4.49, 1, '1', 'NODESCUENT', 1, 172, 4),
(1105, '2024-06-06 22:37:02', 1850108166, 1, 1, 5.98, 1, '1', 'NODESCUENT', 1, 172, 4),
(1106, '2024-06-06 22:38:15', 1850108166, 1, 1, 5.98, 1, '1', 'NODESCUENT', 1, 172, 4),
(1107, '2024-06-06 22:39:21', 1850108166, 1, 1, 3.98, 1, '1', 'NODESCUENT', 1, 172, 4),
(1108, '2024-06-06 22:41:52', 1850108166, 1, 1, 5.98, 1, '1', 'NODESCUENT', 1, 172, 4),
(1109, '2024-06-06 22:44:22', 1850108166, 1, 1, 6.49, 1, '1', 'NODESCUENT', 1, 172, 4),
(1110, '2024-06-06 22:45:39', 1850108166, 1, 1, 5.98, 1, '1', 'NODESCUENT', 1, 172, 4),
(1111, '2024-06-06 22:46:57', 1850108166, 1, 1, 6.98, 1, '1', 'NODESCUENT', 1, 172, 4),
(1112, '2024-06-07 09:50:13', 302433214, 1, 1, 6.00, 1, '1', 'NODESCUENT', 1, 173, 4),
(1113, '2024-06-07 10:01:42', 302433214, 1, 1, 3.00, 3, '', 'NODESCUENT', 1, 173, 4),
(1114, '2024-06-07 22:02:01', 302433214, 1, 1, 11.23, 3, '', 'NODESCUENT', 1, 173, 4),
(1115, '2024-06-07 22:04:02', 302433214, 1, 1, 12.48, 3, '', 'NODESCUENT', 1, 173, 4),
(1116, '2024-06-07 22:06:40', 302433214, 1, 1, 11.96, 1, '1', 'NODESCUENT', 1, 173, 4),
(1117, '2024-06-07 23:28:52', 302433214, 1, 1, 14.72, 1, '1', 'NODESCUENT', 1, 173, 4),
(1118, '2024-06-08 07:47:53', 302433214, 1, 1, 10.84, 1, '1', 'NODESCUENT', 1, 174, 4),
(1119, '2024-06-08 08:54:17', 302433214, 1, 1, 27.50, 1, '1', 'NODESCUENT', 1, 174, 4),
(1120, '2024-06-08 10:33:17', 302433214, 1, 1, 21.00, 1, '1', 'NODESCUENT', 1, 174, 4),
(1121, '2024-06-08 19:33:34', 302433214, 1, 1, 7.98, 1, '1', 'NODESCUENT', 1, 174, 4),
(1122, '2024-06-08 19:35:39', 302433214, 1, 1, 13.96, 1, '1', 'NODESCUENT', 1, 174, 4),
(1123, '2024-06-08 19:38:14', 302433214, 1, 1, 15.97, 3, '', 'NODESCUENT', 1, 174, 4),
(1124, '2024-06-08 20:25:16', 302433214, 1, 1, 12.96, 1, '1', 'NODESCUENT', 1, 174, 4),
(1125, '2024-06-08 21:20:24', 302433214, 1, 1, 8.50, 1, '1', 'NODESCUENT', 1, 174, 4),
(1126, '2024-06-09 09:53:37', 302433214, 1, 1, 5.97, 1, '1', 'NODESCUENT', 1, 175, 4),
(1127, '2024-06-09 10:21:43', 302433214, 1, 2, 13.97, 3, '', 'NODESCUENT', 1, 175, 4),
(1128, '2024-06-09 10:23:22', 302433214, 1, 2, 12.00, 1, '1', 'NODESCUENT', 1, 175, 4),
(1129, '2024-06-09 10:30:24', 302433214, 1, 1, 11.99, 1, '1', 'NODESCUENT', 1, 175, 4),
(1130, '2024-06-09 11:17:45', 302433214, 1, 1, 34.50, 1, '1', 'NODESCUENT', 1, 175, 4),
(1131, '2024-06-09 19:20:27', 302433214, 1, 1, 8.98, 1, '1', 'NODESCUENT', 1, 175, 4),
(1132, '2024-06-09 19:22:24', 302433214, 1, 1, 5.97, 3, '', 'NODESCUENT', 1, 175, 4),
(1133, '2024-06-09 19:23:49', 302433214, 1, 1, 5.98, 3, '', 'NODESCUENT', 1, 175, 4),
(1134, '2024-06-09 19:25:34', 302433214, 1, 1, 6.48, 1, '1', 'NODESCUENT', 1, 175, 4),
(1135, '2024-06-09 19:27:55', 302433214, 1, 1, 12.48, 1, '1', 'NODESCUENT', 1, 175, 4),
(1136, '2024-06-09 19:28:39', 302433214, 1, 1, 7.98, 1, '1', 'NODESCUENT', 1, 175, 4),
(1137, '2024-06-09 19:31:00', 302433214, 1, 1, 6.99, 1, '1', 'NODESCUENT', 1, 175, 4),
(1138, '2024-06-09 19:49:33', 302433214, 1, 1, 2.50, 3, '', 'NODESCUENT', 1, 175, 4),
(1139, '2024-06-09 21:08:45', 302433214, 1, 1, 21.46, 1, '1', 'NODESCUENT', 1, 175, 4),
(1140, '2024-06-09 21:36:16', 302433214, 1, 1, 1.98, 1, '1', 'NODESCUENT', 1, 175, 4),
(1141, '2024-06-12 20:52:26', 1850108166, 1, 1, 11.96, 1, '1', 'NODESCUENT', 1, 176, 4),
(1142, '2024-06-12 23:55:33', 1850108166, 1, 1, NULL, 1, '1', 'NODESCUENT', 1, 176, 4),
(1143, '2024-06-13 08:05:36', 1850108166, 1, 1, 9.98, 1, '1', 'NODESCUENT', 1, 176, 4),
(1144, '2024-06-14 18:22:48', 1850108166, 1, 2, 3.99, 1, '1', 'NODESCUENT', 1, 177, 4),
(1145, '2024-06-14 19:45:46', 1850108166, 1, 1, 10.97, 1, '1', 'NODESCUENT', 1, 177, 4),
(1146, '2024-06-14 21:46:59', 1850108166, 1, 1, 3.50, 1, '1', 'NODESCUENT', 1, 177, 4),
(1147, '2024-06-14 22:21:46', 1850108166, 1, 1, 10.47, 3, '', 'NODESCUENT', 1, 177, 4),
(1148, '2024-06-14 22:45:20', 1850108166, 1, 1, 15.72, 1, '1', 'NODESCUENT', 1, 177, 4),
(1149, '2024-06-15 10:08:35', 1850108166, 1, 1, 10.49, 1, '1', 'NODESCUENT', 1, 178, 4),
(1150, '2024-06-15 10:40:48', 1850108166, 1, 1, 18.46, 3, '', 'NODESCUENT', 1, 178, 4),
(1151, '2024-06-15 22:01:51', 1850108166, 1, 1, 20.73, 1, '1', 'NODESCUENT', 1, 179, 4),
(1152, '2024-06-15 23:47:44', 1850108166, 1, 1, 2.99, 1, '1', 'NODESCUENT', 1, 179, 4),
(1153, '2024-06-16 18:53:09', 1850108166, 1, 1, 11.98, 1, '1', 'NODESCUENT', 1, 180, 4),
(1154, '2024-06-16 20:45:28', 1850108166, 1, 1, 15.96, 1, '1', 'NODESCUENT', 1, 180, 4),
(1155, '2024-06-16 20:47:22', 1850108166, 1, 1, 3.48, 1, '1', 'NODESCUENT', 1, 180, 4),
(1156, '2024-06-16 20:48:23', 1850108166, 1, 1, 12.98, 1, '1', 'NODESCUENT', 1, 180, 4),
(1157, '2024-06-16 20:49:52', 1850108166, 1, 2, 6.98, 3, '', 'NODESCUENT', 1, 180, 4),
(1158, '2024-06-16 20:51:05', 1850108166, 1, 1, 6.49, 3, '', 'NODESCUENT', 1, 180, 4),
(1159, '2024-06-16 20:52:28', 1850108166, 1, 1, 5.98, 3, '', 'NODESCUENT', 1, 180, 4),
(1160, '2024-06-16 20:54:24', 1850108166, 1, 1, 4.98, 1, '1', 'NODESCUENT', 1, 180, 4),
(1161, '2024-06-16 21:57:55', 1850108166, 1, 1, 4.98, 1, '1', 'NODESCUENT', 1, 180, 4),
(1162, '2024-06-16 21:58:15', 1850108166, 1, 2, 2.97, 1, '1', 'NODESCUENT', 1, 180, 4),
(1163, '2024-06-20 20:08:23', 1850108166, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 181, 4),
(1164, '2024-06-22 19:34:52', 1850108166, 1, 1, 5.99, 3, '', 'NODESCUENT', 1, 182, 4),
(1165, '2024-06-22 20:36:53', 1850108166, 1, 1, 2.99, 1, '1', 'NODESCUENT', 1, 182, 4),
(1166, '2024-06-22 20:55:56', 1850108166, 1, 1, 13.73, 1, '1', 'NODESCUENT', 1, 182, 4),
(1167, '2024-06-22 21:37:17', 1850108166, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 182, 4),
(1168, '2024-06-22 21:57:00', 1850108166, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 182, 4),
(1169, '2024-06-22 22:19:27', 1850108166, 1, 1, 2.99, 1, '1', 'NODESCUENT', 1, 182, 4),
(1170, '2024-06-23 19:42:02', 302433214, 1, 1, 1.99, 1, '1', 'NODESCUENT', 1, 183, 4),
(1171, '2024-06-23 20:05:14', 302433214, 1, 1, 12.97, 1, '1', 'NODESCUENT', 1, 183, 4),
(1172, '2024-06-23 20:18:20', 302433214, 1, 1, 14.97, 1, '1', 'NODESCUENT', 1, 183, 4),
(1173, '2024-06-23 20:22:05', 302433214, 1, 2, 22.97, 1, '1', 'NODESCUENT', 1, 183, 4),
(1174, '2024-06-23 23:58:09', 302433214, 1, 1, 5.49, 3, '', 'NODESCUENT', 1, 183, 4),
(1175, '2024-06-24 00:00:29', 302433214, 1, 1, 5.48, 3, '', 'NODESCUENT', 1, 183, 4),
(1176, '2024-06-24 00:01:17', 302433214, 1, 1, 10.50, 1, '1', 'NODESCUENT', 1, 183, 4),
(1177, '2024-06-28 20:06:57', 1850108166, 1, 1, 6.98, 1, '1', 'NODESCUENT', 1, 184, 4),
(1178, '2024-06-28 22:26:52', 1850108166, 1, 1, 15.48, 1, '1', 'NODESCUENT', 1, 184, 4),
(1179, '2024-06-29 10:15:21', 1850108166, 1, 1, 4.99, 1, '1', 'NODESCUENT', 1, 185, 4),
(1180, '2024-06-29 10:15:49', 1850108166, 1, 1, 4.99, 1, '1', 'NODESCUENT', 1, 185, 4),
(1181, '2024-06-29 19:08:06', 1850108166, 1, 1, 6.24, 1, '1', 'NODESCUENT', 1, 185, 4),
(1182, '2024-06-29 20:27:44', 1850108166, 1, 1, 30.93, 1, '1', 'NODESCUENT', 1, 185, 4),
(1183, '2024-06-29 22:10:33', 1850108166, 1, 1, 7.00, 1, '1', 'NODESCUENT', 1, 185, 4),
(1184, '2024-06-29 22:11:58', 1850108166, 1, 1, 4.49, 1, '1', 'NODESCUENT', 1, 185, 4),
(1185, '2024-06-30 09:59:24', 1850108166, 1, 1, 1.75, 1, '1', 'NODESCUENT', 1, 186, 4),
(1186, '2024-06-30 19:21:22', 1850108166, 1, 1, 25.48, 1, '1', 'NODESCUENT', 1, 186, 4),
(1187, '2024-06-30 21:40:01', 1850108166, 1, 1, 7.99, 1, '1', 'NODESCUENT', 1, 186, 4),
(1188, '2024-07-05 19:07:43', 1850108166, 1, 1, 10.47, 1, '1', 'NODESCUENT', 1, 187, 4),
(1189, '2024-07-05 21:02:24', 1850108166, 1, 1, 7.49, 1, '1', 'NODESCUENT', 1, 187, 4),
(1190, '2024-07-05 21:59:42', 1850108166, 1, 1, 9.48, 1, '1', 'NODESCUENT', 1, 187, 4),
(1191, '2024-07-05 22:33:18', 1850108166, 1, 1, 14.24, 1, '1', 'NODESCUENT', 1, 187, 4),
(1192, '2024-07-05 22:34:21', 1850108166, 1, 1, 5.99, 3, '', 'NODESCUENT', 1, 187, 4),
(1193, '2024-07-06 19:12:40', 1850108166, 1, 1, 6.49, 1, '1', 'NODESCUENT', 1, 188, 4),
(1194, '2024-07-06 20:35:41', 1850108166, 1, 1, 5.98, 1, '1', 'NODESCUENT', 1, 188, 4),
(1195, '2024-07-06 21:20:21', 1850108166, 1, 1, 9.99, 1, '1', 'NODESCUENT', 1, 188, 4),
(1196, '2024-07-06 21:43:52', 1850108166, 1, 27, 1.75, 1, '1', 'NODESCUENT', 1, 188, 4),
(1197, '2024-07-06 22:43:31', 1850108166, 1, 1, 2.00, 1, '1', 'NODESCUENT', 1, 188, 4),
(1198, '2024-07-07 19:55:12', 1850108166, 1, 1, 13.00, 1, '1', 'NODESCUENT', 1, 189, 4),
(1199, '2024-07-07 20:53:34', 1850108166, 1, 1, 3.50, 1, '1', 'NODESCUENT', 1, 189, 4),
(1200, '2024-07-12 10:33:47', 1850108166, 1, 1, 11.00, 1, '1', 'NODESCUENT', 1, 190, 4),
(1201, '2024-07-12 18:36:45', 1850108166, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 190, 4),
(1202, '2024-07-12 20:13:48', 1850108166, 1, 1, 2.50, 1, '1', 'NODESCUENT', 1, 190, 4),
(1203, '2024-07-12 21:20:55', 1850108166, 1, 1, 3.50, 1, '1', 'NODESCUENT', 1, 190, 4),
(1204, '2024-07-12 22:16:05', 1850108166, 1, 1, 13.96, 1, '1', 'NODESCUENT', 1, 190, 4),
(1205, '2024-07-12 22:56:48', 1850108166, 1, 1, 8.48, 1, '1', 'NODESCUENT', 1, 190, 4),
(1206, '2024-07-12 23:34:37', 1850108166, 1, 1, 3.98, 1, '1', 'NODESCUENT', 1, 190, 4),
(1207, '2024-07-13 08:14:49', 1850108166, 1, 1, 9.49, 1, '1', 'NODESCUENT', 1, 191, 4),
(1208, '2024-07-13 19:51:30', 1850108166, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 191, 4),
(1209, '2024-07-13 19:56:14', 1850108166, 1, 2, 1.50, 1, '1', 'NODESCUENT', 1, 191, 4),
(1210, '2024-07-13 20:43:28', 1850108166, 1, 1, 12.48, 1, '1', 'NODESCUENT', 1, 191, 4),
(1211, '2024-07-13 20:53:18', 1850108166, 1, 1, 32.99, 1, '1', 'NODESCUENT', 1, 191, 4),
(1212, '2024-07-13 23:04:36', 1850108166, 1, 1, 6.23, 3, '', 'NODESCUENT', 1, 191, 4),
(1213, '2024-07-13 23:20:45', 1850108166, 1, 29, 3.99, 1, '1', 'NODESCUENT', 1, 191, 4),
(1214, '2024-07-14 10:09:24', 1850108166, 1, 1, 17.47, 3, '', 'NODESCUENT', 1, 192, 4),
(1215, '2024-07-14 10:35:52', 1850108166, 1, 1, 18.96, 1, '1', 'NODESCUENT', 1, 192, 4),
(1216, '2024-07-14 20:09:30', 1850108166, 1, 1, 7.96, 1, '1', 'NODESCUENT', 1, 192, 4),
(1217, '2024-07-14 22:03:30', 1850108166, 1, 1, 7.23, 3, '', 'NODESCUENT', 1, 192, 4),
(1218, '2024-07-19 20:40:21', 1850108166, 1, 1, 2.99, 1, '1', 'NODESCUENT', 1, 193, 4),
(1219, '2024-07-19 21:30:01', 1850108166, 1, 1, 14.72, 1, '1', 'NODESCUENT', 1, 193, 4),
(1220, '2024-07-19 22:10:06', 1850108166, 1, 1, 1.99, 1, '1', 'NODESCUENT', 1, 193, 4),
(1221, '2024-07-19 23:22:21', 1850108166, 1, 1, 4.49, 1, '1', 'NODESCUENT', 1, 193, 4),
(1222, '2024-07-19 23:23:08', 1850108166, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 193, 4),
(1223, '2024-07-20 19:11:08', 1850108166, 1, 1, 23.94, 1, '1', 'NODESCUENT', 1, 194, 4),
(1224, '2024-07-20 20:26:46', 1850108166, 1, 1, 18.72, 3, '', 'NODESCUENT', 1, 194, 4),
(1225, '2024-07-20 22:39:25', 1850108166, 1, 1, 6.24, 3, '', 'NODESCUENT', 1, 194, 4),
(1226, '2024-07-21 09:14:11', 1850108166, 1, 1, 24.47, 1, '1', 'NODESCUENT', 1, 194, 4),
(1227, '2024-07-26 21:00:13', 1850108166, 1, 1, 23.96, 1, '1', 'NODESCUENT', 1, 195, 4),
(1228, '2024-08-23 23:54:00', 1756269757, 1, 1, 7.99, 1, '1', 'NODESCUENT', 1, 196, 4),
(1229, '2024-08-23 23:55:34', 1756269757, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 196, 4),
(1230, '2024-08-24 00:01:31', 1756269757, 1, 30, 9.97, 1, '1', 'NODESCUENT', 1, 197, 4),
(1231, '2024-08-24 08:55:19', 1756269757, 1, 1, 11.55, 1, '1', 'NODESCUENT', 1, 198, 4),
(1232, '2024-08-24 09:31:46', 1756269757, 1, 1, 26.46, 1, '1', 'NODESCUENT', 1, 198, 4),
(1233, '2024-08-24 19:30:21', 1756269757, 1, 1, 11.98, 1, '1', 'NODESCUENT', 1, 198, 4),
(1234, '2024-08-24 20:24:05', 1756269757, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 198, 4),
(1235, '2024-08-24 21:10:16', 1756269757, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 198, 4),
(1236, '2024-08-24 22:08:29', 1756269757, 1, 1, 11.47, 1, '1', 'NODESCUENT', 1, 198, 4),
(1237, '2024-08-24 22:13:17', 1756269757, 1, 1, 13.96, 3, '91572561', 'NODESCUENT', 1, 198, 4),
(1238, '2024-08-24 23:06:26', 1756269757, 1, 1, 58.19, 1, '1', 'NODESCUENT', 1, 198, 4),
(1239, '2024-08-24 23:29:10', 1756269757, 1, 1, 39.95, 1, '1', 'NODESCUENT', 1, 198, 4),
(1240, '2024-08-24 23:40:53', 1756269757, 1, 1, 1.50, 1, '1', 'NODESCUENT', 1, 198, 4),
(1241, '2024-08-24 23:53:46', 1756269757, 1, 1, 43.93, 1, '1', 'NODESCUENT', 1, 198, 4),
(1242, '2024-08-25 00:34:06', 1756269757, 1, 1, 19.47, 1, '1', 'NODESCUENT', 1, 198, 4),
(1243, '2024-08-25 08:58:56', 1756269757, 1, 27, 169.21, 1, '1', 'NODESCUENT', 1, 199, 4),
(1244, '2024-08-25 10:18:00', 1756269757, 1, 1, 11.98, 1, '1', 'NODESCUENT', 1, 199, 4),
(1245, '2024-08-25 11:05:14', 1756269757, 1, 1, 7.49, 1, '1', 'NODESCUENT', 1, 199, 4),
(1246, '2024-08-25 20:43:51', 1756269757, 1, 28, 21.96, 1, '1', 'NODESCUENT', 1, 199, 4),
(1247, '2024-08-25 21:58:00', 1756269757, 1, 1, 7.00, 1, '1', 'NODESCUENT', 1, 199, 4),
(1248, '2024-08-25 22:05:08', 1756269757, 1, 2, 3.98, 1, '1', 'NODESCUENT', 1, 199, 4),
(1249, '2024-08-29 20:11:40', 1850108166, 1, 1, 7.97, 1, '1', 'NODESCUENT', 1, 200, 4),
(1250, '2024-08-29 22:14:00', 1850108166, 1, 1, 17.97, 3, '', 'NODESCUENT', 1, 200, 4),
(1251, '2024-08-30 09:26:07', 1756269757, 1, 2, 16.00, 1, '1', 'NODESCUENT', 1, 201, 4),
(1252, '2024-08-30 19:23:06', 1756269757, 1, 27, 7.98, 1, '1', 'NODESCUENT', 1, 201, 4),
(1253, '2024-08-30 19:58:47', 1756269757, 1, 1, 1.50, 1, '1', 'NODESCUENT', 1, 201, 4),
(1254, '2024-08-30 19:59:35', 1756269757, 1, 28, 15.97, 1, '1', 'NODESCUENT', 1, 201, 4),
(1255, '2024-08-30 20:12:03', 1756269757, 1, 1, 6.97, 3, '', 'NODESCUENT', 1, 201, 4),
(1256, '2024-08-30 20:22:28', 1756269757, 1, 27, 10.96, 1, '1', 'NODESCUENT', 1, 201, 4),
(1257, '2024-08-30 20:23:22', 1756269757, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 201, 4),
(1258, '2024-08-30 20:57:32', 1756269757, 1, 2, 16.49, 1, '1', 'NODESCUENT', 1, 201, 4),
(1259, '2024-08-30 21:13:01', 1756269757, 1, 1, 4.99, 1, '1', 'NODESCUENT', 1, 201, 4),
(1260, '2024-08-30 21:51:27', 1756269757, 1, 2, 12.95, 1, '1', 'NODESCUENT', 1, 201, 4),
(1261, '2024-08-30 22:01:12', 1756269757, 1, 2, 2.99, 1, '1', 'NODESCUENT', 1, 201, 4),
(1262, '2024-08-30 22:44:19', 1756269757, 1, 1, 11.47, 1, '1', 'NODESCUENT', 1, 201, 4),
(1263, '2024-08-30 23:42:57', 1756269757, 1, 1, 4.98, 1, '1', 'NODESCUENT', 1, 201, 4),
(1264, '2024-08-31 08:08:30', 1756269757, 1, 2, 3.74, 1, '1', 'NODESCUENT', 1, 202, 4),
(1265, '2024-08-31 08:24:35', 1756269757, 1, 2, 5.99, 1, '1', 'NODESCUENT', 1, 202, 4),
(1266, '2024-08-31 08:34:27', 1756269757, 1, 1, 3.00, 1, '1', 'NODESCUENT', 1, 202, 4),
(1267, '2024-08-31 08:35:33', 1756269757, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 202, 4),
(1268, '2024-08-31 09:05:39', 1756269757, 1, 32, 11.50, 1, '1', 'NODESCUENT', 1, 202, 4),
(1269, '2024-08-31 09:07:42', 1756269757, 1, 30, 11.00, 1, '1', 'NODESCUENT', 1, 202, 4),
(1270, '2024-08-31 09:10:47', 1756269757, 1, 33, 15.96, 1, '1', 'NODESCUENT', 1, 202, 4),
(1271, '2024-08-31 09:19:12', 1756269757, 1, 28, 11.96, 1, '1', 'NODESCUENT', 1, 202, 4),
(1272, '2024-08-31 09:21:07', 1756269757, 1, 30, 5.50, 1, '1', 'NODESCUENT', 1, 202, 4),
(1273, '2024-08-31 09:23:13', 1756269757, 1, 37, 11.00, 1, '1', 'NODESCUENT', 1, 202, 4),
(1274, '2024-08-31 09:26:57', 1756269757, 1, 29, 8.99, 1, '1', 'NODESCUENT', 1, 202, 4),
(1275, '2024-08-31 09:30:12', 1756269757, 1, 33, 6.48, 1, '1', 'NODESCUENT', 1, 202, 4),
(1276, '2024-08-31 09:31:35', 1756269757, 1, 34, 7.74, 1, '1', 'NODESCUENT', 1, 202, 4),
(1277, '2024-08-31 09:34:13', 1756269757, 1, 29, 9.99, 3, '', 'NODESCUENT', 1, 202, 4),
(1278, '2024-08-31 09:36:40', 1756269757, 1, 37, 22.95, 1, '1', 'NODESCUENT', 1, 202, 4),
(1279, '2024-08-31 09:40:36', 1756269757, 1, 35, 0.99, 1, '1', 'NODESCUENT', 1, 202, 4),
(1280, '2024-08-31 09:41:24', 1756269757, 1, 36, 1.99, 1, '1', 'NODESCUENT', 1, 202, 4),
(1281, '2024-08-31 09:50:40', 1756269757, 1, 37, 14.98, 1, '1', 'NODESCUENT', 1, 202, 4),
(1282, '2024-08-31 10:17:27', 1756269757, 1, 31, 12.97, 1, '1', 'NODESCUENT', 1, 202, 4),
(1283, '2024-08-31 19:02:05', 1756269757, 1, 1, 5.00, 1, '1', 'NODESCUENT', 1, 203, 4),
(1284, '2024-08-31 19:03:25', 1756269757, 1, 1, 2.50, 1, '1', 'NODESCUENT', 1, 203, 4),
(1285, '2024-08-31 19:34:21', 1756269757, 1, 1, 19.99, 1, '1', 'NODESCUENT', 1, 203, 4),
(1286, '2024-08-31 19:47:43', 1756269757, 1, 1, 5.98, 3, '', 'NODESCUENT', 1, 203, 4),
(1287, '2024-08-31 20:20:21', 1756269757, 1, 27, 25.43, 1, '1', 'NODESCUENT', 1, 203, 4),
(1288, '2024-08-31 21:22:02', 1756269757, 1, 1, 6.98, 1, '1', 'NODESCUENT', 1, 203, 4),
(1289, '2024-08-31 21:22:36', 1756269757, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 203, 4),
(1290, '2024-08-31 23:04:29', 1756269757, 1, 27, 9.98, 3, '', 'NODESCUENT', 1, 203, 4),
(1291, '2024-08-31 23:12:40', 1756269757, 1, 1, 19.21, 3, '', 'NODESCUENT', 1, 203, 4),
(1292, '2024-08-31 23:14:09', 1756269757, 1, 1, 7.49, 1, '1', 'NODESCUENT', 1, 203, 4),
(1293, '2024-08-31 23:19:56', 1756269757, 1, 1, 1.50, 1, '1', 'NODESCUENT', 1, 203, 4),
(1294, '2024-08-31 23:57:20', 1756269757, 1, 1, 25.95, 1, '1', 'NODESCUENT', 1, 203, 4),
(1295, '2024-09-01 00:51:54', 1234, 1, 1, 14.98, 3, '', 'NODESCUENT', 1, 204, 4),
(1296, '2024-09-01 00:58:02', 1234, 1, 1, 11.98, 1, '1', 'NODESCUENT', 1, 205, 4),
(1297, '2024-09-01 00:58:29', 1234, 1, 1, 11.98, 1, '1', 'NODESCUENT', 1, 205, 4),
(1298, '2024-09-01 00:58:54', 1234, 1, 1, 11.98, 1, '1', 'NODESCUENT', 1, 205, 4),
(1299, '2024-09-01 01:00:58', 1234, 1, 1, 11.98, 1, '1', 'NODESCUENT', 1, 205, 4),
(1300, '2024-09-01 01:01:29', 1234, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 205, 4),
(1301, '2024-09-01 01:04:55', 1234, 1, 1, 11.98, 1, '1', 'NODESCUENT', 1, 205, 4),
(1302, '2024-09-01 01:05:07', 1234, 1, 1, 11.98, 1, '1', 'NODESCUENT', 1, 205, 4),
(1303, '2024-09-01 01:05:27', 1234, 1, 1, 11.98, 1, '1', 'NODESCUENT', 1, 205, 4),
(1304, '2024-09-01 01:06:04', 1234, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 205, 4),
(1305, '2024-09-01 10:27:53', 1234, 1, 28, 25.49, 1, '1', 'NODESCUENT', 1, 206, 4),
(1306, '2024-09-01 10:39:43', 1234, 1, 2, 78.01, 1, '1', 'NODESCUENT', 1, 206, 4),
(1307, '2024-09-01 11:07:48', 1234, 1, 2, 1.75, 1, '1', 'NODESCUENT', 1, 206, 4),
(1308, '2024-09-01 11:08:47', 1234, 1, 27, 27.69, 1, '1', 'NODESCUENT', 1, 206, 4),
(1309, '2024-09-01 11:09:15', 1234, 1, 28, 7.98, 3, '', 'NODESCUENT', 1, 206, 4),
(1310, '2024-09-01 20:05:16', 1756269757, 1, 2, 5.75, 1, '1', 'NODESCUENT', 1, 207, 4),
(1311, '2024-09-01 20:19:47', 1756269757, 1, 1, 2.24, 1, '1', 'NODESCUENT', 1, 207, 4),
(1312, '2024-09-01 20:27:29', 1756269757, 1, 1, 3.00, 1, '1', 'NODESCUENT', 1, 207, 4),
(1313, '2024-09-01 21:00:10', 1756269757, 1, 1, 7.99, 1, '1', 'NODESCUENT', 1, 207, 4),
(1314, '2024-09-01 21:01:30', 1756269757, 1, 1, 4.50, 1, '1', 'NODESCUENT', 1, 207, 4),
(1315, '2024-09-05 20:29:20', 1850108166, 1, 1, 1.99, 1, '1', 'NODESCUENT', 1, 208, 4),
(1316, '2024-09-05 20:39:11', 1850108166, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 208, 4),
(1317, '2024-09-05 22:29:37', 1850108166, 1, 1, 7.49, 3, '', 'NODESCUENT', 1, 208, 4),
(1318, '2024-09-06 08:25:19', 1850108166, 1, 1, 1.99, 3, '9371897', 'NODESCUENT', 1, 209, 4),
(1319, '2024-09-06 19:30:29', 1850108166, 1, 1, 9.96, 1, '1', 'NODESCUENT', 1, 209, 4),
(1320, '2024-09-06 19:38:47', 1850108166, 1, 1, 5.75, 1, '1', 'NODESCUENT', 1, 209, 4),
(1321, '2024-09-06 20:39:27', 1850108166, 1, 28, 8.00, 1, '1', 'NODESCUENT', 1, 209, 4),
(1322, '2024-09-06 20:58:18', 1850108166, 1, 1, 17.93, 1, '1', 'NODESCUENT', 1, 209, 4),
(1323, '2024-09-06 20:59:34', 1850108166, 1, 2, 6.98, 3, '', 'NODESCUENT', 1, 209, 4),
(1324, '2024-09-06 21:00:23', 1850108166, 1, 28, 5.98, 1, '1', 'NODESCUENT', 1, 209, 4),
(1325, '2024-09-06 22:17:51', 1850108166, 1, 28, 3.99, 1, '1', 'NODESCUENT', 1, 209, 4),
(1326, '2024-09-06 22:20:20', 1850108166, 1, 1, 11.47, 1, '1', 'NODESCUENT', 1, 209, 4),
(1327, '2024-09-06 22:21:06', 1850108166, 1, 1, 4.49, 1, '1', 'NODESCUENT', 1, 209, 4),
(1328, '2024-09-06 22:23:00', 1850108166, 1, 2, 11.48, 1, '1', 'NODESCUENT', 1, 209, 4),
(1329, '2024-09-06 22:40:19', 1850108166, 1, 27, 10.23, 1, '1', 'NODESCUENT', 1, 209, 4),
(1330, '2024-09-06 22:49:24', 1850108166, 1, 1, 1.50, 1, '1', 'NODESCUENT', 1, 209, 4),
(1331, '2024-09-06 23:14:31', 1850108166, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 209, 4),
(1332, '2024-09-07 08:03:44', 1756269757, 1, 2, 6.99, 3, '', 'NODESCUENT', 1, 210, 4),
(1333, '2024-09-07 08:06:41', 1756269757, 1, 1, 4.74, 1, '1', 'NODESCUENT', 1, 210, 4),
(1334, '2024-09-07 08:07:47', 1756269757, 1, 1, 1.75, 1, '1', 'NODESCUENT', 1, 210, 4),
(1335, '2024-09-07 10:52:09', 1756269757, 1, 1, 12.49, 1, '1', 'NODESCUENT', 1, 210, 4),
(1336, '2024-09-07 11:02:28', 1756269757, 1, 1, 15.98, 1, '1', 'NODESCUENT', 1, 210, 4),
(1337, '2024-09-07 11:11:21', 1756269757, 1, 2, 27.43, 3, '', 'NODESCUENT', 1, 210, 4),
(1338, '2024-09-07 18:47:52', 1756269757, 1, 1, 17.24, 1, '1', 'NODESCUENT', 1, 211, 4),
(1339, '2024-09-07 19:30:25', 1756269757, 1, 1, 5.48, 1, '1', 'NODESCUENT', 1, 211, 4),
(1340, '2024-09-07 20:38:28', 1756269757, 1, 2, 53.42, 3, '', 'NODESCUENT', 1, 211, 4),
(1341, '2024-09-07 21:44:38', 1756269757, 1, 1, 10.00, 1, '1', 'NODESCUENT', 1, 211, 4),
(1342, '2024-09-07 21:54:40', 1756269757, 1, 2, 17.22, 1, '1', 'NODESCUENT', 1, 211, 4),
(1343, '2024-09-07 22:01:15', 1756269757, 1, 1, 4.50, 1, '1', 'NODESCUENT', 1, 211, 4),
(1344, '2024-09-07 22:45:21', 1756269757, 1, 1, 3.75, 1, '1', 'NODESCUENT', 1, 211, 4),
(1345, '2024-09-07 23:46:51', 1756269757, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 211, 4),
(1346, '2024-09-07 23:49:18', 1756269757, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 211, 4),
(1347, '2024-09-08 08:29:37', 1756269757, 1, 1, 12.00, 1, '1', 'NODESCUENT', 1, 212, 4),
(1348, '2024-09-08 08:58:23', 1756269757, 1, 1, 23.95, 1, '1', 'NODESCUENT', 1, 212, 4),
(1349, '2024-09-08 10:30:17', 1756269757, 1, 27, 25.98, 1, '1', 'NODESCUENT', 1, 212, 4),
(1350, '2024-09-08 10:42:52', 1756269757, 1, 1, 7.98, 1, '1', 'NODESCUENT', 1, 212, 4),
(1351, '2024-09-08 10:43:41', 1756269757, 1, 1, 7.96, 1, '1', 'NODESCUENT', 1, 212, 4),
(1352, '2024-09-08 10:56:54', 1756269757, 1, 27, 14.97, 3, '', 'NODESCUENT', 1, 212, 4),
(1353, '2024-09-08 11:06:00', 1756269757, 1, 2, 26.96, 3, '', 'NODESCUENT', 1, 212, 4),
(1354, '2024-09-12 18:20:05', 1756269757, 1, 1, 6.98, 1, '1', 'NODESCUENT', 1, 213, 4),
(1355, '2024-09-12 18:57:20', 1756269757, 1, 1, 1.98, 1, '1', 'NODESCUENT', 1, 213, 4),
(1356, '2024-09-12 20:03:10', 1756269757, 1, 1, 6.98, 3, '', 'NODESCUENT', 1, 213, 4),
(1357, '2024-09-12 21:21:35', 1756269757, 1, 1, 5.98, 4, '', 'NODESCUENT', 1, 213, 4),
(1358, '2024-09-12 21:23:26', 1756269757, 1, 1, 5.99, 3, '', 'NODESCUENT', 1, 213, 4),
(1359, '2024-09-12 21:23:51', 1756269757, 1, 1, 0.25, 1, '1', 'NODESCUENT', 1, 213, 4),
(1360, '2024-09-12 22:13:26', 1756269757, 1, 2, 27.48, 1, '1', 'NODESCUENT', 1, 213, 4),
(1361, '2024-09-12 22:47:04', 1756269757, 1, 1, 4.97, 1, '1', 'NODESCUENT', 1, 213, 4),
(1362, '2024-09-12 23:41:44', 1756269757, 1, 2, 33.91, 1, '1', 'NODESCUENT', 1, 213, 4),
(1363, '2024-09-12 23:44:28', 1756269757, 1, 1, 5.48, 1, '1', 'NODESCUENT', 1, 213, 4),
(1364, '2024-09-13 09:35:59', 1756269757, 1, 1, 6.99, 1, '1', 'NODESCUENT', 1, 214, 4),
(1365, '2024-09-13 10:26:13', 1756269757, 1, 1, 22.45, 1, '1', 'NODESCUENT', 1, 214, 4),
(1366, '2024-09-13 10:35:36', 1756269757, 1, 2, 28.22, 1, '1', 'NODESCUENT', 1, 214, 4),
(1367, '2024-09-13 19:54:52', 1756269757, 1, 1, 18.49, 1, '1', 'NODESCUENT', 1, 214, 4),
(1368, '2024-09-13 21:25:06', 1756269757, 1, 1, 20.97, 1, '1', 'NODESCUENT', 1, 214, 4),
(1369, '2024-09-13 22:02:35', 1756269757, 1, 28, 20.95, 1, '1', 'NODESCUENT', 1, 214, 4),
(1370, '2024-09-13 22:24:23', 1756269757, 1, 1, 25.44, 1, '1', 'NODESCUENT', 1, 214, 4),
(1371, '2024-09-13 22:31:16', 1756269757, 1, 1, 7.25, 1, '1', 'NODESCUENT', 1, 214, 4),
(1372, '2024-09-13 22:41:48', 1756269757, 1, 2, 3.50, 1, '1', 'NODESCUENT', 1, 214, 4),
(1373, '2024-09-13 22:51:18', 1756269757, 1, 1, 1.75, 1, '1', 'NODESCUENT', 1, 214, 4),
(1374, '2024-09-13 23:23:02', 1756269757, 1, 1, 3.99, 1, '1', 'NODESCUENT', 1, 214, 4),
(1375, '2024-09-14 09:07:26', 1756269757, 1, 1, 17.92, 1, '1', 'NODESCUENT', 1, 215, 4),
(1376, '2024-09-14 09:08:43', 1756269757, 1, 1, 2.97, 1, '1', 'NODESCUENT', 1, 215, 4),
(1377, '2024-09-14 09:13:57', 1756269757, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 215, 4),
(1378, '2024-09-14 09:21:46', 1756269757, 1, 1, 11.00, 1, '1', 'NODESCUENT', 1, 215, 4),
(1379, '2024-09-14 10:28:26', 1756269757, 1, 1, 46.42, 3, '', 'NODESCUENT', 1, 215, 4),
(1380, '2024-09-14 10:29:02', 1756269757, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 215, 4),
(1381, '2024-09-14 10:29:21', 1756269757, 1, 1, 0.25, 1, '1', 'NODESCUENT', 1, 215, 4),
(1382, '2024-09-14 10:43:22', 1756269757, 1, 2, 21.85, 1, '1', 'NODESCUENT', 1, 215, 4),
(1383, '2024-09-14 11:22:33', 1756269757, 1, 1, 40.53, 1, '1', 'NODESCUENT', 1, 215, 4),
(1384, '2024-09-14 19:36:05', 1756269757, 1, 1, 0.99, 1, '1', 'NODESCUENT', 1, 216, 4),
(1385, '2024-09-14 20:58:11', 1756269757, 1, 1, 1.50, 1, '1', 'NODESCUENT', 1, 216, 4),
(1386, '2024-09-14 21:41:55', 1756269757, 1, 27, 6.98, 1, '1', 'NODESCUENT', 1, 216, 4),
(1387, '2024-09-14 21:47:06', 1756269757, 1, 27, 0.25, 1, '1', 'NODESCUENT', 1, 216, 4),
(1388, '2024-09-14 21:48:21', 1756269757, 1, 28, 9.96, 1, '1', 'NODESCUENT', 1, 216, 4),
(1389, '2024-09-14 22:22:53', 1756269757, 1, 1, 26.45, 3, '', 'NODESCUENT', 1, 216, 4),
(1390, '2024-09-14 22:23:08', 1756269757, 1, 2, 24.20, 1, '1', 'NODESCUENT', 1, 216, 4),
(1391, '2024-09-14 23:03:25', 1756269757, 1, 1, 4.49, 1, '1', 'NODESCUENT', 1, 216, 4),
(1392, '2024-09-14 23:13:36', 1756269757, 1, 1, 5.75, 1, '1', 'NODESCUENT', 1, 216, 4),
(1393, '2025-01-02 14:31:20', 1850108166, 1, 1, 17.47, 1, '', NULL, 1, 217, 4),
(1394, '2025-01-02 14:32:12', 1850108166, 1, 2, 33.99, 1, '', NULL, 1, 217, 4),
(1395, '2025-01-02 14:32:30', 1850108166, 1, 2, 18.99, 1, '', NULL, 1, 217, 4),
(1396, '2025-01-02 14:33:15', 1850108166, 1, 2, 9.99, 1, '', NULL, 1, 217, 4),
(1397, '2025-01-02 14:59:42', 1850108166, 1, 1, 43.43, 1, '', NULL, 1, 217, 4),
(1398, '2025-01-02 15:09:38', 1850108166, 1, 1, 44.43, 1, '', NULL, 1, 217, 4),
(1399, '2025-01-02 15:11:30', 1850108166, 1, 1, 22.47, 1, '', NULL, 1, 217, 4),
(1400, '2025-01-02 15:12:28', 1850108166, 1, 2, 7.96, 1, '', NULL, 1, 217, 4),
(1401, '2025-01-02 15:14:07', 1850108166, 1, 2, 2.97, 1, '1', 'NODESCUENT', 1, 217, 4),
(1402, '2025-01-02 15:14:37', 1850108166, 1, 1, 16.97, 1, '1', 'NODESCUENT', 1, 217, 4),
(1403, '2025-01-02 15:16:21', 1850108166, 1, 1, 11.98, 1, '1', 'NODESCUENT', 1, 217, 4),
(1404, '2025-01-02 15:54:03', 1850108166, 1, 1, 29.95, 1, '1', 'NODESCUENT', 1, 217, 4),
(1405, '2025-01-02 15:54:37', 1850108166, 1, 1, 11.98, 1, '1', 'NODESCUENT', 1, 217, 4),
(1406, '2025-01-02 16:08:01', 1850108166, 1, 1, 0.99, 1, '', NULL, 1, 217, 4),
(1407, '2025-01-02 16:12:04', 1850108166, 1, 1, 5.99, 1, '', NULL, 1, 217, 4),
(1408, '2025-01-02 16:13:38', 1850108166, 1, 1, 5.99, 1, '1', 'NODESCUENT', 1, 217, 4),
(1409, '2025-01-02 18:13:22', 1850108166, 1, 1, 2.50, 3, '', 'NODESCUENT', 1, 218, 4),
(1410, '2025-01-02 18:13:50', 1850108166, 1, 1, 10.98, 1, '1', 'NODESCUENT', 1, 218, 4),
(1411, '2025-01-02 18:14:26', 1850108166, 1, 1, 8.98, 1, '1', 'NODESCUENT', 1, 218, 4),
(1412, '2025-01-02 18:15:13', 1850108166, 1, 1, 11.00, 2, '', 'NODESCUENT', 1, 218, 4),
(1413, '2025-01-02 21:45:50', 1850108166, 1, 2, 13.97, 1, '1', 'NODESCUENT', 1, 218, 4),
(1414, '2025-01-02 21:53:25', 1850108166, 1, 1, 27.96, 1, '1', 'NODESCUENT', 1, 218, 4),
(1415, '2025-01-02 22:09:37', 1850108166, 1, 30, 73.34, 1, '1', 'NODESCUENT', 1, 218, 4),
(1416, '2025-01-02 22:14:47', 1850108166, 1, 30, 3.75, 1, '1', 'NODESCUENT', 1, 218, 4),
(1417, '2025-01-02 22:24:03', 1850108166, 1, 28, 31.46, 1, '1', 'NODESCUENT', 1, 218, 4),
(1418, '2025-01-02 22:31:47', 1850108166, 1, 1, 18.96, 1, '1', 'NODESCUENT', 1, 218, 4),
(1419, '2025-01-02 23:18:26', 1850108166, 1, 1, 27.46, 1, '1', 'NODESCUENT', 1, 218, 4),
(1420, '2025-01-02 23:42:37', 1850108166, 1, 28, 41.43, 1, '1', 'NODESCUENT', 1, 218, 4),
(1421, '2025-01-02 23:45:12', 1850108166, 1, 28, 7.50, 1, '1', 'NODESCUENT', 1, 218, 4),
(1422, '2025-01-03 00:02:10', 1850108166, 1, 2, 11.48, 1, '1', 'NODESCUENT', 1, 218, 4),
(1423, '2025-01-03 00:04:14', 1850108166, 1, 2, 14.47, 3, '', 'NODESCUENT', 1, 218, 4),
(1424, '2025-01-03 00:05:36', 1850108166, 1, 2, 7.49, 1, '1', 'NODESCUENT', 1, 218, 4),
(1425, '2025-01-03 00:07:40', 1850108166, 1, 2, 5.99, 1, '1', 'NODESCUENT', 1, 218, 4),
(1426, '2025-01-03 00:13:01', 1850108166, 1, 28, 0.25, 1, '1', 'NODESCUENT', 1, 218, 4),
(1427, '2025-01-03 00:15:30', 1850108166, 1, 2, 4.00, 1, '1', 'NODESCUENT', 1, 218, 4),
(1428, '2025-01-03 00:26:50', 1850108166, 1, 2, 44.94, 3, '', 'NODESCUENT', 1, 218, 4),
(1429, '2025-01-03 08:07:08', 1850108166, 1, 1, 40.68, 1, '1', 'NODESCUENT', 1, 219, 4),
(1430, '2025-01-03 08:07:54', 1850108166, 1, 27, 4.98, 1, '1', 'NODESCUENT', 1, 219, 4),
(1431, '2025-01-03 08:08:07', 1850108166, 1, 29, 18.96, 1, '1', 'NODESCUENT', 1, 219, 4),
(1432, '2025-01-03 09:56:26', 1756269757, 1, 1, 24.46, 1, '1', 'NODESCUENT', 1, 220, 4),
(1433, '2025-01-03 09:59:12', 1756269757, 1, 1, 22.48, 1, '1', 'NODESCUENT', 1, 220, 4),
(1434, '2025-01-03 09:59:47', 1756269757, 1, 1, 19.00, 1, '1', 'NODESCUENT', 1, 220, 4),
(1435, '2025-01-03 10:00:19', 1756269757, 1, 1, 13.48, 3, '1', 'NODESCUENT', 1, 220, 4),
(1436, '2025-01-03 10:06:08', 1756269757, 1, 27, 11.48, 1, '1', 'NODESCUENT', 1, 220, 4),
(1437, '2025-01-03 10:08:06', 1756269757, 1, 2, 11.48, 1, '1', 'NODESCUENT', 1, 220, 4),
(1438, '2025-01-03 10:22:35', 1756269757, 1, 1, 14.97, 1, '1', 'NODESCUENT', 1, 220, 4),
(1439, '2025-01-03 11:33:45', 1756269757, 1, 1, 25.97, 1, '', NULL, 1, 220, 4),
(1440, '2025-01-03 11:34:48', 1756269757, 1, 1, 13.49, 3, '', 'NODESCUENT', 1, 220, 4),
(1441, '2025-01-03 12:09:48', 1756269757, 1, 1, 12.50, 1, '1', 'NODESCUENT', 1, 220, 4),
(1442, '2025-01-03 13:18:26', 1756269757, 1, 2, 4.50, 1, '1', 'NODESCUENT', 1, 220, 4),
(1443, '2025-01-03 13:26:53', 1756269757, 1, 1, 7.96, 2, '1', 'NODESCUENT', 1, 220, 4),
(1444, '2025-01-03 13:53:05', 1756269757, 1, 1, 1.99, 1, '1', 'NODESCUENT', 1, 220, 4),
(1445, '2025-01-03 19:25:42', 1756269757, 1, 1, 21.44, 1, '1', 'NODESCUENT', 1, 221, 4),
(1446, '2025-01-03 19:28:58', 1756269757, 1, 2, 24.45, 4, '', 'NODESCUENT', 1, 221, 4),
(1447, '2025-01-03 19:55:29', 1756269757, 1, 1, 13.99, 3, '', 'NODESCUENT', 1, 221, 4),
(1448, '2025-01-03 20:02:08', 1756269757, 1, 2, 23.95, 2, '', 'NODESCUENT', 1, 221, 4),
(1449, '2025-01-03 20:18:12', 1756269757, 1, 27, 59.84, 3, '', 'NODESCUENT', 1, 221, 4),
(1450, '2025-01-03 20:31:36', 1756269757, 1, 4, 20.95, 1, '', NULL, 1, 221, 4),
(1451, '2025-01-03 20:32:53', 1756269757, 1, 4, 18.94, 1, '', NULL, 1, 221, 4),
(1452, '2025-01-03 20:46:52', 1756269757, 1, 1, 1.99, 1, '1', 'NODESCUENT', 1, 221, 4),
(1453, '2025-01-03 21:04:44', 1756269757, 1, 2, 0.99, 1, '1', 'NODESCUENT', 1, 221, 4),
(1454, '2025-01-03 21:44:48', 1756269757, 1, 1, 15.73, 1, '', NULL, 1, 221, 4),
(1455, '2025-01-03 21:49:33', 1756269757, 1, 1, 15.48, 1, '', NULL, 1, 221, 4),
(1456, '2025-01-03 22:49:33', 1756269757, 1, 1, 31.94, 2, '', 'NODESCUENT', 1, 221, 4),
(1457, '2025-01-03 23:05:14', 1756269757, 1, 2, 11.48, 1, '1', 'NODESCUENT', 1, 221, 4),
(1458, '2025-01-03 23:33:09', 1756269757, 1, 3, 18.22, 1, '1', 'NODESCUENT', 1, 221, 4),
(1459, '2025-01-03 23:44:59', 1756269757, 1, 2, 13.96, 2, '', 'NODESCUENT', 1, 221, 4),
(1460, '2025-01-03 23:45:47', 1756269757, 1, 1, 11.96, 2, '', 'NODESCUENT', 1, 221, 4),
(1461, '2025-01-04 00:07:22', 1756269757, 1, 4, 1.50, 3, '', 'NODESCUENT', 1, 221, 4),
(1462, '2025-01-04 00:09:14', 1756269757, 1, 2, 3.44, 1, '1', 'NODESCUENT', 1, 221, 4),
(1463, '2025-01-04 00:17:52', 1756269757, 1, 1, 16.47, 1, '1', 'NODESCUENT', 1, 221, 4),
(1464, '2025-01-04 08:47:29', 1756269757, 1, 1, 7.98, 1, '1', 'NODESCUENT', 1, 222, 4),
(1465, '2025-01-04 09:25:49', 1756269757, 1, 1, 3.45, 1, '1', 'NODESCUENT', 1, 222, 4),
(1466, '2025-01-04 09:28:10', 1756269757, 1, 1, 11.00, 3, '', 'NODESCUENT', 1, 222, 4),
(1467, '2025-01-04 10:29:41', 1756269757, 1, 1, 2.50, 1, '1', 'NODESCUENT', 1, 222, 4),
(1468, '2025-01-04 10:54:25', 1756269757, 1, 1, 13.97, 3, '', 'NODESCUENT', 1, 222, 4),
(1469, '2025-01-04 19:23:37', 1756269757, 1, 1, 5.49, 1, '1', 'NODESCUENT', 1, 222, 4),
(1470, '2025-01-04 20:37:19', 1756269757, 1, 13, 5.99, 1, '1', 'NODESCUENT', 1, 222, 4),
(1471, '2025-01-04 20:37:54', 1756269757, 1, 12, 5.99, 1, '1', 'NODESCUENT', 1, 222, 4),
(1472, '2025-01-04 20:51:31', 1756269757, 1, 1, 15.95, 3, '', 'NODESCUENT', 1, 222, 4),
(1473, '2025-01-04 21:37:30', 1756269757, 1, 1, 9.97, 1, '1', 'NODESCUENT', 1, 222, 4),
(1474, '2025-01-04 21:38:26', 1756269757, 1, 2, 3.99, 4, '', 'NODESCUENT', 1, 222, 4),
(1475, '2025-01-04 22:07:18', 1756269757, 1, 1, 48.40, 3, '', 'NODESCUENT', 1, 222, 4),
(1476, '2025-01-04 22:37:57', 1756269757, 1, 2, 9.98, 3, '', 'NODESCUENT', 1, 222, 4),
(1477, '2025-01-04 22:38:11', 1756269757, 1, 13, 6.98, 3, '', 'NODESCUENT', 1, 222, 4),
(1478, '2025-01-04 23:11:28', 1756269757, 1, 1, 3.50, 1, '1', 'NODESCUENT', 1, 222, 4);
INSERT INTO `factura` (`nofactura`, `fecha`, `usuario`, `codcliente`, `mesa`, `totalfactura`, `tipopago`, `codigopago`, `cupon`, `caja`, `id_cierre`, `estatus`) VALUES
(1479, '2025-01-04 23:58:35', 1756269757, 1, 1, 15.96, 4, '', 'NODESCUENT', 1, 222, 4),
(1480, '2025-01-05 00:05:53', 1756269757, 1, 2, 12.47, 1, '1', 'NODESCUENT', 1, 222, 4),
(1481, '2025-01-05 00:06:21', 1756269757, 1, 3, 11.46, 1, '1', 'NODESCUENT', 1, 222, 4),
(1482, '2025-01-05 09:38:07', 1756269757, 1, 1, 7.49, 3, '', 'NODESCUENT', 1, 223, 4),
(1483, '2025-01-05 09:38:44', 1756269757, 1, 2, 5.50, 3, '', 'NODESCUENT', 1, 223, 4),
(1484, '2025-01-05 09:40:02', 1756269757, 1, 3, 4.00, 1, '1', 'NODESCUENT', 1, 223, 4),
(1485, '2025-01-05 09:40:17', 1756269757, 1, 4, 5.50, 1, '1', 'NODESCUENT', 1, 223, 4),
(1486, '2025-01-05 09:40:29', 1756269757, 1, 5, 3.99, 1, '1', 'NODESCUENT', 1, 223, 4),
(1487, '2025-01-05 09:45:20', 1756269757, 1, 7, 4.00, 1, '1', 'NODESCUENT', 1, 223, 4),
(1488, '2025-01-05 09:45:31', 1756269757, 1, 6, 5.50, 1, '1', 'NODESCUENT', 1, 223, 4),
(1489, '2025-01-05 09:46:30', 1756269757, 1, 8, 10.49, 3, '', 'NODESCUENT', 1, 223, 4),
(1490, '2025-01-05 10:06:01', 1756269757, 1, 9, 39.42, 3, '', 'NODESCUENT', 1, 223, 4),
(1491, '2025-01-05 11:17:04', 1756269757, 1, 1, 15.96, 3, '', 'NODESCUENT', 1, 223, 4),
(1492, '2025-01-05 11:26:03', 1756269757, 1, 1, 6.99, 1, '1', 'NODESCUENT', 1, 223, 4),
(1493, '2025-01-05 19:09:27', 1756269757, 1, 1, 11.96, 2, '', 'NODESCUENT', 1, 224, 4),
(1494, '2025-01-10 20:35:15', 1850108166, 1, 1, 1.99, 1, '1', 'NODESCUENT', 1, 225, 4),
(1495, '2025-01-10 23:50:14', 1850108166, 1, 1, 7.49, 1, '1', 'NODESCUENT', 1, 225, 4),
(1496, '2025-01-11 20:43:32', 1850108166, 1, 9, 20.96, 3, '', 'NODESCUENT', 1, 226, 4),
(1497, '2025-01-12 11:20:23', 302433214, 1, 1, 20.97, 1, '1', 'NODESCUENT', 1, 227, 4),
(1498, '2025-01-12 21:37:01', 302433214, 1, 1, 20.98, 1, '1', 'NODESCUENT', 1, 227, 4),
(1499, '2025-01-13 00:11:26', 302433214, 1, 1, 14.49, 1, '1', 'NODESCUENT', 1, 227, 4),
(1500, '2025-01-18 19:15:53', 1850108166, 1, 1, 8.99, 1, '1', 'NODESCUENT', 1, 228, 4),
(1501, '2025-01-18 20:59:02', 1850108166, 1, 2, 33.90, 1, '1', 'NODESCUENT', 1, 228, 4),
(1502, '2025-01-18 21:59:35', 1850108166, 1, 3, 32.41, 4, '', 'NODESCUENT', 1, 228, 4),
(1503, '2025-01-19 00:16:29', 1850108166, 1, 5, 36.43, 1, '1', 'NODESCUENT', 1, 228, 4),
(1504, '2025-01-19 20:25:52', 1850108166, 1, 1, 7.00, 4, '', 'NODESCUENT', 1, 229, 4),
(1505, '2025-01-19 20:56:50', 1850108166, 1, 4, 4.99, 1, '1', 'NODESCUENT', 1, 229, 4),
(1506, '2025-01-19 20:58:51', 1850108166, 1, 2, 5.98, 4, '', 'NODESCUENT', 1, 229, 4),
(1507, '2025-01-19 20:59:22', 1850108166, 1, 3, 4.98, 4, '', 'NODESCUENT', 1, 229, 4),
(1508, '2025-01-19 21:01:04', 1850108166, 1, 1, 6.48, 1, '1', 'NODESCUENT', 1, 229, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `factura_credito`
--

DROP TABLE IF EXISTS `factura_credito`;
CREATE TABLE IF NOT EXISTS `factura_credito` (
  `nofactura` bigint NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario` int DEFAULT NULL,
  `codcliente` int DEFAULT NULL,
  `totalfactura` decimal(10,2) NOT NULL,
  `estatus` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`nofactura`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `factura_credito`
--

INSERT INTO `factura_credito` (`nofactura`, `fecha`, `usuario`, `codcliente`, `totalfactura`, `estatus`) VALUES
(1, '0000-00-00 00:00:00', 1234, 123433, 5.00, 1),
(2, '0000-00-00 00:00:00', 1234, 123433, 5.00, 1),
(3, '2020-08-04 00:01:12', 1803641420, 123433, 10.00, 1),
(4, '2020-08-04 00:31:08', 1803641420, 123433, 5.00, 1),
(5, '2020-08-04 00:54:18', 1803641420, 123433, 4.00, 1),
(6, '2020-08-04 00:55:13', 1803641420, 123433, 4.00, 1),
(9, '2022-01-16 20:50:06', 1234, 1234, 10.00, 1),
(10, '2022-01-16 20:50:44', 1234, 1234, 4.00, 1),
(11, '2022-03-04 23:55:57', 1234, 1234, 1.00, 1),
(12, '2022-03-04 23:57:41', 1234, 1234, 20.00, 1),
(13, '2022-03-05 00:05:07', 1234, 1234, 19.00, 1),
(14, '2022-03-05 00:05:30', 1234, 1234, 1.00, 1),
(15, '2022-03-05 00:05:46', 1234, 1234, 1.00, 1),
(16, '2022-03-05 16:24:34', 1234, 1234, 10.00, 1),
(17, '2022-03-10 12:45:10', 1234, 1803641420, 10.00, 1),
(18, '2022-07-21 11:44:39', 1234, 1234, 100.00, 1),
(19, '2022-07-26 16:54:54', 1234, 1234, 100.00, 1),
(20, '2022-07-26 17:15:45', 1234, 12345, 10.00, 1),
(21, '2022-08-10 16:45:08', 1234, 1234, 10.00, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fotos`
--

DROP TABLE IF EXISTS `fotos`;
CREATE TABLE IF NOT EXISTS `fotos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ubicacion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha` date NOT NULL,
  `dia_festivo` int NOT NULL,
  `fecha_add` date NOT NULL,
  `user_add` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `fotos`
--

INSERT INTO `fotos` (`id`, `ubicacion`, `fecha`, `dia_festivo`, `fecha_add`, `user_add`) VALUES
(1, 'img1.jpg', '2022-03-17', 2, '2022-03-17', '1234'),
(2, 'img2.jpg', '2022-03-17', 2, '2022-03-17', '1234'),
(3, 'img3.jpg', '2022-03-17', 2, '2022-03-17', '1234'),
(4, 'img4.jpg', '2022-03-15', 2, '2022-03-17', '1234'),
(5, 'img5.jpg', '2022-03-15', 1, '2022-03-15', '1234'),
(6, 'img6.jpg', '2022-03-15', 1, '2022-03-15', '1234'),
(7, 'img7.jpg', '2022-03-15', 1, '2022-03-15', '1234'),
(8, 'img8.jpg', '2022-03-15', 1, '2022-03-15', '1234'),
(9, 'img9.jpg', '2022-03-15', 2, '2022-03-15', '1234');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `foto_perfil`
--

DROP TABLE IF EXISTS `foto_perfil`;
CREATE TABLE IF NOT EXISTS `foto_perfil` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cedula` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` int NOT NULL,
  `ubicacion` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `foto_perfil`
--

INSERT INTO `foto_perfil` (`id`, `cedula`, `tipo`, `ubicacion`) VALUES
(1, '345345', 1, '16541141061.png'),
(2, '', 1, '16541142481.png'),
(3, '', 2, '16541142551.png'),
(4, '34234234', 1, '16541143091.png'),
(5, '34234234', 2, '16541143111.png'),
(6, '123123', 2, '16541153791.png'),
(7, '3123123', 1, '16541155381.png'),
(8, '3123123', 2, '16541155401.png'),
(9, '12312333', 1, '16541275451.png'),
(10, '12312333', 2, '16541275461.png'),
(11, '1234', 1, '16541402651.png'),
(12, '1234', 2, '16603367391.jpg'),
(13, '012345', 1, '16591351701.png'),
(14, '012345', 0, '16591351881.png'),
(15, '1234567898', 2, '16603430121.png'),
(16, '1234567898', 1, '16603430141.png'),
(17, '8577466475', 1, '16608648811.png'),
(18, '8577466475', 2, '16608648851.png');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gimnasio`
--

DROP TABLE IF EXISTS `gimnasio`;
CREATE TABLE IF NOT EXISTS `gimnasio` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `semana` int NOT NULL,
  `turno` int NOT NULL,
  `usuario` int NOT NULL,
  `fecha_ingreso` datetime NOT NULL,
  `estatus` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `gimnasio`
--

INSERT INTO `gimnasio` (`id`, `fecha`, `semana`, `turno`, `usuario`, `fecha_ingreso`, `estatus`) VALUES
(72, '2022-07-14', 28, 1, 1234, '2022-07-14 10:06:36', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `google_maps_php_mysql`
--

DROP TABLE IF EXISTS `google_maps_php_mysql`;
CREATE TABLE IF NOT EXISTS `google_maps_php_mysql` (
  `id` int NOT NULL,
  `mision` int NOT NULL,
  `durante` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `zona` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `nombre` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `direccion` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `lat` float(10,6) NOT NULL,
  `lng` float(10,6) NOT NULL,
  `pais` int NOT NULL DEFAULT '1',
  `cantidad` int NOT NULL,
  `altura` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `google_maps_php_mysql`
--

INSERT INTO `google_maps_php_mysql` (`id`, `mision`, `durante`, `zona`, `fecha`, `hora`, `nombre`, `direccion`, `lat`, `lng`, `pais`, `cantidad`, `altura`) VALUES
(7, 2, 'despegue', 'Cabecera 06', '2021-07-09', '08:15:00', 'asdasd', 'adasd', -0.937330, -80.665855, 1, 1, 100),
(8, 2, 'ascenso', 'La Pila', '2021-07-08', '02:49:00', '', '', -0.938789, -80.670319, 1, 1, 100),
(9, 2, 'despegue', 'Cabecera 06', '2021-07-03', '02:59:00', '', '', -0.917764, -80.449646, 1, 1, 100),
(10, 3, 'ascenso', 'La Pila', '2021-07-09', '23:58:00', '', '', -0.715910, -80.104950, 1, 5, 500),
(11, 4, 'Aterrizaje', 'sdfg', '2021-07-09', '00:14:00', '', '', -1.547955, -79.968994, 1, 1, 100),
(12, 2, 'Despegue', 'Cabecera 08', '2021-07-05', '08:56:00', '', '', -0.934927, -80.663193, 1, 15, 1000),
(13, 1, 'Despegue', 'sdfg', '2021-07-07', '15:44:00', '', '', -0.950289, -80.684052, 1, 15, 500),
(14, 2, 'Despegue', 'sdfg', '2021-07-07', '17:11:00', '', '', -0.943509, -80.670319, 1, 10, 100),
(15, 2, 'Despegue', 'sdfg', '2021-07-07', '17:11:00', '', '', -0.943509, -80.670319, 1, 10, 100),
(16, 2, 'Despegue', 'sdfg', '2021-07-07', '17:11:00', '', '', -0.943509, -80.670319, 1, 10, 100),
(17, 1, 'Despegue', 'Cabecera 06', '2021-07-08', '02:46:00', '', '', -0.953679, -80.689758, 1, 1, 1500),
(18, 5, 'Despegue', 'Marcador 04', '2021-09-08', '18:30:00', '', '', -0.950718, -80.684135, 1, 200, 100);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grado`
--

DROP TABLE IF EXISTS `grado`;
CREATE TABLE IF NOT EXISTS `grado` (
  `id` int NOT NULL AUTO_INCREMENT,
  `grado` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `n_grado` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `grado`
--

INSERT INTO `grado` (`id`, `grado`, `n_grado`) VALUES
(1, 'Cnrl.', ''),
(2, 'Tcrn.', ''),
(3, 'Mayo.', 'Mayor'),
(4, 'Capt.', 'Capitán'),
(5, 'Tnte.', 'Teniente'),
(6, 'Subt.', ''),
(7, 'Subm.', ''),
(8, 'Subp.', ''),
(9, 'Subs.', ''),
(10, 'Sgop.', ''),
(11, 'Sgos.', ''),
(12, 'Cbop.', ''),
(13, 'Cbos.', ''),
(14, 'Sldo.', ''),
(15, 'Civil', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horario`
--

DROP TABLE IF EXISTS `horario`;
CREATE TABLE IF NOT EXISTS `horario` (
  `id` int NOT NULL,
  `horas` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `horario`
--

INSERT INTO `horario` (`id`, `horas`) VALUES
(1, '06:00 - 07:00'),
(2, '14:00 - 15:00'),
(3, '18:30 - 19:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horario_pelu`
--

DROP TABLE IF EXISTS `horario_pelu`;
CREATE TABLE IF NOT EXISTS `horario_pelu` (
  `id` int NOT NULL AUTO_INCREMENT,
  `horas` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `horario_pelu`
--

INSERT INTO `horario_pelu` (`id`, `horas`) VALUES
(1, '07:00 - 07:30'),
(2, '07:30 - 08:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `items`
--

DROP TABLE IF EXISTS `items`;
CREATE TABLE IF NOT EXISTS `items` (
  `id` int NOT NULL,
  `item` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `peso` decimal(10,4) NOT NULL,
  `braso` decimal(10,4) NOT NULL,
  `momentum` decimal(10,4) NOT NULL,
  `capa` int NOT NULL,
  `posicion` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `items`
--

INSERT INTO `items` (`id`, `item`, `peso`, `braso`, `momentum`, `capa`, `posicion`) VALUES
(2, 'Ametralladora izquierda y caja municiÃ³n', 48.8000, 3.9800, 194.1000, 4, 5),
(3, 'Pylon interno', 28.0000, 3.8800, 108.6000, 1, 2),
(4, 'Pylon externo', 27.0000, 3.9600, 107.0000, 1, 1),
(5, 'Pylon ventral', 20.0000, 4.0800, 81.5000, 1, 3),
(6, 'Tanque Ala', 48.6000, 3.9500, 188.4000, 3, 2),
(7, 'Tanque Ventral', 48.6000, 4.2100, 196.9000, 3, 1),
(8, 'FLIR SS COMPLETO', 68.3000, 3.2600, 222.7000, 5, 1),
(9, 'FLIR SS CEU Y PCU', 12.6000, 6.1800, 77.9000, 5, 1),
(10, 'No Rack', 0.0000, 0.0000, 0.0000, 2, 2),
(11, 'No Rack', 0.0000, 0.0000, 0.0000, 2, 1),
(12, 'No Rack', 0.0000, 0.0000, 0.0000, 2, 3),
(13, 'No Store', 0.0000, 0.0000, 0.0000, 3, 1),
(14, 'No Store', 0.0000, 0.0000, 0.0000, 3, 1),
(15, 'No Store', 0.0000, 0.0000, 0.0000, 3, 1),
(16, 'SUU-20 vacio', 125.2000, 4.1200, 516.1000, 2, 3),
(17, '490 kg', 486.7000, 4.0395, 1966.0470, 6, 7),
(18, '720 kg', 738.3000, 0.0000, 2932.6750, 6, 7);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `kardex`
--

DROP TABLE IF EXISTS `kardex`;
CREATE TABLE IF NOT EXISTS `kardex` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL,
  `cantidad` int NOT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `tipo_moneda` int NOT NULL,
  `tipo_transaccion` int NOT NULL,
  `id_usuario` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_user` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `motivo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estatus` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `kardex`
--

INSERT INTO `kardex` (`id`, `fecha`, `cantidad`, `valor`, `tipo_moneda`, `tipo_transaccion`, `id_usuario`, `id_user`, `motivo`, `estatus`) VALUES
(1, '2024-09-08 11:37:09', 1, 6.00, 1, 1, '1', '1756269757', 'Compra Leche', 1),
(2, '2024-09-08 11:37:31', 1, 2.00, 1, 1, '1', '1756269757', 'Yogurt Griego', 1),
(3, '2024-09-08 11:37:53', 1, 4.00, 1, 1, '6', '1756269757', 'Salami', 1),
(4, '2024-09-14 00:02:35', 1, 4.00, 1, 1, '3', '1756269757', 'Compra Leche ,pan', 1),
(5, '2024-09-14 11:30:06', 1, 3.00, 1, 1, '3', '1756269757', 'verde', 1),
(17, '2024-09-14 23:36:49', 1, 13.00, 1, 1, '1', '1756269757', 'Pendiente', 1),
(18, '2024-09-14 23:37:30', 1, 2.25, 1, 1, '9', '1756269757', 'Leches', 1),
(19, '2025-01-02 13:31:15', 1, 3.00, 1, 1, '1', '1850108166', 'fasdad', 1),
(20, '2025-01-02 15:28:58', 1, 10.00, 1, 1, '1', '1850108166', '1FDFS', 1),
(21, '2025-01-02 19:25:56', 1, 0.40, 1, 1, '3', '1850108166', 'COMPRAR   TACO FISHER', 1),
(22, '2025-01-03 12:12:21', 1, 6.00, 1, 1, '4', '1756269757', 'Pago tiramizu', 1),
(23, '2025-01-03 19:38:19', 1, 2.00, 1, 1, '9', '1756269757', 'comprar limon', 1),
(24, '2025-01-03 20:41:07', 1, 6.00, 1, 1, '9', '1756269757', 'GRANADINA', 1),
(25, '2025-01-04 00:19:25', 1, 6.70, 1, 1, '6', '1756269757', 'PICKLES Y PAN', 1),
(26, '2025-01-05 08:49:18', 1, 3.35, 1, 1, '3', '1756269757', 'comprar mermelada', 1),
(27, '2025-01-12 22:06:09', 1, 8.92, 1, 1, '6', '302433214', 'COMPRA CEVERZA CLUB', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lugar`
--

DROP TABLE IF EXISTS `lugar`;
CREATE TABLE IF NOT EXISTS `lugar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lugar` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `lugar`
--

INSERT INTO `lugar` (`id`, `lugar`) VALUES
(1, 'Hotel'),
(2, 'Burguer');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `menu_aerotecnicos`
--

DROP TABLE IF EXISTS `menu_aerotecnicos`;
CREATE TABLE IF NOT EXISTS `menu_aerotecnicos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `desayuno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `almuerzo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `merienda` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `menu_aerotecnicos`
--

INSERT INTO `menu_aerotecnicos` (`id`, `desayuno`, `almuerzo`, `merienda`, `fecha`) VALUES
(1, 'asdfasdf', 'asdfasdf', 'asdfasdf', '2020-08-06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `menu_oficiales`
--

DROP TABLE IF EXISTS `menu_oficiales`;
CREATE TABLE IF NOT EXISTS `menu_oficiales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `desayuno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `almuerzo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `merienda` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `postre` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `menu_oficiales`
--

INSERT INTO `menu_oficiales` (`id`, `desayuno`, `almuerzo`, `merienda`, `postre`, `fecha`) VALUES
(4, 'asdasddff', 'asdasdddd', 'asdasddff', 'asdasddff', '2022-07-26'),
(5, 'asdasdr', 'asdasd', 'asdasd', 'asdasd', '2022-08-09'),
(6, 'asdasdr', 'asdasd', 'asdasd', 'asdasd', '2022-08-10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mesas`
--

DROP TABLE IF EXISTS `mesas`;
CREATE TABLE IF NOT EXISTS `mesas` (
  `id` int NOT NULL,
  `numero` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estatus` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mesas`
--

INSERT INTO `mesas` (`id`, `numero`, `estatus`) VALUES
(1, '1', 1),
(2, '2', 1),
(3, '3', 1),
(4, '4', 1),
(5, '5', 1),
(6, '6', 1),
(7, '7', 1),
(8, '8', 1),
(9, '9', 1),
(10, '10', 1),
(11, '11', 1),
(12, '12', 1),
(13, '13', 1),
(14, '14', 1),
(15, '15', 1),
(16, '16', 1),
(17, '17', 1),
(18, '18', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `misiones`
--

DROP TABLE IF EXISTS `misiones`;
CREATE TABLE IF NOT EXISTS `misiones` (
  `id` int NOT NULL,
  `mision` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `misiones`
--

INSERT INTO `misiones` (`id`, `mision`) VALUES
(1, 'Contacto'),
(2, 'Instrumentos'),
(3, 'Formaci&oacuten'),
(4, 'Nocturno'),
(5, 'Formaci&oacuten Nocturna'),
(6, 'Navegaci&oacuten Rasante'),
(7, 'Formaci&oacuten T&aacutectica'),
(8, 'Pol&iacutegono Regular'),
(9, 'Pol&iacutegono T&aacutectico'),
(10, 'Navegaci&oacuten'),
(11, 'Maniobras T&aacutecticas'),
(12, 'Tiro Aire Aire'),
(13, 'Maniobras de Combate A&eacutereo'),
(14, 'Maniobras B&aacutesicas de Combate'),
(15, 'Defensa A&eacuterea'),
(16, 'Reconocimiento');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ncdc`
--

DROP TABLE IF EXISTS `ncdc`;
CREATE TABLE IF NOT EXISTS `ncdc` (
  `id` int NOT NULL,
  `codigo` int NOT NULL,
  `n_ncdc` int NOT NULL,
  `estado` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estatus` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ncdc`
--

INSERT INTO `ncdc` (`id`, `codigo`, `n_ncdc`, `estado`, `estatus`) VALUES
(1, 21040, 40, 'DaÃ±ado tarjeta interna ', 1),
(2, 21050, 50, 'Sin Novedad', 1),
(3, 21060, 60, 'sdfsdf', 1),
(4, 21020, 20, 'Sin Novedad', 1),
(5, 21021, 21, 'asdasd', 2),
(6, 21047, 47, 'Sin Novedad', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ocurrio`
--

DROP TABLE IF EXISTS `ocurrio`;
CREATE TABLE IF NOT EXISTS `ocurrio` (
  `id` int NOT NULL,
  `fechaAmi` date NOT NULL,
  `aire` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tierra` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `lugar` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `p_ensenanza` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `gyn` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `n_cola` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `c_operativa` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `acciones` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `f_reporte` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` int NOT NULL DEFAULT '1',
  `f_anulado` datetime NOT NULL,
  `anuladoPor` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ocurrio`
--

INSERT INTO `ocurrio` (`id`, `fechaAmi`, `aire`, `tierra`, `lugar`, `descripcion`, `p_ensenanza`, `gyn`, `n_cola`, `c_operativa`, `acciones`, `f_reporte`, `status`, `f_anulado`, `anuladoPor`) VALUES
(1, '2021-06-18', '', 'Encendido', 'Hangaretas', 'En el Encendido me percate que estaba mal', 'Revisar la Shut Off', 'Tnte. Fiallos Francis', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(17, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(18, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(19, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(20, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(21, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(22, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(23, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(24, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(25, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(26, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(27, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(28, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(29, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(30, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(31, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(32, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(33, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(34, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(35, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(36, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(37, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(38, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(39, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(40, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(41, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(42, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(43, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(44, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(45, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(46, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(47, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(48, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(49, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(50, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(51, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(52, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(53, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(54, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(55, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(56, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(57, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(58, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(59, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(60, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(61, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(62, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(63, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(64, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(65, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(66, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(67, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(68, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(69, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(70, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(71, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(72, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(73, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(74, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(75, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(76, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(77, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(78, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(79, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(80, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(81, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(82, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(83, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(84, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(85, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(86, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(87, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(88, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(89, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(90, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(91, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(92, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(93, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(94, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(95, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(96, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(97, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(98, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(99, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(100, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(101, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(102, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(103, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(104, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(105, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(106, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(107, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(108, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(109, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(110, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(111, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(112, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(113, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(114, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(115, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(116, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(117, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(118, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(119, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(120, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(121, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(122, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(123, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(124, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(125, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(126, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(127, '2021-07-22', 'despegue', '', 'asdasd', 'asdasd', 'asdasd', 'asdasd', '', '', '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', ''),
(128, '2021-08-26', 'despegue', '', '3', '4', '5', '6', '1', 'instructor', '', '2021-08-26 01:26:46', 1, '0000-00-00 00:00:00', ''),
(129, '2021-08-26', 'despegue', '', '3', '4', '5', '6', '1', 'instructor', '', '2021-08-26 01:27:44', 1, '0000-00-00 00:00:00', ''),
(130, '2021-08-26', '', 'asd', 'adasd', 'landifniadlsfas', 'asdfasdfasd', 'asdads', 'asdasd', 'Instructor', '', '2021-08-26 02:03:54', 2, '2021-08-26 11:14:31', '1234');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `parentesco`
--

DROP TABLE IF EXISTS `parentesco`;
CREATE TABLE IF NOT EXISTS `parentesco` (
  `id` int NOT NULL AUTO_INCREMENT,
  `n_parentesco` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `parentesco`
--

INSERT INTO `parentesco` (`id`, `n_parentesco`) VALUES
(1, 'Primo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personas`
--

DROP TABLE IF EXISTS `personas`;
CREATE TABLE IF NOT EXISTS `personas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombres` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estatus` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `personas`
--

INSERT INTO `personas` (`id`, `nombres`, `estatus`) VALUES
(1, 'Francis Fiallos', 1),
(2, 'Yolanda Silva', 1),
(3, 'Leda Fiallos', 1),
(4, 'Emilia Gonzales', 1),
(5, 'Fabio Gonzales', 1),
(6, 'Naty Reyes', 1),
(7, 'Emilio Gonzales', 1),
(8, 'Silvia ', 1),
(9, 'Alexander Sailema', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `piscina`
--

DROP TABLE IF EXISTS `piscina`;
CREATE TABLE IF NOT EXISTS `piscina` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `semana` int NOT NULL,
  `turno` int NOT NULL,
  `usuario` int NOT NULL,
  `fecha_ingreso` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estatus` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `posicion`
--

DROP TABLE IF EXISTS `posicion`;
CREATE TABLE IF NOT EXISTS `posicion` (
  `id` int NOT NULL,
  `posicion` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `posicion`
--

INSERT INTO `posicion` (`id`, `posicion`) VALUES
(1, 'Puntos duros externos (1 y 5)'),
(2, 'Puntos duros internos (2 y 4)'),
(3, 'Punto duro ventral (3)'),
(4, 'Ametralladora Derecha'),
(5, 'Ametralladora Izquierda'),
(6, 'Flir'),
(7, 'Combustible');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

DROP TABLE IF EXISTS `producto`;
CREATE TABLE IF NOT EXISTS `producto` (
  `id` int NOT NULL,
  `codproducto` int NOT NULL AUTO_INCREMENT,
  `codbarras` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `producto` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `costo` decimal(10,0) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `precio2` decimal(10,0) NOT NULL,
  `precio3` decimal(10,0) NOT NULL,
  `existencia` int NOT NULL,
  `categoria` int NOT NULL,
  `lugar` int NOT NULL,
  `foto` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `codatributos` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estatus` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`codproducto`)
) ENGINE=InnoDB AUTO_INCREMENT=166 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`id`, `codproducto`, `codbarras`, `producto`, `costo`, `precio`, `precio2`, `precio3`, `existencia`, `categoria`, `lugar`, `foto`, `codatributos`, `estatus`) VALUES
(0, 1, '0', 'DESAYUNO CONTINENTAL', 0, 3.99, 0, 0, -137, 23, 1, 'logo.jpg', '1,2,3', 1),
(0, 2, '0', 'DESAYUNO AMERICANO ', 0, 4.99, 0, 0, -55, 23, 1, 'logo.jpg', '3,1,2', 1),
(0, 3, '0', 'DESAYUNO MONTUBIO', 0, 5.50, 0, 0, -134, 23, 1, 'logo.jpg', '25,1,2,3', 1),
(0, 4, '0', 'DESAYUNO CALIKAPE', 0, 4.99, 0, 0, -59, 23, 1, 'logo.jpg', '1,2', 1),
(0, 5, '0', 'DESAYUNO CALIKAPE XL', 0, 5.99, 0, 0, -75, 23, 1, 'logo.jpg', '1,2', 1),
(0, 6, '0', 'WAFFLES CON FRUTAS', 0, 3.99, 0, 0, -20, 24, 1, 'logo.jpg', '', 1),
(0, 7, '0', 'PANCAKES CLASICO', 0, 3.50, 0, 0, -7, 24, 1, 'logo.jpg', '', 1),
(0, 8, '0', 'ENSALADA DE FRUTAS', 0, 2.99, 0, 0, -11, 24, 1, 'logo.jpg', '', 1),
(0, 9, '0', 'CANASTA DE PAN', 0, 0.99, 0, 0, -22, 24, 1, 'logo.jpg', '', 1),
(0, 10, '0', 'TOCINO', 0, 0.99, 0, 0, -12, 24, 1, 'logo.jpg', '', 1),
(0, 11, '0', 'SALSA DE QUESO', 0, 0.99, 0, 0, -6, 24, 1, 'logo.jpg', '', 1),
(0, 12, '0', 'BISTEC DE CARNE', 0, 1.99, 0, 0, -10, 24, 1, 'logo.jpg', '', 1),
(0, 13, '0', 'PICO DE GALLO', 0, 0.99, 0, 0, -1, 24, 1, 'logo.jpg', '', 1),
(0, 14, '0', 'SANDUCHE CALIKAPHE', 0, 5.50, 0, 0, -11, 18, 1, 'logo.jpg', '4,5,6', 1),
(0, 15, '0', 'SANDUCHE CHIVITO', 0, 5.50, 0, 0, -28, 18, 1, 'logo.jpg', '4,5,6', 1),
(0, 16, '0', 'SANDUCHE SUBMARINO ', 0, 4.99, 0, 0, -11, 18, 1, 'logo.jpg', '4,5,6', 1),
(0, 17, '0', 'SANDUCHE VEGETARIANO', 0, 4.99, 0, 0, -1, 18, 1, 'logo.jpg', '4,5,6', 1),
(0, 18, '0', 'NACHO LOVERS ', 0, 3.99, 0, 0, -7, 13, 1, 'logo.jpg', '', 1),
(0, 19, '0', 'NACHO CHILI', 0, 4.50, 0, 0, -3, 13, 1, 'logo.jpg', '', 1),
(0, 20, '0', 'NACHOS BURGUR BEER', 0, 5.50, 0, 0, -7, 13, 1, 'logo.jpg', '', 1),
(0, 21, '0', 'PAN DE AJO ', 0, 1.99, 0, 0, -3, 13, 1, 'logo.jpg', '', 1),
(0, 22, '0', 'CHISTORRA', 0, 3.99, 0, 0, 0, 13, 1, 'logo.jpg', '', 1),
(0, 23, '0', 'CHICKEN BURGUER', 0, 3.99, 0, 0, -26, 1, 1, 'logo.jpg', '5,6,7,4', 1),
(0, 24, '0', 'TRIPLE CHEESE BURGUER', 0, 4.99, 0, 0, -77, 1, 1, 'logo.jpg', '5,6,7,4', 1),
(0, 25, '0', 'BIG BBQ BURGUER', 0, 4.99, 0, 0, -106, 1, 1, 'logo.jpg', '6,5,7,4', 1),
(0, 26, '0', 'XL BURGUER', 0, 5.99, 0, 0, -56, 1, 1, 'logo.jpg', '5,6,7,4', 1),
(0, 27, '0', 'CHEESE & BACON XL BURGUER', 0, 5.99, 0, 0, -148, 1, 1, 'logo.jpg', '5,6,7,4', 1),
(0, 28, '0', 'HAMBUGUESA CLASICA', 0, 3.50, 0, 0, -134, 1, 1, 'logo.jpg', '5,6,7,4', 1),
(0, 29, '0', 'EXTRA QUESO CHEEDAR', 0, 0.99, 0, 0, -5, 25, 1, 'logo.jpg', '', 1),
(0, 30, '0', 'EXTRA QUESO HIERBAS', 0, 0.99, 0, 0, -1, 25, 1, 'logo.jpg', '', 1),
(0, 31, '0', 'EXTRA QUESO FUNDIDO', 0, 1.50, 0, 0, -2, 25, 1, 'logo.jpg', '', 1),
(0, 32, '0', 'EXTRA TOCINO', 0, 0.99, 0, 0, -9, 25, 1, 'logo.jpg', '', 1),
(0, 33, '0', 'EXTRA HUEVO', 0, 0.99, 0, 0, -26, 25, 1, 'logo.jpg', '', 1),
(0, 34, '0', 'EXTRA PIÑA', 0, 0.99, 0, 0, -3, 25, 1, 'logo.jpg', '', 1),
(0, 35, '0', 'EXTRA CARNE', 0, 1.50, 0, 0, -9, 25, 1, 'logo.jpg', '', 1),
(0, 36, '0', 'EXTRA SALCHICHA PARRILLERA', 0, 0.99, 0, 0, -3, 25, 1, 'logo.jpg', '', 1),
(0, 37, '0', 'CEBOLLA CARAMELIZADA', 0, 0.99, 0, 0, -2, 25, 1, 'logo.jpg', '', 1),
(0, 38, '0', 'EXTRA CHILI', 0, 1.99, 0, 0, -3, 25, 1, 'logo.jpg', '', 1),
(0, 39, '0', 'SALCHIPAPA CONO', 0, 2.50, 0, 0, -79, 2, 1, 'logo.jpg', '5,6,7', 1),
(0, 40, '0', 'PAPAS CON POLLO CONO', 0, 2.99, 0, 0, -85, 2, 1, 'logo.jpg', '5,6,7', 1),
(0, 41, '0', 'PAPI CARNE CONO', 0, 2.99, 0, 0, -38, 2, 1, 'logo.jpg', '5,6,7', 1),
(0, 42, '0', 'SUPREMA DE POLLO', 0, 3.99, 0, 0, -101, 2, 1, 'logo.jpg', '5,6,7,8', 1),
(0, 43, '0', 'SUPREMA DE CARNE', 0, 3.99, 0, 0, -36, 2, 1, 'logo.jpg', '5,6,7,8', 1),
(0, 44, '0', 'PAPAS B&B ULTIMATE ', 0, 5.50, 0, 0, -72, 2, 1, 'logo.jpg', '5,6,7,8', 1),
(0, 45, '0', 'PAPAS CON TOCINO', 0, 3.99, 0, 0, -22, 2, 1, 'logo.jpg', '5,6,7,8', 1),
(0, 46, '0', 'PAPAS CON CHILI', 0, 3.99, 0, 0, -47, 2, 1, 'logo.jpg', '5,6,8,7,11', 1),
(0, 47, '0', 'CHILI DOG', 0, 3.50, 0, 0, -3, 16, 1, 'logo.jpg', '', 1),
(0, 48, '0', 'CLASSIC DOG', 0, 2.99, 0, 0, -2, 16, 1, 'logo.jpg', '', 1),
(0, 49, '0', '3 PROMO ALITAS', 0, 2.99, 0, 0, -20, 17, 1, 'logo.jpg', '5,6,22', 1),
(0, 50, '0', '3 ALITAS', 0, 3.50, 0, 0, -75, 17, 1, 'logo.jpg', '22,5,6', 1),
(0, 51, '0', '6 ALITAS', 0, 5.99, 0, 0, -98, 17, 1, 'logo.jpg', '6,5,22,22', 1),
(0, 52, '0', '9 ALITAS', 0, 8.99, 0, 0, -23, 17, 1, 'logo.jpg', '5,6,22,22,22', 1),
(0, 53, '0', '15 ALITAS', 0, 13.99, 0, 0, -14, 17, 1, 'logo.jpg', '5,6,22,22,22,22', 1),
(0, 54, '0', '20 ALITAS', 0, 17.99, 0, 0, -6, 17, 1, 'logo.jpg', '5,6,22,22,22,22', 1),
(0, 55, '0', '30 ALITAS', 0, 25.99, 0, 0, -7, 17, 1, 'logo.jpg', '5,6,22,22,22,22', 1),
(0, 56, '0', 'ENSALADA BURGUER BEER', 0, 5.50, 0, 0, -10, 14, 1, 'logo.jpg', '15', 1),
(0, 57, '0', 'ENSALADA FRESCA', 0, 4.50, 0, 0, -3, 14, 1, 'logo.jpg', '15', 1),
(0, 58, '0', 'ENSALADA CESAR', 0, 4.99, 0, 0, -41, 14, 1, 'logo.jpg', '15', 1),
(0, 59, '0', 'MOUSSE MARACUYA', 0, 2.50, 0, 0, -4, 19, 1, 'logo.jpg', '', 1),
(0, 60, '0', 'POSTRE DEL DIA', 0, 2.50, 0, 0, -17, 19, 1, 'logo.jpg', '', 1),
(0, 61, '0', 'TE HELADO DE LA CASA', 0, 1.50, 0, 0, -13, 20, 1, 'logo.jpg', '', 1),
(0, 62, '0', 'JUGOS', 0, 1.99, 0, 0, -55, 20, 1, 'logo.jpg', '2,9', 1),
(0, 63, '0', 'BATIDOS', 0, 1.99, 0, 0, -23, 20, 1, 'logo.jpg', '2,9', 1),
(0, 64, '0', 'GASEOSA', 0, 0.99, 0, 0, -316, 20, 1, 'logo.jpg', '9,10', 1),
(0, 65, '0', 'GASEOSA 1.2 LITRO', 0, 1.99, 0, 0, -89, 20, 1, 'logo.jpg', '10,9', 1),
(0, 66, '0', 'FUZE TEA', 0, 0.99, 0, 0, -48, 20, 1, 'logo.jpg', '', 1),
(0, 67, '0', 'AGUA ', 0, 0.99, 0, 0, -90, 20, 1, 'logo.jpg', '', 1),
(0, 68, '0', 'AGUA MINERAL', 0, 0.99, 0, 0, -20, 20, 1, 'logo.jpg', '', 1),
(0, 69, '0', 'LIMONADA', 0, 1.50, 0, 0, -47, 20, 1, 'logo.jpg', '2,9', 1),
(0, 70, '0', 'LIMONADA IMPERIAL', 0, 1.99, 0, 0, -6, 20, 1, 'logo.jpg', '2,9', 1),
(0, 71, '0', 'LIMONADA PINK', 0, 1.99, 0, 0, -110, 20, 1, 'logo.jpg', '2,9', 1),
(0, 72, '0', 'EXPRESO', 0, 1.50, 0, 0, -2, 21, 1, 'logo.jpg', '', 1),
(0, 73, '0', 'EXPRESO DOBLE', 0, 1.99, 0, 0, 0, 21, 1, 'logo.jpg', '', 1),
(0, 74, '0', 'CAFE AMERICANO', 0, 1.50, 0, 0, -95, 21, 1, 'logo.jpg', '', 1),
(0, 75, '0', 'CAPUCHINO', 0, 2.50, 0, 0, 0, 21, 1, 'logo.jpg', '', 1),
(0, 76, '0', 'MOCACHINO ', 0, 2.75, 0, 0, 0, 21, 1, 'logo.jpg', '', 1),
(0, 77, '0', 'CHOCOLATE CALIENTE', 0, 2.50, 0, 0, -11, 21, 1, 'logo.jpg', '', 1),
(0, 78, '0', 'CAFE,LECHE O AGUA AROMATICA', 0, 1.50, 0, 0, -85, 21, 1, 'logo.jpg', '', 1),
(0, 79, '0', 'CERVEZA NACIONAL', 0, 2.50, 0, 0, -52, 22, 1, 'logo.jpg', '2,9', 1),
(0, 80, '0', 'CERVEZA IMPORTADA', 0, 3.50, 0, 0, -20, 22, 1, 'logo.jpg', '2,9', 1),
(0, 81, '0', 'MICHELADA NACIONAL', 0, 3.50, 0, 0, -24, 22, 1, 'logo.jpg', '', 1),
(0, 82, '0', 'PROMO 2 MICHELADAS NACIONAL', 0, 4.99, 0, 0, -4, 22, 1, 'logo.jpg', '', 1),
(0, 83, '0', 'PROMO 2 MICHELADAS IMPORTADA', 0, 5.99, 0, 0, 0, 22, 1, 'logo.jpg', '', 1),
(0, 95, '0', 'PAN INDIVIDUAL', 0, 0.35, 0, 0, -25, 24, 1, 'logo.jpg', '', 1),
(0, 96, '0', 'PORCION DE PAPAS', 0, 2.00, 0, 0, -39, 25, 1, 'logo.jpg', '', 1),
(0, 97, '0', 'CERVEZA NACIONAL LITRO', 0, 3.99, 0, 0, -9, 22, 1, 'logo.jpg', '', 1),
(0, 98, '0', 'HUEVOS', 0, 0.99, 0, 0, -33, 24, 1, 'logo.jpg', '3', 1),
(0, 99, '0', 'JARRA TE HELADO DE LA CASA', 0, 4.99, 0, 0, -1, 20, 1, 'logo.jpg', '9', 1),
(0, 100, '0', 'JARRA LIMONADA', 0, 4.99, 0, 0, -16, 20, 1, 'logo.jpg', '2,9', 1),
(0, 101, '0', 'COMBO DESECHABLE', 0, 0.45, 0, 0, -110, 26, 1, 'logo.jpg', '', 1),
(0, 102, '0', 'QUESO INDIVIDUAL', 0, 0.50, 0, 0, -2, 24, 1, 'logo.jpg', '', 1),
(0, 103, '0', 'BOLON Y HUEVO', 0, 3.00, 0, 0, -5, 24, 1, 'logo.jpg', '', 1),
(0, 104, '', 'POLLO BBQ PLATO FUERTE', 0, 8.99, 0, 0, -7, 15, 1, 'logo.jpg', '26,27,28,29,30', 1),
(0, 105, '', 'JARRA LIMONADA PINK', 0, 5.99, 0, 0, -5, 20, 1, 'logo.jpg', '2,9', 1),
(0, 106, '', 'LOMO RES PLATO FUERTE', 0, 8.99, 0, 0, -7, 15, 2, 'logo.jpg', '26,27,28,29,30', 1),
(0, 107, '', 'CAMARONES PLATO FUERTE', 0, 8.99, 0, 0, -4, 15, 1, 'logo.jpg', '26,27,28,29,30', 1),
(0, 108, '', 'COSTILLAS PLATO FUERTE', 0, 8.99, 0, 0, -5, 15, 2, 'logo.jpg', '26,27,28,29,30', 1),
(0, 109, '0', 'FUZE TEA 1.5 LTS', 0, 2.25, 0, 0, -7, 20, 1, 'logo.jpg', '9', 1),
(0, 110, '0', 'AGUA 1 LTS', 0, 1.50, 0, 0, -15, 20, 1, 'logo.jpg', '9', 1),
(0, 111, '0', 'FUZE TEA 1 LTS', 0, 1.99, 0, 0, -18, 20, 1, 'logo.jpg', '9', 1),
(0, 112, '', 'CONTINENTAL PROMO', 0, 3.00, 0, 0, -106, 23, 2, 'logo.jpg', '1,2,3', 1),
(0, 113, '', 'FUZE TEA VIDRIO', 0, 0.99, 0, 0, -28, 20, 2, 'logo.jpg', '9', 1),
(0, 114, '', 'ENVIO BAÑOS', 0, 1.50, 0, 0, -3, 27, 2, 'logo.jpg', '', 1),
(0, 115, '', 'DESAYUNO HOTEL', 0, 0.01, 0, 0, -8, 23, 2, 'logo.jpg', '1,2,3', 1),
(0, 116, '', 'ENCEBOLLADO', 0, 3.50, 0, 0, -2, 15, 1, 'logo.jpg', '', 1),
(0, 117, '', 'FRESAS CON CREMA', 0, 1.50, 0, 0, -1, 19, 2, 'logo.jpg', '', 1),
(0, 118, '', 'JAMAICA ILIMITADA', 0, 1.50, 0, 0, -5, 20, 2, 'logo.jpg', '', 1),
(0, 119, '', 'COMPLETO', 0, 7.50, 0, 0, -24, 36, 2, 'logo.jpg', '26,27,28,29,30', 1),
(0, 120, '', 'CHULETAZO', 0, 4.99, 0, 0, -25, 36, 2, 'logo.jpg', '26,27,28,29,30', 1),
(0, 121, '', 'PATACONAZO', 0, 3.99, 0, 0, -13, 36, 2, 'logo.jpg', '26,27,28,29,30', 1),
(0, 122, '', 'CARNIVORO', 0, 4.99, 0, 0, -3, 36, 2, 'logo.jpg', '26,27,28,29,30', 1),
(0, 123, '', 'CHICKEN DELUXE', 0, 4.99, 0, 0, -5, 18, 2, 'logo.jpg', '', 1),
(0, 124, '', 'GRILLED CHICKEN', 0, 4.99, 0, 0, -2, 18, 2, 'logo.jpg', '', 1),
(0, 125, '', 'CHEESE BRISTO BURGER', 0, 4.99, 0, 0, -6, 1, 2, 'logo.jpg', '', 1),
(0, 126, '', 'CARNIVORA BURGER', 0, 5.99, 0, 0, -9, 1, 2, 'logo.jpg', '', 1),
(0, 127, '', 'CLASICA ESTUDIANTIL', 0, 1.99, 0, 0, -5, 1, 2, 'logo.jpg', '', 1),
(0, 128, '', 'EXTRA GUACAMOLE', 0, 1.50, 0, 0, 0, 25, 2, 'logo.jpg', '', 1),
(0, 129, '', 'EXTRA MADURITOS', 0, 1.99, 0, 0, 0, 25, 2, 'logo.jpg', '', 1),
(0, 130, '', 'MEGA ULTIMATE', 0, 6.50, 0, 0, -2, 2, 2, 'logo.jpg', '', 1),
(0, 131, '', 'SUPREMA DE CAMARON', 0, 3.99, 0, 0, -1, 2, 2, 'logo.jpg', '', 1),
(0, 132, '', 'PROMO PARA 2 PREMIUM', 0, 17.99, 0, 0, 0, 37, 2, 'logo.jpg', '', 1),
(0, 133, '', 'PROMO PARA 2 ', 0, 13.99, 0, 0, -7, 37, 2, 'logo.jpg', '', 1),
(0, 134, '', 'PROMO PARA COMPARTIR', 0, 24.99, 0, 0, -2, 37, 2, 'logo.jpg', '', 1),
(0, 135, '', 'HIDRATANTE', 0, 0.99, 0, 0, 0, 20, 2, 'logo.jpg', '', 1),
(0, 136, '', 'JARRA LIMONADA PINK', 0, 5.99, 0, 0, -1, 20, 2, 'logo.jpg', '', 1),
(0, 137, '', 'JARRA LIMONADA IMPERIAL', 0, 5.99, 0, 0, 0, 20, 2, 'logo.jpg', '', 1),
(0, 138, '', 'HIDRATANTE GRANDE', 0, 1.50, 0, 0, -1, 20, 2, 'logo.jpg', '', 1),
(0, 139, '', 'CHOCOLATE CALIKAPHE', 0, 2.99, 0, 0, -1, 21, 2, 'logo.jpg', '', 1),
(0, 140, '', 'MICHELADA IMPORTADA', 0, 4.50, 0, 0, 0, 22, 2, 'logo.jpg', '', 1),
(0, 141, '', 'EL INDECISO', 0, 5.99, 0, 0, -20, 38, 2, 'logo.jpg', '', 1),
(0, 142, '', 'EL GUAYACO', 0, 4.99, 0, 0, -4, 38, 2, 'logo.jpg', '', 1),
(0, 143, '', 'TIGRILLO SOLO', 0, 3.50, 0, 0, -21, 38, 2, 'logo.jpg', '', 1),
(0, 144, '', 'BOLON SOLO', 0, 2.50, 0, 0, -26, 38, 2, 'logo.jpg', '', 1),
(0, 145, '', 'PATACONAZO DESAYUNOS', 0, 1.99, 0, 0, -4, 38, 2, 'logo.jpg', '', 1),
(0, 146, '', 'TOSTADA', 0, 1.50, 0, 0, -2, 24, 2, 'logo.jpg', '', 1),
(0, 147, '', 'TOSTADA MIXTA', 0, 1.99, 0, 0, -7, 24, 2, 'logo.jpg', '', 1),
(0, 148, '', 'DESAYUNO COSTEÑO', 0, 5.50, 0, 0, -32, 23, 2, 'logo.jpg', '1,2,3,25', 1),
(0, 149, '', 'DESAYUNO CALENTADITO', 0, 5.50, 0, 0, -7, 23, 2, 'logo.jpg', '', 1),
(0, 150, '', 'EXTRA ARROZ MORO ', 0, 1.99, 0, 0, -16, 24, 2, 'logo.jpg', '', 1),
(0, 151, '', 'EXTRA PATACONES', 0, 1.50, 0, 0, -2, 24, 2, 'logo.jpg', '', 1),
(0, 152, '', 'EXTRA SALPRIETA', 0, 0.99, 0, 0, -1, 24, 2, 'logo.jpg', '', 1),
(0, 153, '', 'EXTRA CHICHARRON', 0, 1.99, 0, 0, 0, 24, 2, 'logo.jpg', '', 1),
(0, 154, '', 'EXTRA MADURITOS', 0, 1.50, 0, 0, -1, 24, 2, 'logo.jpg', '', 1),
(0, 155, '', 'DESAYUNO 2.5', 0, 2.50, 0, 0, -2, 23, 2, 'logo.jpg', '', 1),
(0, 161, '', 'MENU NAVIDAD 1', 0, 13.00, 0, 0, -5, 36, 2, 'logo.jpg', '26,27,28,29,30', 1),
(0, 162, '', 'MENU NAVIDAD 2', 0, 15.00, 0, 0, -5, 36, 2, 'logo.jpg', '26,27,28,29,30', 1),
(0, 163, '', 'MENU NAVIDAD 3', 0, 15.00, 0, 0, -5, 36, 2, 'logo.jpg', '26,27,28,29,30', 1),
(0, 164, '', 'ENVIO AFUERAS DE BAÑOS', 0, 2.50, 0, 0, -3, 27, 2, 'logo.jpg', '', 1),
(0, 165, '', 'SUPREMA DE TOCINO', 0, 3.99, 0, 0, -1, 2, 2, 'logo.jpg', '', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_aerotecnicos`
--

DROP TABLE IF EXISTS `producto_aerotecnicos`;
CREATE TABLE IF NOT EXISTS `producto_aerotecnicos` (
  `codproducto` int NOT NULL AUTO_INCREMENT,
  `producto` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `existencia` int NOT NULL,
  `usuario_id` int NOT NULL,
  `date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estatus` int DEFAULT '1',
  PRIMARY KEY (`codproducto`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `producto_aerotecnicos`
--

INSERT INTO `producto_aerotecnicos` (`codproducto`, `producto`, `descripcion`, `precio`, `existencia`, `usuario_id`, `date_add`, `estatus`) VALUES
(1, 'almuerzo', '', 8.00, 3, 12343, '2020-08-05 00:24:14', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_oficiales`
--

DROP TABLE IF EXISTS `producto_oficiales`;
CREATE TABLE IF NOT EXISTS `producto_oficiales` (
  `codproducto` int NOT NULL AUTO_INCREMENT,
  `producto` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `existencia` int NOT NULL,
  `usuario_id` int NOT NULL,
  `date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estatus` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`codproducto`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `producto_oficiales`
--

INSERT INTO `producto_oficiales` (`codproducto`, `producto`, `descripcion`, `precio`, `existencia`, `usuario_id`, `date_add`, `estatus`) VALUES
(1, 'desayuno', '', 8.00, 6, 1234, '2020-08-05 00:25:11', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor`
--

DROP TABLE IF EXISTS `proveedor`;
CREATE TABLE IF NOT EXISTS `proveedor` (
  `codproveedor` int NOT NULL,
  `nombre` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `p_apellido` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `s_apellido` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `dateadd` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estatus` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`codproveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prueba`
--

DROP TABLE IF EXISTS `prueba`;
CREATE TABLE IF NOT EXISTS `prueba` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `prueba`
--

INSERT INTO `prueba` (`id`, `nombre`) VALUES
(1, 'hola');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `residentes`
--

DROP TABLE IF EXISTS `residentes`;
CREATE TABLE IF NOT EXISTS `residentes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `c_depen` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nom` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `p_ape` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `s_ape` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cedula` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `correo` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `foto_delan` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `foto_perfil` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `certificado` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `parentesco` int NOT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clave` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estatus` int NOT NULL DEFAULT '1',
  `fecha_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `p_vez` int NOT NULL DEFAULT '1',
  `user_check` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_check` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `residentes`
--

INSERT INTO `residentes` (`id`, `c_depen`, `nom`, `p_ape`, `s_ape`, `cedula`, `correo`, `telefono`, `foto_delan`, `foto_perfil`, `certificado`, `parentesco`, `observaciones`, `clave`, `estatus`, `fecha_add`, `p_vez`, `user_check`, `fecha_check`) VALUES
(31, '1234', 'Francis1', 'asdasd1', 'Fiallos1', '1234567898', 'francis_andre94@hotmail.com', '0984452560', '', '', '', 1, '', '81dc9bdb52d04dc20036dbd8313ed055', 1, '2022-08-17 13:44:41', 2, '1234', '2022-08-17 02:08:12'),
(32, '1234', 'Francis', 'asdasd', 'Fiallos silva', '8577466475', '', '0984452560', '', '', '', 1, '', '', 1, '2022-08-18 18:20:58', 1, '', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `revision_veh`
--

DROP TABLE IF EXISTS `revision_veh`;
CREATE TABLE IF NOT EXISTS `revision_veh` (
  `id` int NOT NULL AUTO_INCREMENT,
  `revision` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `revision_veh`
--

INSERT INTO `revision_veh` (`id`, `revision`) VALUES
(1, '2018');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

DROP TABLE IF EXISTS `rol`;
CREATE TABLE IF NOT EXISTS `rol` (
  `idrol` int NOT NULL AUTO_INCREMENT,
  `rol` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`idrol`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rol`
--

INSERT INTO `rol` (`idrol`, `rol`) VALUES
(1, 'Administrador'),
(2, 'Vendedor'),
(3, 'Cliente'),
(4, 'Sistema de Seguridad'),
(5, 'Infanteria');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sis`
--

DROP TABLE IF EXISTS `sis`;
CREATE TABLE IF NOT EXISTS `sis` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cedula` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `grado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `apellidos` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `correo` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `dependencia` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cargo` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `expedicion` date NOT NULL,
  `expiracion` date NOT NULL,
  `vencimiento` date NOT NULL,
  `tipo_user` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `domicilio` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `inteligencia` int NOT NULL DEFAULT '1',
  `sin` int NOT NULL DEFAULT '1',
  `estatus` int NOT NULL DEFAULT '1',
  `token` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sis`
--

INSERT INTO `sis` (`id`, `cedula`, `grado`, `nombre`, `apellidos`, `telefono`, `correo`, `dependencia`, `cargo`, `tipo`, `expedicion`, `expiracion`, `vencimiento`, `tipo_user`, `domicilio`, `inteligencia`, `sin`, `estatus`, `token`) VALUES
(35, '1803641420', 'Cnrl.', 'Francis', 'Fiallos', '0984452560', 'fafs.1405@gmail.com', 'GRUPO DE VUELO 231', 'clc2', 'B', '2022-03-10', '2022-03-10', '2022-03-10', 'Oficial', 'ex fol', 1, 1, 1, 'b0bbf9b16c33cf10e63fe94a4d7ec32f'),
(36, '1234', '', '', '', '0984452560', '', '2', 'CLC2', '', '0000-00-00', '0000-00-00', '0000-00-00', '', 'Baños', 1, 1, 1, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sis_archivos`
--

DROP TABLE IF EXISTS `sis_archivos`;
CREATE TABLE IF NOT EXISTS `sis_archivos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cedula` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `c_depen` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` int NOT NULL,
  `direccion` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `placa` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=560 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sis_archivos`
--

INSERT INTO `sis_archivos` (`id`, `cedula`, `c_depen`, `tipo`, `direccion`, `placa`) VALUES
(501, '', '', 3, 'uploads/16541127011.png', ''),
(503, '1231234123', '', 3, 'uploads/16541112841.png', ''),
(504, '123123123', '', 3, 'uploads/16541114621.png', ''),
(505, '1231234414', '', 3, 'uploads/16541115751.png', ''),
(506, '234234', '', 3, 'uploads/16541125931.png', ''),
(507, '1233123', '', 3, 'uploads/16541125231.png', ''),
(508, '24234234', '', 3, 'uploads/16541128471.png', ''),
(513, '34234234', '', 3, 'uploads/16541143241.png', ''),
(514, '34234234', '', 4, 'uploads/16541143291.png', ''),
(519, '12312333', '', 3, 'uploads/16541275421.png', ''),
(520, '12312333', '', 4, 'uploads/16541275431.png', ''),
(548, '1234', '', 9, 'uploads/16603311401.png', 'PCY1028'),
(549, '1234', '', 10, 'uploads/16603312421.png', 'PCY1028'),
(550, '1234', '', 11, 'uploads/16603313041.png', 'PCY1028'),
(551, '1234', '', 9, 'uploads/16603312241.png', 'PCY1010'),
(552, '1234', '', 10, 'uploads/16603314771.png', 'PCY1010'),
(553, '1234', '', 10, 'uploads/16603312851.png', 'PCY1213'),
(554, '1234', '', 1, 'uploads/16603369941.png', ''),
(555, '1234567898', '', 4, 'uploads/16603430151.png', ''),
(556, '1234567898', '', 3, 'uploads/16603430171.png', ''),
(557, '1234', '', 9, 'uploads/16607512051.png', 'PCY1213'),
(558, '8577466475', '', 3, 'uploads/16608648661.png', ''),
(559, '8577466475', '', 4, 'uploads/16608648761.png', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sis_novedades`
--

DROP TABLE IF EXISTS `sis_novedades`;
CREATE TABLE IF NOT EXISTS `sis_novedades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cedula` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_novedad` int NOT NULL,
  `novedad` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_add` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_add` datetime NOT NULL,
  `status` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sis_novedades`
--

INSERT INTO `sis_novedades` (`id`, `cedula`, `tipo_novedad`, `novedad`, `user_add`, `fecha_add`, `status`) VALUES
(3, '1234', 1, 'El oficial registra novedades con la pencion de alimentos', '1234', '2022-06-16 07:13:46', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sis_personas`
--

DROP TABLE IF EXISTS `sis_personas`;
CREATE TABLE IF NOT EXISTS `sis_personas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cedula_titular` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cedula_dependiente` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `apellido` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `parentesco` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `placa` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sis_personas`
--

INSERT INTO `sis_personas` (`id`, `cedula_titular`, `cedula_dependiente`, `nombre`, `apellido`, `parentesco`, `placa`) VALUES
(76, '1234', '12345', 'francis', 'silva', 'primo', 'PCY1030');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sis_vehiculos`
--

DROP TABLE IF EXISTS `sis_vehiculos`;
CREATE TABLE IF NOT EXISTS `sis_vehiculos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cedula` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cedula_propietario` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre_propietario` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `apellido_propietario` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `placa` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `revision` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `a_tipo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `color` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estatus` int NOT NULL DEFAULT '1',
  `user_acep` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_acep` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sis_vehiculos`
--

INSERT INTO `sis_vehiculos` (`id`, `cedula`, `cedula_propietario`, `nombre_propietario`, `apellido_propietario`, `placa`, `revision`, `a_tipo`, `color`, `estatus`, `user_acep`, `fecha_acep`) VALUES
(88, '1803641420', '', '', '', 'PCy1020', '2018 o menos', 'Automovil', 'BLANCO', 2, '', '0000-00-00 00:00:00'),
(89, '1803641420', '1803641420', 'Francis', 'Fiallos silva', 'PCY-1021', '2018 o menos', 'Automovil', 'BLANCO', 3, '', '0000-00-00 00:00:00'),
(90, '1803641420', '1803641420', 'Francis', 'Fiallos silva', 'PCY1023', '2018 o menos', 'Automovil', 'BLANCO', 3, '', '0000-00-00 00:00:00'),
(91, '1803641420', '1803641420', 'Francis', 'Fiallos silva', 'PCY1024', '2018 o menos', 'Automovil', 'BLANCO', 2, '', '0000-00-00 00:00:00'),
(92, '1803641420', '1803641420', 'Francis', 'Fiallos silva', 'PCY1025', '2018 o menos', 'Automovil', 'BLANCO', 2, '', '0000-00-00 00:00:00'),
(93, '1803641420', '1803641420', 'Francis', 'Mayorga Soria ', 'PCY1030', '2018 o menos', 'Camioneta', 'BLANCO', 2, '', '0000-00-00 00:00:00'),
(94, '1234', '1803641420', 'Francis', 'Fiallos silva', 'PCY1028', '2019', 'Automovil', 'BLANCO', 2, '', '0000-00-00 00:00:00'),
(95, '1234', '1803641420', 'Francis', 'Fiallos silva', 'PCY1010', '2018 o menos', 'Camioneta', 'BLANCO1', 2, '1234', '2022-08-17 09:54:58'),
(96, '1234', '', '', '', 'PCY1013', '2018 o menos', 'Automovil', 'BLANCO', 2, '', '0000-00-00 00:00:00'),
(97, '1234', '', '', '', 'PCY1123', '2018 o menos', 'Automovil', 'BLANCO', 3, '', '0000-00-00 00:00:00'),
(98, '1234', '', '', '', 'PCY1333', '2020', 'Utilitario', 'BLANCO', 2, '1234', '2022-08-17 10:46:22'),
(99, '1234', '', '', '', 'PCY1766', '2018 o menos', 'Automovil', 'BLANCO1', 3, '1234', '2022-08-17 10:46:28'),
(100, '1234', '', '', '', 'PCY1213', '2018 o menos', 'Automovil', 'BLANCO', 1, '', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sms`
--

DROP TABLE IF EXISTS `sms`;
CREATE TABLE IF NOT EXISTS `sms` (
  `id` int NOT NULL,
  `fecha` date NOT NULL,
  `d_o` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `gyn` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sms`
--

INSERT INTO `sms` (`id`, `fecha`, `d_o`, `descripcion`, `gyn`) VALUES
(2, '0000-00-00', 'asdasdsd', 'asdasdasd', 'asdasd'),
(3, '2021-06-03', 'jahsjdhasd', 'JSdhjasd', 'asdjhasd');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes`
--

DROP TABLE IF EXISTS `solicitudes`;
CREATE TABLE IF NOT EXISTS `solicitudes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `c_depen` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_solicitud` int NOT NULL,
  `fecha_add` datetime NOT NULL,
  `fecha_check` datetime NOT NULL,
  `user_check` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `obs` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estatus` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `solicitudes`
--

INSERT INTO `solicitudes` (`id`, `c_depen`, `tipo_solicitud`, `fecha_add`, `fecha_check`, `user_check`, `obs`, `estatus`) VALUES
(4, '1234', 1, '2022-04-16 19:36:11', '2022-04-25 14:21:20', '1234', '', 4),
(5, '1234', 1, '2022-04-16 20:14:30', '2022-05-01 12:32:05', '1234', 'kjabdhjadfhjahsdf asjdfnjnasdnfijmnas dfkjasndfjnasdf ajsdnfnaiusdf jasndfiansduif ', 3),
(6, '1234', 1, '2022-04-16 20:17:39', '0000-00-00 00:00:00', '', 'Solicitud rechazada por falta de fotografia frontal', 2),
(7, '1234', 1, '2022-04-18 11:07:40', '0000-00-00 00:00:00', '', '', 4),
(8, '1234', 1, '2022-04-18 11:14:02', '0000-00-00 00:00:00', '', '', 1),
(9, '1234', 2, '2022-08-12 16:00:07', '0000-00-00 00:00:00', '', '', 3),
(10, '1234', 1, '2022-08-12 16:13:49', '2022-08-17 15:48:52', '1234', '', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_atributos`
--

DROP TABLE IF EXISTS `tipo_atributos`;
CREATE TABLE IF NOT EXISTS `tipo_atributos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codatributo` int NOT NULL,
  `tipo` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estatus` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipo_atributos`
--

INSERT INTO `tipo_atributos` (`id`, `codatributo`, `tipo`, `estatus`) VALUES
(1, 1, 'Cafe Pasado', 1),
(2, 1, 'Cafe en leche', 1),
(3, 1, 'Leche Pura', 1),
(4, 1, 'Agua', 1),
(5, 1, 'Sin Bebida', 1),
(6, 2, 'Jugo Mora', 1),
(7, 2, 'Jugo Frutilla', 1),
(8, 2, 'Jugo Papaya', 1),
(9, 2, 'Agua', 1),
(10, 2, 'Te Frio', 1),
(11, 2, 'Sin bebida', 1),
(12, 3, 'Frito Duro', 1),
(13, 3, 'Frito Suave', 1),
(14, 3, 'Revuelto normal', 1),
(15, 3, 'Revuelto Seco', 1),
(16, 3, 'Sin huevo', 1),
(17, 4, 'Sin vegetales', 1),
(18, 5, 'Sin mayonesa', 1),
(19, 6, 'Sin salsa de tomate', 1),
(20, 7, 'Sin BBQ', 1),
(21, 8, 'Sin queso liquido', 1),
(22, 9, 'Frio', 1),
(23, 9, 'Al clima', 1),
(24, 9, 'Con Hielo', 1),
(25, 10, 'Coca Cola', 1),
(26, 10, 'Fanta', 1),
(27, 10, 'Sprite', 1),
(28, 10, 'Fiora Fresa', 1),
(29, 10, 'Fiora Manzana', 1),
(30, 10, 'Inca', 1),
(31, 11, 'Sin chili', 1),
(32, 12, 'Sin pico de gallo', 1),
(33, 13, 'Sin guacamole', 1),
(34, 14, 'Bien Picante', 1),
(35, 14, 'Medio picante', 1),
(36, 14, 'No picante', 1),
(37, 15, 'Vinagreta', 1),
(38, 15, 'Salsa de Casa', 1),
(39, 15, 'Salsa Cesar', 1),
(40, 15, 'Sin aderezo', 1),
(41, 16, 'Sin queso cheddar', 1),
(42, 17, 'Sin queso hierbas', 1),
(43, 18, 'Sin tocino', 1),
(44, 19, 'Sin cebolla caramelizada', 1),
(45, 20, 'Sin salchicha', 1),
(46, 21, 'Sin Pickles', 1),
(47, 22, 'BBQ', 1),
(48, 22, 'BBQ Picante', 1),
(49, 22, 'Moztaza y miel', 1),
(50, 22, 'Maracuya', 1),
(51, 22, 'Parmesano', 1),
(52, 22, 'Sin Salsa', 1),
(53, 23, 'Sin champiñones', 1),
(54, 24, 'Bien cocido', 1),
(55, 24, '3/4', 1),
(56, 24, 'Medio', 1),
(57, 3, 'Cocinado Duro', 1),
(58, 3, 'Cocinado Tibio', 1),
(59, 25, 'Tigrillo queso', 1),
(60, 25, 'Tigrillo chicharron', 1),
(61, 25, 'Tigrillo mixto', 1),
(62, 25, 'Bolon queso', 1),
(63, 25, 'Bolon chicharron', 1),
(64, 25, 'Bolon mixto', 1),
(65, 1, 'Chocolate', 1),
(66, 26, 'Arroz y menestra', 1),
(67, 27, 'Papas Fritas', 1),
(68, 28, 'Patacones', 1),
(69, 29, 'Maduritos', 1),
(70, 30, 'Cesar', 1),
(71, 30, 'Fresca', 1),
(72, 26, 'Arroz Moro', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_comedor`
--

DROP TABLE IF EXISTS `tipo_comedor`;
CREATE TABLE IF NOT EXISTS `tipo_comedor` (
  `id` int NOT NULL AUTO_INCREMENT,
  `comedor` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estatus` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipo_comedor`
--

INSERT INTO `tipo_comedor` (`id`, `comedor`, `estatus`) VALUES
(1, 'HALCONES', 1),
(2, 'DRAGONES', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_licencia`
--

DROP TABLE IF EXISTS `tipo_licencia`;
CREATE TABLE IF NOT EXISTS `tipo_licencia` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo_lic` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipo_licencia`
--

INSERT INTO `tipo_licencia` (`id`, `tipo_lic`, `descripcion`) VALUES
(1, 'A', 'Licencia Moto');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_novedad`
--

DROP TABLE IF EXISTS `tipo_novedad`;
CREATE TABLE IF NOT EXISTS `tipo_novedad` (
  `id` int NOT NULL AUTO_INCREMENT,
  `n_novedad` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipo_novedad`
--

INSERT INTO `tipo_novedad` (`id`, `n_novedad`) VALUES
(1, 'PROBLEMAS JUDICIALES');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_solicitud`
--

DROP TABLE IF EXISTS `tipo_solicitud`;
CREATE TABLE IF NOT EXISTS `tipo_solicitud` (
  `id` int NOT NULL AUTO_INCREMENT,
  `n_solicitud` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipo_solicitud`
--

INSERT INTO `tipo_solicitud` (`id`, `n_solicitud`) VALUES
(1, 'Solicitud TCI'),
(2, 'asdasd13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_usuario`
--

DROP TABLE IF EXISTS `tipo_usuario`;
CREATE TABLE IF NOT EXISTS `tipo_usuario` (
  `id_tipouser` int NOT NULL,
  `tipo_user` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_tipouser`),
  KEY `id_tipousuario` (`id_tipouser`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipo_usuario`
--

INSERT INTO `tipo_usuario` (`id_tipouser`, `tipo_user`) VALUES
(1, 'Oficial'),
(2, 'Aerotécnico'),
(3, 'Servidor Público / Trabajador Público');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_vehiculo`
--

DROP TABLE IF EXISTS `tipo_vehiculo`;
CREATE TABLE IF NOT EXISTS `tipo_vehiculo` (
  `id` int NOT NULL,
  `t_vehiculo` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipo_vehiculo`
--

INSERT INTO `tipo_vehiculo` (`id`, `t_vehiculo`) VALUES
(1, 'Automovil');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `uso_ncdc`
--

DROP TABLE IF EXISTS `uso_ncdc`;
CREATE TABLE IF NOT EXISTS `uso_ncdc` (
  `id` int NOT NULL,
  `codigo` int NOT NULL,
  `custodio` int NOT NULL,
  `n_ncdc` int NOT NULL,
  `fecha_uso` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_entrega` datetime NOT NULL,
  `estado` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `uso_ncdc`
--

INSERT INTO `uso_ncdc` (`id`, `codigo`, `custodio`, `n_ncdc`, `fecha_uso`, `fecha_entrega`, `estado`) VALUES
(4, 21040, 1803641420, 40, '2021-03-03 23:31:37', '2021-03-03 21:45:13', 2),
(5, 21040, 1803641420, 40, '2021-03-04 02:42:43', '2021-03-03 21:45:13', 2),
(6, 21040, 1803641420, 40, '2021-03-04 02:44:59', '2021-03-03 21:45:13', 2),
(7, 21040, 1803641420, 40, '2021-03-04 02:46:14', '2021-03-03 21:46:27', 2),
(8, 21040, 1234, 40, '2021-03-04 18:44:14', '0000-00-00 00:00:00', 2),
(9, 21040, 1234, 40, '2021-03-17 03:04:54', '0000-00-00 00:00:00', 2),
(10, 21040, 1234, 0, '2021-03-17 03:09:24', '0000-00-00 00:00:00', 2),
(11, 21040, 1234, 40, '2021-03-17 03:12:28', '0000-00-00 00:00:00', 2),
(12, 21040, 1234, 40, '2021-03-17 03:14:17', '0000-00-00 00:00:00', 2),
(13, 21040, 1234, 40, '2021-03-17 03:14:54', '2021-03-16 22:15:43', 2),
(14, 21040, 401021746, 40, '2021-05-29 18:21:41', '2021-05-29 14:29:15', 2),
(15, 2121, 1234, 21, '2021-05-29 19:26:38', '2021-05-29 14:29:07', 2),
(16, 21060, 401021746, 60, '2021-05-29 19:26:50', '2021-05-29 14:29:20', 2),
(17, 21040, 401021746, 40, '2021-05-29 19:30:12', '2021-05-29 14:30:55', 2),
(18, 21040, 401021746, 40, '2021-05-29 20:40:08', '2021-05-29 16:29:17', 2),
(19, 21060, 1234, 60, '2021-05-29 20:42:58', '2021-05-29 16:29:12', 2),
(20, 21040, 1234, 40, '2021-06-01 14:53:05', '2021-06-02 21:23:58', 2),
(21, 21060, 1234, 60, '2021-06-03 01:48:42', '2021-06-02 21:41:59', 3),
(22, 21060, 1234, 60, '2021-06-03 02:48:49', '2021-06-02 21:49:26', 3),
(23, 21050, 401021746, 50, '2021-06-03 14:43:18', '2021-06-16 20:51:24', 3),
(24, 21020, 602566473, 20, '2021-06-30 20:05:10', '0000-00-00 00:00:00', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

DROP TABLE IF EXISTS `usuario`;
CREATE TABLE IF NOT EXISTS `usuario` (
  `id` int NOT NULL,
  `usuario` int NOT NULL,
  `nombre` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `apellido` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `correo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clave` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `rol` int NOT NULL,
  `lugar` int NOT NULL DEFAULT '0',
  `estatus` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`usuario`),
  KEY `usuario_ibfk_1` (`rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id`, `usuario`, `nombre`, `apellido`, `correo`, `clave`, `rol`, `lugar`, `estatus`) VALUES
(1, 1, 'CLIENTE', 'CLIENTE', '0', '1234567812345678', 1, 1, 1),
(2, 1234, 'ADM', 'FULL', '123ss4@gmail.com', '81dc9bdb52d04dc20036dbd8313ed055', 1, 1, 1),
(4, 302433214, 'Dayana', 'Colt', 'Dayana@hotmail.com', '81dc9bdb52d04dc20036dbd8313ed055', 1, 2, 1),
(0, 1756269757, 'Bella', 'Ramos', 'ejemplo@hotmail.com', '81dc9bdb52d04dc20036dbd8313ed055', 1, 2, 1),
(3, 1850108166, 'Silvia', 'Luisa', 'silvia@hotmail.com', '81dc9bdb52d04dc20036dbd8313ed055', 1, 2, 1);

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`rol`) REFERENCES `rol` (`idrol`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
