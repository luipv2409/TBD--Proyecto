<?php
/**
 * Script de conexión a la base de datos GESCOMED.
 * * Usamos PDO para una conexión segura y moderna.
 * Este archivo se incluirá en todas las páginas que necesiten interactuar con la BD.
 */

// 1. Parámetros de conexión para tu entorno local (XAMPP)
$host       = 'localhost';        // El servidor donde está la base de datos
$db_name    = 'gescomed';         // El nombre de tu base de datos
$username   = 'root';             // Tu usuario de MySQL
$password   = '';             // Tu contraseña de MySQL
$charset    = 'utf8mb4';          // El conjunto de caracteres para soportar acentos y emojis

// 2. Creación del DSN (Data Source Name)
// Esta es la cadena que le dice a PDO a qué base de datos conectarse.
$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

// 3. Opciones de configuración para PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Manejar errores como excepciones
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devolver resultados como arrays asociativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Usar preparaciones de sentencias nativas de MySQL
];

// 4. Bloque try-catch para manejar errores de conexión
try {
    // Intentamos crear la instancia de PDO (la conexión)
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    // Si la conexión falla, se captura la excepción y se detiene la ejecución
    // Mostramos un mensaje amigable en lugar del error técnico.
    throw new \PDOException("Error de conexión: No se pudo conectar a la base de datos.", (int)$e->getCode());
}

// Si todo sale bien, la variable $pdo está disponible para ser usada en otros scripts.
?>