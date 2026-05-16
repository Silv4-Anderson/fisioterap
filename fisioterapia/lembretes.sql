-- =============================================
-- Adicionar tabela de lembretes ao banco
-- Execute após o banco.sql já ter sido importado
-- =============================================

USE fisioterapia_db;

CREATE TABLE IF NOT EXISTS lembretes (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  agendamento_id    INT NOT NULL UNIQUE,
  data_hora_consulta DATETIME NOT NULL,
  status            ENUM('pendente','enviado','falhou') DEFAULT 'pendente',
  enviado_em        TIMESTAMP NULL,
  tentativas        TINYINT DEFAULT 0,
  FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id) ON DELETE CASCADE,
  INDEX idx_status_data (status, data_hora_consulta)
);
