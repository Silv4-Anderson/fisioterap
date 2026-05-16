<?php
// =============================================
// includes/config.php — Configurações do sistema
// =============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Altere para seu usuário MySQL
define('DB_PASS', '');           // Altere para sua senha MySQL
define('DB_NAME', 'fisioterapia_db');

// PHPMailer — Gmail SMTP
define('MAIL_HOST',     'smtp.gmail.com');
define('MAIL_PORT',     587);
define('MAIL_USER',     'Araujo.andersonti@gmail.com');   // Seu e-mail Gmail
define('MAIL_PASS',     'smgbrpifisadxgun');     // Senha de app do Gmail
define('MAIL_FROM',     'araujo.andersonti@gmail.com');
define('MAIL_FROM_NAME','Clínica Fisioterap');

// Telegram Bot
define('TELEGRAM_TOKEN',   '8675738496:AAEGVZvEIWG7JPGYhVz6S6hqlGqW1QxQPEo');     // Token do BotFather
define('TELEGRAM_CHAT_ID', '8581764770');       // Chat ID da clínica

// Site
define('SITE_NAME', 'Fisioterap');
define('SITE_URL',  'http://localhost/fisioterapia');
?>
