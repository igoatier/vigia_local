<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config.php';
require dirname(__DIR__) . '/lib/db.php';
require dirname(__DIR__) . '/lib/helpers.php';
require dirname(__DIR__) . '/lib/auth.php';

exigir_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    flash('erro', 'Sessão expirada. Tente novamente.');
    redirecionar('../index.php?page=horarios');
}

garantir_estrutura_rondas();
$pdo = db();
$acao = (string) ($_POST['acao'] ?? '');
$id = (int) ($_POST['id'] ?? 0);

if ($acao !== 'salvar') {
    flash('erro', 'Ação não permitida.');
    redirecionar('../index.php?page=horarios');
}

$nome = trim((string) ($_POST['nome'] ?? ''));
$horario = trim((string) ($_POST['horario'] ?? ''));
$ordem = max(1, min(999, (int) ($_POST['ordem'] ?? 1)));
$tolerancia = max(0, min(240, (int) ($_POST['tolerancia_minutos'] ?? 35)));
$ativo = $id > 0 ? ((int) ($_POST['ativo'] ?? 1) === 1 ? 1 : 0) : 1;

if ($nome === '' || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $horario)) {
    flash('erro', 'Informe um nome e um horário válido no formato HH:MM.');
    redirecionar('../index.php?page=horarios' . ($id > 0 ? '&editar=' . $id : ''));
}

$duplicado = $pdo->prepare('SELECT id FROM rondas WHERE horario = ? AND id <> ? LIMIT 1');
$duplicado->execute([$horario, $id]);
if ($duplicado->fetch()) {
    flash('erro', 'Já existe uma ronda cadastrada para este horário.');
    redirecionar('../index.php?page=horarios' . ($id > 0 ? '&editar=' . $id : ''));
}

if ($id > 0) {
    $existente = $pdo->prepare('SELECT id FROM rondas WHERE id = ? LIMIT 1');
    $existente->execute([$id]);
    if (!$existente->fetch()) {
        flash('erro', 'Horário não encontrado.');
        redirecionar('../index.php?page=horarios');
    }
}

if ($ativo === 0) {
    $ativos = (int) $pdo->query('SELECT COUNT(*) FROM rondas WHERE ativo = 1 AND id <> ' . $id)->fetchColumn();
    if ($ativos === 0) {
        flash('erro', 'Mantenha pelo menos um horário de ronda ativo.');
        redirecionar('../index.php?page=horarios&editar=' . $id);
    }
}

if ($id > 0) {
    $stmt = $pdo->prepare('UPDATE rondas SET nome = ?, horario = ?, ordem = ?, tolerancia_minutos = ?, ativo = ? WHERE id = ?');
    $stmt->execute([$nome, $horario, $ordem, $tolerancia, $ativo, $id]);
    flash('ok', 'Horário da ronda atualizado.');
} else {
    $stmt = $pdo->prepare('INSERT INTO rondas (nome, horario, ordem, tolerancia_minutos, ativo, criado_por) VALUES (?, ?, ?, ?, 1, ?)');
    $stmt->execute([$nome, $horario, $ordem, $tolerancia, (int) usuario_atual()['id']]);
    flash('ok', 'Novo horário de ronda criado.');
}

redirecionar('../index.php?page=horarios');
