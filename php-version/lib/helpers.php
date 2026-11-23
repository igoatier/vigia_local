<?php
declare(strict_types=1);

const RONDAS = [1 => '00:00', 2 => '02:00', 3 => '04:00'];

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
    $hora = (int) date('G', $dataHora ? strtotime($dataHora) : time());
    if ($hora >= 23 || $hora < 1) {
        return 1;
    }
    if ($hora < 3) {
        return 2;
    }
    if ($hora < 5) {
        return 3;
    }
    return 1;
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
