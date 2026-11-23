<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config.php';
require dirname(__DIR__) . '/lib/db.php';
require dirname(__DIR__) . '/lib/helpers.php';
require dirname(__DIR__) . '/lib/auth.php';

header('Content-Type: application/json; charset=utf-8');

function responder_json(bool $ok, string $status, string $mensagem = '', int $codigo = 200, array $extra = []): never
{
    http_response_code($codigo);
    echo json_encode(array_merge([
        'ok' => $ok,
        'status' => $status,
        'mensagem' => $mensagem,
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function garantir_estrutura_sync(): void
{
    $pdo = db();
    $coluna = $pdo->query("SHOW COLUMNS FROM registros LIKE 'sync_id'")->fetch();
    if (!$coluna) {
        $pdo->exec('ALTER TABLE registros ADD COLUMN sync_id CHAR(36) NULL AFTER id');
    }

    $indice = $pdo->query("SHOW INDEX FROM registros WHERE Key_name = 'uk_registros_sync_id'")->fetch();
    if (!$indice) {
        $pdo->exec('ALTER TABLE registros ADD UNIQUE KEY uk_registros_sync_id (sync_id)');
    }
}

$nomeArquivo = null;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responder_json(false, 'metodo_invalido', 'Método não permitido.', 405);
    }
    if (!esta_logado()) {
        responder_json(false, 'sessao_expirada', 'Faça login novamente para sincronizar os registros.', 401);
    }
    if (!csrf_valido()) {
        responder_json(false, 'csrf_invalido', 'Sessão expirada. Abra o sistema novamente.', 419);
    }

    $syncId = trim((string) ($_POST['sync_id'] ?? ''));
    if (!preg_match('/^[A-Za-z0-9-]{8,64}$/', $syncId)) {
        responder_json(false, 'sync_id_invalido', 'Identificador do registro inválido.', 422);
    }

    garantir_estrutura_sync();
    $jaExiste = db()->prepare('SELECT id FROM registros WHERE sync_id = ? LIMIT 1');
    $jaExiste->execute([$syncId]);
    if ($jaExiste->fetch()) {
        responder_json(true, 'ja_sincronizado', '', 200, ['sync_id' => $syncId]);
    }

    $postoId = (int) ($_POST['posto_id'] ?? 0);
    $ronda = (int) ($_POST['ronda'] ?? 0);
    $rondaConfiguracao = ronda_configuracao($ronda);
    if (!$rondaConfiguracao) {
        responder_json(false, 'ronda_invalida', 'O horário da ronda não existe ou está desativado.', 422);
    }
    $latitude = (float) str_replace(',', '.', (string) ($_POST['latitude'] ?? ''));
    $longitude = (float) str_replace(',', '.', (string) ($_POST['longitude'] ?? ''));
    $precisao = max(0, (int) ($_POST['precisao'] ?? 0));
    $observacao = trim((string) ($_POST['observacao'] ?? ''));

    if ($latitude === 0.0 && $longitude === 0.0) {
        responder_json(false, 'gps_invalido', 'A localização do ponto não foi capturada.', 422);
    }
    if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
        responder_json(false, 'coordenadas_invalidas', 'As coordenadas informadas são inválidas.', 422);
    }

    $postoStmt = db()->prepare('SELECT * FROM postos WHERE id = ? AND ativo = 1 LIMIT 1');
    $postoStmt->execute([$postoId]);
    $posto = $postoStmt->fetch();
    if (!$posto) {
        responder_json(false, 'posto_invalido', 'O posto não existe ou está inativo.', 422);
    }

    $arquivo = $_FILES['foto'] ?? null;
    if (!$arquivo || $arquivo['error'] !== UPLOAD_ERR_OK) {
        responder_json(false, 'foto_ausente', 'A foto do ponto não foi recebida.', 422);
    }
    if ((int) $arquivo['size'] > MAX_UPLOAD_BYTES) {
        responder_json(false, 'foto_grande', 'A foto ultrapassou o limite permitido.', 422);
    }

    $extensoes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
    if (!isset($extensoes[$mime])) {
        responder_json(false, 'foto_invalida', 'Envie uma imagem JPG, PNG ou WEBP.', 422);
    }

    $distancia = distancia_metros($latitude, $longitude, (float) $posto['latitude'], (float) $posto['longitude']);
    $dentro = $distancia <= (int) $posto['raio_metros'];

    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0775, true) && !is_dir(UPLOAD_DIR)) {
        responder_json(false, 'pasta_upload', 'A pasta de imagens não está disponível.', 500);
    }

    $nomeArquivo = bin2hex(random_bytes(12)) . '.' . $extensoes[$mime];
    if (!move_uploaded_file($arquivo['tmp_name'], UPLOAD_DIR . '/' . $nomeArquivo)) {
        responder_json(false, 'upload_falhou', 'Não foi possível salvar a foto.', 500);
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $inserir = $pdo->prepare(
            'INSERT INTO registros (sync_id, posto_id, usuario_id, ronda, ronda_horario, latitude, longitude, precisao_metros, distancia_metros, dentro_raio, foto, observacao, turno_data)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $inserir->execute([
            $syncId,
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
        $pdo->commit();
    } catch (Throwable $erro) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $erro;
    }

    responder_json(true, 'sincronizado', '', 200, ['sync_id' => $syncId]);
} catch (PDOException $erro) {
    if ($nomeArquivo) @unlink(UPLOAD_DIR . '/' . $nomeArquivo);
    if ($erro->getCode() === '23000') {
        responder_json(true, 'ja_sincronizado', '', 200, ['sync_id' => $syncId ?? '']);
    }
    error_log('VigiaCheck sync: falha de banco; código ' . $erro->getCode());
    responder_json(false, 'banco_indisponivel', 'O servidor não conseguiu gravar o registro agora.', 503);
} catch (Throwable $erro) {
    if ($nomeArquivo) @unlink(UPLOAD_DIR . '/' . $nomeArquivo);
    error_log('VigiaCheck sync: erro de processamento; ' . $erro->getMessage());
    responder_json(false, 'erro_processamento', 'Não foi possível processar o registro.', 422);
}
