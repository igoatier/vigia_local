<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config.php';
require dirname(__DIR__) . '/lib/db.php';
require dirname(__DIR__) . '/lib/helpers.php';
require dirname(__DIR__) . '/lib/auth.php';

exigir_admin();
garantir_foto_local_postos();

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
    $fotoNova = null;
    $fotoAnterior = null;

    $voltar = $id > 0 ? '../index.php?page=postos&editar=' . $id : '../index.php?page=postos';

    if ($nome === '') {
        flash('erro', 'Informe o nome do posto.');
        redirecionar($voltar);
    }
    if ($latitude === 0.0 && $longitude === 0.0) {
        flash('erro', 'Informe as coordenadas do posto (use o botão de localização).');
        redirecionar($voltar);
    }

    if (isset($_FILES['foto_local']) && (int) $_FILES['foto_local']['error'] !== UPLOAD_ERR_NO_FILE) {
        $arquivo = $_FILES['foto_local'];
        if ((int) $arquivo['error'] !== UPLOAD_ERR_OK || (int) $arquivo['size'] > MAX_UPLOAD_BYTES) {
            flash('erro', 'A foto do local é inválida ou ultrapassa o limite de 6 MB.');
            redirecionar($voltar);
        }
        $imagem = @getimagesize((string) $arquivo['tmp_name']);
        $mime = is_array($imagem) ? (string) ($imagem['mime'] ?? '') : '';
        $extensoes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($extensoes[$mime])) {
            flash('erro', 'A foto do local deve ser JPEG, PNG ou WebP.');
            redirecionar($voltar);
        }
        if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0775, true) && !is_dir(UPLOAD_DIR)) {
            flash('erro', 'Não foi possível preparar a pasta de fotos.');
            redirecionar($voltar);
        }
        $fotoNova = bin2hex(random_bytes(16)) . '.' . $extensoes[$mime];
        if (!move_uploaded_file((string) $arquivo['tmp_name'], UPLOAD_DIR . '/' . $fotoNova)) {
            flash('erro', 'Não foi possível salvar a foto do local.');
            redirecionar($voltar);
        }
    }

    if ($id > 0) {
        $existente = db()->prepare('SELECT foto_local FROM postos WHERE id = ? LIMIT 1');
        $existente->execute([$id]);
        $postoAnterior = $existente->fetch();
        $fotoAnterior = $postoAnterior['foto_local'] ?? null;
        $sql = 'UPDATE postos SET nome = ?, endereco = ?, latitude = ?, longitude = ?, raio_metros = ?, ordem = ?, ativo = 1';
        $parametros = [$nome, $endereco !== '' ? $endereco : null, $latitude, $longitude, $raio, $ordem];
        if ($fotoNova !== null) {
            $sql .= ', foto_local = ?';
            $parametros[] = $fotoNova;
        }
        $sql .= ' WHERE id = ?';
        $parametros[] = $id;
        db()->prepare($sql)->execute($parametros);
        if ($fotoNova !== null && $fotoAnterior && is_file(UPLOAD_DIR . '/' . basename((string) $fotoAnterior))) {
            @unlink(UPLOAD_DIR . '/' . basename((string) $fotoAnterior));
        }
        flash('ok', 'Posto atualizado.');
    } else {
        $inserir = db()->prepare('INSERT INTO postos (nome, endereco, latitude, longitude, foto_local, raio_metros, ordem, ativo) VALUES (?, ?, ?, ?, ?, ?, ?, 1)');
        $inserir->execute([$nome, $endereco !== '' ? $endereco : null, $latitude, $longitude, $fotoNova, $raio, $ordem]);
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
