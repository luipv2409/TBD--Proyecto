<?php
require_once 'includes/db_connection.php';
$error_message = '';
$success_message = '';

// Si se envió el formulario...
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Jalamos todos los datos del médico.
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $ci = trim($_POST['ci']);
        $email = trim($_POST['email']);
        $id_especialidad = $_POST['id_especialidad'];
        $id_consultorio = $_POST['id_consultorio'];

        // Cadena de validaciones.
        if (empty($nombre) || empty($apellido) || empty($ci) || empty($id_especialidad) || empty($id_consultorio)) {
            throw new Exception("Todos los campos marcados con * son obligatorios.");
        }
        if (!preg_match('/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+$/u', $nombre)) {
            throw new Exception("El nombre solo puede tener letras y espacios.");
        }
        if (!preg_match('/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+$/u', $apellido)) {
            throw new Exception("El apellido solo puede tener letras y espacios.");
        }
        if (!preg_match('/^[0-9]{6,8}$/', $ci)) {
            throw new Exception("El Carnet de Identidad debe tener entre 6 y 8 números.");
        }
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El formato del correo es incorrecto.");
        }

        // Llamamos al nuevo procedimiento para guardar al médico.
        $stmt = $pdo->prepare("CALL sp_medico_crud('create', ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $apellido, $ci, $email, $id_especialidad, $id_consultorio]);
        
        $success_message = "¡Médico registrado con éxito!";

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Jalamos las especialidades y consultorios para los menús desplegables.
try {
    $especialidades = $pdo->query("SELECT * FROM especialidad ORDER BY nombre_especialidad")->fetchAll();
    $consultorios = $pdo->query("SELECT * FROM consultorio ORDER BY nombre_consultorio")->fetchAll();
} catch (Exception $e) {
    $error_message = "Error al cargar datos para el formulario: " . $e->getMessage();
    $especialidades = [];
    $consultorios = [];
}
?>

<?php include 'includes/header.php'; ?>

<h2>Registrar Nuevo Médico</h2>
<p>Complete el formulario para añadir un nuevo médico al sistema.</p>

<?php if(!empty($success_message)): ?><div class="message success"><?php echo $success_message; ?></div><?php endif; ?>
<?php if(!empty($error_message)): ?><div class="message error"><?php echo $error_message; ?></div><?php endif; ?>

<form method="POST" action="registrar_medico.php" class="data-form">
    <div class="form-group">
        <label for="nombre">Nombre: *</label>
        <input type="text" id="nombre" name="nombre" required pattern="[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+" title="Solo letras y espacios.">
    </div>
    <div class="form-group">
        <label for="apellido">Apellido: *</label>
        <input type="text" id="apellido" name="apellido" required pattern="[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+" title="Solo letras y espacios.">
    </div>
    <div class="form-group">
        <label for="ci">Carnet de Identidad (CI): *</label>
        <input type="text" id="ci" name="ci" required pattern="[0-9]{6,8}" maxlength="8" title="El carnet debe tener entre 6 y 8 números.">
    </div>
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email">
    </div>
    <div class="form-group">
        <label for="id_especialidad">Especialidad: *</label>
        <select id="id_especialidad" name="id_especialidad" required>
            <option value="">-- Seleccione una especialidad --</option>
            <?php foreach ($especialidades as $item): ?>
                <option value="<?php echo $item['id_especialidad']; ?>"><?php echo htmlspecialchars($item['nombre_especialidad']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="id_consultorio">Consultorio Asignado: *</label>
        <select id="id_consultorio" name="id_consultorio" required>
            <option value="">-- Seleccione un consultorio --</option>
            <?php foreach ($consultorios as $item): ?>
                <option value="<?php echo $item['id_consultorio']; ?>"><?php echo htmlspecialchars($item['nombre_consultorio'] . ' (' . $item['ubicacion'] . ')'); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn">Registrar Médico</button>
        <a href="index.php" class="btn-secondary">Volver al Menú</a>
    </div>
</form>

<?php include 'includes/footer.php'; ?>