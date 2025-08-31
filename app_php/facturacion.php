<?php
require_once 'includes/db_connection.php';
$error_message = '';

// --- LÓGICA DE ACCIONES (PAGAR / ANULAR) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_factura'])) {
    $id_factura = $_POST['id_factura'];
    $accion = $_POST['accion'];

    if ($accion === 'pagar') {
        $sql_update = "UPDATE factura SET estado_pago = 'pagada', fecha_pago = NOW() WHERE id_factura = ?";
    } elseif ($accion === 'anular') {
        $sql_update = "UPDATE factura SET estado_pago = 'anulada' WHERE id_factura = ?";
    }

    if (isset($sql_update)) {
        try {
            $stmt = $pdo->prepare($sql_update);
            $stmt->execute([$id_factura]);
            header("Location: " . $_SERVER['REQUEST_URI']); // Recargamos la página para ver el cambio
            exit;
        } catch (PDOException $e) {
            $error_message = "Error al actualizar la factura: " . $e->getMessage();
        }
    }
}

// --- LÓGICA DE FILTROS Y BÚSQUEDA ---
try {
    // Jalamos los filtros de la URL, si existen.
    $fecha_inicio = $_GET['fecha_inicio'] ?? '';
    $fecha_fin = $_GET['fecha_fin'] ?? '';
    $estado_filtro = $_GET['estado_filtro'] ?? 'todos';

    // Armamos la consulta de a pedacitos.
    $sql_base = "SELECT f.*, CONCAT(p.nombre, ' ', p.apellido) AS paciente_nombre
                 FROM factura f
                 JOIN cita c ON f.id_cita = c.id_cita
                 JOIN paciente p ON c.id_paciente = p.id_paciente";
    $params = [];
    $where_clauses = [];

    if (!empty($fecha_inicio)) {
        $where_clauses[] = "f.fecha_emision >= ?";
        $params[] = $fecha_inicio;
    }
    if (!empty($fecha_fin)) {
        $where_clauses[] = "f.fecha_emision <= ?";
        $params[] = $fecha_fin;
    }
    if ($estado_filtro !== 'todos' && !empty($estado_filtro)) {
        $where_clauses[] = "f.estado_pago = ?";
        $params[] = $estado_filtro;
    }

    if (!empty($where_clauses)) {
        $sql_base .= " WHERE " . implode(' AND ', $where_clauses);
    }
    $sql_base .= " ORDER BY f.fecha_emision DESC, f.id_factura DESC";

    $stmt = $pdo->prepare($sql_base);
    $stmt->execute($params);
    $facturas = $stmt->fetchAll();

    // --- LÓGICA DE ARQUEO / RESUMEN ---
    $total_facturado = 0;
    $total_pagado = 0;
    $total_pendiente = 0;
    foreach ($facturas as $factura) {
        if ($factura['estado_pago'] !== 'anulada') {
            $total_facturado += $factura['monto_total'];
            if ($factura['estado_pago'] === 'pagada') {
                $total_pagado += $factura['monto_total'];
            } else {
                $total_pendiente += $factura['monto_total'];
            }
        }
    }

} catch (PDOException $e) {
    $error_message = "Error al cargar las facturas: " . $e->getMessage();
    $facturas = [];
}
?>

<?php include 'includes/header.php'; ?>

<h2>Módulo de Facturación y Arqueo</h2>
<p>Filtra y gestiona todas las facturas emitidas por el sistema.</p>

<div class="report-container">
    <h3>Filtrar Facturas</h3>
    <form method="GET" class="data-form">
        <div style="display: flex; gap: 15px; align-items: flex-end;">
            <div class="form-group"><label for="fecha_inicio">Desde:</label><input type="date" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>"></div>
            <div class="form-group"><label for="fecha_fin">Hasta:</label><input type="date" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>"></div>
            <div class="form-group">
                <label for="estado_filtro">Estado:</label>
                <select name="estado_filtro">
                    <option value="todos" <?php if($estado_filtro == 'todos') echo 'selected'; ?>>Todos</option>
                    <option value="pendiente" <?php if($estado_filtro == 'pendiente') echo 'selected'; ?>>Pendiente</option>
                    <option value="pagada" <?php if($estado_filtro == 'pagada') echo 'selected'; ?>>Pagada</option>
                    <option value="anulada" <?php if($estado_filtro == 'anulada') echo 'selected'; ?>>Anulada</option>
                </select>
            </div>
            <div class="form-group"><button type="submit" class="btn">Filtrar</button></div>
        </div>
    </form>
</div>

<div class="report-container">
    <h3>Resumen del Periodo Filtrado</h3>
    <p><strong>Total Facturado (Válido):</strong> Bs. <?php echo number_format($total_facturado, 2); ?></p>
    <p style="color: green;"><strong>Total Pagado:</strong> Bs. <?php echo number_format($total_pagado, 2); ?></p>
    <p style="color: orange;"><strong>Total Pendiente por Cobrar:</strong> Bs. <?php echo number_format($total_pendiente, 2); ?></p>
</div>

<div class="report-container">
    <h3>Listado de Facturas</h3>
    <table class="report-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha Emisión</th>
                <th>Cliente</th>
                <th>NIT/CI</th>
                <th>Monto Total</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($facturas) > 0): ?>
                <?php foreach ($facturas as $factura): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($factura['id_factura']); ?></td>
                        <td><?php echo htmlspecialchars($factura['fecha_emision']); ?></td>
                        <td><?php echo htmlspecialchars($factura['razon_social_cliente']); ?></td>
                        <td><?php echo htmlspecialchars($factura['nit_cliente']); ?></td>
                        <td>Bs. <?php echo htmlspecialchars(number_format($factura['monto_total'], 2)); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo htmlspecialchars($factura['estado_pago']); ?>">
                                <?php echo htmlspecialchars(ucfirst($factura['estado_pago'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($factura['estado_pago'] == 'pendiente'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id_factura" value="<?php echo $factura['id_factura']; ?>">
                                    <input type="hidden" name="accion" value="pagar">
                                    <button type="submit" class="btn-confirmar">Marcar Pagada</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($factura['estado_pago'] != 'anulada'): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que quieres anular esta factura? Esta acción no se puede deshacer.');">
                                    <input type="hidden" name="id_factura" value="<?php echo $factura['id_factura']; ?>">
                                    <input type="hidden" name="accion" value="anular">
                                    <button type="submit" class="btn-cancelar">Anular</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align:center;">No se encontraron facturas con los filtros seleccionados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<a href="index.php" class="btn-secondary" style="display:inline-block; margin-top: 20px;">Volver al Menú Principal</a>

<?php include 'includes/footer.php'; ?>