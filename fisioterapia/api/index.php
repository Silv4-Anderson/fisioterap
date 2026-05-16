<?php
// =============================================
// api/index.php — Roteador principal da API REST
// Fisioterap — TCC FATEC Osasco
// =============================================
// Exemplos de uso:
//   GET    /api/index.php/agendamentos
//   GET    /api/index.php/agendamentos/5
//   POST   /api/index.php/agendamentos
//   PUT    /api/index.php/agendamentos/5
//   DELETE /api/index.php/agendamentos/5
//   GET    /api/index.php/pacientes
//   PUT    /api/index.php/pacientes/3
//   GET    /api/index.php/fisioterapeutas
//   GET    /api/index.php/horarios?fisio_id=1&data=2025-04-10
// =============================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/auth_api.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── Roteamento ─────────────────────────────────────────────────
$path   = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$parts  = explode('/', $path);
// Remove prefixo caso a URL contenha "api/index.php"
$apiIdx = array_search('index.php', $parts);
$parts  = $apiIdx !== false ? array_slice($parts, $apiIdx + 1) : $parts;

$resource = $parts[0] ?? '';
$id       = isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : null;
$method   = $_SERVER['REQUEST_METHOD'];
$body     = json_decode(file_get_contents('php://input'), true) ?? [];

// ── Despacho de recursos ───────────────────────────────────────
try {
    match ($resource) {
        'agendamentos'    => (new AgendamentosController($method, $id, $body))->handle(),
        'pacientes'       => (new PacientesController($method, $id, $body))->handle(),
        'fisioterapeutas' => (new FisioterapeutasController($method, $id, $body))->handle(),
        'horarios'        => (new HorariosController($method, $id, $body))->handle(),
        default           => resposta(404, ['erro' => "Recurso '$resource' não encontrado."]),
    };
} catch (Throwable $e) {
    resposta(500, ['erro' => 'Erro interno: ' . $e->getMessage()]);
}

// ── Helpers ────────────────────────────────────────────────────
function resposta(int $code, mixed $data): never {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function paginar(array $rows, int $total, int $pagina, int $porPagina): array {
    return [
        'dados'       => $rows,
        'total'       => $total,
        'pagina'      => $pagina,
        'por_pagina'  => $porPagina,
        'total_paginas' => (int)ceil($total / $porPagina),
    ];
}

// ══════════════════════════════════════════════════════════════
// CONTROLLER: Agendamentos
// ══════════════════════════════════════════════════════════════
class AgendamentosController {
    public function __construct(
        private string $method,
        private ?int   $id,
        private array  $body
    ) {}

    public function handle(): void {
        autenticar(); // JWT obrigatório
        match ($this->method) {
            'GET'    => $this->id ? $this->show()   : $this->index(),
            'POST'   => $this->store(),
            'PUT'    => $this->id ? $this->update()  : resposta(400, ['erro' => 'ID obrigatório para PUT.']),
            'DELETE' => $this->id ? $this->destroy() : resposta(400, ['erro' => 'ID obrigatório para DELETE.']),
            default  => resposta(405, ['erro' => 'Método não permitido.']),
        };
    }

    // GET /agendamentos?pagina=1&status=confirmado&fisio_id=2
    private function index(): void {
        $db       = getDB();
        $pagina   = max(1, (int)($_GET['pagina']   ?? 1));
        $limite   = min(100, max(1, (int)($_GET['limite'] ?? 10)));
        $offset   = ($pagina - 1) * $limite;
        $where    = ['1=1'];
        $params   = [];
        $types    = '';

        if (!empty($_GET['status'])) {
            $where[]  = 'a.status = ?';
            $params[] = $_GET['status'];
            $types   .= 's';
        }
        if (!empty($_GET['fisio_id'])) {
            $where[]  = 'a.fisioterapeuta_id = ?';
            $params[] = (int)$_GET['fisio_id'];
            $types   .= 'i';
        }
        if (!empty($_GET['data'])) {
            $where[]  = 'a.data_consulta = ?';
            $params[] = $_GET['data'];
            $types   .= 's';
        }

        $cond = implode(' AND ', $where);

        // Total
        $stmtC = $db->prepare("SELECT COUNT(*) FROM agendamentos a WHERE $cond");
        if ($types) $stmtC->bind_param($types, ...$params);
        $stmtC->execute();
        $total = (int)$stmtC->get_result()->fetch_row()[0];

        // Dados
        $sql = "SELECT a.id, a.data_consulta, a.hora_consulta, a.status, a.observacoes,
                       a.criado_em,
                       u.id AS paciente_id, u.nome AS paciente_nome, u.email AS paciente_email,
                       f.id AS fisio_id, f.nome AS fisio_nome, f.especialidade
                FROM agendamentos a
                JOIN usuarios u ON u.id = a.usuario_id
                JOIN fisioterapeutas f ON f.id = a.fisioterapeuta_id
                WHERE $cond
                ORDER BY a.data_consulta ASC, a.hora_consulta ASC
                LIMIT ? OFFSET ?";

        $stmt = $db->prepare($sql);
        $params[] = $limite; $params[] = $offset;
        $types   .= 'ii';
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        resposta(200, paginar($rows, $total, $pagina, $limite));
    }

    // GET /agendamentos/{id}
    private function show(): void {
        $db   = getDB();
        $stmt = $db->prepare(
            "SELECT a.id, a.data_consulta, a.hora_consulta, a.status, a.observacoes, a.criado_em,
                    u.id AS paciente_id, u.nome AS paciente_nome, u.email AS paciente_email, u.telefone AS paciente_telefone,
                    f.id AS fisio_id, f.nome AS fisio_nome, f.especialidade, f.crefito
             FROM agendamentos a
             JOIN usuarios u ON u.id = a.usuario_id
             JOIN fisioterapeutas f ON f.id = a.fisioterapeuta_id
             WHERE a.id = ?"
        );
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $row ? resposta(200, $row) : resposta(404, ['erro' => 'Agendamento não encontrado.']);
    }

    // POST /agendamentos
    private function store(): void {
        $db = getDB();
        $campos = ['usuario_id','fisioterapeuta_id','data_consulta','hora_consulta'];
        foreach ($campos as $c) {
            if (empty($this->body[$c])) resposta(422, ['erro' => "Campo '$c' é obrigatório."]);
        }

        ['usuario_id' => $uid, 'fisioterapeuta_id' => $fid,
         'data_consulta' => $data, 'hora_consulta' => $hora] = $this->body;
        $obs = $this->body['observacoes'] ?? '';

        // Verificar conflito
        $chk = $db->prepare(
            "SELECT id FROM agendamentos WHERE fisioterapeuta_id=? AND data_consulta=? AND hora_consulta=? AND status NOT IN ('cancelado')"
        );
        $chk->bind_param('iss', $fid, $data, $hora);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            resposta(409, ['erro' => 'Horário já ocupado para este fisioterapeuta.']);
        }

        $ins = $db->prepare(
            "INSERT INTO agendamentos (usuario_id, fisioterapeuta_id, data_consulta, hora_consulta, observacoes, status)
             VALUES (?,?,?,?,?,'confirmado')"
        );
        $ins->bind_param('iisss', $uid, $fid, $data, $hora, $obs);
        $ins->execute();
        $novoId = $db->insert_id;

        // Disparar lembrete agendado
        $this->agendarLembrete($novoId, $data, $hora);

        resposta(201, ['mensagem' => 'Agendamento criado com sucesso.', 'id' => $novoId]);
    }

    // PUT /agendamentos/{id}
    private function update(): void {
        $db      = getDB();
        $campos  = array_intersect_key($this->body, array_flip(['data_consulta','hora_consulta','status','observacoes']));
        if (empty($campos)) resposta(422, ['erro' => 'Nenhum campo válido para atualizar.']);

        $sets   = implode(', ', array_map(fn($k) => "$k = ?", array_keys($campos)));
        $values = array_values($campos);
        $types  = str_repeat('s', count($values)) . 'i';
        $values[] = $this->id;

        $stmt = $db->prepare("UPDATE agendamentos SET $sets WHERE id = ?");
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $stmt->affected_rows > 0
            ? resposta(200, ['mensagem' => 'Agendamento atualizado.'])
            : resposta(404, ['erro'     => 'Agendamento não encontrado.']);
    }

    // DELETE /agendamentos/{id}
    private function destroy(): void {
        $db   = getDB();
        $stmt = $db->prepare("UPDATE agendamentos SET status='cancelado' WHERE id=?");
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        $stmt->affected_rows > 0
            ? resposta(200, ['mensagem' => 'Agendamento cancelado.'])
            : resposta(404, ['erro'     => 'Agendamento não encontrado.']);
    }

    private function agendarLembrete(int $agId, string $data, string $hora): void {
        $db = getDB();
        $dataHora = $data . ' ' . $hora;
        $stmt = $db->prepare(
            "INSERT INTO lembretes (agendamento_id, data_hora_consulta, status) VALUES (?, ?, 'pendente')
             ON DUPLICATE KEY UPDATE status='pendente'"
        );
        $stmt->bind_param('is', $agId, $dataHora);
        $stmt->execute();
    }
}

// ══════════════════════════════════════════════════════════════
// CONTROLLER: Pacientes
// ══════════════════════════════════════════════════════════════
class PacientesController {
    public function __construct(
        private string $method,
        private ?int   $id,
        private array  $body
    ) {}

    public function handle(): void {
        autenticar();
        match ($this->method) {
            'GET' => $this->id ? $this->show() : $this->index(),
            'PUT' => $this->id ? $this->update() : resposta(400, ['erro' => 'ID obrigatório.']),
            default => resposta(405, ['erro' => 'Método não permitido.']),
        };
    }

    private function index(): void {
        $db    = getDB();
        $busca = $_GET['busca'] ?? '';
        if ($busca) {
            $like = "%$busca%";
            $stmt = $db->prepare("SELECT id, nome, email, telefone, data_nascimento, cpf, criado_em FROM usuarios WHERE nome LIKE ? OR email LIKE ? ORDER BY nome LIMIT 50");
            $stmt->bind_param('ss', $like, $like);
        } else {
            $stmt = $db->prepare("SELECT id, nome, email, telefone, data_nascimento, cpf, criado_em FROM usuarios ORDER BY nome LIMIT 50");
        }
        $stmt->execute();
        resposta(200, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    private function show(): void {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id, nome, email, telefone, data_nascimento, cpf, criado_em FROM usuarios WHERE id=?");
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $row ? resposta(200, $row) : resposta(404, ['erro' => 'Paciente não encontrado.']);
    }

    private function update(): void {
        $db     = getDB();
        $campos = array_intersect_key($this->body, array_flip(['nome','email','telefone','data_nascimento']));
        if (empty($campos)) resposta(422, ['erro' => 'Nenhum campo válido.']);

        $sets   = implode(', ', array_map(fn($k) => "$k = ?", array_keys($campos)));
        $values = array_values($campos);
        $types  = str_repeat('s', count($values)) . 'i';
        $values[] = $this->id;

        $stmt = $db->prepare("UPDATE usuarios SET $sets WHERE id=?");
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $stmt->affected_rows > 0
            ? resposta(200, ['mensagem' => 'Paciente atualizado.'])
            : resposta(404, ['erro'     => 'Paciente não encontrado.']);
    }
}

// ══════════════════════════════════════════════════════════════
// CONTROLLER: Fisioterapeutas
// ══════════════════════════════════════════════════════════════
class FisioterapeutasController {
    public function __construct(
        private string $method,
        private ?int   $id,
        private array  $body
    ) {}

    public function handle(): void {
        autenticar();
        match ($this->method) {
            'GET' => $this->id ? $this->show() : $this->index(),
            default => resposta(405, ['erro' => 'Método não permitido.']),
        };
    }

    private function index(): void {
        $rows = getDB()->query("SELECT * FROM fisioterapeutas ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
        resposta(200, $rows);
    }

    private function show(): void {
        $stmt = getDB()->prepare("SELECT * FROM fisioterapeutas WHERE id=?");
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $row ? resposta(200, $row) : resposta(404, ['erro' => 'Fisioterapeuta não encontrado.']);
    }
}

// ══════════════════════════════════════════════════════════════
// CONTROLLER: Horários disponíveis
// ══════════════════════════════════════════════════════════════
class HorariosController {
    public function __construct(
        private string $method,
        private ?int   $id,
        private array  $body
    ) {}

    public function handle(): void {
        if ($this->method !== 'GET') resposta(405, ['erro' => 'Apenas GET permitido.']);

        $fisioId = (int)($_GET['fisio_id'] ?? 0);
        $data    = $_GET['data'] ?? '';

        if (!$fisioId || !$data) resposta(422, ['erro' => 'Parâmetros fisio_id e data são obrigatórios.']);

        $db        = getDB();
        $diaSemana = (int)date('w', strtotime($data));

        $stmt = $db->prepare(
            "SELECT hora_inicio FROM horarios WHERE fisioterapeuta_id=? AND dia_semana=? AND disponivel=1 ORDER BY hora_inicio"
        );
        $stmt->bind_param('ii', $fisioId, $diaSemana);
        $stmt->execute();
        $todos = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'hora_inicio');

        $stmt2 = $db->prepare(
            "SELECT hora_consulta FROM agendamentos WHERE fisioterapeuta_id=? AND data_consulta=? AND status NOT IN ('cancelado')"
        );
        $stmt2->bind_param('is', $fisioId, $data);
        $stmt2->execute();
        $ocupados = array_column($stmt2->get_result()->fetch_all(MYSQLI_ASSOC), 'hora_consulta');

        $disponíveis = array_values(array_filter(
            array_map(fn($h) => substr($h, 0, 5), $todos),
            fn($h) => !in_array($h, array_map(fn($o) => substr($o, 0, 5), $ocupados))
        ));

        resposta(200, ['data' => $data, 'fisio_id' => $fisioId, 'horarios' => $disponíveis]);
    }
}
