-- VigiaCheck · banco de dados MySQL
-- Importe este arquivo no phpMyAdmin (aba "Importar") antes de abrir install.php

CREATE DATABASE IF NOT EXISTS ptechne1_vigiacheckV2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ptechne1_vigiacheckV2;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  senha_hash VARCHAR(255) NOT NULL,
  papel ENUM('admin', 'moderador', 'vigia') NOT NULL DEFAULT 'vigia',
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_usuarios_email (email)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS postos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(120) NOT NULL,
  endereco VARCHAR(180) DEFAULT NULL,
  latitude DECIMAL(10, 7) NOT NULL,
  longitude DECIMAL(10, 7) NOT NULL,
  foto_local VARCHAR(255) DEFAULT NULL,
  raio_metros INT UNSIGNED NOT NULL DEFAULT 1,
  ordem INT UNSIGNED NOT NULL DEFAULT 1,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS escala_semanal (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  dia_semana TINYINT UNSIGNED NOT NULL,
  responsavel_id INT UNSIGNED NOT NULL,
  criado_por INT UNSIGNED DEFAULT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_escala_dia_semana (dia_semana),
  KEY idx_escala_responsavel (responsavel_id),
  CONSTRAINT fk_escala_responsavel FOREIGN KEY (responsavel_id) REFERENCES usuarios (id) ON DELETE CASCADE,
  CONSTRAINT fk_escala_criado_por FOREIGN KEY (criado_por) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS registros (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  sync_id CHAR(36) NULL,
  posto_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NOT NULL,
  ronda TINYINT UNSIGNED NOT NULL,
  ronda_horario CHAR(5) NOT NULL,
  latitude DECIMAL(10, 7) NOT NULL,
  longitude DECIMAL(10, 7) NOT NULL,
  precisao_metros INT UNSIGNED DEFAULT NULL,
  distancia_metros INT UNSIGNED DEFAULT NULL,
  dentro_raio TINYINT(1) NOT NULL DEFAULT 0,
  foto VARCHAR(255) NOT NULL,
  observacao VARCHAR(500) DEFAULT NULL,
  turno_data DATE NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_registros_sync_id (sync_id),
  UNIQUE KEY uk_registro_ronda (posto_id, usuario_id, turno_data, ronda),
  KEY idx_turno (turno_data, ronda),
  KEY idx_usuario (usuario_id, turno_data),
  CONSTRAINT fk_registros_posto FOREIGN KEY (posto_id) REFERENCES postos (id) ON DELETE CASCADE,
  CONSTRAINT fk_registros_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS alertas_ronda (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  turno_data DATE NOT NULL,
  ronda TINYINT UNSIGNED NOT NULL,
  responsavel_id INT UNSIGNED DEFAULT NULL,
  postos_pendentes TEXT NOT NULL,
  quantidade_pendente INT UNSIGNED NOT NULL DEFAULT 0,
  mensagem VARCHAR(500) NOT NULL,
  status ENUM('aberto', 'justificado', 'resolvido') NOT NULL DEFAULT 'aberto',
  justificativa TEXT DEFAULT NULL,
  justificado_por INT UNSIGNED DEFAULT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  email_enviado_em DATETIME DEFAULT NULL,
  resolvido_em DATETIME DEFAULT NULL,
  resolvido_por INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_alerta_turno_ronda (turno_data, ronda),
  KEY idx_alerta_status (status, criado_em),
  CONSTRAINT fk_alerta_responsavel FOREIGN KEY (responsavel_id) REFERENCES usuarios (id) ON DELETE SET NULL,
  CONSTRAINT fk_alerta_justificado_por FOREIGN KEY (justificado_por) REFERENCES usuarios (id) ON DELETE SET NULL,
  CONSTRAINT fk_alerta_resolvido_por FOREIGN KEY (resolvido_por) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
