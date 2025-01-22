<?php  
session_start();

include '../conexion.php';

?>


<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<?php include "includes/scripts.php"; ?>
	<title>Lista de Productos</title>
</head>
<body>
	<?php include "includes/header.php"; ?>

	<section id="container"> 

		<h1>Lista de Productos</h1>
		<?php
					if($_SESSION['rol'] == 1){
 					?>
		<button type="button" class="btn_new anadirForm" ac="formProducto"><i class="fas fa-plus"></i> Crear Producto</button>
		<?php } ?>

		<table id="myTable">
			<thead>
			<tr>
				<th>Código</th>
				<th>Producto</th>
				<th>PVP 1</th>
				<th>Categoria</th>
				<th>Atributo</th>
				<th>Atributos</th>
				<?php if($_SESSION["rol"] == 1){?>
				<th>Acciones</th>
				<?php } ?>
			</tr>
			</thead>
			<?php

// Consulta principal para obtener los productos
$query = mysqli_query($conection, "
    SELECT 
        p.codproducto, p.costo, p.producto, p.precio, p.precio2, p.precio3, p.existencia,
        c.categoria, l.lugar, p.codatributos
    FROM 
        producto p
    INNER JOIN 
        categorias c ON c.id = p.categoria
    INNER JOIN 
        lugar l ON l.id = p.lugar
    ORDER BY 
        p.codproducto
");
//mysqli_close($conection);

// Verificar si hay resultados
$result = mysqli_num_rows($query);

if ($result > 0) {
    while ($data = mysqli_fetch_array($query)) {
        $codproducto = $data["codproducto"];
        $producto = htmlspecialchars($data["producto"], ENT_QUOTES, 'UTF-8');
        $precio = $data["precio"];
        $categoria = htmlspecialchars($data["categoria"], ENT_QUOTES, 'UTF-8');
        $codatributos = $data["codatributos"];

        // Procesar atributos
        $atributos = '';
        if (!empty($codatributos)) {
            $ids_array = array_map('intval', explode(",", $codatributos));
            $ids_string = implode(",", $ids_array);

            // Obtener todos los atributos
            $query_atributos = mysqli_query($conection, "
                SELECT 
                    id, atributo 
                FROM 
                    atributos_productos 
                WHERE 
                    id IN ($ids_string)
            ");

            $atributos_data = [];
            if ($query_atributos && mysqli_num_rows($query_atributos) > 0) {
                while ($data_atributo = mysqli_fetch_assoc($query_atributos)) {
                    $atributo = htmlspecialchars($data_atributo['atributo'], ENT_QUOTES, 'UTF-8');
                    $atributos_data[] = $atributo;
                }
            }

            // Unir atributos separados por comas
            $atributos = implode(", ", $atributos_data);
        }
        ?>
        <tr class="row<?php echo $codproducto ?>">
            <td><?php echo $codproducto ?></td>
            <td><?php echo $producto ?></td>
            <td class="celPrecio">$ <?php echo $precio ?></td>
            <td><?php echo $categoria ?></td>
            <td><?php echo $atributos ?></td>
            <td></td>
            <td align="center">
                <button class="btn_view anadirForm" ac="formAnadirAtributo" co="<?php echo $codproducto; ?>"><i class="fas fa-plus"></i></button>
                <button class="btn_view anadirForm" ac="formEditarProducto" co="<?php echo $codproducto; ?>"><i class="far fa-edit"></i></button>			
                <button class="btn_anular anadirForm" ac="formEliminarProducto" co="<?php echo $codproducto; ?>"><i class="far fa-trash-alt"></i></button>
            </td>
        </tr>
        <?php
    }
} else {
    echo '<tr><td colspan="7">No se encontraron productos.</td></tr>';
}
?>




		</table>
	
	</section>

<?php include "includes/footer.php"; ?>
</body>
</html>

<script>
    var contadorAtributos = 1;

    function agregarAtributo() {
        var container = document.getElementById("atributosContainer");
        var nuevoAtributoDiv = document.getElementById("nuevoAtributo");
        var clone = nuevoAtributoDiv.cloneNode(true);
        var select = clone.querySelector("select");

        // Limpiar el valor seleccionado
        var selectedOption = select.querySelector("option[selected]");
        if (selectedOption) {
            select.removeChild(selectedOption);
        }

        // Crear un objeto para almacenar los valores únicos
        var valoresUnicos = {};

        // Recorrer todas las opciones para eliminar duplicados
        for (var i = 0; i < select.options.length; i++) {
            var option = select.options[i];
            valoresUnicos[option.textContent] = option.value;
        }

        // Eliminar todas las opciones actuales
        select.innerHTML = '';

        // Agregar la opción por defecto "Seleccione"
        var defaultOption = document.createElement("option");
        defaultOption.text = "Seleccione";
        defaultOption.value = 0;
        select.appendChild(defaultOption);

        // Agregar las opciones únicas al select
        for (var key in valoresUnicos) {
            var nuevaOption = document.createElement("option");
            nuevaOption.text = key;
            nuevaOption.value = valoresUnicos[key];
            select.appendChild(nuevaOption);
        }

        // Seleccionar la opción por defecto "Seleccione"
        select.selectedIndex = 0;

        // Añadir número al atributo
        var label = clone.querySelector("label");
        label.textContent = "Atributo " + contadorAtributos;

        // Incrementar el contador
        contadorAtributos++;

        // Mostrar el nuevo atributo clonado
        clone.style.display = "flex";

        // Agregar el nuevo div clonado al contenedor de atributos
        container.appendChild(clone);

        // Mostrar el botón de nuevo atributo si se había ocultado
        document.getElementById("nuevoAtributo").style.display = "flex";
    }

    function eliminarAtributo(button) {
        var divPadre = button.parentNode;
        divPadre.parentNode.removeChild(divPadre);

        // Decrementar el contador si hay más de 1 atributo visible
        if (contadorAtributos > 2) {
            contadorAtributos--;
        }

        // Ocultar el botón si solo queda un atributo visible
        if (contadorAtributos === 2) {
            document.getElementById("nuevoAtributoBtn").style.display = "none";
        }
    }

    function resetContador() {
        contadorAtributos = 1;
    }
</script>
