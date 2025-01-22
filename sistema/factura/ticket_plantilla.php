<!DOCTYPE html>
<html>
<head>
	<style type="text/css">
		* {
			font-size: 14px;
			font-family: 'Arial';
		}

		td,
		th,
		tr,
		table {

			border-collapse: collapse;
			width: 100%;
		}


		td.info,
		th.info {
			width: 80px;
			max-width: 80px;
		}
		td.producto,
		th.producto {
			width: 220px;
			max-width: 220px;
		}

		td.cantidad,
		th.cantidad {
			width: 40px;
			max-width: 40px;
			word-break: break-all;
		}

		.centrado {
			text-align: center;
			align-content: center;
		}

		.ticket {
			width: 270px;
			max-width: 285px;
			border: 1px solid;
			margin: 5px;
		}

		p{
			padding: 0px;
			margin: 3px;
		}

		.font10{
			font-size: 10px;

		}
		.font16{
			font-size: 16px;

		}

	</style>
</head>
<body>
	<div class="ticket">
		<p class="centrado">
		<b>BURGUERBEER</b></p>
		<table class="informacion">
			<tbody>
				<tr>
					<td class="info"><b>MESERO</b></td>
					<td class="producto">Francis Fiallos</td>
				</tr>

				<tr>
					<td class="info"><b>FECHA</b></td>
					<td class="producto">23/08/2017 08:22 a.m.</td>
				</tr>

				<tr>
					<th colspan="2" class="font16">MESA #1 O PARA LLEVAR</th>
				</tr>
			</tbody>

		</table>
		<p class="centrado">================================</p>
		<table>
			<thead>
				<tr>
					<th colspan="2">PRODUCTOS A PREPARAR</th>
				</tr>
			</thead>

			<tbody>
				<tr>
					<td class="cantidad centrado">1</td>
					<td class="producto">HAMBUGUESA</td>

				</tr>
				<tr>
					<td colspan="2" class="centrado font10">SIN TOMATE, SIN CEBOLLA, SIN LECHUGA</td>
				</tr>
				
				<tr>
					<td class="cantidad centrado">2</td>
					<td class="producto">KINDER DELICE</td>

				</tr>
				
				<tr>
					<td class="cantidad centrado">1</td>
					<td class="producto">COCA COLA 600 ML</td>

				</tr>
			</tbody>
		</table>
		<p class="centrado">================================</p>
	</div>
</body>
</html>

