<?php 
// Incluimos la cabecera reutilizable
include 'includes/header.php'; 
?>

<h2>Menú Principal</h2>
<p>Seleccione una opción para comenzar a gestionar el consultorio.</p>

<nav class="main-nav">
    <ul>
        <li><a href="gestionar_citas.php" style="background-color: #ffc107; color: #333;">✅ Gestionar Citas del Día</a></li>
        
        <li><a href="calendario_citas.php" style="background-color: #17a2b8;">🗓️ Calendario de Citas</a></li>
        <li><a href="agendar_cita.php">Agendar Citas</a></li>
        <li><a href="registrar_paciente.php">Registrar Paciente</a></li>
        <li><a href="generar_factura.php">Generar Factura</a></li>
        <li><a href="reportes.php">Ver Reportes</a></li>
    </ul>
</nav>

<?php 
// Incluimos el pie de página reutilizable
include 'includes/footer.php'; 
?>