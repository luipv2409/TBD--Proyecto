<?php
// Nos conectamos a la base de datos para empezar.
require_once 'includes/db_connection.php';

// Esta parte se ejecuta si se hace clic en uno de los botones (Confirmar o Cancelar).
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_cita'])) {
    $id_cita = $_POST['id_cita'];
    $nuevo_estado = $_POST['nuevo_estado'];

    // Preparamos una consulta segura para actualizar el estado.
    $sql_update = "UPDATE cita SET estado = ? WHERE id_cita = ?";
    $stmt = $pdo->prepare($sql_update);
    
    // Ejecutamos la actualización.
    if ($stmt->execute([$nuevo_estado, $id_cita])) {
        // Si todo sale bien, recargamos la página para ver el cambio.
        header("Location: gestionar_citas.php");
        exit;
    } else {
        $error_message = "Hubo un error al actualizar el estado de la cita.";
    }
}

// Esta parte se ejecuta siempre para mostrar la lista de citas.
// Buscamos todas las citas del día de hoy.
try {
    $sql_citas_hoy = "SELECT 
                        c.id_cita, c.hora_cita, c.estado,
                        CONCAT(p.nombre, ' ', p.apellido) AS nombre_paciente,
                        CONCAT(m.nombre, ' ', m.apellido) AS nombre_medico
                     FROM cita c
                     JOIN paciente p ON c.id_paciente = p.id_paciente
                     JOIN medico m ON c.id_medico = m.id_medico
                     WHERE c.fecha_cita = CURDATE()
                     ORDER BY c.hora_cita ASC";
    $citas_de_hoy = $pdo->query($sql_citas_hoy)->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error al cargar las citas del día: " . $e->getMessage();
    $citas_de_hoy = [];
}

include 'includes/header.php';
?>

<h2>Gestionar Citas del Día (<?php echo date('d/m/Y'); ?>)</h2>
<p>Aquí puedes confirmar la asistencia de los pacientes o cancelar sus citas.</p>

<?php if (!empty($error_message)): ?>
    <div class="message error"><?php echo $error_message; ?></div>
<?php endif; ?>

<table class="report-table">
    <thead>
        <tr>
            <th>Hora</th>
            <th>Paciente</th>
            <th>Médico</th>
            <th>Estado Actual</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($citas_de_hoy) > 0): ?>
            <?php foreach ($citas_de_hoy as $cita): ?>
                <tr>
                    <td><?php echo htmlspecialchars(date('h:i A', strtotime($cita['hora_cita']))); ?></td>
                    <td><?php echo htmlspecialchars($cita['nombre_paciente']); ?></td>
                    <td><?php echo htmlspecialchars($cita['nombre_medico']); ?></td>
                    <td><strong><?php echo htmlspecialchars(ucfirst($cita['estado'])); ?></strong></td>
                    <td>
                        <?php
                        // Novedad: La lógica ahora está aquí adentro.
                        if ($cita['estado'] == 'pendiente') {
                            // Agarramos la hora actual de Bolivia.
                            $hora_actual = new DateTime("now", new DateTimeZone('America/La_Paz'));
                            $hora_cita = new DateTime($cita['hora_cita']);

                            // Comparamos si la hora de la cita ya pasó.
                            if ($hora_cita < $hora_actual) {
                                // Si ya pasó, mostramos "No Asistió".
                                echo '<span class="status-ausente">No Asistió</span>';
                            } else {
                                // Si todavía no es la hora, recién mostramos los botones.
                                ?>
                                <form action="gestionar_citas.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="id_cita" value="<?php echo $cita['id_cita']; ?>">
                                    <input type="hidden" name="nuevo_estado" value="atendida">
                                    <button type="submit" class="btn-confirmar">Confirmar Asistencia</button>
                                </form>
                                <form action="gestionar_citas.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="id_cita" value="<?php echo $cita['id_cita']; ?>">
                                    <input type="hidden" name="nuevo_estado" value="cancelada">
                                    <button type="submit" class="btn-cancelar">Cancelar</button>
                                </form>
                                <?php
                            }
                        } else {
                            // Si la cita ya está 'atendida' o 'cancelada', no mostramos nada.
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" style="text-align:center;">No hay citas programadas para el día de hoy.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<a href="index.php" class="btn-secondary" style="display:inline-block; margin-top: 20px;">Volver al Menú Principal</a>

<?php include 'includes/footer.php'; ?>