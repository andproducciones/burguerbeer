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
    <title>Tarifas Extras</title>
    <?php include "includes/scripts.php"; ?>
</head>

<body>
    <?php include "includes/header.php"; ?>

    <section id="container">
        <h1><i class="fas fa-utensils"></i> Tarifas Extras</h1>

        <button class="btn_new anadirForm" ac="formTarifaExtra">
            <i class="fas fa-plus-circle"></i> Nueva Tarifa Extra
        </button>

        <table id="myTable">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Valor</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
        $sql = mysqli_query($conection, "SELECT * FROM tarifa_extras ORDER BY tipo_extra ASC");
while ($row = mysqli_fetch_assoc($sql)) { ?>
                <tr>
                    <td><?= ucfirst($row['tipo_extra']) ?>
                    </td>
                    <td>$<?= number_format($row['valor'], 2) ?>
                    </td>
                    <td><span
                            class="estado"><?= $row['habilitado'] ? 'Activo' : 'Inactivo' ?></span>
                    </td>
                    <td>
                        <button class="btn btn_editar anadirForm" ac="formEditarTarifaExtra"
                            co="<?= $row['id'] ?>">
                            <i class="fas fa-pen"></i>
                        </button>
                        <?php if ($row['habilitado']) { ?>
                        <button class="btn btn_eliminar"
                            onclick="eliminarTarifaExtra(<?= $row['id'] ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php } else { ?>
                        <button class="btn btn_activar"
                            onclick="activarTarifaExtra(<?= $row['id'] ?>)">
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
        function eliminarTarifaExtra(id) {
            Swal.fire({
                title: '¿Desactivar tarifa?',
                text: 'Esta acción la ocultará del sistema',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, desactivar',
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('ajax.php', {
                        action: 'eliminarTarifaExtra',
                        id: id
                    }, function(response) {
                        if (response.trim() === 'ok') {
                            Swal.fire('Desactivada', '', 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', response, 'error');
                        }
                    });
                }
            });
        }

        function activarTarifaExtra(id) {
            $.post('ajax.php', {
                action: 'activarTarifaExtra',
                id: id
            }, function(response) {
                if (response.trim() === 'ok') {
                    Swal.fire('Activada', '', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', response, 'error');
                }
            });
        }

        function guardarTarifaExtra() {
            const form = document.getElementById('form_tarifa_extra');
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
                .catch(() => Swal.fire('Error', 'Error de conexión con el servidor', 'error'));
        }

        function actualizarTarifaExtra() {
            const form = document.getElementById('form_editar_tarifa_extra');
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
                .catch(() => Swal.fire('Error', 'Error de conexión con el servidor', 'error'));
        }
    </script>
</body>

</html>