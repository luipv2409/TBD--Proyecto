<?php
require_once 'includes/db_connection.php';

$error_message = '';

// Si el usuario envió el formulario para generar la factura...
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_cita = $_POST['id_cita'];
    $nit_cliente = trim($_POST['nit_cliente']);
    $razon_social = trim($_POST['razon_social_cliente']);

    if (empty($id_cita) || empty($nit_cliente) || empty($razon_social)) {
        $error_message = "Debes seleccionar una cita y completar el NIT y la Razón Social.";
    } else {
        try {
            // 1. Llamamos al procedimiento para que cree la factura en la base de datos.
            $stmt = $pdo->prepare("CALL sp_factura_generar(?, ?, ?)");
            $stmt->execute([$id_cita, $nit_cliente, $razon_social]);
            $stmt->closeCursor();
            
            // 2. Buscamos los datos completos de la factura que acabamos de crear.
            $sql_factura_info = "SELECT 
                                    f.*,
                                    CONCAT(p.nombre, ' ', p.apellido) AS paciente_nombre,
                                    p.ci AS paciente_ci
                                 FROM factura f
                                 JOIN cita c ON f.id_cita = c.id_cita
                                 JOIN paciente p ON c.id_paciente = p.id_paciente
                                 WHERE f.id_cita = ? LIMIT 1";

            $stmt_info = $pdo->prepare($sql_factura_info);
            $stmt_info->execute([$id_cita]);
            $factura = $stmt_info->fetch(PDO::FETCH_ASSOC);

            // 3. Si encontramos los datos, preparamos y descargamos el archivo de texto.
            if ($factura) {
                $nombre_archivo = "factura_" . ($factura['nro_factura'] ?? $factura['id_factura']) . ".txt";
                $contenido_txt = "========================================\n";
                $contenido_txt .= "         FACTURA GESCOMED\n";
                $contenido_txt .= "========================================\n\n";
                $contenido_txt .= "Factura Nro:       " . ($factura['nro_factura'] ?? $factura['id_factura']) . "\n";
                $contenido_txt .= "Fecha de Emision:  " . $factura['fecha_emision'] . "\n\n";
                $contenido_txt .= "----------------- CLIENTE ----------------\n";
                $contenido_txt .= "Razon Social:      " . $factura['razon_social_cliente'] . "\n";
                $contenido_txt .= "NIT/CI:            " . $factura['nit_cliente'] . "\n\n";
                $contenido_txt .= "---------------- DETALLES ----------------\n";
                $contenido_txt .= "Servicio:          Consulta Medica\n";
                $contenido_txt .= "Paciente:          " . $factura['paciente_nombre'] . "\n\n";
                $contenido_txt .= "----------------- MONTOS -----------------\n";
                $contenido_txt .= "Subtotal:          Bs. " . number_format($factura['monto_base_iva'], 2) . "\n";
                $contenido_txt .= "IVA (13%):         Bs. " . number_format($factura['iva_13'], 2) . "\n";
                $contenido_txt .= "Monto Total:       Bs. " . number_format($factura['monto_total'], 2) . "\n\n";
                $contenido_txt .= "IT (3%):           Bs. " . number_format($factura['it_3'], 2) . "\n";
                $contenido_txt .= "========================================\n";

                header('Content-Type: text/plain; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
                echo $contenido_txt;
                exit;
            }

        } catch (PDOException $e) {
            $error_message = "Error al generar la factura: " . $e->getMessage();
        }
    }
}

// Para el menú desplegable, buscamos las citas que se pueden facturar.
try {
    $sql_citas = "SELECT c.id_cita, c.fecha_cita, CONCAT(p.nombre, ' ', p.apellido) AS nombre_paciente, CONCAT(m.nombre, ' ', m.apellido) AS medico_nombre
                  FROM cita c
                  JOIN paciente p ON c.id_paciente = p.id_paciente
                  JOIN medico m ON c.id_medico = m.id_medico
                  LEFT JOIN factura f ON c.id_cita = f.id_cita
                  WHERE c.estado = 'atendida' AND f.id_factura IS NULL
                  ORDER BY c.fecha_cita DESC";
    $citas_facturables = $pdo->query($sql_citas)->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error al cargar las citas: " . $e->getMessage();
    $citas_facturables = [];
}
?>

<?php include 'includes/header.php'; ?>

<h2>Generar Factura de Cita</h2>
<p>Seleccione una cita y complete los datos del cliente para la facturación.</p>

<?php if (!empty($error_message)): ?>
    <div class="message error"><?php echo $error_message; ?></div>
<?php endif; ?>

<form method="POST" class="data-form">
    <div class="form-group">
        <label for="id_cita">Cita para Facturar:</label>
        <select id="id_cita" name="id_cita" required>
            <option value="">-- Seleccione una cita --</option>
            <?php foreach ($citas_facturables as $cita): ?>
                <option value="<?php echo htmlspecialchars($cita['id_cita']); ?>"><?php echo htmlspecialchars($cita['fecha_cita'] . " - " . $cita['nombre_paciente'] . " (Dr. " . $cita['medico_nombre'] . ")"); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="report-container" style="padding-top: 5px;">
        <h3>Datos para la Factura</h3>
        <div class="form-group">
            <label for="nit_cliente">NIT / CI del Cliente:</label>
            <input type="text" id="nit_cliente" name="nit_cliente" required placeholder="Carnet o NIT para la factura">
        </div>
        <div class="form-group">
            <label for="razon_social_cliente">Nombre o Razón Social:</label>
            <input type="text" id="razon_social_cliente" name="razon_social_cliente" required placeholder="Nombre completo o razón social">
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn">Generar y Descargar Factura</button>
        <a href="index.php" class="btn-secondary">Volver al Menú</a>
    </div>
</form>

<?php include 'includes/footer.php'; ?>