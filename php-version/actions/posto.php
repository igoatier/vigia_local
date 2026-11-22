<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config.php';
require dirname(__DIR__) . '/lib/db.php';
require dirname(__DIR__) . '/lib/helpers.php';
require dirname(__DIR__) . '/lib/auth.php';

exigir_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    flash('erro', 'Sessão expirada. Tente novamente.');
    redirecionar('../index.php?page=postos');
}

$acao = $_POST['acao'] ?? '';

if ($acao === 'salvar') {
    $id = (int) ($_POST['id'] ?? 0);
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $endereco = trim((string) ($_POST['endereco'] ?? ''));
    $latitude = (float) str_replace(',', '.', (string) ($_POST['latitude'] ?? ''));
    $longitude = (float) str_replace(',', '.', (string) ($_POST['longitude'] ?? ''));
    $raio = min(5000, max(1, (int) ($_POST['raio_metros'] ?? 1)));
    $ordem = max(1, (int) ($_POST['ordem'] ?? 1));

    $voltar = $id > 0 ? '../index.php?page=postos&editar=' . $id : '../index.php?page=postos';

    if ($nome === '') {
        flash('erro', 'Informe o nome do posto.');
        redirecionar($voltar);
    }
    if ($latitude === 0.0 && $longitude === 0.0) {
        flash('erro', 'Informe as coordenadas do posto (use o botão de localização).');
        redirecionar($voltar);
    }

    if ($id > 0) {
        $atualizar = db()->prepare('UPDATE postos SET nome = ?, endereco = ?, latitude = ?, longitude = ?, raio_metros = ?, ordem = ?, ativo = 1 WHERE id = ?');
        $atualizar->execute([$nome, $endereco !== '' ? $endereco : null, $latitude, $longitude, $raio, $ordem, $id]);
        flash('ok', 'Posto atualizado.');
    } else {
        $inserir = db()->prepare('INSERT INTO postos (nome, endereco, latitude, longitude, raio_metros, ordem, ativo) VALUES (?, ?, ?, ?, ?, ?, 1)');
        $inserir->execute([$nome, $endereco !== '' ? $endereco : null, $latitude, $longitude, $raio, $ordem]);
        flash('ok', 'Posto cadastrado.');
    }

    redirecionar('../index.php?page=postos');
}

if ($acao === 'excluir') {
    $id = (int) ($_POST['id'] ?? 0);
    db()->prepare('UPDATE postos SET ativo = 0 WHERE id = ?')->execute([$id]);
    flash('ok', 'Posto desativado.');
    redirecionar('../index.php?page=postos');
}

redirecionar('../index.php?page=postos');