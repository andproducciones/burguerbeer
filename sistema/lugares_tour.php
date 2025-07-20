<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] > 2) {
    header('location: ../index2.php');
    exit;
}
include "../conexion.php";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Lugares Turísticos</title>
    <?php include "includes/scripts.php"; ?>
</head>

<body>
    <?php include "includes/header.php"; ?>

    <section id="container">
        <h1><i class="fas fa-map-marker-alt"></i> Lugares Turísticos</h1>

        <button class="btn_new anadirForm" ac="formLugarTour">
            <i class="fas fa-plus-circle"></i> Nuevo Lugar
        </button>

        <table id="myTable">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = mysqli_query($conection, "SELECT * FROM lugares_tour ORDER BY nombre ASC");
while ($row = mysqli_fetch_assoc($query)) {
    ?>
                <tr>
                    <td><?= htmlspecialchars($row['nombre']) ?>
                    </td>
                    <td><span
                            class="estado <?= $row['activo'] ? 'activo' : 'inactivo' ?>">
                            <?= $row['activo'] ? 'Activo' : 'Inactivo' ?>
                        </span></td>
                    <td>
                        <?php if ($row['activo']) { ?>
                        <button class="btn btn_editar anadirForm" ac="formEditarLugarTour"
                            co="<?= $row['id'] ?>">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button class="btn btn_eliminar"
                            onclick="eliminarLugarTour(<?= $row['id'] ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php } else { ?>
                        <button class="btn btn_activar"
                            onclick="activarLugarTour(<?= $row['id'] ?>)">
                            <i class="fas fa-check"></i>
                        </button>
                        <?php } ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </section>

    <?php include "includes/footer.php"; ?>

    <script>
        function eliminarLugarTour(id) {
            Swal.fire({
                title: '¿Eliminar lugar?',
                text: "Esto lo desactivará del sistema",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('ajax.php', {
                        action: 'eliminarLugarTour',
                        id: id
                    }, function(response) {
                        if (response.trim() === 'ok') {
                            Swal.fire('Eliminado', 'El lugar fue desactivado', 'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Error', response, 'error');
                        }
                    });
                }
            });
        }

        function activarLugarTour(id) {
            Swal.fire({
                title: '¿Activar lugar turístico?',
                text: "El lugar será visible nuevamente en el sistema",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, activar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('ajax.php', {
                        action: 'activarLugarTour',
                        id: id
                    }, function(response) {
                        if (response.trim() === 'ok') {
                            Swal.fire('Activado', 'El lugar fue activado correctamente', 'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Error', response, 'error');
                        }
                    });
                }
            });
        }


        function guardarLugarTour() {
            const form = document.getElementById('form_lugar_tour');
            const formData = new FormData(form);

            fetch('ajax.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.text())
                .then(response => {
                    if (response.trim() === 'ok') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Lugar turístico guardado correctamente',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Error', response, 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'Error de conexión con el servidor', 'error'));
        }

        function actualizarLugarTour() {
            const form = document.getElementById('form_editar_lugar_tour');
            const formData = new FormData(form);

            fetch('ajax.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.text())
                .then(response => {
                    if (response.trim() === 'ok') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Lugar turístico actualizado correctamente',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Error', response, 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'Error de conexión con el servidor', 'error'));
        }
    </script>
</body>

</html>