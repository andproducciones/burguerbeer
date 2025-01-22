<?php 
include "conexion.php";
		

		//$codCliente = 123433;
		//$noFactura = 17;


//$query = mysqli_query($conection,"SELECT f.nofactura, DATE_FORMAT(f.fecha, '%d/%m/%Y') as fecha, DATE_FORMAT(f.fecha,'%H:%i:%s') as  hora, f.codcliente, f.estatus,
												 //v.nombre as vendedor,
												 //cl.nombre,cl.direccion,cl.telefono
											//FROM factura f
											//INNER JOIN usuario v
											//ON f.usuario = v.usuario
											//INNER JOIN cliente cl
											//ON f.codcliente = cl.usuario_c
											//WHERE f.nofactura = $noFactura AND f.codcliente = $//codCliente  AND f.estatus != 10 ");
//$result = mysqli_fetch_assoc($query);

//print_r($result);

$noFactura = 17;

$query_productos = mysqli_query($conection,"SELECT p.producto,dt.cantidad,dt.precio_venta,(dt.cantidad * dt.precio_venta) as precio_total
														FROM factura f
														INNER JOIN detalle_factura dt
														ON f.nofactura = dt.nofactura
														INNER JOIN producto p
														ON dt.codproducto = p.codproducto
														WHERE f.nofactura = $no_factura ");


$result = mysqli_fetch_assoc($query_producto);

print_r($result);

 ?>