<?php
// =============================================
// includes/notificacoes.php — PHPMailer + Telegram
// =============================================
require_once __DIR__ . '/config.php';
// Coloque o PHPMailer em /vendor/phpmailer/ ou instale via Composer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Autoload simples (sem Composer)
$phpmailerBase = __DIR__ . '/../PHPMailer-master/';
if (file_exists($phpmailerBase . 'src/PHPMailer.php')) {
    require_once $phpmailerBase . 'src/Exception.php';
    require_once $phpmailerBase . 'src/PHPMailer.php';
    require_once $phpmailerBase . 'src/SMTP.php';
}

/**
 * Envia e-mail de confirmação de agendamento via PHPMailer + Gmail SMTP.
 */
function enviarEmailConfirmacao(array $dados): bool {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) return false;

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($dados['email'], $dados['nome']);

        $mail->isHTML(true);
        $mail->Subject = '✅ Agendamento Confirmado — ' . SITE_NAME;
        $mail->Body    = gerarHtmlEmail($dados);
        $mail->AltBody = gerarTextoEmail($dados);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Erro PHPMailer: ' . $mail->ErrorInfo);
        return false;
    }
}

function gerarHtmlEmail(array $d): string {
    return "
    <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto'>
      <div style='background:#1a7f5a;padding:30px;text-align:center'>
        <h1 style='color:#fff;margin:0'>🫀 " . SITE_NAME . "</h1>
      </div>
      <div style='padding:30px;background:#f9f9f9'>
        <h2 style='color:#1a7f5a'>Agendamento Confirmado!</h2>
        <p>Olá, <strong>{$d['nome']}</strong>!</p>
        <p>Seu agendamento foi realizado com sucesso. Confira os detalhes:</p>
        <table style='width:100%;border-collapse:collapse'>
          <tr><td style='padding:8px;border-bottom:1px solid #ddd'><strong>Fisioterapeuta:</strong></td><td style='padding:8px;border-bottom:1px solid #ddd'>{$d['fisio']}</td></tr>
          <tr><td style='padding:8px;border-bottom:1px solid #ddd'><strong>Data:</strong></td><td style='padding:8px;border-bottom:1px solid #ddd'>{$d['data']}</td></tr>
          <tr><td style='padding:8px;border-bottom:1px solid #ddd'><strong>Horário:</strong></td><td style='padding:8px;border-bottom:1px solid #ddd'>{$d['hora']}</td></tr>
          <tr><td style='padding:8px'><strong>Especialidade:</strong></td><td style='padding:8px'>{$d['especialidade']}</td></tr>
        </table>
        <p style='margin-top:20px;color:#666;font-size:13px'>Caso precise cancelar, acesse sua área do paciente em nosso site.</p>
      </div>
      <div style='background:#1a7f5a;padding:15px;text-align:center'>
        <p style='color:#fff;margin:0;font-size:12px'>© " . date('Y') . " " . SITE_NAME . "</p>
      </div>
    </div>";
}

function gerarTextoEmail(array $d): string {
    return "Agendamento Confirmado!\nOlá {$d['nome']},\n\nFisioterapeuta: {$d['fisio']}\nData: {$d['data']}\nHorário: {$d['hora']}\nEspecialidade: {$d['especialidade']}\n\n" . SITE_NAME;
}

/**
 * Envia notificação para o Telegram da clínica.
 */
function enviarTelegram(string $mensagem): bool {
    if (TELEGRAM_TOKEN === 'SEU_BOT_TOKEN') return false;

    $url  = "https://api.telegram.org/bot" . TELEGRAM_TOKEN . "/sendMessage";
    $data = ['chat_id' => TELEGRAM_CHAT_ID, 'text' => $mensagem, 'parse_mode' => 'HTML'];

    $opts = ['http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($data),
        'timeout' => 5,
    ]];
    $ctx = stream_context_create($opts);
    $r   = @file_get_contents($url, false, $ctx);
    return $r !== false;
}

function notificarNovoAgendamento(array $dados): void {
    $msg = "🗓 <b>Novo Agendamento</b>\n\n"
         . "👤 Paciente: {$dados['nome']}\n"
         . "🩺 Fisio: {$dados['fisio']}\n"
         . "📅 Data: {$dados['data']} às {$dados['hora']}\n"
         . "📋 Especialidade: {$dados['especialidade']}";
    enviarTelegram($msg);
}
?>
