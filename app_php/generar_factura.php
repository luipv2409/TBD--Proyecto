<?php
// Primero, nos conectamos a la base de datos.
require_once 'includes/db_connection.php';

$error_message = '';

// Si el usuario envió el formulario (hizo clic en el botón)...
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_cita = $_POST['id_cita'];

    // Validamos que haya seleccionado una cita.
    if (empty($id_cita)) {
        $error_message = "Debes seleccionar una cita para generar la factura.";
    } else {
        try {
            // 1. Llamamos al procedimiento para que cree la factura en la base de datos.
            $stmt = $pdo->prepare("CALL sp_factura_generar(?)");
            $stmt->execute([$id_cita]);
            
            // 2. Ahora, buscamos los datos de la factura que acabamos de crear para el archivo .txt.
            $sql_factura_info = "SELECT 
                                    f.id_factura, f.fecha_emision, f.monto_total,
                                    CONCAT(p.nombre, ' ', p.apellido) AS paciente_nombre,
                                    CONCAT(m.nombre, ' ', m.apellido) AS medico_nombre,
                                    e.nombre_especialidad
                                 FROM factura f
                                 JOIN cita c ON f.id_cita = c.id_cita
                                 JOIN paciente p ON c.id_paciente = p.id_paciente
                                 JOIN medico m ON c.id_medico = m.id_medico
                                 JOIN especialidad e ON m.id_especialidad = e.id_especialidad
                                 WHERE f.id_cita = ? LIMIT 1";

            $stmt_info = $pdo->prepare($sql_factura_info);
            $stmt_info->execute([$id_cita]);
            $factura = $stmt_info->fetch(PDO::FETCH_ASSOC);

            // 3. Si encontramos los datos, preparamos el contenido del archivo de texto.
            if ($factura) {
                // Creamos un nombre de archivo único, ej: factura_5.txt
                $nombre_archivo = "factura_" . $factura['id_factura'] . ".txt";

                // Definimos el contenido del archivo.
                $contenido_txt = "========================================\n";
                $contenido_txt .= "         FACTURA GESCOMED\n";
                $contenido_txt .= "========================================\n\n";
                $contenido_txt .= "Factura Nro:       " . $factura['id_factura'] . "\n";
                $contenido_txt .= "Fecha de Emision:  " . $factura['fecha_emision'] . "\n\n";
                $contenido_txt .= "---------------- PACIENTE ----------------\n";
                $contenido_txt .= "Nombre:            " . $factura['paciente_nombre'] . "\n\n";
                $contenido_txt .= "---------------- SERVICIO ----------------\n";
                $contenido_txt .= "Atendido por:      Dr. " . $factura['medico_nombre'] . "\n";
                $contenido_txt .= "Especialidad:      " . $factura['nombre_especialidad'] . "\n\n";
                $contenido_txt .= "----------------- TOTAL ------------------\n";
                $contenido_txt .= "Monto Total:       Bs. " . number_format($factura['monto_total'], 2) . "\n\n";
                $contenido_txt .= "========================================\n";

                // 4. Forzamos la descarga del archivo en el navegador.
                header('Content-Type: text/plain');
                header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
                echo $contenido_txt;
                exit; // Detenemos el script para que no muestre el HTML de abajo.
            }

        } catch (PDOException $e) {
            $error_message = "Error al generar la factura: " . $e->getMessage();
        }
    }
}

// Para el menú desplegable, buscamos las citas que se pueden facturar.
try {
    $sql = "SELECT 
                c.id_cita, c.fecha_cita,
                CONCAT(p.nombre, ' ', p.apellido) AS nombre_paciente,
                CONCAT(m.nombre, ' ', m.apellido) AS apellido_medico
            FROM cita c
            JOIN paciente p ON c.id_paciente = p.id_paciente
            JOIN medico m ON c.id_medico = m.id_medico
            LEFT JOIN factura f ON c.id_cita = f.id_cita
            WHERE c.estado = 'atendida' AND f.id_factura IS NULL
            ORDER BY c.fecha_cita DESC";
    
    $citas_facturables = $pdo->query($sql)->fetchAll();

} catch (PDOException $e) {
    $error_message = "Error al cargar las citas: " . $e->getMessage();
    $citas_facturables = [];
}
?>

<?php include 'includes/header.php'; ?>

<h2>Generar Factura de Cita</h2>
<p>Seleccione una cita completada para generar su correspondiente factura.</p>

<?php if (!empty($error_message)): ?>
    <div class="message error"><?php echo $error_message; ?></div>
<?php endif; ?>

<form action="generar_factura.php" method="POST" class="data-form">
    <div class="form-group">
        <label for="id_cita">Cita para Facturar:</label>
        <select id="id_cita" name="id_cita" required>
            <option value="">-- Seleccione una cita --</option>
            <?php if (count($citas_facturables) > 0): ?>
                <?php foreach ($citas_facturables as $cita): ?>
                    <option value="<?php echo htmlspecialchars($cita['id_cita']); ?>">
                        <?php echo htmlspecialchars($cita['fecha_cita'] . " - " . $cita['nombre_paciente'] . " (Dr. " . $cita['apellido_medico'] . ")"); ?>
                    </option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="" disabled>No hay citas pendientes de facturación</option>
            <?php endif; ?>
        </select>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn">Generar Factura</button>
        <a href="index.php" class="btn-secondary">Volver al Menú</a>
    </div>
</form>

<?php include 'includes/footer.php'; ?>