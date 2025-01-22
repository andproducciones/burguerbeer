<?php 
//session_start();
include "../conexion.php";

if(!empty($_REQUEST['code_correlativo'])){

	
	//$prueba = 59;
	//$code = base64_encode($prueba);
	//$deco = base64_decode($code);

	//echo "$code";
	//echo "$deco";
	//exit;


	//print_r($_REQUEST['correlativo']); exit;
	
	$data = $_REQUEST['code_correlativo'];
	$correlativoP = base64_decode($data);
//}

//	print_r($correlativoP); //exit;
	
	
	$query_infoproducto = mysqli_query($conection,"UPDATE detalle_factura SET estatus_dt = 2 WHERE correlativo = $correlativoP");

	if($query_infoproducto){
		echo 'EL CONSUMO DEL PRODUCTO SE REGISTRO CON EXITO';
	}else{
		echo 'NO SE REGISTRO EL CONSUMO EL PRODUCTO';
	}
	

	//$query_infoproducto = mysqli_query($conection,"SELECT dt.correlativo,dt.nofactura,p.producto,c.comedor,dt.cantidad,dt.fecha,dt.estatus_dt FROM detalle_factura dt INNER JOIN producto p ON dt.codproducto = p.codproducto INNER JOIN tipo_comedor c ON dt.comedor = c.id WHERE correlativo = $correlativoP AND estatus_dt = 1" );

//	print_r($query_infoproducto); exit;

	//$data1 = mysqli_fetch_array($query_infoproducto);
	//echo JSON_encode($data1,JSON_UNESCAPED_UNICODE);
	
	//print_r($data1);exit;
}




//$requestjson = ();

//print_r($data1);
//print_r($_REQUEST);



//echo "holaaaa";

 ?>