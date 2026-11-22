<?php
declare(strict_types=1);

function garantir_estrutura_alertas(): void
{
    static $pronto = false;
    if ($pronto) return;

    $pdo = db();
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS escala_semanal (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            dia_semana TINYINT UNSIGNED NOT NULL,
            responsavel_id INT UNSIGNED NOT NULL,
            criado_por INT UNSIGNED NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_escala_dia_semana (dia_semana),
            KEY idx_escala_responsavel (responsavel_id),
            CONSTRAINT fk_escala_responsavel FOREIGN KEY (responsavel_id) REFERENCES usuarios (id) ON DELETE CASCADE,
            CONSTRAINT fk_escala_criado_por FOREIGN KEY (criado_por) REFERENCES usuarios (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS alertas_ronda (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            turno_data DATE NOT NULL,
            ronda TINYINT UNSIGNED NOT NULL,
            responsavel_id INT UNSIGNED NULL,
            postos_pendentes TEXT NOT NULL,
            quantidade_pendente INT UNSIGNED NOT NULL DEFAULT 0,
            mensagem VARCHAR(500) NOT NULL,
            status ENUM('aberto','justificado','resolvido') NOT NULL DEFAULT 'aberto',
            justificativa TEXT NULL,
            justificado_por INT UNSIGNED NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            email_enviado_em DATETIME NULL,
            resolvido_em DATETIME NULL,
            resolvido_por INT UNSIGNED NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uk_alerta_turno_ronda (turno_data, ronda),
            KEY idx_alerta_status (status, criado_em),
            CONSTRAINT fk_alerta_responsavel FOREIGN KEY (responsavel_id) REFERENCES usuarios (id) ON DELETE SET NULL,
            CONSTRAINT fk_alerta_justificado_por FOREIGN KEY (justificado_por) REFERENCES usuarios (id) ON DELETE SET NULL,
            CONSTRAINT fk_alerta_resolvido_por FOREIGN KEY (resolvido_por) REFERENCES usuarios (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pronto = true;
}

function buscar_escala_semanal(): array
{
    garantir_estrutura_alertas();
    $stmt = db()->query(
        'SELECT e.dia_semana, e.responsavel_id, u.nome AS responsavel_nome
         FROM escala_semanal e
         JOIN usuarios u ON u.id = e.responsavel_id
         ORDER BY e.dia_semana'
    );
    $escala = [];
    foreach ($stmt->fetchAll() as $linha) {
        $escala[(int) $linha['dia_semana']] = $linha;
    }
    return $escala;
}

function vigias_ativos_para_escala(): array
{
    $stmt = db()->query("SELECT id, nome FROM usuarios WHERE ativo = 1 AND papel = 'vigia' ORDER BY nome");
    return $stmt->fetchAll();
}

function responsavel_da_escala(PDO $pdo, string $turnoData): ?array
{
    $diaSemana = (int) date('N', strtotime($turnoData));
    $stmt = $pdo->prepare(
        "SELECT e.responsavel_id AS usuario_id, u.nome
         FROM escala_semanal e
         JOIN usuarios u ON u.id = e.responsavel_id AND u.ativo = 1 AND u.papel = 'vigia'
         WHERE e.dia_semana = ?
         LIMIT 1"
    );
    $stmt->execute([$diaSemana]);
    $responsavel = $stmt->fetch();
    return $responsavel ?: null;
}

function status_alerta_rotulo(string $status): string
{
    return match ($status) {
        'justificado' => 'Justificado',
        'resolvido' => 'Resolvido',
        default => 'Aberto',
    };
}

function alertas_email(string $assunto, string $corpo, array $destinatarios): int
{
    if (!defined('ALERTAS_EMAIL_ATIVO') || !ALERTAS_EMAIL_ATIVO || !$destinatarios || !function_exists('mail')) {
        return 0;
    }

    $para = implode(',', array_unique(array_filter(array_map('strval', $destinatarios))));
    if ($para === '') return 0;

    $remetente = defined('ALERTAS_EMAIL_REMETENTE') ? ALERTAS_EMAIL_REMETENTE : 'vigia@localhost';
    $nome = defined('ALERTAS_EMAIL_NOME') ? ALERTAS_EMAIL_NOME : 'VigiaCheck';
    $cabecalhos = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $nome . ' <' . $remetente . '>',
        'Reply-To: ' . $remetente,
    ];

    return mail($para, '=?UTF-8?B?' . base64_encode($assunto) . '?=', $corpo, implode("\r\n", $cabecalhos)) ? count(explode(',', $para)) : 0;
}

function buscar_alertas(array $filtros = []): array
{
    garantir_estrutura_alertas();
    $sql = "SELECT a.*, r.nome AS responsavel_nome, j.nome AS justificado_nome, u.nome AS resolvido_nome
            FROM alertas_ronda a
            LEFT JOIN usuarios r ON r.id = a.responsavel_id
            LEFT JOIN usuarios j ON j.id = a.justificado_por
            LEFT JOIN usuarios u ON u.id = a.resolvido_por
            WHERE 1=1";
    $parametros = [];

    if (!empty($filtros['status'])) {
        $sql .= ' AND a.status = ?';
        $parametros[] = $filtros['status'];
    }
    if (!empty($filtros['data'])) {
        $sql .= ' AND a.turno_data = ?';
        $parametros[] = $filtros['data'];
    }
    if (isset($filtros['responsavel_id'])) {
        $sql .= ' AND a.responsavel_id = ?';
        $parametros[] = (int) $filtros['responsavel_id'];
    }
    $sql .= ' ORDER BY a.criado_em DESC, a.turno_data DESC, a.ronda DESC LIMIT 200';

    $stmt = db()->prepare($sql);
    $stmt->execute($parametros);
    $alertas = $stmt->fetchAll();
    foreach ($alertas as &$alerta) {
        $alerta['postos_pendentes_lista'] = json_decode((string) $alerta['postos_pendentes'], true) ?: [];
    }
    unset($alerta);
    return $alertas;
}
