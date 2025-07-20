$(document).ready(function(){

$('#myTable').DataTable({
 
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.11/i18n/Spanish.json"
            },
            dom: 'Bfrtip',
           buttons: [
                    'excelHtml5',
                    {
                        extend: 'pdfHtml5',
                        title: 'Reporte',
                        exportOptions: {
                    columns: ':not(:last-child)' // Excluir la última columna
                },
                }
                ]
        });

$('.js-example-basic-single').select2();

$('.btn_menu').click(function(e){
e.preventDefault();
    if($('nav').hasClass('viewMenu'))
{
    $('nav').removeClass('viewMenu');
}else{

    $('nav').addClass('viewMenu');
}

});

$('nav ul li').click(function(){
    $('nav ul li ul').slideUp();
    $(this).children('ul').slideToggle();
});


    //--------------------- SELECCIONAR FOTO PRODUCTO ---------------------
    $("#foto").on("change",function(){
    	var uploadFoto = document.getElementById("foto").value;
        var foto       = document.getElementById("foto").files;
        var nav = window.URL || window.webkitURL;
        var contactAlert = document.getElementById('form_alert');
        
            if(uploadFoto !='')
            {
                var type = foto[0].type;
                var name = foto[0].name;
                if(type != 'image/jpeg' && type != 'image/jpg' && type != 'image/png')
                {
                    contactAlert.innerHTML = '<p class="errorArchivo">El archivo no es v�lido.</p>';                        
                    $("#img").remove();
                    $(".delPhoto").addClass('notBlock');
                    $('#foto').val('');
                    return false;
                }else{  
                        contactAlert.innerHTML='';
                        $("#img").remove();
                        $(".delPhoto").removeClass('notBlock');
                        var objeto_url = nav.createObjectURL(this.files[0]);
                        $(".prevPhoto").append("<img id='img' src="+objeto_url+">");
                        $(".upimg label").remove();
                        
                    }
              }else{
              	alert("No selecciono foto");
                $("#img").remove();
              }              
    });

    $('.delPhoto').click(function(){
    	$('#foto').val('');
    	$(".delPhoto").addClass('notBlock');
    	$("#img").remove();
    });
        
        //agregar prodcuto//
            $('.add_product').click(function(e){
                //act on the event /
                e.preventDefault();
                var producto = $(this).attr('product');
                var action = 'infoProducto';
        
        $.ajax({
                url: 'ajax.php',
                type: 'POST',
                async: true,
                data: {action:action,producto:producto}, 
               
            success: function(response){
                  //console.log(response);   
                   
                if(response != 'error'){
                 var info = JSON.parse(response);
                    //$('#producto_id').val(info.codproducto);
                   // $('.nameProducto').html(info.producto);
                    
                    $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); sendDataProduct();">'+
                '<h1><i class="fas fa-cubes" style="font-size: 45pt;"></i><br>Agregar Producto</h1>'+
                '<h2 class="nameProducto"></h2>'+
                '<h2 class="descripcion">'+info.producto+'</h2><br>'+
                '<input type="number" name="cantidad" id="txtCantidad" placeholder="Cantidad del Producto" required><br>'+
                '<input type="text" name="precio" id="txtPrecio" placeholder="Precio del Producto" required>'+
                '<input type="hidden" name="producto_id" id="producto_id" value="'+info.codproducto+'" required>'+
                '<input type="hidden" name="action" value="addProduct" required>'+
                '<div class="alert alertAddProduct"></div>'+
                '<button type="submit" class="btn_new"><i class="fas fa-plus"></i> Agregar</button>'+
                '<a href="#" class="btn_ok closeModal" onclick="closeModal();"><i class="fas fa-ban"></i> Cerrar</a>'+
            '</form>');

                }
            },
                error: function(error){
                console.log(error);
                }
            });
            
            $('.modal').fadeIn();
     });

//buscar cliente 

$('#cl_usuario').change(function(e){
    e.preventDefault();

    var cl     = $(this).val();
    var action = 'searchCliente';

    $.ajax({
            url: 'ajax.php',
            type: 'POST',
            async: true,
            data: {action:action,cliente:cl},

            success: function(response){
                console.log(response);
                if(response == 0){
                   $('#id_cliente').val('');
                   $('#nom_cliente').val('');
                   $('#ap_cliente').val('');
                   $('#cred_act').val('');
                   $('#direccion').val('');
                   $('#telefono').val('');
                   $('#correo').val('');
                   $('.btn_new_cliente').slideDown();
                }else{ 
                    
                    var data = $.parseJSON(response);
                    console.log(data);
                    
                    $('#id_cliente').val(data.usuario);
                    $('#nom_cliente').val(data.nombre);
                    $('#ap_cliente').val(data.p_apellido);
                    $('#direccion').val(data.direccion);
                    $('#telefono').val(data.telefono);
                    $('#correo').val(data.correo);
                    $('#cred_act').val(data.credito);
                    $('.btn_new_cliente').slideUp();

                    const Toast = Swal.mixin({
                      toast: true,
                      position: 'top-end',
                      showConfirmButton: false,
                      timer: 1500,
                      timerProgressBar: true,
                      didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                      }
                    })

                    Toast.fire({
                      icon: 'success',
                      title: 'Cliente Encontrado'
                    })

                }
            },

            error: function(error){
                   console.log(error);
            }
        });
    });
//buscar producto
$('#txt_cod_producto').keyup(function(e){
    e.preventDefault();

    var producto = $(this).val();
    var action = 'infoProducto';

    if(producto != '')
{
    $.ajax({
            url: 'ajax.php',
            type: 'POST',
            async: true,
            data: {action:action,producto:producto},

            success: function(response)
            {
                console.log(response);
                
                if(response != 'error')
                {
                    var info =  JSON.parse(response);   
                   $('#txt_producto').html(info.producto);
                   $('#txt_descripcion').html(info.descripcion);
                   $('#txt_existencia').html(info.existencia);
                   $('#txt_cant_producto').val('1');
                   $('#txt_precio').html(info.precio);
                   $('#txt_precio_total').html(info.precio);
                   
                   $('#txt_cant_producto').removeAttr('disabled');

                   $('#add_product_venta').slideDown();
               
                }else{ 
                    
                    $('#txt_producto').html('-');
                    $('#txt_descripcion').html('-');
                    $('#txt_existencia').html('-');
                    $('#txt_cant_producto').val('0');
                    $('#txt_precio').html('0.00');
                    $('#txt_precio_total').html('0.00');

                    $('#txt_cant_producto').attr('disabled','disabled');

                    $('#add_product_venta').slideUp();
                }
            },

            error: function(error){
                   console.log(error);
            }
        });
    
    }else{ 
                    
                    $('#txt_producto').html('-');
                    $('#txt_descripcion').html('-');
                    $('#txt_existencia').html('-');
                    $('#txt_cant_producto').val('0');
                    $('#txt_precio').html('0.00');
                    $('#txt_precio_total').html('0.00');

                    $('#txt_cant_producto').attr('disabled','disabled');

                    $('#add_product_venta').slideUp();
                }
    });

//validar cantidad multiplicacion

$('#txt_cant_producto').keyup(function(e) {
    e.preventDefault();

    // Obtener el valor ingresado y asegurarse de que sea un número
    var cantidad = parseInt($(this).val());
    var precio = parseFloat($('#txt_precio').html());
    var existencia = parseInt($('#txt_existencia').html());
    var precio_total = 0;

    // Calcular el precio total solo si la cantidad es un número válido
    if (!isNaN(cantidad) && cantidad > 0) {
        precio_total = cantidad * precio;
    }

    // Mostrar el precio total
    $('#txt_precio_total').html(precio_total.toFixed(2)); // Formatear a dos decimales

    // Verificar si la cantidad es válida y dentro de los límites permitidos
    if (cantidad < 1 || isNaN(cantidad) || cantidad > existencia) {
        $('#add_product_venta').slideUp(); // Ocultar botón si la cantidad es inválida
    } else {
        $('#add_product_venta').slideDown(); // Mostrar botón si la cantidad es válida
    }
});
// Agregar producto al detalle
$('#add_product_venta').click(function(e) {
    e.preventDefault();
    
    var cantidad = parseInt($('#txt_cant_producto').val());

    // Verificar si la cantidad es válida
    if (cantidad > 0) {
        var codproducto = $('#txt_cod_producto').val();
        var mesa = $('#id_mesa').val();
        var action = 'addProductoDetalle';

        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            async: true,
            data: { action: action, producto: codproducto, cantidad: cantidad, mesa: mesa },

            success: function(response) {
                console.log(response);

                if (response !== 'error') {
                    var info = JSON.parse(response);
                    console.log(info);

                    // Actualizar el detalle de la venta
                    $('#detalle_venta').html(info.detalle);
                    $('#detalle_totales').html(info.totales);

                    // Reiniciar los campos del formulario
                    $('#txt_cod_producto').val('');
                    $('#txt_producto').html('-');
                    $('#txt_descripcion').html('-');
                    $('#txt_existencia').html('-');
                    $('#txt_cant_producto').val('0');
                    $('#txt_precio').html('0.00');
                    $('#txt_precio_total').html('0.00');

                    $('#txt_cant_producto').attr('disabled', 'disabled');
                    $('#add_product_venta').slideUp();
                } else {
                    console.log('No se pudo agregar el producto al detalle.');
                }

                viewProcesar(); // Llamar a la función para actualizar el proceso de venta
            },

            error: function(error) {
                console.error('Error en la solicitud AJAX:', error);
            }
        });
    }
});

//anular venta
$('#btn_anular_venta').click(function(e){
e.preventDefault();
    
    var rows  = $('#detalle_venta tr').length;
    
    if(rows > 0)
        {

        var mesa        = $('#id_mesa').val();    
        if(mesa == 0){
        alert("Seleccione una mesa");
        return false;
        }



            var action = 'anularVenta';

            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                async: true,
                data: {action:action,mesa:mesa},

                success: function(response)
                {
                    console.log(response)
                    if(response != 'error')
                    {   

                        location.reload();
                    }
                },
                error: function(error){
                    console.log(error);
                }
            });
        }
});
//Prepara pedido
$('.btn_imprimir_general').click(function(e){
e.preventDefault();
    
    var mesa         = $('#id_mesa').val();
    var action       = $(this).attr('ac');
    
        if(mesa == 0){
                Swal.fire({
                          position: 'center',
                          icon: 'error',
                          title: 'Seleccione una Mesa',
                          showConfirmButton: false,
                          timer: 1000
                        })
                return false;
            }
    
    var rows  = $('#detalle_venta tr').length;
    
        if(rows > 0)
        {
            Swal.fire({
                          title: 'Cargando....',
                          html: 'Espere Porfavor',
                          allowEscapeKey: false,
                          allowOutsideClick: false,
                          didOpen: () => {
                            Swal.showLoading()
                          }
                        });

            $.ajax({
                url: 'factura/ajax.php',
                type: 'POST',
                async: true,
                data: {action:action,mesa:mesa},

                success: function(response)
                {   
                     console.log(response); 
                     //return false;
                    
                    if(response != 'error')
                    {   

                        var info =JSON.parse(response);
                        
                        Swal.close();
                        searchForDetalle(info.id);
                        console.log(info);

                    }else{
                        console.log('no data');
                    }
                },
                error: function(error){
                    console.log(error);
                }
            });
        }else{

            Swal.fire({
                          position: 'center',
                          icon: 'error',
                          title: 'Seleccione un producto',
                          showConfirmButton: false,
                          timer: 1000
                        })
                return false;

        }
    });



//Facturar venta
$('#btn_facturar_venta').click(function(e) {
    e.preventDefault();

    // Verificar si hay filas en el detalle de la venta
    var rows = $('#detalle_venta tr').length;

    if (rows > 0) {
        var action = 'procesarVenta';
        var codcliente = $('#id_cliente').val();
        var mesa = $('#id_mesa').val();

        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            async: true,
            data: { action: action, codcliente: codcliente, mesa: mesa },

            success: function(response) {
                console.log(response);

                if (response !== 'error') {
                    try {
                        var info = JSON.parse(response);
                        console.log(info);

                        // Generar el PDF del ticket
                        generarPDFticket(info.codcliente, info.nofactura);

                        // Imprimir el ticket
                        imprimirTicket(info.nofactura);

                        // Recargar la página para limpiar el formulario
                        location.reload();
                    } catch (e) {
                        console.error('Error al procesar la respuesta:', e);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Hubo un problema al procesar la venta. Por favor, intenta nuevamente.',
                        });
                    }
                } else {
                    console.log('No se recibieron datos de la venta.');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo procesar la venta. Verifica la información e intenta nuevamente.',
                    });
                }
            },
            error: function(error) {
                console.error('Error en la solicitud AJAX:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Hubo un problema al procesar la venta. Por favor, intenta nuevamente.',
                });
            }
        });
    } else {
        Swal.fire({
            icon: 'warning',
            title: 'Detalle vacío',
            text: 'No hay productos en el detalle de la venta.',
        });
    }
});


    $('.view_factura').click(function(e){
        e.preventDefault();

        var codCliente  = $(this).attr('cl');
        var nofactura   = $(this).attr('f');

        generarPDF(codCliente,nofactura);
    });



    //cambiar password

    $('.newPass').keyup(function(){
        validPass();

    });


    $('#frmChangePass').submit(function(e){
        e.preventDefault();

        var passActual = $('#txtPassUser').val();
        var passNuevo = $('#txtNewPassUser').val();
        var confirmPassNuevo = $('#txtPassConfirm').val();
        var action = "changePassword";

        if (passNuevo != confirmPassNuevo) {
            $('.alertChangePass').html('<p>Las contraseñas no son iguales</p>');
            $('.alertChangePass').slideDown();
            return false;
        }
            if (passNuevo.length < 7) {
            $('.alertChangePass').html('<p>La nueva contraseña debe ser de 6 caracteres como mínimo.</p>');
            $('.alertChangePass').slideDown();
            return false;
        }
            $.ajax({
                            url: 'ajax.php',
                            type: 'POST',
                            async: true,
                            data: {action:action,passActual:passActual,passNuevo:passNuevo},

                            success: function(response)
                            {   
                                
                                console.log(response);
                                
                                if(response != 'error')
                                {
                                    var info = JSON.parse(response);
                                    console.log(info);
                                    if(info.cod == '00'){
                                    $('.alertChangePass').html('<p style="color:green;">'+info.msg+'</p>');
                                    $('#frmChangePass')[0].reset();
                                    }else{
                                         $('.alertChangePass').html('<p style="color:red;">'+info.msg+'</p>');
                                    }
                                        $('.alertChangePass').slideDown();
                                }

                                },
                            error: function(error){
                                console.log(error);
                            }
                        });
                    });

///// final venta credito




/// hotel



    $(document).on('change', 'input[name="productos_seleccionados[]"]', calcularTotalDividir);









        const buscarProductosInput = document.getElementById("buscarProductosGrid");
        if (buscarProductosInput) {
            buscarProductosInput.addEventListener("keyup", () => {
                // Obtener el valor del campo de búsqueda y convertir a minúsculas
                let query = buscarProductosInput.value.toLowerCase();
            
                // Seleccionar todos los elementos con la clase 'productoG'
                let elements = document.querySelectorAll(".productoG");
            
                // Iterar sobre todos los elementos
                elements.forEach((element) => {
                    // Obtener el texto del elemento y convertir a minúsculas
                    let text = element.textContent.toLowerCase();
            
                    // Verificar si el texto contiene la consulta
                    if (text.includes(query) || query === "") {
                        // Mostrar el elemento si coincide
                        element.classList.remove("hidden");
                    } else {
                        // Ocultar el elemento si no coincide
                        element.classList.add("hidden");
                    }
                });
            });
        }
    
        const buscarCategoriasInput = document.getElementById("buscarCategoriasGrid");
        if (buscarCategoriasInput) {
            buscarCategoriasInput.addEventListener("keyup", () => {
                // Obtener el valor del campo de búsqueda y convertir a minúsculas
                let query = buscarCategoriasInput.value.toLowerCase();
            
                // Seleccionar todos los elementos con la clase 'categoriaG'
                let elements = document.querySelectorAll(".categoriaG");
            
                // Iterar sobre todos los elementos
                elements.forEach((element) => {
                    // Obtener el texto del elemento y convertir a minúsculas
                    let text = element.textContent.toLowerCase();
            
                    // Verificar si el texto contiene la consulta
                    if (text.includes(query) || query === "") {
                        // Mostrar el elemento si coincide
                        element.classList.remove("hidden");
                    } else {
                        // Ocultar el elemento si no coincide
                        element.classList.add("hidden");
                    }
                });
            });
        }


           // === GESTIÓN AUTOMÁTICA DE LA COLA DE CORREOS ===
    setTimeout(function () {
        procesarColaCorreos();
    }, 3000); // espera 3 segundos tras cargar la página
    


    



});  //final del ready






function procesarColaCorreos() {
    $.ajax({
        url: 'includes/plantillas/procesar_cola.php',
        method: 'GET',
        dataType: 'json',
        success: function (response) {
            console.log("✅ Cola de correos:", response);

            if (response.detalles && response.detalles.length > 0) {
                // Si hay correos pendientes, vuelve a llamar la función
                procesarColaCorreos();
            }
        },
        error: function (xhr, status, error) {
            console.error("❌ Error procesando cola:", error);
        }
    });
}

function validPass(){
    var passNuevo = $('#txtNewPassUser').val();
    var confirmPassNuevo =$('#txtPassConfirm').val();

    if (passNuevo != confirmPassNuevo) {
        $('.alertChangePass').html('<p>Las contraseñas no son iguales</p>');
        $('.alertChangePass').slideDown();
        return false;
    }
    if (passNuevo.length < 7) {
        $('.alertChangePass').html('<p>La nueva contraseña debe ser de 6 caracteres como mínimo.</p>');
        $('.alertChangePass').slideDown();
        return false;



}
        $('.alertChangePass').html('');
        $('.alertChangePass').slideUp();
}

function generarPDF(cliente,factura){

    var ancho = 1000;
    var alto  = 800;

    var x = parseInt((window.screen.width/2) - (ancho / 2));
    var y = parseInt((window.screen.heigth/2) -  (alto / 2));

    $url = 'factura/factura.php?cl='+cliente+'&f='+factura;
    window.open($url,"Factura","left="+x+",top="+y+",height="+alto+",width="+ancho+",scrollbar=si,location=no,resizable=no,menubar=no");
}

function imprimirTodo(cliente,factura,nombre){

    var action = 'imprimirTodo';

     $.ajax({
                            url: 'factura/ajax.php',
                            type: 'POST',
                            async: true,
                            data: {action:action,cl:cliente,f:factura,nC:nombre},

                            success: function(response)
                            {   
                                
                                console.log(response);
                                
                                },
                            error: function(error){
                                console.log(error);
                            }
                        });
    
    }

function imprimirFactura(cliente,factura,nombre){

        var action = 'imprimirFactura';
    
         $.ajax({
                                url: 'factura/ajax.php',
                                type: 'POST',
                                async: true,
                                data: {action:action,cl:cliente,f:factura,nC:nombre},
    
                                success: function(response)
                                {   
                                    
                                    console.log(response);
                                    
                                    },
                                error: function(error){
                                    console.log(error);
                                }
                            });
        
        }
function imprimirComanda(cliente,factura,nombre){

            var action = 'imprimirComanda';
        
             $.ajax({
                                    url: 'factura/ajax.php',
                                    type: 'POST',
                                    async: true,
                                    data: {action:action,cl:cliente,f:factura,nC:nombre},
        
                                    success: function(response)
                                    {   
                                        
                                        console.log(response);
                                        
                                        },
                                    error: function(error){
                                        console.log(error);
                                    }
                                });
            
            }

function generarPDFticket(cliente,factura){

    var ancho = 1000;
    var alto  = 800;

    var x = parseInt((window.screen.width/2) - (ancho / 2));
    var y = parseInt((window.screen.heigth/2) -  (alto / 2));

    $url = 'factura/ticket.php?cl='+cliente+'&f='+factura;
    window.open($url,"Factura","left="+x+",top="+y+",height="+alto+",width="+ancho+",scrollbar=si,location=no,resizable=no,menubar=no");
}

function generarPDFticketPrevio(co,user,mesa){

    var ancho = 1000;
    var alto  = 800;

    var x = parseInt((window.screen.width/2) - (ancho / 2));
    var y = parseInt((window.screen.heigth/2) -  (alto / 2));

    $url = 'factura/ticketNopago.php?co='+co+'&u='+user+'&m='+mesa;
    window.open($url,"Factura","left="+x+",top="+y+",height="+alto+",width="+ancho+",scrollbar=si,location=no,resizable=no,menubar=no");
}

function abrirModoVentas() {
    var ancho = screen.width;
    var alto = screen.height;

    var url = 'nueva_venta.php';

    // Abrir la ventana en modo maximizado
    var ventana = window.open(
        url,
        "Modulo de Ventas",
        "scrollbars=no,location=no,resizable=no,menubar=no,status=no,titlebar=no,toolbar=no"
    );

    // Si la ventana fue bloqueada, mostrar un mensaje al usuario
    if (!ventana || ventana.closed || typeof ventana.closed === 'undefined') {
        alert('Por favor, permita las ventanas emergentes para utilizar esta función.');
        return;
    }

    // Forzar la ventana a ocupar toda la pantalla
    ventana.moveTo(0, 0);
    ventana.resizeTo(ancho, alto);

    // Intentar poner la ventana en pantalla completa
    ventana.addEventListener('load', function() {
        if (ventana.document.documentElement.requestFullscreen) {
            ventana.document.documentElement.requestFullscreen().catch((err) => {
                console.warn(`Error al intentar activar el modo pantalla completa: ${err.message}`);
            });
        } else {
            console.warn('El navegador no soporta el modo pantalla completa.');
        }

        // Bloquear ciertas combinaciones de teclas
        ventana.document.addEventListener('keydown', function(event) {
            if (event.key === 'F11' || (event.ctrlKey && event.key.toLowerCase() === 'l')) {
                event.preventDefault();
            }
        });

        // Prevenir el cierre accidental de la ventana o la modificación de la URL
        ventana.addEventListener('beforeunload', function(event) {
            event.preventDefault();
            event.returnValue = '¿Seguro que deseas salir?';
        });

        // Forzar el foco en la nueva ventana
        ventana.focus();
    });
}



function del_product_detalle(correlativo) {

    var action = 'del_product_detalle';
    var id_detalle = correlativo;
    var mesa = $('#id_mesa').val();

    // Verificar si se ha seleccionado una mesa
    if (!mesa || mesa == 0) {
        alert('Seleccione una mesa');
        return false;
    }

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: action, id_detalle: id_detalle, mesa: mesa },

        success: function(response) {
            console.log(response);
            if (response != 'error') {
                try {
                    var info = JSON.parse(response);

                    // Actualizar los detalles de la venta
                    $('#detalle_venta').html(info.detalle);
                    $('#detalle_totales').html(info.totales);
                    $('#id_precioFinal').val(info.preciofinal);

                    // Restablecer el formulario de producto
                    $('#txt_cod_producto').val('');
                    $('#txt_descripcion').html('-');
                    $('#txt_existencia').html('-');
                    $('#txt_cant_producto').val('0');
                    $('#txt_precio').html('0.00');
                    $('#txt_precio_total').html('0.00');
                    $('#txt_cant_producto').attr('disabled', 'disabled');

                    $('#add_product_venta').slideUp();

                } catch (e) {
                    console.log(response);
                    console.error("Error al procesar la respuesta:", e);
                    $('#detalle_venta').html('');
                    $('#detalle_totales').html('');
                    $('#id_precioFinal').val('');
                }
            } else {
                // Limpiar detalles si no hay respuesta válida
                $('#detalle_venta').html('');
                $('#detalle_totales').html('');
                $('#id_precioFinal').val('');
            }
            viewProcesar();
        },

        error: function(error) {
            console.error("Error en la solicitud AJAX:", error);
        }
    });
}
function viewProcesar(){
    if($('#detalle_venta tr').length > 0)
     {
     
        $('#btn_facturar_venta_1').show();
        $('.imprimir_todo').show();
        $('#btn_anular_venta').show();
        
        }else{
        $('#btn_facturar_venta_1').hide();
        $('.imprimir_todo').hide();
        $('#btn_anular_venta').hide();

    }

}
 function viewProcesarCred(){
    if($('#detalle_venta_credito tr').length == 1)
     {
     
        $('#btn_facturar_venta_credito').show();
        }else{
        $('#btn_facturar_venta_credito').hide();
    }

}
function searchForDetalle(id,idmesa) {
    var action = 'searchForDetalle';
    var user = id;
    var mesa = idmesa || $('#id_mesa').val(); 

    console.log(mesa);

    if (!mesa || mesa == 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'Seleccione una mesa',
            confirmButtonText: 'Aceptar'
        });
        return false;
    }

    // Mostrar mensaje de carga
    Swal.fire({
        title: 'Cargando...',
        html: 'Espere por favor',
        allowEscapeKey: false,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: action, user: user, mesa: mesa },
        success: function(response) {
            console.log(response);
            Swal.close(); // Cerrar el loader al recibir respuesta

            if (response !== 'error') {
                try {
                    var info = JSON.parse(response);

                    $('#detalle_venta').html(info.detalle || '');
                    $('#detalle_totales').html(info.totales || '');
                    $('#id_precioFinal').val(info.preciofinal || 0);
                    $('#id_mesa').val(info.mesa || '');

                } catch (e) {
                    console.error("Error al procesar la respuesta JSON:", e);
                    limpiarDetalles();
                }
            } else {
                limpiarDetalles();
                console.log('No hay datos disponibles');
                $('#id_mesa').val(mesa);
            }
            viewProcesar();
        },
        error: function(error) {
            console.error("Error en la solicitud AJAX:", error.responseText || error);
            Swal.close(); // Cerrar el loader también en error
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo consultar el detalle de la venta',
                confirmButtonText: 'Aceptar'
            });
        }
    });
}

// Función auxiliar para limpiar los detalles de la venta
function limpiarDetalles() {
    $('#detalle_venta').html('');
    $('#detalle_totales').html('');
    $('#id_precioFinal').val('');
    $('#id_mesa').val('');
}


function sendDataProduct(){

        $('.alertAddProduct').html('');

        $.ajax({

            url: 'ajax.php',
            type: "POST",
            async: true,
            data: $('#form_add_product').serialize(), 
                           
            success: function(response){
                //console.log(response);
                 if(response == 'error')
                {
                    $('.alertAddProduct').html('<p style="color: red;">Error al agregar el Producto</p>');
                }else{
                    var info = JSON.parse(response);
                    $('.row'+info.producto_id+' .celPrecio').html(info.nuevo_precio);
                    $('.row'+info.producto_id+' .celExistencia').html(info.nueva_existencia);
                    $('#txtCantidad').val('');
                    $('#txtPrecio').val('');
                   $('.alertAddProduct').html('<p>Producto agregado corectamente</p>');
               }
                            
            },
            error: function(error){
                 console.log(error);
            }
            });
        }

function clearFormFields() {
            $('.alertAddProduct').html('');
            $('#txtCantidad').val('');
            $('#txtPrecio').val('');
        }
        
        function closeModal() {
            clearFormFields();
            $('.bodyModal').html('');
            $('.modal').fadeOut();
        }
        
        function closeModal2() {
            clearFormFields();
            $('.bodyModal2').html('');
            $('.modal2').fadeOut();
            $('.modal').fadeOut(); // Asegura que ambas modales se cierren
        }
        
        function closeModal3() {
            clearFormFields();
            $('.bodyModal3').html('');
            $('.modal3').fadeOut();
        }

        function closeModal4() {
            clearFormFields();
            $('.bodyModal4').html('');
            $('.modal4').fadeOut();
        }


        function addproduct(code) {
            var codproducto = code;
            var mesa = $('#id_mesa').val();
            var cantidad = 1;
            var action = 'addProductoDetalle';
        
            // Verificar si se ha seleccionado una mesa
            if (!mesa || mesa == 0) {
                Swal.fire({
                    position: 'center',
                    icon: 'error',
                    title: 'Seleccione una Mesa',
                    showConfirmButton: false,
                    timer: 1000
                });
                return false;
            }
        
            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                async: true,
                data: { action: action, producto: codproducto, cantidad: cantidad, mesa: mesa },
        
                success: function(response) {
                    console.log(response);
        
                    if (response != 'error') {
                        try {
                            var info = JSON.parse(response);
        
                            // Actualizar los detalles de la venta
                            $('#detalle_venta').html(info.detalle);
                            $('#detalle_totales').html(info.totales);
                            $('#id_precioFinal').val(info.preciofinal);
        
                            // Restablecer el formulario de producto
                            resetProductForm();
        
                        } catch (e) {
                            console.error("Error al procesar la respuesta JSON:", e);
                        }
                    } else {
                        console.log('No se encontraron datos.');
                    }
        
                    viewProcesar();
                },
        
                error: function(error) {
                    console.error("Error en la solicitud AJAX:", error);
                }
            });
        }
        
        // Función auxiliar para restablecer el formulario de producto
        function resetProductForm() {
            $('#txt_cod_producto').val('');
            $('#txt_producto').html('-');
            $('#txt_descripcion').html('-');
            $('#txt_existencia').html('-');
            $('#txt_cant_producto').val('0');
            $('#txt_precio').html('0.00');
            $('#txt_precio_total').html('0.00');
            $('#txt_cant_producto').attr('disabled', 'disabled');
            $('#add_product_venta').slideUp();
        }

function selectCategorias(code) {

    var action      = 'addProductoTabla';
    
    $.ajax({
            url: 'ajax.php',
            type: 'POST',
            async: true,
            data: {action:action,code:code},

            success: function(response)
            {
               console.log(response);

                if(response != 'error')
                {   
                    var info =JSON.parse(response);
                    console.log(info);
                    $('.categoriaProd').html(info.detalle);
                    //setupProductSearch();
                    }else{
                        $('.categoriaProd').html('');
                 //console.log('no data');   
                }   

             
            },

            error: function(error){
                   console.log(error);
            }
        });
}

function todasCategorias() {

    var action      = 'addProductosTabla';
    
    $.ajax({
            url: 'ajax.php',
            type: 'POST',
            async: true,
            data: {action:action},

            success: function(response)
            {
               console.log(response);

                if(response != 'error')
                {   
                    var info =JSON.parse(response);
                    //console.log(info);
                    $('.categoriaProd').html(info.detalle);
                    }else{
                        $('.categoriaProd').html('');
                 //console.log('no data');   
                }   

             
            },

            error: function(error){
                   console.log(error);
            }
        });
}

function sendDataForm() {
    $('.alertAddProduct').html('');

    var formData = new FormData(document.getElementById("form_add_product"));

    $.ajax({
        url: "ajax.php",
        type: "post",
        dataType: "html",
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        success: function(response) {
            console.log(response);
            handleResponse(response);
        },
        error: function(error) {
            console.error("Error en la solicitud AJAX:", error);
            Swal.fire({
                position: 'top-end',
                icon: 'error',
                title: 'Error en la solicitud',
                showConfirmButton: false,
                timer: 1500
            });
        }
    });
}

function handleResponse(response) {
    switch (response) {
        case '1':
            showAlert('error', 'Complete todos los datos');
            break;
        case '2':
            showAlert('error', 'Registrado Anteriormente.');
            break;
        case '3':
            showAlert('error', 'Error');
            break;
        case '4':
            showAlert('error', 'Caja no está disponible para abrir');
            break;
        case '6':
            showAlert('error', 'Todas las cajas abiertas');
            break;
        case 'Ok':
            showSuccessAlert('Relizado Correctamente', 'Se ha relizado Correctamente', function() {
                location.reload();
            });
        break;
            case '10':
                 showSuccessAlert('Relizado Correctamente', 'Se ha relizado Correctamente', function() {
                location.reload();
            });
        break;
        default:
            try {
                var info = JSON.parse(response);
                showSuccessAlert('Creado Correctamente', 'Se ha Actualizado Correctamente', function() {
                    location.reload();
                });
                resetForm(info);
            } catch (e) {
                console.log(response);
                console.error("Error al procesar la respuesta JSON:", e);
                showAlert('error', 'Error inesperado');
            }
            break;
    }
}

function showAlert(type, message) {
    Swal.fire({
        position: 'top-end',
        icon: type,
        title: message,
        showConfirmButton: false,
        timer: 1000
    });
}

function showSuccessAlert(title, text, callback) {
    Swal.fire({
        icon: 'success',
        title: title,
        text: text,
        showConfirmButton: true,
        confirmButtonText: 'Ok',
        allowOutsideClick: false
    }).then((result) => {
        if (result.isConfirmed && typeof callback === 'function') {
            callback();
        }
    });
}

function resetForm(info) {
    $('#form_add_product')[0].reset();
    $('.modal').fadeOut();
    $('.cl_usuario').val(info.cedula);
    $('.nombre').val(info.nombre);
    $('.p_apellido').val(info.p_apellid);
}

function sendDataFormImprimir() {


    $('.alertAddProduct').html('');

    var formData = new FormData(document.getElementById("form_add_product"));
    $.ajax({
        url: "factura/ajax.php",
        type: "post",
        dataType: "html",
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        success: function(response) {
            console.log(response);
            var info = JSON.parse(response);
            if (info.code == 1) {
           Swal.fire({
                position: 'top-end',
                icon: 'success',
                title: 'Realizado Correctamente',
                showConfirmButton: false,
                timer: 1500
            });
           searchForDetalle(info.user);

            closeModal2();
            }
            
        },
        error: function(error) {
            console.error("Error en la solicitud AJAX:", error);
            Swal.fire({
                position: 'top-end',
                icon: 'error',
                title: 'Error en la solicitud',
                showConfirmButton: false,
                timer: 1500
            });
        }
    });
}

function handleResponseImprimir(response) {
    switch (response) {
        case '1':
            showAlert('error', 'Complete todos los datos');
            break;
        case '2':
            showAlert('error', 'Registrado Anteriormente.');
            break;
        case '3':
            showAlert('error', 'Error');
            break;
        case '6':
            showAlert('error', 'Todas las cajas abiertas');
            break;
        default:
            try {
                var info = JSON.parse(response);
                showSuccessAlert('Realizado Correctamente', 'Se ha Actualizado Correctamente', function() {
                    location.reload();
                });
                resetForm(info);
            } catch (e) {
                console.error("Error al procesar la respuesta JSON:", e);
                showAlert('error', 'Error inesperado');
            }
            break;
    }
}

function sendDataForm2() {
    $('.alertAddProduct').html('');

    var formData = new FormData(document.getElementById("form_add_product"));
    $.ajax({
        url: "ajax.php",
        type: "post",
        dataType: "html",
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        success: function(response) {
            //console.log(response);
            handleResponseForm2(response);
        },
        error: function(error) {
            console.error("Error en la solicitud AJAX:", error);
            Swal.fire({
                position: 'top-end',
                icon: 'error',
                title: 'Error en la solicitud',
                showConfirmButton: false,
                timer: 1500
            });
        }
    });
}

function handleResponseForm2(response) {
    if (response == '1') {
        showAlert('error', 'Complete todos los datos');
    } else if (response.length == 2) {
        showAlert('error', 'Registrado Anteriormente.');
    } else {
        try {
            var info = JSON.parse(response);
            if (info.code == 3) {
                searchForDetalle(info.user);
            }

            $('.modal').fadeOut();
            $('.bodyModal').html('');
            $('.modal2').fadeOut();
            $('.bodyModal2').html('');

            Swal.fire({
                position: 'center',
                icon: 'success',
                title: 'Realizado Correctamente',
                showConfirmButton: false,
                timer: 1000
            });
        } catch (e) {
            console.error("Error al procesar la respuesta JSON:", e);
            showAlert('error', 'Error inesperado');
        }
    }
}

function codigoPromocional() {

            // Limpiar el mensaje de cortesía
            $('#descripcionCortersia').html('');
        
            // Obtener valores
            var action = 'codigoPromocional';
            var codigo = $('#cupon').val().trim();
            var total = $('#total').html().trim();
        
            // Verificar que el código no esté vacío
            if (codigo === '') {
                $('#descripcionCortersia').html('Ingrese un Código');
                return;
            }
        
            // Realizar la solicitud AJAX para validar el código promocional
            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                async: true,
                data: {
                    action: action,
                    codigo: codigo,
                    total: total
                }, 
                success: function(response) {
                    console.log(response);
        
                    // Manejo de errores en la respuesta
                    if (response === 'error') {
                        $('#descripcionCortersia').html('Ingrese un Código');
                    } else if (response == 3) {
                        $('#descripcionCortersia').html('Código no Vigente');
                        $('#cupon').val('');
                    } else {
                        try {
                            var info = JSON.parse(response);
                            
                            // Actualizar la UI con la información del cupón
                            $('#cupon').attr('disabled', 'disabled');
                            $('#id_cupon').val(info.id_cupon);
                            $('#descripcionCortersia').html(info.descripcion);
                            $('#descuento').html(info.descuento);
                            $('#total').html(info.total);
                            $('#codigoPromocional').val(info.codigo);
                            $('.btn_aplicar').slideUp();
                        } catch (error) {
                            console.error('Error al procesar la respuesta JSON:', error);
                            $('#descripcionCortersia').html('Error al aplicar el código');
                        }
                    }
                },
                error: function(error) {
                    console.error('Error en la solicitud AJAX:', error);
                    $('#descripcionCortersia').html('Error al procesar la solicitud');
                }
            });
        }
        

// Objeto global para llevar el control de advertencias por mesa
var correoWarnings = correoWarnings || {};

function facturarVenta() {
    // Mostrar mensaje de carga
    Swal.fire({
        title: 'Cargando...',
        html: 'Espere por favor',
        allowEscapeKey: false,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    var numeroMesa = $('#id_mesa').length ? $('#id_mesa').val() : 0;
    var nombreCliente = $('#nombreCliente').length ? $('#nombreCliente').val().trim() : '';

    if (nombreCliente === '') {
        Swal.close();
        Swal.fire({
            position: 'center',
            icon: 'error',
            title: 'Ingrese el nombre del cliente',
            showConfirmButton: false,
            timer: 1500
        });
        return false;
    }

    // Validar correo marketing de forma segura
    var correoMarketing = '';

    if ($('#correoMarketing').length) {
        correoMarketing = $('#correoMarketing').val() || '';
        correoMarketing = correoMarketing.trim();
    }

    if (correoMarketing !== '') {
        // Regex robusto para correos válidos
        var emailRegex = /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/;
        if (!emailRegex.test(correoMarketing)) {
            Swal.close();
            Swal.fire({
                position: 'center',
                icon: 'error',
                title: 'El correo ingresado no es válido',
                showConfirmButton: false,
                timer: 2000
            });
            return false;
        }
    } else {
        if (!correoWarnings[numeroMesa]) {
            correoWarnings[numeroMesa] = true;
            Swal.close();
            Swal.fire({
                position: 'center',
                icon: 'warning',
                title: 'No ingresó el correo de marketing. Debe ingresarlo o continuar sin él.',
                showConfirmButton: false,
                timer: 2000
            });
            return false;
        }
        // Segunda vez en esta mesa: continúa sin mostrar alerta
    }

    var pago = parseInt($("input[type=radio][name=pago]:checked").val()) || 0;
    var codigoTarjeta = $('#codigoTarjeta').length ? $('#codigoTarjeta').val().trim() : '';
    var codigoTransferencia = $('#codigoTransferencia').length ? $('#codigoTransferencia').val().trim() : '';

    if (pago !== 1) {
        if (pago === 2 && codigoTarjeta === '') {
            Swal.close();
            Swal.fire({
                position: 'center',
                icon: 'error',
                title: 'Ingrese la referencia de la tarjeta',
                showConfirmButton: false,
                timer: 1500
            });
            return false;
        }

        if ((pago === 3 || pago === 4) && codigoTransferencia === '') {
            Swal.close();
            Swal.fire({
                position: 'center',
                icon: 'error',
                title: 'Ingrese el número de documento de la transferencia',
                showConfirmButton: false,
                timer: 1500
            });
            return false;
        }
    }

    var rows = $('#detalle_venta').length ? $('#detalle_venta tr').length : 0;
    if (rows < 1) {
        Swal.close();
        Swal.fire({
            position: 'center',
            icon: 'error',
            title: 'No hay productos en la venta',
            showConfirmButton: false,
            timer: 1500
        });
        return false;
    }

    var action = 'procesarVenta';
    var codcliente = $('#id_cliente').length ? $('#id_cliente').val() : 1;
    var cupon = $('#id_cupon').length ? $('#id_cupon').val() : 0;
    var caja = $('#id_caja').length ? $('#id_caja').val() : 0;
    var factura = $('#facturaImpresa').length && $('#facturaImpresa').is(':checked') ? 1 : 2;
    var comandas = $('#comandasImpresa').length && $('#comandasImpresa').is(':checked') ? 1 : 2;

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: {
            action: action,
            codcliente: codcliente,
            mesa: numeroMesa,
            cupon: cupon,
            pago: pago,
            codigoTarjeta: codigoTarjeta,
            codigoTransferencia: codigoTransferencia,
            caja: caja,
            factura: factura,
            comandas: comandas,
            correoMarketing: correoMarketing,
            nombreCliente: nombreCliente
        },
        success: function(response) {
            console.log(response);
            try {
                var info = JSON.parse(response);
                console.log(info);

                if (info.venta) {
                    if (info.factura == 1 && info.comandas == 1) {
                        imprimirTodo(info.cod_cliente, info.no_factura, nombreCliente);
                    } else if (info.factura == 1) {
                        imprimirFactura(info.cod_cliente, info.no_factura, nombreCliente);
                    } else if (info.comandas == 1) {
                        imprimirComanda(info.cod_cliente, info.no_factura, nombreCliente);
                    }

                    Swal.fire({
                        position: 'center',
                        icon: 'success',
                        title: 'Realizado Correctamente',
                        showConfirmButton: false,
                        timer: 1000
                    });

                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                }
            } catch (error) {
                console.error('Error al procesar la respuesta:', error);
                console.log('Respuesta recibida:', response);
                Swal.fire({
                    position: 'center',
                    icon: 'error',
                    title: 'Error en el procesamiento de la respuesta',
                    showConfirmButton: true
                });
            }
        },
        error: function(error) {
            console.error('Error en la solicitud AJAX:', error.responseText || error);
            Swal.fire({
                position: 'center',
                icon: 'error',
                title: 'Error al procesar la venta',
                showConfirmButton: true
            });
        }
    });
}


// Función para limpiar el estado de la mesa
function salirDeMesa(numeroMesa) {
    delete correoWarnings[numeroMesa];
}



function seleccionarPago(valor) {
            // Ocultar todos los elementos por defecto
            $("#Tarjeta, #Transferencia").hide();
            $(".divDescuento").hide();
        
            if (valor == 2) {
                // Mostrar solo el campo de tarjeta
                $("#Tarjeta").show();
            } else if (valor == 3) {
                // Mostrar solo el campo de transferencia y el descuento
                $("#Transferencia, .divDescuento").show();
            } else if (valor == 4) {
                // Mostrar solo el descuento para "DeUna"
                $("#Transferencia, .divDescuento").show();
            } else {
                // Si el valor no es 2, 3 o 4, solo mostrar el descuento
                $(".divDescuento").show();
            }
        }

        function seleccionarPago2(valor) {
            // Ocultar todos los elementos por defecto
            $("#Tarjeta, #Transferencia").hide();
            $(".divDescuento").hide();
        
            if (valor == 2) {
                // Mostrar solo el campo de tarjeta
                $("#Tarjeta").show();
            } else if (valor == 3) {
                // Mostrar solo el campo de transferencia y el descuento
                $("#Transferencia, .divDescuento").show();
            } else if (valor == 4) {
                // Mostrar solo el descuento para "DeUna"
                $(".divDescuento").show();
            } else {
                // Si el valor no es 2, 3 o 4, solo mostrar el descuento
                $(".divDescuento").show();
            }
        }

        function anadirForm2(action, co) {

            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                async: true,
                data: { action: action, co: co },
                success: function(response) {
        
                    console.log(response);
        
                    // Mostrar el contenido de la respuesta en el modal
                    $('.bodyModal2').html(response);
                    $('.modal2').fadeIn();
                    $('#codigoBarras').focus();
                    
                    if (response.includes('form_dividir_cuentas')) {
                    $('.bodyModal').html('');
                    $('.modal').fadeOut();
                    }

                    // Inicializar DataTables para la tabla de arqueo
                    $('#myTableArqueo').DataTable();
        
                    
                    if (response == 6) {
                        $('.modal2').fadeOut();
                        Swal.fire({
                            position: 'top-end',
                            icon: 'error',
                            title: 'Todas las cajas están abiertas',
                            showConfirmButton: false,
                            timer: 1500
                        });
                        return;
                    }
        
                    // Inicializar DataTables para la tabla de ventas con configuraciones adicionales
                    $('#myTableVentas').DataTable({
                        language: {
                            url: "https://cdn.datatables.net/plug-ins/1.10.11/i18n/Spanish.json"
                        },
                        dom: 'Bfrtip',
                        buttons: [
                            {
                                extend: 'excelHtml5',
                                text: 'Exportar a Excel',
                            },
                            {
                                extend: 'pdfHtml5',
                                text: 'Exportar a PDF',
                            }
                        ]
                    });
                },
                error: function(xhr, status, error) {
                    console.error('Error en la petición AJAX:', error);
                    Swal.fire({
                        position: 'center',
                        icon: 'error',
                        title: 'Hubo un error al cargar los datos.',
                        showConfirmButton: true,
                    });
                }
            });
        }

    function anadirForm(action,co){

        $.ajax({
            url:'ajax.php',
            type:'POST',
            async: true,
            data: {action:action,co:co},
            
            success: function(response) {
                
                 console.log(response);

                 $('.bodyModal').html(response);
                 $('.modal').fadeIn();
             
      
            },
         });
    };

 function anadirForm3(action,co){

        $.ajax({
            url:'ajax.php',
            type:'POST',
            async: true,
            data: {action:action,co:co},
            
            success: function(response) {
                
                 console.log(response);

                 $('.bodyModal3').html(response);
                 $('.modal3').fadeIn();
             
      
            },
         });
    };


    function imprimirTicket(co){

    const urlPdf = co+".pdf";
    const nombreImpresora = "comandas";
    const url = `http://localhost:8080/?nombrePdf=${urlPdf}&impresora=${nombreImpresora}`;
    fetch(url);
    console.log(url);

    }

    function imprimirTicketPrevio(co,mesa){

    const urlPdf = co+"-"+mesa+".pdf";
    const nombreImpresora = "comandas";
    const url = `http://localhost:8080/?nombrePdf=${urlPdf}&impresora=${nombreImpresora}`;
    fetch(url);
    console.log(url);

    }

    function calcular() {
    const input1 = document.getElementById('monto_efectivo');
    const input2 = document.getElementById('monto_tarjeta');
    const input3 = document.getElementById('monto_transferencia');
    const input4 = document.getElementById('monto_deuna');
    const resultado = document.getElementById('monto_final');
    const resultado2 = document.getElementById('monto_final2');

    if (input1 && input2 && input3 && input4 && resultado) {
        const valor1 = parseFloat(input1.value) || 0;
        const valor2 = parseFloat(input2.value) || 0;
        const valor3 = parseFloat(input3.value) || 0;
        const valor4 = parseFloat(input4.value) || 0;

        const sumaTotal = valor1 + valor2 + valor3 + valor4;
        resultado.value = sumaTotal.toFixed(2);
        if (resultado2) resultado2.value = sumaTotal.toFixed(2);
    }

    calcular3(); // Ejecuta también cálculo de efectivo neto al cambiar montos entregados
}

function calcular3() {
    const totalEntregado = parseFloat(document.getElementById('monto_efectivo')?.value) || 0;

    const empleado2 = parseFloat(document.getElementById('empleado_cristina')?.value) || 0;
    const empleado3 = parseFloat(document.getElementById('empleado_patricia')?.value) || 0;

    const totalPagadoPersonal = empleado2 + empleado3;
    const efectivoNeto = totalEntregado - totalPagadoPersonal;

    const output = document.getElementById('efectivo_neto');
    if (output) output.value = efectivoNeto.toFixed(2);
}



function calcular2() {

    const input1 = document.getElementById('entrega');
    const input2 = document.getElementById('totalCalcular');

    const resultado = document.getElementById('cambio');

    const valor1 = parseFloat(input1.value) || 0;
    const valor2 = parseFloat(input2.value) || 0;

    
    const sumaTotal = valor2 - valor1;
    resultado.innerHTML = "$ " + Math.abs(sumaTotal).toFixed(2);
    console.log(sumaTotal);

}
function procesarDivisionCuenta () {
    // Mostrar mensaje de carga con SweetAlert
    Swal.fire({
        title: 'Cargando...',
        html: 'Espere por favor',
        allowEscapeKey: false,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Verificar si hay productos seleccionados para dividir
    var productosSeleccionados = [];
    $('input[name="productos_seleccionados[]"]:checked').each(function() {
        productosSeleccionados.push($(this).val());
    });

    if (productosSeleccionados.length === 0) {
        Swal.close();
        Swal.fire({
            position: 'center',
            icon: 'error',
            title: 'Seleccione al menos un producto para dividir la cuenta',
            showConfirmButton: false,
            timer: 1500
        });
        return false;
    }

    // Obtener los datos necesarios para dividir la cuenta
    var action = 'procesarDivisionCuenta';
    var mesa = $('#id_mesa').val() || 'error';
    var codcliente = $('#id_cliente').val() || 1;
    var codcliente2 = $('#cedula_cliente').val() || 1;
    var cupon = $('#id_cupon').val() || 0;
    var pago = $("input[type=radio][name=pago]:checked").val() || 0;
    var codigoTarjeta = $('#codigoTarjeta').val().trim() || '';
    var codigoTransferencia = $('#codigoTransferencia').val().trim() || '';
    var caja = $('#id_caja').val() || 0;
    var imprimir = $('#imprimir_factura').prop('checked') ? 1 : 0;

    // Realizar la solicitud AJAX para procesar la división de la cuenta
    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: {
            action: action,
            codcliente: codcliente,
            codCliente2: codcliente2,
            mesa: mesa,
            cupon: cupon,
            pago: pago,
            codigoTarjeta: codigoTarjeta,
            codigoTransferencia: codigoTransferencia,
            caja: caja,
            productos: productosSeleccionados,
            imprimir: imprimir // Enviar productos seleccionados para la nueva factura
        },
        success: function(response) {
            console.log(response);
            try {
                var info = JSON.parse(response);

                console.log(response);

                if (info.status === 'success') {
                    Swal.fire({
                        position: 'center',
                        icon: 'success',
                        title: 'División realizada correctamente',
                        showConfirmButton: false,
                        timer: 1000
                    });
                    Swal.fire({
                        title: 'Cargando...',
                        html: 'Espere por favor',
                        allowEscapeKey: false,
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                   imprimirFactura(info.cliente, info.factura, 'ClienteFinal');


                    setTimeout(function() {
                        location.reload(); // Recargar la página para reflejar los cambios
                    }, 1000);
                } else {
                    Swal.fire({
                        position: 'center',
                        icon: 'error',
                        title: info.message || 'Error al dividir la cuenta',
                        showConfirmButton: true
                    });
                }
            } catch (error) {
                console.error('Error al procesar la respuesta:', error);
                console.log(response);
                Swal.fire({
                    position: 'center',
                    icon: 'error',
                    title: 'Error en el procesamiento de la respuesta',
                    showConfirmButton: true
                });
            }
        },
        error: function(error) {
            console.error('Error en la solicitud AJAX:', error);
            console.log(response);
            Swal.fire({
                position: 'center',
                icon: 'error',
                title: 'Error al dividir la cuenta',
                showConfirmButton: true
            });
        }
    });
}



function buscarCliente(){

    var cl     = $('#cedula_cliente').val();
    var action = 'searchCliente';

    $.ajax({
            url: 'ajax.php',
            type: 'POST',
            async: true,
            data: {action:action,cliente:cl},

            success: function(response){
                console.log(response);
                if(response == 0){
                   $('#id_cliente').val('');
                   $('#nom_cliente').val('');
                   $('#ap_cliente').val('');
                   $('#cred_act').val('');
                   $('#direccion').val('');
                   $('#telefono').val('');
                   $('#correo').val('');
                   $('.btn_new_cliente').slideDown();
                }else{ 
                    
                    var data = $.parseJSON(response);
                    console.log(data);
                    
                    $('#id_cliente').val(data.usuario);
                    $('#nom_cliente').val(data.nombre);
                    $('#ap_cliente').val(data.p_apellido);
                    $('#direccion').val(data.direccion);
                    $('#telefono').val(data.telefono);
                    $('#correo').val(data.correo);
                    $('#cred_act').val(data.credito);
                    $('.btn_new_cliente').slideUp();

                    const Toast = Swal.mixin({
                      toast: true,
                      position: 'top-end',
                      showConfirmButton: false,
                      timer: 1500,
                      timerProgressBar: true,
                      didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                      }
                    })

                    Toast.fire({
                      icon: 'success',
                      title: 'Cliente Encontrado'
                    })

                }
            },

            error: function(error){
                   console.log(error);
            }
        });

}

// Función para calcular el total de los productos seleccionados
function calcularTotalDividir() {
    let total = 0;
    console.log('Ejecutando calcularTotalDividir');

    // Recorrer todos los checkboxes de los productos seleccionados
    document.querySelectorAll('input[name="productos_seleccionados[]"]:checked').forEach(function(checkbox) {
        // Obtener el precio del producto desde un atributo de datos (data-precio)
        let precio = parseFloat(checkbox.getAttribute('data-precio'));
        let cantidad = parseInt(checkbox.getAttribute('data-cantidad'));

        // Mensajes de depuración
        console.log('Precio:', precio);
        console.log('Cantidad:', cantidad);

        // Verificar si precio y cantidad son números válidos
        if (!isNaN(precio) && !isNaN(cantidad)) {
            // Sumar al total el precio del producto multiplicado por la cantidad
            total += precio * cantidad;
        } else {
            console.error('Precio o cantidad no son números válidos:', precio, cantidad);
        }
    });

    // Actualizar el valor del total en el HTML
    console.log('Total calculado:', total);
    if (document.getElementById('totalDividir')) {
        document.getElementById('totalDividir').textContent = "$ " + total.toFixed(2);
    }
    if (document.getElementById('totalDividirCalcular')) {
        document.getElementById('totalDividirCalcular').value = total.toFixed(2);
    }
}


function esJson(cadena) {
  try {
    JSON.parse(cadena);
    return true;
  } catch {
    return false;
  }
}

function prepararDatosParaPDF() {
    const data = {
        action: 'actualizarPreviewPDF',
        efectivo: document.getElementById('monto_efectivo')?.value || 0,
        tarjeta: document.getElementById('monto_tarjeta')?.value || 0,
        transferencia: document.getElementById('monto_transferencia')?.value || 0,
        deuna: document.getElementById('monto_deuna')?.value || 0,
        monto_final: document.getElementById('monto_final')?.value || 0,
        observaciones: document.getElementById('observaciones')?.value || "",
        compras: document.getElementById('compras')?.value || ""
    };

    return fetch('ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams(data)
    });
}


function addHabitacion() {
    const entrada = document.getElementById('fecha_entrada').value;
    const salida = document.getElementById('fecha_salida').value;

    if (!entrada || !salida) {
        Swal.fire({
            icon: 'warning',
            title: 'Fechas requeridas',
            text: 'Selecciona primero la fecha de entrada y salida antes de añadir habitaciones.'
        });
        return;
    }

    if (new Date(salida) <= new Date(entrada)) {
        Swal.fire({
            icon: 'error',
            title: 'Fechas inválidas',
            text: 'La fecha de salida debe ser posterior a la de entrada.'
        });
        return;
    }

    const container = document.getElementById('habitacionesContainer');
    const original = container.querySelector('.habitacion-row');
    const clone = original.cloneNode(true);

    // Limpiar habitación seleccionada
    const habitacionSelect = clone.querySelector('.id_habitacion');
    habitacionSelect.value = '';
    habitacionSelect.onchange = recalcularTotalReserva;

    // Limpiar tarifas
    const tarifaAdulto = clone.querySelector('.select_tarifa');
    const tarifaNino = clone.querySelector('.select_tarifa_nino');
    if (tarifaAdulto) {
        tarifaAdulto.value = '';
        tarifaAdulto.onchange = recalcularTotalReserva;
    }
    if (tarifaNino) {
        tarifaNino.value = '';
        tarifaNino.onchange = recalcularTotalReserva;
    }

    // Resetear adultos y niños
    clone.querySelector('.input_adultos').value = 1;
    clone.querySelector('.input_adultos').onchange = recalcularTotalReserva;
    clone.querySelector('.input_ninos').value = 0;
    clone.querySelector('.input_ninos').onchange = recalcularTotalReserva;

    // Resetear desayuno
    const desayunoChk = clone.querySelector('.chk_desayuno');
    const desayunoSel = clone.querySelector('.precio_desayuno');
    desayunoChk.checked = false;
    desayunoChk.onchange = () => {
        togglePrecio(desayunoChk, 'precio_desayuno');
        recalcularTotalReserva();
    };
    desayunoSel.disabled = true;
    desayunoSel.value = '';
    desayunoSel.onchange = recalcularTotalReserva;

    // Resetear tour
    const tourChk = clone.querySelector('.chk_tour');
    const tourSel = clone.querySelector('.precio_tour');
    const lugarTour = clone.querySelector('.select_lugar_tour');
    tourChk.checked = false;
    tourChk.onchange = () => {
        togglePrecio(tourChk, 'precio_tour');
        togglePrecio(tourChk, 'select_lugar_tour');
        recalcularTotalReserva();
    };
    tourSel.disabled = true;
    tourSel.value = '';
    tourSel.onchange = recalcularTotalReserva;

    lugarTour.disabled = true;
    lugarTour.value = '';

    // Cargar habitaciones disponibles desde PHP
    fetch('ajax.php', {
        method: 'POST',
        body: new URLSearchParams({
            action: 'habitacionesDisponibles',
            fecha_entrada: entrada,
            fecha_salida: salida
        })
    })
    .then(r => r.json())
    .then(data => {
        const select = clone.querySelector('.id_habitacion');
        select.innerHTML = '<option value="">Seleccione</option>';
        data.forEach(h => {
            select.innerHTML += `<option value="${h.idhabitacion}">Hab. ${h.numero}</option>`;
        });
    });

    container.appendChild(clone);
    recalcularTotalReserva();
}


function removeHabitacion(btn) {
    const container = document.getElementById('habitacionesContainer');
    if (container.children.length > 1) {
        btn.parentElement.remove();
        recalcularTotalReserva();
    }
}

function calcularNoches() {
    const f1 = document.getElementById('fecha_entrada').value;
    const f2 = document.getElementById('fecha_salida').value;
    const d1 = new Date(f1);
    const d2 = new Date(f2);
    const diff = (d2 - d1) / (1000 * 60 * 60 * 24);
    return (isNaN(diff) || diff <= 0) ? 0 : diff;
}

function recalcularTotalReserva() {
    const noches = calcularNoches();
    if (noches <= 0) return;

    let total = 0;
    const habitacionesUsadas = [];

    document.querySelectorAll('.habitacion-row').forEach((row, idx) => {
        const selectHabitacion = row.querySelector('.id_habitacion');
        const idHab = selectHabitacion?.value;
        if (idHab) {
            if (habitacionesUsadas.includes(idHab)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Habitación duplicada',
                    text: 'No puedes seleccionar la misma habitación más de una vez.'
                });
                selectHabitacion.value = '';
                return;
            }
            habitacionesUsadas.push(idHab);
        }

        const adultos = parseInt(row.querySelector('.input_adultos')?.value || 0);
        const ninos = parseInt(row.querySelector('.input_ninos')?.value || 0);

        const precioAdulto = parseFloat(row.querySelector('.select_tarifa')?.selectedOptions[0]?.dataset.precio || 0);
        const precioNino = parseFloat(row.querySelector('.select_tarifa_nino')?.selectedOptions[0]?.dataset.precio || 0);
        const precioDesayuno = row.querySelector('.chk_desayuno')?.checked
            ? parseFloat(row.querySelector('.precio_desayuno')?.selectedOptions[0]?.dataset?.precio || 0)
            : 0;

        const precioTour = row.querySelector('.chk_tour')?.checked
            ? parseFloat(row.querySelector('.precio_tour')?.selectedOptions[0]?.dataset?.precio || 0)
            : 0;


        const subtotalAdulto = adultos * (precioAdulto + precioDesayuno + precioTour) * noches;
        const subtotalNino = ninos * (precioNino + precioDesayuno + precioTour) * noches;

        total += subtotalAdulto + subtotalNino;
    });

    document.getElementById('total').value = total.toFixed(2);
}

function togglePrecio(checkbox, clase) {
    const select = checkbox.closest('.habitacion-row').querySelector(`.${clase}`);
    if (checkbox.checked) {
        select.disabled = false;
    } else {
        select.disabled = true;
        select.value = '';
    }
    recalcularTotalReserva();
}

function actualizarHabitacionesDisponibles() {
    const entrada = document.getElementById('fecha_entrada').value;
    const salida = document.getElementById('fecha_salida').value;

    if (!entrada || !salida) return;

    fetch('ajax.php', {
        method: 'POST',
        body: new URLSearchParams({
            action: 'habitacionesDisponibles',
            fecha_entrada: entrada,
            fecha_salida: salida
        })
    })
    .then(res => res.json())
    .then(data => {
        document.querySelectorAll('.id_habitacion').forEach(select => {
            const currentValue = select.value;
            let disponible = false;

            select.innerHTML = '<option value="">Seleccione</option>';
            data.forEach(h => {
                if (h.idhabitacion == currentValue) disponible = true;
                select.innerHTML += `<option value="${h.idhabitacion}" ${h.idhabitacion == currentValue ? 'selected' : ''}>Hab. ${h.numero}</option>`;
            });

            if (!disponible && currentValue) {
                Swal.fire({
                    icon: 'info',
                    title: 'Habitación no disponible',
                    text: 'Una de las habitaciones ya no está disponible en el rango de fechas seleccionado.'
                });
                select.value = '';
            }
        });
    })
    .catch(err => {
        Swal.fire({
            icon: 'error',
            title: 'Error al cargar habitaciones',
            text: 'No se pudo verificar disponibilidad. Intenta nuevamente o contacta soporte.'
        });
        console.error('Error al actualizar habitaciones:', err);
    });
}


function iniciarEventosReserva() {
    const fechaEntrada = document.getElementById('fecha_entrada');
    const fechaSalida = document.getElementById('fecha_salida');

    if (fechaEntrada && fechaSalida) {
        fechaEntrada.addEventListener('change', () => {
            actualizarHabitacionesDisponibles();
            recalcularTotalReserva();
        });

        fechaSalida.addEventListener('change', () => {
            actualizarHabitacionesDisponibles();
            recalcularTotalReserva();
        });
    }
}



function sendDataReserva(modo = 'nueva') {
    const form = document.getElementById('formReserva');
    if (!form) {
        Swal.fire('Error', 'Formulario no encontrado', 'error');
        return;
    }

    const habitaciones = form.querySelectorAll('.habitacion-row');
    if (habitaciones.length === 0) {
        Swal.fire('Error', 'Debes añadir al menos una habitación.', 'error');
        return;
    }

    const entrada = document.getElementById('fecha_entrada')?.value;
    const salida = document.getElementById('fecha_salida')?.value;

    if (!entrada || !salida || new Date(salida) <= new Date(entrada)) {
        Swal.fire('Error', 'La fecha de salida debe ser posterior a la fecha de entrada.', 'error');
        return;
    }

    let habitacionesValidas = true;
    const habitacionesUsadas = [];

    habitaciones.forEach(row => {
        const select = row.querySelector('.id_habitacion');
        const idHab = select?.value;

        if (!idHab) {
            habitacionesValidas = false;
        } else {
            if (habitacionesUsadas.includes(idHab)) {
                habitacionesValidas = false;
                Swal.fire('Error', 'Has seleccionado la misma habitación más de una vez.', 'error');
                return;
            }
            habitacionesUsadas.push(idHab);
        }
    });

    if (!habitacionesValidas) {
        Swal.fire('Error', 'Hay habitaciones no seleccionadas o duplicadas.', 'error');
        return;
    }

    const total = parseFloat(document.getElementById('total')?.value || 0);
    const abono = parseFloat(document.getElementById('abono')?.value || 0);

    if (isNaN(total) || total <= 0) {
        Swal.fire('Error', 'El total debe ser mayor a cero.', 'error');
        return;
    }

    if (abono > total) {
        Swal.fire('Error', 'El abono no puede ser mayor que el total.', 'error');
        return;
    }

    const data = new FormData(form);
    data.set('action', (modo === 'editar') ? 'editarReserva' : 'guardarReserva');

    fetch('ajax.php', {
        method: 'POST',
        body: data
    })
    .then(res => res.text())
    .then(response => {
        console.log(response);
        if (response.trim() === 'ok') {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: (modo === 'editar') ? 'Reserva actualizada correctamente' : 'Reserva guardada correctamente'
            });
            closeModal('modalReserva');
            location.reload();
        } else {
            Swal.fire('Error', response, 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
    });
}





$(document).on('click', '.anadirForm', function () {
    const action = $(this).attr('ac');
    const co = $(this).attr('co');
    const tipo = $(this).attr('ti');
    const placa = $(this).attr('pl');
    const lugar = $(this).attr('lu');
    const cedula = $('#id_cliente').val();
    const nombre = $('#nom_cliente').val();
    const apellido = $('#ap_cliente').val();
    const mesa = $('#id_mesa').val();
    const final = $('#id_precioFinal').val();
    const rows = $('#detalle_venta tr').length;
    const dividirBtn = (rows > 1) ? 1 : 2;

    // Reset modal y globales
    $('.modal .bodyModal').html('');
    $('.modal').hide();
    delete window.tarifasDisponibles;
    delete window.tarifasExtras;

    // Si es cancelar reserva, verificar abono antes
    if (action === 'formCancelarReserva') {
        $.post('ajax.php', { action: 'verificarAbono', idreserva: co }, function (resp) {
            try {
                const json = JSON.parse(resp);
                if (json.abono > 0) {
                    Swal.fire({
                        title: 'Reserva con Abono',
                        text: `Esta reserva tiene un abono de $${json.abono}. ¿Deseas continuar con la cancelación?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, cancelar',
                        cancelButtonText: 'No, volver',
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            cargarFormularioModal();
                        }
                    });
                } else {
                    cargarFormularioModal();
                }
            } catch (e) {
                Swal.fire('Error', 'Error al procesar la verificación del abono.', 'error');
            }
        });
    } else {
        cargarFormularioModal();
    }

    function cargarFormularioModal() {
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: {
                action: action,
                co: co,
                tipo: tipo,
                pl: placa,
                lu: lugar,
                ce: cedula,
                nom: nombre,
                ape: apellido,
                mesa: mesa,
                final: final,
                dividirBtn: dividirBtn
            },
            success: function (response) {
                if (response != '6') {
                    $('.modal .bodyModal').html(response);
                    $('.modal').fadeIn('fast', function () {
                        // Activar select2 si existe
                        if ($('.js-example-basic-single').length) {
                            $('.js-example-basic-single').select2({
                                width: '100%',
                                dropdownParent: $('.modal')
                            });
                        }

                        // Eventos fecha entrada/salida para validación de habitaciones
                        if (typeof iniciarEventosReserva === 'function') {
                            iniciarEventosReserva(); // solo si está definida
                        }
                    });

                    // Inicializar datatables si corresponde
                    if ($('#myTableArqueo').length || $('#myTableVentas').length) {
                        $('#myTableArqueo, #myTableVentas').DataTable({
                            language: {
                                url: "https://cdn.datatables.net/plug-ins/1.10.11/i18n/Spanish.json"
                            },
                            dom: 'Bfrtip',
                            buttons: ['excelHtml5', 'pdfHtml5']
                        });
                    }
                } else {
                    $('.modal').fadeOut();
                    Swal.fire({
                        position: 'top-end',
                        icon: 'error',
                        title: 'Todas las cajas están abiertas',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Hubo un problema al cargar el formulario.'
                });
            }
        });
    }
});


function sendCancelarReserva() {
    const form = document.getElementById('form_cancelar_reserva');
    const formData = new FormData(form);

    fetch('ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(resp => resp.text())
    .then(data => {
        alert(data);
        closeModal();
        location.reload();
    })
    .catch(err => {
        alert('Error al cancelar la reserva');
    });
}




function abrirFormularioCliente() {
    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        data: { action: 'formCliente' },
        success: function (response) {
            $('.modal .bodyModal').html(response);
            $('.modal').fadeIn();
        }
    });
}

