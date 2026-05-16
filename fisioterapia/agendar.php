<?php
// agendar.php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/notificacoes.php';
exigirLogin();

$db = getDB();
$sucesso = $erro = '';

// ── Processar POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fisio_id = (int)($_POST['fisioterapeuta_id'] ?? 0);
    $data     = $_POST['data_consulta'] ?? '';
    $hora     = $_POST['hora_consulta'] ?? '';
    $obs      = trim($_POST['observacoes'] ?? '');
    $uid      = $_SESSION['usuario_id'];

    if (!$fisio_id || !$data || !$hora) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } elseif (strtotime($data) < strtotime(date('Y-m-d'))) {
        $erro = 'A data não pode ser no passado.';
    } else {
        // Verificar conflito
        $chk = $db->prepare(
            "SELECT id FROM agendamentos
             WHERE fisioterapeuta_id=? AND data_consulta=? AND hora_consulta=?
             AND status NOT IN ('cancelado')"
        );
        $chk->bind_param('iss', $fisio_id, $data, $hora);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $erro = 'Este horário já está ocupado. Escolha outro.';
        } else {
            $ins = $db->prepare(
                "INSERT INTO agendamentos (usuario_id, fisioterapeuta_id, data_consulta, hora_consulta, observacoes, status)
                 VALUES (?,?,?,?,?,'confirmado')"
            );
            $ins->bind_param('iisss', $uid, $fisio_id, $data, $hora, $obs);
            if ($ins->execute()) {
                // Buscar dados para notificações
                $fisio = $db->query("SELECT nome, especialidade, email FROM fisioterapeutas WHERE id=$fisio_id")->fetch_assoc();
                $user  = $db->query("SELECT nome, email FROM usuarios WHERE id=$uid")->fetch_assoc();
                $dados = [
                    'nome'         => $user['nome'],
                    'email'        => $user['email'],
                    'fisio'        => $fisio['nome'],
                    'especialidade'=> $fisio['especialidade'],
                    'data'         => date('d/m/Y', strtotime($data)),
                    'hora'         => $hora,
                ];
                enviarEmailConfirmacao($dados);
                notificarNovoAgendamento($dados);
                $sucesso = 'Agendamento realizado com sucesso! Você receberá um e-mail de confirmação.';
            } else {
                $erro = 'Erro ao salvar o agendamento. Tente novamente.';
            }
        }
    }
}

// ── Buscar fisioterapeutas ─────────────────────────────────────
$fisios = $db->query("SELECT * FROM fisioterapeutas ORDER BY nome");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Agendar Consulta — <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary-custom shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-heart-pulse-fill me-2"></i><?= SITE_NAME ?></a>
    <div class="d-flex gap-2">
      <a href="meus-agendamentos.php" class="btn btn-outline-light btn-sm">Meus Agendamentos</a>
      <a href="logout.php" class="btn btn-light btn-sm text-primary-custom">Sair</a>
    </div>
  </div>
</nav>

<div class="container py-5" style="max-width:700px">
  <h2 class="fw-bold text-primary-custom mb-1"><i class="bi bi-calendar-plus me-2"></i>Novo Agendamento</h2>
  <p class="text-muted mb-4">Olá, <strong><?= htmlspecialchars($_SESSION['usuario_nome']) ?></strong>! Escolha o fisioterapeuta e o horário desejado.</p>

  <?php if ($erro): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-x-circle me-2"></i><?= $erro ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif; ?>
  <?php if ($sucesso): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= $sucesso ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm">
    <div class="card-body p-4">
      <form method="POST" id="formAgendar">

        <!-- Fisioterapeuta -->
        <div class="mb-4">
          <label class="form-label fw-semibold">Fisioterapeuta <span class="text-danger">*</span></label>
          <select name="fisioterapeuta_id" id="fisio" class="form-select" required onchange="carregarHorarios()">
            <option value="">— Selecione —</option>
            <?php while ($f = $fisios->fetch_assoc()): ?>
              <option value="<?= $f['id'] ?>" data-esp="<?= htmlspecialchars($f['especialidade']) ?>"
                <?= (($_POST['fisioterapeuta_id'] ?? '') == $f['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($f['nome']) ?> — <?= htmlspecialchars($f['especialidade']) ?>
              </option>
            <?php endwhile; ?>
          </select>
          <div id="badge-esp" class="mt-2"></div>
        </div>

        <!-- Data -->
        <div class="mb-4">
          <label class="form-label fw-semibold">Data da Consulta <span class="text-danger">*</span></label>
          <input type="date" name="data_consulta" id="data" class="form-control"
                 min="<?= date('Y-m-d') ?>"
                 value="<?= htmlspecialchars($_POST['data_consulta'] ?? '') ?>"
                 required onchange="carregarHorarios()">
        </div>

        <!-- Horários disponíveis -->
        <div class="mb-4">
          <label class="form-label fw-semibold">Horário <span class="text-danger">*</span></label>
          <div id="horarios-container">
            <p class="text-muted small">Selecione um fisioterapeuta e uma data para ver os horários.</p>
          </div>
          <input type="hidden" name="hora_consulta" id="hora_consulta" required>
        </div>

        <!-- Observações -->
        <div class="mb-4">
          <label class="form-label fw-semibold">Observações <small class="text-muted">(opcional)</small></label>
          <textarea name="observacoes" class="form-control" rows="3" placeholder="Descreva brevemente o motivo da consulta..."><?= htmlspecialchars($_POST['observacoes'] ?? '') ?></textarea>
        </div>

        <!-- Resumo -->
        <div id="resumo" class="alert alert-info d-none mb-4">
          <strong><i class="bi bi-info-circle me-2"></i>Resumo do Agendamento</strong>
          <div id="resumo-texto" class="mt-2"></div>
        </div>

        <button type="submit" class="btn btn-primary-custom w-100 py-2 fw-semibold" id="btnAgendar" disabled>
          <i class="bi bi-calendar-check me-2"></i>Confirmar Agendamento
        </button>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
async function carregarHorarios() {
  const fisioId  = document.getElementById('fisio').value;
  const data     = document.getElementById('data').value;
  const container = document.getElementById('horarios-container');
  document.getElementById('hora_consulta').value = '';
  document.getElementById('btnAgendar').disabled = true;
  document.getElementById('resumo').classList.add('d-none');

  if (!fisioId || !data) {
    container.innerHTML = '<p class="text-muted small">Selecione um fisioterapeuta e uma data para ver os horários.</p>';
    return;
  }

  container.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary-custom"></div> Carregando horários...</div>';

  const res  = await fetch(`js/horarios.php?fisio_id=${fisioId}&data=${data}`);
  const lista = await res.json();

  if (!lista.length) {
    container.innerHTML = '<div class="alert alert-warning mb-0"><i class="bi bi-calendar-x me-2"></i>Nenhum horário disponível para esta data.</div>';
    return;
  }

  let html = '<div class="d-flex flex-wrap gap-2">';
  lista.forEach(h => {
    html += `<button type="button" class="btn btn-outline-success btn-sm px-3 py-2 horario-btn" data-hora="${h}"
                onclick="selecionarHorario('${h}')">${h}</button>`;
  });
  html += '</div>';
  container.innerHTML = html;
}

function selecionarHorario(hora) {
  document.querySelectorAll('.horario-btn').forEach(b => b.classList.remove('active', 'btn-success'));
  const btn = document.querySelector(`.horario-btn[data-hora="${hora}"]`);
  btn.classList.add('active', 'btn-success');
  btn.classList.remove('btn-outline-success');
  document.getElementById('hora_consulta').value = hora;
  document.getElementById('btnAgendar').disabled = false;

  // Resumo
  const fisioText = document.getElementById('fisio').selectedOptions[0].text;
  const data      = document.getElementById('data').value;
  const dataFmt   = new Date(data + 'T00:00:00').toLocaleDateString('pt-BR');
  document.getElementById('resumo-texto').innerHTML =
    `<b>Fisioterapeuta:</b> ${fisioText}<br><b>Data:</b> ${dataFmt}<br><b>Horário:</b> ${hora}`;
  document.getElementById('resumo').classList.remove('d-none');
}
</script>
</body>
</html>
