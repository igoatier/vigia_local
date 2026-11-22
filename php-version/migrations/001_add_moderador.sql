-- Execute uma vez em bancos já instalados antes de usar o novo perfil.
USE vigia_check;
ALTER TABLE usuarios MODIFY papel ENUM('admin', 'moderador', 'vigia') NOT NULL DEFAULT 'vigia';
