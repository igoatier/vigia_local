-- Horários de ronda configuráveis pelo administrador.
CREATE TABLE IF NOT EXISTS rondas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(100) NOT NULL,
  horario CHAR(5) NOT NULL,
  ordem INT UNSIGNED NOT NULL DEFAULT 1,
  tolerancia_minutos INT UNSIGNED NOT NULL DEFAULT 35,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_por INT UNSIGNED DEFAULT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_ronda_horario (horario),
  KEY idx_ronda_ativa_ordem (ativo, ordem),
  CONSTRAINT fk_ronda_criado_por FOREIGN KEY (criado_por) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT IGNORE INTO rondas (id, nome, horario, ordem, tolerancia_minutos, ativo)
VALUES
  (1, 'Ronda 00:00', '00:00', 1, 35, 1),
  (2, 'Ronda 02:00', '02:00', 2, 35, 1),
  (3, 'Ronda 04:00', '04:00', 3, 35, 1);
