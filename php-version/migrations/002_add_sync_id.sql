-- Identificador idempotente dos registros criados online ou offline.
-- Execute no banco vigia_check antes de usar a fila offline.

ALTER TABLE registros
  ADD COLUMN sync_id CHAR(36) NULL AFTER id;

ALTER TABLE registros
  ADD UNIQUE KEY uk_registros_sync_id (sync_id);
