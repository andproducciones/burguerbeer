<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] == 3) {
    header('location: ../index2.php');
    session_destroy();
    exit('Acceso restringido');
}
include '../conexion.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Habitaciones</title>
    <?php include "includes/scripts.php"; ?>
</head>

<body>
    <?php include "includes/header.php"; ?>

    <section id="container">
        <h1><i class="fas fa-bed"></i> Habitaciones</h1>

        <?php if ($_SESSION['rol'] <= 2) { ?>
        <button type="button" class="anadirForm btn_new" ac="formHabitacion">
            <i class="fas fa-plus-circle"></i> Nueva Habitación
        </button>
        <?php } ?>

        <table id="myTable">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Tipo</th>
                    <th>Descripción</th>
                    <th>Piso</th>
                    <th>Capacidad</th>
                    <th>Precio</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = mysqli_query($conection, "
    SELECT 
        h.idhabitacion,
        h.numero,
        h.descripcion,
        h.piso,
        h.capacidad,
        h.precio,
        h.estado,
        h.habilitada,
        t.nombre AS tipo
    FROM habitaciones h
    INNER JOIN tipo_habitacion t ON h.id_tipo = t.id_tipo
    ORDER BY h.numero ASC
");

while ($row = mysqli_fetch_assoc($query)) {
    ?>
                <tr>
                    <td><?= htmlspecialchars($row['numero']) ?>
                    </td>
                    <td><?= htmlspecialchars($row['tipo']) ?>
                    </td>
                    <td><?= htmlspecialchars($row['descripcion']) ?>
                    </td>
                    <td><?= htmlspecialchars($row['piso']) ?>
                    </td>
                    <td><?= intval($row['capacidad']) ?>
                    </td>
                    <td>$<?= number_format($row['precio'], 2) ?>
                    </td>
                    <td><span
                            class="estado <?= $row['estado'] ?>"><?= ucfirst($row['estado']) ?></span>
                    </td>
                    <td>
                        <?php if ($row['habilitada'] == 1): ?>
                        <button class="btn btn_editar anadirForm"
                            co="<?= $row['idhabitacion'] ?>"
                            ac="formEditarHabitacion">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button class="btn btn_eliminar"
                            onclick="eliminarHabitacion(<?= $row['idhabitacion'] ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php else: ?>
                        <button class="btn btn_activar"
                            onclick="activarHabitacion(<?= $row['idhabitacion'] ?>)">
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
        function eliminarHabitacion(id) {
            Swal.fire({
                title: '¿Desactivar habitación?',
                text: "La habitación no se eliminará definitivamente, solo se desactivará.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, desactivar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('ajax.php', {
                        action: 'eliminarHabitacion',
                        idhabitacion: id
                    }, function(response) {
                        if (response.trim() === 'OK') {
                            Swal.fire('Desactivada', 'La habitación fue desactivada correctamente.',
                                    'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Error', response, 'error');
                        }
                    });
                }
            });
        }



        function guardarHabitacion() {
            const form = document.getElementById('form_habitacion');
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
                            title: 'Habitación guardada correctamente',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Error', response, 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'Error de conexión con el servidor', 'error'));
        }


        function actualizarHabitacion() {
            const form = document.getElementById('form_editar_habitacion');
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
                            title: 'Habitación actualizada correctamente',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Error', response, 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'Error de conexión con el servidor', 'error'));
        }

        function activarHabitacion(id) {
            Swal.fire({
                title: '¿Activar habitación?',
                text: "Esto restaurará la habitación al estado activo.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, activar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('ajax.php', {
                        action: 'activarHabitacion',
                        idhabitacion: id
                    }, function(response) {
                        if (response.trim() === 'OK') {
                            Swal.fire('Activada', 'La habitación ha sido activada correctamente.',
                                    'success')
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