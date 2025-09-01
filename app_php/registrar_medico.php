<?php
// Novedad: Iniciamos sesión para la seguridad del token CSRF.
session_start();
require_once 'includes/db_connection.php';

$error_message = '';
$success_message = '';

// Novedad: Definimos las variables afuera para que el formulario "pegajoso" funcione.
$nombre = ''; $apellido = ''; $ci = ''; $email = '';
$id_especialidad = ''; $id_consultorio = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Novedad: Verificación del token de seguridad CSRF.
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Error de validación de seguridad. Intenta de nuevo.");
        }

        // Jalamos los datos para que el formulario los recuerde si hay un error.
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

        $stmt = $pdo->prepare("CALL sp_medico_crud('create', ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $apellido, $ci, $email, $id_especialidad, $id_consultorio]);
        
        $success_message = "¡Médico registrado con éxito!";
        // Limpiamos las variables después de un registro exitoso.
        $nombre = $apellido = $ci = $email = $id_especialidad = $id_consultorio = '';

    } catch (PDOException $e) {
        // Novedad: Mensajes de error más vivos para datos duplicados.
        if ($e->getCode() == '23000') { // Código de error para violación de integridad (UNIQUE)
            if (strpos($e->getMessage(), 'ci')) {
                $error_message = "Error: El Carnet de Identidad '" . htmlspecialchars($ci) . "' ya está registrado.";
            } elseif (strpos($e->getMessage(), 'email')) {
                $error_message = "Error: El Email '" . htmlspecialchars($email) . "' ya está registrado.";
            } else {
                $error_message = "Error: Hubo un problema de datos duplicados.";
            }
        } else {
            $error_message = "Error de base de datos: " . $e->getMessage();
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Novedad: Generamos el token de seguridad para el formulario.
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Jalamos las especialidades y consultorios para los menús desplegables.
try {
    $especialidades = $pdo->query("SELECT * FROM especialidad ORDER BY nombre_especialidad")->fetchAll();
    $consultorios = $pdo->query("SELECT * FROM consultorio ORDER BY nombre_consultorio")->fetchAll();
} catch (Exception $e) {
    // Si la conexión a la BD falla desde el principio, mostramos un error.
    if (empty($error_message)) {
        $error_message = "Error al cargar datos para el formulario: " . $e->getMessage();
    }
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
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

    <div class="form-group">
        <label for="nombre">Nombre: *</label>
        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required pattern="[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+" title="Solo letras y espacios.">
    </div>
    <div class="form-group">
        <label for="apellido">Apellido: *</label>
        <input type="text" id="apellido" name="apellido" value="<?php echo htmlspecialchars($apellido); ?>" required pattern="[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+" title="Solo letras y espacios.">
    </div>
    <div class="form-group">
        <label for="ci">Carnet de Identidad (CI): *</label>
        <input type="text" id="ci" name="ci" value="<?php echo htmlspecialchars($ci); ?>" required pattern="[0-9]{6,8}" maxlength="8" title="El carnet debe tener entre 6 y 8 números.">
    </div>
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
    </div>
    <div class="form-group">
        <label for="id_especialidad">Especialidad: *</label>
        <select id="id_especialidad" name="id_especialidad" required>
            <option value="">-- Seleccione una especialidad --</option>
            <?php foreach ($especialidades as $item): ?>
                <option value="<?php echo $item['id_especialidad']; ?>" <?php if($id_especialidad == $item['id_especialidad']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($item['nombre_especialidad']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="id_consultorio">Consultorio Asignado: *</label>
        <select id="id_consultorio" name="id_consultorio" required>
            <option value="">-- Seleccione un consultorio --</option>
            <?php foreach ($consultorios as $item): ?>
                <option value="<?php echo $item['id_consultorio']; ?>" <?php if($id_consultorio == $item['id_consultorio']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($item['nombre_consultorio'] . ' (' . $item['ubicacion'] . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn">Registrar Médico</button>
        <a href="index.php" class="btn-secondary">Volver al Menú</a>
    </div>
</form>

<?php include 'includes/footer.php'; ?>