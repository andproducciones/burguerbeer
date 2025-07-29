<?php

include('../conexion.php');
include "../sistema/includes/functions.php";

// Configurar encabezados
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept');
header('Content-Type: application/json; charset=utf-8');

// Leer el cuerpo de la solicitud
$post = json_decode(file_get_contents('php://input'), true);

// Verificar si hay acción definida
if (!isset($post['accion'])) {
    echo json_encode(['response' => 'No hay accion 1', 'estado' => false]);
    exit;
}



// Variables globales
$respuesta = [];
$data = [];

switch ($post['accion']) {
    case 'categorias':
        // Consultar las categorías activas
        $sql = "SELECT id, categoria, foto, estatus FROM categorias WHERE estatus = 1";
        $query = mysqli_query($conection, $sql);
    
        if ($query && $query->num_rows > 0) {
            $categorias = [];
            while ($row = $query->fetch_assoc()) {
                $categorias[] = $row; // Agregar cada categoría al arreglo
            }
            $respuesta = [
                'response' => 'Consulta exitosa',
                'estado' => true
                 // Devolver el arreglo de categorías
            ];
            $data = $categorias;
        } else {
            $respuesta = [
                'response' => 'No se encontraron categorías',
                'estado' => false
            ];
        }
        break;

        case 'productos':
            // Consultar los productos activos
            $sql = "SELECT id, codproducto, codbarras, producto, costo, precio, precio2, precio3, existencia, categoria, lugar, foto, codatributos, estatus 
                    FROM producto 
                    WHERE estatus = 1";
            $query = mysqli_query($conection, $sql);
        
            if ($query && $query->num_rows > 0) {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http');
                $host = $_SERVER['HTTP_HOST'];
                $baseUrl = $protocol . '://' . $host . '/burguerbeer/sistema/img/productos/';
                $imagePath = $_SERVER['DOCUMENT_ROOT'] . '/burguerbeer/sistema/img/productos/'; // Ruta absoluta
        
                $productos = [];
                while ($row = $query->fetch_assoc()) {
                    // Verificar si el archivo existe usando la ruta del sistema de archivos
                    if (!empty($row['foto']) && file_exists($imagePath . $row['foto'])) {
                        $row['foto'] = $baseUrl . $row['foto'];
                    } else {
                        $row['foto'] = null; // Si no existe la imagen, devuelve null
                    }
                    $productos[] = $row; // Agregar el producto al arreglo
                }
                $respuesta = [
                    'response' => 'Consulta exitosa',
                    'estado' => true
                ];
                $data = $productos;
            } else {
                $respuesta = [
                    'response' => 'No se encontraron productos',
                    'estado' => false
                ];
                $data = null;
            }
            break;

            case 'addProductoDetalle':
                if (empty($post['producto']) || empty($post['cantidad']) || empty($post['mesa']) || empty($post['usuario'])) {
                    $respuesta = [
                        'response' => 'Error: Parámetros inválidos',
                        'estado' => false
                    ];
                    $data = null;
                    break;
                }

                $codproducto = intval($post['producto']);
                $cantidad = intval($post['cantidad']);
                $mesa = intval($post['mesa']);
                $token = md5($post['usuario']);

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
                            $sub_total += $precioTotal;
                            $total += $precioTotal;
                
                            // Procesar observaciones
                            $observaciones = json_decode($data['observaciones'] ?? '[]', true);
                            $seleccionado = is_array($observaciones) ? implode(' | ', array_map('htmlspecialchars', $observaciones)) : null;
                
                            $detalle[] = [
                                'cantidad' => (int) $data['cantidad'],
                                'producto' => htmlspecialchars($data['producto'], ENT_QUOTES, 'UTF-8'),
                                'observaciones' => $seleccionado,
                                'precio_unitario' => number_format($data['precio_venta'], 2),
                                'precio_total' => number_format($precioTotal, 2),
                                'preparar' => (int) $data['preparar'],
                                'correlativo' => (int) $data['correlativo']
                            ];
                
                            $numero = $data['numero']; // Número de mesa
                        }

                        $impuesto = round($sub_total * 0.12, 2); // IVA del 12%
                        $tl_sniva = round($sub_total - $impuesto, 2);
                        $total = round($tl_sniva + $impuesto, 2);
                    
                    }

                    $respuesta = [
                        'response' => 'Detalle de venta generado exitosamente',
                        'estado' => true
                    ];

                    $data = [
                        'detalle' => $detalle,
                        'totales' => [
                            'subtotal_sin_iva' => number_format($tl_sniva, 2),
                            'impuesto' => number_format($impuesto, 2),
                            'total' => number_format($total, 2)
                        ],
                        'mesa' => $numero
                    ];
                }else {
                    $respuesta = [
                        'response' => 'Error al generar detalle de venta',
                        'estado' => false
                    ];
                    $data = null;
                }

            break;

            case 'del_product_detalle':
                if (empty($post['id_detalle']) || empty($post['mesa']) || empty($post['usuario'])) {
                    $respuesta = [
                        'response' => 'Error: Parámetros inválidos',
                        'estado' => false
                    ];
                    $data = null;
                    break;
                }
            
                $id_detalle = intval($post['id_detalle']);
                $mesa = intval($post['mesa']);
                $token = md5($post['usuario']);
            
                // Llamar al procedimiento almacenado para eliminar el producto del detalle
                $query_detalle_temp = mysqli_query($conection, "CALL del_detalle_temp($id_detalle, '$token', $mesa)");
            
                if ($query_detalle_temp) {
                    $result = mysqli_num_rows($query_detalle_temp);
            
                    if ($result > 0) {
                        $detalle = [];
                        $sub_total = 0;
                        $total = 0;
            
                        while ($data = mysqli_fetch_assoc($query_detalle_temp)) {
                            $precioTotal = round($data['cantidad'] * $data['precio_venta'], 2);
                            $sub_total += $precioTotal;
                            $total += $precioTotal;
            
                            // Procesar observaciones
                            $observaciones = json_decode($data['observaciones'] ?? '[]', true);
                            $seleccionado = is_array($observaciones) ? implode(' | ', array_map('htmlspecialchars', $observaciones)) : null;
            
                            $detalle[] = [
                                'cantidad' => (int) $data['cantidad'],
                                'producto' => htmlspecialchars($data['producto'], ENT_QUOTES, 'UTF-8'),
                                'observaciones' => $seleccionado,
                                'precio_unitario' => number_format($data['precio_venta'], 2),
                                'precio_total' => number_format($precioTotal, 2),
                                'preparar' => (int) $data['preparar'],
                                'correlativo' => (int) $data['correlativo']
                            ];
            
                            $numero = $data['numero']; // Número de mesa
                        }
            
                        $impuesto = round($sub_total * 0.12, 2); // IVA del 12%
                        $tl_sniva = round($sub_total - $impuesto, 2);
                        $total = round($tl_sniva + $impuesto, 2);
            
                        $respuesta = [
                            'response' => 'Producto eliminado correctamente',
                            'estado' => true
                        ];
                        $data = [
                            'detalle' => $detalle,
                            'totales' => [
                                'subtotal_sin_iva' => number_format($tl_sniva, 2),
                                'impuesto' => number_format($impuesto, 2),
                                'total' => number_format($total, 2)
                            ],
                            'mesa' => $numero
                        ];
                    } else {
                        $respuesta = [
                            'response' => 'No se encontró el producto en el detalle',
                            'estado' => false
                        ];
                        $data = null;
                    }
                } else {
                    $respuesta = [
                        'response' => 'Error al eliminar el producto',
                        'estado' => false
                    ];
                    $data = null;
                }
                break;

                case 'imprimirComanda2':
                    try {
                        // Iniciar una transacción
                        mysqli_begin_transaction($conection);
                
                        // Verificar parámetros obligatorios
                        if (empty($post['mesa']) || empty($post['nombre']) || empty($post['usuario'])) {
                            throw new Exception("Error: Parámetros inválidos");
                        }
                
                        // Asignar variables seguras
                        $co = md5($post['usuario']);
                        $mesa = intval($post['mesa']);
                        $nombreCliente = htmlspecialchars($post['nombre'], ENT_QUOTES, 'UTF-8');
                
                        // Obtener el número de la mesa
                        $query_mesa = mysqli_query($conection, "SELECT numero FROM mesas WHERE id = $mesa");
                        if (!$query_mesa || mysqli_num_rows($query_mesa) == 0) {
                            throw new Exception("Error: No se pudo obtener el número de la mesa");
                        }
                        $mesas = mysqli_fetch_assoc($query_mesa);
                        $mesa2 = $mesas['numero'];
                
                        // Actualizar el estado de los productos en detalle_temp (preparar = 2)
                        $query_update = mysqli_query($conection, "UPDATE detalle_temp SET preparar = 2 WHERE token_user = '$co' AND mesa = $mesa AND preparar = 1");
                        if (!$query_update) {
                            throw new Exception("Error: No se pudo actualizar el detalle de la mesa");
                        }
                
                        // Obtener productos de la mesa
                        $query_productos = mysqli_query($conection, "SELECT p.producto, dt.cantidad, dt.mesa, dt.atributos, dt.observaciones
                            FROM detalle_temp dt
                            INNER JOIN producto p ON dt.codproducto = p.codproducto
                            WHERE dt.token_user = '$co' AND dt.mesa = $mesa");
                
                        if (!$query_productos) {
                            throw new Exception("Error: No se pudo obtener los productos de la mesa");
                        }
                
                        $result_detalle = mysqli_num_rows($query_productos);
                        $data2 = [];
                
                        if ($result_detalle > 0) {

                            while ($row = mysqli_fetch_assoc($query_productos)) { 
                                    // Añadimos producto y cantidad a $data2
                                    $data2[] = $row['producto'];
                                    $data2[] = $row['cantidad'];

                                    // Procesamos las observaciones si están presentes
                                    if (!empty($row['observaciones'])) {
                                        $array = json_decode($row['observaciones'], true); // JSON ya se decodifica en un array asociativo
                                        if (is_array($array)) {
                                            $seleccionado = implode(', ', $array); // Convierte el array en una cadena con ", " como separador
                                        } else {
                                            $seleccionado = ''; // En caso de error en el formato JSON, dejamos vacío
                                        }
                                        $data2[] = $seleccionado;
                                    } else {
                                        $data2[] = ''; // Si no hay observaciones, añadimos un valor vacío
                                    }
                                }
                            


                        } else {
                            throw new Exception("Error: No hay productos para generar el ticket");
                        }
                
                        // Generar datos de la comanda
                        $fecha = date('Y-m-d G:i:s');
                        $nombreMesero = htmlspecialchars($post['nombre_mesero'] . ' ' . $post['apellido_mesero'], ENT_QUOTES, 'UTF-8');
                
                        // Enviar a impresión
                        $imprimir = imprimirComanda($mesa2, $nombreCliente, $nombreMesero, $data2, $fecha);
                        if (!$imprimir) {
                            throw new Exception("Error: No se pudo imprimir la comanda 1");
                        }

                        $imprimir = imprimirComanda($mesa2, $nombreCliente, $nombreMesero, $data2, $fecha);
                        if (!$imprimir) {
                            throw new Exception("Error: No se pudo imprimir la comanda 2");
                        }
                
                        // Confirmar la transacción
                        mysqli_commit($conection);
                
                        // Respuesta JSON de éxito
                        $respuesta = [
                            'response' => 'Comanda generada exitosamente',
                            'estado' => true
                        ];
                        $data = '';
                    } catch (Exception $e) {
                        // Revertir la transacción en caso de error
                        mysqli_rollback($conection);
                        $respuesta = [
                            'response' => $e->getMessage(),
                            'estado' => false
                        ];
                        $data = null;
                    }
                    break;


                    case 'obtenerMesas':
                        try {
                            // Consulta para obtener las mesas
                            $sql = "SELECT id, numero, nombre, estatus FROM mesas";
                            $query = mysqli_query($conection, $sql);
                    
                            // Verificar si la consulta devolvió resultados
                            if ($query && mysqli_num_rows($query) > 0) {
                                $mesas = [];
                                while ($row = mysqli_fetch_assoc($query)) {
                                    $mesas[] = [
                                        'id' => (int) $row['id'],
                                        'numero' => (int) $row['numero'],
                                        'nombre' =>  $row['nombre'],
                                        'estatus' => $row['estatus'] // Se asume que es un texto o número
                                    ];
                                }
                    
                                // Respuesta exitosa
                                $respuesta = [
                                    'response' => 'Consulta exitosa',
                                    'estado' => true
                                    
                                ];

                                $data = $mesas;
                            } else {
                                // Sin resultados
                                $respuesta = [
                                    'response' => 'No se encontraron mesas',
                                    'estado' => false,
                                    'data' => null
                                ];
                            }
                        } catch (Exception $e) {
                            // En caso de error
                            $respuesta = [
                                'response' => 'Error en la consulta: ' . $e->getMessage(),
                                'estado' => false,
                                'data' => null
                            ];
                        }
                
                        break;


    case 'searchForDetalle':

    // Validación de parámetros obligatorios
    if (empty($post['mesa']) || empty($post['usuario'])) {
        echo json_encode([
            'respuesta' => [
                'response' => 'Error: Parámetros inválidos',
                'estado'   => false
            ],
            'data' => null
        ], JSON_UNESCAPED_UNICODE);
        break;
    }

    // Definir variables
    $mesa  = intval($post['mesa']);
    $token = md5($post['usuario']);

    try {
        // Iniciar transacción
        mysqli_begin_transaction($conection, MYSQLI_TRANS_START_READ_ONLY);

        // Consulta para obtener los detalles del pedido en la mesa
        $query = mysqli_query($conection, "
            SELECT 
                tmp.correlativo, tmp.cantidad, tmp.precio_venta, tmp.mesa, 
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

        if (!$query) {
            throw new Exception('Error en la consulta de productos');
        }

        // Verificar si hay productos en la mesa
        if (mysqli_num_rows($query) === 0) {
            
        }

        // Inicializar variables
        $detalle = [];
        $sub_total = 0;
        $iva = 12; // IVA del 12%
        $total = 0;

        // Procesar los detalles del pedido
        while ($data = mysqli_fetch_assoc($query)) {
            $precioTotal = round($data['cantidad'] * $data['precio_venta'], 2);
            $sub_total += $precioTotal;

            // Procesar observaciones
            $observaciones = json_decode($data['observaciones'] ?? '[]', true);
            $seleccionado = is_array($observaciones) ? implode(' | ', array_map('htmlspecialchars', $observaciones)) : null;

            // Agregar detalle al array
            $detalle[] = [
                'correlativo'     => (int) $data['correlativo'],
                'cantidad'        => (int) $data['cantidad'],
                'producto'        => htmlspecialchars($data['producto'], ENT_QUOTES, 'UTF-8'),
                'observaciones'   => $seleccionado,
                'precio_unitario' => number_format($data['precio_venta'], 2),
                'precio_total'    => number_format($precioTotal, 2),
                'preparar'        => (int) $data['preparar']
            ];
        }

        // Cálculo de impuestos y totales
        $impuesto = round($sub_total * ($iva / 100), 2);
        $tl_sniva = round($sub_total - $impuesto, 2);
        $total = round($tl_sniva + $impuesto, 2);

        // Obtener número de mesa
        $query_2 = mysqli_query($conection, "SELECT numero FROM mesas WHERE id = $mesa");
        if (!$query_2 || mysqli_num_rows($query_2) === 0) {
            throw new Exception('Error: No se encontró la mesa');
        }
        $numero = mysqli_fetch_assoc($query_2)['numero'];

        $respuesta = [
                            'response' => 'Productos correctamente',
                            'estado' => true
                        ];
                        $data = [
                            'detalle' => $detalle,
                            'totales' => [
                                'subtotal_sin_iva' => number_format($tl_sniva, 2),
                                'impuesto' => number_format($impuesto, 2),
                                'total' => number_format($total, 2)
                            ],
                            'mesa' => $numero
                        ];

        // Obtener el número de mesa
        

        // Confirmar la transacción
        mysqli_commit($conection);

        

    } catch (Exception $e) {
        // En caso de error, revertir la transacción
        mysqli_rollback($conection);
        
        echo json_encode([
            'respuesta' => [
                'response' => 'Error: ' . $e->getMessage(),
                'estado'   => false
            ],
            'data' => null
        ], JSON_UNESCAPED_UNICODE);
    }

    break;
    
       case 'actualizarNombreMesa':
        try {
            if (empty($post['mesa']) || !isset($post['nombre'])) {
                throw new Exception("Error: Parámetros inválidos");
            }
<<<<<<< Updated upstream

    
            $mesa = intval($post['mesa']);
            //$usuario = $post['usuario'];
           $nombre = trim($post['nombre']) === '' ? 'NULL' : "'" . mysqli_real_escape_string($conection, $post['nombre']) . "'";
=======
            $mesa = intval($post['mesa']);
            //$usuario = $post['usuario'];
            $nombre = trim($post['nombre']) === '' ? 'NULL' : "'" . mysqli_real_escape_string($conection, $post['nombre']) . "'";
>>>>>>> Stashed changes

    
            // ✅ Iniciar transacción
            mysqli_begin_transaction($conection);
    
            // 🔍 Verificar si la mesa existe
            $query_mesa = mysqli_query($conection, "SELECT numero FROM mesas WHERE id = $mesa");
            if (!$query_mesa || mysqli_num_rows($query_mesa) == 0) {
                throw new Exception("Error: No se encontró la mesa.");
            }
    
            // 📝 Actualizar el nombre de la mesa
            $query_update = mysqli_query($conection, "UPDATE mesas SET nombre = $nombre WHERE id = $mesa");
<<<<<<< Updated upstream

=======
>>>>>>> Stashed changes
            if (!$query_update) {
                throw new Exception("Error: No se pudo actualizar el nombre de la mesa.");
            }
    
            // ✅ Confirmar cambios
            mysqli_commit($conection);
    
            // 🔄 Respuesta de éxito
            $respuesta = [
                'response' => 'Nombre de la mesa actualizado correctamente.',
                'estado' => true
            ];
    
            $data = [
                'mesa' => $mesa,
                'nombre' => $nombre
            ];
    
        } catch (Exception $e) {
            // ❌ Si hay un error, revertir cambios
            mysqli_rollback($conection);
    
            $respuesta = [
                'response' => $e->getMessage(),
                'estado' => false
            ];
            $data = null;
        }
        break;

        case 'verificarProductosMesa':
            try {
                if (empty($post['mesa'])) {
                    throw new Exception("Error: Falta el ID de la mesa.");
                }
        
                $mesa = intval($post['mesa']);
                $estadoPreparacion = 0; // 0 = sin productos, 2 = en preparación, 3 = servidos
        
                // Primero: ¿hay productos en esta mesa?
                $query_total = mysqli_query($conection, "
                    SELECT COUNT(*) as total 
                    FROM detalle_temp 
                    WHERE mesa = $mesa
                ");
        
                if (!$query_total) {
                    throw new Exception("Error al contar productos.");
                }
        
                $row_total = mysqli_fetch_assoc($query_total);
                $totalProductos = intval($row_total['total']);
        
                if ($totalProductos === 0) {
                    $estadoPreparacion = 0;
                } else {
                    // ¿Existen productos NO servidos?
                    $query_no_servidos = mysqli_query($conection, "
                        SELECT 1 
                        FROM detalle_temp 
                        WHERE mesa = $mesa AND preparar != 3 
                        LIMIT 1
                    ");
        
                    if (!$query_no_servidos) {
                        throw new Exception("Error al verificar productos pendientes.");
                    }
        
                    $estadoPreparacion = (mysqli_num_rows($query_no_servidos) > 0) ? 2 : 3;
                }
        
                $respuesta = [
                    'response' => 'Consulta exitosa',
                    'estado'   => true
                ];
        
                $data = [
                    'estado' => $estadoPreparacion
                ];
        
            } catch (Exception $e) {
                $respuesta = [
                    'response' => $e->getMessage(),
                    'estado' => false
                ];
                $data = null;
            }
            break;
        

            case 'formDetalleProducto2':
                try {
                    if (empty($post['co'])) {
                        throw new Exception("Error: Parámetros inválidos");
                    }
        
                    $id = intval($post['co']);
        
                    $query = mysqli_query($conection, "SELECT observaciones, codatributos FROM detalle_temp WHERE correlativo = $id");
        
                    if (!$query) {
                        throw new Exception("Error en la consulta de observaciones");
                    }
        
                    $data_result = mysqli_fetch_assoc($query);
                    $observaciones = $data_result['observaciones'] ?? '[]';
                    $codatributos = $data_result['codatributos'] ?? '';
        
                    // **Corrección: Manejar correctamente `codatributos`**
                    $atributos = [];
                    if (!empty($codatributos)) {
                        $ids_array = array_map('intval', explode(",", $codatributos));
        
                        foreach ($ids_array as $id2) {
                            $query_atributo = mysqli_query($conection, "SELECT id, atributo FROM atributos_productos WHERE id = $id2");
        
                            if ($query_atributo && mysqli_num_rows($query_atributo) > 0) {
                                while ($data_atributo = mysqli_fetch_assoc($query_atributo)) {
                                    $id_atributo = $data_atributo['id'];
                                    $atributo_nombre = htmlspecialchars($data_atributo['atributo'], ENT_QUOTES, 'UTF-8');
        
                                    // Buscar los tipos disponibles para este atributo
                                    $tipos = [];
                                    $query_tipo = mysqli_query($conection, "SELECT tipo FROM tipo_atributos WHERE codatributo = $id_atributo");
        
                                    if ($query_tipo && mysqli_num_rows($query_tipo) > 0) {
                                        while ($data_tipo = mysqli_fetch_assoc($query_tipo)) {
                                            $tipos[] = htmlspecialchars($data_tipo['tipo'], ENT_QUOTES, 'UTF-8');
                                        }
                                    }
        
                                    $atributos[] = [
                                        'id' => $id_atributo,
                                        'nombre' => $atributo_nombre,
                                        'tipos' => $tipos
                                    ];
                                }
                            }
                        }
                    }
        
                    // **Procesar observaciones**
                    $observaciones_formateadas = json_decode($observaciones, true);
                    if (!is_array($observaciones_formateadas)) {
                        $observaciones_formateadas = [];
                    }
        
                    $respuesta = [
                        'response' => 'Datos obtenidos correctamente',
                        'estado' => true
                    ];
                    $data = [
                        'atributos' => $atributos,
                        'observaciones' => $observaciones_formateadas
                    ];
        
                } catch (Exception $e) {
                    $respuesta = [
                        'response' => $e->getMessage(),
                        'estado' => false
                    ];
                    $data = null;
                }
                break;


                case 'editarProducto':
                    try {
                        if (empty($post['co'])) {
                            throw new Exception("Error: Parámetro 'co' (correlativo) es obligatorio.");
                        }
            
                        $id = intval($post['co']);
                        $observaciones = $post['atributos'] ?? [];
                        $observaciones2 = $post['observaciones'] ?? [];

                        // ✅ Convertir el array en un objeto JSON `{}` en lugar de `[]`
                            if (!empty($observaciones)) {
                                $observaciones_obj = [];
                                foreach ($observaciones as $obs) {
                                    $observaciones_obj[$obs['id']] = $obs['valor'];
                                }
                            } else {
                                $observaciones_obj = new stdClass(); // `{}` vacío en JSON
                            }

                            $observaciones_json['ob'] = $observaciones2;

                            $observaciones_json = json_encode($observaciones_obj, JSON_UNESCAPED_UNICODE);

                            if ($observaciones_json === '{}') {
                                $observaciones_json = '{"ob": "' . addslashes($observaciones2) . '"}';
                            }else{
                                $observaciones_json = substr($observaciones_json, 0, -1) . ', "ob": "' . addslashes($observaciones2) . '"}';
                            }
            
                        // **Corrección: Convertir a JSON seguro incluso si está vacío**
                        //$observaciones_json = json_encode($observaciones ?: [], JSON_UNESCAPED_UNICODE);
                        //$atributos_json = json_encode($atributos ?: [], JSON_UNESCAPED_UNICODE);
            
                        // **Verificar si el producto existe en detalle_temp**
                        $query_exist = mysqli_query($conection, "SELECT * FROM detalle_temp WHERE correlativo = $id");
                        if (!$query_exist || mysqli_num_rows($query_exist) == 0) {
                            throw new Exception("Error: No se encontró el producto en la orden.");
                        }
            
                        mysqli_begin_transaction($conection);
            
                        $query_update = mysqli_query($conection, "
                            UPDATE detalle_temp 
                            SET observaciones = '$observaciones_json'
                                
                            WHERE correlativo = $id
                        ");
            
                        if (!$query_update) {
                            throw new Exception("Error: No se pudo actualizar el producto.");
                        }
            
                        mysqli_commit($conection);
            
                        $respuesta = [
                            'response' => 'Producto actualizado correctamente.',
                            'estado' => true
                        ];
            
                        $data = $observaciones_json;
            
                    } catch (Exception $e) {
                        mysqli_rollback($conection);
                        $respuesta = [
                            'response' => $e->getMessage(),
                            'estado' => false
                        ];
                        $data = null;
                    }
                    break;

                    case 'servido':
                        if (empty($post['correlativo'])) {
                            $respuesta = [
                                'response' => 'Error: Parámetros inválidos',
                                'estado' => false
                            ];
                            $data = null;
                            break;
                        }

                        $correlativo = $post['correlativo'];
                                            
                        // 🔄 Actualizar todos los productos con preparar 1 o 2 a preparar = 3
                        $query_update = mysqli_query(
                            $conection,
                            "UPDATE detalle_temp 
                             SET preparar = 3 
                             WHERE correlativo = $correlativo AND preparar IN (1,2)"
                        );
                    
                        if ($query_update) {
                            $filasAfectadas = mysqli_affected_rows($conection);
                            if ($filasAfectadas == 1) {
                                $respuesta = [
                                    'response' => "Productos marcados como servidos",
                                    'estado' => true
                                ];
                                $data = null;
                            } else {
                                $respuesta = [
                                    'response' => "No hay productos pendientes para marcar como servidos",
                                    'estado' => false
                                ];
                                $data = null;
                            }
                        } else {
                            $respuesta = [
                                'response' => 'Error al actualizar los productos como servidos',
                                'estado' => false
                            ];
                            $data = null;
                        }
                        break;
                    


    default:
        $respuesta = ['response' => 'No hay accion 2', 'estado' => false];
        break;
}

echo json_encode(['respuesta' => $respuesta, 'data' => $data]);
