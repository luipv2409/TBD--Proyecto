<?php
// Primero lo primero, nos conectamos a la base de datos.
require_once 'includes/db_connection.php';

$error_message = '';
$success_message = '';

// Si el usuario envió el formulario...
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_paciente = $_POST['id_paciente'];
    $id_medico = $_POST['id_medico'];
    $fecha_cita = $_POST['fecha_cita'];
    $hora_cita = $_POST['hora_cita'];

    // Validamos que los campos no estén vacíos.
    if (empty($id_paciente) || empty($id_medico) || empty($fecha_cita) || empty($hora_cita)) {
        $error_message = "Todos los campos son obligatorios, pues.";
    } else {
        // Validamos las fechas.
        $fecha_seleccionada = new DateTime($fecha_cita);
        $fecha_actual = new DateTime('today');
        $fecha_maxima = (new DateTime('today'))->modify('+3 months');

        // Novedad: Definimos el horario de atención.
        $hora_apertura = '08:00';
        $hora_cierre = '18:00';

        if ($fecha_seleccionada < $fecha_actual) {
            $error_message = "¡No puedes agendar una cita en el pasado, che!";
        } elseif ($fecha_seleccionada > $fecha_maxima) {
            $error_message = "No se puede agendar con más de 3 meses de anticipación.";
        // Novedad: Validamos que la hora esté dentro del horario de atención.
        } elseif ($hora_cita < $hora_apertura || $hora_cita > $hora_cierre) {
            $error_message = "El horario de atención es de 8:00 AM a 6:00 PM.";
        } else {
            // Si todo está en orden, recién intentamos guardar.
            try {
                $stmt = $pdo->prepare("CALL sp_cita_agendar(?, ?, ?, ?, @resultado)");
                $stmt->bindParam(1, $id_paciente, PDO::PARAM_INT);
                $stmt->bindParam(2, $id_medico, PDO::PARAM_INT);
                $stmt->bindParam(3, $fecha_cita, PDO::PARAM_STR);
                $stmt->bindParam(4, $hora_cita, PDO::PARAM_STR);
                $stmt->execute();

                $sql_resultado = $pdo->query("SELECT @resultado AS resultado")->fetch(PDO::FETCH_ASSOC);
                $mensaje_bd = $sql_resultado['resultado'];

                if (strpos($mensaje_bd, 'exitosamente') !== false) {
                    $success_message = $mensaje_bd;
                } else {
                    $error_message = $mensaje_bd;
                }
            } catch (PDOException $e) {
                $error_message = "Error al agendar la cita: " . $e->getMessage();
            }
        }
    }
}


// Para los menús desplegables, buscamos pacientes y médicos.
try {
    $pacientes = $pdo->query("SELECT id_paciente, CONCAT(nombre, ' ', apellido) AS nombre_completo FROM paciente ORDER BY nombre_completo ASC")->fetchAll();
    
    $sql_medicos = "SELECT 
                        m.id_medico, 
                        CONCAT(m.nombre, ' ', m.apellido, ' - ', e.nombre_especialidad) AS medico_con_especialidad
                    FROM medico m
                    JOIN especialidad e ON m.id_especialidad = e.id_especialidad
                    ORDER BY m.nombre ASC";
    $medicos = $pdo->query($sql_medicos)->fetchAll();

} catch (PDOException $e) {
    $error_message = "No se pudieron cargar los datos de pacientes o médicos: " . $e->getMessage();
    $pacientes = [];
    $medicos = [];
}
?>

<?php include 'includes/header.php'; ?>

<h2>Agendar Nueva Cita</h2>
<p>Seleccione el paciente, el médico y la fecha/hora deseada para la cita.</p>

<?php if (!empty($success_message)): ?>
    <div class="message success"><?php echo $success_message; ?></div>
<?php endif; ?>
<?php if (!empty($error_message)): ?>
    <div class="message error"><?php echo $error_message; ?></div>
<?php endif; ?>

<form action="agendar_cita.php" method="POST" class="data-form">
    <div class="form-group"><label for="id_paciente">Paciente:</label><select id="id_paciente" name="id_paciente" required><option value="">-- Seleccione un paciente --</option><?php foreach ($pacientes as $paciente): ?><option value="<?php echo htmlspecialchars($paciente['id_paciente']); ?>"><?php echo htmlspecialchars($paciente['nombre_completo']); ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label for="id_medico">Médico:</label><select id="id_medico" name="id_medico" required><option value="">-- Seleccione un médico --</option><?php foreach ($medicos as $medico): ?><option value="<?php echo htmlspecialchars($medico['id_medico']); ?>"><?php echo htmlspecialchars($medico['medico_con_especialidad']); ?></option><?php endforeach; ?></select></div>

    <div class="form-group">
        <label for="fecha_cita">Fecha de la Cita:</label>
        <input type="date" id="fecha_cita" name="fecha_cita" required 
               min="<?php echo date('Y-m-d'); ?>" 
               max="<?php echo date('Y-m-d', strtotime('+3 months')); ?>">
    </div>

    <div class="form-group">
        <label for="hora_cita">Hora de la Cita:</label>
        <input type="time" id="hora_cita" name="hora_cita" required
               min="08:00" max="18:00" step="1800">
    </div>

    <div class="form-actions">
        <button type="submit" class="btn">Agendar Cita</button>
        <a href="index.php" class="btn-secondary">Volver al Menú</a>
    </div>
</form>

<?php include 'includes/footer.php'; ?>

// MOTOR DE BUSQUEDA Y ORDENAR POR APELLIDO, CONTROLAR LA HORA A LA QUE SE AGENDA, NO SE PUEDE AGENDAR HORAS PASADAS