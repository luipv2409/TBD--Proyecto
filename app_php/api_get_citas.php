<?php
// Primero lo primero, nos conectamos a la base de datos, pues.
require_once 'includes/db_connection.php';

// Ché, avisamos al navegador que le vamos a mandar puro JSON, ¿ya?
header('Content-Type: application/json');

try {
    // Armamos la consulta pa' jalar todas las citas con sus datos.
    $sql = "SELECT 
                c.id_cita,
                c.fecha_cita,
                c.hora_cita,
                c.estado,
                CONCAT(p.nombre, ' ', p.apellido) AS paciente,
                CONCAT('Dr. ', m.apellido) AS medico
            FROM cita c
            JOIN paciente p ON c.id_paciente = p.id_paciente
            JOIN medico m ON c.id_medico = m.id_medico WHERE hora_cita > '12:00'" ;
    
    $stmt = $pdo->query($sql);
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $eventos = [];
    foreach ($citas as $cita) {
        // Pa' que se vea churo, le cambiamos el colorcito según el estado de la cita.
        $color = '#3498db'; // Azul pa' los pendientes
        if ($cita['estado'] == 'atendida') {
            $color = '#28a745'; // Verde si ya fue atendido
        } elseif ($cita['estado'] == 'cancelada') {
            $color = '#dc3545'; // Rojo si la cancelaron
        }

        // Armamos el paquetito de datos como le gusta a FullCalendar.
        $eventos[] = [
            'title' => $cita['paciente'] . " (" . $cita['medico'] . ")",
            'start' => $cita['fecha_cita'] . 'T' . $cita['hora_cita'],
            'color' => $color,
            'extendedProps' => [
                'estado' => ucfirst($cita['estado'])
            ]
        ];
    }
    
    // Mandamos el JSON de una vez. ¡Listo el pollo!
    echo json_encode($eventos);

} catch (PDOException $e) {
    // Si algo sale mal, le mandamos un mensajito de error.
    echo json_encode(['error' => $e->getMessage()]);
}