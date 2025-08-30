<?php
// Primero lo primero, nos conectamos a la base de datos.
require_once 'includes/db_connection.php';

$error_message = '';
$success_message = '';

// --- LÓGICA DEL FORMULARIO ---

// Jalamos los filtros de búsqueda y orden de la URL (GET o POST) por si existen.
$search_term = isset($_REQUEST['search_term']) ? trim($_REQUEST['search_term']) : '';
$sort_by = isset($_REQUEST['sort_by']) ? $_REQUEST['sort_by'] : 'apellido';

// Si el usuario envió el formulario para AGENDAR (método POST)...
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_paciente = $_POST['id_paciente'];
    $id_medico = $_POST['id_medico'];
    $fecha_cita = $_POST['fecha_cita'];
    $hora_cita = $_POST['hora_cita'];

    // Validamos que los campos principales no estén vacíos.
    if (empty($id_paciente) || empty($id_medico) || empty($fecha_cita) || empty($hora_cita)) {
        $error_message = "Todos los campos son obligatorios, pues.";
    } else {
        // Preparamos todas las fechas y horas para las validaciones.
        $fecha_seleccionada = new DateTime($fecha_cita);
        $fecha_actual = new DateTime('today');
        $fecha_maxima = (new DateTime('today'))->modify('+3 months');
        $hora_apertura = '08:00';
        $hora_cierre = '18:00';
        $hora_actual_bolivia = new DateTime("now", new DateTimeZone('America/La_Paz'));
        $hora_actual_str = $hora_actual_bolivia->format('H:i');

        // Empezamos la cadena de validaciones.
        if ($fecha_seleccionada < $fecha_actual) {
            $error_message = "¡No puedes agendar una cita en el pasado, che!";
        } elseif ($fecha_seleccionada > $fecha_maxima) {
            $error_message = "No se puede agendar con más de 3 meses de anticipación.";
        } elseif ($hora_cita < $hora_apertura || $hora_cita > $hora_cierre) {
            $error_message = "El horario de atención es de 8:00 AM a 6:00 PM.";
        } elseif ($fecha_cita == $hora_actual_bolivia->format('Y-m-d') && $hora_cita < $hora_actual_str) {
            $error_message = "No puedes agendar una cita para una hora que ya pasó hoy.";
        } else {
            // Si todo está en orden, recién intentamos guardar en la base de datos.
            try {
                $stmt = $pdo->prepare("CALL sp_cita_agendar(?, ?, ?, ?, @resultado)");
                $stmt->execute([$id_paciente, $id_medico, $fecha_cita, $hora_cita]);
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

// --- LÓGICA PARA CARGAR LOS DATOS DEL FORMULARIO ---
try {
    // Jalamos la lista de especialidades para el primer menú.
    $especialidades = $pdo->query("SELECT id_especialidad, nombre_especialidad FROM especialidad ORDER BY nombre_especialidad ASC")->fetchAll();
    
    // Armamos la consulta dinámica para buscar y ordenar pacientes.
    $sql_pacientes_base = "SELECT id_paciente, CONCAT(nombre, ' ', apellido) AS nombre_completo FROM paciente";
    $params = [];
    if (!empty($search_term)) {
        $sql_pacientes_base .= " WHERE nombre LIKE ? OR apellido LIKE ?";
        $params[] = "%$search_term%";
        $params[] = "%$search_term%";
    }
    $order_clause = ($sort_by === 'nombre') ? " ORDER BY nombre ASC, apellido ASC" : " ORDER BY apellido ASC, nombre ASC";
    $sql_pacientes_base .= $order_clause;
    
    $stmt_pacientes = $pdo->prepare($sql_pacientes_base);
    $stmt_pacientes->execute($params);
    $pacientes = $stmt_pacientes->fetchAll();
    
    // Si la búsqueda da un solo resultado, lo preparamos para auto-selección.
    $paciente_seleccionado_id = (count($pacientes) === 1) ? $pacientes[0]['id_paciente'] : null;

} catch (PDOException $e) {
    $error_message = "Error al cargar los datos iniciales: " . $e->getMessage();
    $pacientes = []; 
    $especialidades = [];
}
?>

<?php include 'includes/header.php'; ?>

<h2>Agendar Nueva Cita</h2>
<p>Busca al paciente, elige la especialidad y luego selecciona al médico disponible.</p>

<?php if(!empty($success_message)): ?><div class="message success"><?php echo $success_message; ?></div><?php endif; ?>
<?php if(!empty($error_message)): ?><div class="message error"><?php echo $error_message; ?></div><?php endif; ?>

<div class="report-container">
    <h3>Buscar Paciente</h3>
    <form action="agendar_cita.php" method="GET" class="data-form">
        <div style="display: flex; gap: 15px; align-items: flex-end;">
            <div class="form-group" style="flex-grow: 1;">
                <label for="search_term">Buscar por Nombre o Apellido:</label>
                <input type="text" name="search_term" id="search_term" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Escribe aquí...">
            </div>
            <div class="form-group">
                <label for="sort_by">Ordenar por:</label>
                <select name="sort_by" id="sort_by">
                    <option value="apellido" <?php echo ($sort_by === 'apellido') ? 'selected' : ''; ?>>Apellido</option>
                    <option value="nombre" <?php echo ($sort_by === 'nombre') ? 'selected' : ''; ?>>Nombre</option>
                </select>
            </div>
            <div class="form-group"><button type="submit" class="btn">Buscar</button></div>
        </div>
    </form>
</div>

<form action="agendar_cita.php" method="POST" class="data-form">
    <input type="hidden" name="search_term" value="<?php echo htmlspecialchars($search_term); ?>">
    <input type="hidden" name="sort_by" value="<?php echo htmlspecialchars($sort_by); ?>">

    <div class="form-group">
        <label for="id_paciente">Paciente (Resultados de la búsqueda):</label>
        <select id="id_paciente" name="id_paciente" required>
            <option value="">-- Seleccione un paciente de la lista --</option>
            <?php foreach ($pacientes as $paciente): ?>
                <option value="<?php echo htmlspecialchars($paciente['id_paciente']); ?>" 
                        <?php if ($paciente_seleccionado_id == $paciente['id_paciente']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($paciente['nombre_completo']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="id_especialidad">Especialidad:</label>
        <select id="id_especialidad" name="id_especialidad" required>
            <option value="">-- Primero elige una especialidad --</option>
            <?php foreach ($especialidades as $especialidad): ?>
                <option value="<?php echo htmlspecialchars($especialidad['id_especialidad']); ?>">
                    <?php echo htmlspecialchars($especialidad['nombre_especialidad']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="id_medico">Médico:</label>
        <select id="id_medico" name="id_medico" required disabled>
            <option value="">-- Esperando especialidad --</option>
        </select>
    </div>

    <div class="form-group">
        <label for="fecha_cita">Fecha de la Cita:</label>
        <input type="date" id="fecha_cita" name="fecha_cita" required min="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d', strtotime('+3 months')); ?>">
    </div>

    <div class="form-group">
        <label for="hora_cita">Hora de la Cita:</label>
        <input type="time" id="hora_cita" name="hora_cita" required min="08:00" max="18:00" step="1800">
    </div>

    <div class="form-actions">
        <button type="submit" class="btn">Agendar Cita</button>
        <a href="index.php" class="btn-secondary">Volver al Menú</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const especialidadSelect = document.getElementById('id_especialidad');
    const medicoSelect = document.getElementById('id_medico');
    const fechaInput = document.getElementById('fecha_cita');
    const horaInput = document.getElementById('hora_cita');
    const hoy = new Date().toISOString().split('T')[0];

    // Función para conectar los menús de Especialidad y Médico
    especialidadSelect.addEventListener('change', function() {
        const id_especialidad = this.value;
        medicoSelect.innerHTML = '<option value="">Cargando médicos...</option>';
        medicoSelect.disabled = true;

        if (!id_especialidad) {
            medicoSelect.innerHTML = '<option value="">-- Esperando especialidad --</option>';
            return;
        }

        fetch('api_get_medicos.php?id_especialidad=' + id_especialidad)
            .then(response => response.json())
            .then(medicos => {
                medicoSelect.innerHTML = '<option value="">-- Seleccione un médico --</option>';
                if (medicos.length > 0) {
                    medicos.forEach(function(medico) {
                        const option = document.createElement('option');
                        option.value = medico.id_medico;
                        option.textContent = medico.nombre_completo;
                        medicoSelect.appendChild(option);
                    });
                    medicoSelect.disabled = false;
                } else {
                    medicoSelect.innerHTML = '<option value="">No hay médicos en esta especialidad</option>';
                }
            });
    });
    
    // Función para ajustar la hora mínima si el día seleccionado es hoy
    function ajustarHoraMinima() {
        if (fechaInput.value === hoy) {
            let ahora = new Date();
            let hora = ahora.getHours().toString().padStart(2, '0');
            let minutos = ahora.getMinutes().toString().padStart(2, '0');
            horaInput.min = `${hora}:${minutos}`;
        } else {
            horaInput.min = '08:00';
        }
    }
    fechaInput.addEventListener('change', ajustarHoraMinima);
    ajustarHoraMinima(); // La llamamos una vez al cargar la página.
});
</script>

<?php include 'includes/footer.php'; ?>