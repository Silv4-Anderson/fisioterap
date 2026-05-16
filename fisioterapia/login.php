<?php
// login.php
require_once 'includes/config.php';
require_once 'includes/auth.php';
iniciarSessao();
if (usuarioLogado()) { header('Location: index.php'); exit; }

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $r = fazerLogin($email, $senha);
    if ($r['ok']) {
        header('Location: index.php'); exit;
    }
    $erro = $r['erro'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light">

<div class="min-vh-100 d-flex flex-column justify-content-center py-5">
  <div class="container" style="max-width:420px">

    <div class="text-center mb-4">
      <a href="index.php" class="text-decoration-none">
        <h2 class="text-primary-custom fw-bold"><i class="bi bi-heart-pulse-fill me-2"></i><?= SITE_NAME ?></h2>
      </a>
      <p class="text-muted">Acesse sua conta</p>
    </div>

    <?php if ($erro): ?>
      <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-x-circle me-2"></i><?= $erro ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">
        <form method="POST">
          <div class="mb-3">
            <label class="form-label fw-semibold">E-mail</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-envelope"></i></span>
              <input type="email" name="email" class="form-control" placeholder="email@exemplo.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Senha</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input type="password" name="senha" id="senha" class="form-control" placeholder="Sua senha" required>
              <button class="btn btn-outline-secondary" type="button" onclick="toggleSenha()"><i class="bi bi-eye"></i></button>
            </div>
          </div>
          <button type="submit" class="btn btn-primary-custom w-100 py-2 fw-semibold">
            <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
          </button>
        </form>
      </div>
    </div>

    <p class="text-center mt-3 text-muted">Não tem conta? <a href="cadastro.php" class="text-primary-custom fw-semibold">Cadastre-se grátis</a></p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSenha() {
  const el = document.getElementById('senha');
  el.type = el.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
