<?php
// =============================================
// api/login.php — Endpoint de autenticação da API
// POST /api/login.php
// Body JSON: { "email": "...", "senha": "..." }
// Retorna:   { "token": "eyJ...", "expira_em": 3600, "usuario": {...} }
// =============================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/auth_api.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Apenas POST permitido.']);
    exit;
}

$body  = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim($body['email'] ?? '');
$senha = $body['senha'] ?? '';

if (!$email || !$senha) {
    http_response_code(422);
    echo json_encode(['erro' => 'email e senha são obrigatórios.']);
    exit;
}

$db   = getDB();
$stmt = $db->prepare("SELECT id, nome, senha FROM usuarios WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !password_verify($senha, $user['senha'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Credenciais inválidas.']);
    exit;
}

$token = gerarJwt($user['id'], $user['nome']);
http_response_code(200);
echo json_encode([
    'token'     => $token,
    'expira_em' => JWT_EXPIRY,
    'usuario'   => ['id' => $user['id'], 'nome' => $user['nome'], 'email' => $email],
], JSON_UNESCAPED_UNICODE);
