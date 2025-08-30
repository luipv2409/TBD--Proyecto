<?php
// Che, este archivo es solo un sirviente, no una página completa.
require_once 'includes/db_connection.php';
header('Content-Type: application/json'); // Avisamos que la respuesta es JSON.

// Solo si nos pasan el ID de la especialidad, hacemos algo.
if (isset($_GET['id_especialidad']) && !empty($_GET['id_especialidad'])) {
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