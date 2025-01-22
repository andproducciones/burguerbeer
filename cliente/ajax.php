<?php 

 include "../conexion.php"; 
 session_start();
if(!empty($_POST)){
		//Extraer datos del Producto para el Modal
		if($_POST['action'] == 'infoComedor'){
			 
			 //print_r($_POST);
			if($_POST['comedor'] != 0){


			$comedor = $_POST['comedor'];
			
			if($comedor == 1){
			
				$query_productos = mysqli_query($conection,"SELECT codproducto,producto FROM producto WHERE comedor = 1 AND estatus = 1");
							
				
							
							$result = mysqli_num_rows($query_productos);
							$detalleTabla = '';
							$arrayData = array();	
						
						if($result > 0){
								
							while($data = mysqli_fetch_assoc($query_productos)){

							$cadena = '<option value="0">Seleccione una opción</option>';
							
							$detalleTabla  .= '<option value="'.$data['codproducto'].'">'.$data['producto'].'</option>';
						


							

						}

							$arrayData['detalle'] = $detalleTabla;	
							//print_r($detalleTabla);
							

							echo JSON_encode($arrayData, JSON_UNESCAPED_UNICODE);
								mysqli_close($conection);
								exit;
							
						}else{
							echo 'error';
							exit;
						}
			
			}
			if($comedor == 2){
			
				$query_productos = mysqli_query($conection,"SELECT codproducto,producto FROM producto WHERE comedor = 2 AND estatus = 1");
							
				
							
							$result = mysqli_num_rows($query_productos);
							
							$detalleTabla = '';
							$arrayData = array();
						if($result > 0){
								
							while($data = mysqli_fetch_assoc($query_productos)){

							$cadena = '<option value="0">Seleccione una opción</option>';

							$detalleTabla .= '<option value="'.$data['codproducto'].'">'.$data['producto'].'</option>';

						}

							$arrayData['detalle'] = $detalleTabla;	
							
							//print_r($detalleTabla);
							

							echo JSON_encode($arrayData, JSON_UNESCAPED_UNICODE);
								//print_r($arrayData);
								mysqli_close($conection);
								exit;
							
						}else{
							echo 'error';
							exit;
						}
			
			}
			}else{				
				echo 'error';
				exit;
			}
	}


			


	if($_POST['action'] == 'addProductoDetalleComedor')
			{ //print_r($_POST);
				//exit;


				if( $_POST['producto'] != 0 && $_POST['comedor'] != 0){

					$producto = $_POST['producto'];
					$comedor = $_POST['comedor'];
					
					if($comedor == 1){
					
						$query_productos = mysqli_query($conection,"SELECT producto,existencia, precio_oficiales as precio FROM producto WHERE codproducto = $producto AND estatus = 1");
									
						//print_r($query_productos);
							
						$result = mysqli_num_rows($query_productos);
						mysqli_close($conection);
							if($result > 0){
								$data = mysqli_fetch_assoc($query_productos);
								echo JSON_encode($data,JSON_UNESCAPED_UNICODE);
								exit;
							}else{
									echo 'error';
									exit;
									}
					}

					if($comedor == 2){
					
						$query_productos = mysqli_query($conection,"SELECT producto,existencia,precio_oficiales as precio FROM producto WHERE codproducto = $producto AND estatus = 1");
									
						//print_r($query_productos);
							
						$result = mysqli_num_rows($query_productos);
						mysqli_close($conection);
							if($result > 0){
								$data = mysqli_fetch_assoc($query_productos);
								echo JSON_encode($data,JSON_UNESCAPED_UNICODE);
								exit;
							}else{
									echo 'error';
									exit;
									}
					}

					

					}else{				
						echo 'error';
						exit;

				}
			
 				

			}



			if($_POST['action'] == 'addProductoDetalle'){

			//print_r($_POST);exit;

			if(empty($_POST['producto']) || empty($_POST['cantidad']))
			{
				echo 'error';
			}else{
				$codproducto = $_POST['producto'];
				$cantidad = $_POST['cantidad'];
				$token = md5($_SESSION['idUser']);


				$query_detalle_temp = mysqli_query($conection,"CALL add_detalle_temp($codproducto,$cantidad,'$token')");
					//print_r($query_detalle_temp);exit;
					$result = mysqli_num_rows($query_detalle_temp);
					//print_r($result);exit;
					$detalleTabla = '';
					$sub_total = 0;
					$iva = 0;
					$total = 0;
					$arrayData = array();
					
					if($result > 0){


					while($data = mysqli_fetch_assoc($query_detalle_temp)){

						print_r($data);exit;
						$precioTotal = round($data['cantidad'] * $data['precio'],2);
						$sub_total = round($sub_total + $precioTotal, 2);
						$total =round($total + $precioTotal, 2);


						$detalleTabla .= '<tr>
										<td>'.$data['codproducto'].'</td>
										<td colspan="3" >'.$data['producto'].'</td>
										<td class="textcenter">'.$data['cantidad'].'</td>
										<td class="textright">'.$data['precio_venta'].'</td>
										<td class="textright">'.$precioTotal.'</td>
										<td class="">
											<a class="link_delete" href="" onclick="event.preventDefault(); del_product_detalle('.$data['correlativo'].');"><i class="far fa-trash-alt"></i></a>
											
										</td>
										</tr>';

					}

					$impuesto = round($sub_total * ($iva/100),2);
					$tl_sniva = round($sub_total - $impuesto, 2);
					$total = round($tl_sniva + $impuesto, 2);
					
					$detalleTotales ='<tr>
				<td colspan="6" class="textright">Total $</td>
				<td class="textright" id="totalVenta" value="'.$total.'">'.$total.'</td>
			</tr>';

			$arrayData['detalle'] = $detalleTabla;
			$arrayData['totales'] = $detalleTotales;
			
			echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);


					}else{
						echo 'error';

					}
					mysqli_close($conection);
			}
			exit;
			
	}
 				
if($_POST['action'] == 'addProductoDetalleVenta'){

			//print_r($_POST);exit;

			if (empty($_POST['producto']) || empty($_POST['cantidad']) && empty($_POST['fecha']) || empty($_POST['comedor']))
			{
				echo 'error';
			}else{
				//echo 'estamos bien';
			//}

				$codproducto = $_POST['producto'];
				$cantidad = $_POST['cantidad'];
				$comedor = $_POST['comedor'];
				$fecha = $_POST['fecha'];
				$token = md5($_SESSION['idUser']);


				$query_detalle_temp = mysqli_query($conection,"CALL add_detalle_compra($codproducto,$cantidad,'$fecha',$comedor,'$token')");
					//print_r($query_detalle_temp);exit;
					
					$result = mysqli_num_rows($query_detalle_temp);
					//print_r($result);exit;
					$detalleTabla = '';
					$sub_total = 0;
					$iva = 0;
					$total = 0;
					$arrayData = array();
					
					if($result > 0){


					while($data = mysqli_fetch_assoc($query_detalle_temp)){

						//print_r($data);exit;
						$precioTotal = round($data['cantidad'] * $data['precio_venta'], 2);
						$sub_total = round($sub_total + $precioTotal, 2);
						$total = round($total + $precioTotal, 2);

						//$detalleTabla2 = '<td>seleccione </td>';

						$detalleTabla .= '<tr>
										<td>'.$data['comedor'].'</td>
										<td colspan="2" >'.$data['producto'].'</td>
										<td class="textcenter">'.$data['fecha'].'</td>
										<td class="textcenter">'.$data['cantidad'].'</td>
										<td class="textright">'.$data['precio_venta'].'</td>
										<td class="textright">'.$precioTotal.'</td>
										<td class="">
											<a class="link_delete" href="" onclick="event.preventDefault(); del_product_detalle('.$data['correlativo'].');"><i class="far fa-trash-alt"></i></a>
											
										</td>
										</tr>';

						//$detalleTabla1 = ($detalleTabla2.$detalleTabla);

					}

					$impuesto = round($sub_total * ($iva/100),2);
					$tl_sniva = round($sub_total - $impuesto, 2);
					$total = round($tl_sniva + $impuesto, 2);
					
					$detalleTotales ='<tr>
				<td colspan="6" class="textright">Total $</td>
				<td class="textright" id="totalVenta" value="'.$total.'">'.$total.'</td>
			</tr>';

			$arrayData['detalle'] = $detalleTabla;
			$arrayData['totales'] = $detalleTotales;
			
			echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);

			//print_r($arrayData);exit;
					}else{
						echo 'error';

					}
					mysqli_close($conection);
			}
			exit;
			
	}
if($_POST['action'] == 'searchForDetalle'){

			//print_r($_POST);exit;
			if(empty($_POST['user']))
			{
				echo 'error';
			}else{
				
				$token = md5($_SESSION['idUser']);

				$query = mysqli_query($conection,"SELECT tmp.correlativo,tmp.token_user,tmp.cantidad,tmp.precio_venta,tmp.fecha,p.codproducto,p.producto,p.comedor FROM detalle_temp tmp INNER JOIN producto p ON tmp.codproducto = p.codproducto WHERE token_user = '$token' ");
					
				$result = mysqli_num_rows($query);
				
				//$query_detalle_temp = mysqli_query($conection,"CALL add_detalle_temp($codproducto,$cantidad,'$token')");
					//print_r($query_detalle_temp);exit;
					
					//print_r($result);exit;
					$detalleTabla = '';
					$sub_total = 0;
					$iva = 0;
					$total = 0;
					$arrayData = array();
					
					if($result > 0){


					while($data = mysqli_fetch_assoc($query)){

						//print_r($data);exit;
						$precioTotal = round($data['cantidad'] * $data['precio_venta'],2);
						$sub_total = round($sub_total + $precioTotal, 2);
						$total =round($total + $precioTotal, 2);


						$detalleTabla .= '<tr>
										<td>'.$data['comedor'].'</td>
										<td colspan="2">'.$data['producto'].'</td>
										<td class="textcenter">'.$data['fecha'].'</td>
										<td class="textcenter">'.$data['cantidad'].'</td>
										<td class="textright">'.$data['precio_venta'].'</td>
										<td class="textright">'.$precioTotal.'</td>
										<td class="">
											<a class="link_delete" href="" onclick="event.preventDefault(); del_product_detalle('.$data['correlativo'].');"><i class="far fa-trash-alt"></i></a>
											
										</td>
										</tr>';

					}

					$impuesto = round($sub_total * ($iva/100),2);
					$tl_sniva = round($sub_total - $impuesto, 2);
					$total = round($tl_sniva + $impuesto, 2);
					
					$detalleTotales ='<tr>
				<td colspan="6" class="textright">Total $</td>
				<td class="textright" id="totalVenta" value="'.$total.'">'.$total.'</td>
			</tr>';

			$arrayData['detalle'] = $detalleTabla;
			$arrayData['totales'] = $detalleTotales;
			
			echo json_encode($arrayData,JSON_UNESCAPED_UNICODE);


					}else{
						echo 'error';

					}
					mysqli_close($conection);
			}
			exit;
			
	}

	if($_POST['action'] == 'del_product_detalle'){
		if(empty($_POST['id_detalle']))
			{
				echo 'error';
			}else{
				
				$id_detalle = $_POST['id_detalle'];
				$token = md5($_SESSION['idUser']);

				$query_detalle_temp = mysqli_query($conection,"CALL del_detalle_temp($id_detalle,'$token')");
				$result = mysqli_num_rows($query_detalle_temp);
				
				//print_r($query_detalle_temp);exit;
					
					//print_r($result);exit;
					$detalleTabla = '';
					$sub_total = 0;
					$iva = 0;
					$total = 0;
					$arrayData = array();
					
					if($result > 0){


					while($data = mysqli_fetch_assoc($query_detalle_temp)){

						//print_r($data);exit;
						$precioTotal = round($data['cantidad'] * $data['precio_venta'], 2);
						$sub_total = round($sub_total + $precioTotal, 2);
						$total = round($total + $precioTotal, 2);


						$detalleTabla .= '<tr>
										<td>'.$data['comedor'].'</td>
										<td colspan="2">'.$data['producto'].'</td>
										<td class="textcenter">'.$data['cantidad'].'</td>
										<td class="textcenter">'.$data['fecha'].'</td>
										<td class="textright">'.$data['precio_venta'].'</td>
										<td class="textright">'.$precioTotal.'</td>
										<td class="">
											<a class="link_delete" href="" onclick="event.preventDefault(); del_product_detalle('.$data['correlativo'].');"><i class="far fa-trash-alt"></i></a>
											
										</td>
										</tr>';

					}

					$impuesto = round($sub_total * ($iva/100),2);
					$tl_sniva = round($sub_total - $impuesto, 2);
					$total = round($tl_sniva + $impuesto, 2);
					
					$detalleTotales ='<tr>
				<td colspan="6" class="textright">Total $</td>
				<td class="textright" id="totalVenta" value="'.$total.'">'.$total.'</td>
			</tr>';

			$arrayData['detalle'] = $detalleTabla;
			$arrayData['totales'] = $detalleTotales;
			
			echo json_encode($arrayData,JSON_UNESCAPED_UNICODE);


					}else{
						echo 'error';

					}
					mysqli_close($conection);
			}
			exit;
		//print_r($_POST);exit;

	}



if($_POST['action'] == 'procesarVenta'){

			//print_r($_POST);



			if(empty($_POST['codcliente'])){
			
				echo "error";
			
			}else{

			
			if ($_POST['credito'] > $_POST['precioVenta']){

			

			
			$codcliente = $_POST['codcliente'];
			$token 		= md5($_SESSION['idUser']);
			$usuario 	= 1;
			
			$query 	= mysqli_query($conection,"SELECT * FROM detalle_temp WHERE token_user = '$token' ");
			$result = mysqli_num_rows($query);

			if ($result > 0) {
				
				$query_procesar = mysqli_query($conection,"CALL procesar_venta_ok($usuario,$codcliente,'$token')");
				
				$result_detalle = mysqli_num_rows($query_procesar);

				if ($result_detalle > 0){
					$data = mysqli_fetch_assoc($query_procesar);
					echo json_encode($data,JSON_UNESCAPED_UNICODE);
					}else{
						echo "error";
					}
				}else{
					echo "error";
				}
				mysqli_close($conection);
				exit;
				}else{
					echo "1";
				}
			}


		}


if($_POST['action'] == 'generarQR')
			{ 
				//print_r($_POST);
				//exit;

				if(!empty($_POST)){
				//$correlativo = ;
				$arrayData = array();


				$arrayData['detalle'] = base64_encode($_POST['correlativo']);
				//$arrayData['detalle2'] = $dato;
				
				echo json_encode($arrayData,JSON_UNESCAPED_UNICODE);
				//print_r($correlativo);
				
				}else{
					echo 'error';
				}
			}

if($_POST['action'] == 'generarQR2')
			{ 
				//print_r($_POST);
				//exit;
			
   			include('factura/phpqrcode/qrlib.php'); 
    		

   			$code     = base64_encode($_POST['correlativo']);
    		$codesDir = "factura/codes/";   
    		$codeFile = date('d-m-Y-h-i-s').'.png';
    		QRcode::png($code, $codesDir.$codeFile, 'H', '6'); 
    		
    		echo '<form action="" method="post" name="form_add_product" id="form_add_product">
    				<h2>CODIGO:   '.$code.'</h2>
    				<img class="img-thumbnail" src="'.$codesDir.$codeFile.'"/><br>
				
    				<a href="#" class="btn_ok closeModal" onclick="closeModal();"><i class="fas fa-ban"></i> Cerrar</a></form>';
			} 
			exit;
			
			//else {
    		//header('location:./');
			//}
			//}










}//final del IF General

	





 //print_r($_POST);
 //exit;


?>