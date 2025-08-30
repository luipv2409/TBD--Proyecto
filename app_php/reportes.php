<?php
// Incluir la conexión a la base de datos
require_once 'includes/db_connection.php';

// --- LÓGICA PARA CARGAR DATOS DE TODOS LOS REPORTES ---

try {
    // 1. Reporte de Ingresos por Especialidad
    $sql_ingresos = "SELECT e.nombre_especialidad, SUM(f.monto_total) AS total_ingresos
                     FROM factura f
                     JOIN cita c ON f.id_cita = c.id_cita
                     JOIN medico m ON c.id_medico = m.id_medico
                     JOIN especialidad e ON m.id_especialidad = e.id_especialidad
                     WHERE f.estado_pago = 'pagada'
                     GROUP BY e.nombre_especialidad ORDER BY total_ingresos DESC";
    $reporte_ingresos = $pdo->query($sql_ingresos)->fetchAll();

    // 2. Reporte de Citas por Médico
    $sql_citas_medico = "SELECT CONCAT(m.nombre, ' ', m.apellido) AS nombre_medico, 
                                fn_contar_citas_medico(m.id_medico) AS citas_atendidas
                         FROM medico m ORDER BY citas_atendidas DESC";
    $reporte_citas_medico = $pdo->query($sql_citas_medico)->fetchAll();

    // 3. Reporte de Pacientes por Rango de Edad
    $sql_edad = "SELECT CASE 
                    WHEN fn_obtener_edad_paciente(fecha_nacimiento) BETWEEN 0 AND 17 THEN '0-17 (Niños/Adolescentes)'
                    WHEN fn_obtener_edad_paciente(fecha_nacimiento) BETWEEN 18 AND 35 THEN '18-35 (Jóvenes Adultos)'
                    WHEN fn_obtener_edad_paciente(fecha_nacimiento) BETWEEN 36 AND 60 THEN '36-60 (Adultos)'
                    ELSE '61+ (Adultos Mayores)'
                 END AS rango_edad, COUNT(id_paciente) AS numero_de_pacientes
                 FROM paciente GROUP BY rango_edad ORDER BY rango_edad";
    $reporte_edad = $pdo->query($sql_edad)->fetchAll();

    // 4. Reporte de Citas por Mes (Último Año)
    $sql_citas_mes = "SELECT DATE_FORMAT(fecha_cita, '%Y-%m') AS mes, COUNT(id_cita) AS cantidad_citas
                      FROM cita
                      WHERE estado = 'atendida' AND fecha_cita >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
                      GROUP BY mes ORDER BY mes ASC";
    $reporte_citas_mes = $pdo->query($sql_citas_mes)->fetchAll();


    /*/ 2. Reporte de Citas por Médico
    $sql_citas_medico = "SELECT CONCAT(m.nombre, ' ', m.apellido) AS nombre_medico, 
                                fn_contar_citas_medico(m.id_medico) AS citas_atendidas
                         FROM medico m ORDER BY citas_atendidas DESC";
    $reporte_citas_medico = $pdo->query($sql_citas_medico)->fetchAll();

    //4.5 Ingreso de medicos que atendieron por citas
    $sql_citas_medico2 = "SELECT CONCAT(m.nombre, ' ', m.apellido) AS nombre_medico, 
                                c.fecha_cita(c.id_medico) AS fecha_cita
                         FROM medico m, cita c";
    $reporte_citas_medico2 = $pdo->query($sql_citas_medico2)->fetchAll();*/


    // 5. Reporte de Facturas Pendientes
    $sql_facturas_pendientes = "SELECT f.id_factura, f.fecha_emision, f.monto_total,
                                       CONCAT(p.nombre, ' ', p.apellido) AS nombre_paciente, p.telefono
                                FROM factura f
                                JOIN cita c ON f.id_cita = c.id_cita
                                JOIN paciente p ON c.id_paciente = p.id_paciente
                                WHERE f.estado_pago = 'pendiente' ORDER BY f.fecha_emision ASC";
    $reporte_facturas_pendientes = $pdo->query($sql_facturas_pendientes)->fetchAll();

    // Lógica para el reporte interactivo de Historial del Paciente
    $pacientes_para_historial = $pdo->query("SELECT id_paciente, CONCAT(nombre, ' ', apellido) AS nombre_completo FROM paciente ORDER BY nombre_completo ASC")->fetchAll();
    $reporte_historial = [];
    $paciente_seleccionado_nombre = '';

    if (isset($_GET['id_paciente']) && !empty($_GET['id_paciente'])) {
        $id_paciente_buscado = $_GET['id_paciente'];

        // Obtener el nombre del paciente para el título
        $stmt_nombre = $pdo->prepare("SELECT CONCAT(nombre, ' ', apellido) AS nombre_completo FROM paciente WHERE id_paciente = ?");
        $stmt_nombre->execute([$id_paciente_buscado]);
        $paciente_seleccionado_nombre = $stmt_nombre->fetchColumn();

        $sql_historial = "SELECT 'Cita' AS tipo_registro, c.fecha_cita AS fecha, CONCAT('Cita con Dr. ', m.apellido, ' (', e.nombre_especialidad, ')') AS descripcion, c.estado AS detalles
                          FROM cita c JOIN medico m ON c.id_medico = m.id_medico JOIN especialidad e ON m.id_especialidad = e.id_especialidad
                          WHERE c.id_paciente = ?
                          UNION ALL
                          SELECT 'Diagnóstico' AS tipo_registro, hc.fecha_registro AS fecha, hc.diagnostico AS descripcion, CONCAT('Tratamiento: ', hc.tratamiento) AS detalles
                          FROM historial_clinico hc WHERE hc.id_paciente = ?
                          UNION ALL
                          SELECT 'Factura' AS tipo_registro, f.fecha_emision AS fecha, CONCAT('Factura por consulta del ', c.fecha_cita) AS descripcion, CONCAT('Monto: ', f.monto_total, ', Estado: ', f.estado_pago) AS detalles
                          FROM factura f JOIN cita c ON f.id_cita = c.id_cita WHERE c.id_paciente = ?
                          ORDER BY fecha DESC";
        $stmt_historial = $pdo->prepare($sql_historial);
        $stmt_historial->execute([$id_paciente_buscado, $id_paciente_buscado, $id_paciente_buscado]);
        $reporte_historial = $stmt_historial->fetchAll();
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
        <h2>Facturas Pendientes de Pago</h2>
        <?php if (!empty($reporte_facturas_pendientes)): ?>
            <table class="report-table">
                <thead><tr><th>ID Factura</th><th>Fecha Emisión</th><th>Monto Total</th><th>Paciente</th><th>Teléfono</th></tr></thead>
                <tbody>
                    <?php foreach ($reporte_facturas_pendientes as $fila): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fila['id_factura']); ?></td>
                            <td><?php echo htmlspecialchars($fila['fecha_emision']); ?></td>
                            <td>Bs. <?php echo htmlspecialchars(number_format($fila['monto_total'], 2)); ?></td>
                            <td><?php echo htmlspecialchars($fila['nombre_paciente']); ?></td>
                            <td><?php echo htmlspecialchars($fila['telefono']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-data">No hay facturas pendientes.</p>
        <?php endif; ?>
    </div>

    <div class="report-container">
        <h2>Total de Ingresos por Especialidad</h2>
        <?php if (!empty($reporte_ingresos)): ?>
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
        <?php else: ?>
            <p class="no-data">No hay ingresos registrados para mostrar.</p>
        <?php endif; ?>
    </div>
    
    <div class="report-container">
        <h2>Citas Atendidas por Médico</h2>
        <?php if (!empty($reporte_citas_medico)): ?>
            <table class="report-table">
                <thead><tr><th>Médico</th><th>N° de Citas Atendidas</th></tr></thead>
                <tbody>
                    <?php foreach ($reporte_citas_medico as $fila): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fila['nombre_medico']); ?></td>
                            <td><?php echo htmlspecialchars($fila['citas_atendidas']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-data">No hay datos de citas para mostrar.</p>
        <?php endif; ?>
    </div>

    
    <div class="report-container">
        <h2>Medicos que atendieron a las citas</h2>
        <?php if (!empty($reporte_citas_medico2)): ?>
            <table class="report-table">
                <thead><tr><th>Médico</th><th>Citas en Fecha</th></tr></thead>
                <tbody>
                    <?php foreach ($reporte_citas_medico2 as $fila): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fila['nombre_medico']); ?></td>
                            <td><?php echo htmlspecialchars($fila['fecha_cita']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-data">No hay datos de citas para mostrar.</p>
        <?php endif; ?>
    </div>

    <div class="report-container">
        <h2>Distribución de Pacientes por Edad</h2>
        <?php if (!empty($reporte_edad)): ?>
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
        <?php else: ?>
            <p class="no-data">No hay pacientes registrados para calcular rangos de edad.</p>
        <?php endif; ?>
    </div>

    <div class="report-container">
        <h2>Citas Atendidas por Mes (Último Año)</h2>
        <?php if (!empty($reporte_citas_mes)): ?>
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
        <?php else: ?>
            <p class="no-data">No hay citas en el último año para mostrar.</p>
        <?php endif; ?>
    </div>
    
    <div class="report-container">
        <h2>Historial Completo de un Paciente</h2>
        <form action="reportes.php" method="GET" class="data-form">
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
            <?php else: ?>
                <p class="no-data">Este paciente no tiene registros en su historial.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <a href="index.php" class="btn-secondary" style="display:inline-block; margin-top: 20px;">Volver al Menú Principal</a>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>