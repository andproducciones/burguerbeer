<?php

session_start();
if (($_SESSION['rol']) != 3) {


    ?>
<!DOCTYPE html>
<html lang="es">

<head>
	<meta charset="UTF-8">
	<?php include "includes/scripts.php"; ?>
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<title>Sisteme Ventas</title>

	<style>
		/* General */
		html,
		body {
			width: 100vw;
			height: 100vh;
			margin: 0;
			padding: 0;

			font-family: Arial, sans-serif;
			box-sizing: border-box;
		}

		/* Contenedor principal en grid */
		.divContainer.dashboard-grid {
			display: grid;
			grid-template-rows: 0.1fr 0.2fr 0.35fr;
			/* título, métricas, gráficos, tablas */
			padding: 10px;
			gap: 10px;
			overflow: scroll;
		}

		/* Título */
		.titlePanelControl {
			font-size: 20px;
			margin-top: 75px;
			text-align: center;
			display: flex;
			align-items: center;
			justify-content: center;
			background: #fff;
			border-radius: 8px;
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
			color: #333;
		}

		/* Tarjetas métricas */
		.grid-card-metrics {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));

			gap: 10px;
		}

		.card-dashboard {
			background: #fff;
			border-radius: 8px;
			box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
			padding: 10px;
			text-align: center;
			transition: transform 0.2s ease;
			display: flex;
			flex-direction: column;
			justify-content: center;
			height: 100%;
		}

		.card-dashboard:hover {
			transform: scale(1.03);
		}

		.card-dashboard i {
			font-size: 20px;
			color: #0a4661;
			margin-bottom: 5px;
		}

		.card-dashboard h3 {
			font-size: 13px;
			margin: 0 0 4px;
			color: #444;
		}

		.card-dashboard span {
			font-size: 14px;
			font-weight: bold;
			color: #058167;
		}

		.card-dashboard a {
			text-decoration: none;
			color: inherit;
			display: block;
			width: 100%;
			height: 100%;
		}

		/* Gráficos */
		.grid-charts {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 10px;
			height: 100%;
		}

		.chart-container,
		.chart-container-full {
			background: #fff;
			border: 1px solid #ccc;
			border-radius: 8px;
			padding: 5px;
			display: flex;
			align-items: center;
			justify-content: center;
			height: 100%;
		}

		canvas {
			width: 100% !important;
			height: 100% !important;
			background: #fff;
			border-radius: 8px;
		}

		/* Tablas */
		.grid-tables {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 10px;
			height: 100%;
		}

		.productos-box {
			background: #fff;
			padding: 5px;
			border-radius: 8px;
			border: 1px solid #ccc;
			display: flex;
			flex-direction: column;
			height: 100%;
			overflow: hidden;
		}

		.productos-box h3 {
			font-size: 13px;
			text-align: center;
			margin: 5px 0;
			color: #333;
		}

		.productos-table {
			width: 100%;
			border-collapse: collapse;
			font-size: 11px;
			flex: 1;
		}

		.productos-table th,
		.productos-table td {
			border: 1px solid #ccc;
			padding: 4px;
			text-align: center;
		}

		/* Responsive de emergencia (móviles pequeños) */
		@media (max-width: 768px) {
			.divContainer.dashboard-grid {
				display: block;
				overflow-y: auto;
				height: auto;
			}

			canvas {
				height: 200px !important;
			}
		}
	</style>
</head>

<body>

	<?php
        include "includes/header.php";
    include '../conexion.php';

    $query_dash = mysqli_query($conection, "CALL dataDashboard();");
    $result_das = mysqli_num_rows($query_dash);

    if ($result_das > 0) {
        // Primer conjunto de resultados: Datos generales
        $data_dash = mysqli_fetch_assoc($query_dash);

        // Avanzar al segundo conjunto de resultados
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

        // Avanzar al cuarto conjunto de resultados
        mysqli_next_result($conection);

        // Cuarto conjunto de resultados: Productos menos vendidos
        $productos_menos_vendidos = [];
        if ($result = mysqli_store_result($conection)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $productos_menos_vendidos[] = $row;
            }
        }

        mysqli_close($conection);
    }

    print_r($data_dash);
    ?>

	<section class="divContainer dashboard-grid">

		<!-- TÍTULO -->
		<h1 class="titlePanelControl">📊 Panel de Control</h1>

		<!-- MÉTRICAS -->
		<div class="grid-card-metrics">
			<?php if ($_SESSION['rol'] == 1) { ?>
			<div class="card-dashboard">
				<a href="lista_usuarios.php">
					<i class="fas fa-users"></i>
					<h3>Usuarios</h3>
					<span><?= $data_dash['total_usuarios']; ?></span>
				</a>
			</div>
			<?php } ?>

			<div class="card-dashboard">
				<a href="lista_clientes.php">
					<i class="fas fa-user"></i>
					<h3>Clientes</h3>
					<span><?= $data_dash['total_clientes']; ?></span>
				</a>
			</div>

			<div class="card-dashboard">
				<a href="lista_producto.php">
					<i class="fas fa-cubes"></i>
					<h3>Productos</h3>
					<span><?= $data_dash['total_productos']; ?></span>
				</a>
			</div>

			<div class="card-dashboard">
				<a href="ventas.php">
					<i class="fas fa-cash-register"></i>
					<h3>Ventas del Día</h3>
					<span><?= $data_dash['ventas_hoy']; ?></span>
				</a>
			</div>
		</div>

		<!-- GRÁFICOS -->
		<div class="grid-charts">
			<div class="chart-container">
				<canvas id="ventasChart"></canvas>
			</div>
			<div class="chart-container">
				<canvas id="salariosChart"></canvas>
			</div>
		</div>

		<!-- GRÁFICO COMBINADO Y TABLAS -->
		<div class="grid-tables">

			<div class="chart-container-full">
				<canvas id="ventasSalariosChart"></canvas>
			</div>

			<div class="productos-box">
				<h3>🔝 Productos Más Vendidos</h3>
				<table class="productos-table">
					<thead>
						<tr>
							<th>#</th>
							<th>Producto</th>
							<th>Total</th>
						</tr>
					</thead>
					<tbody>
						<?php $rank = 1;
    foreach ($productos_mas_vendidos as $producto) { ?>
						<tr>
							<td><?= $rank++; ?></td>
							<td><?= $producto['nombre_producto']; ?>
							</td>
							<td><?= $producto['total_vendidos']; ?>
							</td>
						</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>

			<div class="productos-box">
				<h3>🔻 Productos Menos Vendidos</h3>
				<table class="productos-table">
					<thead>
						<tr>
							<th>#</th>
							<th>Producto</th>
							<th>Total</th>
						</tr>
					</thead>
					<tbody>
						<?php $rank = 1;
    foreach ($productos_menos_vendidos as $producto) { ?>
						<tr>
							<td><?= $rank++; ?></td>
							<td><?= $producto['nombre_producto']; ?>
							</td>
							<td><?= $producto['total_vendidos']; ?>
							</td>
						</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>

		</div>
	</section>





	<?php include "includes/footer.php"; ?>
</body>

</html>
<script>
	// Datos para los gráficos
	const chartData = <?= json_encode($chartData); ?> ;

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
				legend: {
					position: 'top'
				},
				title: {
					display: true,
					text: 'Ventas por Día'
				}
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
				legend: {
					position: 'top'
				},
				title: {
					display: true,
					text: 'Salarios'
				}
			}
		}
	});


	// Configuración del gráfico combinado
	new Chart(document.getElementById('ventasSalariosChart'), {
		type: 'line',
		data: {
			labels: labels,
			datasets: [{
					label: 'Ventas Diarias',
					data: ventas,
					borderColor: 'rgba(75, 192, 192, 1)',
					backgroundColor: 'rgba(75, 192, 192, 0.2)',
					borderWidth: 2,
					tension: 0.3
				},
				{
					label: 'Salarios',
					data: salarios,
					borderColor: 'rgba(255, 99, 132, 1)',
					backgroundColor: 'rgba(255, 99, 132, 0.2)',
					borderWidth: 2,
					tension: 0.3
				}
			]
		},
		options: {
			responsive: true,
			plugins: {
				legend: {
					position: 'top'
				},
				title: {
					display: true,
					text: 'Comparación de Ventas y Salarios Diarios'
				}
			},
			scales: {
				y: {
					beginAtZero: true
				}
			}
		}
	});
</script>

</script>

<?php
} else {


    header('location: ../index2.php');
    session_destroy();
}
?>