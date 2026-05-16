<?php
// =============================================
// includes/auth.php — Autenticação e sessão
// =============================================
require_once __DIR__ . '/db.php';

function iniciarSessao(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

function usuarioLogado(): bool {
    iniciarSessao();
    return isset($_SESSION['usuario_id']);
}

function exigirLogin(): void {
    if (!usuarioLogado()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

function registrarUsuario(string $nome, string $email, string $senha, string $telefone, string $nascimento, string $cpf): array {
    $db   = getDB();
    $hash = password_hash($senha, PASSWORD_DEFAULT);

    $stmt = $db->prepare(
        "INSERT INTO usuarios (nome, email, senha, telefone, data_nascimento, cpf) VALUES (?,?,?,?,?,?)"
    );
    $stmt->bind_param('ssssss', $nome, $email, $hash, $telefone, $nascimento, $cpf);

    if ($stmt->execute()) {
        return ['ok' => true, 'id' => $db->insert_id];
    }
    // E-mail ou CPF duplicado
    return ['ok' => false, 'erro' => 'E-mail ou CPF já cadastrado.'];
}

function fazerLogin(string $email, string $senha): array {
    $db   = getDB();
    $stmt = $db->prepare("SELECT id, nome, senha FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row && password_verify($senha, $row['senha'])) {
        iniciarSessao();
        $_SESSION['usuario_id']   = $row['id'];
        $_SESSION['usuario_nome'] = $row['nome'];
        return ['ok' => true];
    }
    return ['ok' => false, 'erro' => 'E-mail ou senha incorretos.'];
}

function fazerLogout(): void {
    iniciarSessao();
    session_destroy();
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}
?>
