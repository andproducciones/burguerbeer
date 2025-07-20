<nav>
	<ul>
		<!-- INICIO -->
		<li>
			<a href="index.php"><i class="fas fa-home"></i> Inicio</a>
		</li>

		<!-- USUARIOS (solo para rol 1) -->
		<?php if ($_SESSION['rol'] == 1) { ?>
		<li class="principal">
			<a href="lista_usuarios.php"><i class="fas fa-users"></i> Usuarios <span class="arrow"></span></a>
		</li>
		<?php } ?>

		<!-- CLIENTES -->
		<li class="principal">
			<a href="lista_clientes.php"><i class="fas fa-user-friends"></i> Clientes <span class="arrow"></span></a>
		</li>

		<!-- BURGERBEER -->
		<li class="principal">
			<a href="#" class="toggle-submenu">
				<i class="fas fa-hamburger"></i> BurgerBeer
				<span class="arrow"><i class="fas fa-angle-down"></i></span>
			</a>
			<ul>
				<li><a href="lista_categorias.php"><i class="fas fa-cash-register"></i> Categorías</a></li>
				<li><a href="lista_producto.php"><i class="fas fa-hand-holding-usd"></i> Productos</a></li>
				<li><a href="lista_atributos.php"><i class="fas fa-hand-holding-usd"></i> Atributos</a></li>
				<li><a href="#" onclick="abrirModoVentas(); return false;"><i class="fas fa-cash-register"></i>
						Facturar</a>
				</li>
				<li><a href="ventas.php"><i class="fas fa-user-friends"></i> Ventas</a></li>
			</ul>
		</li>

		<!-- CAÑALIMEÑA -->
		<li class="principal">
			<a href="#" class="toggle-submenu">
				<i class="fas fa-hotel"></i> Cañalimeña
				<span class="arrow"><i class="fas fa-angle-down"></i></span>
			</a>
			<ul>
				<li><a href="reservas.php"><i class="fas fa-calendar-check"></i> Gestión de Reservas</a></li>
				<li><a href="checkin_checkout.php"><i class="fas fa-sign-in-alt"></i> Check-In / Check-Out</a></li>
				<li><a href="facturacion.php"><i class="fas fa-file-invoice-dollar"></i> Facturar</a></li>
				<li><a href="habitaciones.php"><i class="fas fa-bed"></i> Habitaciones</a></li>
				<li><a href="tarifas.php"><i class="fas fa-dollar-sign"></i> Tarifas Habitaciones</a></li>
				<li><a href="tarifas_extra.php"><i class="fas fa-utensils"></i> Tarifas Extras</a></li>
				<li><a href="lugares_tour.php"><i class="fas fa-map-marked-alt"></i> Lugares Turísticos</a></li>
				<li><a href="caja_hotel.php"><i class="fas fa-cash-register"></i> Caja Hotel</a></li>
				<li><a href="reportes.php"><i class="fas fa-chart-line"></i> Reportes</a></li>
			</ul>
		</li>


		<!-- ADMINISTRACIÓN -->
		<li class="principal">
			<a href="#" class="toggle-submenu">
				<i class="fas fa-store-alt"></i> Administración
				<span class="arrow"><i class="fas fa-angle-down"></i></span>
			</a>
			<ul>
				<li><a href="lista_cajas.php"><i class="fas fa-cash-register"></i> Cajas</a></li>
			</ul>
		</li>
	</ul>
</nav>