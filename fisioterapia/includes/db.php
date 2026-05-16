<?php
// =============================================
// includes/db.php — Conexão com o banco de dados
// =============================================
require_once __DIR__ . '/config.php';

function getDB(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die('Erro de conexão: ' . $conn->connect_error);
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}
?>
