<?php
// meus-agendamentos.php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
exigirLogin();

$db  = getDB();
$uid = $_SESSION['usuario_id'];

// Cancelar agendamento
if (isset($_POST['cancelar_id'])) {
    $cid  = (int)$_POST['cancelar_id'];
    $stmt = $db->prepare(
        "UPDATE agendamentos SET status='cancelado'
         WHERE id=? AND usuario_id=? AND data_consulta >= CURDATE()"
    );
    $stmt->bind_param('ii', $cid, $uid);
    $stmt->execute();
    header('Location: meus-agendamentos.php?msg=cancelado'); exit;
}

$flash = match($_GET['msg'] ?? '') {
    'cancelado' => ['type' => 'warning', 'text' => 'Agendamento cancelado com sucesso.'],
    default     => null,
};

// Buscar agendamentos
$stmt = $db->prepare(
    "SELECT a.*, f.nome AS fisio_nome, f.especialidade, f.crefito
     FROM agendamentos a
     JOIN fisioterapeutas f ON f.id = a.fisioterapeuta_id
     WHERE a.usuario_id = ?
     ORDER BY a.data_consulta DESC, a.hora_consulta DESC"
);
$stmt->bind_param('i', $uid);
$stmt->execute();
$agendamentos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$statusLabel = [
    'pendente'   => ['class' => 'badge-pendente',   'icon' => 'clock',         'txt' => 'Pendente'],
    'confirmado' => ['class' => 'badge-confirmado',  'icon' => 'check-circle',  'txt' => 'Confirmado'],
    'cancelado'  => ['class' => 'badge-cancelado',   'icon' => 'x-circle',      'txt' => 'Cancelado'],
    'concluido'  => ['class' => 'badge-concluido',   'icon' => 'patch-check',   'txt' => 'Concluído'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Meus Agendamentos — <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary-custom shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-heart-pulse-fill me-2"></i><?= SITE_NAME ?></a>
    <div class="d-flex gap-2">
      <a href="agendar.php" class="btn btn-outline-light btn-sm"><i class="bi bi-plus-circle me-1"></i>Novo Agendamento</a>
      <a href="logout.php" class="btn btn-light btn-sm text-primary-custom">Sair</a>
    </div>
  </div>
</nav>

<div class="container py-5">
  <h2 class="fw-bold text-primary-custom mb-1"><i class="bi bi-list-check me-2"></i>Meus Agendamentos</h2>
  <p class="text-muted mb-4">Olá, <strong><?= htmlspecialchars($_SESSION['usuario_nome']) ?></strong>! Aqui estão suas consultas.</p>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
      <i class="bi bi-<?= $flash['type'] === 'warning' ? 'exclamation-triangle' : 'check-circle' ?> me-2"></i>
      <?= $flash['text'] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (empty($agendamentos)): ?>
    <div class="card border-0 shadow-sm text-center py-5">
      <i class="bi bi-calendar-x display-4 text-muted mb-3"></i>
      <p class="text-muted">Você ainda não tem agendamentos.</p>
      <a href="agendar.php" class="btn btn-primary-custom px-4">Agendar Agora</a>
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($agendamentos as $ag):
        $s = $statusLabel[$ag['status']] ?? $statusLabel['pendente'];
        $passado  = $ag['data_consulta'] < date('Y-m-d');
        $cancelável = in_array($ag['status'], ['pendente','confirmado']) && !$passado;
      ?>
      <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <h6 class="fw-bold mb-0"><?= htmlspecialchars($ag['fisio_nome']) ?></h6>
              <span class="badge rounded-pill px-3 py-2 <?= $s['class'] ?>">
                <i class="bi bi-<?= $s['icon'] ?> me-1"></i><?= $s['txt'] ?>
              </span>
            </div>
            <p class="text-muted small mb-1"><i class="bi bi-award me-2 text-primary-custom"></i><?= htmlspecialchars($ag['especialidade']) ?></p>
            <p class="text-muted small mb-1"><i class="bi bi-calendar3 me-2 text-primary-custom"></i><?= date('d/m/Y', strtotime($ag['data_consulta'])) ?></p>
            <p class="text-muted small mb-3"><i class="bi bi-clock me-2 text-primary-custom"></i><?= substr($ag['hora_consulta'], 0, 5) ?></p>
            <?php if ($ag['observacoes']): ?>
              <p class="text-muted small fst-italic border-top pt-2">"<?= htmlspecialchars($ag['observacoes']) ?>"</p>
            <?php endif; ?>
            <?php if ($cancelável): ?>
              <form method="POST" onsubmit="return confirm('Deseja cancelar este agendamento?')">
                <input type="hidden" name="cancelar_id" value="<?= $ag['id'] ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm w-100 mt-2">
                  <i class="bi bi-x-circle me-1"></i>Cancelar
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
