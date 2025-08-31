<?php
// Toda la lógica para procesar el formulario va aquí arriba.
$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'includes/db_connection.php';
    $pdo->beginTransaction();

    try {
        // Jalamos los datos principales del paciente, incluyendo el nuevo CI.
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $ci = trim($_POST['ci']);
        $fecha_nacimiento = $_POST['fecha_nacimiento'];
        $email = trim($_POST['email']);
        
        // --- CADENA COMPLETA DE VALIDACIONES ---
        $fecha_nac_obj = new DateTime($fecha_nacimiento);
        $hoy = new DateTime('today');
        $fecha_minima = (new DateTime('today'))->modify('-100 years');

        if (empty($nombre) || empty($apellido) || empty($ci) || empty($fecha_nacimiento)) {
            throw new Exception("El nombre, apellido, CI y fecha de nacimiento son obligatorios.");
        }
        if (!preg_match('/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+$/u', $nombre)) {
            throw new Exception("El nombre solo puede tener letras y espacios.");
        }
        if (!preg_match('/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+$/u', $apellido)) {
            throw new Exception("El apellido solo puede tener letras y espacios.");
        }
        if (!preg_match('/^[0-9]{6,8}$/', $ci)) {
            throw new Exception("El Carnet de Identidad debe tener entre 6 y 8 números, sin letras.");
        }
        if ($fecha_nac_obj >= $hoy) {
            throw new Exception("La fecha de nacimiento no puede ser hoy ni un día futuro.");
        }
        if ($fecha_nac_obj < $fecha_minima) {
            throw new Exception("El paciente no puede tener más de 100 años.");
        }
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El formato del correo es incorrecto.");
        }
        
        // 1. Insertamos al paciente (ahora con el CI).
        $stmt_paciente = $pdo->prepare("CALL sp_paciente_crud('create', NULL, ?, ?, ?, ?, ?)");
        $stmt_paciente->execute([$nombre, $apellido, $ci, $fecha_nacimiento, $email]);

        // 2. Obtenemos el ID del paciente que acabamos de crear.
        $id_paciente_nuevo = $pdo->lastInsertId();

        // 3. Validamos y guardamos los contactos.
        if (isset($_POST['tipo_contacto'])) {
            $tipos = $_POST['tipo_contacto'];
            $valores = $_POST['valor'];
            $descripciones = $_POST['descripcion'];

            for ($i = 0; $i < count($tipos); $i++) {
                $valor_actual = trim($valores[$i]);
                if (empty($valor_actual)) { throw new Exception("El campo 'Valor' de un contacto no puede estar en blanco."); }
                if ($tipos[$i] === 'telefono' && !preg_match('/^[0-9]{8}$/', $valor_actual)) {
                    throw new Exception("El teléfono '" . htmlspecialchars($valor_actual) . "' es inválido. Debe tener 8 números.");
                } elseif ($tipos[$i] === 'direccion' && strlen($valor_actual) > 255) {
                    throw new Exception("La dirección es demasiado larga.");
                }
            }
            $stmt_contacto = $pdo->prepare("INSERT INTO contacto_paciente (id_paciente, tipo_contacto, valor, descripcion) VALUES (?, ?, ?, ?)");
            for ($i = 0; $i < count($tipos); $i++) {
                if (!empty(trim($valores[$i]))) {
                    $stmt_contacto->execute([$id_paciente_nuevo, $tipos[$i], trim($valores[$i]), trim($descripciones[$i])]);
                }
            }
        }

        // Si todo salió bien, confirmamos los cambios.
        $pdo->commit();
        $success_message = "¡Paciente y sus contactos registrados con éxito!";

    } catch (Exception $e) {
        // Si algo falló, deshacemos todo y mostramos el error.
        $pdo->rollBack();
        $error_message = $e->getMessage();
    }
}
?>

<?php include 'includes/header.php'; ?>

<h2>Registrar Nuevo Paciente</h2>
<p>Complete el formulario. Puede añadir múltiples teléfonos y direcciones.</p>

<?php if(!empty($success_message)): ?><div class="message success"><?php echo $success_message; ?></div><?php endif; ?>
<?php if(!empty($error_message)): ?><div class="message error"><?php echo $error_message; ?></div><?php endif; ?>

<form method="POST" class="data-form">
    <div class="form-group">
        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" required pattern="[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+" title="Solo letras y espacios.">
    </div>
    <div class="form-group">
        <label for="apellido">Apellido:</label>
        <input type="text" id="apellido" name="apellido" required pattern="[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+" title="Solo letras y espacios.">
    </div>
    
    <div class="form-group">
        <label for="ci">Carnet de Identidad (CI):</label>
        <input type="text" id="ci" name="ci" required 
               pattern="[0-9]{6,8}" 
               maxlength="8" 
               title="El carnet debe tener entre 6 y 8 números.">
    </div>

    <div class="form-group">
        <label for="fecha_nacimiento">Fecha de Nacimiento:</label>
        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required max="<?php echo date('Y-m-d', strtotime('-1 day')); ?>" min="<?php echo date('Y-m-d', strtotime('-100 years')); ?>">
    </div>
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email">
    </div>

    <div class="report-container">
        <h2>Contactos de Referencia</h2>
        <div id="contactos-container"></div>
        <button type="button" id="btn-add-contacto" class="btn-secondary" style="margin-top:10px;">+ Añadir Contacto</button>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn">Registrar Paciente</button>
        <a href="index.php" class="btn-secondary">Volver al Menú</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('contactos-container');
    const addButton = document.getElementById('btn-add-contacto');
    function aplicarReglas(selectElement) {
        const valorInput = selectElement.closest('.contacto-item').querySelector('input[name="valor[]"]');
        if (selectElement.value === 'telefono') {
            valorInput.type = 'tel';
            valorInput.placeholder = 'Solo 8 números';
            valorInput.pattern = '[0-9]{8}';
            valorInput.maxLength = 8;
            valorInput.title = 'El teléfono debe tener 8 dígitos numéricos.';
        } else {
            valorInput.type = 'text';
            valorInput.placeholder = 'Calle, número, zona...';
            valorInput.pattern = '.{5,}';
            valorInput.maxLength = 255;
            valorInput.title = 'La dirección debe tener al menos 5 caracteres.';
        }
    }
    function addContactoField() {
        const div = document.createElement('div');
        div.className = 'form-group contacto-item';
        div.style.display = 'flex';
        div.style.gap = '10px';
        div.style.alignItems = 'flex-end';
        div.style.marginBottom = '10px';
        div.innerHTML = `<div style="flex: 1;"><label>Tipo</label><select name="tipo_contacto[]" class="form-control tipo-selector"><option value="telefono">Teléfono</option><option value="direccion">Dirección</option></select></div><div style="flex: 3;"><label>Valor</label><input type="tel" name="valor[]" class="form-control" required></div><div style="flex: 2;"><label>Descripción</label><input type="text" name="descripcion[]" class="form-control" placeholder="Ej: Casa, Trabajo..."></div><button type="button" class="btn-cancelar btn-remove-contacto">X</button>`;
        container.appendChild(div);
        aplicarReglas(div.querySelector('.tipo-selector'));
    }
    addButton.addEventListener('click', addContactoField);
    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('tipo-selector')) {
            aplicarReglas(e.target);
        }
        if (e.target.classList.contains('btn-remove-contacto')) {
            e.target.closest('.contacto-item').remove();
        }
    });
    addContactoField();
});
</script>

<?php include 'includes/footer.php'; ?>