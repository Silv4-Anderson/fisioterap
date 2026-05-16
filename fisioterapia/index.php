<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// index.php — Página inicial
require_once 'includes/config.php';
require_once 'includes/auth.php';
iniciarSessao();
$logado = usuarioLogado();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= SITE_NAME ?> — Agendamento de Fisioterapia</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary-custom shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">
      <i class="bi bi-heart-pulse-fill me-2"></i><?= SITE_NAME ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto align-items-center gap-2">
        <li class="nav-item"><a class="nav-link" href="index.php">Início</a></li>
        <?php if ($logado): ?>
          <li class="nav-item"><a class="nav-link" href="agendar.php"><i class="bi bi-calendar-plus me-1"></i>Agendar</a></li>
          <li class="nav-item"><a class="nav-link" href="meus-agendamentos.php"><i class="bi bi-list-check me-1"></i>Meus Agendamentos</a></li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['usuario_nome']) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="btn btn-outline-light btn-sm px-3" href="login.php">Entrar</a></li>
          <li class="nav-item"><a class="btn btn-light btn-sm px-3 text-primary-custom fw-semibold" href="cadastro.php">Cadastrar</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- HERO -->
<section class="hero-section d-flex align-items-center">
  <div class="container text-center text-white">
    <h1 class="display-4 fw-bold mb-3">Agende sua Consulta com <br>Facilidade e Segurança</h1>
    <p class="lead mb-4">Escolha seu fisioterapeuta, selecione o horário e confirme em minutos.<br>Sem filas, sem espera.</p>
    <a href="<?= $logado ? 'agendar.php' : 'cadastro.php' ?>" class="btn btn-light btn-lg px-5 fw-semibold shadow">
      <i class="bi bi-calendar2-check me-2"></i>
      <?= $logado ? 'Fazer Agendamento' : 'Começar Agora' ?>
    </a>
  </div>
</section>

<!-- BENEFÍCIOS -->
<section class="py-5">
  <div class="container">
    <h2 class="text-center fw-bold mb-5 text-primary-custom">Por que usar o <?= SITE_NAME ?>?</h2>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm card-hover text-center p-4">
          <i class="bi bi-calendar-check fs-1 text-primary-custom mb-3"></i>
          <h5 class="fw-bold">Agendamento Online</h5>
          <p class="text-muted">Marque sua consulta a qualquer hora, de qualquer lugar, em poucos cliques.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm card-hover text-center p-4">
          <i class="bi bi-envelope-check fs-1 text-primary-custom mb-3"></i>
          <h5 class="fw-bold">Confirmação por E-mail</h5>
          <p class="text-muted">Receba um e-mail de confirmação com todos os detalhes da sua consulta.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm card-hover text-center p-4">
          <i class="bi bi-phone fs-1 text-primary-custom mb-3"></i>
          <h5 class="fw-bold">Notificação via Telegram</h5>
          <p class="text-muted">A clínica é notificada em tempo real sobre novos agendamentos.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FISIOTERAPEUTAS -->
<section class="py-5 bg-light">
  <div class="container">
    <h2 class="text-center fw-bold mb-5 text-primary-custom">Nossa Equipe</h2>
    <div class="row g-4 justify-content-center">
      <?php
      require_once 'includes/db.php';
      $fisios = getDB()->query("SELECT * FROM fisioterapeutas ORDER BY nome");
      while ($f = $fisios->fetch_assoc()):
      ?>
      <div class="col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm text-center p-4 card-hover">
          <div class="avatar-circle mx-auto mb-3">
            <i class="bi bi-person-circle fs-1 text-primary-custom"></i>
          </div>
          <h5 class="fw-bold mb-1"><?= htmlspecialchars($f['nome']) ?></h5>
          <span class="badge bg-primary-soft text-primary-custom mb-2"><?= htmlspecialchars($f['especialidade']) ?></span>
          <small class="text-muted d-block"><?= htmlspecialchars($f['crefito']) ?></small>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer class="bg-primary-custom text-white text-center py-4 mt-auto">
  <p class="mb-0">&copy; 2026 <?= SITE_NAME ?></p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
