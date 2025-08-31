<?php
// Incluir la conexión a la base de datos
require_once 'includes/db_connection.php';

try {
    // --- LÓGICA PARA TODOS LOS REPORTES ---

    // 1. Reporte de Ingresos por Especialidad
    $sql_ingresos = "SELECT e.nombre_especialidad, SUM(f.monto_total) AS total_ingresos FROM factura f JOIN cita c ON f.id_cita = c.id_cita JOIN medico m ON c.id_medico = m.id_medico JOIN especialidad e ON m.id_especialidad = e.id_especialidad WHERE f.estado_pago = 'pagada' GROUP BY e.nombre_especialidad ORDER BY total_ingresos DESC";
    $reporte_ingresos = $pdo->query($sql_ingresos)->fetchAll();

    // 2. Reporte de Citas por Médico (Resumen) - ESTA CONSULTA YA NO LA NECESITAREMOS DIRECTAMENTE, PERO LA DEJAMOS POR SI ACASO
    $sql_citas_medico = "SELECT CONCAT(m.nombre, ' ', m.apellido) AS nombre_medico, fn_contar_citas_medico(m.id_medico) AS citas_atendidas FROM medico m ORDER BY citas_atendidas DESC";
    $reporte_citas_medico = $pdo->query($sql_citas_medico)->fetchAll();

    // 3. Reporte de Pacientes por Rango de Edad
    $sql_edad = "SELECT CASE WHEN fn_obtener_edad_paciente(fecha_nacimiento) BETWEEN 0 AND 17 THEN '0-17 (Niños/Adolescentes)' WHEN fn_obtener_edad_paciente(fecha_nacimiento) BETWEEN 18 AND 35 THEN '18-35 (Jóvenes Adultos)' WHEN fn_obtener_edad_paciente(fecha_nacimiento) BETWEEN 36 AND 60 THEN '36-60 (Adultos)' ELSE '61+ (Adultos Mayores)' END AS rango_edad, COUNT(id_paciente) AS numero_de_pacientes FROM paciente GROUP BY rango_edad ORDER BY rango_edad";
    $reporte_edad = $pdo->query($sql_edad)->fetchAll();

    // 4. Reporte de Citas por Mes (Último Año)
    $sql_citas_mes = "SELECT DATE_FORMAT(fecha_cita, '%Y-%m') AS mes, COUNT(id_cita) AS cantidad_citas FROM cita WHERE estado = 'atendida' AND fecha_cita >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR) GROUP BY mes ORDER BY mes ASC";
    $reporte_citas_mes = $pdo->query($sql_citas_mes)->fetchAll();

    // 5. Reporte de Facturas Pendientes
    $sql_facturas_pendientes = "SELECT f.id_factura, f.fecha_emision, f.monto_total, CONCAT(p.nombre, ' ', p.apellido) AS nombre_paciente, (SELECT valor FROM contacto_paciente cp WHERE cp.id_paciente = p.id_paciente AND cp.tipo_contacto = 'telefono' LIMIT 1) AS telefono_paciente FROM factura f JOIN cita c ON f.id_cita = c.id_cita JOIN paciente p ON c.id_paciente = p.id_paciente WHERE f.estado_pago = 'pendiente' ORDER BY f.fecha_emision ASC";
    $reporte_facturas_pendientes = $pdo->query($sql_facturas_pendientes)->fetchAll();
    
    // 6. Lógica para el reporte interactivo de Historial de Paciente
    $pacientes_para_historial = $pdo->query("SELECT id_paciente, CONCAT(nombre, ' ', apellido) AS nombre_completo FROM paciente ORDER BY nombre_completo ASC")->fetchAll();
    $reporte_historial = [];
    $paciente_seleccionado_nombre = '';
    if (isset($_GET['id_paciente']) && !empty($_GET['id_paciente'])) {
        $id_paciente_buscado = $_GET['id_paciente'];
        $stmt_nombre = $pdo->prepare("SELECT CONCAT(nombre, ' ', apellido) AS nombre_completo FROM paciente WHERE id_paciente = ?");
        $stmt_nombre->execute([$id_paciente_buscado]);
        $paciente_seleccionado_nombre = $stmt_nombre->fetchColumn();
        $sql_historial = "SELECT 'Cita' AS tipo_registro, c.fecha_cita AS fecha, CONCAT('Cita con Dr. ', m.apellido, ' (', e.nombre_especialidad, ')') AS descripcion, c.estado AS detalles FROM cita c JOIN medico m ON c.id_medico = m.id_medico JOIN especialidad e ON m.id_especialidad = e.id_especialidad WHERE c.id_paciente = ? UNION ALL SELECT 'Diagnóstico' AS tipo_registro, hc.fecha_registro AS fecha, hc.diagnostico AS descripcion, CONCAT('Tratamiento: ', hc.tratamiento) AS detalles FROM historial_clinico hc WHERE hc.id_paciente = ? UNION ALL SELECT 'Factura' AS tipo_registro, f.fecha_emision AS fecha, CONCAT('Factura por consulta del ', c.fecha_cita) AS descripcion, CONCAT('Monto: ', f.monto_total, ', Estado: ', f.estado_pago) AS detalles FROM factura f JOIN cita c ON f.id_cita = c.id_cita WHERE c.id_paciente = ? ORDER BY fecha DESC";
        $stmt_historial = $pdo->prepare($sql_historial);
        $stmt_historial->execute([$id_paciente_buscado, $id_paciente_buscado, $id_paciente_buscado]);
        $reporte_historial = $stmt_historial->fetchAll();
    }

    // 7. Lógica para el reporte de Historial de Médicos (el que ya hicimos)
    $medicos_para_reporte = $pdo->query("SELECT id_medico, CONCAT(nombre, ' ', apellido) AS nombre_completo FROM medico ORDER BY apellido ASC")->fetchAll();
    $citas_del_medico = [];
    $medico_seleccionado_nombre = '';
    if (isset($_GET['ver_medico_id']) && !empty($_GET['ver_medico_id'])) {
        $id_medico_buscado = $_GET['ver_medico_id'];
        $stmt_medico_nombre = $pdo->prepare("SELECT CONCAT(nombre, ' ', apellido) AS nombre_completo FROM medico WHERE id_medico = ?");
        $stmt_medico_nombre->execute([$id_medico_buscado]);
        $medico_seleccionado_nombre = $stmt_medico_nombre->fetchColumn();
        $sort_medico_citas = isset($_GET['sort_medico_citas']) ? $_GET['sort_medico_citas'] : 'fecha_desc';
        $order_clause_medico = " ORDER BY c.fecha_cita DESC, c.hora_cita DESC";
        if ($sort_medico_citas === 'paciente_asc') {
            $order_clause_medico = " ORDER BY paciente_atendido ASC";
        }
        $sql_citas = "SELECT c.fecha_cita, c.hora_cita, CONCAT(p.nombre, ' ', p.apellido) AS paciente_atendido FROM cita c JOIN paciente p ON c.id_paciente = p.id_paciente WHERE c.id_medico = ? AND c.estado = 'atendida'" . $order_clause_medico;
        $stmt_citas = $pdo->prepare($sql_citas);
        $stmt_citas->execute([$id_medico_buscado]);
        $citas_del_medico = $stmt_citas->fetchAll();
    }

} catch (PDOException $e) {
    $error_message = "Error al generar los reportes: " . $e->getMessage();
}
?>

<?php include 'includes/header.php'; ?>

<h1>Reportes Analíticos del Sistema</h1>

<?php if (isset($error_message)): ?>
    <div class="message error"><?php echo $error_message; ?></div>
<?php else: ?>

    <div class="report-container">
        <h2>Historial Detallado de Citas por Médico</h2>
        <p>Selecciona un médico de la lista y haz clic en "Ver Historial" para ver sus citas atendidas.</p>
        
        <form action="reportes.php#historial-medico" method="GET" class="data-form">
            <div style="display: flex; gap: 15px; align-items: flex-end;">
                <div class="form-group" style="flex-grow: 1;">
                    <label for="ver_medico_id">Médico:</label>
                    <select name="ver_medico_id" id="ver_medico_id" required>
                        <option value="">-- Seleccione un médico --</option>
                        <?php foreach ($medicos_para_reporte as $medico): ?>
                            <option value="<?php echo $medico['id_medico']; ?>" <?php if (isset($_GET['ver_medico_id']) && $_GET['ver_medico_id'] == $medico['id_medico']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($medico['nombre_completo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn">Ver Historial</button>
                </div>
            </div>
        </form>

        <?php if (isset($_GET['ver_medico_id']) && !empty($_GET['ver_medico_id'])): ?>
            <div id="historial-medico" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                <h3>Citas atendidas por: <?php echo htmlspecialchars($medico_seleccionado_nombre); ?></h3>
                <p>
                    Ordenar por: 
                    <a href="?ver_medico_id=<?php echo $_GET['ver_medico_id']; ?>&sort_medico_citas=fecha_desc#historial-medico">Más Recientes</a> | 
                    <a href="?ver_medico_id=<?php echo $_GET['ver_medico_id']; ?>&sort_medico_citas=paciente_asc#historial-medico">Paciente (A-Z)</a>
                </p>
                <?php if (!empty($citas_del_medico)): ?>
                <table class="report-table">
                    <thead><tr><th>Fecha</th><th>Hora</th><th>Paciente Atendido</th></tr></thead>
                    <tbody>
                        <?php foreach ($citas_del_medico as $cita): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cita['fecha_cita']); ?></td>
                            <td><?php echo htmlspecialchars(date('h:i A', strtotime($cita['hora_cita']))); ?></td>
                            <td><?php echo htmlspecialchars($cita['paciente_atendido']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?><p class="no-data">Este médico no tiene citas atendidas registradas.</p><?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="report-container">
        <h2>Facturas Pendientes de Pago</h2>
        <table class="report-table">
            <thead><tr><th>ID Factura</th><th>Fecha Emisión</th><th>Monto Total</th><th>Paciente</th><th>Teléfono</th></tr></thead>
            <tbody>
                <?php foreach ($reporte_facturas_pendientes as $fila): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fila['id_factura']); ?></td>
                        <td><?php echo htmlspecialchars($fila['fecha_emision']); ?></td>
                        <td>Bs. <?php echo htmlspecialchars(number_format($fila['monto_total'], 2)); ?></td>
                        <td><?php echo htmlspecialchars($fila['nombre_paciente']); ?></td>
                        <td><?php echo htmlspecialchars($fila['telefono_paciente']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="report-container">
        <h2>Total de Ingresos por Especialidad</h2>
        <table class="report-table">
            <thead><tr><th>Especialidad</th><th>Total Ingresos</th></tr></thead>
            <tbody>
                <?php foreach ($reporte_ingresos as $fila): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fila['nombre_especialidad']); ?></td>
                        <td>Bs. <?php echo htmlspecialchars(number_format($fila['total_ingresos'], 2)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="report-container">
        <h2>Citas Atendidas por Médico (Resumen)</h2>
        <table class="report-table">
            <thead><tr><th>Médico</th><th>N° Total de Citas Atendidas</th></tr></thead>
            <tbody>
                <?php foreach ($reporte_citas_medico as $fila): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fila['nombre_medico']); ?></td>
                        <td><?php echo htmlspecialchars($fila['citas_atendidas']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="report-container">
        <h2>Distribución de Pacientes por Edad</h2>
        <table class="report-table">
            <thead><tr><th>Rango de Edad</th><th>Cantidad de Pacientes</th></tr></thead>
            <tbody>
                <?php foreach ($reporte_edad as $fila): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fila['rango_edad']); ?></td>
                        <td><?php echo htmlspecialchars($fila['numero_de_pacientes']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="report-container">
        <h2>Citas Atendidas por Mes (Último Año)</h2>
        <table class="report-table">
            <thead><tr><th>Mes</th><th>Cantidad de Citas</th></tr></thead>
            <tbody>
                <?php foreach ($reporte_citas_mes as $fila): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fila['mes']); ?></td>
                        <td><?php echo htmlspecialchars($fila['cantidad_citas']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div id="historial-paciente" class="report-container">
        <h2>Historial Completo de un Paciente</h2>
        <form action="reportes.php#historial-paciente" method="GET" class="data-form">
            <div class="form-group">
                <label for="id_paciente">Seleccione un paciente para ver su historial:</label>
                <select name="id_paciente" id="id_paciente" onchange="this.form.submit()">
                    <option value="">-- Seleccionar Paciente --</option>
                    <?php foreach ($pacientes_para_historial as $paciente): ?>
                        <option value="<?php echo $paciente['id_paciente']; ?>" <?php echo (isset($_GET['id_paciente']) && $_GET['id_paciente'] == $paciente['id_paciente']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($paciente['nombre_completo']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <?php if (isset($_GET['id_paciente']) && !empty($_GET['id_paciente'])): ?>
            <h3>Mostrando historial para: <?php echo htmlspecialchars($paciente_seleccionado_nombre); ?></h3>
            <?php if (!empty($reporte_historial)): ?>
                <table class="report-table">
                    <thead><tr><th>Tipo de Registro</th><th>Fecha</th><th>Descripción</th><th>Detalles</th></tr></thead>
                    <tbody>
                        <?php foreach ($reporte_historial as $fila): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($fila['tipo_registro']); ?></td>
                                <td><?php echo htmlspecialchars($fila['fecha']); ?></td>
                                <td><?php echo htmlspecialchars($fila['descripcion']); ?></td>
                                <td><?php echo htmlspecialchars($fila['detalles']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?><p class="no-data">Este paciente no tiene registros en su historial.</p><?php endif; ?>
        <?php endif; ?>
    </div>

    <a href="index.php" class="btn-secondary" style="display:inline-block; margin-top: 20px;">Volver al Menú Principal</a>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>