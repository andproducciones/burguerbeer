<?php  
session_start();

include '../conexion.php';

?>


<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<?php include "includes/scripts.php"; ?>
	<title>Lista de Atributos</title>
</head>
<body>
	<?php include "includes/header.php"; ?>

	<section id="container"> 

		<h1>Lista de Atributos</h1>
		<?php
					if($_SESSION['rol'] == 1){
 					?>
		<button type="button" class="btn_new anadirForm" ac="formCategoria"><i class="fas fa-plus"></i> Crear Producto</button>
		<?php } ?>

		<table id="myTable">
			<thead>
			<tr>
				<th class="textcenter wd5" >Código</th>
				<th class="textcenter">Atributo</th>
	
				<?php if($_SESSION["rol"] == 1){?>
				<th class="wd20">Acciones</th>
				<?php } ?>
			</tr>
			</thead>
			<?php
		
			
			$query = mysqli_query($conection,"SELECT a.id,a.atributo FROM atributos_productos a
				");
			mysqli_close($conection);
			
			$result = mysqli_num_rows($query);
			
			if($result > 0){

				while ($data= mysqli_fetch_array($query)){

					

			?>
			
			<tr class="row<?php echo $data["id"]?>">
				<td class="textcenter"><?php echo $data["id"]?></td>
				<td class="textcenter"><?php echo $data["atributo"]?></td>
				
				<td align="center">
					<button class="btn_view anadirForm" co="<?php echo $data["id"]; ?>" ac="formAddTipo"><i class="fas fa-plus"></i></button>
					<button class="btn_view anadirForm" co="<?php echo $data["id"]; ?>" ac="formEditarAributo"><i class="far fa-edit"></i></button>
					<button class="btn_anular anadirForm" co="<?php echo $data["id"]; ?>" ac="formElimAtributo"><i class="far fa-trash-alt"></i></button>
				
				</td>
			</tr>
			<?php 
				}
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
