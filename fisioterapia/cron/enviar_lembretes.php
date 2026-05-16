<?php
// =============================================
// cron/enviar_lembretes.php
// Sistema de Notificações Automáticas — Fisioterap
// =============================================
// Este script deve ser executado a cada 30 minutos via agendador de tarefas.
//
// WINDOWS (XAMPP) — Agendador de Tarefas:
//   Programa:  C:\xampp\php\php.exe
//   Argumentos: C:\xampp\htdocs\fisioterapia\cron\enviar_lembretes.php
//   Repetir:   a cada 30 minutos
//
// LINUX (crontab -e):
//   */30 * * * * php /var/www/html/fisioterapia/cron/enviar_lembretes.php >> /var/log/fisioterap_lembretes.log 2>&1
// =============================================

use PHPMailer\PHPMailer\PHPMailer;

// Evitar execução via HTTP acidental
if (PHP_SAPI !== 'cli' && ($_SERVER['REMOTE_ADDR'] ?? '') !== '127.0.0.1') {
    http_response_code(403);
    die('Acesso negado. Execute via CLI ou localhost.');
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notificacoes.php';

$db  = getDB();
$log = [];

function logMsg(string $msg): void {
    global $log;
    $linha = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
    $log[] = $linha;
    echo $linha . PHP_EOL;
}

logMsg('=== Iniciando envio de lembretes ===');

// ── 1. Lembretes de 24 horas antes ──────────────────────────
$amanha = date('Y-m-d H:i:s', strtotime('+24 hours'));
$agora  = date('Y-m-d H:i:s');
$janela = date('Y-m-d H:i:s', strtotime('+24 hours +30 minutes'));

$stmt = $db->prepare("
    SELECT l.id AS lembrete_id, l.agendamento_id,
           a.data_consulta, a.hora_consulta,
           u.nome AS paciente_nome, u.email AS paciente_email, u.telefone,
           f.nome AS fisio_nome, f.especialidade
    FROM lembretes l
    JOIN agendamentos a ON a.id = l.agendamento_id
    JOIN usuarios u ON u.id = a.usuario_id
    JOIN fisioterapeutas f ON f.id = a.fisioterapeuta_id
    WHERE l.status = 'pendente'
      AND l.tentativas < 3
      AND a.status = 'confirmado'
      AND CONCAT(a.data_consulta, ' ', a.hora_consulta) BETWEEN ? AND ?
    ORDER BY a.data_consulta ASC, a.hora_consulta ASC
");
$stmt->bind_param('ss', $amanha, $janela);
$stmt->execute();
$pendentes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

logMsg('Lembretes pendentes (24h): ' . count($pendentes));

foreach ($pendentes as $r) {
    $dados = [
        'nome'         => $r['paciente_nome'],
        'email'        => $r['paciente_email'],
        'fisio'        => $r['fisio_nome'],
        'especialidade'=> $r['especialidade'],
        'data'         => date('d/m/Y', strtotime($r['data_consulta'])),
        'hora'         => substr($r['hora_consulta'], 0, 5),
    ];

    $emailOk    = enviarEmailLembrete($dados);
    $telegramOk = enviarTelegramLembrete($dados);
    $sucesso    = $emailOk || $telegramOk;

    $novoStatus    = $sucesso ? 'enviado' : 'falhou';
    $tentativas    = $r['lembrete_id'];
    $stmtUpd = $db->prepare(
        "UPDATE lembretes SET status=?, enviado_em=NOW(), tentativas=tentativas+1 WHERE id=?"
    );
    $stmtUpd->bind_param('si', $novoStatus, $r['lembrete_id']);
    $stmtUpd->execute();

    logMsg(sprintf(
        '[%s] Agendamento #%d — Email: %s | Telegram: %s',
        $novoStatus,
        $r['agendamento_id'],
        $emailOk ? 'OK' : 'FALHOU',
        $telegramOk ? 'OK' : 'FALHOU'
    ));
}

// ── 2. Retentativa de falhos (até 3 tentativas) ──────────────
$stmtFalhos = $db->prepare("
    SELECT l.id AS lembrete_id, l.agendamento_id, l.tentativas,
           a.data_consulta, a.hora_consulta,
           u.nome AS paciente_nome, u.email AS paciente_email,
           f.nome AS fisio_nome, f.especialidade
    FROM lembretes l
    JOIN agendamentos a ON a.id = l.agendamento_id
    JOIN usuarios u ON u.id = a.usuario_id
    JOIN fisioterapeutas f ON f.id = a.fisioterapeuta_id
    WHERE l.status = 'falhou'
      AND l.tentativas < 3
      AND a.status = 'confirmado'
      AND CONCAT(a.data_consulta, ' ', a.hora_consulta) > NOW()
");
$stmtFalhos->execute();
$falhos = $stmtFalhos->get_result()->fetch_all(MYSQLI_ASSOC);

logMsg('Retentativas de falhos: ' . count($falhos));

foreach ($falhos as $r) {
    $dados = [
        'nome'         => $r['paciente_nome'],
        'email'        => $r['paciente_email'],
        'fisio'        => $r['fisio_nome'],
        'especialidade'=> $r['especialidade'],
        'data'         => date('d/m/Y', strtotime($r['data_consulta'])),
        'hora'         => substr($r['hora_consulta'], 0, 5),
    ];

    $sucesso    = enviarEmailLembrete($dados) || enviarTelegramLembrete($dados);
    $novoStatus = $sucesso ? 'enviado' : 'falhou';

    $stmtUpd = $db->prepare("UPDATE lembretes SET status=?, tentativas=tentativas+1 WHERE id=?");
    $stmtUpd->bind_param('si', $novoStatus, $r['lembrete_id']);
    $stmtUpd->execute();

    logMsg(sprintf('[RETRY %d] Agendamento #%d → %s', $r['tentativas'] + 1, $r['agendamento_id'], $novoStatus));
}

logMsg('=== Concluído. Total processados: ' . (count($pendentes) + count($falhos)) . ' ===');

// Salvar log em arquivo
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) mkdir($logDir, 0755, true);
file_put_contents($logDir . '/lembretes_' . date('Y-m-d') . '.log', implode(PHP_EOL, $log) . PHP_EOL, FILE_APPEND);


// ══════════════════════════════════════════════════════════════
// Funções de envio de lembrete
// ══════════════════════════════════════════════════════════════

function enviarEmailLembrete(array $dados): bool {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $base = __DIR__ . '/../vendor/phpmailer/src/';
        if (!file_exists($base . 'PHPMailer.php')) return false;
        require_once $base . 'Exception.php';
        require_once $base . 'PHPMailer.php';
        require_once $base . 'SMTP.php';
    }

    
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($dados['email'], $dados['nome']);
        $mail->isHTML(true);
        $mail->Subject = '⏰ Lembrete: sua consulta é amanhã — ' . SITE_NAME;
        $mail->Body    = gerarHtmlLembrete($dados);
        $mail->AltBody = "Lembrete: sua consulta com {$dados['fisio']} é amanhã, {$dados['data']} às {$dados['hora']}.";
        $mail->send();
        return true;
    } catch (\Exception $e) {
        error_log('Lembrete email falhou: ' . $e->getMessage());
        return false;
    }
}

function gerarHtmlLembrete(array $d): string {
    return "
    <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto'>
      <div style='background:#1a7f5a;padding:30px;text-align:center'>
        <h1 style='color:#fff;margin:0'>⏰ Lembrete de Consulta</h1>
        <p style='color:rgba(255,255,255,.85);margin:8px 0 0'>".SITE_NAME."</p>
      </div>
      <div style='padding:30px;background:#f9f9f9'>
        <p>Olá, <strong>{$d['nome']}</strong>!</p>
        <p>Este é um lembrete automático: <strong>sua consulta é amanhã!</strong></p>
        <table style='width:100%;border-collapse:collapse;margin:20px 0'>
          <tr style='background:#e6f5f0'>
            <td style='padding:10px;border-bottom:1px solid #ddd'><strong>Fisioterapeuta</strong></td>
            <td style='padding:10px;border-bottom:1px solid #ddd'>{$d['fisio']}</td>
          </tr>
          <tr>
            <td style='padding:10px;border-bottom:1px solid #ddd'><strong>Especialidade</strong></td>
            <td style='padding:10px;border-bottom:1px solid #ddd'>{$d['especialidade']}</td>
          </tr>
          <tr style='background:#e6f5f0'>
            <td style='padding:10px;border-bottom:1px solid #ddd'><strong>Data</strong></td>
            <td style='padding:10px;border-bottom:1px solid #ddd'>{$d['data']}</td>
          </tr>
          <tr>
            <td style='padding:10px'><strong>Horário</strong></td>
            <td style='padding:10px'>{$d['hora']}</td>
          </tr>
        </table>
        <p style='color:#666;font-size:13px'>Caso precise cancelar, acesse sua área do paciente.</p>
      </div>
      <div style='background:#1a7f5a;padding:15px;text-align:center'>
        <p style='color:#fff;margin:0;font-size:12px'>© ".date('Y')." ".SITE_NAME." — Notificação automática</p>
      </div>
    </div>";
}

function enviarTelegramLembrete(array $dados): bool {
    if (TELEGRAM_TOKEN === 'SEU_BOT_TOKEN') return false;

    $msg  = "⏰ <b>Lembrete de Consulta</b>\n\n"
          . "👤 Paciente: {$dados['nome']}\n"
          . "🩺 Fisio: {$dados['fisio']}\n"
          . "📋 Especialidade: {$dados['especialidade']}\n"
          . "📅 Data: {$dados['data']} às {$dados['hora']}\n\n"
          . "📌 <i>Notificação automática — " . SITE_NAME . "</i>";

    $url  = "https://api.telegram.org/bot" . TELEGRAM_TOKEN . "/sendMessage";
    $data = ['chat_id' => TELEGRAM_CHAT_ID, 'text' => $msg, 'parse_mode' => 'HTML'];
    $opts = ['http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($data),
        'timeout' => 5,
    ]];
    $r = @file_get_contents($url, false, stream_context_create($opts));
    return $r !== false;
}
