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
            JOIN medico m ON c.id_medico = m.id_medico" ;
    
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

<?php
// Che, este archivo es solo un sirviente, no una página completa.
require_once 'includes/db_connection.php';
header('Content-Type: application/json'); // Avisamos que la respuesta es JSON.

// Solo si nos pasan el ID de la especialidad, hacemos algo.
if (isset($_GET['id_especialidad'])) {
    $id_especialidad = $_GET['id_especialidad'];

    try {
        // Preparamos la consulta pa' jalar solo los médicos de esa especialidad.
        $sql = "SELECT id_medico, CONCAT(nombre, ' ', apellido) AS nombre_completo 
                FROM medico 
                WHERE id_especialidad = ? 
                ORDER BY apellido ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_especialidad]);
        $medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Devolvemos la lista de médicos en formato JSON.
        echo json_encode($medicos);

    } catch (PDOException $e) {
        // Si algo falla, mandamos un error.
        echo json_encode(['error' => 'No se pudieron cargar los médicos.']);
    }
} else {
    // Si no nos pasan especialidad, devolvemos una lista vacía.
    echo json_encode([]);
}