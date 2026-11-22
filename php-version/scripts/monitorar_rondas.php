<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/config.php';
require dirname(__DIR__) . '/lib/db.php';
require dirname(__DIR__) . '/lib/helpers.php';
require dirname(__DIR__) . '/lib/alertas.php';

garantir_estrutura_alertas();
$pdo = db();
$agora = new DateTimeImmutable('now');
$turnoData = turno_data($agora->format('Y-m-d H:i:s'));
$horariosLimite = [1 => '00:35', 2 => '02:35', 3 => '04:35'];

$argumentoRonda = null;
foreach ($argv as $argumento) {
    if (preg_match('/^--ronda=([123])$/', $argumento, $m)) {
        $argumentoRonda = (int) $m[1];
    }
}

$rondas = $argumentoRonda ? [$argumentoRonda] : array_keys($horariosLimite);
$relatorio = [];

foreach ($rondas as $ronda) {
    $limite = new DateTimeImmutable($agora->format('Y-m-d') . ' ' . $horariosLimite[$ronda] . ':00');
    if (!$argumentoRonda && $agora < $limite) continue;

    $pendentesStmt = $pdo->prepare(
        'SELECT p.id, p.nome
         FROM postos p
         WHERE p.ativo = 1
           AND NOT EXISTS (
             SELECT 1 FROM registros r
             WHERE r.posto_id = p.id AND r.turno_data = ? AND r.ronda = ?
           )
         ORDER BY p.ordem, p.nome'
    );
    $pendentesStmt->execute([$turnoData, $ronda]);
    $pendentes = $pendentesStmt->fetchAll();
    if (!$pendentes) {
        $relatorio[] = 'Ronda ' . RONDAS[$ronda] . ': concluída.';
        continue;
    }

    // A escala fixa do dia tem prioridade; o primeiro registro é apenas fallback.
    $responsavel = responsavel_da_escala($pdo, $turnoData);
    $responsavelFonte = $responsavel ? 'escala semanal' : 'primeiro registro';
    if (!$responsavel) {
        $responsavelStmt = $pdo->prepare(
            'SELECT r.usuario_id, u.nome
             FROM registros r
             JOIN usuarios u ON u.id = r.usuario_id
             WHERE r.turno_data = ? AND r.ronda = ?
             ORDER BY r.criado_em, r.id
             LIMIT 1'
        );
        $responsavelStmt->execute([$turnoData, $ronda]);
        $responsavel = $responsavelStmt->fetch() ?: null;
    }

    $nomesPendentes = array_map(static fn (array $posto): string => (string) $posto['nome'], $pendentes);
    $quantidade = count($nomesPendentes);
    $responsavelNome = $responsavel['nome'] ?? 'Nenhum vigia identificado (nenhum registro foi realizado)';
    $mensagem = 'Ronda ' . RONDAS[$ronda] . ' atrasada: ' . $quantidade . ' posto(s) pendente(s).';

    $inserir = $pdo->prepare(
        'INSERT IGNORE INTO alertas_ronda
         (turno_data, ronda, responsavel_id, postos_pendentes, quantidade_pendente, mensagem)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $inserir->execute([
        $turnoData,
        $ronda,
        $responsavel['usuario_id'] ?? null,
        json_encode($nomesPendentes, JSON_UNESCAPED_UNICODE),
        $quantidade,
        $mensagem,
    ]);

    if ($inserir->rowCount() === 0) {
        $relatorio[] = 'Ronda ' . RONDAS[$ronda] . ': alerta já existente.';
        continue;
    }

    $alertaId = (int) $pdo->lastInsertId();
    $destinatariosStmt = $pdo->query(
        "SELECT email FROM usuarios WHERE ativo = 1 AND papel IN ('admin', 'moderador') AND email <> ''"
    );
    $destinatarios = $destinatariosStmt->fetchAll(PDO::FETCH_COLUMN);
    $corpo = "A ronda das " . RONDAS[$ronda] . " não foi concluída dentro do prazo de 35 minutos.\n\n"
        . "Data do turno: " . date('d/m/Y', strtotime($turnoData)) . "\n"
        . "Vigia responsável: " . $responsavelNome . "\n"
        . "Responsável definido pela: " . $responsavelFonte . "\n"
        . "Horário do alerta: " . $agora->format('d/m/Y H:i') . "\n"
        . "Quantidade de postos pendentes: " . $quantidade . "\n\n"
        . "Postos pendentes:\n- " . implode("\n- ", $nomesPendentes) . "\n\n"
        . "Acesse o VigiaCheck para registrar uma justificativa ou resolver o alerta.";
    $enviados = alertas_email('VigiaCheck - Ronda ' . RONDAS[$ronda] . ' atrasada', $corpo, $destinatarios);
    if ($enviados > 0) {
        $pdo->prepare('UPDATE alertas_ronda SET email_enviado_em = NOW() WHERE id = ?')->execute([$alertaId]);
    }

    $relatorio[] = 'Ronda ' . RONDAS[$ronda] . ': alerta criado; responsável pela ' . $responsavelFonte . '; ' . $enviados . ' e-mail(s) enviado(s).';
}

echo implode(PHP_EOL, $relatorio) . PHP_EOL;
