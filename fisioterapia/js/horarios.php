<?php
// js/horarios.php — API AJAX: retorna horários disponíveis
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

$fisio_id = (int)($_GET['fisio_id'] ?? 0);
$data     = $_GET['data'] ?? '';

if (!$fisio_id || !$data || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    echo json_encode([]);
    exit;
}

$db = getDB();

// Dia da semana: 0=Dom … 6=Sab
$diaSemana = (int)date('w', strtotime($data));

// Horários cadastrados para este fisioterapeuta neste dia
$stmt = $db->prepare(
    "SELECT hora_inicio FROM horarios
     WHERE fisioterapeuta_id = ? AND dia_semana = ? AND disponivel = 1
     ORDER BY hora_inicio"
);
$stmt->bind_param('ii', $fisio_id, $diaSemana);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Remover horários já agendados
$ocupados = [];
$stmt2 = $db->prepare(
    "SELECT hora_consulta FROM agendamentos
     WHERE fisioterapeuta_id = ? AND data_consulta = ? AND status NOT IN ('cancelado')"
);
$stmt2->bind_param('is', $fisio_id, $data);
$stmt2->execute();
foreach ($stmt2->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
    $ocupados[] = $r['hora_consulta'];
}

$disponíveis = [];
foreach ($rows as $r) {
    $hora = substr($r['hora_inicio'], 0, 5); // HH:MM
    if (!in_array($hora . ':00', $ocupados) && !in_array($hora, $ocupados)) {
        $disponíveis[] = $hora;
    }
}

echo json_encode($disponíveis);
