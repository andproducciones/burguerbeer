<?php 

if(empty($_SESSION['active']))
{
	header('location: ../');
}

?>
<header>
		<div class="header">
			<a href="#" class="btn_menu"><i class="fas fa-bars"></i></a>
			<h1>BURGERBBER</h1>
			<div class="optionsBar">
				<p class="fecha">Ecuador, <?php echo fechaC(); ?></p>
				<span class="fecha">|</span>
				<span class="user"><?php echo $_SESSION['nombre']." ".$_SESSION['apellido']." -".$_SESSION['rol']; ?></span>
				<img class="photouser" src="img/user.png" alt="Usuario">
				<a href="salir.php" class="optionsBarSalir"><img class="close" src="img/salir.png" alt="Salir del Sistema" title="Salir"></a>
			</div> 
		</div>
		<?php include"nav.php";?>
	</header>

	<div class="modal">
		<div class="bodyModal">
		</div>
	</div>

	<div class="modal2">
		<div class="bodyModal2">
		</div>
	</div>

	<div class="modal3">
		<div class="bodyModal3">
		</div>
	</div>

	<div id="modalDividirCuenta" class="modalDividirCuenta" style="display:none;">
    <div class="bodyModalDividirCuenta">
        <h1>Dividir Cuenta</h1>
        <p>Selecciona los productos para la nueva factura:</p>
        <form id="form_dividir_cuenta">
            <div class="productos-dividir">
                <!-- Aquí se mostrarán los productos con checkboxes -->
                <?php
                foreach ($productos as $producto) {
                    echo '<div class="producto-seleccion">
                            <input type="checkbox" name="productos_seleccionados[]" value="' . $producto['codproducto'] . '" class="producto-checkbox">
                            <label>' . $producto['producto'] . ' - Cantidad: ' . $producto['cantidad'] . ' - Precio: $' . number_format($producto['precio_venta'], 2) . '</label>
                          </div>';
                }
                ?>
            </div>

            <div class="acciones">
                <button type="button" class="boton verde" onclick="procesarDivisionCuenta();">Crear Nueva Factura</button>
                <a href="#" class="closeModal" onclick="cerrarDividirCuenta();">Cerrar</a>
            </div>
        </form>
    </div>
</div>