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
    <title>Tarifas</title>
    <?php include "includes/scripts.php"; ?>
</head>

<body>
    <?php include "includes/header.php"; ?>

    <section id="container">
        <h1><i class="fas fa-dollar-sign"></i> Tarifas del Hotel</h1>

        <button class="btn_new anadirForm" ac="formTarifaHabitacion">
            <i class="fas fa-plus-circle"></i> Nueva Tarifa
        </button>

        <table id="myTable">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Precio</th>
                    <th>habilitada</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
            $sql = mysqli_query($conection, "SELECT * FROM tarifas_habitaciones  ORDER BY nombre ASC");
while ($row = mysqli_fetch_assoc($sql)) {
    ?>
                <tr>
                    <td><?= htmlspecialchars($row['nombre']) ?>
                    </td>
                    <td><?= htmlspecialchars($row['descripcion']) ?>
                    </td>
                    <td>$<?= number_format($row['precio_por_persona'], 2) ?>
                    </td>
                    <td><span
                            class="habilitada"><?= $row['habilitada'] == 1 ? 'Activo' : 'Inactivo' ?></span>
                    </td>
                    <td>
                        <?php if ($row['habilitada'] == 1): ?>
                        <!-- Botón editar solo si está habilitada -->
                        <button class="btn btn_editar anadirForm" ac="formEditarTarifaHabitacion"
                            co="<?= $row['id'] ?>">
                            <i class="fas fa-pen"></i>
                        </button>

                        <!-- Botón desactivar -->
                        <button class="btn btn_eliminar"
                            onclick="eliminarTarifa(<?= $row['id'] ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php else: ?>
                        <!-- Botón activar si está deshabilitada -->
                        <button class="btn btn_activar"
                            onclick="activarTarifa(<?= $row['id'] ?>)">
                            <i class="fas fa-check"></i>
                        </button>
                        <?php endif; ?>
                    </td>

                </tr>
                <?php } ?>
            </tbody>
        </table>
    </section>

    <?php include "includes/footer.php"; ?>

    <script>
        function eliminarTarifa(id) {
            Swal.fire({
                title: '¿Eliminar tarifa?',
                text: "Esto la desactivará del sistema",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('ajax.php', {
                        action: 'eliminarTarifaHabitacion',
                        id: id
                    }, function(response) {
                        if (response.trim() === 'ok') {
                            Swal.fire('Eliminada', 'La tarifa fue eliminada', 'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Error', response, 'error');
                        }
                    });
                }
            });
        }


        function guardarTarifaHabitacion() {
            const form = document.getElementById('form_tarifa');
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
                            title: 'Tarifa guardada correctamente',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Error', response, 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'No se pudo guardar la tarifa', 'error'));
        }


        function actualizarTarifaHabitacion() {
            const form = document.getElementById('form_editar_tarifa');
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
                            title: 'Tarifa actualizada correctamente',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Error', response, 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'No se pudo actualizar la tarifa', 'error'));
        }

        function activarTarifa(id) {
            Swal.fire({
                title: '¿Activar tarifa?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, activar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('ajax.php', {
                        action: 'activarTarifaHabitacion',
                        id: id
                    }, function(response) {
                        if (response.trim() === 'ok') {
                            Swal.fire('Activada', 'La tarifa fue activada', 'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Error', response, 'error');
                        }
                    });
                }
            });
        }
    </script>
</body>

</html>