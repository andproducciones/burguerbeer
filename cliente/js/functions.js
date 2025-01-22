$(document).ready(function(){

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

$('#cl_usuario').keyup(function(e){
    e.preventDefault();

    var cl = $(this).val();
    var action = 'searchCliente';

    $.ajax({
            url: 'ajax.php',
            type: 'POST',
            async: true,
            data: {action:action,cliente:cl},

            success: function(response){
                //console.log(response);
                if(response == 0){
                   $('#id_cliente').val('');
                   $('#nom_cliente').val('');
                   $('#ap_cliente').val('');
                   $('.btn_new_cliente').slideDown();
                }else{ 
                    var data = $.parseJSON(response);
                    $('#id_cliente').val(data.usuario_c);
                    $('#cl_usuario').val(data.usuario_c);
                    $('#nom_cliente').val(data.nombre);
                    $('#ap_cliente').val(data.p_apellido);
                    $('.btn_new_cliente').slideUp();

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

    if(producto!='')
{
    $.ajax({
            url: 'ajax.php',
            type: 'POST',
            async: true,
            data: {action:action,producto:producto},

            success: function(response)
            {
                //console.log(response);
                
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
    
    }
    });

//validar cantidad multiplicacion

$('#txt_cant_producto').keyup(function(e){
    e.preventDefault();
    var precio_total = $(this).val() * $('#txt_precio').html();
    var existencia = parseInt($('#txt_existencia').html());
    
    $('#txt_precio_total').html(precio_total);


    if( ($(this).val() < 1 || isNaN($(this).val())) || ( $(this).val() > existencia)) {
        
        $('#add_product_detalle').slideUp();
        //$('#no_hay_productos').slideDown();
    }else{
        $('#add_product_detalle').slideDown();
        //$('#no_hay_productos').slideUp();
    }

});




//agregar producto al detalle aqui una
$('#add_product_venta').click(function(e){
    e.preventDefault();
    
    if($('#txt_cant_producto').val() > 0 )
    {
    var codproducto = $('#txt_cod_producto').val();
    var cantidad    = $('#txt_cant_producto').val();
    var action      = 'addProductoDetalle';
    
    $.ajax({
            url: 'ajax.php',
            type: 'POST',
            async: true,
            data: {action:action,producto:codproducto,cantidad:cantidad},

            success: function(response)
            {console.log(response);
                if(response != 'error')
                {   
                    var info =JSON.parse(response);
                    //console.log(info);
                    $('#detalle_venta').html(info.detalle);
                    $('#detalle_totales').html(info.totales);
                    
                    $('#txt_cod_producto').val('');
                    $('#txt_descripcion').html('-');
                    $('#txt_existencia').html('-');
                    $('#txt_cant_producto').val('0');
                    $('#txt_precio').html('0.00');
                    $('#txt_precio_total').html('0.00');

                    $('#txt_cant_producto').attr('disabled','disabled');

                    $('#add_product_venta').slideUp();
                    }else{
                 //console.log('no data');   
                }   

                viewProcesar();
            },

            error: function(error){
                   console.log(error);
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
            var action = 'anularVenta';

            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                async: true,
                data: {action:action},

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


//Facturar venta
$('#btn_facturar_compra').click(function(e){
e.preventDefault();
    
    var rows  = $('#detalle_venta tr').length;
    
        if(rows > 0)
        {
            var action          = 'procesarVenta';
            var codcliente      = $('#id_cliente').val();
            var credito         = $('#credito').val();
            var precioVenta    = $('#totalVenta').html();

            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                async: true,
                data: {action:action,codcliente:codcliente,credito:credito,precioVenta:precioVenta},

                success: function(response)
                {   
                    console.log(response);
                    
                    if (response != "1"){

                    if(response != 'error')
                    {   
                        var info =JSON.parse(response);
                        console.log(info);

                        //generarPDF(info.codcliente,info.nofactura);

                       location.reload();
                    }else{
                        console.log('no data');
                    }
                
                    }else{
                        
                    $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product">'+'<h2><i class="fas fa-money-bill-wave" style="font-size: 45pt;"></i><br>SALDO INSUFICINENTE PARA COMPLETAR COMPRA</h2>'+
                            '<a href="#" class="btn_ok closeModal" onclick="closeModal();"><i class="fas fa-ban"></i> Cerrar</a>'+ '</form>');

                    $('.modal').fadeIn();
                    console.log('no credito');
                    }

                },
                error: function(error){
                    console.log(error);
                }
            });
        }
    });

    $('.view_ticket').click(function(e){
        e.preventDefault();

        var correlativo  = $(this).attr('co');

        generarPDFticket(correlativo);
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

// añadir productos nuevo AQUIIIIII

    $('#txt_producto').change(function(e){
    e.preventDefault();
    
    var codproducto = $(this).val();
    var comedor     = $('#txt_comedor').val();
    var action      = 'addProductoDetalleComedor';
    
    $.ajax({
            url: 'ajax.php',
            type: 'POST',
            async: true,
            data: {action:action,producto:codproducto,comedor:comedor},

            success: function(response)
            {
               console.log(response);
                if(response != 'error')
                {
                    var info =  JSON.parse(response);   
                   
                        console.log(info);
                  
                   
                   $('#txt_existencia').html(info.existencia);
                   $('#txt_cant_producto').val('0');
                   $('#txt_precio').html(info.precio);
                   $('#txt_precio_total').html(info.precio);
                   
                   $('#txt_cant_producto').removeAttr('disabled');
                   //$('#option_0').attr('selected','selected');
                   $('#txt_comedor').attr('disabled','disabled');

                   //$('#add_product_detalle').slideDown();
                   


                }else{ 
                    //$('#txt_comedor').attr('#option_0');
                    $('#txt_existencia').html('-');
                    $('#txt_cant_producto').val('0');
                    $('#txt_precio').html('0.00');
                    $('#txt_precio_total').html('0.00');

                    $('#txt_comedor').removeAttr('disabled');
                    //$('#option_0').attr('selected','selected');
                    $('#txt_cant_producto').attr('disabled','disabled');
                    

                    $('#add_product_detalle').slideUp();
                }
                
            },

            error: function(error){
                   console.log(error);
            }
        });
}); 

//buscar producto
$('#txt_comedor').change(function(e){
    e.preventDefault();

    var comedor = $(this).val();
    var action = 'infoComedor';

    if(comedor!='')
{
    $.ajax({
            url: 'ajax.php',
            type: 'POST',
            async: true,
            data: {action:action,comedor:comedor},

            success: function(response)
            {
                //console.log(response);
                if(response != 'error')
                {
                var info = JSON.parse(response);
                //console.log(info);
                $('#txt_producto').html(info.detalle);
                //$('#option_0').attr('selected','selected');
            }else{
                $('#txt_producto').html('');
            }

            },

            error: function(error){
                   console.log(error);
            }
        });
    
    }
    });


//añadir productos al detalle nuevooooooo AQUUUUUUIIIIIIIIIIII

$('#add_product_detalle').click(function(e){
    e.preventDefault();
    
    if($('#txt_cant_producto').val() > 0)
    {
    var codproducto = $('#txt_producto').val();
    var cantidad    = $('#txt_cant_producto').val();
    var fecha       = $('#txt_fecha').val();
    var comedor    = $('#txt_comedor').val();
    var action      = 'addProductoDetalleVenta';
    
    $.ajax({
            url: 'ajax.php',
            type: 'POST',
            async: true,
            data: {action:action,producto:codproducto,cantidad:cantidad,fecha:fecha,comedor:comedor},

            success: function(response)
            {
               console.log(response);
            if(response != 'error')
                {   
                    //console.log(response);
                    var info =JSON.parse(response);
                    //console.log(info);
                    $('#detalle_venta').html(info.detalle);
                    $('#detalle_totales').html(info.totales);
                    
                    $('#txt_comedor').removeAttr('disabled');
                    $('#txt_producto').html('-');
                    $('#txt_existencia').html('-');
                    //$('#txt_fecha').val('');
                    $('#txt_cant_producto').val('0');
                    $('#txt_precio').html('0.00');
                    $('#txt_precio_total').html('0.00');

                    $('#txt_cant_producto').attr('disabled','disabled');
                    //$('#txt_comedor').attr('disabled','disabled');

                    $('#add_product_detalle').slideUp();
                    location.reload();
                    }else{
                 //console.log('no data');   
                }   

                viewProcesar();

            },

            error: function(error){
                   console.log(error);
            }
        });
    }

});



$('.view_ticketcel1').click(function(){
        

            var correlativo  = $(this).attr('co');
            var action       = 'generarQR2';


        $.ajax({
            url:'ajax.php',
            type:'POST',
            async: true,
            data: {action:action,correlativo:correlativo},
            
            success: function(response) {
                
                 console.log(response);

                 $('.bodyModal').html(response);
                 $('.modal').fadeIn();


                //$(".showQRCode").html(response);  
            },
         });
    });

});  //final del ready

function generateQR(){

 var correlativo  = $(this).attr('co');

     

        $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product">'+'<h2><i class="fas fa-money-bill-wave" style="font-size: 45pt;"></i><br>SALDO INSUFICINENTE PARA COMPLETAR COMPRA</h2>'+
        '<a href="#" class="btn_ok closeModal" onclick="closeModalCon();"><i class="fas fa-ban"></i> Cerrar</a>'+ '</form>');

        $('.modal').fadeIn();
        console.log('no credito');




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

    $url = 'factura/generaFactura.php?cl='+cliente+'&f='+factura;
    window.open($url,"Factura","left="+x+",top="+y+",height="+alto+"width="+ancho+",scrollbar=si,location=no,resizable=si,menubar=no");
}

function generarPDFticket(correlativo){

    var ancho = 1000;
    var alto  = 800;

    var x = parseInt((window.screen.width/2) - (ancho / 2));
    var y = parseInt((window.screen.heigth/2) -  (alto / 2));

    $url = 'factura/generaTicket.php?co='+correlativo;
    window.open($url,"ticket","left="+x+",top="+y+",height="+alto+"width="+ancho+",scrollbar=si,location=no,resizable=si,menubar=no");
}




function del_product_detalle(correlativo){

    var action  = 'del_product_detalle';
    var id_detalle    = correlativo;
 
 $.ajax({
            url: 'ajax.php',
            type: 'POST',
            async: true,
            data: {action:action,id_detalle:id_detalle},

            success: function(response)
            { console.log(response); 
                 if(response != 'error')
                {   
                    
                    var info =JSON.parse(response);
                   console.log(info);
                    $('#detalle_venta').html(info.detalle);
                    $('#detalle_totales').html(info.totales);
                    
                    //$('#txt_cod_producto').val('');
                    //$('#txt_descripcion').html('-');
                    $('#txt_existencia').html('-');
                    $('#txt_cant_producto').val('0');
                    $('#txt_precio').html('0.00');
                    $('#txt_precio_total').html('0.00');

                    $('#txt_cant_producto').attr('disabled','disabled');

                    $('#add_product_detalle').slideUp();

                 //console.log(response);   
                }else{
                    $('#detalle_venta').html('');
                    $('#detalle_totales').html('');

                }
                viewProcesar();
            },

            error: function(error){
                   console.log(error);
            }
        }); 

}

function viewProcesar(){
    if($('#detalle_venta tr').length > 0)
     {
     
        $('#btn_facturar_compra').show();
        }else{
        $('#btn_facturar_compra').hide();
    }

}

function searchForDetalle(id){

    var action  = 'searchForDetalle';
    var user    = id;
 
 $.ajax({
            url: 'ajax.php',
            type: 'POST',
            async: true,
            data: {action:action,user:user},

            success: function(response)
            {
                if(response != 'error')
                {   
                    var info =JSON.parse(response);
                    //console.log(info);
                    $('#detalle_venta').html(info.detalle);
                    $('#detalle_totales').html(info.totales);
                    
                    }else{
                 console.log('no data');   
                }
                viewProcesar();
            },

            error: function(error){
                   console.log(error);
            }
        });           
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

function closeModal(){
    
    $('.alertAddProduct').html('');
    $('#txtCantidad').val('');
    $('#txtPrecio').val('');
    $('.modal').fadeOut();
    }

    function closeModal(){
    

    $('.modal').fadeOut();
    location.reload();
    }