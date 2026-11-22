<?php
declare(strict_types=1);

// ─── Configuração do banco MySQL ────────────────────────────────────────────
const DB_HOST = 'localhost';
const DB_PORT = 3306;
const DB_NAME = 'vigia_check';
const DB_USER = 'root';
const DB_PASS = 'root';

// ─── Alertas de rondas ───────────────────────────────────────────────────────
// Em hospedagem Linux, habilite e configure o MTA/SMTP do servidor.
const ALERTAS_EMAIL_ATIVO = true;
const ALERTAS_EMAIL_REMETENTE = 'vigia@localhost';
const ALERTAS_EMAIL_NOME = 'VigiaCheck';

// ─── Pastas e limites ───────────────────────────────────────────────────────
const UPLOAD_DIR = __DIR__ . '/uploads';
const MAX_UPLOAD_BYTES = 6 * 1024 * 1024; // 6 MB

date_default_timezone_set('America/Sao_Paulo');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
