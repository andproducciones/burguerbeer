<?php


include "../conexion.php";
include "includes/functions.php";
require_once 'includes/email.php';
session_start();
date_default_timezone_set('America/Guayaquil');
mysqli_set_charset($conection, 'utf8mb4');
//print_r($_POST);exit;



if (!empty($_POST)) {
    //Extraer datos del Producto para el Modal
    if ($_POST['action'] == 'infoProducto') {
        $producto_id = $_POST['producto'];

        $query = mysqli_query($conection, "SELECT codproducto,producto,existencia,precio FROM producto WHERE codproducto = $producto_id AND estatus = 1");

        mysqli_close($conection);

        $result = mysqli_num_rows($query);
        if ($result > 0) {
            $data = mysqli_fetch_assoc($query);
            echo JSON_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo 'error';
        exit;
    }

    //Añadir Productos foreach
    if ($_POST['action'] == 'addProduct') {


        if (!empty($_POST['cantidad']) || !empty($_POST['precio']) || !empty($_POST['producto_id'])) {
            $cantidad 		= $_POST['cantidad'];
            $precio		 	= $_POST['precio'];
            $producto_id	= $_POST['producto_id'];
            $usuario_id		= $_SESSION['idUser'];

            $query_insert	= mysqli_query($conection, "INSERT INTO entradas(codproducto,cantidad,precio,usuario_id)VALUES($producto_id,$cantidad,$precio,$usuario_id)");

            if ($query_insert) {


                $query_upd = mysqli_query($conection, "CALL actualizar_precio_producto($cantidad,$precio,$producto_id)");
                $result_pro = mysqli_num_rows($query_upd);
                if ($result_pro > 0) {
                    $data = mysqli_fetch_assoc($query_upd);
                    $data['producto_id'] = $producto_id;
                    echo json_encode($data, JSON_UNESCAPED_UNICODE);
                    exit;
                }

            } else {
                echo 'error';
            }
            mysqli_close($conection);

        } else {
            echo 'error';
        }
        exit;
    }

    if ($_POST['action'] == 'addProducto') {


        if (!empty($_POST['producto']) || !empty($_POST['precio1']) || !empty($_POST['categoria']) || !empty($_POST['lugar'])) {
            $producto 			= $_POST['producto'];
            $precio1		 	= $_POST['precio1'];
            $categoria			= $_POST['categoria'];
            $lugar				= $_POST['lugar'];

            $query_insert	= mysqli_query($conection, "INSERT INTO producto(codproducto,producto,precio,categoria,lugar,foto)VALUES('','$producto','$precio1',$categoria,$lugar,'logo.jpg')");
            mysqli_close($conection);
            if ($query_insert) {

                echo 'ok';
                exit;


            } else {
                echo 3;
            }


        } else {
            echo 1;
            exit;
        }

    }


    //buscar cliente

    if ($_POST['action'] == 'searchCliente') {


        if (!empty($_POST['cliente'])) {

            $cedula = $_POST['cliente'];

            $query = mysqli_query($conection, "SELECT usuario,nombre,p_apellido,s_apellido,direccion,correo_c as correo,telefono FROM clientes WHERE usuario LIKE '$cedula' and estatus = 1");


            $result = mysqli_num_rows($query);

            $data = '';
            if ($result > 0) {
                $data = mysqli_fetch_assoc($query);
                //print_r($data);

            } else {
                $data = 0;
            }

            echo json_encode($data, JSON_UNESCAPED_UNICODE);

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



    if ($_POST['action'] == 'addProductoTabla') {

        //print_r($_POST);exit;

        if (empty($_POST['code'])) {
            echo 'error';
        } else {

            $id = $_POST['code'];

            $query = mysqli_query($conection, "SELECT * FROM producto WHERE categoria = $id");
            $result = mysqli_num_rows($query);
            $data = '';
            $detalleTabla = '';
            $arrayData = array();

            if ($result > 0) {

                while ($data = mysqli_fetch_assoc($query)) {


                    $detalleTabla .= '<div class="producto productoG">
					<button type="button" class="btn1"  onclick="addproduct('.$data['codproducto'].')">
					<img src="img/productos/'. $data['foto'].'">
					<p>'. $data['producto'].'</p>
					</button>
					</div>';

                }

                $arrayData['detalle'] = $detalleTabla;
                echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);

            } else {
                echo 'error';
            }
        }
    }

    if ($_POST['action'] == 'addProductosTabla') {

        //print_r($_POST);exit;

        $query = mysqli_query($conection, "SELECT * FROM producto");
        $result = mysqli_num_rows($query);
        $data = '';
        $detalleTabla = '';
        $arrayData = array();

        if ($result > 0) {

            while ($data = mysqli_fetch_assoc($query)) {


                $detalleTabla .= '<div class="producto productoG">
				<button type="button" class="btn1"  onclick="addproduct('.$data['codproducto'].')">
				<img src="img/productos/'. $data['foto'].'">
				<p>'. $data['producto'].'</p>
				</button>
				</div>';

            }

            $arrayData['detalle'] = $detalleTabla;
            echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);

        } else {
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

        $codcliente = empty($_POST['codcliente']) ? 1 : intval($_POST['codcliente']);

        if (empty($_POST['mesa'])) {
            echo json_encode(['error' => 'Mesa no enviada']);
            exit;
        }
        $mesa = intval($_POST['mesa']);

        if (empty($_POST['caja'])) {
            echo json_encode(['error' => 'Caja no enviada']);
            exit;
        }
        $caja = intval($_POST['caja']);

        if ($_POST['pago'] == 2) {
            $codigopago = $_POST['codigoTarjeta'] ?? '';
        } elseif ($_POST['pago'] == 3 || $_POST['pago'] == 4) {
            $codigopago = $_POST['codigoTransferencia'] ?? '';
        } else {
            $codigopago = 1;
        }
        $codigopago = mysqli_real_escape_string($conection, $codigopago);

        $cupon = empty($_POST['cupon']) ? 1 : intval($_POST['cupon']);
        $pago = intval($_POST['pago']);
        $token = md5($_SESSION['idUser']);
        $usuario = intval($_SESSION['idUser']);
        $nombreCliente = mysqli_real_escape_string($conection, $_POST['nombreCliente']);
        $correoMarketing = '';
        if (!empty($_POST['correoMarketing'])) {
            $correoMarketing = mysqli_real_escape_string($conection, $_POST['correoMarketing']);
        }

        $query = mysqli_query(
            $conection,
            "SELECT * FROM detalle_temp 
         WHERE token_user = '$token'
           AND mesa = $mesa"
        );

        if (mysqli_num_rows($query) > 0) {

            $query_procesar = mysqli_query(
                $conection,
                "CALL procesar_venta(
                $usuario,
                $codcliente,
                '$token',
                $mesa,
                $pago,
                '$codigopago',
                '$cupon',
                 $caja
            )"
            );

            if ($query_procesar && mysqli_num_rows($query_procesar) == 1) {
                $data = mysqli_fetch_assoc($query_procesar);

                while (mysqli_more_results($conection)) {
                    mysqli_next_result($conection);
                }

                $factura = $_POST['factura'] == 1 ? 1 : 0;
                $comandas = $_POST['comandas'] == 1 ? 1 : 0;

                $correoEstado = 'no solicitado';
                $correoError = null;

                if (!empty($correoMarketing)) {
                    try {
                        $sqlCheckPromo = mysqli_query(
                            $conection,
                            "SELECT codigo FROM promociones_redimidas 
                         WHERE correo = '$correoMarketing' 
                         LIMIT 1"
                        );

                        if (mysqli_num_rows($sqlCheckPromo) > 0) {
                            $rowPromo = mysqli_fetch_assoc($sqlCheckPromo);
                            $codigoPromo = $rowPromo['codigo'];
                            $correoEstado = 'ya registrado, no se envía promoción';
                        } else {
                            $codigoPromo = 'PROMO-' . strtoupper(bin2hex(random_bytes(4)));

                            mysqli_query(
                                $conection,
                                "INSERT INTO promociones_redimidas (correo, codigo) 
                             VALUES ('$correoMarketing', '$codigoPromo')"
                            );

                            $checkCorreo = mysqli_query(
                                $conection,
                                "SELECT id FROM correos_marketing 
                             WHERE correo = '$correoMarketing' 
                             LIMIT 1"
                            );

                            if (mysqli_num_rows($checkCorreo) == 0) {
                                mysqli_query(
                                    $conection,
                                    "INSERT INTO correos_marketing (correo) 
                                 VALUES ('$correoMarketing')"
                                );
                            }

                            $plantillaPath = 'includes/plantillas/bienvenida.php';
                            if (file_exists($plantillaPath)) {
                                $plantilla = file_get_contents($plantillaPath);
                                $plantilla = str_replace('{{NOMBRE}}', $nombreCliente, $plantilla);
                                $plantilla = str_replace('{{CODIGO}}', $codigoPromo, $plantilla);
                            } else {
                                $plantilla = '<p>¡Gracias por registrarte!</p>';
                            }

                            // Definir imágenes embebidas con rutas relativas para PHPMailer
                            $imagenesEmbed = json_encode([
                                ['ruta' => 'img/grupo.png',              'cid' => 'logoGrupo'],
                                ['ruta' => 'img/aninga travel.png',      'cid' => 'logoAninga'],
                                ['ruta' => 'img/burguerbeer2.png',       'cid' => 'logoBurguer'],
                                ['ruta' => 'img/calikaphe.png',          'cid' => 'logoCalikaphe'],
                                ['ruta' => 'img/canalimena.png',         'cid' => 'logoHostal']
                            ]);

                            $imagenesEscaped = mysqli_real_escape_string($conection, $imagenesEmbed);
                            $correoEscaped = mysqli_real_escape_string($conection, $correoMarketing);
                            $nombreEscaped = mysqli_real_escape_string($conection, $nombreCliente);
                            $asuntoEscaped = mysqli_real_escape_string($conection, '¡Gracias por compartir tu correo con Canalimena Group!');
                            $contenidoEscaped = mysqli_real_escape_string($conection, $plantilla);

                            $insert = mysqli_query(
                                $conection,
                                "INSERT INTO cola_envios
                             (correo_destino, nombre_destino, asunto, contenido_html, imagenes_embed)
                             VALUES
                             ('$correoEscaped', '$nombreEscaped', '$asuntoEscaped', '$contenidoEscaped', '$imagenesEscaped')"
                            );

                            if ($insert) {
                                $correoEstado = 'en cola';
                            } else {
                                $correoEstado = 'error al guardar en cola';
                                $correoError = mysqli_error($conection);
                            }
                        }
                    } catch (Exception $e) {
                        $correoEstado = 'error al guardar en cola';
                        $correoError = $e->getMessage();
                        error_log("Excepción guardando correo en cola: " . $e->getMessage());
                    }
                }

                $mensaje = 'Venta realizada correctamente.';
                if ($correoEstado === 'en cola') {
                    $mensaje .= ' El correo será enviado en segundo plano.';
                } elseif ($correoEstado === 'ya registrado, no se envía promoción') {
                    $mensaje .= ' El correo ya estaba registrado y no se vuelve a enviar la promoción.';
                }

                $response = [
                    'venta'      => true,
                    //'mensaje'       => $mensaje,
                    'no_factura'    => $data['no_factura'],
                    'factura'       => $factura,
                    'comandas'      => $comandas,
                    'cod_cliente'   => $codcliente,
                    'nombreCliente' => $nombreCliente
                    //'correo_estado' => $correoEstado,
                    //'correo_error'  => $correoError

                ];

                echo json_encode($response, JSON_UNESCAPED_UNICODE);


            } else {
                echo json_encode(['error' => 'Error al procesar la venta']);
            }
        } else {
            echo json_encode(['error' => 'No hay productos en la venta']);
        }
    }

    if ($_POST['action'] == 'abrirModalMesas') {
        $query = mysqli_query($conection, "
        SELECT m.id, m.numero,
            (SELECT COUNT(*) FROM detalle_temp WHERE mesa = m.id) as total_productos
        FROM mesas m
        WHERE m.estatus = 1
    ");

        $mesas = [];
        while ($row = mysqli_fetch_assoc($query)) {
            $mesas[] = [
                'id' => (int)$row['id'],
                'numero' => 'Mesa ' . $row['numero'],
                'ocupada' => ($row['total_productos'] > 0) ? true : false
            ];
        }

        echo json_encode($mesas);
        exit;
    }

    //cambiar contraseña
    if ($_POST['action'] == 'changePassword') {


        //print_r($_POST);
        //exit;

        if (!empty($_POST['passActual']) && !empty($_POST['passNuevo'])) {

            $password 	= md5($_POST['passActual']);
            $newPass	= md5($_POST['passNuevo']);
            $idUser  	= $_SESSION['idUser'];



            $cod 		= '';
            $msg		= '';
            $arrData 	= array();

            $query_user = mysqli_query($conection, "SELECT * FROM usuario WHERE clave = '$password' and usuario = $idUser");

            $result = mysqli_num_rows($query_user);

            if ($result > 0) {

                $query_update = mysqli_query($conection, "UPDATE usuario SET clave = '$newPass'WHERE usuario = $idUser");
                mysqli_close($conection);

                if ($query_update) {
                    $code = '00';
                    $msg = "Su contraseña se ha actualizado con éxito.";
                } else {
                    $code = '2';
                    $msg = "No es Posible cambiar su contraseña.";
                }

            } else {
                $code = '1';
                $msg = "Su contraseña actual es incorrecta.";

            }
            $arrData = array('cod' => $code, 'msg' => $msg);
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);

        } else {
            echo "error";
        }
        exit;
    }






    if ($_POST['action'] == 'formCliente') {

        //print_r($_POST);exit;


        if (empty($_POST['co'])) {
            $btn =  '<a href="#" class="boton rojo closeModal" onclick="closeModal2();"><i class="fas fa-ban"></i> Cerrar</a>';
        } else {
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



    if ($_POST['action'] == 'addCliente') {

        //print_r($_POST);
        //print_r($_FILES);
        //exit;

        if (empty($_POST['cedula']) || empty($_POST['nombre']) || empty($_POST['p_apellido']) || empty($_POST['correo']) || empty($_POST['direccion']) || empty($_POST['telefono'])) {
            echo 1;
            exit;

        } else {


            $usuario 		= $_POST['cedula'];
            $nombre 		= $_POST['nombre'];
            $p_apellido 	= $_POST['p_apellido'];
            $s_apellido 	= $_POST['s_apellido'];
            $correo 		= $_POST['correo'];
            $direccion 		= $_POST['direccion'];
            $telefono 		= $_POST['telefono'];


            $query = mysqli_query($conection, "SELECT * FROM clientes WHERE usuario = '$usuario'");

            $result = mysqli_num_rows($query);


            if ($result > 0) {
                echo 2;
                exit;

            } else {

                $query_insert = mysqli_query($conection, "INSERT INTO clientes(usuario,nombre,p_apellido,s_apellido,correo_c,direccion,telefono) VALUES('$usuario','$nombre','$p_apellido','$s_apellido','$correo','$direccion','$telefono')");

                if ($query_insert) {

                    $arrayData = array();

                    $arrayData['cedula'] 	= $usuario;
                    $arrayData['nombre'] 	= $nombre;
                    $arrayData['apellido'] 	= $p_apellido;

                    echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
                    exit;

                } else {

                    echo 3;
                    exit;

                }
            }


        }

    }




    if ($_POST['action'] == 'formUsuario') {

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

    if ($_POST['action'] == 'addUsuario') {

        //print_r($_POST);
        //print_r($_FILES);
        //exit;

        if (empty($_POST['usuario']) || empty($_POST['nombre']) || empty($_POST['apellido']) || empty($_POST['correo']) || empty($_POST['clave']) || empty($_POST['rol']) || empty($_POST['lugar'])) {
            echo 1;
            exit;

        } else {



            $usuario 		= $_POST['usuario'];
            $nombre 		= $_POST['nombre'];
            $apellido 		= $_POST['apellido'];
            $correo 		= $_POST['correo'];
            $clave 			= $_POST['clave'];
            $rol 			= $_POST['rol'];


            $query = mysqli_query($conection, "SELECT * FROM usuario WHERE usuario = '$usuario'");

            $result = mysqli_fetch_array($query);


            if ($result > 0) {

                echo 2;
                exit;

            } else {

                $query_insert = mysqli_query($conection, "INSERT INTO usuario(usuario,nombre,apellido,correo,clave,rol) VALUES('$usuario','$nombre','$apellido','$correo','$clave','$rol')");

                if ($query_insert) {

                    echo 1;
                    exit;

                } else {
                    echo 3;
                    exit;

                }
            }


        }

    }

    if ($_POST['action'] == 'formProducto') {

        //print_r($_POST);exit;

        $query = mysqli_query($conection, "SELECT * FROM categorias");

        $result = mysqli_num_rows($query);


        if ($result > 0) {
            $options = '';

            while ($data = mysqli_fetch_assoc($query)) {

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

    if ($_POST['action'] == 'formAnadirAtributo') {

        //print_r($_POST);exit;

        $id = $_POST['co'];

        $query = mysqli_query($conection, "SELECT * FROM atributos_productos");

        $result = mysqli_num_rows($query);


        if ($result > 0) {
            $atributos = '';

            while ($data = mysqli_fetch_assoc($query)) {

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

    if ($_POST['action'] == 'formAddTipo') {

        //print_r($_POST);exit;

        $id = $_POST['co'];

        $query = mysqli_query($conection, "SELECT * FROM tipo_atributos");

        $result = mysqli_num_rows($query);


        if ($result > 0) {
            $tipos = '';

            while ($data = mysqli_fetch_assoc($query)) {

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


                while ($data_tipo = mysqli_fetch_assoc($query_1)) {

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



    if ($_POST['action'] == 'formEditarProducto') {

        //print_r($_POST);exit;


        $id = $_POST['co'];

        $query_producto = mysqli_query($conection, "SELECT
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


        if ($result_producto == 1) {


            $data_producto = mysqli_fetch_assoc($query_producto);

            $producto 	= $data_producto['producto'];
            $costo 		= $data_producto['costo'];
            $precio 	= $data_producto['precio'];
            $precio2 	= $data_producto['precio2'];
            $precio3 	= $data_producto['precio3'];
            $options2 	= '<option value="'.$data_producto['idCategoria'].'">'.$data_producto['categoria'].'</option>';
            $options3 	= '<option value="'.$data_producto['idLugar'].'">'.$data_producto['lugar'].'</option>';


        }


        $query = mysqli_query($conection, "SELECT * FROM categorias");

        $result = mysqli_num_rows($query);


        if ($result > 0) {
            $options = '';

            while ($data = mysqli_fetch_assoc($query)) {

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

        if ($dividirBtn == 1 and $mesa >= 0) {
            $btn = '<button type="button" class="boton verde" onclick="event.preventDefault(); anadirForm2('.$dividir.',' . $mesa . ');" style="margin: 0px 8px"><i class="fas fa-cash-register"></i>Dividir</button>';

        } else {

            $btn = "";
        }



        // Construir el formulario HTML
        echo '<div class="scroll">
            <form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault();">
                <h1><i class="fas fa-clipboard-list fa-2x"></i><br><br>Procesar Pedido #' . $mesa . '</h1>
                <h2>Datos para el Pedido</h2>
                <div class="pagos aligncenter clientePagos">
                    <i class="fas fa-user fa-2x"></i>
                    <div class="datosCliente" style="display:none">' . $cliente . '</div>
                    <div class="datosCliente">
                        <input type="text" name="nombreCliente" id="nombreCliente" value="Cliente" style="margin-bottom: 10px">
                         <input type="text" name="correoMarketing" id="correoMarketing" placeholder="Correo Electrónico">
                    </div>

                   
                </div>
                 

                 <div class="pagos pagos2 block">
                    <div class="preciosFinal"><h2>Entrega</h2><input type="number" name="entrega" id="entrega" step="0.01" onkeyup="calcular2();" style="width: 120px"></div>
                    <div class="preciosFinal"><h2>Subtotal</h2><h2 id="subtotal">$ ' . $final . '</h2></div>
                    <div class="preciosFinal"><h2>Descuento</h2><h2 id="descuento">$ 0.00</h2></div>
                    <div class="preciosFinal"><h2>Total</h2><h2 id="total">$ ' . $final . '</h2></div>
                    <div class="preciosFinal"><h2>Cambio</h2><h2 id="cambio">$ 0.00</h2></div>
                </div>

                <h2>Método de Pago</h2>
                <div class="pagos pagos2">
                    <input onclick="seleccionarPago(1);" type="radio" name="pago" class="pago" value="1" checked>
                    <label for="efectivo" style="margin: 0px 5px">Efectivo</label >

                    <input onclick="seleccionarPago(2);" type="radio" name="pago" class="pago" value="2">
                    <label for="tarjeta" style="margin: 0px 5px">Tarjeta</label>

                    <input onclick="seleccionarPago(3);" type="radio" name="pago" class="pago" value="3">
                    <label for="transferencia" style="margin: 0px 5px">Transferencia</label>

                    <input onclick="seleccionarPago(4);" type="radio" name="pago" class="pago" value="4">
                    <label for="deuna" style="margin: 0px 5px"  >DeUna</label>
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
                while (mysqli_next_result($conection)) {
                    ;
                }

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


    if ($_POST['action'] == 'codigoPromocional') {

        //print_r($_POST);
        //print_r($_FILES);
        //exit;

        if (empty($_POST['codigo'])) {
            echo 'error';
            exit;

        } else {



            $codigo 		= $_POST['codigo'];
            $total 			= $_POST['total'];
            $fecha 			= date('Y-m-d');


            $query = mysqli_query($conection, "SELECT * FROM codigos_promocionales WHERE codigo = '$codigo' AND estatus = 1");

            $result = mysqli_num_rows($query);


            if ($result > 0) {

                $arrayData = array();

                $data 						= mysqli_fetch_assoc($query);
                $fecha_inicio 				= $data['fecha_inicio'];
                $fecha_fin 					= $data['fecha_fin'];


                $arrayData['descripcion'] 	= $data['descripcion'];
                $arrayData['id_cupon'] 		= $data['id'];


                if (empty($data['porcentaje'])) {

                    $totalDescuento = $total - $data['dinero'];
                    $descuento = $data['dinero'];

                } else {

                    $totalDescuento =  $total - (($total * $data['porcentaje']) / 100);
                    $descuento = (($total * $data['porcentaje']) / 100);
                }

                $arrayData['descuento'] 	= number_format($descuento, 2);
                $arrayData['total'] 		= number_format($totalDescuento, 2);


                if ($fecha >= $fecha_inicio and $fecha <= $fecha_fin) {

                    echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
                    exit;

                } else {
                    echo 2;
                    exit;
                }

            } else {

                echo 3;
                exit;
            }


        }

    }


    if ($_POST['action'] == 'formCategoria') {

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

    if ($_POST['action'] == 'addCategoria') {

        //print_r($_POST);
        //print_r($_FILES);
        //exit;

        if (empty($_POST['categoria'])) {
            echo 1;
            exit;

        } else {

            $categoria 		= $_POST['categoria'];

            $query_insert = mysqli_query($conection, "INSERT INTO categorias(categoria) VALUES('$categoria')");

            if ($query_insert) {

                echo 2;

            } else {

                echo 3;
                exit;

            }

        }
    }


    //TODO: CAJAS

    if ($_POST['action'] == 'formCaja') {

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

    if ($_POST['action'] == 'addCaja') {

        //print_r($_POST);
        //print_r($_FILES);
        //exit;

        if (empty($_POST['lugar'])) {
            echo 1;
            exit;

        } else {

            $lugar 		= $_POST['lugar'];

            $query_insert = mysqli_query($conection, "INSERT INTO cajas(lugar) VALUES('$lugar')");

            if ($query_insert) {

                echo 2;

            } else {

                echo 3;
                exit;

            }

        }

    }


    if ($_POST['action'] == 'arqueoCajas') {

        //print_r($_POST);
        //exit;

        $id = $_POST['co'];
        $query = mysqli_query($conection, "SELECT a.id,u.nombre,u.apellido,a.fecha_inicio,a.fecha_fin,a.monto_inicial,a.monto_final,a.total_ventas,a.total_cash,a.estatus FROM arqueo_caja a INNER JOIN usuario u ON a.id_usuario = u.usuario WHERE a.id_caja = $id ORDER BY a.id DESC LIMIT 5");
        $result = mysqli_num_rows($query);
        $data = '';

        if ($result > 0) {
            $table = '';
            $action2 = "'verCierreCaja'";
            $action = "'formCerrarCaja'";

            while ($data = mysqli_fetch_assoc($query)) {

                if ($data['estatus'] == 1) {


                    $estado = '<span class="pagada">Abierto</span>';
                    $boton  = '<button type="button" class="btn_view" onclick="anadirForm2('.$action.','.$data['id'].');"><i class="fas fa-eye"></i></button>';

                } else {

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
									<td class="textcenter">'.$boton.'<button type="button" class="btn_lista" onclick="anadirForm2('.$action2.','.$data['id'].');"><i class="fas fa-list"></i></button></td>
									</tr>';
            }
        } else {
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
        $user2 = md5($_SESSION['idUser']);

        // Consultar datos del arqueo de caja
        $query_pro_temp = mysqli_query($conection, "SELECT * FROM detalle_temp WHERE token_user = '$user2'");

        if (mysqli_num_rows($query_pro_temp) > 0) {

            echo '<form action="" method="post" name="form_add_product" class="cierreCaja" id="form_add_product" onsubmit="event.preventDefault(); sendDataForm();" style="width: 200px; height:auto;">
                    <div class="acciones wd100">
                    <h2 style="text-align:center; ">Existen ordenes abiertas</h2>
                    </div>
                    <div class="acciones wd100">
                            
                        <a href="#" class="btn_ok closeModal" onclick="closeModal2();"><i class="fas fa-ban"></i> Cerrar</a>
                    </div>
             
                </form>';
            exit;


        }

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
            if (mysqli_num_rows($query_ventas) > 0) {

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

            } else {
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
                        $estilo = 'sty  le="color: red;"';
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
            <span>Efectivo</span>
            <span>$ ' . number_format($entradasEfectivo - $salidasEfectivo, 2) .'</span>
        </div>
        <div class="caja_valores">
            <span>Transferencia</span>
            <span>$ ' . number_format($entradasTransferencia - $salidasTransferencia, 2) .'</span>
        </div>';
            } else {
                $salidasHTML .= '
        <div class="caja_valores">
            <span>No hay Movimientos</span>
        </div>';
            }


            // === NUEVO BLOQUE: traer códigos de pago ===
            $query_codigos = mysqli_query($conection, "
            SELECT 
                tipopago,
                codigopago,
                SUM(totalfactura) AS total
            FROM factura
            WHERE caja = $id_caja
              AND estatus = 1
              AND tipopago != 1
              AND fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'
            GROUP BY tipopago, codigopago
            ORDER BY tipopago, codigopago
        ");

            $codigosAgrupados = [];

            if (mysqli_num_rows($query_codigos) > 0) {
                while ($row = mysqli_fetch_assoc($query_codigos)) {
                    $tipoPago = tipoPagoNombre($row['tipopago']);
                    $codigo = $row['codigopago'];
                    $total = $row['total'];

                    if ($row['tipopago'] == 2) {
                        $total = $total / 0.94;
                    }

                    $total = number_format($total, 2);

                    if (!isset($codigosAgrupados[$tipoPago])) {
                        $codigosAgrupados[$tipoPago] = [];
                    }

                    $codigosAgrupados[$tipoPago][] = [
                        'codigo' => $codigo,
                        'total' => $total
                    ];
                }
            }

            $codigosHTML = '';

            if (!empty($codigosAgrupados)) {
                $codigosHTML .= '<h3>Códigos de Pago</h3><hr>';
                foreach ($codigosAgrupados as $tipo => $codigos) {
                    $codigosHTML .= "<strong>$tipo:</strong>";
                    $codigosHTML .= '
            <table border="1" cellpadding="4" cellspacing="0" style="border-collapse: collapse; margin-top: 5px; margin-bottom: 10px; width: 100%;">
                <thead style="background-color: #f2f2f2;">
                    <tr>
                        <th style="text-align: left;">Código</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>';
                    foreach ($codigos as $item) {
                        $codigosHTML .= '
                    <tr>
                        <td>' . htmlspecialchars($item['codigo']) . '</td>
                        <td style="text-align: right;">$ ' . $item['total'] . '</td>
                    </tr>';
                    }
                    $codigosHTML .= '
                </tbody>
            </table>
            <hr>';
                }
            }

            // === FIN BLOQUE NUEVO ===




            // Calcular el monto final a entregar
            $totalSalidas = $entradasEfectivo - $salidasEfectivo + $entradasTransferencia - $salidasTransferencia;
            $entregar = ($inicial + $montoFinal) + $totalSalidas;

            $totalFinalEfectivoEntregar = $montoEfectivo + $inicial + $entradasEfectivo - $salidasEfectivo;

            $totalFinalTransferenciaEntregar = $montoTransferencia + $entradasTransferencia - $salidasTransferencia;

            $totalEfectivo = number_format($totalFinalEfectivoEntregar, 2, '.', '');
            $totalTarjeta = number_format($montoTarjeta, 2, '.', '');
            $totalTransferencia = number_format($totalFinalTransferenciaEntregar, 2, '.', '');
            $totalDeUna = number_format($montoDeUna, 2, '.', '');

            $_SESSION['preview_form'] = [
                    'fecha_inicio' => $fecha_inicio,
                    'fecha_fin' => $fecha_fin,
                    'monto_inicial' => $inicial,
                    'ventas_totales' => $ventasFinal,
                    'monto_total' => $montoFinal,
                    'movimientos' => $salidasHTML,
                    'total_salidas' => number_format($totalSalidas, 2) ,
                    'entregar' => $entregar,
                    'montoEfectivo' => $totalFinalEfectivoEntregar,
                    'montoTarjeta' => $montoTarjeta != 0 ? number_format($montoTarjeta / 0.94, 2) : '0.00',
                    'montoTransferencia' => $totalFinalTransferenciaEntregar,
                    'montoDeUna' => $montoDeUna,
                    'detalle_ventas' => $inputs,
                    'observaciones' => '',
                    'compras' => ''
                ];

            $_SESSION['data_cierre_pdf'] = [
            'idArqueo' => $id,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'monto_inicial' => $inicial,
            'monto_total' => $montoFinal,
            'total_cash' => $montoFinal,
            'total_ventas' => $ventasFinal,
            'total_efectivo' => $totalFinalEfectivoEntregar,
            'total_tarjeta' => $montoTarjeta != 0 ? number_format($montoTarjeta / 0.94, 2, '.', '') : '0.00',
            'total_transferencia' => $totalFinalTransferenciaEntregar,
            'total_deuna' => $montoDeUna,
            'movimientos' => $salidasHTML,
            // Los siguientes cuatro son necesarios para evitar warnings
            'montoEfectivo' => $totalFinalEfectivoEntregar,
            'montoTarjeta' => $montoTarjeta != 0 ? number_format($montoTarjeta / 0.94, 2, '.', '') : 0,
            'montoTransferencia' => $totalFinalTransferenciaEntregar,
            'montoDeUna' => $montoDeUna,
            'total_salidas' => number_format($totalSalidas, 2) ,

            'efectivo' => 0,
            'tarjeta' => 0,
            'transferencia' => 0,
            'deuna' => 0,
            'monto_final' => 0,
            'nombre' => $_SESSION['nombre'],
            'apellido' => $_SESSION['apellido'],
            'salidas' => [],
            'observaciones' => '',
            'compras' => ''
        ];


            $comillas = "'";


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

                        ' . $codigosHTML . '
                    

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
                            <div class="empleado" style="margin: 0px 5px">
                                <label for="empleado_1">
                                    Trabajador 1
                                    <input type="number" name="empleado_1" id="empleado_1" value="23" readonly>
                                </label>
                            </div>
                            <div class="empleado" style="margin: 0px 5px">
                                <label for="empleado_cristina">
                                    Trabajador 2
                                    <input type="number" name="empleado_2" id="empleado_cristina" onkeyup="calcular3();">
                                </label>
                            </div>
                            <div class="empleado" style="margin: 0px 5px">
                                <label for="empleado_patricia">
                                    Trabajador 3
                                    <input type="number" name="empleado_3" id="empleado_patricia" onkeyup="calcular3();">
                                </label>
                            </div>
                        </div>

                        <div style="margin: 5px;">
                        <label for="efectivo_neto">
                            Efectivo Final:
                            <input type="number" id="efectivo_neto" readonly>
                        </label>
                    </div>

                        <div>
                        <label>Observaciones</label>
                        <textarea class="wd100"  style="height: 100px" id="observaciones" name="observaciones"></textarea>
                        </div>

                        <div>
                        <label>Compras</label>
                        <textarea class="wd100" style="height: 100px" id="compras" name="compras"></textarea>
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
                            <a href="#" onclick="prepararDatosParaPDF().then(() => window.open('.$comillas.'generarCierrePDF.php?preview=1'.$comillas.', '.$comillas.'_blank'.$comillas.'));" class="btn_ok" style="margin-top:10px;">
                                <i class="fas fa-file-pdf"></i> Ver Cierre en PDF
                            </a>

                        </div>

                        <input type="hidden" name="monto_efectivo_calc" value="' . $totalFinalEfectivoEntregar . '">
                        <input type="hidden" name="monto_tarjeta_calc" value="' . ($montoTarjeta != 0 ? number_format($montoTarjeta / 0.94, 2, '.', '') : '0.00') . '">
                        <input type="hidden" name="monto_transferencia_calc" value="' . $totalFinalTransferenciaEntregar . '">
                        <input type="hidden" name="monto_deuna_calc" value="' . $montoDeUna . '">
                        <input type="hidden" name="redirigir_pdf" value="1">

                        
                </form>';
        }
    }

    if ($_POST['action'] == 'actualizarPreviewPDF') {
        $campos = [
            'efectivo' => floatval($_POST['efectivo']),
            'tarjeta' => floatval($_POST['tarjeta']),
            'transferencia' => floatval($_POST['transferencia']),
            'deuna' => floatval($_POST['deuna']),
            'monto_final' => floatval($_POST['efectivo']) + floatval($_POST['tarjeta']) + floatval($_POST['transferencia']) + floatval($_POST['deuna']),
            'observaciones' => trim($_POST['observaciones']),
            'compras' => trim($_POST['compras']),
        ];

        foreach ($campos as $k => $v) {
            $_SESSION['preview_form'][$k] = $v;
            $_SESSION['data_cierre_pdf'][$k] = $v;
        }

        exit('OK');
    }


    if ($_POST['action'] == 'cerrarCaja') {


        //print_r($_POST);
        //exit;
        // Inicio de la transacción
        mysqli_begin_transaction($conection, MYSQLI_TRANS_START_READ_WRITE);

        try {
            if (empty($_POST['co']) || empty($_POST['monto_final']) || empty($_POST['empleado_1'])) {
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
            $totalFinalEfectivoEntregar = isset($_POST['monto_efectivo_calc']) ? floatval($_POST['monto_efectivo_calc']) : 0;
            $totalFinalTransferenciaEntregar = isset($_POST['monto_transferencia_calc']) ? floatval($_POST['monto_transferencia_calc']) : 0;
            $totalTarjetaCalculado = isset($_POST['monto_tarjeta_calc']) ? floatval($_POST['monto_tarjeta_calc']) : 0;
            $totalDeUnaCalculado = isset($_POST['monto_deuna_calc']) ? floatval($_POST['monto_deuna_calc']) : 0;

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
            SELECT k.id AS id_salida, k.valor, k.tipo_moneda, k.id_usuario, p.nombres AS nombre_usuario, k.motivo, k.tipo_transaccion
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
                    'salidas' => $salidas, // Añadir todas las salidas para la impresión
                    'total_efectivo' => $totalFinalEfectivoEntregar,
                    'total_tarjeta' => $totalTarjetaCalculado,
                    'total_transferencia' => $totalFinalTransferenciaEntregar,
                    'total_deuna' => $totalDeUnaCalculado,
                    'observaciones' => $_POST['observaciones'],
                    'compras' => $_POST['compras'],
                    'salarios' => $salarios

                ];

                // === AUDITORÍA DE MONTO ENTREGADO VS CALCULADO ===

                $calculado = [
                    'efectivo' => $totalFinalEfectivoEntregar,
                    'tarjeta' => $totalTarjetaCalculado,
                    'transferencia' => $totalFinalTransferenciaEntregar,
                    'deuna' => $totalDeUnaCalculado
                ];


                $entregado = [
                    'efectivo' => floatval($efectivo),
                    'tarjeta' => floatval($tarjeta),
                    'transferencia' => floatval($transferencia),
                    'deuna' => floatval($deuna)
                ];

                $diferencias = compararMontosEntregadosVsCalculados($calculado, $entregado);

                // Registrar en tabla de auditoría
                foreach ($diferencias as $tipo => $info) {
                    if ($tipo === 'TOTAL') {
                        continue;
                    }

                    $tipo_pago = ucfirst($tipo); // capitalizar
                    $estado = $info['estado'];
                    $diferencia = $info['diferencia'];

                    mysqli_query($conection, "
                INSERT INTO auditoria_cierre_caja (id_cierre, tipo_pago, estado, diferencia) 
                VALUES ($id_cierre, '$tipo_pago', '$estado', $diferencia)
            ");
                }

                // Traer códigos de pago agrupados por tipo de pago para este cierre
                $tipos_codigos_pago = [];

                $query_codigos = mysqli_query($conection, "
                SELECT 
                    tipopago, 
                    codigopago, 
                    COUNT(*) AS cantidad, 
                    SUM(totalfactura) AS total
                FROM factura
                WHERE id_cierre = $id_cierre
                AND estatus = 4
                AND tipopago != 1
                GROUP BY tipopago, codigopago
                ORDER BY tipopago, codigopago
            ");

                if (mysqli_num_rows($query_codigos) > 0) {
                    while ($row = mysqli_fetch_assoc($query_codigos)) {
                        $tipoPago = tipoPagoNombre($row['tipopago']);
                        $codigo = $row['codigopago'];
                        $cantidad = $row['cantidad'];
                        $total = $row['total'];

                        if ($row['tipopago'] == 2) {
                            $total = $total / 0.94;
                        }

                        $total = number_format($total, 2);

                        if (!isset($tipos_codigos_pago[$tipoPago])) {
                            $tipos_codigos_pago[$tipoPago] = [];
                        }

                        $tipos_codigos_pago[$tipoPago][] = [
                            'codigo' => $codigo,
                            'cantidad' => $cantidad,
                            'total' => $total
                        ];
                    }
                }

                // Guardar en data
                $data['pagos_codigos'] = $tipos_codigos_pago;


                imprimirCierreCaja($data);

                echo 10;

            } else {
                echo 2;
                mysqli_rollback($conection);
                exit;
            }
        } catch (Exception $e) {
            mysqli_rollback($conection);
            echo 2;
            exit;
        }
    }


    if ($_POST['action'] == 'verCierreCaja') {

        //print_r($_POST);exit;

        $id = $_POST['co'];

        $query 	= mysqli_query($conection, "SELECT id,id_caja,fecha_inicio,fecha_fin FROM arqueo_caja WHERE id = $id");

        $result 			= mysqli_num_rows($query);
        $data_caja 			= mysqli_fetch_assoc($query);
        $fecha_inicio 		= $data_caja['fecha_inicio'];

        if (empty($data_caja['fecha_fin'])) {
            $fecha_fin 			= date('Y-m-d G:i:s');
        } else {
            $fecha_fin 			= $data_caja['fecha_fin'];
        }



        $query = mysqli_query($conection, "SELECT SUM(dt.cantidad) as cantidad, SUM(dt.precio_venta) as precio_total,p.producto FROM detalle_factura dt INNER JOIN producto p ON p.codproducto = dt.codproducto INNER JOIN factura f ON f.nofactura = dt.nofactura WHERE fecha BETWEEN '$fecha_inicio' AND '$fecha_fin' GROUP BY p.producto");
        $result = mysqli_num_rows($query);
        $data = '';

        if ($result > 0) {
            $table = '';
            $action = "'verCierreCaja'";

            while ($data = mysqli_fetch_assoc($query)) {



                $table .= '	<tr>
									<td class="textcenter wd10">'.$data['cantidad'].'</td>
									<td class="textcenter">'.$data['producto'].'</td>
									<td class="textcenter">'.$data['precio_total'].'</td>
									<td class="textcenter wd12"></td>
									
									
									</tr>';
            }
        } else {
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




    if ($_POST['action'] == 'formDetalleProducto') {

        //print_r($_POST);exit;

        $id = $_POST['co'];

        $query = mysqli_query($conection, "SELECT codproducto,atributos FROM detalle_temp WHERE correlativo = $id");

        $result = mysqli_num_rows($query);

        if ($result == 1) {

            $data = mysqli_fetch_assoc($query);
            $id2 = $data['codproducto'];

            $query_atributo = mysqli_query($conection, "SELECT * FROM atributos_productos WHERE codproducto = $id2");
            $result_atributo = mysqli_num_rows($query_atributo);

            if ($result_atributo > 0) {

                $tabla = '';
                $todo = array();
                $todo = json_decode($data['atributos'], JSON_UNESCAPED_UNICODE);

                while ($data_atributo = mysqli_fetch_assoc($query_atributo)) {

                    if (!empty($data['atributos'])) {

                        $buscar = $data_atributo['atributo'];
                        //echo $buscar;
                        //print_r($todo);

                        $check = '';
                        if (array_key_exists($buscar, $todo)) {

                            $check = 'checked';

                        }


                        $tabla .= '<tr><td>'.$data_atributo['atributo'].'</td>
														<td><input type="checkbox" name="'.$data_atributo['atributo'].'" value="No" '.$check.'></td></tr>';


                    } else {

                        $tabla .= '<tr><td>'.$data_atributo['atributo'].'</td>
											<td><input type="checkbox" name="'.$data_atributo['atributo'].'" value="No"></td></tr>';
                    }
                }


            } else {
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

    if ($_POST['action'] == 'addAtributoProducto') {

        //print_r($_POST);
        //print_r($_FILES);
        //exit;

        if (empty($_POST['co'])) {
            echo 1;
            exit;

        } else {

            $id = $_POST['co'];

            $atributos = $_POST['atributo'];
            $atributos = array_unique($atributos);
            $atributos = array_filter($atributos);
            $atributos_csv = implode(',', $atributos);


            $query_update = mysqli_query($conection, "UPDATE producto SET codatributos = '$atributos_csv' WHERE codproducto = $id");
            if ($query_update) {

                echo 'ok';
                exit;
            }

        }

    }

    if ($_POST['action'] == 'addTipoAtributo') {

        print_r($_POST);
        //print_r($_FILES);
        exit;

        if (empty($_POST['co'])) {
            echo 1;
            exit;

        } else {

            $id 	= $_POST['co'];
            $tipo 	= $_POST['atributo'];
            $tipo 	= array_unique($tipo);
            $tipo 	= array_filter($tipo);

            foreach ($tipo as $key) {

            }

            $query_update = mysqli_query($conection, "UPDATE producto SET codatributos = '$atributos_csv' WHERE codproducto = $id");
            if ($query_update) {

                echo 'ok';
                exit;
            }

        }

    }


    if ($_POST['action'] == 'formClienteComanda') {

        //print_r($_POST);exit;

        $mesa 		= $_POST['mesa'];

        if (empty($mesa)) {
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

    if ($_POST['action'] == 'formClientePre') {

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

    if ($_POST['action'] == 'formSalidaDinero') {

        //print_r($_POST);exit;


        $query = mysqli_query($conection, "SELECT * FROM personas WHERE estatus = 1");
        $result = mysqli_num_rows($query);
        $data = '';

        if ($result > 0) {

            $select = '';
            while ($data = mysqli_fetch_assoc($query)) {


                $select .= '<option value="'.$data['id'].'">'.$data['nombres'].' </option>';


            }
        } else {
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

    if ($_POST['action'] == 'salidaDinero') {

        //print_r($_POST);
        //print_r($_FILES);
        //exit;

        if (empty($_POST['nombre']) || empty($_POST['monto']) || empty($_POST['motivo']) || empty($_POST['tipo'])) {
            echo 1;
            exit;

        } else {

            $fecha 					= date('Y-m-d G:i:s');
            $monto 					= $_POST['monto'];
            $idCliente 				= $_POST['nombre'];
            $moneda 				= $_POST['moneda'];
            $transaccion 			= $_POST['tipo'];
            $user 					= $_SESSION['idUser'];
            $motivo 				= $_POST['motivo'];
            $cantidad 				= 1;
            $query 					= mysqli_query($conection, "INSERT INTO `kardex`(`fecha`, `cantidad`, `valor`, `tipo_moneda`, `tipo_transaccion`, `id_usuario`, `id_user`, `motivo`) VALUES('$fecha','$cantidad',$monto,'$moneda','$transaccion','$idCliente',
			'$user','$motivo')");

            if (!$query) {
                echo 4;
                exit;
            }
            $data = '';
            $data = array();
            $data['id']                 = mysqli_insert_id($conection);


            $query_2 = mysqli_query($conection, "SELECT * FROM personas WHERE id = $idCliente");

            $result = mysqli_num_rows($query_2);

            if ($result > 0) {

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
            } else {
                echo 3;
                exit;
            }
        }
    }





    if ($_POST['action'] == 'formReserva') {
        //include '../conexion.php';

        // Clientes
        $clientes = mysqli_query($conection, "SELECT usuario, CONCAT(nombre, ' ', p_apellido) AS nombre FROM clientes ORDER BY nombre ASC");

        // Habitaciones disponibles
        $habitaciones = mysqli_query($conection, "SELECT idhabitacion, numero FROM habitaciones WHERE habilitada = 1");

        // Tarifas por persona
        $tarifas = mysqli_query($conection, "SELECT id, nombre, precio_por_persona FROM tarifas_habitaciones WHERE habilitada = 1");
        $tarifa_data = [];
        while ($t = mysqli_fetch_assoc($tarifas)) {
            $tarifa_data[] = $t;
        }

        // Extras
        $extras = mysqli_query($conection, "SELECT tipo_extra, valor FROM tarifa_extras WHERE habilitado = 1 ORDER BY valor ASC");
        $extras_data = ['desayuno' => [], 'tour' => []];
        while ($e = mysqli_fetch_assoc($extras)) {
            $extras_data[$e['tipo_extra']][] = $e['valor'];
        }

        // Lugares de tour
        $tours = mysqli_query($conection, "SELECT id, nombre FROM lugares_tour WHERE activo = 1 ORDER BY nombre ASC");
        $tour_data = [];
        while ($t = mysqli_fetch_assoc($tours)) {
            $tour_data[] = $t;
        }


        ob_start(); ?>
<form action="" method="post" name="form_reserva" id="formReserva"
    onsubmit="event.preventDefault(); sendDataReserva();">
    <input type="hidden" name="action" value="guardarReserva">

    <h1><i class="fas fa-calendar-plus"></i> Nueva Reserva</h1>

    <!-- Cliente -->
    <div class="form_group">
        <label for="id_cliente">Cliente:</label>
        <div style="display: flex; gap: 5px;">
            <select name="id_cliente" id="id_cliente" class="js-example-basic-single notItemOne" required
                style="flex: 1;">
                <?php while ($c = mysqli_fetch_assoc($clientes)) {
                    echo '<option value="'.$c['usuario'].'">'.$c['nombre'].'</option>';
                } ?>
            </select>
            <button type="button" class="btn_new" onclick="abrirFormularioCliente()"
                style="margin: 0px;margin-left: 5px;"><i class="fas fa-user-plus"></i></button>
        </div>
    </div>

    <!-- Fechas -->
    <div class="form_group">
        <label for="fecha_entrada">Fecha Entrada:</label>
        <input type="date" name="fecha_entrada" id="fecha_entrada" required onchange="recalcularTotalReserva()">
    </div>
    <div class="form_group">
        <label for="fecha_salida">Fecha Salida:</label>
        <input type="date" name="fecha_salida" id="fecha_salida" required onchange="recalcularTotalReserva()">
    </div>

    <!-- Habitaciones -->
    <hr>
    <h3>Habitaciones</h3>
    <div id="habitacionesContainer">
        <div class="habitacion-row">
            <!-- HABITACION -->
            <select name="id_habitacion[]" class="id_habitacion" onchange="recalcularTotalReserva()" required>
                <option value="">Seleccione</option>
                <?php mysqli_data_seek($habitaciones, 0);
        while ($h = mysqli_fetch_assoc($habitaciones)) {
            echo '<option value="'.$h['idhabitacion'].'">Hab. '.$h['numero'].'</option>';
        } ?>
            </select>

            <!-- TARIFA -->
            <label>Adultos:</label>
            <select name="tarifa[]" class="select_tarifa" onchange="recalcularTotalReserva()" required>
                <?php foreach ($tarifa_data as $t) {
                    echo '<option value="'.$t['id'].'" data-precio="'.$t['precio_por_persona'].'" data-nombre="'.strtolower($t['nombre']).'">'.ucfirst($t['nombre']).' ($'.$t['precio_por_persona'].')</option>';
                } ?>
            </select>
            <input type="number" name="adultos[]" class="input_adultos" value="1" min="1"
                onchange="recalcularTotalReserva()">

            <label>Niños:</label>

            <select name="tarifa_nino[]" class="select_tarifa_nino" onchange="recalcularTotalReserva()">
                <?php foreach ($tarifa_data as $t) {
                    echo '<option value="'.$t['id'].'" data-precio="'.$t['precio_por_persona'].'" data-nombre="'.strtolower($t['nombre']).'">'.ucfirst($t['nombre']).' ($'.$t['precio_por_persona'].')</option>';
                } ?>
            </select>
            <input type="number" name="ninos[]" class="input_ninos" value="0" min="0"
                onchange="recalcularTotalReserva()">


            <!-- Extras -->
            <label><input type="checkbox" class="chk_desayuno" onchange="togglePrecio(this, 'precio_desayuno')">
                Desayuno</label>
            <select name="precio_desayuno[]" class="precio_desayuno" onchange="recalcularTotalReserva()" disabled>
                <option value="">$0.00</option>
                <?php foreach ($extras_data['desayuno'] as $d) {
                    echo '<option value="'.$d.'" data-precio="'.$d.'">$'.number_format($d, 2).'</option>';
                } ?>
            </select>


            <label><input type="checkbox" class="chk_tour" onchange="togglePrecio(this, 'precio_tour')"> Tour</label>
            <select name="precio_tour[]" class="precio_tour" onchange="recalcularTotalReserva()" disabled>
                <option value="">$0.00</option>
                <?php foreach ($extras_data['tour'] as $t) {
                    echo '<option value="'.$t.'" data-precio="'.$t.'">$'.number_format($t, 2).'</option>';
                } ?>
            </select>


            <label>Lugar Tour:</label>
            <select name="lugar_tour[]" class="lugar_tour">
                <option value="">Seleccione</option>
                <?php foreach ($tour_data as $t) {
                    echo '<option value="'.$t['id'].'">'.$t['nombre'].'</option>';
                } ?>
            </select>


            <span class="btn_remove" onclick="removeHabitacion(this)"><i class="fas fa-times"></i></span>
        </div>
    </div>

    <button type="button" class="btn_new_line" onclick="addHabitacion();"><i class="fas fa-plus-circle"></i> Añadir
        Habitación</button>

    <!-- Total y abono -->
    <div class="form_group">
        <label for="total">Total ($):</label>
        <input type="number" step="0.01" name="total" id="total" readonly>
    </div>
    <div class="form_group">
        <label for="abono">Abono ($):</label>
        <input type="number" step="0.01" name="abono" id="abono" value="0.00">
    </div>

    <div class="form_group">
        <label for="metodo_pago">Método de Pago de Abono:</label>
        <select name="metodo_pago" id="metodo_pago" required>
            <option value="efectivo">Efectivo</option>
            <option value="tarjeta">Tarjeta</option>
            <option value="transferencia">Transferencia</option>
        </select>
    </div>

    <div class="form_group">
        <label for="referencia_pago">Referencia / Documento:</label>
        <input type="text" name="referencia_pago" id="referencia_pago" placeholder="Opcional si aplica...">
    </div>


    <!-- Observaciones -->
    <div class="form_group">
        <label for="observaciones">Observaciones:</label>
        <textarea name="observaciones" id="observaciones" rows="3"></textarea>
    </div>

    <!-- Botones -->
    <div class="btn_block">
        <button type="submit" class="btn_save"><i class="fas fa-save"></i> Guardar</button>
        <a href="#" class="btn_cancel closeModal" onclick="closeModal('modalReserva')"><i class="fas fa-times"></i>
            Cancelar</a>
    </div>
</form>

<script>
    window.tarifasExtras = <?= json_encode($extras_data); ?> ;
</script>
<?php
                echo ob_get_clean();
    }

    if ($_POST['action'] == 'habitacionesDisponibles') {
        //include '../conexion.php';

        $entrada = $_POST['fecha_entrada'];
        $salida = $_POST['fecha_salida'];

        $query = mysqli_query($conection, "
        SELECT idhabitacion, numero 
        FROM habitaciones 
        WHERE habilitada = 1 
        AND estado = 'disponible'
        AND idhabitacion NOT IN (
            SELECT rd.id_habitacion
            FROM reservas r
            INNER JOIN reservas_detalle rd ON r.idreserva = rd.idreserva
            WHERE r.estado IN ('pendiente','confirmada','checkin')
            AND ('$entrada' < r.fecha_salida AND '$salida' > r.fecha_entrada)
            )
        ");

        $result = [];
        while ($row = mysqli_fetch_assoc($query)) {
            $result[] = $row;
        }

        echo json_encode($result);
        exit;
    }

    if ($_POST['action'] == 'verificarHabitacionesDisponiblesFinal') {
        //require_once 'conexion.php'; // o usa $conection si ya está activo

        $entrada = $_POST['fecha_entrada'];
        $salida  = $_POST['fecha_salida'];
        $habitaciones = json_decode($_POST['habitaciones'], true); // Recibe array JSON

        if (!is_array($habitaciones) || empty($habitaciones)) {
            echo json_encode([]);
            exit;
        }

        // Convertir array a cadena segura de IDs enteros
        $ids = implode(",", array_map('intval', $habitaciones));

        $sql = "SELECT DISTINCT rd.id_habitacion
            FROM reservas r
            INNER JOIN reservas_detalle rd ON r.idreserva = rd.idreserva
            WHERE r.estado IN ('pendiente','confirmada','checkin')
            AND rd.id_habitacion IN ($ids)
            AND ('$entrada' < r.fecha_salida AND '$salida' > r.fecha_entrada)";

        $query = mysqli_query($conection, $sql);

        $ocupadas = [];
        while ($row = mysqli_fetch_assoc($query)) {
            $ocupadas[] = $row['id_habitacion'];
        }

        echo json_encode($ocupadas);
        exit;
    }

    if ($_POST['action'] == 'guardarReserva') {
        mysqli_begin_transaction($conection);

        try {
            $id_cliente     = intval($_POST['id_cliente']);
            $fecha_entrada  = $_POST['fecha_entrada'];
            $fecha_salida   = $_POST['fecha_salida'];
            $observaciones  = mysqli_real_escape_string($conection, $_POST['observaciones'] ?? '');
            $total          = floatval($_POST['total']);
            $abono          = floatval($_POST['abono']);
            $usuario_id     = $_SESSION['idUser'] ?? 1;
            $canal_reserva  = 'recepción';

            $metodo_pago     = mysqli_real_escape_string($conection, $_POST['metodo_pago'] ?? 'efectivo');
            $referencia_pago = mysqli_real_escape_string($conection, $_POST['referencia_pago'] ?? '');

            if ($id_cliente <= 0 || empty($fecha_entrada) || empty($fecha_salida)) {
                throw new Exception('Faltan datos obligatorios');
            }

            if ($abono >= $total) {
                $estado_pago = 'pagado';
                $estado_reserva = 'confirmada';
            } elseif ($abono > 0) {
                $estado_pago = 'parcial';
                $estado_reserva = 'confirmada';
            } else {
                $estado_pago = 'pendiente';
                $estado_reserva = 'pendiente';
            }

            $sql = "INSERT INTO reservas (
            id_cliente, fecha_entrada, fecha_salida, total,
            estado_pago, estado, observaciones, canal_reserva, usuario_id
        ) VALUES (
            $id_cliente, '$fecha_entrada', '$fecha_salida', $total,
            '$estado_pago', '$estado_reserva', '$observaciones', '$canal_reserva', $usuario_id
        )";

            if (!mysqli_query($conection, $sql)) {
                throw new Exception('Error al registrar la reserva principal');
            }

            $idreserva = mysqli_insert_id($conection);

            // Validar arrays
            if (!isset($_POST['id_habitacion'], $_POST['tarifa'], $_POST['tarifa_nino'], $_POST['adultos'], $_POST['ninos'])) {
                throw new Exception('Faltan datos de habitaciones');
            }

            $ids_hab         = $_POST['id_habitacion'];
            $tarifas_adulto  = $_POST['tarifa'];
            $tarifas_nino    = $_POST['tarifa_nino'];
            $adultos         = $_POST['adultos'];
            $ninos           = $_POST['ninos'];
            $precio_desayuno = $_POST['precio_desayuno'] ?? [];
            $precio_tour     = $_POST['precio_tour'] ?? [];
            $lugares_tour    = $_POST['lugar_tour'] ?? [];

            for ($i = 0; $i < count($ids_hab); $i++) {
                $idh = intval($ids_hab[$i]);
                $tfa = intval($tarifas_adulto[$i]);
                $tfn = intval($tarifas_nino[$i]);
                $adt = intval($adultos[$i]);
                $nin = intval($ninos[$i]);

                $precio_adulto = 0;
                $resA = mysqli_query($conection, "SELECT precio_por_persona FROM tarifas_habitaciones WHERE id = $tfa LIMIT 1");
                if ($resA && mysqli_num_rows($resA) > 0) {
                    $rowA = mysqli_fetch_assoc($resA);
                    $precio_adulto = floatval($rowA['precio_por_persona']);
                }

                $precio_nino = 0;
                $resN = mysqli_query($conection, "SELECT precio_por_persona FROM tarifas_habitaciones WHERE id = $tfn LIMIT 1");
                if ($resN && mysqli_num_rows($resN) > 0) {
                    $rowN = mysqli_fetch_assoc($resN);
                    $precio_nino = floatval($rowN['precio_por_persona']);
                }

                $precioD = floatval($precio_desayuno[$i] ?? 0);
                $precioT = floatval($precio_tour[$i] ?? 0);
                $incluye_desayuno = ($precioD > 0) ? 1 : 0;
                $incluye_tour     = ($precioT > 0) ? 1 : 0;
                $lugar_tour = mysqli_real_escape_string($conection, $lugares_tour[$i] ?? '');

                $subtotal = $adt * ($precio_adulto + $precioD + $precioT) + $nin * ($precio_nino + $precioD + $precioT);

                $insertDetalle = "INSERT INTO reservas_detalle (
                idreserva, id_habitacion, adultos, ninos,
                incluye_desayuno, incluye_tour, lugar_tour,
                precio_unitario, precio_nino, precio_desayuno, precio_tour, subtotal
            ) VALUES (
                $idreserva, $idh, $adt, $nin,
                $incluye_desayuno, $incluye_tour, '$lugar_tour',
                $precio_adulto, $precio_nino, $precioD, $precioT, $subtotal
            )";

                if (!mysqli_query($conection, $insertDetalle)) {
                    throw new Exception('Error al insertar detalle de habitación');
                }
            }

            if ($abono > 0) {
                $insertPago = "INSERT INTO reservas_pagos (
                idreserva, monto, metodo_pago, referencia_pago, usuario_id
            ) VALUES (
                $idreserva, $abono, '$metodo_pago', '$referencia_pago', $usuario_id
            )";

                if (!mysqli_query($conection, $insertPago)) {
                    throw new Exception('Error al registrar el abono');
                }
            }

            mysqli_commit($conection);
            echo 'ok';
        } catch (Exception $e) {
            mysqli_rollback($conection);
            echo 'Error: ' . $e->getMessage();
        }
        exit;
    }


    if ($_POST['action'] == 'agregarAbono') {
        mysqli_begin_transaction($conection);

        try {
            $id = intval($_POST['idreserva']);
            $monto = floatval($_POST['monto']);
            $usuario_id = $_SESSION['idUser'] ?? 0;
            $fecha = date('Y-m-d H:i:s');
            $metodo_pago = intval($_POST['metodo_pago'] ?? 0);
            $referencia = trim($_POST['referencia'] ?? '');

            // Validaciones básicas
            if ($id <= 0 || $monto <= 0 || $metodo_pago <= 0) {
                throw new Exception('Datos inválidos');
            }

            // Validar referencia si aplica
            if (in_array($metodo_pago, [2, 3]) && $referencia == '') {
                throw new Exception('Debe ingresar una referencia para pagos con tarjeta o transferencia.');
            }

            // Verificar que la reserva exista y obtener total
            $res = mysqli_query($conection, "SELECT total FROM reservas WHERE idreserva = $id FOR UPDATE");
            if (!$res || mysqli_num_rows($res) == 0) {
                throw new Exception('Reserva no encontrada');
            }

            $row = mysqli_fetch_assoc($res);
            $total = floatval($row['total']);

            // Calcular abonos previos
            $pagos = mysqli_query($conection, "SELECT SUM(monto) AS abonos FROM reservas_pagos WHERE idreserva = $id");
            $data_pagos = mysqli_fetch_assoc($pagos);
            $abonos_actuales = floatval($data_pagos['abonos']);

            $nuevo_total_abonado = $abonos_actuales + $monto;

            if ($nuevo_total_abonado > $total) {
                $saldo_disponible = $total - $abonos_actuales;
                throw new Exception("El abono excede el total de la reserva. Saldo disponible: $" . number_format($saldo_disponible, 2));
            }

            if (in_array($metodo_pago, [2, 3])) {
                $checkRef = mysqli_query($conection, "SELECT idpago FROM reservas_pagos WHERE referencia_pago = '$referencia'");
                if (mysqli_num_rows($checkRef) > 0) {
                    throw new Exception('La referencia ya fue registrada anteriormente.');
                }
            }

            // Insertar abono
            $stmt = mysqli_prepare($conection, "INSERT INTO reservas_pagos (idreserva, monto, metodo_pago, referencia_pago, fecha_pago, usuario_id) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "idisss", $id, $monto, $metodo_pago, $referencia, $fecha, $usuario_id);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('No se pudo registrar el abono');
            }


            // Si antes no había abonos y ahora hay, actualizar estado de la reserva a confirmada
            if ($nuevo_total_abonado > 0) {
                $estado_pago = ($nuevo_total_abonado >= $total) ? 'pagado' : 'parcial';
                $upd = mysqli_query($conection, "UPDATE reservas SET estado = 'confirmada', estado_pago = '$estado_pago' WHERE idreserva = $id");
                if (!$upd) {
                    throw new Exception('Error al actualizar el estado de la reserva');
                }
            }


            mysqli_commit($conection);
            echo json_encode(['ok' => true]);
        } catch (Exception $e) {
            mysqli_rollback($conection);
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    if ($_POST['action'] == 'formCancelarReserva') {

        //print_r($_POST);
        //exit;


        $id = isset($_POST['co']) ? intval($_POST['co']) : 0;


        echo '
        <form action="" method="post" name="form_cancelar_reserva" class="cancelarReservaForm" id="form_cancelar_reserva" onsubmit="event.preventDefault(); sendCancelarReserva();">
        <h2>Cancelar Reserva</h2>
        <hr>
        <input type="hidden" name="idreserva" id="idreserva_cancelar" value="' .$id. '">

        <label for="motivo_cancelacion">Motivo de Cancelación</label>
        <textarea name="motivo" id="motivo_cancelacion" class="wd100" style="height: 100px;" required></textarea>

        <label for="abono_devuelto">¿El abono fue devuelto?</label>
        <select name="abono_devuelto" id="abono_devuelto" class="wd100 notItemOne" required>
            <option value="">Seleccione una opción</option>
            <option value="si">Sí, se devolvió</option>
            <option value="no">No, pendiente</option>
        </select>

        <input type="hidden" name="action" value="cancelarReserva">

        <div class="acciones wd100">
            <button type="submit" class="btn_new"><i class="fas fa-ban"></i> Cancelar Reserva</button>
            <a href="#" class="btn_ok closeModal" onclick="closeModal();"><i class="fas fa-times"></i> Cerrar</a>
        </div>
        </form>';
    }


    if ($_POST['action'] == 'verificarAbono') {
        //include '../conexion.php';
        $id = intval($_POST['idreserva']);
        $query = mysqli_query($conection, "SELECT abono FROM reservas WHERE idreserva = $id");
        $res = mysqli_fetch_assoc($query);
        echo json_encode(['abono' => floatval($res['abono'])]);
        exit;
    }


    if ($_POST['action'] == 'cancelarReserva') {

        //print_r($_POST);
        //exit;


        $id = intval($_POST['idreserva']);
        $motivo = mysqli_real_escape_string($conection, $_POST['motivo']);
        $abono_devuelto = $_POST['abono_devuelto'];
        $usuario_id = $_SESSION['idUser'];



        if ($id <= 0 || strlen($motivo) < 5 || !in_array($abono_devuelto, ['si','no'])) {
            echo 'Datos inválidos';
            exit;
        }

        $query = mysqli_query($conection, "SELECT estado, abono FROM reservas WHERE idreserva = $id");
        if (!$query || mysqli_num_rows($query) == 0) {
            echo 'Reserva no encontrada';
            exit;
        }

        $data = mysqli_fetch_assoc($query);
        if ($data['estado'] == 'cancelada') {
            echo 'Ya está cancelada';
            exit;
        }

        $abono = floatval($data['abono']);
        $info_abono = $abono > 0 ? "Abono: $" . number_format($abono, 2) . " / Devuelto: " . strtoupper($abono_devuelto) : "Sin abono";
        $motivo_final = $info_abono . ". Motivo: " . $motivo;

        $update = mysqli_query(
            $conection,
            "UPDATE reservas SET estado = 'cancelada',
            observaciones = CONCAT(IFNULL(observaciones,''), '\nCancelación: ', '$motivo_final'),
            usuario_id = '$usuario_id'
            WHERE idreserva = $id"
        );

        echo $update ? 'Reserva cancelada con éxito' : 'Error al cancelar';
        exit;
    }


    if ($_POST['action'] == 'formEditarReserva') {

        $idreserva = intval($_POST['co']);

        // 1. Obtener datos de la reserva principal
        $reserva = mysqli_fetch_assoc(mysqli_query($conection, "SELECT * FROM reservas WHERE idreserva = $idreserva"));
        $cliente = mysqli_fetch_assoc(mysqli_query($conection, "
        SELECT usuario, CONCAT(nombre, ' ', p_apellido) AS nombre 
        FROM clientes 
        WHERE usuario = {$reserva['id_cliente']}
        "));

        // 2. Cargar detalles de habitaciones
        $detalles = mysqli_query($conection, "
        SELECT d.*, h.numero AS numero_habitacion 
        FROM reservas_detalle d 
        INNER JOIN habitaciones h ON h.idhabitacion = d.id_habitacion 
        WHERE d.idreserva = $idreserva
        ");

        // 3. Cargar lugares de tour
        $tours = mysqli_query($conection, "SELECT id, nombre FROM lugares_tour WHERE activo = 1 ORDER BY nombre ASC");
        $tour_data = [];
        while ($row = mysqli_fetch_assoc($tours)) {
            $tour_data[] = $row;
        }

        // Cargar tarifas de habitaciones
        $tarifas = mysqli_query($conection, "SELECT id as idtarifa, nombre, precio_por_persona as precio FROM tarifas_habitaciones ORDER BY nombre ASC");
        $lista_tarifas = [];
        while ($t = mysqli_fetch_assoc($tarifas)) {
            $lista_tarifas[] = $t;
        }

        // Cargar extras (desayuno/tour)
        $extras = mysqli_query($conection, "SELECT tipo_extra as tipo, valor as precio FROM tarifa_extras WHERE habilitado = 1");
        $precios_desayuno = [];
        $precios_tour = [];
        while ($e = mysqli_fetch_assoc($extras)) {
            if ($e['tipo'] == 'desayuno') {
                $precios_desayuno[] = $e['precio'];
            }
            if ($e['tipo'] == 'tour') {
                $precios_tour[] = $e['precio'];
            }
        }


        // 4. Renderizar el formulario
        ob_start(); ?>

<form action="" method="post" id="formReserva" onsubmit="event.preventDefault(); sendDataReserva('editar');"
    style="max-width: 350px;">
    <input type="hidden" name="action" value="editarReserva">
    <input type="hidden" name="idreserva" value="<?= $idreserva ?>">

    <div class="form_group">
        <label>Cliente:</label>
        <input type="text"
            value="<?= $cliente['usuario'] . ' - ' . $cliente['nombre'] ?>"
            readonly>
        <input type="hidden" name="id_cliente"
            value="<?= $reserva['id_cliente'] ?>">
    </div>

    <div class="form_group">
        <label>Entrada:</label>
        <input type="date" name="fecha_entrada" id="fecha_entrada"
            value="<?= $reserva['fecha_entrada'] ?>"
            onchange="recalcularTotalReserva()">
    </div>
    <div class="form_group">
        <label>Salida:</label>
        <input type="date" name="fecha_salida" id="fecha_salida"
            value="<?= $reserva['fecha_salida'] ?>"
            onchange="recalcularTotalReserva()">
    </div>

    <h3>Habitaciones</h3>
    <div id="habitacionesContainer">
        <?php while ($d = mysqli_fetch_assoc($detalles)) { ?>
        <div class="habitacion-row">
            <label>Habitación:</label>
            <select class="id_habitacion" name="id_habitacion[]" onchange="recalcularTotalReserva()">
                <option
                    value="<?= $d['id_habitacion'] ?>">
                    Hab.
                    <?= $d['numero_habitacion'] ?>
                </option>
            </select>

            <label>Tarifa Adulto:</label>
            <select name="tarifa_adulto[]" class="select_tarifa" onchange="recalcularTotalReserva()">
                <option value="">Seleccione</option>
                <?php foreach ($lista_tarifas as $t) { ?>
                <option
                    value="<?= $t['precio'] ?>"
                    data-precio="<?= $t['precio'] ?>"
                    <?= $d['precio_unitario'] == $t['precio'] ? 'selected' : '' ?>>
                    <?= $t['nombre'] ?> -
                    $<?= number_format($t['precio'], 2) ?>
                </option>

                <?php } ?>
            </select>

            <label>Tarifa Niño:</label>
            <select name="tarifa_nino[]" class="select_tarifa_nino" onchange="recalcularTotalReserva()">
                <option value="">Seleccione</option>
                <?php foreach ($lista_tarifas as $t) { ?>
                <option
                    value="<?= $t['precio'] ?>"
                    data-precio="<?= $t['precio'] ?>"
                    <?= $d['precio_nino'] == $t['precio'] ? 'selected' : '' ?>>
                    <?= $t['nombre'] ?> -
                    $<?= number_format($t['precio'], 2) ?>
                </option>
                <?php } ?>
            </select>


            <label>Adultos:</label>
            <input type="number" name="adultos[]" class="input_adultos"
                value="<?= $d['adultos'] ?>"
                min="0" onchange="recalcularTotalReserva()">

            <label>Niños:</label>
            <input type="number" name="ninos[]" class="input_ninos"
                value="<?= $d['ninos'] ?>"
                min="0" onchange="recalcularTotalReserva()">

            <label>
                <input type="checkbox"
                    name="desayuno_<?= $d['id_habitacion'] ?>"
                    class="chk_desayuno"
                    <?= $d['incluye_desayuno'] ? 'checked' : '' ?>
                onchange="togglePrecio(this, 'precio_desayuno'); recalcularTotalReserva()" >
                ¿Incluir Desayuno?
            </label>
            <label>Precio Desayuno:</label>
            <select name="precio_desayuno[]" class="precio_desayuno"
                <?= $d['incluye_desayuno'] ? '' : 'disabled' ?>
                onchange="recalcularTotalReserva();">
                <option value="">Seleccione</option>
                <?php foreach ($precios_desayuno as $p) { ?>
                <option value="<?= $p ?>"
                    data-precio="<?= $p ?>" <?= $d['precio_desayuno'] == $p ? 'selected' : '' ?>>$<?= number_format($p, 2) ?>
                </option>
                <?php } ?>
            </select>


            <label>
                <input type="checkbox"
                    name="tour_<?= $d['id_habitacion'] ?>"
                    class="chk_tour"
                    <?= $d['incluye_tour'] ? 'checked' : '' ?>
                onchange="togglePrecio(this, 'precio_tour'); togglePrecio(this, 'select_lugar_tour');
                recalcularTotalReserva()">
                ¿Incluir Tour?
            </label>
            <label>Precio Tour:</label>
            <select name="precio_tour[]" class="precio_tour"
                <?= $d['incluye_tour'] ? '' : 'disabled' ?>
                onchange="recalcularTotalReserva()">
                <option value="">Seleccione</option>
                <?php foreach ($precios_tour as $p) { ?>
                <option value="<?= $p ?>"
                    data-precio="<?= $p ?>" <?= $d['precio_tour'] == $p ? 'selected' : '' ?>>
                    $<?= number_format($p, 2) ?>
                </option>

                <?php } ?>
            </select>


            <label>Lugar Tour:</label>
            <select name="lugar_tour[]" class="select_lugar_tour"
                <?= $d['incluye_tour'] ? '' : 'disabled' ?>>
                <option value="">Seleccionar lugar tour</option>
                <?php foreach ($tour_data as $tour) { ?>
                <option
                    value="<?= $tour['id'] ?>"
                    <?= $d['lugar_tour'] == $tour['id'] ? 'selected' : '' ?>>
                    <?= $tour['nombre'] ?>
                </option>
                <?php } ?>
            </select>

            <button type="button" onclick="removeHabitacion(this)">Eliminar</button>
        </div>

        <?php } ?>
    </div>

    <button type="button" onclick="addHabitacion()">+ Añadir Habitación</button>

    <div class="form_group">
        <label>Total:</label>
        <input type="number" step="0.01" name="total" id="total"
            value="<?= $reserva['total'] ?>"
            readonly>
    </div>

    <div class="form_group">
        <label>Observaciones:</label>
        <textarea name="observaciones"
            id="observaciones"><?= htmlspecialchars($reserva['observaciones']) ?></textarea>
    </div>

    <div class="btn_block">
        <button type="submit" class="btn_save"><i class="fas fa-save"></i> Guardar Cambios</button>
        <a href="#" class="btn_cancel closeModal" onclick="closeModal();"><i class="fas fa-times"></i> Cancelar</a>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        actualizarHabitacionesDisponibles();
        recalcularTotalReserva();
    });
</script>

<?php
                    echo ob_get_clean();
        exit;
    }


    if ($_POST['action'] == 'editarReserva') {

        $idreserva = intval($_POST['idreserva']);
        $id_cliente = intval($_POST['id_cliente']);
        $entrada = mysqli_real_escape_string($conection, $_POST['fecha_entrada']);
        $salida = mysqli_real_escape_string($conection, $_POST['fecha_salida']);
        $total = floatval($_POST['total']);


        // Estado de la reserva (confirmada si hay abono)
        $estado = ($abono > 0) ? 'confirmada' : 'pendiente';

        $observaciones = mysqli_real_escape_string($conection, $_POST['observaciones']);

        $sql_update = "UPDATE reservas SET 
        fecha_entrada = '$entrada',
        fecha_salida = '$salida',
        total = $total,
        estado_pago = '$estado_pago',
        estado = '$estado',
        observaciones = '$observaciones'
        WHERE idreserva = $idreserva";

        if (!mysqli_query($conection, $sql_update)) {
            echo 'Error al actualizar la reserva principal';
            exit;
        }

        mysqli_query($conection, "DELETE FROM reservas_detalle WHERE idreserva = $idreserva");

        $habitaciones = $_POST['id_habitacion'] ?? [];
        $adultos_arr = $_POST['adultos'] ?? [];
        $ninos_arr = $_POST['ninos'] ?? [];
        $tarifa_adulto_arr = $_POST['tarifa_adulto'] ?? [];
        $tarifa_nino_arr = $_POST['tarifa_nino'] ?? [];
        $precio_desayuno_arr = $_POST['precio_desayuno'] ?? [];
        $precio_tour_arr = $_POST['precio_tour'] ?? [];
        $lugar_tour_arr = $_POST['lugar_tour'] ?? [];

        for ($i = 0; $i < count($habitaciones); $i++) {
            $id_habitacion = intval($habitaciones[$i]);
            $adultos = intval($adultos_arr[$i]);
            $ninos = intval($ninos_arr[$i]);
            $precio_unitario = floatval($tarifa_adulto_arr[$i]);
            $precio_nino = floatval($tarifa_nino_arr[$i]);
            $precio_desayuno = isset($precio_desayuno_arr[$i]) ? floatval($precio_desayuno_arr[$i]) : 0;
            $precio_tour = isset($precio_tour_arr[$i]) ? floatval($precio_tour_arr[$i]) : 0;
            $incluye_desayuno = isset($_POST['desayuno_' . $id_habitacion]) ? 1 : 0;
            $incluye_tour = isset($_POST['tour_' . $id_habitacion]) ? 1 : 0;
            $lugar_tour = isset($lugar_tour_arr[$i]) ? mysqli_real_escape_string($conection, $lugar_tour_arr[$i]) : '';

            $subtotal = 0; // puedes recalcular si lo deseas

            $sql_det = "INSERT INTO reservas_detalle (
            idreserva, id_habitacion, adultos, ninos,
            incluye_desayuno, incluye_tour, lugar_tour,
            precio_unitario, precio_nino,
            precio_desayuno, precio_tour,
            subtotal
        ) VALUES (
            $idreserva, $id_habitacion, $adultos, $ninos,
            $incluye_desayuno, $incluye_tour, '$lugar_tour',
            $precio_unitario, $precio_nino,
            $precio_desayuno, $precio_tour,
            $subtotal
        )";

            if (!mysqli_query($conection, $sql_det)) {
                echo 'Error al insertar detalle: habitación ' . $id_habitacion;
                exit;
            }
        }

        echo 'ok';
        exit;
    }



    if ($_POST['action'] == 'formCheckinDirecto') {
        $fechaEntrada = date('Y-m-d');
        $fechaSalida = date('Y-m-d', strtotime('+1 day'));
        $estado = 'checkin';
        $id_habitacion_fijada = isset($_POST['co']) ? intval($_POST['co']) : 0;


        // Clientes
        $clientes = mysqli_query($conection, "SELECT usuario, CONCAT(nombre, ' ', p_apellido) AS nombre FROM clientes ORDER BY nombre ASC");

        // Habitaciones disponibles
        $fechaEntrada = date('Y-m-d');
        $fechaSalida  = date('Y-m-d', strtotime('+1 day'));

        $habitaciones = mysqli_query($conection, "
    SELECT h.idhabitacion, h.numero 
    FROM habitaciones h
    WHERE h.estado = 'disponible' AND h.habilitada = 1
            AND h.idhabitacion NOT IN (
                SELECT d.id_habitacion
                FROM reservas_detalle d
                INNER JOIN reservas r ON r.idreserva = d.idreserva
                WHERE r.estado NOT IN ('cancelada', 'checkout') 
                AND ('$fechaEntrada' < r.fecha_salida AND '$fechaSalida' > r.fecha_entrada)
            )
            ORDER BY h.numero ASC
        ");

        // Tarifas
        $tarifas = mysqli_query($conection, "SELECT id, nombre, precio_por_persona FROM tarifas_habitaciones WHERE habilitada = 1");
        $tarifa_data = [];
        while ($t = mysqli_fetch_assoc($tarifas)) {
            $tarifa_data[] = $t;
        }

        // Extras (desayuno, tour)
        $extras = mysqli_query($conection, "SELECT tipo_extra, valor FROM tarifa_extras WHERE habilitado = 1 ORDER BY valor ASC");
        $extras_data = ['desayuno' => [], 'tour' => []];
        while ($e = mysqli_fetch_assoc($extras)) {
            $extras_data[$e['tipo_extra']][] = $e['valor'];
        }

        // Tours
        $tours = mysqli_query($conection, "SELECT id, nombre FROM lugares_tour WHERE activo = 1 ORDER BY nombre ASC");
        $tour_data = [];
        while ($t = mysqli_fetch_assoc($tours)) {
            $tour_data[] = $t;
        }

        ob_start(); ?>
<form action="" method="post" name="form_reserva" id="formReserva"
    onsubmit="event.preventDefault(); sendDataCheckin();">
    <input type="hidden" name="action" value="guardarCheckinDirecto">
    <input type="hidden" name="estado_forzado"
        value="<?= $estado ?>">
    <input type="hidden" name="fecha_entrada"
        value="<?= $fechaEntrada ?>">
    <input type="hidden" name="fecha_salida"
        value="<?= $fechaSalida ?>">

    <h1><i class="fas fa-sign-in-alt"></i> Check-in Directo</h1>

    <div class="form_group">
        <label for="id_cliente">Cliente:</label>
        <div style="display: flex; gap: 5px;">
            <select name="id_cliente" id="id_cliente" class="js-example-basic-single" required>
                <option value="">Seleccione un cliente</option>
                <?php while ($c = mysqli_fetch_assoc($clientes)) { ?>
                <option
                    value="<?= $c['usuario'] ?>">
                    <?= $c['nombre'] ?>
                    (<?= $c['usuario'] ?>)
                </option>
                <?php } ?>
            </select>
            <button type="button" class="btn_new" style="margin: 0px;" onclick="abrirFormularioCliente()"><i
                    class="fas fa-user-plus"></i></button>
        </div>
    </div>

    <div class="form_group">
        <label for="id_habitacion">Habitación:</label>
        <select name="id_habitacion" id="id_habitacion" class="js-example-basic-single" required
            onchange="recalcularTotalCheckin();"
            <?= $id_habitacion_fijada > 0 ? 'disabled' : '' ?>>
            <option value="">Seleccione habitación</option>
            <?php while ($h = mysqli_fetch_assoc($habitaciones)) { ?>
            <option
                value="<?= $h['idhabitacion'] ?>"
                <?= $h['idhabitacion'] == $id_habitacion_fijada ? 'selected' : '' ?>>
                Hab. <?= $h['numero'] ?>
            </option>
            <?php } ?>
        </select>
        <?php if ($id_habitacion_fijada > 0): ?>
        <input type="hidden" name="id_habitacion"
            value="<?= $id_habitacion_fijada ?>">
        <?php endif; ?>
    </div>


    <label for="noches">Noches</label>
    <input type="number" name="noches" id="noches" min="1" value="1" required onchange="recalcularTotalCheckin();">


    <div class="form_group">
        <label for="tarifa">Tarifa Adulto:</label>
        <select name="tarifa" id="tarifa" required onchange="recalcularTotalCheckin();">
            <?php foreach ($tarifa_data as $t) { ?>
            <option value="<?= $t['id'] ?>"
                data-precio="<?= $t['precio_por_persona'] ?>">
                <?= ucfirst($t['nombre']) ?>
                ($<?= $t['precio_por_persona'] ?>)
            </option>
            <?php } ?>
        </select><br>
        <input type="number" name="adultos" id="adultos" value="1" min="1" onchange="recalcularTotalCheckin();">
    </div>


    <!-- TARIFA NIÑO -->
    <div class="form_group">
        <label for="tarifa_nino">Tarifa Niño:</label>
        <select name="tarifa_nino" id="tarifa_nino" onchange="recalcularTotalCheckin();">
            <?php foreach ($tarifa_data as $t) { ?>
            <option value="<?= $t['id'] ?>"
                data-precio="<?= $t['precio_por_persona'] ?>">
                <?= ucfirst($t['nombre']) ?>
                ($<?= $t['precio_por_persona'] ?>)
            </option>
            <?php } ?>
        </select><br>
        <input type="number" name="ninos" id="ninos" value="0" min="0" onchange="recalcularTotalCheckin();">
    </div>



    <div class="form_group">
        <label><input type="checkbox" id="chk_desayuno" onchange="togglePrecioCheckin(this, 'precio_desayuno')">
            Desayuno</label>
        <select name="precio_desayuno" id="precio_desayuno" disabled onchange="recalcularTotalCheckin();" class="">
            <option value="">Seleccione</option>
            <?php foreach ($extras_data['desayuno'] as $d) { ?>
            <option value="<?= $d ?>">
                $<?= number_format($d, 2) ?></option>
            <?php } ?>
        </select>
    </div>

    <div class="form_group">
        <label><input type="checkbox" id="chk_tour" onchange="togglePrecioCheckin(this, 'precio_tour')"> Tour</label>
        <select name="precio_tour" id="precio_tour" disabled onchange="recalcularTotalCheckin();">
            <option value="">Seleccione</option>
            <?php foreach ($extras_data['tour'] as $t) { ?>
            <option value="<?= $t ?>">
                $<?= number_format($t, 2) ?></option>
            <?php } ?>
        </select>
    </div>

    <div class="form_group">
        <label>Lugar del Tour:</label>
        <select name="lugar_tour" id="lugar_tour" class="js-example-basic-single">
            <option value="">Seleccione un Tour</option>
            <?php foreach ($tour_data as $t) { ?>
            <option value="<?= $t['id'] ?>">
                <?= $t['nombre'] ?>
            </option>
            <?php } ?>
        </select>
    </div>

    <div class="form_group">
        <label for="total">Total ($):</label>
        <input type="number" step="0.01" name="total" id="total" readonly>
    </div>

    <div class="form_group">
        <label for="metodo_pago">Método de Pago:</label>
        <select name="metodo_pago" id="metodo_pago" required>
            <option value="efectivo">Efectivo</option>
            <option value="tarjeta">Tarjeta</option>
            <option value="transferencia">Transferencia</option>
        </select>
    </div>

    <div class="form_group">
        <label for="referencia_pago">Referencia / Documento:</label>
        <input type="text" name="referencia_pago" id="referencia_pago" placeholder="Opcional si aplica...">
    </div>

    <div class="btn_block">
        <button type="submit" class="btn_save"><i class="fas fa-file-invoice-dollar"></i> Registrar y Facturar</button>
        <a href="#" class="btn_cancel closeModal" onclick="closeModal()"><i class="fas fa-times"></i> Cancelar</a>
    </div>
</form>

<script>
    function togglePrecioCheckin(checkbox, id) {
        const select = document.getElementById(id);
        select.disabled = !checkbox.checked;
        if (!checkbox.checked) select.value = '';
        recalcularTotalCheckin();
    }

    function recalcularTotalCheckin() {
        const adultos = parseInt(document.getElementById('adultos')?.value || 0);
        const ninos = parseInt(document.getElementById('ninos')?.value || 0);

        const tarifaAdulto = document.getElementById('tarifa')?.selectedOptions[0]?.dataset.precio || 0;
        const tarifaNino = document.getElementById('tarifa_nino')?.selectedOptions[0]?.dataset.precio || 0;

        const noches = parseInt(document.getElementById('noches')?.value || 1);


        const precioDesayuno = parseFloat(document.getElementById('precio_desayuno')?.value || 0);
        const precioTour = parseFloat(document.getElementById('precio_tour')?.value || 0);

        const chkDesayuno = document.getElementById('chk_desayuno').checked;
        const chkTour = document.getElementById('chk_tour').checked;

        const totalAdultos = adultos * (parseFloat(tarifaAdulto) + (chkDesayuno ? precioDesayuno : 0) + (chkTour ?
            precioTour : 0)) * noches;
        const totalNinos = ninos * (parseFloat(tarifaNino) + (chkDesayuno ? precioDesayuno : 0) + (chkTour ?
            precioTour : 0)) * noches;


        const total = totalAdultos + totalNinos;
        document.getElementById('total').value = total.toFixed(2);
    }

    function sendDataCheckin() {
        const total = parseFloat(document.getElementById('total').value || 0);
        if (isNaN(total) || total <= 0) {
            Swal.fire('Error', 'El total debe ser mayor a $0.00', 'error');
            return;
        }

        const form = document.getElementById('formReserva');
        const data = new FormData(form);

        fetch('ajax.php', {
                method: 'POST',
                body: data
            })
            .then(res => res.text())
            .then(resp => {
                if (resp.trim() === 'ok') {
                    Swal.fire('Éxito', 'Check-in registrado y facturado', 'success');
                    closeModal('modalReserva');
                    location.reload();
                } else {
                    Swal.fire('Error', resp, 'error');
                }
            })
            .catch(() => {
                Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
            });
    }



    window.addEventListener('DOMContentLoaded', recalcularTotalCheckin);
</script>

<?php
                            echo ob_get_clean();
        exit;
    }


    if ($_POST['action'] == 'guardarCheckinDirecto') {
        mysqli_begin_transaction($conection);

        try {
            $cliente         = intval($_POST['id_cliente']);
            $habitacion      = intval($_POST['id_habitacion']);
            $adultos         = intval($_POST['adultos']);
            $ninos           = intval($_POST['ninos']);
            $tarifa_adulto_id = intval($_POST['tarifa']);
            $tarifa_nino_id   = intval($_POST['tarifa_nino']);
            $noches          = intval($_POST['noches']);
            $incluye_tour     = isset($_POST['precio_tour']) && $_POST['precio_tour'] !== '' ? 1 : 0;
            $incluye_desayuno = isset($_POST['precio_desayuno']) && $_POST['precio_desayuno'] !== '' ? 1 : 0;
            $valor_tour       = floatval($_POST['precio_tour'] ?? 0);
            $valor_desayuno   = floatval($_POST['precio_desayuno'] ?? 0);
            $tour_destino = isset($_POST['lugar_tour']) && trim($_POST['lugar_tour']) != ''
    ? "'" . mysqli_real_escape_string($conection, $_POST['lugar_tour']) . "'"
    : "''";
            $total_enviado    = floatval($_POST['total']);
            $metodo_pago      = mysqli_real_escape_string($conection, $_POST['metodo_pago']);
            $referencia       = mysqli_real_escape_string($conection, $_POST['referencia_pago']);
            $usuario_id       = $_SESSION['idUser'] ?? 0;

            if ($cliente <= 0 || $habitacion <= 0 || $adultos <= 0 || $noches <= 0 || $total_enviado <= 0) {
                throw new Exception('Datos inválidos o incompletos');
            }

            $fecha_entrada = date('Y-m-d');
            $fecha_salida  = date('Y-m-d', strtotime("+$noches days"));
            $hora_checkin  = date('Y-m-d H:i:s');

            // Obtener tarifas
            $precio_adulto = 0;
            $precio_nino   = 0;
            $tarifas = mysqli_query($conection, "SELECT id, precio_por_persona FROM tarifas_habitaciones WHERE id IN ($tarifa_adulto_id, $tarifa_nino_id)");
            while ($t = mysqli_fetch_assoc($tarifas)) {
                if ($t['id'] == $tarifa_adulto_id) {
                    $precio_adulto = floatval($t['precio_por_persona']);
                }
                if ($t['id'] == $tarifa_nino_id) {
                    $precio_nino   = floatval($t['precio_por_persona']);
                }
            }

            // Calcular totales
            $subtotal_adultos = $adultos * ($precio_adulto + $valor_desayuno + $valor_tour) * $noches;
            $subtotal_ninos   = $ninos * ($precio_nino + $valor_desayuno + $valor_tour) * $noches;
            $total_calculado  = round($subtotal_adultos + $subtotal_ninos, 2);

            if (abs($total_calculado - $total_enviado) > 0.01) {
                throw new Exception("El total no coincide con el cálculo del sistema");
            }

            // Verificar disponibilidad de la habitación
            $ocupada = mysqli_query($conection, "
            SELECT 1 FROM reservas_detalle d
            INNER JOIN reservas r ON r.idreserva = d.idreserva
            WHERE d.id_habitacion = $habitacion
            AND r.estado NOT IN ('cancelada', 'checkout')
            AND ('$fecha_entrada' < r.fecha_salida AND '$fecha_salida' > r.fecha_entrada)
            LIMIT 1
        ");
            if (mysqli_num_rows($ocupada) > 0) {
                throw new Exception('La habitación seleccionada ya está reservada en ese rango de fechas');
            }

            // Insertar reserva
            $sql = "INSERT INTO reservas (
            id_cliente, fecha_entrada, fecha_salida, total, estado_pago, estado, facturada, 
            fecha_factura, observaciones, canal_reserva, usuario_id,
            hora_checkin, usuario_checkin
        ) VALUES (
            $cliente, '$fecha_entrada', '$fecha_salida', $total_calculado, 'pagado', 'checkin', 1,
            NOW(), 'Check-in directo facturado', 'recepción', $usuario_id,
            '$hora_checkin', $usuario_id
        )";
            if (!mysqli_query($conection, $sql)) {
                throw new Exception('Error al guardar reserva');
            }

            $idreserva = mysqli_insert_id($conection);

            // Detalle
            $sql_det = "INSERT INTO reservas_detalle (
            idreserva, id_habitacion, adultos, ninos, incluye_desayuno, incluye_tour, 
            lugar_tour, precio_unitario, precio_nino, precio_desayuno, precio_tour, subtotal
        ) VALUES (
            $idreserva, $habitacion, $adultos, $ninos, $incluye_desayuno, $incluye_tour,
            $tour_destino, $precio_adulto, $precio_nino, $valor_desayuno, $valor_tour, $total_calculado
        )";
            if (!mysqli_query($conection, $sql_det)) {
                throw new Exception('Error al guardar detalle de habitación');
            }

            // Pago
            $sql_pago = "INSERT INTO reservas_pagos (
            idreserva, monto, metodo_pago, referencia_pago, fecha_pago, usuario_id
        ) VALUES (
            $idreserva, $total_calculado, '$metodo_pago', '$referencia', NOW(), $usuario_id
        )";
            if (!mysqli_query($conection, $sql_pago)) {
                throw new Exception('Error al registrar el pago');
            }

            // === Factura ===
            $fecha = date('Y-m-d H:i:s');
            $sql_factura = "INSERT INTO factura (fecha, usuario, codcliente, totalfactura, tipopago, codigopago)
                        VALUES ('$fecha', $usuario_id, $cliente, $total_calculado, '$metodo_pago', '$referencia')";
            if (!mysqli_query($conection, $sql_factura)) {
                throw new Exception('Error al registrar la factura');
            }

            $idfactura = mysqli_insert_id($conection);
            $dias = $noches;

            $detalle = mysqli_query($conection, "
            SELECT d.*, h.numero AS habitacion_numero 
            FROM reservas_detalle d
            INNER JOIN habitaciones h ON d.id_habitacion = h.idhabitacion
            WHERE d.idreserva = $idreserva
        ");

            while ($row = mysqli_fetch_assoc($detalle)) {
                $habitacion = $row['habitacion_numero'];
                $adultos = intval($row['adultos']);
                $ninos = intval($row['ninos']);
                $desayuno = intval($row['incluye_desayuno']);
                $tour = intval($row['incluye_tour']);
                $lugar_tour = $row['lugar_tour'];

                if ($adultos > 0) {
                    $pa = floatval($row['precio_unitario']);
                    mysqli_query($conection, "INSERT INTO detalle_factura (nofactura, codproducto, descripcion_servicio, cantidad, precio_venta, tipo_servicio)
                VALUES ($idfactura, 0, 'Hospedaje Adultos - Hab. $habitacion', $adultos, $pa * $dias, 'hospedaje')");
                }

                if ($ninos > 0) {
                    $pn = floatval($row['precio_nino']);
                    mysqli_query($conection, "INSERT INTO detalle_factura (nofactura, codproducto, descripcion_servicio, cantidad, precio_venta, tipo_servicio)
                VALUES ($idfactura, 0, 'Hospedaje Niños - Hab. $habitacion', $ninos, $pn * $dias, 'hospedaje')");
                }

                if ($desayuno) {
                    $val = mysqli_fetch_assoc(mysqli_query($conection, "SELECT valor FROM tarifa_extras WHERE tipo_extra = 'desayuno' AND habilitado = 1 LIMIT 1"));
                    $precio = floatval($val['valor']);
                    $personas = $adultos + $ninos;
                    mysqli_query($conection, "INSERT INTO detalle_factura (nofactura, codproducto, descripcion_servicio, cantidad, precio_venta, tipo_servicio)
                VALUES ($idfactura, 0, 'Desayuno - Hab. $habitacion', $personas, $precio * $dias, 'desayuno')");
                }

                if ($tour) {
                    $val = mysqli_fetch_assoc(mysqli_query($conection, "SELECT valor FROM tarifa_extras WHERE tipo_extra = 'tour' AND habilitado = 1 LIMIT 1"));
                    $precio = floatval($val['valor']);
                    $personas = $adultos + $ninos;
                    mysqli_query($conection, "INSERT INTO detalle_factura (nofactura, codproducto, descripcion_servicio, cantidad, precio_venta, tipo_servicio)
                VALUES ($idfactura, 0, 'Tour: $lugar_tour - Hab. $habitacion', $personas, $precio * $dias, 'tour')");
                }
            }

            mysqli_commit($conection);
            echo 'ok';
        } catch (Exception $e) {
            mysqli_rollback($conection);
            echo 'Error: ' . $e->getMessage();
        }
        exit;
    }

    if ($_POST['action'] == 'realizarCheckout') {
        $idreserva    = intval($_POST['idreserva']);
        $faltante     = floatval($_POST['faltante']);
        $metodo_pago  = intval($_POST['metodo_pago']);
        $referencia   = mysqli_real_escape_string($conection, $_POST['referencia']);
        $usuario_id   = $_SESSION['idUser'] ?? 0;
        $fecha        = date('Y-m-d H:i:s');
        $hora         = date('Y-m-d H:i:s');

        mysqli_begin_transaction($conection);

        try {
            // Validar reserva activa
            $res = mysqli_query($conection, "SELECT * FROM reservas WHERE idreserva = $idreserva AND estado = 'checkin'");
            if (mysqli_num_rows($res) == 0) {
                throw new Exception('Reserva no válida o ya procesada');
            }
            $reserva = mysqli_fetch_assoc($res);
            $cliente_id = intval($reserva['id_cliente']);
            $facturada = intval($reserva['facturada']);

            // Obtener abonos reales
            $res_abonos = mysqli_query($conection, "SELECT SUM(monto) AS total_abonos FROM reservas_pagos WHERE idreserva = $idreserva");
            $data_abonos = mysqli_fetch_assoc($res_abonos);
            $abono = floatval($data_abonos['total_abonos']);
            $total = $abono + $faltante;

            // Insertar abono final si hay faltante
            if ($faltante > 0) {
                $sql_pago = "INSERT INTO reservas_pagos (idreserva, monto, metodo_pago, referencia_pago, fecha_pago, usuario_id)
                         VALUES ($idreserva, $faltante, $metodo_pago, '$referencia', NOW(), $usuario_id)";
                if (!mysqli_query($conection, $sql_pago)) {
                    throw new Exception('No se pudo registrar el abono final');
                }
            }

            // Solo generar factura si aún no está facturada
            if ($facturada === 0) {
                $sql_factura = "INSERT INTO factura (fecha, usuario, codcliente, totalfactura, tipopago, codigopago)
                            VALUES ('$fecha', $usuario_id, $cliente_id, $total, '$metodo_pago', '$referencia')";
                if (!mysqli_query($conection, $sql_factura)) {
                    throw new Exception('Error al registrar la factura');
                }
                $idfactura = mysqli_insert_id($conection);

                // Detalles
                $detalles = mysqli_query($conection, "
                SELECT d.*, h.numero AS habitacion_numero 
                FROM reservas_detalle d
                INNER JOIN habitaciones h ON d.id_habitacion = h.idhabitacion
                WHERE d.idreserva = $idreserva");

                $dias = (strtotime($reserva['fecha_salida']) - strtotime($reserva['fecha_entrada'])) / (60 * 60 * 24);

                while ($row = mysqli_fetch_assoc($detalles)) {
                    $habitacion = $row['habitacion_numero'];
                    $adultos = intval($row['adultos']);
                    $ninos = intval($row['ninos']);
                    $desayuno = intval($row['incluye_desayuno']);
                    $tour = intval($row['incluye_tour']);
                    $lugar_tour = $row['lugar_tour'];

                    $p_adulto = floatval($row['precio_unitario']);
                    $p_nino   = floatval($row['precio_nino']);

                    // Adultos
                    if ($adultos > 0) {
                        mysqli_query($conection, "INSERT INTO detalle_factura 
                        (nofactura, codproducto, descripcion_servicio, cantidad, precio_venta, tipo_servicio)
                        VALUES ($idfactura, 0, 'Hospedaje Adultos - Hab. $habitacion', $adultos, $p_adulto * $dias, 'hospedaje')");
                    }

                    // Niños
                    if ($ninos > 0) {
                        mysqli_query($conection, "INSERT INTO detalle_factura 
                        (nofactura, codproducto, descripcion_servicio, cantidad, precio_venta, tipo_servicio)
                        VALUES ($idfactura, 0, 'Hospedaje Niños - Hab. $habitacion', $ninos, $p_nino * $dias, 'hospedaje')");
                    }

                    // Desayuno
                    if ($desayuno) {
                        $val = mysqli_fetch_assoc(mysqli_query($conection, "SELECT valor FROM tarifa_extras WHERE tipo_extra = 'desayuno' AND habilitado = 1 LIMIT 1"));
                        $precio = floatval($val['valor']);
                        $personas = $adultos + $ninos;
                        mysqli_query($conection, "INSERT INTO detalle_factura 
                        (nofactura, codproducto, descripcion_servicio, cantidad, precio_venta, tipo_servicio)
                        VALUES ($idfactura, 0, 'Desayuno - Hab. $habitacion', $personas, $precio * $dias, 'desayuno')");
                    }

                    // Tour
                    if ($tour) {
                        $val = mysqli_fetch_assoc(mysqli_query($conection, "SELECT valor FROM tarifa_extras WHERE tipo_extra = 'tour' AND habilitado = 1 LIMIT 1"));
                        $precio = floatval($val['valor']);
                        $personas = $adultos + $ninos;
                        mysqli_query($conection, "INSERT INTO detalle_factura 
                        (nofactura, codproducto, descripcion_servicio, cantidad, precio_venta, tipo_servicio)
                        VALUES ($idfactura, 0, 'Tour: $lugar_tour - Hab. $habitacion', $personas, $precio, 'tour')");
                    }
                }

                // Marcar reserva como facturada
                mysqli_query($conection, "UPDATE reservas SET facturada = 1 WHERE idreserva = $idreserva");
            }

            // Actualizar estado y hora de checkout
            mysqli_query($conection, "UPDATE reservas 
            SET estado = 'checkout', hora_checkout = '$hora', usuario_checkout = $usuario_id 
            WHERE idreserva = $idreserva");

            // Liberar habitación
            mysqli_query($conection, "
            UPDATE habitaciones h
            INNER JOIN reservas_detalle d ON d.id_habitacion = h.idhabitacion
            SET h.estado = 'disponible'
            WHERE d.idreserva = $idreserva");

            mysqli_commit($conection);
            echo 'OK';
        } catch (Exception $e) {
            mysqli_rollback($conection);
            echo 'Error: ' . $e->getMessage();
        }

        exit;
    }


    if ($_POST['action'] == 'cambiarEstadoReserva') {
        $idreserva = intval($_POST['idreserva']);
        $estado = mysqli_real_escape_string($conection, $_POST['estado']);
        $usuario = $_SESSION['idUser'] ?? 0;

        if ($idreserva <= 0 || !in_array($estado, ['pendiente', 'confirmada', 'checkin', 'checkout', 'cancelada'])) {
            echo 'Datos inválidos';
            exit;
        }

        if ($estado === 'checkin') {
            $hora = date('Y-m-d H:i:s');
            $sql = "UPDATE reservas 
                SET estado = 'checkin', 
                    hora_checkin = '$hora', 
                    usuario_checkin = $usuario 
                WHERE idreserva = $idreserva";
        } else {
            $sql = "UPDATE reservas 
                SET estado = '$estado' 
                WHERE idreserva = $idreserva";
        }

        $query = mysqli_query($conection, $sql);

        if ($query) {
            echo 'OK';
        } else {
            echo 'Error al actualizar';
        }

        exit;
    }



    if ($_POST['action'] == 'calendarioHabitaciones_reservas') {

        //print_r($_POST);
        //exit;
        $estado = (isset($_POST['estado']) && $_POST['estado'] !== '') ? $_POST['estado'] : '';
        $eventos = [];
        $sql = "
                SELECT 
                    r.idreserva,
                    r.fecha_entrada,
                    r.fecha_salida,
                    CONCAT(c.nombre, ' ', c.p_apellido) AS cliente,
                    GROUP_CONCAT(h.numero ORDER BY h.numero SEPARATOR ', ') AS habitaciones,
                    r.estado
                FROM reservas r
                INNER JOIN clientes c ON r.id_cliente = c.usuario
                INNER JOIN reservas_detalle rd ON rd.idreserva = r.idreserva
                INNER JOIN habitaciones h ON rd.id_habitacion = h.idhabitacion
                WHERE r.estado IN ('pendiente', 'confirmada', 'checkin')
            ";

        // Filtro opcional por estado
        if (!empty($estado)) {
            $estado = mysqli_real_escape_string($conection, $estado);
            $sql .= " AND r.estado = '$estado'";
        }

        $sql .= " GROUP BY r.idreserva";

        $query = mysqli_query($conection, $sql);

        while ($r = mysqli_fetch_assoc($query)) {
            // Asignar color por estado
            $color = match($r['estado']) {
                'pendiente' => '#ffc107',      // amarillo
                'confirmada' => '#007bff',     // azul
                'checkin' => '#28a745',        // verde
                default => '#6c757d'           // gris
            };

            $eventos[] = [
                'id' => $r['idreserva'] . '_reserva',
                'title' => $r['cliente'] . ' (Hab: ' . $r['habitaciones'] . ')',
                'start' => $r['fecha_entrada'] . 'T12:00:00',
                'end' => $r['fecha_salida'] . 'T12:00:00',
                'color' => $color,
                'description' => 'Cliente: ' . $r['cliente'] . '\nHabitaciones: ' . $r['habitaciones'] . '\nEstado: ' . ucfirst($r['estado'])
            ];

        }

        echo json_encode($eventos);
        exit;
    }


    if ($_POST['action'] == 'filtroReservasPorFecha') {


        $desde = !empty($_POST['desde']) ? mysqli_real_escape_string($conection, $_POST['desde']) : '';
        $hasta = !empty($_POST['hasta']) ? mysqli_real_escape_string($conection, $_POST['hasta']) : '';
        $estado = !empty($_POST['estado']) ? mysqli_real_escape_string($conection, $_POST['estado']) : '';

        $where = "1"; // siempre verdadero

        // Filtro de fechas
        if (!empty($desde) && !empty($hasta)) {
            if ($desde === $hasta) {
                $where .= " AND r.fecha_entrada = '$desde'";
            } else {
                $where .= " AND r.fecha_entrada BETWEEN '$desde' AND '$hasta'";
            }
        }

        // Filtro de estado (solo si no es "todos")
        if (!empty($estado) && $estado !== 'todos') {
            $where .= " AND r.estado = '$estado'";
        }

        $query = mysqli_query($conection, "
                SELECT 
                    r.fecha_creacion,
                    r.idreserva,
                    CONCAT(c.nombre, ' ', c.p_apellido) AS cliente,
                    r.fecha_entrada,
                    r.fecha_salida,
                    r.total,
                    r.abono,
                    r.estado,
                    r.estado_pago,
                    GROUP_CONCAT(h.numero ORDER BY h.numero SEPARATOR ', ') AS habitaciones,
                    SUM(rd.adultos) AS adultos,
                    SUM(rd.ninos) AS ninos,
                    SUM(rd.incluye_desayuno) AS desayuno,
                    SUM(rd.incluye_tour) AS tour,
                    r.total - r.abono AS saldo
                FROM reservas r
                INNER JOIN clientes c ON r.id_cliente = c.usuario
                INNER JOIN reservas_detalle rd ON rd.idreserva = r.idreserva
                INNER JOIN habitaciones h ON rd.id_habitacion = h.idhabitacion
                WHERE $where
                GROUP BY r.idreserva
                ORDER BY r.fecha_entrada ASC
                ");

        if ($query && mysqli_num_rows($query) > 0) {
            while ($data = mysqli_fetch_assoc($query)) {
                ?>
<tr>
    <td><?= date('d-m-Y', strtotime($data["fecha_creacion"])) ?>
    </td>
    <td><?= htmlspecialchars($data["cliente"]) ?>
    </td>
    <td><?= htmlspecialchars($data["habitaciones"]) ?>
    </td>
    <td><?= date('d-m-Y', strtotime($data["fecha_entrada"])) ?>
    </td>
    <td><?= date('d-m-Y', strtotime($data["fecha_salida"])) ?>
    </td>
    <td><?= intval($data["adultos"]) ?></td>
    <td><?= intval($data["ninos"]) ?></td>
    <td><?= ($data["desayuno"] > 0) ? 'Sí' : 'No' ?>
    </td>
    <td><?= ($data["tour"] > 0) ? 'Sí' : 'No' ?>
    </td>
    <td>$<?= number_format($data["total"], 2) ?>
    </td>
    <td>$<?= number_format($data["abono"], 2) ?>
    </td>
    <td>$<?= number_format($data["saldo"], 2) ?>
    </td>
    <td><span
            class="estado <?= $data["estado"] ?>"><?= ucfirst($data["estado"]) ?></span>
    </td>
    <td><span
            class="estado <?= $data["estado_pago"] ?>"><?= ucfirst($data["estado_pago"]) ?></span>
    </td>
    <td align="center">
        <a class="btn" style="background: blue;"
            href="pdf/reservas/verReservaPDF.php?id=<?= $data['idreserva']; ?>"
            target="_blank" title="Ver reserva PDF">
            <i class="fas fa-file-pdf"></i>
        </a>
        <?php if ($data['estado'] == 'pendiente' || $data['estado'] == 'confirmada') { ?>
        <button class="btn btn_editar anadirForm"
            co="<?= $data["idreserva"]; ?>"
            ac="formEditarReserva" title="Editar reserva">
            <i class="fas fa-pen"></i>
        </button>
        <button class="btn anadirForm" ac="formCancelarReserva"
            co="<?= $data["idreserva"]; ?>">
            <i class="fa-solid fa-ban"></i>
        </button>
        <?php } ?>
    </td>
</tr>
<?php
            }
        } else {
            echo "<tr><td colspan='15' align='center'>No se encontraron reservas en ese rango o estado.</td></tr>";
        }

        exit;
    }



    if ($_POST['action'] == 'formHabitacion') {

        $tipos = mysqli_query($conection, "SELECT * FROM tipo_habitacion ORDER BY nombre ASC");

        echo '
    <form action="" method="post" name="form_habitacion" class="formHabitacion" id="form_habitacion" onsubmit="event.preventDefault(); guardarHabitacion();">
        <h2>Nueva Habitación</h2>
        <hr>

        <label for="numero">Número:</label>
        <input type="text" name="numero" id="numero" class="wd100" required>

        <label for="id_tipo">Tipo de Habitación:</label>
        <select name="id_tipo" id="id_tipo" class="wd100" required>
            <option value="">Seleccione tipo</option>';
        while ($t = mysqli_fetch_assoc($tipos)) {
            echo '<option value="'.$t['id_tipo'].'">'.htmlspecialchars($t['nombre']).'</option>';
        }
        echo '</select>

        <label for="descripcion">Descripción:</label>
        <textarea name="descripcion" id="descripcion" class="wd100" style="height: 80px;"></textarea>

        <label for="piso">Piso:</label>
        <input type="text" name="piso" id="piso" class="wd100" required>

        <label for="capacidad">Capacidad:</label>
        <input type="number" name="capacidad" id="capacidad" class="wd100" min="1" required>

        <label for="precio">Precio por noche:</label>
        <input type="number" step="0.01" name="precio" id="precio" class="wd100" required>

        <label for="estado">Estado:</label>
        <select name="estado" id="estado" class="wd100" required>
            <option value="">Seleccione estado</option>
            <option value="disponible">Disponible</option>
            <option value="ocupada">Ocupada</option>
            <option value="mantenimiento">Mantenimiento</option>
        </select>

        <input type="hidden" name="action" value="guardarHabitacion">

        <div class="acciones wd100">
            <button type="submit" class="btn_new"><i class="fas fa-save"></i> Guardar Habitación</button>
            <a href="#" class="btn_ok closeModal" onclick="closeModal();"><i class="fas fa-times"></i> Cerrar</a>
        </div>
    </form>';
    }

    if ($_POST['action'] == 'formEditarHabitacion') {
        $id = intval($_POST['co']);
        $habitacion = mysqli_fetch_assoc(mysqli_query($conection, "SELECT * FROM habitaciones WHERE idhabitacion = $id"));
        $tipos = mysqli_query($conection, "SELECT * FROM tipo_habitacion ORDER BY nombre ASC");

        if (!$habitacion) {
            echo '<p>Error: Habitación no encontrada.</p>';
            exit;
        }

        echo '
    <form action="" method="post" name="form_editar_habitacion" class="formEditarHabitacion" id="form_editar_habitacion" onsubmit="event.preventDefault(); actualizarHabitacion();">
        <h2>Editar Habitación</h2>
        <hr>

        <input type="hidden" name="idhabitacion" id="idhabitacion" value="' . $habitacion['idhabitacion'] . '">

        <label for="numero">Número:</label>
        <input type="text" name="numero" id="numero" class="wd100" required value="' . htmlspecialchars($habitacion['numero']) . '">

        <label for="id_tipo">Tipo de Habitación:</label>
        <select name="id_tipo" id="id_tipo" class="wd100" required>
            <option value="">Seleccione tipo</option>';
        while ($t = mysqli_fetch_assoc($tipos)) {
            $selected = ($habitacion['id_tipo'] == $t['id_tipo']) ? 'selected' : '';
            echo '<option value="' . $t['id_tipo'] . '" ' . $selected . '>' . htmlspecialchars($t['nombre']) . '</option>';
        }
        echo '</select>

        <label for="descripcion">Descripción:</label>
        <textarea name="descripcion" id="descripcion" class="wd100" style="height: 80px;">' . htmlspecialchars($habitacion['descripcion']) . '</textarea>

        <label for="piso">Piso:</label>
        <input type="text" name="piso" id="piso" class="wd100" required value="' . htmlspecialchars($habitacion['piso']) . '">

        <label for="capacidad">Capacidad:</label>
        <input type="number" name="capacidad" id="capacidad" class="wd100" min="1" required value="' . intval($habitacion['capacidad']) . '">

        <label for="precio">Precio por noche:</label>
        <input type="number" step="0.01" name="precio" id="precio" class="wd100" required value="' . floatval($habitacion['precio']) . '">

        <label for="estado">Estado:</label>
        <select name="estado" id="estado" class="wd100" required>
            <option value="">Seleccione estado</option>
            <option value="disponible" ' . ($habitacion['estado'] == 'disponible' ? 'selected' : '') . '>Disponible</option>
            <option value="ocupada" ' . ($habitacion['estado'] == 'ocupada' ? 'selected' : '') . '>Ocupada</option>
            <option value="mantenimiento" ' . ($habitacion['estado'] == 'mantenimiento' ? 'selected' : '') . '>Mantenimiento</option>
        </select>

        <input type="hidden" name="action" value="actualizarHabitacion">

        <div class="acciones wd100">
            <button type="submit" class="btn_new"><i class="fas fa-save"></i> Actualizar</button>
            <a href="#" class="btn_ok closeModal" onclick="closeModal();"><i class="fas fa-times"></i> Cerrar</a>
        </div>
    </form>';
    }

    if ($_POST['action'] == 'guardarHabitacion') {
        $numero     = mysqli_real_escape_string($conection, $_POST['numero']);
        $id_tipo    = intval($_POST['id_tipo']);
        $descripcion = mysqli_real_escape_string($conection, $_POST['descripcion'] ?? '');
        $piso       = intval($_POST['piso']);
        $capacidad  = intval($_POST['capacidad']);
        $precio     = floatval($_POST['precio']);

        if ($numero == '' || $id_tipo <= 0 || $capacidad <= 0 || $precio <= 0) {
            echo 'Datos incompletos o inválidos';
            exit;
        }

        // Antes de INSERT o UPDATE
        $check = mysqli_query($conection, "SELECT idhabitacion FROM habitaciones WHERE numero = '$numero'");
        if (mysqli_num_rows($check) > 0) {
            echo 'Ya existe una habitación con ese número';
            exit;
        }


        $query = mysqli_query($conection, "
        INSERT INTO habitaciones (numero, id_tipo, descripcion, piso, capacidad, precio, estado)
        VALUES ('$numero', $id_tipo, '$descripcion', $piso, $capacidad, $precio, 'disponible')
    ");

        echo $query ? 'ok' : 'Error al guardar la habitación';
        exit;
    }

    if ($_POST['action'] == 'actualizarHabitacion') {
        $id          = intval($_POST['idhabitacion']);
        $numero      = mysqli_real_escape_string($conection, $_POST['numero']);
        $id_tipo     = intval($_POST['id_tipo']);
        $descripcion = mysqli_real_escape_string($conection, $_POST['descripcion'] ?? '');
        $piso        = intval($_POST['piso']);
        $capacidad   = intval($_POST['capacidad']);
        $precio      = floatval($_POST['precio']);
        $estado      = mysqli_real_escape_string($conection, $_POST['estado']);

        if (
            $id <= 0 || $numero == '' || $id_tipo <= 0 ||
            $capacidad <= 0 || $precio <= 0 || $estado == ''
        ) {
            echo 'Datos inválidos';
            exit;
        }

        // Validar que no se repita el número de habitación
        $check = mysqli_query($conection, "
        SELECT idhabitacion FROM habitaciones 
        WHERE numero = '$numero' AND idhabitacion != $id
    ");
        if (mysqli_num_rows($check) > 0) {
            echo 'Ya existe una habitación con ese número';
            exit;
        }

        // Actualizar habitación
        $query = mysqli_query($conection, "
        UPDATE habitaciones 
        SET numero = '$numero',
            id_tipo = $id_tipo,
            descripcion = '$descripcion',
            piso = $piso,
            capacidad = $capacidad,
            precio = $precio,
            estado = '$estado'
        WHERE idhabitacion = $id
    ");

        echo $query ? 'ok' : 'Error al actualizar la habitación';
        exit;
    }

    if ($_POST['action'] == 'eliminarHabitacion') {
        $id = intval($_POST['idhabitacion']);

        if ($id <= 0) {
            echo 'ID inválido';
            exit;
        }

        // Eliminación lógica: desactivar la habitación
        $query = mysqli_query($conection, "
        UPDATE habitaciones 
        SET habilitada = 0 
        WHERE idhabitacion = $id
    ");

        echo $query ? 'OK' : 'Error al desactivar la habitación';
        exit;
    }

    if ($_POST['action'] == 'activarHabitacion') {
        $id = intval($_POST['idhabitacion']);

        if ($id <= 0) {
            echo 'ID inválido';
            exit;
        }

        $query = mysqli_query($conection, "
        UPDATE habitaciones 
        SET habilitada = 1 
        WHERE idhabitacion = $id
    ");

        echo $query ? 'OK' : 'Error al activar la habitación';
        exit;
    }


    if ($_POST['action'] == 'formTarifaHabitacion') {
        echo '
    <form action="" method="post" name="form_tarifa" id="form_tarifa" class="formTarifa" onsubmit="event.preventDefault(); guardarTarifaHabitacion();">
        <h2>Nueva Tarifa</h2>
        <hr>

        <label for="nombre">Nombre:</label>
        <input type="text" name="nombre" id="nombre" class="wd100" required>

        <label for="descripcion">Descripción:</label>
        <textarea name="descripcion" id="descripcion" class="wd100" style="height: 80px;"></textarea>

        <label for="precio_por_persona">Precio por persona:</label>
        <input type="number" step="0.01" name="precio_por_persona" id="precio_por_persona" class="wd100" required>

        <input type="hidden" name="action" value="guardarTarifaHabitacion">

        <div class="acciones wd100">
            <button type="submit" class="btn_new"><i class="fas fa-save"></i> Guardar</button>
            <a href="#" class="btn_ok closeModal" onclick="closeModal();"><i class="fas fa-times"></i> Cerrar</a>
        </div>
    </form>';
    }



    if ($_POST['action'] == 'formEditarTarifaHabitacion') {
        $id = intval($_POST['co']);
        $tarifa = mysqli_fetch_assoc(mysqli_query($conection, "SELECT * FROM tarifas_habitaciones WHERE id = $id"));

        if (!$tarifa) {
            echo '<p>Error: Tarifa no encontrada.</p>';
            exit;
        }

        echo '
    <form action="" method="post" name="form_editar_tarifa" id="form_editar_tarifa" class="formEditarTarifa" onsubmit="event.preventDefault(); actualizarTarifaHabitacion();">
        <h2>Editar Tarifa</h2>
        <hr>

        <input type="hidden" name="id" value="' . $tarifa['id'] . '">

        <label for="nombre">Nombre:</label>
        <input type="text" name="nombre" id="nombre" class="wd100" required value="' . htmlspecialchars($tarifa['nombre']) . '">

        <label for="descripcion">Descripción:</label>
        <textarea name="descripcion" id="descripcion" class="wd100" style="height: 80px;">' . htmlspecialchars($tarifa['descripcion']) . '</textarea>

        <label for="precio_por_persona">Precio por persona:</label>
        <input type="number" step="0.01" name="precio_por_persona" id="precio_por_persona" class="wd100" required value="' . floatval($tarifa['precio_por_persona']) . '">

        <input type="hidden" name="action" value="actualizarTarifaHabitacion">

        <div class="acciones wd100">
            <button type="submit" class="btn_new"><i class="fas fa-save"></i> Actualizar</button>
            <a href="#" class="btn_ok closeModal" onclick="closeModal();"><i class="fas fa-times"></i> Cerrar</a>
        </div>
    </form>';
    }

    if ($_POST['action'] == 'guardarTarifaHabitacion') {
        $nombre     = mysqli_real_escape_string($conection, $_POST['nombre']);
        $descripcion = mysqli_real_escape_string($conection, $_POST['descripcion'] ?? '');
        $precio     = floatval($_POST['precio_por_persona']);

        if ($nombre == '' || $precio <= 0) {
            echo 'Datos inválidos';
            exit;
        }

        // Evitar duplicados
        $check = mysqli_query($conection, "SELECT id FROM tarifas_habitaciones WHERE nombre = '$nombre' AND habilitada = 1");
        if (mysqli_num_rows($check) > 0) {
            echo 'Ya existe una tarifa con ese nombre';
            exit;
        }

        $sql = "INSERT INTO tarifas_habitaciones (nombre, descripcion, precio_por_persona, habilitada)
            VALUES ('$nombre', '$descripcion', $precio, 1)";
        echo mysqli_query($conection, $sql) ? 'ok' : 'Error al guardar la tarifa';
        exit;
    }

    if ($_POST['action'] == 'actualizarTarifaHabitacion') {
        $id         = intval($_POST['id']);
        $nombre     = mysqli_real_escape_string($conection, $_POST['nombre']);
        $descripcion = mysqli_real_escape_string($conection, $_POST['descripcion'] ?? '');
        $precio     = floatval($_POST['precio_por_persona']);

        if ($id <= 0 || $nombre == '' || $precio <= 0) {
            echo 'Datos inválidos';
            exit;
        }

        // Validar nombre duplicado (excepto la misma ID)
        $check = mysqli_query($conection, "SELECT id FROM tarifas_habitaciones WHERE nombre = '$nombre' AND id != $id AND habilitada = 1");
        if (mysqli_num_rows($check) > 0) {
            echo 'Ya existe otra tarifa con ese nombre';
            exit;
        }

        $sql = "UPDATE tarifas_habitaciones 
            SET nombre = '$nombre', descripcion = '$descripcion', precio_por_persona = $precio
            WHERE id = $id";
        echo mysqli_query($conection, $sql) ? 'ok' : 'Error al actualizar la tarifa';
        exit;
    }


    if ($_POST['action'] == 'eliminarTarifaHabitacion') {
        $id = intval($_POST['id']);
        if ($id <= 0) {
            echo 'ID inválido';
            exit;
        }

        $sql = "UPDATE tarifas_habitaciones SET habilitada = 0 WHERE id = $id";
        echo mysqli_query($conection, $sql) ? 'ok' : 'Error a   l eliminar';
        exit;
    }




    if ($_POST['action'] == 'activarTarifaHabitacion') {
        $id = intval($_POST['id']);
        if ($id <= 0) {
            echo 'ID inválido';
            exit;
        }

        $sql = "UPDATE tarifas_habitaciones SET habilitada = 1 WHERE id = $id";
        echo mysqli_query($conection, $sql) ? 'ok' : 'Error al activar';
        exit;
    }


    if ($_POST['action'] == 'formTarifaExtra') {
        echo '
    <form action="" method="post" name="form_tarifa_extra" id="form_tarifa_extra" class="formTarifaExtra"
        onsubmit="event.preventDefault(); guardarTarifaExtra();">
        <h2>Nueva Tarifa Extra</h2>
        <hr>

        <label for="tipo_extra">Tipo de Extra:</label>
        <select name="tipo_extra" id="tipo_extra" class="wd100" required>
            <option value="">Seleccione</option>
            <option value="desayuno">Desayuno</option>
            <option value="tour">Tour</option>
        </select>

        <label for="valor">Valor:</label>
        <input type="number" step="0.01" name="valor" id="valor" class="wd100" required>

        <input type="hidden" name="action" value="guardarTarifaExtra">

        <div class="acciones wd100">
            <button type="submit" class="btn_new"><i class="fas fa-save"></i> Guardar</button>
            <a href="#" class="btn_ok closeModal" onclick="closeModal();"><i class="fas fa-times"></i> Cerrar</a>
        </div>
    </form>';
    }

    if ($_POST['action'] == 'formEditarTarifaExtra') {
        $id = intval($_POST['co']);
        $extra = mysqli_fetch_assoc(mysqli_query($conection, "SELECT * FROM tarifa_extras WHERE id = $id"));

        if (!$extra) {
            echo '<p>Error: Tarifa no encontrada.</p>';
            exit;
        }

        echo '
    <form action="" method="post" name="form_editar_tarifa_extra" id="form_editar_tarifa_extra" class="formEditarTarifaExtra"
        onsubmit="event.preventDefault(); actualizarTarifaExtra();">
        <h2>Editar Tarifa Extra</h2>
        <hr>

        <input type="hidden" name="id" value="' . $extra['id'] . '">

        <label for="tipo_extra">Tipo de Extra:</label>
        <select name="tipo_extra" id="tipo_extra" class="wd100" required>
            <option value="">Seleccione</option>
            <option value="desayuno" ' . ($extra['tipo_extra'] == 'desayuno' ? 'selected' : '') . '>Desayuno</option>
            <option value="tour" ' . ($extra['tipo_extra'] == 'tour' ? 'selected' : '') . '>Tour</option>
        </select>

        <label for="valor">Valor:</label>
        <input type="number" step="0.01" name="valor" id="valor" class="wd100" required value="' . floatval($extra['valor']) . '">

        <input type="hidden" name="action" value="actualizarTarifaExtra">

        <div class="acciones wd100">
            <button type="submit" class="btn_new"><i class="fas fa-save"></i> Actualizar</button>
            <a href="#" class="btn_ok closeModal" onclick="closeModal();"><i class="fas fa-times"></i> Cerrar</a>
        </div>
    </form>';
    }

    if ($_POST['action'] == 'guardarTarifaExtra') {
        $tipo_extra = mysqli_real_escape_string($conection, $_POST['tipo_extra']);
        $valor = floatval($_POST['valor']);

        if (!in_array($tipo_extra, ['desayuno', 'tour']) || $valor <= 0) {
            echo 'Datos inválidos';
            exit;
        }

        // Validar duplicados activos
        $check = mysqli_query($conection, "SELECT id FROM tarifa_extras WHERE valor = ''");
        if (mysqli_num_rows($check) > 0) {
            echo 'Ya existe una tarifa activa para ese tipo';
            exit;
        }

        $sql = "INSERT INTO tarifa_extras (tipo_extra, valor, habilitado)
            VALUES ('$tipo_extra', $valor, 1)";
        echo mysqli_query($conection, $sql) ? 'ok' : 'Error al guardar';
        exit;
    }

    if ($_POST['action'] == 'actualizarTarifaExtra') {
        $id = intval($_POST['id']);
        $tipo_extra = mysqli_real_escape_string($conection, $_POST['tipo_extra']);
        $valor = floatval($_POST['valor']);

        if ($id <= 0 || !in_array($tipo_extra, ['desayuno', 'tour']) || $valor <= 0) {
            echo 'Datos inválidos';
            exit;
        }

        // Validar duplicados
        $check = mysqli_query($conection, "SELECT id FROM tarifa_extras WHERE valor = '$valor' AND id != $id ");
        if (mysqli_num_rows($check) > 0) {
            echo 'Ya existe otra tarifa activa para ese tipo';
            exit;
        }

        $sql = "UPDATE tarifa_extras SET tipo_extra = '$tipo_extra', valor = $valor WHERE id = $id";
        echo mysqli_query($conection, $sql) ? 'ok' : 'Error al actualizar';
        exit;
    }

    if ($_POST['action'] == 'eliminarTarifaExtra') {
        $id = intval($_POST['id']);
        if ($id <= 0) {
            echo 'ID inválido';
            exit;
        }

        $sql = "UPDATE tarifa_extras SET habilitado = 0 WHERE id = $id";
        echo mysqli_query($conection, $sql) ? 'ok' : 'Error al eliminar';
        exit;
    }

    if ($_POST['action'] == 'activarTarifaExtra') {
        $id = intval($_POST['id']);
        if ($id <= 0) {
            echo 'ID inválido';
            exit;
        }

        $tipo = mysqli_fetch_assoc(mysqli_query($conection, "SELECT tipo_extra FROM tarifa_extras WHERE id = $id"));
        if (!$tipo) {
            echo 'No encontrado';
            exit;
        }

        // Desactivar otros del mismo tipo
        mysqli_query($conection, "UPDATE tarifa_extras SET habilitado = 0 WHERE tipo_extra = '{$tipo['tipo_extra']}'");

        // Activar este
        $sql = "UPDATE tarifa_extras SET habilitado = 1 WHERE id = $id";
        echo mysqli_query($conection, $sql) ? 'ok' : 'Error al activar';
        exit;
    }


    if ($_POST['action'] == 'formLugarTour') {
        echo '
    <form action="" method="post" id="form_lugar_tour" onsubmit="event.preventDefault(); guardarLugarTour();">
        <h2>Nuevo Lugar Turístico</h2>
        <hr>
        <label for="nombre">Nombre del lugar:</label>
        <input type="text" name="nombre" id="nombre" class="wd100" required>

        <input type="hidden" name="action" value="guardarLugarTour">

        <div class="acciones wd100">
            <button type="submit" class="btn_new"><i class="fas fa-save"></i> Guardar</button>
            <a href="#" class="btn_ok closeModal" onclick="closeModal();">
                <i class="fas fa-times"></i> Cerrar
            </a>
        </div>
    </form>';
    }

    if ($_POST['action'] == 'formEditarLugarTour') {
        $id = intval($_POST['co']);
        $lugar = mysqli_fetch_assoc(mysqli_query($conection, "SELECT * FROM lugares_tour WHERE id = $id AND activo = 1"));

        if (!$lugar) {
            echo '<p>Error: Lugar no encontrado o inactivo.</p>';
            exit;
        }

        echo '
    <form action="" method="post" id="form_editar_lugar_tour" onsubmit="event.preventDefault(); actualizarLugarTour();">
        <h2>Editar Lugar Turístico</h2>
        <hr>

        <input type="hidden" name="id" value="' . $lugar['id'] . '">

        <label for="nombre">Nombre del lugar:</label>
        <input type="text" name="nombre" id="nombre" class="wd100" required value="' . htmlspecialchars($lugar['nombre']) . '">

        <input type="hidden" name="action" value="actualizarLugarTour">

        <div class="acciones wd100">
            <button type="submit" class="btn_new"><i class="fas fa-save"></i> Actualizar</button>
            <a href="#" class="btn_ok closeModal" onclick="closeModal();">
                <i class="fas fa-times"></i> Cerrar
            </a>
        </div>
    </form>';
    }



    if ($_POST['action'] == 'guardarLugarTour') {
        $nombre = mysqli_real_escape_string($conection, $_POST['nombre']);

        if ($nombre == '') {
            echo 'Nombre requerido';
            exit;
        }

        // Validar duplicado
        $check = mysqli_query($conection, "SELECT id FROM lugares_tour WHERE nombre = '$nombre' AND activo = 1");
        if (mysqli_num_rows($check) > 0) {
            echo 'Ya existe un lugar turístico con ese nombre';
            exit;
        }

        $query = mysqli_query($conection, "INSERT INTO lugares_tour (nombre, activo) VALUES ('$nombre', 1)");
        echo $query ? 'ok' : 'Error al guardar';
        exit;
    }

    if ($_POST['action'] == 'actualizarLugarTour') {
        $id = intval($_POST['id']);
        $nombre = mysqli_real_escape_string($conection, $_POST['nombre']);

        if ($id <= 0 || $nombre == '') {
            echo 'Datos inválidos';
            exit;
        }

        // Validar duplicado con otro ID
        $check = mysqli_query($conection, "SELECT id FROM lugares_tour WHERE nombre = '$nombre' AND id != $id AND activo = 1");
        if (mysqli_num_rows($check) > 0) {
            echo 'Ya existe otro lugar con ese nombre';
            exit;
        }

        $query = mysqli_query($conection, "UPDATE lugares_tour SET nombre = '$nombre' WHERE id = $id");
        echo $query ? 'ok' : 'Error al actualizar';
        exit;
    }

    if ($_POST['action'] == 'eliminarLugarTour') {
        $id = intval($_POST['id']);

        if ($id <= 0) {
            echo 'ID inválido';
            exit;
        }

        $query = mysqli_query($conection, "UPDATE lugares_tour SET activo = 0 WHERE id = $id");

        echo $query ? 'ok' : 'Error al eliminar';
        exit;
    }

    if ($_POST['action'] == 'activarLugarTour') {
        $id = intval($_POST['id']);

        if ($id <= 0) {
            echo 'ID inválido';
            exit;
        }

        $query = mysqli_query($conection, "UPDATE lugares_tour SET activo = 1 WHERE id = $id");

        echo $query ? 'ok' : 'Error al activar';
        exit;
    }


    if ($_POST['action'] == 'imprimirDesayunos') {
        echo imprimirDesayunosHoy();
        exit;
    }



    //print_r($data);exit;
}

function tipoPagoNombre($tipo)
{
    switch ($tipo) {
        case 2: return 'Tarjeta';
        case 3: return 'Transferencia';
        case 4: return 'DeUna';
        default: return 'Otro';
    }
}
exit;
?>