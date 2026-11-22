<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $erro) {
            error_log('VigiaCheck: falha de conexão com o banco; código ' . $erro->getCode());
            http_response_code(503);
            exit('<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>VigiaCheck indisponível</title><style>body{margin:0;padding:32px;background:#0b0e14;color:#f4f1ea;font:16px system-ui}main{max-width:520px;margin:15vh auto}h1{color:#f5b841}p{color:#8b95a6;line-height:1.6}</style><main><h1>Banco de dados indisponível</h1><p>Não foi possível conectar ao banco agora. Verifique o serviço MySQL/MariaDB e as credenciais em <code>config.php</code>.</p></main></html>');
        }
    }

    return $pdo;
}
