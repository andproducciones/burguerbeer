<?php 
//session_start();
include "../conexion.php";

if(!empty($_REQUEST['correlativo'])){

	
	//$prueba = 59;
	//$code = base64_encode($prueba);
	//$deco = base64_decode($code);

	//echo "$code";
	//echo "$deco";
	//exit;


	//print_r($_REQUEST['correlativo']); exit;
	
	$data = $_REQUEST['correlativo'];
	$correlativoP = base64_decode($data);
//}

//	print_r($correlativoP); //exit;
	
	$query_infoproducto = mysqli_query($conection,"SELECT dt.correlativo,dt.nofactura,p.producto,c.comedor,dt.cantidad,dt.fecha,dt.estatus_dt FROM detalle_factura dt INNER JOIN producto p ON dt.codproducto = p.codproducto INNER JOIN tipo_comedor c ON dt.comedor = c.id WHERE correlativo = $correlativoP AND estatus_dt = 1" );

//	print_r($query_infoproducto); exit;

	$result = mysqli_num_rows($query_infoproducto);

	if($result >  0){

	$data1 = mysqli_fetch_array($query_infoproducto);
	echo JSON_encode($data1,JSON_UNESCAPED_UNICODE);
	mysqli_close($conection);
	}else{
		echo 'Producto ya Consumido';
	}
	//print_r($data1);exit;
}




//$requestjson = ();

//print_r($data1);
//print_r($_REQUEST);



//echo "holaaaa";

 ?>