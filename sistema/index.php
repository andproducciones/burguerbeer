<?php 

session_start();
if(($_SESSION['rol']) != 3)
{
	

?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<?php include "includes/scripts.php"; ?>
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<title>Sisteme Ventas</title>

	<style>
		.dashboard-container {
    margin-top: 10px;
    padding: 15px;
     
    background: #f9f9f9; /* Fondo claro opcional */

 
}

.section-title h1 {
    font-size: 24px;
    margin-bottom: 20px;
    color: #333;
    text-align: center;
}

.charts-row {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
}

.chart-container {
    flex: 1; /* Distribuir el espacio equitativamente */
    max-width: 48%; /* Limitar el ancho al 48% para dos columnas */
}

canvas {
    width: 100% !important; /* Hacer el canvas responsivo */
    height: auto !important;
    border: 1px solid #ddd; /* Borde opcional para separar gráficos */
    border-radius: 8px; /* Bordes redondeados */
    padding: 10px; /* Espacio interno para estética */
    background: #fff; /* Fondo blanco */
}

	</style>
</head>
<body>

	<?php 
	include "includes/header.php"; 
	include '../conexion.php';

	$query_dash =  mysqli_query($conection,"CALL dataDashboard();");
	$result_das = mysqli_num_rows($query_dash);
	//print($query_dash);
	if($result_das > 0){
		  // Primer conjunto de resultados: Datos generales
		  $data_dash = mysqli_fetch_assoc($query_dash);

		  // Avanzar al siguiente conjunto de resultados
		  mysqli_next_result($conection);
	  
		  // Segundo conjunto de resultados: Datos para gráficos
		  $chartData = [];
		  if ($result = mysqli_store_result($conection)) {
			  while ($row = mysqli_fetch_assoc($result)) {
				  $chartData[] = $row;
			  }
		  }

		  // Avanzar al tercer conjunto de resultados
		  mysqli_next_result($conection);

		  // Tercer conjunto de resultados: Productos más vendidos
		  $productos_mas_vendidos = [];
		  if ($result = mysqli_store_result($conection)) {
			  while ($row = mysqli_fetch_assoc($result)) {
				  $productos_mas_vendidos[] = $row;
			  }
		  }
		  mysqli_close($conection);
	}
	print_r ($data_dash);
	?>

	<section id="container"> 
		<div class="divContainer">
			<div>
				<h1 class="titlePanelControl">Panel de Control</h1>
			</div>
		
			<div class="dashboard">
				<?php
					if($_SESSION['rol'] == 1){
 					?>
				<a href="lista_usuarios.php">
					<i class="fas fa-users  fa-2x"></i>
					<p>
						<strong>Usuarios</strong><br>
						<span><?= $data_dash['usuarios'];?></span>
				</p>
			</a>
			<?php } ?>
			<a href="lista_clientes.php">
				<i class="fas fa-user fa-2x"></i>
				<p>
					<strong>Clientes</strong><br>
					<span><?= $data_dash['clientes'];?></span>
				</p>
			</a>
			<a href="lista_producto.php">
				<i class="fas fa-cubes fa-2x"></i>
				<p>
					<strong>Productos</strong><br>
					<span><?= $data_dash['productos'];?></span>
				</p>
			</a>
			<a href="ventas.php">
				<i class="fas fa-file-alt fa-2x"></i>
				<p>
					<strong>Ventas del Día</strong><br>
					<span><?= $data_dash['ventas'];?></span>
				</p>
			</a>
		</div>

		<!-- Sección para los gráficos -->
			<div class="dashboard-container">
				<div class="">
					<h1 class="titlePanelControl">Estadistica</h1>
				</div>

				<div class="charts-row">
					<div class="chart-container">
						<canvas id="ventasChart"></canvas>
					</div>
					<div class="chart-container">
						<canvas id="salariosChart"></canvas>
					</div>
					<div class="divContainer">
            <table border="1">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Producto</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    foreach ($productos_mas_vendidos as $producto) { ?>
                        <tr>
                            <td><?= $rank++; ?></td>
                            <td><?= $producto['nombre_producto']; ?></td>
                            <td><?= $producto['total_vendidos']; ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
				</div>
			</div>

            
            
        </div>

		


	
	
	<div class="divInfoSistema">
		<div>
			<h1 class="titlePanelControl">Configuración</h1>
		</div>
		
		<div class="containerPerfil">
			<div class="containerDataUser">
				<div class="divDataUser">
					
					<img src="img/logo_user.png" class="logoUser">
					<h4>Informacion Personal</h4>
				
					<div>
						<label>Nombre:</label> <span>Francis<?= $_SESSION['nombre'];?> <?= $_SESSION['apellido'];?></span>
					</div>
					<div>
						<label>Correo:</label> <span><?= $_SESSION['correo'];?></span>
					</div>
					<h4>Datos de Usuario</h4>
					<div>
						<label>Rol:</label> <span><?= $_SESSION['rol_name'];?></span>
					</div>
					
					<h4>Cambiar contraseña</h4>
					<form action="" method="post"  name="frmChangePass" id="frmChangePass">
						<div>
							<input type="password" name="txtPassUser" id="txtPassUser" placeholder="Contraseña Actual" required>
						</div>
						<div>
							<input class="newPass" type="password" name="txtNewPassUser" id="txtNewPassUser" placeholder="Nueva Contraseña" required>
						</div>
						<div>
							<input class="newPass" type="password" name="txtPassConfirm" id="txtPassConfirm" placeholder="Confirmar COntraseña" required>
						</div>
						<div class="alertChangePass" style="display: none;">
							
						</div>

						<div>
							<button type="submit" class="btn_save btnChangePass"><i class="fas fa-key"></i> Cambiar Contraseña</button>
						</div>						

					</form>
				</div>
						
		</div>
	</div>

	</section>

<?php include "includes/footer.php"; ?>
</body>
</html>
<script>
        // Datos para los gráficos
        const chartData = <?= json_encode($chartData); ?>;

        // Extraer fechas, ventas y salarios
        const labels = chartData.map(data => data.fecha);
        const ventas = chartData.map(data => data.total_ventas);
        const salarios = chartData.map(data => data.total_salarios);

        // Configuración del gráfico de Ventas
        new Chart(document.getElementById('ventasChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Ventas Diarias',
                    data: ventas,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderWidth: 1,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Ventas por Día' }
                }
            }
        });

        // Configuración del gráfico de Salarios
        new Chart(document.getElementById('salariosChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Salarios',
                    data: salarios,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderWidth: 1,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Salarios' }
                }
            }
        });
    </script>

<?php
}else{


header('location: ../index2.php');
session_destroy();
}
?>
