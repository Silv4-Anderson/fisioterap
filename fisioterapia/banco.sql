-- =============================================
-- Sistema de Agendamento de Fisioterapia
-- Banco de Dados MySQL
-- =============================================

CREATE DATABASE IF NOT EXISTS fisioterapia_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE fisioterapia_db;

-- Tabela de usuários (pacientes)
CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  telefone VARCHAR(20),
  data_nascimento DATE,
  cpf VARCHAR(14) UNIQUE,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de fisioterapeutas
CREATE TABLE IF NOT EXISTS fisioterapeutas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  crefito VARCHAR(20) NOT NULL UNIQUE,
  especialidade VARCHAR(100),
  email VARCHAR(150),
  foto VARCHAR(255) DEFAULT 'assets/default-doctor.png'
);

-- Tabela de horários disponíveis
CREATE TABLE IF NOT EXISTS horarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fisioterapeuta_id INT NOT NULL,
  dia_semana TINYINT NOT NULL COMMENT '0=Dom, 1=Seg, ..., 6=Sab',
  hora_inicio TIME NOT NULL,
  hora_fim TIME NOT NULL,
  disponivel TINYINT(1) DEFAULT 1,
  FOREIGN KEY (fisioterapeuta_id) REFERENCES fisioterapeutas(id) ON DELETE CASCADE
);

-- Tabela de agendamentos
CREATE TABLE IF NOT EXISTS agendamentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  fisioterapeuta_id INT NOT NULL,
  data_consulta DATE NOT NULL,
  hora_consulta TIME NOT NULL,
  status ENUM('pendente','confirmado','cancelado','concluido') DEFAULT 'pendente',
  observacoes TEXT,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (fisioterapeuta_id) REFERENCES fisioterapeutas(id) ON DELETE CASCADE
);

-- Dados de exemplo: fisioterapeutas
INSERT INTO fisioterapeutas (nome, crefito, especialidade, email) VALUES
('Dra. Ana Paula Silva', 'CREFITO-3/123456-F', 'Ortopedia e Traumatologia', 'ana.paula@clinica.com'),
('Dr. Carlos Eduardo Souza', 'CREFITO-3/654321-F', 'Neurologia', 'carlos.souza@clinica.com'),
('Dra. Marina Costa Lima', 'CREFITO-3/112233-F', 'Esportiva', 'marina.lima@clinica.com');

-- Horários de exemplo
INSERT INTO horarios (fisioterapeuta_id, dia_semana, hora_inicio, hora_fim) VALUES
(1, 1, '08:00', '08:50'), (1, 1, '09:00', '09:50'), (1, 1, '10:00', '10:50'),
(1, 3, '08:00', '08:50'), (1, 3, '09:00', '09:50'), (1, 3, '14:00', '14:50'),
(2, 2, '09:00', '09:50'), (2, 2, '10:00', '10:50'), (2, 4, '13:00', '13:50'),
(3, 1, '14:00', '14:50'), (3, 3, '15:00', '15:50'), (3, 5, '09:00', '09:50');

-- Tabela de lembretes (notificações automáticas)
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
