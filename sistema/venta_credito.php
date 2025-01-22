<?php 
session_start();
include "../conexion.php";

//echo md5($_SESSION['idUser']);

  ?>


<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<?php include "includes/scripts.php"; ?>
	<title></title>
</head>
<body>
<?php include "includes/header.php"; ?>
<section id="container">
		<div class="title_page">
			<h1><i class="fas fa-cube"></i> Nueva Venta</h1>
		</div>
		<div class="datos_cliente">
			<div class="action_cliente">
			<h4>Datos del Cliente</h4>
			<?php if($_SESSION["rol"] == 1){?>
			<a href="registro_cliente.php" class="btn_new btn_new_cliente"><i class="fas fa-plus"></i> Nuevo Cliente</a>
			<?php } ?>
		</div>
		
		<form name="form_new_cliente" id="form_new_cliente" class="datos">
			<input type="hidden" name="action" value="addCliente">
			<input type="hidden" name="id_cliente" id="id_cliente" value="" required>
			<div class="wd30">
				<label>Cedula</label>
				<input type="text" name="cl_usuario" id="cl_usuario" maxlength="10" minlength="10">
			</div>
			<div class="wd30">
				<label>Nombre</label>
				<input type="text" name="nom_cliente" id="nom_cliente" disabled required>
			</div>
			<div class="wd30">
				<label>Apellido</label>
				<input type="text" name="ap_cliente" id="ap_cliente" disabled required>
			</div>
			
			<div class="wd30">
				<label>Credito Actual</label>
				<input type="text" name="cred_act" id="cred_act" disabled required>
			</div>
			<div class="wd30">
				<label>Valor a Recargar</label>
				<input type="text" name="valor_recargar" id="valor_recargar" required>
			</div>

			<div class="wd30">
			
			<a href="#" id="btn_añadir_credito" class="link_add"><i class="fas fa-plus"></i> Agregar</a></td>
			
			</div>


			<div id="div_registro_cliente" class="wd100">
				<button type="submit" class="btn_save"><i class="far fa-save fa-lg"></i> 
				Guardar</button>
			</div>
		</form>
 		
 		<table>
 		
 			<tbody id="detalle_venta_credito">
				<!---contenido ajax--->

			</tbody>
		</table>


 </div>
	<div class="datos_venta">
		<h4>Datos Venta</h4>
		<div class="datos">
			<div class="wd50">
				<label>Vendedor</label>
				<p><?php echo $_SESSION["nombre"]; ?> <?php echo $_SESSION["apellido"]; ?></p>
			</div>
			<div class="wd50">
				<label>Acciones</label>
				<div id="acciones_venta">
					<a href="#" class="btn_ok textcenter" id="btn_anular_venta"><i class="fas fa-ban"></i> Anular</a>
					<a href="#" class="btn_new textcenter" id="btn_facturar_venta_credito" style="display: none;"><i class="far fa-edit"></i> Procesar</a>
				</div>
			</div>
		</div>
	</div>
</section>

<?php include "includes/footer.php"; ?>

<script type="text/javascript">
	$(document).ready(function(){
  	var usuarioid = '<?php echo $_SESSION['idUser'];?>';
  	searchForDetalleCred(usuarioid);

  });

</script>

</body>
</html>