<?php
// Lógica para procesar el formulario cuando se envía
$error_message = '';
$success_message = '';

// Chequeamos si el cumpa ya mandó el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Nos conectamos a la BD, como siempre
    require_once 'includes/db_connection.php';

    // 2. Jalamos los datos del formulario
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $email = trim($_POST['email']);

    // 3. Hora de validar, ¡que no nos metan cualquier cosa!
    if (empty($nombre) || empty($apellido) || empty($fecha_nacimiento)) {
        $error_message = "El nombre, apellido y fecha de nacimiento son obligatorios, pues.";
    
    } elseif (!preg_match('/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+$/u', $nombre)) {
        $error_message = "El nombre solo puede tener letras y espacios, cumpa.";

    } elseif (!preg_match('/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+$/u', $apellido)) {
        $error_message = "El apellido solo puede tener letras y espacios, che.";

    // Novedad: Aquí validamos la fecha de nacimiento.
    } else {
        $fecha_nac_obj = new DateTime($fecha_nacimiento);
        $hoy = new DateTime('today');
        $fecha_minima = (new DateTime('today'))->modify('-100 years');

        if ($fecha_nac_obj >= $hoy) {
            $error_message = "La fecha de nacimiento no puede ser hoy ni un día futuro.";
        } elseif ($fecha_nac_obj < $fecha_minima) {
            $error_message = "El paciente no puede tener más de 100 años.";
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Ese correo parece trucho, che. Revisa el formato.";
        } elseif (!empty($telefono) && !preg_match('/^[0-9]{8}$/', $telefono)) {
            $error_message = "El teléfono tiene que tener 8 números, sin letras ni macanas.";
        } else {
            // 4. Si todo está joya, llamamos al procedimiento pa' guardar
            try {
                $sql = "CALL sp_paciente_crud('create', NULL, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                
                $stmt->execute([$nombre, $apellido, $fecha_nacimiento, $telefono, $direccion, $email]);
                
                $success_message = "¡Paciente registrado con éxito, maquina!";

            } catch (PDOException $e) {
                if ($e->getCode() == '23000') {
                    $error_message = "Error: Ese correo electrónico ya está registrado. Usa otro, pues.";
                } else {
                    $error_message = "Error al registrar el paciente: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<h2>Registrar Nuevo Paciente</h2>
<p>Complete el siguiente formulario para añadir un nuevo paciente al sistema.</p>

<?php if (!empty($success_message)): ?>
    <div class="message success"><?php echo $success_message; ?></div>
<?php endif; ?>
<?php if (!empty($error_message)): ?>
    <div class="message error"><?php echo $error_message; ?></div>
<?php endif; ?>


<form action="registrar_paciente.php" method="POST" class="data-form">
    <div class="form-group">
        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" required 
               pattern="[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+" 
               title="El nombre solo puede contener letras y espacios.">
    </div>
    <div class="form-group">
        <label for="apellido">Apellido:</label>
        <input type="text" id="apellido" name="apellido" required
               pattern="[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+" 
               title="El apellido solo puede contener letras y espacios.">
    </div>
    <div class="form-group">
        <label for="fecha_nacimiento">Fecha de Nacimiento:</label>
        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required
               max="<?php echo date('Y-m-d', strtotime('-1 day')); ?>"
               min="<?php echo date('Y-m-d', strtotime('-100 years')); ?>">
    </div>
    <div class="form-group">
        <label for="telefono">Teléfono:</label>
        <input type="tel" id="telefono" name="telefono" 
               pattern="[0-9]{8}" 
               maxlength="8" 
               title="El teléfono debe tener 8 dígitos numéricos.">
    </div>
    <div class="form-group">
        <label for="direccion">Dirección:</label>
        <input type="text" id="direccion" name="direccion">
    </div>
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email">
    </div>
    <div class="form-actions">
        <button type="submit" class="btn">Registrar Paciente</button>
        <a href="index.php" class="btn-secondary">Volver al Menú</a>
    </div>
</form>

<?php include 'includes/footer.php'; ?>