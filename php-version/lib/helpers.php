<?php
declare(strict_types=1);

const RONDAS = [1 => '00:00', 2 => '02:00', 3 => '04:00'];

function garantir_estrutura_rondas(): void
{
    static $pronto = false;
    if ($pronto) return;

    $pdo = db();
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS rondas (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            nome VARCHAR(100) NOT NULL,
            horario CHAR(5) NOT NULL,
            ordem INT UNSIGNED NOT NULL DEFAULT 1,
            tolerancia_minutos INT UNSIGNED NOT NULL DEFAULT 35,
            ativo TINYINT(1) NOT NULL DEFAULT 1,
            criado_por INT UNSIGNED NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_ronda_horario (horario),
            KEY idx_ronda_ativa_ordem (ativo, ordem),
            CONSTRAINT fk_ronda_criado_por FOREIGN KEY (criado_por) REFERENCES usuarios (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $total = (int) $pdo->query('SELECT COUNT(*) FROM rondas')->fetchColumn();
    if ($total === 0) {
        $inserir = $pdo->prepare('INSERT INTO rondas (id, nome, horario, ordem, tolerancia_minutos, ativo) VALUES (?, ?, ?, ?, 35, 1)');
        foreach ([1 => ['Ronda 00:00', '00:00'], 2 => ['Ronda 02:00', '02:00'], 3 => ['Ronda 04:00', '04:00']] as $id => [$nome, $horario]) {
            $inserir->execute([$id, $nome, $horario, $id]);
        }
    }
    $pronto = true;
}

function rondas_ativas(): array
{
    garantir_estrutura_rondas();
    $stmt = db()->query('SELECT * FROM rondas WHERE ativo = 1 ORDER BY ordem, horario, id');
    $rondas = [];
    foreach ($stmt->fetchAll() as $ronda) {
        $rondas[(int) $ronda['id']] = $ronda;
    }
    return $rondas;
}

function rondas_todas(): array
{
    garantir_estrutura_rondas();
    $stmt = db()->query('SELECT * FROM rondas ORDER BY ordem, horario, id');
    $rondas = [];
    foreach ($stmt->fetchAll() as $ronda) {
        $rondas[(int) $ronda['id']] = $ronda;
    }
    return $rondas;
}

function ronda_configuracao(int $id, bool $somenteAtiva = true): ?array
{
    $rondas = $somenteAtiva ? rondas_ativas() : rondas_todas();
    return $rondas[$id] ?? null;
}

function e(?string $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function redirecionar(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function ronda_atual(?string $dataHora = null): int
{
    $rondas = rondas_ativas();
    if (!$rondas) return 1;
    $horaAtual = date('H:i', $dataHora ? strtotime($dataHora) : time());
    $primeira = (int) array_key_first($rondas);
    $selecionada = $primeira;
    foreach ($rondas as $id => $ronda) {
        if ((string) $ronda['horario'] <= $horaAtual) {
            $selecionada = (int) $id;
        }
    }
    return $selecionada;
}

// Noite de trabalho: 18h até a manhã seguinte pertence ao mesmo turno.
function turno_data(?string $dataHora = null): string
{
    $ts = $dataHora ? strtotime($dataHora) : time();
    $hora = (int) date('G', $ts);
    return $hora < 12 ? date('Y-m-d', strtotime('-1 day', $ts)) : date('Y-m-d', $ts);
}

function distancia_metros(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $raioTerra = 6371000.0;
    $rad = static fn (float $graus): float => $graus * M_PI / 180;
    $dLat = $rad($lat2 - $lat1);
    $dLon = $rad($lon2 - $lon1);
    $h = sin($dLat / 2) ** 2 + cos($rad($lat1)) * cos($rad($lat2)) * sin($dLon / 2) ** 2;
    return 2 * $raioTerra * asin(sqrt($h));
}

function formatar_distancia(?float $metros): string
{
    if ($metros === null) {
        return '—';
    }
    return $metros < 1000
        ? (string) round($metros) . ' m'
        : number_format($metros / 1000, 1, ',', '') . ' km';
}

function formatar_hora(?string $dataHora): string
{
    return $dataHora ? date('H:i', strtotime($dataHora)) : '';
}

function data_br(?string $data): string
{
    return $data ? date('d/m/Y', strtotime($data)) : '';
}

function rotulo_dia(string $dataHora): string
{
    $ts = strtotime($dataHora);
    $dia = date('Y-m-d', $ts);
    if ($dia === date('Y-m-d')) {
        return 'Hoje';
    }
    if ($dia === date('Y-m-d', strtotime('-1 day'))) {
        return 'Ontem';
    }
    $diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    return $diasSemana[(int) date('w', $ts)] . ', ' . date('d/m', $ts);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_valido(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals((string) $_SESSION['csrf'], (string) $_POST['csrf']);
}

function flash(string $tipo, string $mensagem): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function flash_pegar(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function garantir_foto_local_postos(): void
{
    static $pronto = false;
    if ($pronto) return;

    $coluna = db()->query("SHOW COLUMNS FROM postos LIKE 'foto_local'")->fetch();
    if (!$coluna) {
        db()->exec('ALTER TABLE postos ADD COLUMN foto_local VARCHAR(255) DEFAULT NULL AFTER longitude');
    }
    $pronto = true;
}
