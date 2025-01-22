<nav>
			<ul>
				<li><a href="index.php"><i class="fas fa-home"></i> Inicio</a></li>
				
					<?php
					if($_SESSION['rol'] == 1){
 					?>
				<li class="principal">
					
					<a href="lista_usuarios.php"><i class="fas fa-users"></i> Usuarios <span class="arrow"></span></a>

				</li>
				<?php } ?>
				<li class="principal">
					<a href="lista_clientes.php"><i class="fas fa-user-friends"></i> Clientes <span class="arrow"></span></a>
					
				</li>
				<li class="principal">
					<a href="#"><i class="fas fa-cubes"></i> Productos <span class="arrow"><i class="fas fa-angle-down"></i></span></a>

					<ul>
						<li><a href="lista_categorias.php"><i class="fas fa-cash-register"></i> Categorias</a></li>
						<li><a href="lista_producto.php"><i class="fas fa-hand-holding-usd"></i> Productos</a></li>
						<li><a href="lista_atributos.php"><i class="fas fa-hand-holding-usd"></i> Atributos</a></li>
					</ul>
					
				</li>
				
				<li class="principal">
				    <a href="#" onclick="abrirModoVentas(); return false;">
				        <i class="fas fa-cash-register"></i> Caja <span class="arrow"></span>
				    </a>
				</li>

				<li class="principal">
					<a href="ventas.php"><i class="fas fa-user-friends"></i> Ventas <span class="arrow"></span></a>
				</li>


				<li class="principal">
					<a href="#"><i class="fas fa-store-alt"></i> Administración  <span class="arrow"></i><i class="fas fa-angle-down"></i></span></a>
					<ul>
						<li><a href="lista_cajas.php"><i class="fas fa-cash-register"></i> Cajas</a></li>
					</ul>
				</li>
			</ul>
		</nav>