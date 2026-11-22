<?php
declare(strict_types=1);

// Entrega a foto apenas para o dono do registro ou para administradores.
$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT foto, usuario_id FROM registros WHERE id = ?');
$stmt->execute([$id]);
$registro = $stmt->fetch();

if (!$registro) {
    http_response_code(404);
    exit('Foto não encontrada.');
}

$usuario = usuario_atual();
if (!$usuario || (!eh_admin() && (int) $registro['usuario_id'] !== (int) $usuario['id'])) {
    http_response_code(403);
    exit('Acesso negado.');
}

$caminho = UPLOAD_DIR . '/' . basename((string) $registro['foto']);
if (!is_file($caminho)) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}

$mime = 'image/jpeg';
$info = @getimagesize($caminho);
if (is_array($info) && !empty($info['mime'])) {
    $mime = $info['mime'];
} elseif (class_exists('finfo')) {
    $finfoMime = (new finfo(FILEINFO_MIME_TYPE))->file($caminho);
    if ($finfoMime) {
        $mime = $finfoMime;
    }
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($caminho));
header('Cache-Control: private, max-age=86400');
readfile($caminho);