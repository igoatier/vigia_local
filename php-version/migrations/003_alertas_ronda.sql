-- Alertas de rondas não concluídas após 35 minutos.
CREATE TABLE IF NOT EXISTS alertas_ronda (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  turno_data DATE NOT NULL,
  ronda TINYINT UNSIGNED NOT NULL,
  responsavel_id INT UNSIGNED NULL,
  postos_pendentes TEXT NOT NULL,
  quantidade_pendente INT UNSIGNED NOT NULL DEFAULT 0,
  mensagem VARCHAR(500) NOT NULL,
  status ENUM('aberto', 'justificado', 'resolvido') NOT NULL DEFAULT 'aberto',
  justificativa TEXT DEFAULT NULL,
  justificado_por INT UNSIGNED NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  email_enviado_em DATETIME DEFAULT NULL,
  resolvido_em DATETIME DEFAULT NULL,
  resolvido_por INT UNSIGNED NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_alerta_turno_ronda (turno_data, ronda),
  KEY idx_alerta_status (status, criado_em),
  CONSTRAINT fk_alerta_responsavel FOREIGN KEY (responsavel_id) REFERENCES usuarios (id) ON DELETE SET NULL,
  CONSTRAINT fk_alerta_justificado_por FOREIGN KEY (justificado_por) REFERENCES usuarios (id) ON DELETE SET NULL,
  CONSTRAINT fk_alerta_resolvido_por FOREIGN KEY (resolvido_por) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
