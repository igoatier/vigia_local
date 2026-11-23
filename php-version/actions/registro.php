<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config.php';
require dirname(__DIR__) . '/lib/db.php';
require dirname(__DIR__) . '/lib/helpers.php';
require dirname(__DIR__) . '/lib/auth.php';

exigir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    flash('erro', 'Sessão expirada ou limite de upload excedido. Tente novamente.');
    redirecionar('../index.php');
}

$postoId = (int) ($_POST['posto_id'] ?? 0);
$ronda = (int) ($_POST['ronda'] ?? 0);
$rondaConfiguracao = ronda_configuracao($ronda);
if (!$rondaConfiguracao) {
    flash('erro', 'Horário de ronda inválido ou desativado.');
    redirecionar('../index.php?page=ronda');
}
$latitude = (float) str_replace(',', '.', (string) ($_POST['latitude'] ?? ''));
$longitude = (float) str_replace(',', '.', (string) ($_POST['longitude'] ?? ''));
$precisao = max(0, (int) ($_POST['precisao'] ?? 0));
$observacao = trim((string) ($_POST['observacao'] ?? ''));

$voltar = '../index.php?page=registrar&posto=' . $postoId . '&ronda=' . $ronda;

$stmt = db()->prepare('SELECT * FROM postos WHERE id = ? AND ativo = 1');
$stmt->execute([$postoId]);
$posto = $stmt->fetch();

if (!$posto) {
    flash('erro', 'Posto não encontrado.');
    redirecionar('../index.php');
}

if ($latitude === 0.0 && $longitude === 0.0) {
    flash('erro', 'Localização não capturada. Autorize o acesso ao GPS e tente novamente.');
    redirecionar($voltar);
}

$arquivo = $_FILES['foto'] ?? null;

if (!$arquivo || $arquivo['error'] !== UPLOAD_ERR_OK) {
    if ($arquivo && $arquivo['error'] === UPLOAD_ERR_INI_SIZE) {
        flash('erro', 'A foto ultrapassou o limite de upload do servidor.');
    } else {
        flash('erro', 'Anexe a foto do ponto.');
    }
    redirecionar($voltar);
}
if ($arquivo['size'] > MAX_UPLOAD_BYTES) {
    flash('erro', 'A foto deve ter no máximo 6 MB.');
    redirecionar($voltar);
}

$extensoes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);

if (!isset($extensoes[$mime])) {
    flash('erro', 'Envie uma foto em JPG, PNG ou WEBP.');
    redirecionar($voltar);
}

$distancia = distancia_metros($latitude, $longitude, (float) $posto['latitude'], (float) $posto['longitude']);
$dentro = $distancia <= (int) $posto['raio_metros'];

if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0775, true) && !is_dir(UPLOAD_DIR)) {
    flash('erro', 'A pasta uploads não existe e não pôde ser criada.');
    redirecionar($voltar);
}

$nomeArquivo = bin2hex(random_bytes(12)) . '.' . $extensoes[$mime];

if (!move_uploaded_file($arquivo['tmp_name'], UPLOAD_DIR . '/' . $nomeArquivo)) {
    flash('erro', 'Não foi possível salvar a foto. Verifique as permissões da pasta uploads.');
    redirecionar($voltar);
}

try {
    $inserir = db()->prepare(
        'INSERT INTO registros (posto_id, usuario_id, ronda, ronda_horario, latitude, longitude, precisao_metros, distancia_metros, dentro_raio, foto, observacao, turno_data)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $inserir->execute([
        $postoId,
        (int) usuario_atual()['id'],
        $ronda,
        $rondaConfiguracao['horario'],
        $latitude,
        $longitude,
        $precisao,
        (int) round($distancia),
        $dentro ? 1 : 0,
        $nomeArquivo,
        $observacao !== '' ? $observacao : null,
        turno_data(),
    ]);
} catch (PDOException $erro) {
    @unlink(UPLOAD_DIR . '/' . $nomeArquivo);

    if ($erro->getCode() === '23000') {
        flash('erro', 'Este ponto já foi registrado nesta ronda.');
        redirecionar($voltar);
    }

    throw $erro;
}

flash('ok', 'Ponto registrado: ' . $posto['nome'] . '.');
// Sem fixar a ronda na URL, a tela calcula a próxima ronda incompleta automaticamente.
redirecionar('../index.php?page=ronda');
