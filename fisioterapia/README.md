# 🦴 Fisioterap — Sistema de Agendamento de Fisioterapia
**TCC — FATEC Osasco | Stack: PHP + MySQL + Bootstrap + PHPMailer + Telegram API**

---

## 📁 Estrutura do Projeto
```
fisioterapia/
├── index.php                  # Página inicial
├── cadastro.php               # Cadastro de pacientes
├── login.php                  # Login
├── logout.php                 # Logout
├── agendar.php                # Agendamento de consultas ⭐
├── meus-agendamentos.php      # Histórico do paciente
├── banco.sql                  # Script do banco de dados
├── css/
│   └── style.css              # Estilos personalizados
├── js/
│   ├── mascaras.js            # Máscaras CPF/Telefone
│   └── horarios.php           # API AJAX de horários disponíveis
├── includes/
│   ├── config.php             # ⚙️  CONFIGURAÇÕES (editar aqui)
│   ├── db.php                 # Conexão MySQL
│   ├── auth.php               # Autenticação e sessão
│   └── notificacoes.php       # PHPMailer + Telegram
└── vendor/
    └── phpmailer/             # PHPMailer (baixar separado)
```

---

## 🚀 Instalação (XAMPP)

### 1. Copiar o projeto
```
Copie a pasta `fisioterapia/` para:
C:\xampp\htdocs\fisioterapia\
```

### 2. Criar o banco de dados
- Abra o XAMPP e inicie **Apache** e **MySQL**
- Acesse `http://localhost/phpmyadmin`
- Clique em **Importar** → selecione `banco.sql` → Executar

### 3. Instalar o PHPMailer
Baixe em: https://github.com/PHPMailer/PHPMailer/releases

Extraia e coloque em:
```
fisioterapia/vendor/phpmailer/
```
A pasta deve conter: `src/PHPMailer.php`, `src/SMTP.php`, `src/Exception.php`

### 4. Configurar (`includes/config.php`)
```php
// Banco de dados
define('DB_USER', 'root');
define('DB_PASS', '');         // senha do MySQL (padrão XAMPP = vazio)

// Gmail SMTP (PHPMailer)
define('MAIL_USER', 'seuemail@gmail.com');
define('MAIL_PASS', 'xxxx xxxx xxxx xxxx'); // Senha de app (não sua senha normal!)

// Telegram (opcional)
define('TELEGRAM_TOKEN',   'TOKEN_DO_BOT');
define('TELEGRAM_CHAT_ID', 'CHAT_ID');
```

### 5. Configurar Gmail para envio de e-mails
1. Acesse: https://myaccount.google.com/security
2. Ative a **Verificação em duas etapas**
3. Acesse **Senhas de app** → Gerar senha para "Aplicativo de e-mail"
4. Cole a senha gerada em `MAIL_PASS`

### 6. Configurar Telegram Bot (opcional)
1. Abra o Telegram e busque por **@BotFather**
2. Digite `/newbot` e siga as instruções
3. Copie o token gerado para `TELEGRAM_TOKEN`
4. Para obter o `CHAT_ID`: acesse `https://api.telegram.org/botSEU_TOKEN/getUpdates`
   após enviar uma mensagem para o bot

### 7. Acessar o sistema
```
http://localhost/fisioterapia/
```

---

## 🗄️ Modelo Entidade-Relacionamento

```
usuarios (id, nome, email, senha, telefone, data_nascimento, cpf)
    |
    | 1:N
    v
agendamentos (id, usuario_id, fisioterapeuta_id, data_consulta, hora_consulta, status, observacoes)
    |
    | N:1
    v
fisioterapeutas (id, nome, crefito, especialidade, email)
    |
    | 1:N
    v
horarios (id, fisioterapeuta_id, dia_semana, hora_inicio, hora_fim, disponivel)
```

---

## ✅ Funcionalidades Implementadas

| Funcionalidade             | Status |
|----------------------------|--------|
| Cadastro de pacientes      | ✅     |
| Login/Logout com sessão    | ✅     |
| Listagem de fisioterapeutas| ✅     |
| Agendamento com horários   | ✅     |
| Verificação de conflitos   | ✅     |
| Confirmação por e-mail     | ✅ PHPMailer + Gmail |
| Notificação Telegram       | ✅ Bot API |
| Histórico de agendamentos  | ✅     |
| Cancelamento de consulta   | ✅     |
| Interface responsiva       | ✅ Bootstrap 5 |
| Máscaras CPF/Telefone      | ✅     |

---

## 🛠️ Tecnologias Utilizadas
- **PHP 8+** — Backend e lógica do sistema
- **MySQL** — Banco de dados relacional
- **Bootstrap 5.3** — Interface responsiva
- **Bootstrap Icons** — Ícones
- **PHPMailer** — Envio de e-mails via Gmail SMTP
- **Telegram Bot API** — Notificações em tempo real
- **JavaScript (Vanilla)** — AJAX para carregar horários dinamicamente
- **XAMPP** — Servidor local (Apache + PHP + MySQL)

---

## 🔌 API REST (Melhoria implementada)

A API permite comunicação com outros sistemas via HTTP + JSON.

### Autenticação (JWT)
```bash
# 1. Obter token
curl -X POST http://localhost/fisioterapia/api/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"joao@email.com","senha":"123456"}'

# 2. Usar token nas requisições
curl http://localhost/fisioterapia/api/index.php/agendamentos \
  -H "Authorization: Bearer SEU_TOKEN"
```

### Endpoints disponíveis
| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | /api/login.php | Autenticação + token JWT |
| GET | /api/index.php/agendamentos | Listar agendamentos (paginado) |
| GET | /api/index.php/agendamentos/{id} | Buscar agendamento |
| POST | /api/index.php/agendamentos | Criar agendamento |
| PUT | /api/index.php/agendamentos/{id} | Atualizar agendamento |
| DELETE | /api/index.php/agendamentos/{id} | Cancelar agendamento |
| GET | /api/index.php/pacientes | Listar pacientes |
| PUT | /api/index.php/pacientes/{id} | Atualizar paciente |
| GET | /api/index.php/fisioterapeutas | Listar fisioterapeutas |
| GET | /api/index.php/horarios?fisio_id=1&data=... | Horários disponíveis |

Documentação completa: `http://localhost/fisioterapia/api/docs.php`

---

## 🔔 Notificações Automáticas (Melhoria implementada)

Lembretes enviados 24h antes da consulta por e-mail e Telegram, com retentativa automática em caso de falha.

### Configurar no Windows (Agendador de Tarefas)
1. Abra o Agendador de Tarefas do Windows
2. Criar Tarefa Básica → Repetir a cada **30 minutos**
3. Programa: `C:\xampp\php\php.exe`
4. Argumentos: `C:\xampp\htdocs\fisioterapia\cron\enviar_lembretes.php`

### Configurar no Linux (crontab)
```bash
crontab -e
# Adicionar linha:
*/30 * * * * php /var/www/html/fisioterapia/cron/enviar_lembretes.php >> /var/log/fisioterap.log 2>&1
```

Logs salvos em: `fisioterapia/logs/lembretes_YYYY-MM-DD.log`
