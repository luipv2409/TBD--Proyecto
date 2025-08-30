<?php
// Novedad: La lógica ahora es más compleja para manejar múltiples contactos.
$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'includes/db_connection.php';

    // Empezamos una transacción. Esto es por seguridad.
    // Si algo falla al guardar los contactos, se deshace el registro del paciente.
    $pdo->beginTransaction();

    try {
        // Jalamos los datos principales del paciente.
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $fecha_nacimiento = $_POST['fecha_nacimiento'];
        $email = trim($_POST['email']);
        
        // Aquí irían todas tus validaciones que ya creamos antes...

        // 1. Insertamos al paciente primero.
        $stmt_paciente = $pdo->prepare("CALL sp_paciente_crud('create', NULL, ?, ?, ?, ?)");
        $stmt_paciente->execute([$nombre, $apellido, $fecha_nacimiento, $email]);

        // 2. Obtenemos el ID del paciente que acabamos de crear.
        $id_paciente_nuevo = $pdo->lastInsertId();

        // 3. Ahora, recorremos y guardamos los contactos.
        if (isset($_POST['tipo_contacto'])) {
            $tipos = $_POST['tipo_contacto'];
            $valores = $_POST['valor'];
            $descripciones = $_POST['descripcion'];

            $stmt_contacto = $pdo->prepare(
                "INSERT INTO contacto_paciente (id_paciente, tipo_contacto, valor, descripcion) VALUES (?, ?, ?, ?)"
            );

            for ($i = 0; $i < count($tipos); $i++) {
                // Solo guardamos si el campo de valor no está vacío.
                if (!empty(trim($valores[$i]))) {
                    $stmt_contacto->execute([
                        $id_paciente_nuevo,
                        $tipos[$i],
                        trim($valores[$i]),
                        trim($descripciones[$i])
                    ]);
                }
            }
        }

        // Si todo salió bien, confirmamos los cambios en la BD.
        $pdo->commit();
        $success_message = "¡Paciente y sus contactos registrados con éxito!";

    } catch (Exception $e) {
        // Si algo falló, deshacemos todo.
        $pdo->rollBack();
        $error_message = "Error al registrar: " . $e->getMessage();
    }
}
?>

<?php include 'includes/header.php'; ?>

<h2>Registrar Nuevo Paciente</h2>
<p>Complete el formulario. Puede añadir múltiples teléfonos y direcciones.</p>

<?php if (!empty($success_message)): ?>
    <div class="message success"><?php echo $success_message; ?></div>
<?php endif; ?>
<?php if (!empty($error_message)): ?>
    <div class="message error"><?php echo $error_message; ?></div>
<?php endif; ?>

<form method="POST" class="data-form">
    <div class="form-group"><label for="nombre">Nombre:</label><input type="text" id="nombre" name="nombre" required></div>
    <div class="form-group"><label for="apellido">Apellido:</label><input type="text" id="apellido" name="apellido" required></div>
    <div class="form-group"><label for="fecha_nacimiento">Fecha de Nacimiento:</label><input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required></div>
    <div class="form-group"><label for="email">Email:</label><input type="email" id="email" name="email"></div>

    <div class="report-container">
        <h2>Contactos de Referencia</h2>
        <div id="contactos-container">
            </div>
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

    // Función para crear un nuevo campo de contacto
    function addContactoField() {
        const div = document.createElement('div');
        div.className = 'form-group contacto-item';
        div.style.display = 'flex';
        div.style.gap = '10px';
        div.style.alignItems = 'flex-end';
        div.style.marginBottom = '10px';

        div.innerHTML = `
            <div style="flex: 1;">
                <label>Tipo</label>
                <select name="tipo_contacto[]" class="form-control">
                    <option value="telefono">Teléfono</option>
                    <option value="direccion">Dirección</option>
                </select>
            </div>
            <div style="flex: 3;">
                <label>Valor</label>
                <input type="text" name="valor[]" class="form-control" placeholder="Número o dirección" required>
            </div>
            <div style="flex: 2;">
                <label>Descripción</label>
                <input type="text" name="descripcion[]" class="form-control" placeholder="Ej: Casa, Trabajo...">
            </div>
            <button type="button" class="btn-cancelar btn-remove-contacto">X</button>
        `;
        container.appendChild(div);
    }

    // Al hacer clic en el botón "+ Añadir", se crea un nuevo campo
    addButton.addEventListener('click', addContactoField);

    // Si alguien hace clic en el botón de borrar (X)
    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-remove-contacto')) {
            e.target.closest('.contacto-item').remove();
        }
    });

    // Añadimos un campo por defecto al cargar la página
    addContactoField();
});
</script>

<?php include 'includes/footer.php'; ?>