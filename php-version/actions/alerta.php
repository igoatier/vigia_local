<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config.php';
require dirname(__DIR__) . '/lib/db.php';
require dirname(__DIR__) . '/lib/helpers.php';
require dirname(__DIR__) . '/lib/auth.php';
require dirname(__DIR__) . '/lib/alertas.php';

exigir_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    flash('erro', 'Sessão expirada. Tente novamente.');
    redirecionar('../index.php?page=alertas');
}

garantir_estrutura_alertas();
$acao = (string) ($_POST['acao'] ?? '');

if ($acao === 'salvar_escala') {
    if (!pode_gerenciar_equipe()) {
        flash('erro', 'Apenas administradores e moderadores podem alterar a escala.');
        redirecionar('../index.php?page=alertas');
    }

    $pdo = db();
    $vigiasStmt = $pdo->query("SELECT id FROM usuarios WHERE ativo = 1 AND papel = 'vigia'");
    $vigiasPermitidos = array_fill_keys(array_map('intval', $vigiasStmt->fetchAll(PDO::FETCH_COLUMN)), true);
    $usuarioId = (int) usuario_atual()['id'];

    try {
        $pdo->beginTransaction();
        $remover = $pdo->prepare('DELETE FROM escala_semanal WHERE dia_semana = ?');
        $inserir = $pdo->prepare(
            'INSERT INTO escala_semanal (dia_semana, responsavel_id, criado_por) VALUES (?, ?, ?)'
        );
        foreach (range(1, 7) as $dia) {
            $valor = filter_var($_POST['dia_' . $dia] ?? '', FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            if ($valor !== null && !isset($vigiasPermitidos[(int) $valor])) {
                throw new RuntimeException('A escala só pode ser atribuída a vigias ativos.');
            }
            $remover->execute([$dia]);
            if ($valor !== null && $valor > 0) {
                $inserir->execute([$dia, (int) $valor, $usuarioId]);
            }
        }
        $pdo->commit();
        flash('ok', 'Escala semanal salva. Ela será usada nas próximas verificações de ronda.');
    } catch (Throwable $erro) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('erro', 'Não foi possível salvar a escala semanal.');
    }
    redirecionar('../index.php?page=alertas');
}

$id = (int) ($_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM alertas_ronda WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$alerta = $stmt->fetch();

if (!$alerta) {
    flash('erro', 'Alerta não encontrado.');
    redirecionar('../index.php?page=alertas');
}

$usuario = usuario_atual();
$usuarioId = (int) $usuario['id'];

if ($acao === 'justificar') {
    $podeJustificar = eh_admin()
        || eh_moderador()
        || $alerta['responsavel_id'] === null
        || (int) $alerta['responsavel_id'] === $usuarioId;
    $justificativa = trim((string) ($_POST['justificativa'] ?? ''));

    if (!$podeJustificar) {
        flash('erro', 'Somente o vigia responsável pode justificar este alerta.');
    } elseif ($justificativa === '' || mb_strlen($justificativa) > 2000) {
        flash('erro', 'Informe uma justificativa com até 2.000 caracteres.');
    } elseif ($alerta['status'] === 'resolvido') {
        flash('erro', 'Este alerta já foi resolvido.');
    } else {
        $atualizar = db()->prepare(
            "UPDATE alertas_ronda SET justificativa = ?, justificado_por = ?, status = 'justificado' WHERE id = ?"
        );
        $atualizar->execute([$justificativa, $usuarioId, $id]);
        flash('ok', 'Justificativa registrada.');
    }
} elseif ($acao === 'resolver' && pode_gerenciar_equipe()) {
    $atualizar = db()->prepare(
        "UPDATE alertas_ronda SET status = 'resolvido', resolvido_em = NOW(), resolvido_por = ? WHERE id = ? AND status <> 'resolvido'"
    );
    $atualizar->execute([$usuarioId, $id]);
    flash('ok', 'Alerta marcado como resolvido.');
} else {
    flash('erro', 'Ação não permitida.');
}

redirecionar('../index.php?page=alertas');
