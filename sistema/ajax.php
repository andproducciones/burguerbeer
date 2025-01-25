<?php 

	include "../conexion.php";
	include "includes/functions.php";
	session_start();
	date_default_timezone_set('America/Guayaquil');
	mysqli_set_charset($conection, 'utf8mb4');
	//print_r($_POST);exit;
	
	if(!empty($_POST)){
		//Extraer datos del Producto para el Modal
		if($_POST['action'] == 'infoProducto')
		{
			$producto_id = $_POST['producto'];

			$query = mysqli_query($conection,"SELECT codproducto,producto,existencia,precio FROM producto WHERE codproducto = $producto_id AND estatus = 1");
			
			mysqli_close($conection);
			
			$result = mysqli_num_rows($query);
			if($result > 0){
				$data = mysqli_fetch_assoc($query);
				echo JSON_encode($data,JSON_UNESCAPED_UNICODE);
				exit;
			}
			echo 'error';
			exit;
		}

		//Añadir Productos foreach
		if($_POST['action'] == 'addProduct')
		{
		

			if(!empty($_POST['cantidad']) || !empty($_POST['precio']) || !empty($_POST['producto_id']))
			{
				$cantidad 		= $_POST['cantidad'];
				$precio		 	= $_POST['precio'];
				$producto_id	= $_POST['producto_id'];
				$usuario_id		= $_SESSION['idUser'];

				$query_insert	= mysqli_query($conection,"INSERT INTO entradas(codproducto,cantidad,precio,usuario_id)VALUES($producto_id,$cantidad,$precio,$usuario_id)");
					
				if($query_insert) {
					

					$query_upd = mysqli_query($conection,"CALL actualizar_precio_producto($cantidad,$precio,$producto_id)");
					$result_pro = mysqli_num_rows($query_upd);
					if($result_pro > 0){
						$data = mysqli_fetch_assoc($query_upd);
						$data['producto_id'] = $producto_id;	
							echo json_encode($data,JSON_UNESCAPED_UNICODE);
						exit;
					}

				}else{
					echo 'error';
				}
				mysqli_close($conection);

			}else{
					echo 'error';
			}
			exit;
		}

		if($_POST['action'] == 'addProducto')
		{
		

			if(!empty($_POST['producto']) || !empty($_POST['precio1']) || !empty($_POST['categoria']) || !empty($_POST['lugar']))
			{
				$producto 			= $_POST['producto'];
				$precio1		 	= $_POST['precio1'];
				$categoria			= $_POST['categoria'];
				$lugar				= $_POST['lugar'];

				$query_insert	= mysqli_query($conection,"INSERT INTO producto(codproducto,producto,precio,categoria,lugar,foto)VALUES('','$producto','$precio1',$categoria,$lugar,'logo.jpg')");
				mysqli_close($conection);	
				if($query_insert) { 

					echo 'ok';
					exit;
					

				}else{
					echo 3;
				}
				

			}else{
					echo 1;
					exit;
			}
			
		}
	
	
		//buscar cliente

		if($_POST['action'] == 'searchCliente')
		{


			if(!empty($_POST['cliente'])){
			
			$cedula = $_POST['cliente'];
			
			$query=mysqli_query($conection,"SELECT usuario,nombre,p_apellido,s_apellido,direccion,correo_c as correo,telefono FROM clientes WHERE usuario LIKE '$cedula' and estatus = 1");
			
			
			$result = mysqli_num_rows($query);
		
			$data = '';
			if($result > 0){
			$data = mysqli_fetch_assoc($query);
			//print_r($data);

			}else{
				$data = 0;
			}
			
			echo json_encode($data,JSON_UNESCAPED_UNICODE);

			exit;
		}
		
		}


if ($_POST['action'] == 'addProductoDetalle') {

    if (empty($_POST['producto']) || empty($_POST['cantidad']) || empty($_POST['mesa'])) {
        echo 'error';
        exit;
    }

    $codproducto = intval($_POST['producto']);
    $cantidad = intval($_POST['cantidad']);
    $mesa = intval($_POST['mesa']);
    $token = md5($_SESSION['idUser']);

    // Verificar si el producto tiene atributos
    $query_2 = mysqli_query($conection, "SELECT id FROM atributos_productos WHERE codproducto = $codproducto");
    $si = (mysqli_num_rows($query_2) > 0) ? 1 : 2;

    // Añadir detalle temporal
    $query_detalle_temp = mysqli_query($conection, "CALL add_detalle_temp($codproducto, $cantidad, '$token', $mesa, $si)");

    if ($query_detalle_temp) {
        $result = mysqli_num_rows($query_detalle_temp);

        if ($result > 0) {
            $detalleTabla = '';
            $sub_total = 0;
            $total = 0;
            $arrayData = [];

            while ($data = mysqli_fetch_assoc($query_detalle_temp)) {
                $precioTotal = round($data['cantidad'] * $data['precio_venta'], 2);
                $sub_total = round($sub_total + $precioTotal, 2);
                $total = round($total + $precioTotal, 2);

                $eliminar = ($data['preparar'] == 1) ? '<button class="btn_anular" href="" onclick="event.preventDefault(); del_product_detalle(' . $data['correlativo'] . ');"><i class="far fa-trash-alt"></i></button>' : '';
                $editar2 = ($data['preparar'] == 1) ? '<button class="btn_view" href="" onclick="event.preventDefault(); anadirForm(\'formDetalleProducto2\',' . $data['correlativo'] . ');"><i class="far fa-edit"></i></button>' : '';

                // Manejo de observaciones
                $seleccionado = '';
                $observaciones = $data['observaciones'] ?? '';
                $array = json_decode($observaciones, true);

                if (is_array($array)) {
                    $valores = array_map(function ($valor) {
                        return '<span style="font-size: 10px;">' . htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') . '</span>';
                    }, $array);
                    $seleccionado = implode(' | ', $valores);
                }

                $detalleTabla .= '<tr>
                                    <td class="textcenter">' . $data['cantidad'] . '</td>
                                    <td colspan="2" align="left"><div class="nameProduucto"><div>' . htmlspecialchars($data['producto'], ENT_QUOTES, 'UTF-8') . '</div><div style="font-size: 10px;">' . $seleccionado . '</div></div></td>
                                    <td class="textright">$ ' . number_format($data['precio_venta'], 2) . '</td>
                                    <td class="textright">$ ' . number_format($precioTotal, 2) . '</td>
                                    <td class="">' . $editar2 . $eliminar . '</td>
                                  </tr>';

                $numero = $data['numero'];
            }

            $impuesto = number_format(round($sub_total * 0.12, 2), 2); // Asumiendo un IVA del 12%
            $tl_sniva = number_format(round($sub_total - $impuesto, 2), 2);
            $total = number_format(round($tl_sniva + $impuesto, 2), 2);

            $detalleTotales = '<tr>
                                <td class="totalDatos">Total</td>
                                <td class="totalDatos">$ ' . $total . '</td>
                              </tr>
                              <tr>
                                <td class="totalDatos">Mesa:</td>
                                <td class="totalDatos">' . $numero . '</td>
                              </tr>';

            $arrayData['detalle'] = $detalleTabla;
            $arrayData['totales'] = $detalleTotales;
            $arrayData['preciofinal'] = $total;
            $arrayData['mesa'] = $numero;

            echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
        } else {
            echo 'error2';
        }
    } else {
        echo 'error';
    }
}

		

	if($_POST['action'] == 'addProductoTabla'){

			//print_r($_POST);exit;

		if (empty($_POST['code']))
		{
			echo 'error';
		}else{

			$id = $_POST['code'];

			$query = mysqli_query($conection,"SELECT * FROM producto WHERE categoria = $id");
			$result = mysqli_num_rows($query);
			$data = '';
			$detalleTabla = '';
			$arrayData = array();

			if($result > 0){

				while($data = mysqli_fetch_assoc($query)){


					$detalleTabla .= '<div class="producto productoG">
					<button type="button" class="btn1"  onclick="addproduct('.$data['codproducto'].')">
					<img src="img/productos/'. $data['foto'].'">
					<p>'. $data['producto'].'</p>
					</button>
					</div>';

				}

				$arrayData['detalle'] = $detalleTabla;
				echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);

			}else{
				echo 'error';
			}		
		}
	}

	if($_POST['action'] == 'addProductosTabla'){

		//print_r($_POST);exit;

		$query = mysqli_query($conection,"SELECT * FROM producto");
		$result = mysqli_num_rows($query);
		$data = '';
		$detalleTabla = '';
		$arrayData = array();

		if($result > 0){

			while($data = mysqli_fetch_assoc($query)){


				$detalleTabla .= '<div class="producto productoG">
				<button type="button" class="btn1"  onclick="addproduct('.$data['codproducto'].')">
				<img src="img/productos/'. $data['foto'].'">
				<p>'. $data['producto'].'</p>
				</button>
				</div>';

			}

			$arrayData['detalle'] = $detalleTabla;
			echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);

		}else{
			echo 'error';
		}		
	
}



//extraer los datos del detalle temp productos
	
if ($_POST['action'] == 'searchForDetalle') {

    if (empty($_POST['mesa'])) {
        echo 'error';
        exit;
    }

    $mesa = intval($_POST['mesa']);
    $token = md5($_SESSION['idUser']);

    // Consulta para obtener los detalles del producto en la mesa especificada
    $query = mysqli_query($conection, "
        SELECT 
            tmp.correlativo, tmp.token_user, tmp.cantidad, tmp.precio_venta, tmp.mesa, 
            p.codproducto, p.producto, tmp.preparar, tmp.observaciones 
        FROM 
            detalle_temp tmp 
        INNER JOIN 
            producto p ON tmp.codproducto = p.codproducto 
        WHERE 
            tmp.token_user = '$token' AND tmp.mesa = $mesa 
        ORDER BY 
            tmp.correlativo DESC
    ");

    if ($query) {
        $result = mysqli_num_rows($query);

        if ($result > 0) {
            $detalleTabla = '';
            $sub_total = 0;
            $iva = 12; // Asumiendo un IVA del 12%
            $total = 0;
            $arrayData = [];

            while ($data = mysqli_fetch_assoc($query)) {
                $precioTotal = round($data['cantidad'] * $data['precio_venta'], 2);
                $sub_total = round($sub_total + $precioTotal, 2);
                $total = round($total + $precioTotal, 2);

                // Botones de editar y eliminar
                $eliminar = ($data['preparar'] == 1) ? '<button class="btn_anular" onclick="event.preventDefault(); del_product_detalle(' . $data['correlativo'] . ');"><i class="far fa-trash-alt"></i></button>' : '';
                $editar2 = ($data['preparar'] == 1) ? '<button class="btn_view" onclick="event.preventDefault(); anadirForm(\'formDetalleProducto2\',' . $data['correlativo'] . ');"><i class="far fa-edit"></i></button>' : '';

                // Manejo de observaciones
                $seleccionado = '';
                if (!empty($data['observaciones'])) {
                    $observaciones = json_decode($data['observaciones'], true);
                    if (is_array($observaciones)) {
                        $valores = array_map(function ($valor) {
                            return '<span style="font-size: 10px;">' . htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') . '</span>';
                        }, $observaciones);
                        $seleccionado = implode(' | ', $valores);
                    }
                }

                $detalleTabla .= '<tr>
                                    <td class="textcenter">' . htmlspecialchars($data['cantidad'], ENT_QUOTES, 'UTF-8') . '</td>
                                    <td colspan="2" align="left"><div class="nameProduucto"><div>' . htmlspecialchars($data['producto'], ENT_QUOTES, 'UTF-8') . '</div><div style="font-size: 10px;">' . $seleccionado . '</div></div></td>
                                    <td class="textright">$ ' . number_format($data['precio_venta'], 2) . '</td>
                                    <td class="textright">$ ' . number_format($precioTotal, 2) . '</td>
                                    <td>' . $editar2 . $eliminar . '</td>
                                  </tr>';
            }

            // Cálculo de impuestos
            $impuesto = number_format(round($sub_total * ($iva / 100), 2), 2);
            $tl_sniva = number_format(round($sub_total - $impuesto, 2), 2);
            $total = number_format(round($tl_sniva + $impuesto, 2), 2);

            // Obtener número de mesa
            $query_2 = mysqli_query($conection, "SELECT numero FROM mesas WHERE id = $mesa");
            if ($query_2 && mysqli_num_rows($query_2) > 0) {
                $numero = mysqli_fetch_assoc($query_2)['numero'];

                $detalleTotales = '<tr>
                                    <td class="totalDatos">Total</td>
                                    <td class="totalDatos">$ ' . $total . '</td>
                                  </tr>
                                  <tr>
                                    <td class="totalDatos">Mesa:</td>
                                    <td class="totalDatos">' . htmlspecialchars($numero, ENT_QUOTES, 'UTF-8') . '</td>
                                  </tr>';

                $arrayData['detalle'] = $detalleTabla;
                $arrayData['totales'] = $detalleTotales;
                $arrayData['preciofinal'] = $total;
                $arrayData['mesa'] = htmlspecialchars($numero, ENT_QUOTES, 'UTF-8');

                echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
            } else {
                echo 'error';
            }
        } else {
            echo 'error';
        }
    } else {
        echo 'error';
    }

    mysqli_close($conection);
    exit;
}




// Borrar producto del detalle
if ($_POST['action'] == 'del_product_detalle') {

    if (empty($_POST['id_detalle']) || empty($_POST['mesa'])) {
        echo 'error';
        exit;
    }

    $id_detalle = intval($_POST['id_detalle']);
    $mesa = intval($_POST['mesa']);
    $token = md5($_SESSION['idUser']);

    // Llamar al procedimiento almacenado para eliminar el detalle del producto
    $query_detalle_temp = mysqli_query($conection, "CALL del_detalle_temp($id_detalle, '$token', $mesa)");

    if ($query_detalle_temp) {
        $result = mysqli_num_rows($query_detalle_temp);

        if ($result > 0) {
            $detalleTabla = '';
            $sub_total = 0;
            $iva = 12; // Asumiendo un IVA del 12%
            $total = 0;
            $arrayData = [];

            while ($data = mysqli_fetch_assoc($query_detalle_temp)) {
                $precioTotal = round($data['cantidad'] * $data['precio_venta'], 2);
                $sub_total = round($sub_total + $precioTotal, 2);
                $total = round($total + $precioTotal, 2);

                // Botones de editar y eliminar
                $eliminar = ($data['preparar'] == 1) ? '<button class="btn_anular" onclick="event.preventDefault(); del_product_detalle(' . $data['correlativo'] . ');"><i class="far fa-trash-alt"></i></button>' : '';
                $editar2 = ($data['preparar'] == 1) ? '<button class="btn_view" onclick="event.preventDefault(); anadirForm(\'formDetalleProducto2\',' . $data['correlativo'] . ');"><i class="far fa-edit"></i></button>' : '';

                // Manejo de observaciones
                $seleccionado = '';
                if (!empty($data['observaciones'])) {
                    $observaciones = json_decode($data['observaciones'], true);
                    $valores = array_map(function ($valor) {
                        return '<span style="font-size: 10px;">' . htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') . '</span>';
                    }, $observaciones);
                    $seleccionado = implode(' | ', $valores);
                }

                $detalleTabla .= '<tr>
                                    <td class="textcenter">' . $data['cantidad'] . '</td>
                                    <td colspan="2" align="left"><div class="nameProduucto"><div>' . htmlspecialchars($data['producto'], ENT_QUOTES, 'UTF-8') . '</div><div style="font-size: 10px;">' . $seleccionado . '</div></div></td>
                                    <td class="textright">$ ' . number_format($data['precio_venta'], 2) . '</td>
                                    <td class="textright">$ ' . number_format($precioTotal, 2) . '</td>
                                    <td>' . $editar2 . $eliminar . '</td>
                                  </tr>';

                $numero = $data['numero'];
            }

            // Cálculo de impuestos y totales
            $impuesto = round($sub_total * ($iva / 100), 2);
            $tl_sniva = round($sub_total - $impuesto, 2);
            $total = round($tl_sniva + $impuesto, 2);

            $detalleTotales = '<tr>
                                    <td class="totalDatos">Total</td>
                                    <td class="totalDatos">$ ' . number_format($total, 2) . '</td>
                               </tr>
                               <tr>
                                    <td class="totalDatos">Mesa:</td>
                                    <td class="totalDatos">' . htmlspecialchars($numero, ENT_QUOTES, 'UTF-8') . '</td>
                               </tr>';

            $arrayData['detalle'] = $detalleTabla;
            $arrayData['totales'] = $detalleTotales;
            $arrayData['preciofinal'] = number_format($total, 2);
            $arrayData['mesa'] = htmlspecialchars($numero, ENT_QUOTES, 'UTF-8');

            echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
        } else {
            echo 'error';
        }
    } else {
        echo 'error';
    }

    mysqli_close($conection);
    exit;
}
	//borrar credito

	//anular Venta
	
	if ($_POST['action'] == 'anularVenta') {
		// Verificar si se proporcionaron los datos necesarios
		if (empty($_POST['mesa'])) {
			echo 'error';
			exit;
		}
	
		$mesa = intval($_POST['mesa']);
		$token = md5($_SESSION['idUser']);
	
		// Ejecutar la consulta para eliminar los detalles temporales de la venta
		$query_del = mysqli_query($conection, "DELETE FROM detalle_temp WHERE token_user = '$token' AND mesa = $mesa AND preparar != 2");
	
		// Cerrar la conexión a la base de datos
		mysqli_close($conection);
	
		// Verificar si la consulta se ejecutó correctamente
		if ($query_del) {
			echo 'ok';
		} else {
			echo 'error';
		}
		exit;
	}


// Procesar venta
if ($_POST['action'] == 'procesarVenta') {

    // Validar cliente
    $codcliente = empty($_POST['codcliente']) ? 1 : intval($_POST['codcliente']);

    // Validar mesa
    $mesa = empty($_POST['mesa']) ? 'error' : intval($_POST['mesa']);

    // Validar caja
    $caja = empty($_POST['caja']) ? 'error' : intval($_POST['caja']);

    // Validar código de pago según el tipo de pago
    if ($_POST['pago'] == 2) {
        $codigopago = $_POST['codigoTarjeta'] ?? '';
    } elseif ($_POST['pago'] == 3) {
        $codigopago = $_POST['codigoTransferencia'] ?? '';
    } elseif ($_POST['pago'] == 4) {
        $codigopago = ''; // Asignar un valor único o dejarlo vacío si no se requiere código específico para "DeUna"
    } else {
        $codigopago = 1;
    }

    // Validar cupón
    $cupon = empty($_POST['cupon']) ? 1 : intval($_POST['cupon']);

    // Obtener tipo de pago y datos del usuario
    $pago = intval($_POST['pago']);
    $token = md5($_SESSION['idUser']);
    $usuario = intval($_SESSION['idUser']);

    // Verificar si hay detalles en la venta
    $query = mysqli_query($conection, "SELECT * FROM detalle_temp WHERE token_user = '$token'");

    if (mysqli_num_rows($query) > 0) {

        // Llamar al procedimiento almacenado para procesar la venta
        $query_procesar = mysqli_query($conection, "CALL procesar_venta($usuario, $codcliente, '$token', $mesa, $pago, '$codigopago', '$cupon', $caja)");

        if ($query_procesar && mysqli_num_rows($query_procesar) == 1) {
            $data = mysqli_fetch_assoc($query_procesar);

            // Validar si se imprimirá la factura
            $data["factura"] = $_POST['factura'] == 1 ? 1 : 2;

            // Validar si se imprimirán las comandas
            $data["comandas"] = $_POST['comandas'] == 1 ? 1 : 2;

            // Enviar datos de la venta como respuesta JSON
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        } else {
            echo "error";
        }
    } else {
        echo "error";
    }
}


			//print_r($_POST);exit;


//cambiar contraseña
			if($_POST['action'] == 'changePassword'){

						
						//print_r($_POST);
						//exit;

				if(!empty($_POST['passActual']) && !empty($_POST['passNuevo'])){

					$password 	= md5($_POST['passActual']);
					$newPass	= md5($_POST['passNuevo']);
					$idUser  	= $_SESSION['idUser'];



					$cod 		='';
					$msg		='';
					$arrData 	=array();

					$query_user = mysqli_query($conection,"SELECT * FROM usuario WHERE clave = '$password' and usuario = $idUser");

					$result = mysqli_num_rows($query_user);

					if ($result > 0) 
					{
					
					$query_update = mysqli_query($conection,"UPDATE usuario SET clave = '$newPass'WHERE usuario = $idUser");
						mysqli_close($conection);

					if($query_update){
						$code = '00';
						$msg = "Su contraseña se ha actualizado con éxito.";
					}else{
						$code = '2';
						$msg = "No es Posible cambiar su contraseña.";
						}
						
					}else{
							$code = '1';
							$msg = "Su contraseña actual es incorrecta.";
						
						}
						$arrData = array('cod' => $code, 'msg' => $msg);
						echo json_encode($arrData,JSON_UNESCAPED_UNICODE);

						}else{
							echo "error";
						}
						exit;				
					}





			
if($_POST['action'] == 'formCliente'){

			//print_r($_POST);exit;


	if (empty($_POST['co'])) {
	 	$btn =  '<a href="#" class="boton rojo closeModal" onclick="closeModal2();"><i class="fas fa-ban"></i> Cerrar</a>';
	 }else{
	 	$btn =  '<a href="#" class="boton rojo closeModal" onclick="closeModal3();"><i class="fas fa-ban"></i> Cerrar</a>';
	 }

			
				echo '<div class="scroll"><form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); sendDataForm();">
			                <h1><i class="fas fa-id-badge fa-3x"></i><br><br>Añadir Cliente</h1>
				<div class="alertAddProduct"></div>
				<label for="usuario_c">Número de Cédula</label>
				<input type="text" name="cedula" id="cedula">
				<label for="nombre">Nombre</label>
				<input type="text" name="nombre" id="nombre" >
				<label for="p_apellidos">Apellido Paterno</label>
				<input type="text" name="p_apellido" id="p_apellido">
				<label for="s_apellidos">Apellido Materno</label>
				<input type="text" name="s_apellido" id="s_apellido">
				<label for="correo_c">Correo</label>
				<input type="email" name="correo" id="correo" >
				<label for="direccion">Dirección</label>
				<input type="text" name="direccion" id="direccion" >
				<label for="telefono">Teléfono</label>
				<input type="text" name="telefono" id="telefono" >
				<input type="hidden" name="action" value="addCliente">
				<div class="acciones">
				<button type="submit" class="boton"><i class="fas fa-save"></i> Guardar</button>
			   '.$btn.'
			    </div>
			</form>
			                
			                </div>
		           		  ';
					

}



if($_POST['action'] == 'addCliente'){

//print_r($_POST);
//print_r($_FILES);
//exit;

	if(empty($_POST['cedula']) || empty($_POST['nombre']) || empty($_POST['p_apellido']) || empty($_POST['correo']) || empty($_POST['direccion']) || empty($_POST['telefono']))
	{
		echo 1;
		exit;
		
	}else{


		$usuario 		= $_POST['cedula'];
		$nombre 		= $_POST['nombre'];
		$p_apellido 	= $_POST['p_apellido'];
		$s_apellido 	= $_POST['s_apellido'];
		$correo 		= $_POST['correo'];
		$direccion 		= $_POST['direccion'];
		$telefono 		= $_POST['telefono'];


		$query = mysqli_query($conection,"SELECT * FROM clientes WHERE usuario = '$usuario'");

		$result = mysqli_num_rows($query);


		if($result > 0){
			echo 2;
			exit;

		}else{

			$query_insert = mysqli_query($conection,"INSERT INTO clientes(usuario,nombre,p_apellido,s_apellido,correo_c,direccion,telefono) VALUES('$usuario','$nombre','$p_apellido','$s_apellido','$correo','$direccion','$telefono')");

			if($query_insert){

				$arrayData = array();

				$arrayData['cedula'] 	= $usuario;
				$arrayData['nombre'] 	= $nombre;
				$arrayData['apellido'] 	= $p_apellido;

				echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
				exit;

			}else{
				
				echo 3;
				exit;

			}
		}


	}

}



			
if($_POST['action'] == 'formUsuario'){

			//print_r($_POST);exit;

			
			echo '<div><form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); sendDataForm();">
			                <h1><i class="fas fa-id-badge fa-3x"></i><br><br>Añadir Usuario</h1>
			                
				<label for="usuario">Cédula</label>
				<input type="number" name="usuario" id="usuario" placeholder="Cédula">
				<label for="nombre">Nombre</label>
				<input type="text" name="nombre" id="nombre" placeholder="Nombre">
				<label for="apellido">Apellido</label>
				<input type="text" name="apellido" id="apellido" placeholder="Apellido">
				<label for="correo">Correo</label>
				<input type="email" name="correo" id="correo" placeholder="Correo Electrónico">
				<label for="clave">Contraseña</label>
				<input type="password" name="clave" id="clave" placeholder="Contraseña">
				<label for="rol">Tipo de Usuario</label>
				<select name="rol" id="rol" class="notItemOne">
					<option value="">Seleccione</option>
					<option value="1">Administrador</option>
					<option value="2">Vendedor</option>
				</select>
				<label for="rol">Lugar</label>
				<select name="lugar" id="lugar" class="notItemOne">
					<option value="">Seleccione</option>
					<option value="1">Hotel</option>
					<option value="2">Burguer</option>
				</select>
				<input type="hidden" name="action" value="addUsuario">
				<button type="submit" class="btn_new"><i class="fas fa-edit"></i> Guardar</button>
			    <a href="#" class="btn_ok closeModal" onclick="closeModal2();"><i class="fas fa-ban"></i> Cerrar</a>
			</form>
			                
			                </div>
		           		  ';
					



}

if($_POST['action'] == 'addUsuario'){

//print_r($_POST);
//print_r($_FILES);
//exit;

	if(empty($_POST['usuario']) || empty($_POST['nombre']) || empty($_POST['apellido']) || empty($_POST['correo']) || empty($_POST['clave']) || empty($_POST['rol'])|| empty($_POST['lugar']))
	{
		echo 1;
		exit;
		
	}else{



		$usuario 		= $_POST['usuario'];
		$nombre 		= $_POST['nombre'];
		$apellido 		= $_POST['apellido'];
		$correo 		= $_POST['correo'];
		$clave 			= $_POST['clave'];
		$rol 			= $_POST['rol'];


		$query = mysqli_query($conection,"SELECT * FROM usuario WHERE usuario = '$usuario'");

		$result = mysqli_fetch_array($query);


		if($result > 0){
			
			echo 2;
			exit;

		}else{

			$query_insert = mysqli_query($conection,"INSERT INTO usuario(usuario,nombre,apellido,correo,clave,rol) VALUES('$usuario','$nombre','$apellido','$correo','$clave','$rol')");

			if($query_insert){

				echo 1;
				exit;

			}else{
				echo 3;
				exit;

			}
		}


	}

}

if($_POST['action'] == 'formProducto'){

			//print_r($_POST);exit;

			$query = mysqli_query($conection,"SELECT * FROM categorias");

			$result = mysqli_num_rows($query);


			if($result > 0){
				$options ='';

				while($data = mysqli_fetch_assoc($query)){

					$options .= '<option value="'.$data['id'].'">'.$data['categoria'].'</option>';
				}

			}
			
			echo '<div><form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); sendDataForm();">
			                <h1><i class="fas fa-id-badge fa-2x"></i><br>Añadir Producto</h1>
			                
				<label for="producto">Nombre del Producto</label>
				<input type="text" name="producto" id="producto">
				
				<label for="precio">Costo</label>
				<input type="number" step="0.01" name="costo" id="costo">

				<label for="precio">PVP 1</label>
				<input type="number" step="0.01" name="precio1" id="precio1" >

				<label for="precio">PVP 2</label>
				<input type="number" step="0.01" name="precio2" id="precio2" >

				<label for="precio">PVP 3</label>
				<input type="number" step="0.01" name="precio3" id="precio3" >

				<label for="precio">Categoría</label>
				<select name="categoria" id="categoria" class="notItemOne">
					<option value="">Seleccione</option>
					'.$options.'
				</select>
				<label for="rol">Lugar</label>
				<select name="lugar" id="lugar" class="notItemOne">
					<option value="">Seleccione</option>
					<option value="1">Hotel</option>
					<option value="2">Burguer</option>
				</select>
				<input type="hidden" name="action" value="addProducto">
				<button type="submit" class="btn_new"><i class="fas fa-edit"></i> Guardar</button>
			    <a href="#" class="btn_ok closeModal" onclick="closeModal2();"><i class="fas fa-ban"></i> Cerrar</a>
			</form>
			                
			                </div>
		           		  ';
					



}

if($_POST['action'] == 'formAnadirAtributo'){

			//print_r($_POST);exit;

			$id = $_POST['co'];

			$query = mysqli_query($conection,"SELECT * FROM atributos_productos");

			$result = mysqli_num_rows($query);


			if($result > 0){
				$atributos ='';

				while($data = mysqli_fetch_assoc($query)){

					$atributos .= '<option value="'.$data['id'].'">'.$data['atributo'].'</option>';
				}

			}

$query_producto = mysqli_query($conection, "SELECT
                                    p.producto,
                                    p.codatributos
                                FROM
                                    producto p
                                WHERE
                                    p.codproducto = $id");

$result_producto = mysqli_num_rows($query_producto);


if ($result_producto == 1) {
    $data_producto = mysqli_fetch_assoc($query_producto);

    $producto       = $data_producto['producto'];
    $codatributos   = $data_producto['codatributos'];

    
    if (!empty($codatributos)) {
        $ids = explode(",", $codatributos);
        $nombre_atributos = '';

        $num_atributos = count($ids);
        $counter = 0;
        $i = 1;
        $atributos3 = '';

        foreach ($ids as $id3) {
            $query_1 = mysqli_query($conection, "SELECT * FROM atributos_productos WHERE id = $id3");
            $result_1 = mysqli_num_rows($query_1);

            if ($result_1 > 0) {
                $data_atributos = mysqli_fetch_assoc($query_1);
                $nombre_atributos .= $data_atributos['atributo'];


                $atributos3 .= '<div id="nuevoAtributo">
            <label for="atributo">Atributo '.$i++.'</label>
            <select name="atributo[]" class="notItemOne">
                <option value="'.$id3.'">'.$data_atributos['atributo'].'</option>
               '.$atributos.'
            </select>
            <button type="button" class="btn_anular" onclick="eliminarAtributo(this)"><i class="fas fa-trash-alt"></i></button>
        </div>';

                // Añadir coma si no es el último atributo
                if ($counter < $num_atributos - 1) {
                    $nombre_atributos .= ', ';
                }

                $counter++;
            }
        }
    } else {
        $nombre_atributos = 'Sin atributos';
        $atributos3 = '';
    }
}

			
			
			echo '<div> <form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); sendDataForm();">
        <h1><i class="fas fa-plus fa-2x"></i><br>Añadir Atributo</h1>
        <h2>'.$producto.'</h2>
        <span>'.$nombre_atributos.'</span>
        <br><br>
        
        <div id="atributosContainer">
            '.$atributos3.'
        </div>
        <div id="nuevoAtributo" style="display: none;">
            <label for="atributo">Atributo 1</label>
            <select name="atributo[]" class="notItemOne">
                <option value="">Seleccione</option>
               '.$atributos.'
            </select>
            <button type="button" class="btn_anular" onclick="eliminarAtributo(this)"><i class="fas fa-trash-alt"></i></button>
        </div>

        <button type="button" class="btn_view" onclick="agregarAtributo()"><i class="fas fa-plus"></i> Agregar Atributo</button>


        <input type="hidden" name="action" value="addAtributoProducto">
        <input type="hidden" name="co" value="'.$id.'">
        <div class="acciones">
            <button type="submit" class="btn_new"><i class="fas fa-edit"></i> Guardar</button>
            <a href="#" class="btn_ok closeModal" onclick="resetContador(); closeModal2();"><i class="fas fa-ban"></i> Cerrar</a>
        </div>
    </form>
			                
			                </div>
		           		  ';
}

if($_POST['action'] == 'formAddTipo'){

			//print_r($_POST);exit;

			$id = $_POST['co'];

			$query = mysqli_query($conection,"SELECT * FROM tipo_atributos");

			$result = mysqli_num_rows($query);


			if($result > 0){
				$tipos ='';

				while($data = mysqli_fetch_assoc($query)){

					$tipos .= '<option value="'.$data['id'].'">'.$data['tipo'].'</option>';
				}

			}

$query_producto = mysqli_query($conection, "SELECT
                                    atributo
                                FROM
                                    atributos_productos 
                                WHERE
                                    id = $id");

$result_producto = mysqli_num_rows($query_producto);


if ($result_producto == 1) {
    
    $data_producto = mysqli_fetch_assoc($query_producto);
    
    $atributo       = $data_producto['atributo'];


        $nombres_tipo = '';
        $counter = 0;
        $i = 1;
        $tipos3 = '';

            $query_1 = mysqli_query($conection, "SELECT * FROM tipo_atributos WHERE codatributo = $id");
            $result_1 = mysqli_num_rows($query_1);

            if ($result_1 > 0) {


                while($data_tipo = mysqli_fetch_assoc($query_1)){

               	$id2 				= $data_tipo['id'];
                
                $nombres_tipo 	.= $data_tipo['tipo'];
                
                $tipos3 .= '<div id="nuevoAtributo">
            <label for="atributo">Tipo '.$i++.'</label>
            <select name="atributo[]" class="notItemOne">
                <option value="'.$id2.'">'.$data_tipo['tipo'].'</option>
               '.$tipos.'
            </select>
            <button type="button" class="btn_anular" onclick="eliminarAtributo(this)"><i class="fas fa-trash-alt"></i></button>
        </div>';

                // Añadir coma si no es el último atributo
                if ($counter < $result_1 - 1) {
                    $nombres_tipo .= ', ';
                }



                $counter++;

                }
            



            }
      

}

			
			
			echo '<div> <form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); sendDataForm();">
        <h1><i class="fas fa-plus fa-2x"></i><br>Añadir Tipo Atributo</h1>
        <h2>'.$atributo.'</h2>
        <span>'.$nombres_tipo.'</span>
        <br><br>
        
        <div id="atributosContainer">
            '.$tipos3.'
        </div>
        <div id="nuevoAtributo" style="display: none;">
            <label for="atributo">Tipo 1</label>
            <select name="atributo[]" class="notItemOne">
                <option value="">Seleccione</option>
               '.$tipos.'
            </select>
            <button type="button" class="btn_anular" onclick="eliminarAtributo(this)"><i class="fas fa-trash-alt"></i></button>
        </div>

        <button type="button" class="btn_view" onclick="agregarAtributo()"><i class="fas fa-plus"></i> Agregar Tipo</button>


        <input type="hidden" name="action" value="addTipoAtributo">
        <input type="hidden" name="co" value="'.$id.'">
        <div class="acciones">
            <button type="submit" class="btn_new"><i class="fas fa-edit"></i> Guardar</button>
            <a href="#" class="btn_ok closeModal" onclick="resetContador(); closeModal2();"><i class="fas fa-ban"></i> Cerrar</a>
        </div>
    </form>
			                
			                </div>
		           		  ';
}



if($_POST['action'] == 'formEditarProducto'){

			//print_r($_POST);exit;

			
			$id = $_POST['co'];

			$query_producto = mysqli_query($conection,"SELECT
								    p.producto,
								    p.costo,
								    p.precio,
								    p.precio2,
								    p.precio3,
								    p.categoria AS idCategoria,
								    p.lugar AS idLugar,
								    c.categoria,
								    l.lugar
								FROM
								    producto p
								INNER JOIN
								    categorias c ON p.categoria = c.id
								INNER JOIN
								    lugar l ON p.lugar = l.id
								WHERE
								    p.codproducto = $id");

			$result_producto = mysqli_num_rows($query_producto);


			if($result_producto == 1){
				

				$data_producto = mysqli_fetch_assoc($query_producto);

				$producto 	= $data_producto['producto'];
				$costo 		= $data_producto['costo'];
				$precio 	= $data_producto['precio'];
				$precio2 	= $data_producto['precio2'];
				$precio3 	= $data_producto['precio3'];
				$options2 	= '<option value="'.$data_producto['idCategoria'].'">'.$data_producto['categoria'].'</option>';
				$options3 	= '<option value="'.$data_producto['idLugar'].'">'.$data_producto['lugar'].'</option>';
				

			}
			

			$query = mysqli_query($conection,"SELECT * FROM categorias");

			$result = mysqli_num_rows($query);


			if($result > 0){
				$options ='';

				while($data = mysqli_fetch_assoc($query)){

					$options .= '<option value="'.$data['id'].'">'.$data['categoria'].'</option>';
				}

			}
			
			echo '<div><form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); sendDataForm();">
			                <h1><i class="fas fa-edit fa-2x"></i><br>Editar Producto</h1>
			                
				<label for="producto">Nombre del Producto</label>
				<input type="text" name="producto" id="producto" value="'.$producto.'">
				
				<label for="precio">Costo</label>
				<input type="number" step="0.01" name="costo" id="costo" value="'.$costo.'">

				<label for="precio">PVP 1</label>
				<input type="number" step="0.01" name="precio1" id="precio1" value="'.$precio.'" >

				<label for="precio">PVP 2</label>
				<input type="number" step="0.01" name="precio2" id="precio2" value="'.$precio2.'">

				<label for="precio">PVP 3</label>
				<input type="number" step="0.01" name="precio3" id="precio3" value="'.$precio3.'">

				<label for="precio">Categoría</label>
				<select name="categoria" id="categoria" class="notItemOne">
					'.$options2.'
					'.$options.'
				</select>
				<label for="rol">Lugar</label>
				<select name="lugar" id="lugar" class="notItemOne">
					'.$options3.'
					<option value="1">Hotel</option>
					<option value="2">Burguer</option>
				</select>
				<input type="hidden" name="action" value="editarProducto2">
				<input type="hidden" name="co" value="'.$id.'">
				<button type="submit" class="btn_new"><i class="fas fa-edit"></i> Guardar</button>
			    <a href="#" class="btn_ok closeModal" onclick="closeModal2();"><i class="fas fa-ban"></i> Cerrar</a>
			</form>
			                
			                </div>
		           		  ';
					



}

 if ($_POST['action'] == 'facturarVenta') {

 	//print_r($_POST);exit;



    // Obtener los datos enviados desde el formulario
    $mesa = isset($_POST['mesa']) ? intval($_POST['mesa']) : 0;
    $final = isset($_POST['final']) ? number_format(floatval($_POST['final']), 2) : '0.00';
    $dividirBtn = $_POST['dividirBtn'];

    
    	

    // Verificar si el nombre del cliente está vacío
    if (empty($_POST['nom'])) {
        $nombre = '';
        $apellido = '';
        $cedula = '';
        $cliente = '<h4>Consumidor Final</h4>';
    } else {
        $nombre = htmlspecialchars($_POST['nom'], ENT_QUOTES, 'UTF-8');
        $apellido = htmlspecialchars($_POST['ape'], ENT_QUOTES, 'UTF-8');
        $cedula = htmlspecialchars($_POST['ce'], ENT_QUOTES, 'UTF-8');
        $cliente = '<h4>' . $nombre . ' ' . $apellido . '</h4><h4>' . $cedula . '</h4>';
    }

    $dividir = "'formDividirCuentas'";

    if ($dividirBtn == 1 AND $mesa >= 0) {
    $btn = '<button type="button" class="boton verde" onclick="event.preventDefault(); anadirForm2('.$dividir.',' . $mesa . ');"><i class="fas fa-cash-register"></i>Dividir</button>';
    	
    }else{

    $btn = "";
    	}



    // Construir el formulario HTML
    echo '<div class="scroll">
            <form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); sendDataForm();">
                <h1><i class="fas fa-clipboard-list fa-2x"></i><br><br>Procesar Mesa #' . $mesa . '</h1>
                <h2>Datos para el Pedido</h2>
                <div class="pagos aligncenter clientePagos">
                    <i class="fas fa-user fa-2x"></i>
                    <div class="datosCliente" style="display:none">' . $cliente . '</div>
                    <div class="datosCliente">
                        <input type="text" name="nombreCliente" id="nombreCliente">
                    </div>
                </div>

                <h2>Método de Pago</h2>
                <div class="pagos pagos2">
                    <input onclick="seleccionarPago(1);" type="radio" name="pago" class="pago" value="1" checked>
                    <label for="efectivo">Efectivo</label>

                    <input onclick="seleccionarPago(2);" type="radio" name="pago" class="pago" value="2">
                    <label for="tarjeta">Tarjeta</label>

                    <input onclick="seleccionarPago(3);" type="radio" name="pago" class="pago" value="3">
                    <label for="transferencia">Transferencia</label>

                    <input onclick="seleccionarPago(4);" type="radio" name="pago" class="pago" value="4">
                    <label for="deuna">DeUna</label>
                </div>

                <div id="Transferencia" class="pagos pagos2" style="display:none">
                    <label for="codigoTransferencia"># de Documento</label>
                    <input type="number" name="codigoTransferencia" id="codigoTransferencia">
                </div>

                <div class="divDescuento">
                    <h2>Código de Descuento</h2>
                    <div class="pagos pagos2">
                        <input type="text" name="cupon" id="cupon" placeholder="Ingresa Aquí">
                        <button type="button" class="btn_new btn_facturar_venta btn_aplicar" onclick="codigoPromocional();">Aplicar</button>
                    </div>
                    <h4 id="descripcionCortersia"></h4>
                </div>

                <div id="Tarjeta" class="pagos pagos2" style="display:none">
                    <label for="codigoTarjeta"># Boucher</label>
                    <input type="number" name="codigoTarjeta" id="codigoTarjeta">
                </div>

                <div class="pagos pagos2 block">
                    <div class="preciosFinal"><h2>Entrega</h2><input type="number" name="entrega" id="entrega" step="0.01" onkeyup="calcular2();" style="width: 120px"></div>
                    <div class="preciosFinal"><h2>Subtotal</h2><h2 id="subtotal">$ ' . $final . '</h2></div>
                    <div class="preciosFinal"><h2>Descuento</h2><h2 id="descuento">$ 0.00</h2></div>
                    <div class="preciosFinal"><h2>Total</h2><h2 id="total">$ ' . $final . '</h2></div>
                    <div class="preciosFinal"><h2>Cambio</h2><h2 id="cambio">$ 0.00</h2></div>
                </div>
                 
                <input type="hidden" id="totalCalcular" value="' . $final . '">
                <input type="hidden" name="id_cupon" id="id_cupon" value="">

                <h2>Imprimir Comprobantes</h2>
                <div class="pagos pagos2">
                    <input type="checkbox" name="facturaImpresa" id="facturaImpresa" class="pago" value="1" checked>
                    <h4>Factura</h4>

                    <input type="checkbox" name="comandasImpresa" id="comandasImpresa" class="pago" value="1" checked>
                    <h4>Comandas</h4>
                </div>

                <div class="acciones">
                    <button type="button" class="boton verde" onclick="event.preventDefault(); facturarVenta();"><i class="fas fa-cash-register"></i>Facturar</button>

                    ' . $btn . '
                    

                    
                    

                    <a href="#" class="boton rojo closeModal" onclick="closeModal();"><i class="fas fa-ban"></i>Cerrar</a>
                </div>
            </form>
        </div>';
}

if ($_POST['action'] == 'formDividirCuentas') {

	//print_r($_POST);exit;

    // Obtener los datos enviados desde el formulario
    $mesa = isset($_POST['co']) ? intval($_POST['co']) : 0;
    $idUser = md5($_SESSION['idUser']); // Usuario actual

    // Obtener los productos asociados a la mesa
    $productos = [];
    $query_productos = mysqli_query($conection, "
        SELECT tmp.correlativo, tmp.codproducto, tmp.cantidad, tmp.precio_venta, p.producto 
        FROM detalle_temp tmp 
        INNER JOIN producto p ON tmp.codproducto = p.codproducto 
        WHERE tmp.mesa = $mesa AND tmp.token_user = '$idUser'
    ");
    while ($row = mysqli_fetch_assoc($query_productos)) {
        $productos[] = $row;
    }

    // Verificar si hay productos
    if (count($productos) == 0) {
        echo '<h4>No hay productos para dividir en esta mesa.</h4>';
        return;
    }

    $action3 = "'formCliente'";

// Construir el formulario para dividir la cuenta
echo '<div class="scrollDividir">
    <form action="" method="post" name="form_dividir_cuentas" id="form_dividir_cuentas" class="form_dividir_cuentas" onsubmit="event.preventDefault(); procesarDivisionCuenta();">
        <h1><i class="fas fa-clipboard-list fa-2x"></i><br><br>Dividir Cuenta de Mesa #' . $mesa . '</h1>

        <!-- Datos del Cliente -->
        <div class="info-cliente">
            <h2>Datos del Cliente</h2>
            
                <div class="campo campo-cedula">
                    <label for="cedula_cliente">Cédula:</label>
                    <select name="cedula_cliente" id="cedula_cliente" onchange="event.preventDefault(); buscarCliente();">
                        <option value="">Seleccione</option>';
                        echo buscarCliente();
                    echo '</select>
                    <button type="button" class="boton azul" onclick="anadirForm3('.$action3.',1);" style="margin-left: 10px"><i class="fas fa-user-plus"></i> Crear Cliente</button>
                </div>
                <div class="crear-cliente-boton">
                    
                </div>
            
        </div>

        <h2>Método de Pago</h2>
        <div class="pagos pagos2">
            <input onclick="seleccionarPago2(1);" type="radio" name="pago" class="pago" value="1" checked>
            <label for="efectivo">Efectivo</label>

            <input onclick="seleccionarPago2(2);" type="radio" name="pago" class="pago" value="2">
            <label for="tarjeta">Tarjeta</label>

            <input onclick="seleccionarPago2(3);" type="radio" name="pago" class="pago" value="3">
            <label for="transferencia">Transferencia</label>

            <input onclick="seleccionarPago2(4);" type="radio" name="pago" class="pago" value="4">
            <label for="deuna">DeUna</label>
        </div>
        <div id="Transferencia" class="pagos pagos2" style="display:none">
            <label for="codigoTransferencia2"># de Documento</label>
            <input type="number" name="codigoTransferencia2" id="codigoTransferencia">
        </div>

        <div class="divDescuento">
            <h2>Código de Descuento</h2>
            <div class="pagos pagos2">
                <input type="text" name="cupon2" id="cupon2" placeholder="Ingresa Aquí">
                <button type="button" class="btn_new btn_facturar_venta btn_aplicar" onclick="codigoPromocional();">Aplicar</button>
            </div>
            <h4 id="descripcionCortersia"></h4>
        </div>

        <div id="Tarjeta" class="pagos pagos2" style="display:none">
            <label for="codigoTarjeta"># Boucher</label>
            <input type="number" name="codigoTarjeta" id="codigoTarjeta">
        </div>

        <h2>Selecciona los productos para la nueva factura</h2>
        <div class="productos-dividir">';

        // Mostrar los productos con checkboxes
        foreach ($productos as $producto) {
            echo '<div class="producto-seleccion">
                    <span class="cantidad">' . $producto['cantidad'] . '</span>
                    <span class="descripcion">' . $producto['producto'] . '</span>
                    <span class="precio-producto">$' . number_format($producto['precio_venta'], 2) . '</span>
                    <input type="checkbox" name="productos_seleccionados[]" value="' . $producto['correlativo'] . '" class="producto-checkbox" data-precio="' . $producto['precio_venta'] . '" data-cantidad="' . $producto['cantidad'] . '">
                  </div>';
        }

echo '    </div>

        <div class="preciosFinal">
            <h2>Total de la Nueva Factura:</h2>
            <h2 id="totalDividir">$ 0.00</h2>
        </div>

        <input type="hidden" id="totalDividirCalcular" value="0.00">
        <input type="hidden" name="mesa" id="mesa" value="' . $mesa . '">

        <div class="imprimir-comprobantes">
            <h2>Imprimir</h2>
            <input type="checkbox" id="imprimir_factura" name="imprimir_factura" checked>
            <label for="imprimir_factura">Factura</label>
        </div>
        <div class="acciones">
            <button type="submit" class="boton verde"><i class="fas fa-cash-register"></i> Crear Nueva Factura</button>
            <a href="#" class="boton rojo closeModal" onclick="closeModal2();"><i class="fas fa-ban"></i> Cerrar</a>
        </div>
    </form>
</div>';

}

if ($_POST['action'] == 'procesarDivisionCuenta') {

	//print_r($_POST);exit;

    // Obtener los datos enviados desde la solicitud AJAX
    $mesa = isset($_POST['mesa']) ? intval($_POST['mesa']) : 0;
    $productosSeleccionados = isset($_POST['productos']) ? $_POST['productos'] : [];

    // Validar que haya una mesa válida y productos seleccionados
    if ($mesa == 0 || empty($productosSeleccionados)) {
        echo json_encode(['status' => 'error', 'message' => 'No se proporcionó mesa o productos seleccionados']);
        return;
    }

    // Definir el token del usuario y otros parámetros si no están definidos
    $token = md5($_SESSION['idUser']);
    $cod_usuario = isset($_SESSION['idUser']) ? intval($_SESSION['idUser']) : 1234;
    $cod_cliente = isset($_POST['codCliente2']) ? intval($_POST['codCliente2']) : 1;
    $id_cupon = isset($_POST['cupon']) ? intval($_POST['cupon']) : 0;
    $pago = isset($_POST['pago']) ? intval($_POST['pago']) : 0;

    if (!empty($_POST['codigoTarjeta'])) {
        $codigopago = $_POST['codigoTarjeta'];
    } elseif (!empty($_POST['codigoTransferencia'])) {
        $codigopago = $_POST['codigoTransferencia'];
    } else {
        $codigopago = '';
    }

    $caja = isset($_POST['caja']) ? intval($_POST['caja']) : 0;

    // Preparar la lista de productos seleccionados
    $productosSeleccionadosStr = implode(',', array_map('intval', $productosSeleccionados));

    $imprimir =  isset($_POST['imprimir']) ? intval($_POST['imprimir']) : 0;

    // Iniciar una transacción para asegurar la integridad de los datos
    mysqli_begin_transaction($conection);

    try {
        // Llamar al procedimiento almacenado para crear la factura
        $query_procedimiento = "CALL procesar_factura_seleccionada('$mesa', '$productosSeleccionadosStr', '$token', '$cod_usuario', '$cod_cliente', '$id_cupon', '$pago', '$codigopago', '$caja')";

        //print_r($query_procedimiento);exit;


        if (mysqli_query($conection, $query_procedimiento)) {

            // Asegurarse de que todos los resultados del procedimiento almacenado sean procesados
            while (mysqli_next_result($conection)) {;}

            // Obtener la nueva factura generada
            $result = mysqli_query($conection, "SELECT codcliente, nofactura FROM factura ORDER BY nofactura DESC LIMIT 1");

            if ($result && mysqli_num_rows($result) > 0) {
                $factura = mysqli_fetch_assoc($result);

                // Confirmar la transacción si todo salió bien
                mysqli_commit($conection);

                if ($imprimir == 1) {
                	
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Factura creada exitosamente.',
                    'factura' => $factura['nofactura'],
                    'cliente' => $factura['codcliente'],

                ]);



            } else {
                throw new Exception('Error al obtener la nueva factura');
            }
        } else {
            throw new Exception('Error al ejecutar el procedimiento almacenado');
        }
    } catch (Exception $e) {
        // Si ocurre un error, revertir la transacción
        mysqli_rollback($conection);

        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}


if($_POST['action'] == 'codigoPromocional'){

//print_r($_POST);
//print_r($_FILES);
//exit;

	if(empty($_POST['codigo']))
	{
		echo 'error';
		exit;
		
	}else{



		$codigo 		= $_POST['codigo'];
		$total 			= $_POST['total'];
		$fecha 			= date('Y-m-d');
	

		$query = mysqli_query($conection,"SELECT * FROM codigos_promocionales WHERE codigo = '$codigo' AND estatus = 1");

		$result = mysqli_num_rows($query);


		if($result > 0){
	
			$arrayData = array();

			$data 						= mysqli_fetch_assoc($query);
			$fecha_inicio 				= $data['fecha_inicio'];
			$fecha_fin 					= $data['fecha_fin'];
			

			$arrayData['descripcion'] 	= $data['descripcion']; 
			$arrayData['id_cupon'] 		= $data['id'];
			

			if (empty($data['porcentaje'])) {
				
				$totalDescuento = $total - $data['dinero'];
				$descuento = $data['dinero']; 
			
			}else{

				$totalDescuento =  $total - (($total * $data['porcentaje']) / 100);
				$descuento = (($total * $data['porcentaje']) / 100); 
			}

			$arrayData['descuento'] 	= number_format($descuento,2);
			$arrayData['total'] 		= number_format($totalDescuento,2);

	
			if ($fecha >= $fecha_inicio AND $fecha <= $fecha_fin  ) {
				
				echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
				exit;
			
			}else{
				echo 2;
				exit;
			}

		}else{

			echo 3;
			exit;
		}


	}

}


if($_POST['action'] == 'formCategoria'){

			//print_r($_POST);exit;

			
			echo '<div><form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); sendDataForm();">
			                <h1><i class="fas fa-id-badge fa-3x"></i><br><br>Añadir Categoría</h1>
			                
				<label for="categoria">Nombre del Categoria</label>
				<input type="text" name="categoria" id="categoria">
				
				<input type="hidden" name="action" value="addCategoria">
				<button type="submit" class="btn_new"><i class="fas fa-edit"></i> Guardar</button>
			    <a href="#" class="btn_ok closeModal" onclick="closeModal2();"><i class="fas fa-ban"></i> Cerrar</a>
			</form>
			                
			                </div>
		           		  ';
					



}

if($_POST['action'] == 'addCategoria'){

//print_r($_POST);
//print_r($_FILES);
//exit;

	if(empty($_POST['categoria']))
	{
		echo 1;
		exit;
		
	}else{

		$categoria 		= $_POST['categoria'];			

			$query_insert = mysqli_query($conection,"INSERT INTO categorias(categoria) VALUES('$categoria')");

			if($query_insert){

				echo 2;

			}else{
				
				echo 3;
				exit;

			}
	
}
}


//TODO: CAJAS

if($_POST['action'] == 'formCaja'){

			//print_r($_POST);exit;

			
			echo '<div><form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); sendDataForm();">
			                <h1><i class="fas fa-cash-register fa-3x"></i><br><br>Añadir Caja</h1>
			                
				<label for="lugar">Lugar</label>
				<input type="text" name="lugar" id="lugar">
				
				<input type="hidden" name="action" value="addCaja">
				<button type="submit" class="btn_new"><i class="fas fa-edit"></i> Guardar</button>
			    <a href="#" class="btn_ok closeModal" onclick="closeModal2();"><i class="fas fa-ban"></i> Cerrar</a>
			</form>
			                
			                </div>
		           		  ';
}

if($_POST['action'] == 'addCaja'){

//print_r($_POST);
//print_r($_FILES);
//exit;

	if(empty($_POST['lugar']))
	{
		echo 1;
		exit;
		
	}else{

		$lugar 		= $_POST['lugar'];			

			$query_insert = mysqli_query($conection,"INSERT INTO cajas(lugar) VALUES('$lugar')");

			if($query_insert){

				echo 2;

			}else{
				
				echo 3;
				exit;

			}
	
}

}


if($_POST['action'] == 'arqueoCajas'){

			//print_r($_POST);exit;

						$id = $_POST['co'];
						$query = mysqli_query($conection,"SELECT a.id,u.nombre,u.apellido,a.fecha_inicio,a.fecha_fin,a.monto_inicial,a.monto_final,a.total_ventas,a.total_cash,a.estatus FROM arqueo_caja a INNER JOIN usuario u ON a.id_usuario = u.usuario WHERE a.id_caja = $id  ");
						$result = mysqli_num_rows($query);
						$data = '';

						if($result > 0){
							$table = '';
							$action2 = "'verCierreCaja'";
							$action = "'formCerrarCaja'";

							while($data = mysqli_fetch_assoc($query)){

								if($data['estatus'] == 1){
									
									
									$estado = '<span class="pagada">Abierto</span>';
									$boton  = '<button type="button" class="btn_anular" onclick="anadirForm2('.$action.','.$data['id'].');"><i class="fas fa-plus"></i></button>';

									}else{
									
									$estado = '<span class="anulada">Cerrado</span>';
									$boton  = '';
									}


								$table .= '	<tr>
									<td>'.$data['id'].'</td>
									<td>'.$data['nombre'].' '.$data['apellido'].'</td>
									<td class="textcenter">'.$data['fecha_inicio'].'</td>
									<td class="textcenter">'.$data['fecha_fin'].'</td>
									<td class="textcenter">$ '.$data['monto_inicial'].'</td>
									<td class="textcenter">$ '.$data['monto_final'].'</td>
									<td class="textcenter">'.$data['total_ventas'].'</td>
									<td class="textcenter">$ '.$data['total_cash'].'</td>
									<td class="textcenter">'.$estado.'</td>
									<td class="textcenter">'.$boton.'<button type="button" class="btn_view" onclick="anadirForm2('.$action2.','.$data['id'].');"><i class="fas fa-eye"></i></button></td>
									</tr>';
							}
							}else{
								$table = '';
							}
								

			echo '<div class="tableModal">

					<h1><i class="fas fa-cash-register"></i> Arqueo de Caja</h1><br>
						<table id="myTableArqueo">
							<thead>
								<tr>
									<th style="text-align:center;">ID</th>
									<th style="text-align:center;">Usuario</th>
									<th style="text-align:center;">Fecha Inicio</th>
									<th style="text-align:center;">Fecha Final</th>
									<th style="text-align:center;">Monto Inicial</th>
									<th style="text-align:center;">Monto Engregado</th>
									<th style="text-align:center;">Ventas</th>
									<th style="text-align:center;">Total Ventas</th>
									<th style="text-align:center;">Estado</th>
									<th style="text-align:center;">Acciones</th>
								</tr><tbody>
								'.$table.'
								</tbody>
							</table>
 							<div style="text-align:center;">
							 <a href="#" class="btn_ok closeModal" onclick="closeModal2();"><i class="fas fa-ban"></i> Cerrar</a>
			                </div>
			                 </div>
			           
		           		  ';
}

if ($_POST['action'] == 'formAbrirCaja') {

    // Consultar las cajas que están cerradas (estatus = 2)
    $query = mysqli_query($conection, "SELECT id, lugar FROM cajas WHERE estatus = 2");
    $result = mysqli_num_rows($query);

    if ($result > 0) {
        $selectOptions = '';

        while ($data = mysqli_fetch_assoc($query)) {
            $selectOptions .= '<option value="' . $data['id'] . '">' . $data['lugar'] . '</option>';
        }

        // Generar el HTML del formulario
        echo '
            <div>
                <form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); sendDataForm();">
                    <h1><i class="fas fa-cash-register fa-3x"></i><br><br>Abrir Caja</h1>
                    
                    <label for="caja">Caja</label>
                    <select name="caja" id="caja" class="notItemOne">
                        <option value="">Seleccione</option>
                        ' . $selectOptions . '
                    </select>
                    
                    <label for="monto_inicial">Monto Inicial (Sueltos)</label>
                    <input type="number" name="monto_inicial" id="monto_inicial" step="0.01" required>
                    
                    <input type="hidden" name="action" value="abrirCaja">
                    <button type="submit" class="btn_new"><i class="fas fa-edit"></i> Guardar</button>
                    <a href="#" class="btn_ok closeModal" onclick="closeModal2();"><i class="fas fa-ban"></i> Cerrar</a>
                </form>
            </div>';
    } else {
        echo 6; // No hay cajas disponibles para abrir
        exit;
    }
}


if ($_POST['action'] == 'abrirCaja') {

    if (empty($_POST['caja']) || empty($_POST['monto_inicial'])) {
        echo 1; // Faltan datos
        exit;
    }

    $id_caja = $_POST['caja'];
    $monto_inicial = $_POST['monto_inicial'];
    $id_usuario = $_SESSION['idUser'];
    $salida = 0; // Asignar un valor inicial para 'salida'

    // Iniciar transacción
    mysqli_begin_transaction($conection, MYSQLI_TRANS_START_READ_WRITE);

    try {
        // Verificar que la caja esté cerrada (estatus = 2)
        $query = mysqli_query($conection, "SELECT * FROM cajas WHERE id = $id_caja AND estatus = 2");
        if (mysqli_num_rows($query) != 1) {
            echo 4; // La caja no está disponible para abrir
            mysqli_rollback($conection); // Revertir la transacción
            exit;
        }

        // Verificar que el usuario no tenga otra caja abierta (estatus = 1)
        $query_2 = mysqli_query($conection, "SELECT * FROM arqueo_caja WHERE id_usuario = $id_usuario AND estatus = 1");
        if (mysqli_num_rows($query_2) > 0) {
            echo 5; // El usuario ya tiene una caja abierta
            mysqli_rollback($conection); // Revertir la transacción
            exit;
        }

        // Abrir la caja
        $fecha_inicio = date('Y-m-d G:i:s');
        $query_insert = mysqli_query($conection, "INSERT INTO arqueo_caja(id_caja, id_usuario, fecha_inicio, monto_inicial, salida) VALUES('$id_caja', '$id_usuario', '$fecha_inicio', '$monto_inicial', '$salida')");
        if (!$query_insert) {
            echo 3; // Error al insertar el arqueo de caja
            mysqli_rollback($conection); // Revertir la transacción
            exit;
        }

        // Actualizar el estatus de la caja a abierta (estatus = 1)
        $query_update = mysqli_query($conection, "UPDATE cajas SET estatus = 1 WHERE id = $id_caja");
        if (!$query_update) {
            echo 3; // Error al actualizar la caja
            mysqli_rollback($conection); // Revertir la transacción
            exit;
        }

        // Confirmar la transacción
        mysqli_commit($conection);
        echo 'Ok'; // Caja abierta correctamente
        exit;

    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        mysqli_rollback($conection);
        echo 3; // Mensaje genérico para error inesperado
        exit;
    }
}




if ($_POST['action'] == 'formCerrarCaja') {
    $id = intval($_POST['co']); // Asegurarse de que el ID sea un entero
    $user = $_SESSION['idUser'];

    // Consultar datos del arqueo de caja
    $query = mysqli_query($conection, "SELECT * FROM arqueo_caja WHERE id = $id AND estatus = 1");

    if (mysqli_num_rows($query) == 1) {
        $data = mysqli_fetch_assoc($query);
        $fecha_inicio = $data['fecha_inicio'];
        $id_caja = $data['id_caja'];
        $fecha_fin = date('Y-m-d G:i:s');

        // Consultar ventas agrupadas por tipo de pago
        $query_ventas = mysqli_query($conection, "SELECT tipopago, SUM(totalfactura) AS totalMonto, COUNT(totalfactura) AS totalVentas FROM factura WHERE caja = $id_caja AND estatus = 1 AND fecha BETWEEN '$fecha_inicio' AND '$fecha_fin' GROUP BY tipopago");

// Inicializar variables para totales por tipo de pago
$montoEfectivo = 0;
$montoTarjeta = 0;
$montoTransferencia = 0;
$montoDeUna = 0;

// Variables globales
$montoFinal = 0;
$ventasFinal = 0;

// Contenedor para los inputs HTML
$inputs = '';
if(mysqli_num_rows($query_ventas) > 0){

while ($data_ventas = mysqli_fetch_assoc($query_ventas)) {
    // Calcular montos individuales según el tipo de pago
    switch ($data_ventas['tipopago']) {
        case 1:
            $titulo = 'en Efectivo';
            $montoEfectivo += $data_ventas['totalMonto'];
            break;
        case 2:
            $titulo = 'con Tarjeta';
            $montoTarjeta += $data_ventas['totalMonto'];
            break;
        case 3:
            $titulo = 'con Transferencia';
            $montoTransferencia += $data_ventas['totalMonto'];
            break;
        case 4:
            $titulo = 'con DeUna';
            $montoDeUna += $data_ventas['totalMonto'];
            break;
        default:
            $titulo = 'Error';
            break;
    }

    // Generar el HTML correspondiente
    $inputs .= '
        <div class="caja_valores">
            <span>Ventas ' . $titulo . '</span>
            <span>' . $data_ventas['totalVentas'] . '</span>
        </div>
        <div class="caja_valores">
            <span>Monto </span>
            <span>$ ' . number_format($data_ventas['totalMonto'], 2) . '</span>
        </div>';

    // Acumular totales globales
    $montoFinal += $data_ventas['totalMonto'];
    $ventasFinal += $data_ventas['totalVentas'];
}

$inputs .= '<div class="caja_valores">
                            <span>Total Ventas</span>
                            <span>$ ' . number_format($montoFinal, 2) . '</span>
                        </div>
';

}else{
    $inputs = '<div class="caja_valores"><span>No hay ventas</span>
        </div>';
}

        $inicial = $data['monto_inicial'];

// Inicializar variables para salidas y entradas por tipo de moneda
$entradasEfectivo = 0;
$entradasTransferencia = 0;
$salidasEfectivo = 0;
$salidasTransferencia = 0;

$totalSalidas = 0; // Inicializar
$entregar = 0; // Inicializar

$query_salidas = mysqli_query($conection, "SELECT k.id, k.id_usuario, k.valor, k.tipo_transaccion, k.motivo, k.tipo_moneda, p.nombres AS nombre_usuario FROM kardex k JOIN personas p ON k.id_usuario = p.id WHERE k.id_user = '$user' AND k.fecha BETWEEN '$fecha_inicio' AND '$fecha_fin' ORDER BY k.tipo_transaccion");

$salidasHTML = '';

if (mysqli_num_rows($query_salidas) > 0) {
    $salidasHTML .= '
        <div class="caja_valores">
            <span>Nombre</span>
            <span>Motivo</span>
            <span>Monto</span>
        </div>';

    while ($data_salidas = mysqli_fetch_assoc($query_salidas)) {
        $signo = '';
        $estilo = ''; // Clase de estilo para color

        if ($data_salidas['tipo_transaccion'] == 1) { // Salida
            $signo = '-';
            $estilo = 'style="color: red;"';
            if ($data_salidas['tipo_moneda'] == 1) { // Efectivo
                $salidasEfectivo += $data_salidas['valor'];
            } elseif ($data_salidas['tipo_moneda'] == 2) { // Transferencia
                $salidasTransferencia += $data_salidas['valor'];
            }
        } elseif ($data_salidas['tipo_transaccion'] == 2) { // Entrada
            if ($data_salidas['tipo_moneda'] == 1) { // Efectivo
                $entradasEfectivo += $data_salidas['valor'];
            } elseif ($data_salidas['tipo_moneda'] == 2) { // Transferencia
                $entradasTransferencia += $data_salidas['valor'];
            }
        }

        $salidasHTML .= '
            <div class="caja_valores">
                <span>' . $data_salidas['nombre_usuario'] . '</span>
                <span>' . $data_salidas['motivo'] . '</span>
                <span ' . $estilo . '>' . $signo . '$ ' . number_format($data_salidas['valor'], 2) . '</span>
            </div>';
    }

    $salidasHTML .= '<hr>
        <div class="caja_valores">
            <span>Total Movimientos en Efectivo</span>
            <span>$ ' . number_format($entradasEfectivo - $salidasEfectivo, 2) .'</span>
        </div>
        <div class="caja_valores">
            <span>Total Movimientos en Transferencia</span>
            <span>$ ' . number_format($entradasTransferencia - $salidasTransferencia, 2) .'</span>
        </div>';
} else {
    $salidasHTML .= '
        <div class="caja_valores">
            <span>No hay Movimientos</span>
        </div>';
}

        // Calcular el monto final a entregar
        $totalSalidas = $entradasEfectivo - $salidasEfectivo + $entradasTransferencia - $salidasTransferencia;
        $entregar = ($inicial + $montoFinal) + $totalSalidas;

        $totalFinalEfectivoEntregar = $montoEfectivo + $inicial + $entradasEfectivo - $salidasEfectivo;

        $totalFinalTransferenciaEntregar = $montoTransferencia + $entradasTransferencia - $salidasTransferencia;

        $totalEfectivo = number_format($totalFinalEfectivoEntregar, 2, '.', '');
        $totalTarjeta = number_format($montoTarjeta, 2, '.', '');
        $totalTransferencia = number_format($totalFinalTransferenciaEntregar, 2, '.', '');
        $totalDeUna = number_format($montoDeUna, 2, '.', '');
        

        // Generar el HTML del formulario
        echo '
        
            
                <form action="" method="post" name="form_add_product" class="cierreCaja" id="form_add_product" onsubmit="event.preventDefault(); sendDataForm();">
                    <div class="wd60">
                        <h2>Arqueo de Caja</h2>
                        <hr>

                        <h3>Ventas Realizadas</h3>
                        <hr>
                        ' . $inputs . '
                        
                        <hr>

                        <h3>Movimientos de Caja</h3>
                        <hr>
                        ' . $salidasHTML . '
                        <hr>

                        <h2>Arqueo de Caja</h2>
                        <hr>
                        
                        <div class="caja_valores">
                            <span>Monto Inicial (Sueltos)</span> 
                            <span>$ ' . number_format($inicial, 2) . '</span>
                        </div>

                        
                        <div class="caja_valores">
                            <span>Total Ventas </span>
                            <span>$ ' . number_format($montoFinal, 2) . '</span>
                        </div>
                        
                        <div class="caja_valores">
                            <span>Total Movimientos</span>
                            <span>$ ' . number_format($totalSalidas, 2) . '</span>
                        </div>
                        <hr>
                        
                        <div class="caja_valores total-entregar">
                            <h2>CIERRE DE CAJA DEL DIA</h2>
                            <h2>$ ' . number_format($entregar, 2) . '</h2>
                        </div>
                    </div>

                    <div class="wd30">
                        <h2>MONTOS POR ENTREGAR</h2>
                    <div class="caja_valores">
                    <span>Efectivo </span>
                    <span>$'.number_format($totalFinalEfectivoEntregar, 2).' </span>
                    </div>
                    <div class="caja_valores">
                    <span>Tarjeta </span>
                    <span>$'.($montoTarjeta != 0 ? number_format($montoTarjeta / 0.94, 2) : '0.00').' </span>
                    </div>

                    <div class="caja_valores">
                    <span>Transferencia </span>
                    <span>$'.number_format($totalFinalTransferenciaEntregar, 2).'</span>
                    </div>

                    <div class="caja_valores">
                    <span>DeUna </span>
                    <span>$'.number_format($montoDeUna, 2).' </span>
                    </div>

                    <h2>MONTOS ENTREGADOS</h2>
                        <label for="monto_efectivo">Efectivo</label>
                        <input type="number" step="0.01" name="monto_efectivo" id="monto_efectivo" onkeyup="calcular();">

                        <label for="monto_tarjeta">Tarjeta</label>
                        <input type="number" step="0.01" name="monto_tarjeta" id="monto_tarjeta" onkeyup="calcular();">

                        <label for="monto_transferencia">Transferencia</label>
                        <input type="number" step="0.01" name="monto_transferencia" id="monto_transferencia" onkeyup="calcular();">

                        <label for="monto_deuna">DeUna</label>
                        <input type="number" step="0.01" name="monto_deuna" id="monto_deuna" onkeyup="calcular();">

                        <label for="monto_final">Entrega Total</label>
                        <input type="number" step="0.01" name="monto_final" id="monto_final" disabled>
                 <br>
                        <h3>Pagos al Personal</h3>
                        <div class="caja_valores">
                            <div class="empleado">
                                <label for="empleado_1">
                                    Trabajador 1
                                    <input type="number" name="empleado_1" id="empleado_1">
                                </label>
                            </div>
                            <div class="empleado">
                                <label for="empleado_cristina">
                                    Trabajador 2
                                    <input type="number" name="empleado_2" id="empleado_cristina">
                                </label>
                            </div>
                            <div class="empleado">
                                <label for="empleado_patricia">
                                    Trabajador 3
                                    <input type="number" name="empleado_3" id="empleado_patricia">
                                </label>
                            </div>
                        </div>

                        </div>

                    <input type="hidden" name="action" value="cerrarCaja">
                    <input type="hidden" name="co" value="' . $id . '">
                    <input type="hidden" name="total_ventas" value="' . $ventasFinal . '">
                    <input type="hidden" id="monto_final2" name="monto_final" value="">
                    <input type="hidden" name="total_cash" value="' . number_format($entregar, 2) . '">
                    <input type="hidden" name="total_movimientos" value="' . number_format($totalSalidas, 2) . '">

                    <input type="hidden" name="total_efectivo" value="'.$totalEfectivo.'">
                    <input type="hidden" name="total_tarjeta" value="'.$totalTarjeta.'">
                    <input type="hidden" name="total_transferencia" value="'.$totalTransferencia.'">
                    <input type="hidden" name="total_deuna" value="'.$totalDeUna.'">

                        <div class="acciones wd100">
                            <button type="submit" class="btn_new"><i class="fas fa-edit"></i> Guardar</button>
                            <a href="#" class="btn_ok closeModal" onclick="closeModal2();"><i class="fas fa-ban"></i> Cerrar</a>
                        </div>
                </form>';
    }
}


if ($_POST['action'] == 'cerrarCaja') {

    // Inicio de la transacción
    mysqli_begin_transaction($conection, MYSQLI_TRANS_START_READ_WRITE);

    try {
        if (empty($_POST['co']) || empty($_POST['monto_final'])) {
            echo 1;
            exit;
        }

        $id = $_POST['co'];
        $user = $_SESSION['idUser'];

        // Consultar datos del arqueo de caja
        $query = mysqli_query($conection, "SELECT id, id_caja, fecha_inicio, monto_inicial FROM arqueo_caja WHERE id = $id AND estatus = 1");
        if (mysqli_num_rows($query) != 1) {
            echo 4;
            mysqli_rollback($conection);
            exit;
        }

        $data_caja = mysqli_fetch_assoc($query);
        $id_cierre = $data_caja['id'];
        $id_caja = $data_caja['id_caja'];
        $fecha_inicio = $data_caja['fecha_inicio'];
        $fecha_fin = date('Y-m-d G:i:s');

        // Recopilar datos del formulario
        $monto_final = isset($_POST['monto_final']) && $_POST['monto_final'] !== '' ? $_POST['monto_final'] : 0;
        $total_ventas = isset($_POST['total_ventas']) && $_POST['total_ventas'] !== '' ? $_POST['total_ventas'] : 0;
        $total_cash = isset($_POST['total_cash']) && $_POST['total_cash'] !== '' ? $_POST['total_cash'] : 0;
        $total_salidas = isset($_POST['total_salidas']) && $_POST['total_salidas'] !== '' ? $_POST['total_salidas'] : 0;
        $efectivo = isset($_POST['monto_efectivo']) && $_POST['monto_efectivo'] !== '' ? $_POST['monto_efectivo'] : 0;
        $transferencia = isset($_POST['monto_transferencia']) && $_POST['monto_transferencia'] !== '' ? $_POST['monto_transferencia'] : 0;
        $deuna = isset($_POST['monto_deuna']) && $_POST['monto_deuna'] !== '' ? $_POST['monto_deuna'] : 0;
        $tarjeta = isset($_POST['monto_tarjeta']) && $_POST['monto_tarjeta'] !== '' ? $_POST['monto_tarjeta'] : 0;

        // Obtener los valores de los empleados y sumar
        $salarios = 0;
        $salarios += isset($_POST['empleado_1']) && $_POST['empleado_1'] !== '' ? (float) $_POST['empleado_1'] : 0;
        $salarios += isset($_POST['empleado_2']) && $_POST['empleado_2'] !== '' ? (float) $_POST['empleado_2'] : 0;
        $salarios += isset($_POST['empleado_3']) && $_POST['empleado_3'] !== '' ? (float) $_POST['empleado_3'] : 0;

        // Actualizar el arqueo de caja
        $query_update_2 = mysqli_query($conection, "UPDATE arqueo_caja SET fecha_fin = '$fecha_fin', monto_final = '$monto_final', total_ventas = '$total_ventas', total_cash = '$total_cash', efectivo = '$efectivo', transferencia = '$transferencia', deuna = '$deuna', tarjeta = '$tarjeta', salida = '$total_salidas', salarios = '$salarios', estatus = 2 WHERE id = $id");

        if ($query_update_2) {
            // Actualizar el estado de la caja
            $query_update_3 = mysqli_query($conection, "UPDATE cajas SET estatus = 2 WHERE id = $id_caja");
            if (!$query_update_3) {
                echo 3;
                mysqli_rollback($conection);
                exit;
            }

            // Actualizar facturas relacionadas
            $query_update_4 = mysqli_query($conection, "UPDATE factura SET id_cierre = $id_cierre, estatus = 4 WHERE caja = $id_caja AND estatus = 1 AND fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'");
            if (!$query_update_4) {
                echo 3;
                mysqli_rollback($conection);
                exit;
            }

            // Confirmar la transacción
            mysqli_commit($conection);
            
            // Consultar detalles del kardex (todas las salidas) con nombre de usuario
        $salidas = [];
        $query_kardex = mysqli_query($conection, "
            SELECT k.id AS id_salida, k.valor, k.tipo_moneda, k.id_usuario, p.nombres AS nombre_usuario, k.motivo
            FROM kardex k
            JOIN personas p ON k.id_usuario = p.id
            WHERE k.fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'
        ");
        if (mysqli_num_rows($query_kardex) > 0) {
            while ($row = mysqli_fetch_assoc($query_kardex)) {
                $salidas[] = $row;
            }
        }

        // Preparar datos para imprimir el cierre de caja
        $data = [
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'idArqueo' => $id_cierre,
            'idUser' => $user,
            'monto_inicial' => $data_caja['monto_inicial'],
            'nombre' => $_SESSION['nombre'],
            'apellido' => $_SESSION['apellido'],
            'monto_final' => $monto_final,
            'total_ventas' => $total_ventas,
            'total_cash' => $total_cash,
            'efectivo' => $efectivo,
            'transferencia' => $transferencia,
            'tarjeta' => $tarjeta,
            'deuna' => $deuna,
            'total_movimientos' => $total_salidas,
            'salidas' => $salidas // Añadir todas las salidas para la impresión
        ];

        // Imprimir el cierre de caja
        if (imprimirCierreCaja($data)) {
            echo 2;
            exit;
        } else {
            echo 3;
            exit;
        }

        } else {
            echo 3;
            mysqli_rollback($conection);
            exit;
        }
    } catch (Exception $e) {
        mysqli_rollback($conection);
        echo 3;
        exit;
    }
}


if($_POST['action'] == 'verCierreCaja'){

			//print_r($_POST);exit;

					$id = $_POST['co'];

					$query 	= mysqli_query($conection,"SELECT id,id_caja,fecha_inicio,fecha_fin FROM arqueo_caja WHERE id = $id");
						
					$result 			= mysqli_num_rows($query);
					$data_caja 			= mysqli_fetch_assoc($query);
					$fecha_inicio 		= $data_caja['fecha_inicio'];
					
					if (empty($data_caja['fecha_fin'])) {
						$fecha_fin 			= date('Y-m-d G:i:s');
					}else{
						$fecha_fin 			= $data_caja['fecha_fin'];
					}
					


						$query = mysqli_query($conection,"SELECT SUM(dt.cantidad) as cantidad, SUM(dt.precio_venta) as precio_total,p.producto FROM detalle_factura dt INNER JOIN producto p ON p.codproducto = dt.codproducto INNER JOIN factura f ON f.nofactura = dt.nofactura WHERE fecha BETWEEN '$fecha_inicio' AND '$fecha_fin' GROUP BY p.producto");
						$result = mysqli_num_rows($query);
						$data = '';

						if($result > 0){
							$table = '';
							$action = "'verCierreCaja'";

							while($data = mysqli_fetch_assoc($query)){

								

								$table .= '	<tr>
									<td class="textcenter wd10">'.$data['cantidad'].'</td>
									<td class="textcenter">'.$data['producto'].'</td>
									<td class="textcenter">'.$data['precio_total'].'</td>
									<td class="textcenter wd12"></td>
									
									
									</tr>';
							}
							}else{
								$table = '';
							}
								

			echo '<div class="tableModal">

					<h1><i class="fas fa-list"></i> Resumen de Ventas</h1><br>
						<table id="myTableVentas">
							<thead>
								<tr>
									<th style="text-align:center;">Cant.</th>
									<th style="text-align:center;">Producto</th>
									<th style="text-align:center;">Precio Total</th>
									<th style="text-align:center;">Acciones</th>
								</tr><tbody>
								'.$table.'
								</tbody>
							</table>
 							<div style="text-align:center;">
							 <a href="#" class="btn_ok closeModal" onclick="closeModal2();"><i class="fas fa-ban"></i> Cerrar</a>
			                </div>
			                 </div>
			           
		           		  ';
}










if($_POST['action'] == 'formDetalleProducto'){

			//print_r($_POST);exit;

				$id = $_POST['co'];

				$query = mysqli_query($conection,"SELECT codproducto,atributos FROM detalle_temp WHERE correlativo = $id");
				
				$result = mysqli_num_rows($query);
						
				if($result == 1){

					$data = mysqli_fetch_assoc($query);
					$id2 = $data['codproducto'];

					$query_atributo = mysqli_query($conection,"SELECT * FROM atributos_productos WHERE codproducto = $id2");
					$result_atributo = mysqli_num_rows($query_atributo);
				
				if($result_atributo > 0){
				
					$tabla = '';
					$todo = array();
					$todo = json_decode($data['atributos'],JSON_UNESCAPED_UNICODE);

					while($data_atributo = mysqli_fetch_assoc($query_atributo)){
							
						if (!empty($data['atributos'])) {
												
					$buscar = $data_atributo['atributo'];
							//echo $buscar;
							//print_r($todo);		

									$check = '';
									if(array_key_exists($buscar,$todo)) {

										$check = 'checked';
										
									}


									$tabla .= '<tr><td>'.$data_atributo['atributo'].'</td>
														<td><input type="checkbox" name="'.$data_atributo['atributo'].'" value="No" '.$check.'></td></tr>';

									
								}else{

									$tabla .= '<tr><td>'.$data_atributo['atributo'].'</td>
											<td><input type="checkbox" name="'.$data_atributo['atributo'].'" value="No"></td></tr>';
								}
							}


							}else{
								echo 7;
								exit;

							}

				}
	
			
			echo '<div><form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); sendDataForm();">
			                <h1><i class="fas fa-hamburger fa-3x"></i><br>Atributos del Producto</h1>
			                
			                <br>
				<table id="detalle_venta">	
					<thead>
					<tr>
						<th>Atributo</th>
						<th>Eliminar</th>

					</tr>
					</thead>
					<tbody>'.$tabla.'</tbody>
					
				</table>
				<input type="hidden" name="co" value="'.$id.'">
				<input type="hidden" name="action" value="guardarAtributosProducto">
				<button type="submit" class="btn_new"><i class="fas fa-edit"></i> Guardar</button>
			    <a href="#" class="btn_ok closeModal" onclick="closeModal2();"><i class="fas fa-ban"></i> Cerrar</a>
			</form>
			                
			                </div>
		           		  ';
}

if ($_POST['action'] == 'formDetalleProducto2') {

    if (empty($_POST['co'])) {
        echo 'error';
        exit;
    }

    $id = intval($_POST['co']);

    // Consulta para obtener observaciones y atributos
    $query = mysqli_query($conection, "SELECT observaciones, codatributos FROM detalle_temp WHERE correlativo = $id");
    
    if ($query && mysqli_num_rows($query) == 1) {
        $data = mysqli_fetch_assoc($query);
        $observaciones = $data['observaciones'] ?? '';
        $codatributos = $data['codatributos'] ?? '';
    } else {
        $observaciones = '';
        $codatributos = '';
    }

    // Procesar atributos
    $atributos = '';
    if (!empty($codatributos)) {
        $ids_array = explode(",", $codatributos);

        foreach ($ids_array as $id2) {
            $id2 = intval($id2);
            $query_atributo = mysqli_query($conection, "SELECT id, atributo FROM atributos_productos WHERE id = $id2");
            
            if ($query_atributo && mysqli_num_rows($query_atributo) > 0) {
                while ($data_atributo = mysqli_fetch_assoc($query_atributo)) {
                    $idatributo = $data_atributo['id'];
                    $atributo = htmlspecialchars($data_atributo['atributo'], ENT_QUOTES, 'UTF-8');

                    $tipo = '';
                    $query_tipo = mysqli_query($conection, "SELECT tipo FROM tipo_atributos WHERE codatributo = $idatributo");
                    
                    if ($query_tipo && mysqli_num_rows($query_tipo) > 0) {
                        while ($data_tipo2 = mysqli_fetch_assoc($query_tipo)) {
                            $tipo2 = htmlspecialchars($data_tipo2['tipo'], ENT_QUOTES, 'UTF-8');
                            $tipo .= '<div class="tipo"><span>' . $tipo2 . '</span><input type="radio" name="' . $atributo . '" value="' . $tipo2 . '"></div>';
                        }
                    }

                    $atributos .= '<h2>' . $atributo . '</h2><div class="atributo">' . $tipo . '</div>';
                }
            }
        }
    }

    // Procesar observaciones ya seleccionadas
    $seleccionado2 = '';
    if (!empty($observaciones)) {
        $array = json_decode($observaciones, true);
        $seleccionado = '';

        foreach ($array as $clave => $valor) {
            $seleccionado .= "<span>" . htmlspecialchars($clave, ENT_QUOTES, 'UTF-8') . ": " . htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') . " | </span>";
        }

        $seleccionado2 = '<h2>Composición</h2>' . $seleccionado;
    }

    // Generar el formulario
    echo '<div class="containerForm">
            <form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); sendDataForm2();">
                ' . $seleccionado2 . '
                <h1><br><i class="fas fa-edit fa-2x"></i><br>Observaciones</h1>
                ' . $atributos . '
                <h2>Observaciones</h2>
                <textarea style="width: 100%; height: 50px; max-height: 100px; max-width: 100%; min-width: 100%;" name="ob"></textarea>
                <input type="hidden" name="co" value="' . $id . '">
                <input type="hidden" name="action" value="editarProducto">
                <div class="acciones">
                    <button type="submit" class="boton"><i class="fas fa-edit"></i> Guardar</button>
                    <a href="#" class="boton rojo closeModal" onclick="closeModal2();"><i class="fas fa-ban"></i> Cerrar</a>
                </div>
            </form>
          </div>';
}


if ($_POST['action'] == 'guardarAtributosProducto') {

    // Verificar que el campo 'co' esté presente
    if (empty($_POST['co'])) {
        echo 1;
        exit;
    }

    $id = intval($_POST['co']); // Asegurarse de que 'co' sea un entero

    // Filtrar y codificar los atributos en formato JSON
    $atributos = array_diff_key($_POST, ['co' => '', 'action' => '']);
    $atributos_json = json_encode($atributos, JSON_UNESCAPED_UNICODE);

    // Ejecutar la consulta de actualización
    $query_update = mysqli_query($conection, "UPDATE detalle_temp SET atributos = '$atributos_json' WHERE correlativo = $id");

    // Verificar si la consulta se ejecutó correctamente
    if ($query_update) {
        echo 2;
    } else {
        echo 'error';
    }
    exit;
}

if ($_POST['action'] == 'editarProducto') {

    // Verificar que el campo 'co' esté presente
    if (empty($_POST['co'])) {
        echo 1;
        exit;
    }

    $id = intval($_POST['co']); // Asegurarse de que 'co' sea un entero

    // Filtrar y codificar las observaciones en formato JSON
    $observaciones = array_diff_key($_POST, ['co' => '', 'action' => '']);
    $observaciones_json = json_encode($observaciones, JSON_UNESCAPED_UNICODE);

    // Ejecutar la consulta de actualización
    $query_update = mysqli_query($conection, "UPDATE detalle_temp SET observaciones = '$observaciones_json' WHERE correlativo = $id");

    // Verificar si la consulta se ejecutó correctamente
    if ($query_update) {
        $arrayData = [
            'code' => 3,
            'user' => $_SESSION['idUser']
        ];

        // Enviar la respuesta como JSON
        echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
    } else {
        echo 'error';
    }
    exit;
}


if ($_POST['action'] == 'editarProducto2') {

    // Verificar que el campo 'co' esté presente
    if (empty($_POST['co'])) {
        echo 1;
        exit;
    }

    // Obtener y sanitizar los datos de entrada
    $id = intval($_POST['co']);
    $producto = mysqli_real_escape_string($conection, trim($_POST['producto']));
    $costo = floatval($_POST['costo']);
    $precio = floatval($_POST['precio1']);
    $precio2 = floatval($_POST['precio2']);
    $precio3 = floatval($_POST['precio3']);
    $categoria = intval($_POST['categoria']);
    $lugar = mysqli_real_escape_string($conection, trim($_POST['lugar']));

    // Ejecutar la consulta de actualización
    $query_update = mysqli_query($conection, "UPDATE producto SET 
        producto = '$producto', 
        costo = '$costo', 
        precio = '$precio', 
        precio2 = '$precio2', 
        precio3 = '$precio3', 
        categoria = '$categoria', 
        lugar = '$lugar' 
        WHERE codproducto = $id");

    // Verificar si la consulta se ejecutó correctamente
    if ($query_update) {
        echo 'ok';
    } else {
        echo 'error';
    }
    exit;
}

if($_POST['action'] == 'addAtributoProducto'){

//print_r($_POST);
//print_r($_FILES);
//exit;

	if(empty($_POST['co']))
	{
		echo 1;
		exit;
		
	}else{

		$id = $_POST['co'];

		$atributos = $_POST['atributo'];
		$atributos = array_unique($atributos);
     	$atributos = array_filter($atributos);
    	$atributos_csv = implode(',', $atributos);


		$query_update = mysqli_query($conection,"UPDATE producto SET codatributos = '$atributos_csv' WHERE codproducto = $id");				
		if($query_update){
						
			echo 'ok';
			exit;
			}

}

}

if($_POST['action'] == 'addTipoAtributo'){

print_r($_POST);
//print_r($_FILES);
exit;

	if(empty($_POST['co']))
	{
		echo 1;
		exit;
		
	}else{

		$id 	= $_POST['co'];
		$tipo 	= $_POST['atributo'];
		$tipo 	= array_unique($tipo);
     	$tipo 	= array_filter($tipo);

     	foreach ($tipo as $key){
     	
     	}

		$query_update = mysqli_query($conection,"UPDATE producto SET codatributos = '$atributos_csv' WHERE codproducto = $id");				
		if($query_update){
						
			echo 'ok';
			exit;
			}

}

}


if($_POST['action'] == 'formClienteComanda'){

	//print_r($_POST);exit;

	$mesa 		= $_POST['mesa'];

	if(empty($mesa)){
		echo 2; 
	}

		echo '<div class="scroll"><form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); sendDataFormImprimir();">
					<h1><i class="fas fa-print fa-3x"></i><br><br>Imprimir Comanda</h1>
		<label for="nombre">Nombre Cliente</label>
		<input type="text" name="nombre" id="nombre" >
		<input type="hidden" name="action" value="imprimirComanda2">
		<input type="hidden" name="mesa" value="'.$mesa.'">
		<div class="acciones">
		<button type="submit" class="boton"><i class="fas fa-print"></i> Imprimir</button>
		<a href="#" class="boton rojo closeModal" onclick="closeModal2();"><i class="fas fa-ban"></i> Cerrar</a>
		</div>
	</form>
					
					</div>
					 ';
			

}

if($_POST['action'] == 'formClientePre'){

	//sendDataFormImprimir($_POST);exit;

	$mesa 		= $_POST['mesa'];
	
		echo '<div class="scroll"><form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); sendDataFormImprimir();">
					<h1><i class="fas fa-print fa-3x"></i><br><br>Imprimir Pre-Factura</h1>
		<label for="nombre">Nombre Cliente</label>
		<input type="text" name="nombre" id="nombre" >
		<input type="hidden" name="action" value="imprimirPrecuenta2">
		<input type="hidden" name="mesa" value="'.$mesa.'">
		<div class="acciones">
		<button type="submit" class="boton"><i class="fas fa-print"></i> Guardar</button>
		<a href="#" class="boton rojo closeModal" onclick="closeModal2();"><i class="fas fa-ban"></i> Cerrar</a>
		</div>
	</form>
					
					</div>
					 ';
			

}

if($_POST['action'] == 'formSalidaDinero'){

	//print_r($_POST);exit;


		$query = mysqli_query($conection,"SELECT * FROM personas WHERE estatus = 1");
				$result = mysqli_num_rows($query);
				$data = '';

		if($result > 0){
		
			$select = '';
			while($data = mysqli_fetch_assoc($query)){


				$select .= '<option value="'.$data['id'].'">'.$data['nombres'].' </option>';
		
					
					}
					}else{
						echo 6;
						exit;

					}
	
	echo '<div><form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); sendDataForm();">
					<h1><i class="fas fa-cash-register fa-3x"></i><br><br>Entada / Salida Dinero</h1>
					
        <label for="tipo">Tipo</label>
		<select name="tipo" id="tipo" class="notItemOne wd100" style="width: 100% !important;">
			<option value="">Seleccione</option>
			<option value="2">Entrada</option>
			<option value="1">Salida</option>
		</select>
		
        
        <label for="nombre">Nombre</label>
		<select name="nombre" id="nombre" class="notItemOne wd100" style="width: 100% !important;">
			<option value="">Seleccione</option>
			'.$select.'
		</select>
		<label for="moneda">Tipo Moneda</label>
		<select name="moneda" id="moneda" class="notItemOne wd100" style="width: 100% !important;">
			<option value="">Seleccione</option>
			<option value="1">Efectivo</option>
			<option value="2">Transferencia</option>
		</select>
		<label for="monto">Monto</label>
		<input type="number" name="monto" id="monto" step="0.01">
		<label for="motivo">Motivo</label>
		<input type="text" name="motivo" id="motivo">
		<input type="hidden" name="action" value="salidaDinero">
		<button type="submit" class="btn_new"><i class="fas fa-edit"></i> Guardar</button>
		<a href="#" class="btn_ok closeModal" onclick="closeModal2();"><i class="fas fa-ban"></i> Cerrar</a>
	</form>
					
					</div>
					 ';
}

if($_POST['action'] == 'salidaDinero'){

	//print_r($_POST);
	//print_r($_FILES);
	//exit;
	
		if(empty($_POST['nombre']) || empty($_POST['monto']) || empty($_POST['motivo']) || empty($_POST['tipo']))
		{
			echo 1;
			exit;
			
		}else{
			
			$fecha 					= date('Y-m-d G:i:s');
			$monto 					= $_POST['monto'];
			$idCliente 				= $_POST['nombre'];
			$moneda 				= $_POST['moneda'];
			$transaccion 			= $_POST['tipo'];
			$user 					= $_SESSION['idUser'];
			$motivo 				= $_POST['motivo'];
			$cantidad 				= 1;
			$query 					= mysqli_query($conection,"INSERT INTO `kardex`(`fecha`, `cantidad`, `valor`, `tipo_moneda`, `tipo_transaccion`, `id_usuario`, `id_user`, `motivo`) VALUES('$fecha','$cantidad',$monto,'$moneda','$transaccion','$idCliente',
			'$user','$motivo')");

			if(!$query){
				echo 4;
				exit;
			}
			$data = '';
			$data = array();
			$data['id']                 = mysqli_insert_id($conection);


			$query_2 = mysqli_query($conection,"SELECT * FROM personas WHERE id = $idCliente");
			
			$result = mysqli_num_rows($query_2);

			if($result > 0){

				$data2 = mysqli_fetch_assoc($query_2);

			}
		
			$data['fecha']              = $fecha;      
			$data['nombre']            	= $data2['nombres'];         			           
			$data['monto']              = $monto;
			$data['motivo']             = $motivo;
			$data['moneda']        		= $moneda;
            $data['tipo']        		= $transaccion;

					//print_r($data);
					//exit;
					if (imprimirSalidaDinero($data)) {
						echo 2;		
						exit;
					}else{
						echo 3;
						exit;
					}	
				}		
	}
	///
	//print_r($data);exit;
}


	exit;	
?>