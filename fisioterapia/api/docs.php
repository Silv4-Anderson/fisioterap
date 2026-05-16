<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Documentação da API REST — Fisioterap</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .endpoint-card { border-left: 4px solid #ccc; }
    .endpoint-card.get    { border-color: #0d6efd; }
    .endpoint-card.post   { border-color: #198754; }
    .endpoint-card.put    { border-color: #fd7e14; }
    .endpoint-card.delete { border-color: #dc3545; }
    .method-badge { font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 4px; letter-spacing: .5px; }
    .badge-get    { background:#e7f1ff; color:#0d6efd; }
    .badge-post   { background:#d1e7dd; color:#198754; }
    .badge-put    { background:#fff3cd; color:#664d03; }
    .badge-delete { background:#f8d7da; color:#842029; }
    pre { background: #1e1e1e; color: #d4d4d4; border-radius: 8px; padding: 14px; font-size: 12px; overflow-x: auto; }
    .path { font-family: monospace; font-size: 13px; color: #495057; }
  </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary-custom shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="../index.php">
      <i class="bi bi-heart-pulse-fill me-2"></i>Fisioterap
    </a>
    <span class="text-white opacity-75 small">Documentação da API REST</span>
  </div>
</nav>

<div class="container py-5">

  <div class="row mb-5">
    <div class="col-lg-8">
      <h1 class="fw-bold text-primary-custom"><i class="bi bi-braces me-2"></i>API REST — Fisioterap</h1>
      <p class="lead text-muted">Interface de programação que permite a comunicação padronizada entre sistemas via HTTP, com troca de dados no formato JSON.</p>
    </div>
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm p-3 bg-white">
        <div class="small text-muted mb-1">URL Base</div>
        <code>http://localhost/fisioterapia/api/</code>
        <div class="small text-muted mt-2 mb-1">Autenticação</div>
        <code>Authorization: Bearer &lt;token&gt;</code>
      </div>
    </div>
  </div>

  <!-- AUTENTICAÇÃO -->
  <h4 class="fw-bold text-primary-custom mb-3"><i class="bi bi-shield-lock me-2"></i>Autenticação</h4>
  <div class="card border-0 shadow-sm mb-4 endpoint-card post">
    <div class="card-body p-4">
      <div class="d-flex align-items-center gap-3 mb-2">
        <span class="method-badge badge-post">POST</span>
        <span class="path">/api/login.php</span>
        <small class="text-muted">Obter token JWT</small>
      </div>
      <div class="row g-3 mt-1">
        <div class="col-md-6">
          <div class="small fw-semibold mb-1">Requisição (JSON)</div>
          <pre>{ "email": "joao@email.com", "senha": "123456" }</pre>
        </div>
        <div class="col-md-6">
          <div class="small fw-semibold mb-1">Resposta 200</div>
          <pre>{
  "token": "eyJhbGciOiJIUzI1NiJ9...",
  "expira_em": 3600,
  "usuario": { "id": 1, "nome": "João", "email": "joao@email.com" }
}</pre>
        </div>
      </div>
    </div>
  </div>

  <!-- AGENDAMENTOS -->
  <h4 class="fw-bold text-primary-custom mb-3 mt-5"><i class="bi bi-calendar-check me-2"></i>Agendamentos</h4>

  <div class="card border-0 shadow-sm mb-3 endpoint-card get">
    <div class="card-body p-4">
      <div class="d-flex align-items-center gap-3 mb-2">
        <span class="method-badge badge-get">GET</span>
        <span class="path">/api/index.php/agendamentos</span>
        <small class="text-muted">Listar agendamentos (paginado)</small>
      </div>
      <p class="small text-muted mb-2">Parâmetros opcionais: <code>?pagina=1&limite=10&status=confirmado&fisio_id=2&data=2025-04-10</code></p>
      <pre>{
  "dados": [
    { "id": 1, "data_consulta": "2025-04-10", "hora_consulta": "09:00:00",
      "status": "confirmado", "paciente_nome": "João Silva",
      "fisio_nome": "Dra. Ana Paula", "especialidade": "Ortopedia" }
  ],
  "total": 1, "pagina": 1, "por_pagina": 10, "total_paginas": 1
}</pre>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3 endpoint-card get">
    <div class="card-body p-4">
      <div class="d-flex align-items-center gap-3 mb-2">
        <span class="method-badge badge-get">GET</span>
        <span class="path">/api/index.php/agendamentos/{id}</span>
        <small class="text-muted">Buscar agendamento por ID</small>
      </div>
      <pre>{ "id": 1, "data_consulta": "2025-04-10", "hora_consulta": "09:00:00",
  "status": "confirmado", "observacoes": "",
  "paciente_nome": "João Silva", "paciente_email": "joao@email.com",
  "fisio_nome": "Dra. Ana Paula", "especialidade": "Ortopedia", "crefito": "CREFITO-3/123456-F" }</pre>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3 endpoint-card post">
    <div class="card-body p-4">
      <div class="d-flex align-items-center gap-3 mb-2">
        <span class="method-badge badge-post">POST</span>
        <span class="path">/api/index.php/agendamentos</span>
        <small class="text-muted">Criar novo agendamento</small>
      </div>
      <div class="row g-3 mt-1">
        <div class="col-md-6">
          <div class="small fw-semibold mb-1">Requisição</div>
          <pre>{
  "usuario_id": 3,
  "fisioterapeuta_id": 1,
  "data_consulta": "2025-04-15",
  "hora_consulta": "10:00",
  "observacoes": "Dor no joelho"
}</pre>
        </div>
        <div class="col-md-6">
          <div class="small fw-semibold mb-1">Resposta 201</div>
          <pre>{
  "mensagem": "Agendamento criado com sucesso.",
  "id": 12
}</pre>
          <div class="small fw-semibold mb-1 mt-2">Conflito 409</div>
          <pre>{ "erro": "Horário já ocupado para este fisioterapeuta." }</pre>
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3 endpoint-card put">
    <div class="card-body p-4">
      <div class="d-flex align-items-center gap-3 mb-2">
        <span class="method-badge badge-put">PUT</span>
        <span class="path">/api/index.php/agendamentos/{id}</span>
        <small class="text-muted">Atualizar status ou dados</small>
      </div>
      <pre>{ "status": "cancelado" }  <span style="color:#888">// ou "confirmado", "concluido"</span></pre>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-4 endpoint-card delete">
    <div class="card-body p-4">
      <div class="d-flex align-items-center gap-3 mb-2">
        <span class="method-badge badge-delete">DELETE</span>
        <span class="path">/api/index.php/agendamentos/{id}</span>
        <small class="text-muted">Cancelar agendamento (soft delete)</small>
      </div>
      <pre>{ "mensagem": "Agendamento cancelado." }</pre>
    </div>
  </div>

  <!-- PACIENTES -->
  <h4 class="fw-bold text-primary-custom mb-3 mt-5"><i class="bi bi-people me-2"></i>Pacientes</h4>
  <div class="card border-0 shadow-sm mb-3 endpoint-card get">
    <div class="card-body p-4">
      <div class="d-flex align-items-center gap-3 mb-2">
        <span class="method-badge badge-get">GET</span>
        <span class="path">/api/index.php/pacientes</span>
        <small class="text-muted">Listar pacientes — <code>?busca=João</code></small>
      </div>
    </div>
  </div>
  <div class="card border-0 shadow-sm mb-3 endpoint-card put">
    <div class="card-body p-4">
      <div class="d-flex align-items-center gap-3 mb-2">
        <span class="method-badge badge-put">PUT</span>
        <span class="path">/api/index.php/pacientes/{id}</span>
        <small class="text-muted">Atualizar dados do paciente</small>
      </div>
      <pre>{ "nome": "João Carlos", "telefone": "(11) 99999-1234" }</pre>
    </div>
  </div>

  <!-- HORÁRIOS -->
  <h4 class="fw-bold text-primary-custom mb-3 mt-5"><i class="bi bi-clock me-2"></i>Horários Disponíveis</h4>
  <div class="card border-0 shadow-sm mb-4 endpoint-card get">
    <div class="card-body p-4">
      <div class="d-flex align-items-center gap-3 mb-2">
        <span class="method-badge badge-get">GET</span>
        <span class="path">/api/index.php/horarios?fisio_id=1&data=2025-04-10</span>
      </div>
      <pre>{ "data": "2025-04-10", "fisio_id": 1, "horarios": ["08:00","09:00","10:00","14:00"] }</pre>
    </div>
  </div>

  <!-- CÓDIGOS HTTP -->
  <h4 class="fw-bold text-primary-custom mb-3 mt-5"><i class="bi bi-info-circle me-2"></i>Códigos de Resposta HTTP</h4>
  <div class="card border-0 shadow-sm mb-5">
    <div class="card-body p-0">
      <table class="table mb-0">
        <tbody>
          <tr><td><span class="badge bg-success">200</span></td><td>OK — Requisição bem-sucedida</td></tr>
          <tr><td><span class="badge bg-primary">201</span></td><td>Created — Recurso criado</td></tr>
          <tr><td><span class="badge bg-secondary">204</span></td><td>No Content — CORS preflight</td></tr>
          <tr><td><span class="badge bg-warning text-dark">401</span></td><td>Unauthorized — Token ausente, inválido ou expirado</td></tr>
          <tr><td><span class="badge bg-warning text-dark">404</span></td><td>Not Found — Recurso não encontrado</td></tr>
          <tr><td><span class="badge bg-warning text-dark">405</span></td><td>Method Not Allowed — Método HTTP não suportado</td></tr>
          <tr><td><span class="badge bg-danger">409</span></td><td>Conflict — Conflito de dados (ex: horário ocupado)</td></tr>
          <tr><td><span class="badge bg-danger">422</span></td><td>Unprocessable Entity — Campos obrigatórios ausentes</td></tr>
          <tr><td><span class="badge bg-danger">500</span></td><td>Internal Server Error — Erro inesperado no servidor</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div>

<footer class="bg-primary-custom text-white text-center py-3">
  <small>© <?= date('Y') ?> Fisioterap — TCC FATEC Osasco | API REST com PHP + MySQL + JWT</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
