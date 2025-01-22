<?php 

//include "generaTicket.php";

 ?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
  <title>Ticket Venta</title>
  <link rel="stylesheet" href="style.css">
  <style type="text/css">
.TITULO {
  font-family: Arial Black, Gadget, sans-serif;
  font-size: 10px;
}
.TITULO strong {
  font-size: 40px;
}
.TITULO_PRODUCTO {
  font-weight: bold;
  font-size: 15px;
}
body p {
  font-family: Arial, Helvetica, sans-serif;
}
</style>
</head>
<body>
<?php echo $anulada; ?>
<table width="100%" border="1">
  <tr>
    <td width="18%" height="78"><div align="center"><img src="img/logo.png" width="65" height="74" /></div></td>
    <td colspan="2"><div align="center" class="TITULO"><strong>ALA 23</strong></div></td>
    <td width="27%" rowspan="4"><div align="center">
      
<?php 
include('phpqrcode/qrlib.php');
QRcode::png(base64_encode($data['correlativo']),'filename.png','H','7');

echo '<img class="img-thumbnail" src="filename.png" width="300" />';
 ?>

    </div></td>
  </tr>
  <tr>
    <td height="53" colspan="2"><div align="center">
      <p class="TITULO_PRODUCTO">DIA</p>
      <p class="TITULO_PRODUCTO"><?php echo 

      $data['fecha'];
      
      //echo date_create_from_format('D', $cadena_fecha_mysql);


      //echo '$objeto_DateTime';

      ?></p>
    </div></td>
    

    <td height="53"><div class="TITULO" align="center">
    <strong><?php echo $data['producto'];?></strong></div></td>
  </tr>
  

  <tr>
    <td colspan="2"><div align="center"><p class="TITULO_PRODUCTO">FECHA</p>
    <p><?php echo $data['fecha'];?></p></div></td>
    <td ><div align="center"><p class="TITULO_PRODUCTO">DESCRIPCION</p>
    <p><?php echo $data['fecha'];?></p></div></td>
  </tr>
  <tr>
    <td colspan="2" rowspan="1"><div align="center">
      <p class="TITULO_PRODUCTO">CANTIDAD</p>
      <p><?php echo $data['cantidad'];?></p>
    </div></td>
    <td width="38%" rowspan="1"><div align="center">
      <p class="TITULO_PRODUCTO">COMEDOR</p>
      <p><?php 
       
       $comedor = $data['comedor'];

       if($comedor == 1){
       echo 'Halcones';
       }

       if($comedor == 2){
        echo "Dragones";
       }

       ?></p>
    </div></td>
  </tr>

</table>
</body>
</html>