<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config.php';
require dirname(__DIR__) . '/lib/db.php';
require dirname(__DIR__) . '/lib/helpers.php';
require dirname(__DIR__) . '/lib/auth.php';

exigir_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    flash('erro', 'Sessão expirada ou requisição inválida. Tente novamente.');
    redirecionar('../index.php?page=historico');
}

$registroId = (int) ($_POST['registro_id'] ?? 0);
if ($registroId <= 0) {
    flash('erro', 'Registro inválido.');
    redirecionar('../index.php?page=historico');
}

$buscar = db()->prepare('SELECT id, foto FROM registros WHERE id = ? LIMIT 1');
$buscar->execute([$registroId]);
$registro = $buscar->fetch();

if (!$registro) {
    flash('erro', 'Registro não encontrado.');
    redirecionar('../index.php?page=historico');
}

$excluir = db()->prepare('DELETE FROM registros WHERE id = ?');
$excluir->execute([$registroId]);

if (!empty($registro['foto'])) {
    $caminhoFoto = UPLOAD_DIR . '/' . basename((string) $registro['foto']);
    if (is_file($caminhoFoto)) {
        @unlink($caminhoFoto);
    }
}

flash('ok', 'Registro excluído com sucesso.');
redirecionar('../index.php?page=historico');
