-- Foto opcional do local associado ao posto.
ALTER TABLE postos
  ADD COLUMN foto_local VARCHAR(255) DEFAULT NULL AFTER longitude;
