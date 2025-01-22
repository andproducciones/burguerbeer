<nav>
			<ul>
				<li><a href="index.php"><i class="fas fa-home"></i> Inicio</a></li>
				
					<?php
					if($_SESSION['rol'] == 1){
 					?>
				<li class="principal">
					
					<a href="#"><i class="fas fa-users"></i> Usuarios <span class="arrow"><i class="fas fa-angle-down"></i></span></a>
					<ul>
						<li><a href="registro_usuario.php"> <i class="fas fa-user-plus"></i> Nuevo Usuario</a></li>
						<li><a href="lista_usuarios.php"><i class="fas fa-users"></i> Lista de Usuarios</a></li>
					</ul>
				</li>
				<?php } ?>
				<li class="principal">
					<a href="#">Clientes <span class="arrow"><i class="fas fa-angle-down"></i></span></a>
					<ul>
						<?php
					if($_SESSION['rol'] == 1){
 					?>
						<li><a href="registro_cliente.php">Nuevo Cliente</a></li>
					<?php } ?>
						
						<li><a href="lista_clientes.php">Lista de Clientes</a></li>
					</ul>
				</li>
				<li class="principal">
					<a href="#">Proveedores <span><i class="fas fa-angle-down"></i></span></a>
					<ul>
						<li><a href="#">Nuevo Proveedor</a></li>
						<li><a href="#">Lista de Proveedores</a></li>
					</ul>
				</li>
				<li class="principal">
					<a href="#">Productos <span class="arrow"><i class="fas fa-angle-down"></i></span></a>
					<ul>
						<?php
					if($_SESSION['rol'] == 1){
 					?>
						<li><a href="registro_producto.php">Nuevo Producto</a></li>
						<?php } ?>
						<li><a href="lista_producto.php">Lista de Productos</a></li>
					</ul>
				</li>
				<li class="principal">
					<a href="#">Ventas <span class="arrow"><i class="fas fa-angle-down"></i></span></a>
					<ul>
						<li><a href="nueva_venta.php">Nuevo Factura</a></li>
						<li><a href="venta_credito.php">Venta de Credito</a></li>
						<li><a href="ventas.php"><i class="fas fa-newspaper"></i> Ventas realizadas</a></li>
					</ul>
				</li>
			</ul>
		</nav>