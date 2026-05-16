<?php
// cadastro.php
require_once 'includes/config.php';
require_once 'includes/auth.php';
iniciarSessao();
if (usuarioLogado()) { header('Location: index.php'); exit; }

$erro = $sucesso = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome       = trim($_POST['nome'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $senha      = $_POST['senha'] ?? '';
    $confirma   = $_POST['confirma'] ?? '';
    $telefone   = trim($_POST['telefone'] ?? '');
    $nascimento = $_POST['nascimento'] ?? '';
    $cpf        = preg_replace('/\D/', '', $_POST['cpf'] ?? '');

    if (!$nome || !$email || !$senha || !$cpf) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } elseif ($senha !== $confirma) {
        $erro = 'As senhas não coincidem.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter ao menos 6 caracteres.';
    } else {
        $r = registrarUsuario($nome, $email, $senha, $telefone, $nascimento, $cpf);
        if ($r['ok']) {
            $sucesso = 'Cadastro realizado com sucesso! Você pode fazer login agora.';
        } else {
            $erro = $r['erro'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cadastro — <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light">

<div class="min-vh-100 d-flex flex-column justify-content-center py-5">
  <div class="container" style="max-width:520px">

    <div class="text-center mb-4">
      <a href="index.php" class="text-decoration-none">
        <h2 class="text-primary-custom fw-bold"><i class="bi bi-heart-pulse-fill me-2"></i><?= SITE_NAME ?></h2>
      </a>
      <p class="text-muted">Crie sua conta gratuitamente</p>
    </div>

    <?php if ($erro): ?>
      <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-x-circle me-2"></i><?= $erro ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($sucesso): ?>
      <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= $sucesso ?> <a href="login.php">Fazer login</a></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">
        <form method="POST" id="formCadastro">
          <div class="mb-3">
            <label class="form-label fw-semibold">Nome Completo <span class="text-danger">*</span></label>
            <input type="text" name="nome" class="form-control" placeholder="Seu nome completo" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">E-mail <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" placeholder="email@exemplo.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">CPF <span class="text-danger">*</span></label>
            <input type="text" name="cpf" id="cpf" class="form-control" placeholder="000.000.000-00" maxlength="14" value="<?= htmlspecialchars($_POST['cpf'] ?? '') ?>" required>
          </div>
          <div class="row g-3 mb-3">
            <div class="col">
              <label class="form-label fw-semibold">Telefone</label>
              <input type="tel" name="telefone" id="telefone" class="form-control" placeholder="(11) 90000-0000" value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>">
            </div>
            <div class="col">
              <label class="form-label fw-semibold">Data de Nascimento</label>
              <input type="date" name="nascimento" class="form-control" value="<?= htmlspecialchars($_POST['nascimento'] ?? '') ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Senha <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" name="senha" id="senha" class="form-control" placeholder="Mínimo 6 caracteres" required>
              <button class="btn btn-outline-secondary" type="button" onclick="toggleSenha('senha')"><i class="bi bi-eye"></i></button>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Confirmar Senha <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" name="confirma" id="confirma" class="form-control" placeholder="Repita a senha" required>
              <button class="btn btn-outline-secondary" type="button" onclick="toggleSenha('confirma')"><i class="bi bi-eye"></i></button>
            </div>
          </div>
          <button type="submit" class="btn btn-primary-custom w-100 py-2 fw-semibold">
            <i class="bi bi-person-plus me-2"></i>Criar Conta
          </button>
        </form>
      </div>
    </div>

    <p class="text-center mt-3 text-muted">Já tem conta? <a href="login.php" class="text-primary-custom fw-semibold">Fazer login</a></p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/mascaras.js"></script>
<script>
function toggleSenha(id) {
  const el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
