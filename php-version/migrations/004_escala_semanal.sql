-- Escala semanal: 1 segunda-feira, ..., 7 domingo.
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
