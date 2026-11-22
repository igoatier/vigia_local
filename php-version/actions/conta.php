<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config.php';
require dirname(__DIR__) . '/lib/db.php';
require dirname(__DIR__) . '/lib/helpers.php';
require dirname(__DIR__) . '/lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    flash('erro', 'Sessão expirada. Tente novamente.');
    redirecionar('../index.php?page=login');
}

$acao = (string) ($_POST['acao'] ?? '');

if ($acao === 'login') {
    if (entrar((string) ($_POST['email'] ?? ''), (string) ($_POST['senha'] ?? ''))) {
        redirecionar('../index.php');
    }
    flash('erro', 'E-mail ou senha inválidos.');
    redirecionar('../index.php?page=login');
}

exigir_gestor_equipe();
$ehAdmin = eh_admin();
$eu = (int) usuario_atual()['id'];

function voltar_equipe(): never
{
    redirecionar('../index.php?page=equipe');
}

function papel_permitido(string $papel, bool $ehAdmin): bool
{
    return $ehAdmin
        ? in_array($papel, ['admin', 'moderador', 'vigia'], true)
        : in_array($papel, ['moderador', 'vigia'], true);
}

function obter_usuario_conta(int $id): ?array
{
    $stmt = db()->prepare('SELECT id, nome, email, papel FROM usuarios WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $usuario = $stmt->fetch();
    return $usuario ?: null;
}

function moderador_pode_alterar(?array $usuario): bool
{
    return $usuario !== null && (eh_admin() || $usuario['papel'] !== 'admin');
}

if ($acao === 'criar_usuario') {
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $senha = (string) ($_POST['senha'] ?? '');
    $papel = (string) ($_POST['papel'] ?? 'vigia');

    if (!papel_permitido($papel, $ehAdmin)) {
        flash('erro', 'Moderadores só podem criar perfis de vigia ou moderador.');
        voltar_equipe();
    }
    if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($senha) < 6) {
        flash('erro', 'Informe nome, e-mail válido e senha com pelo menos 6 caracteres.');
        voltar_equipe();
    }

    $existe = db()->prepare('SELECT id FROM usuarios WHERE email = ?');
    $existe->execute([$email]);
    if ($existe->fetch()) {
        flash('erro', 'Já existe uma conta com este e-mail.');
        voltar_equipe();
    }

    $inserir = db()->prepare('INSERT INTO usuarios (nome, email, senha_hash, papel) VALUES (?, ?, ?, ?)');
    $inserir->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), $papel]);
    flash('ok', 'Acesso criado para ' . $nome . '.');
    voltar_equipe();
}

if ($acao === 'editar_usuario') {
    $id = (int) ($_POST['id'] ?? 0);
    $alvo = obter_usuario_conta($id);
    if (!moderador_pode_alterar($alvo)) {
        flash('erro', 'Moderadores não podem alterar contas administrativas.');
        voltar_equipe();
    }

    $nome = trim((string) ($_POST['nome'] ?? ''));
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $senha = (string) ($_POST['senha'] ?? '');
    $papel = (string) ($_POST['papel'] ?? $alvo['papel']);

    if ($id === $eu && $papel !== $alvo['papel']) {
        flash('erro', 'Você não pode alterar o próprio perfil.');
        voltar_equipe();
    }
    if (!papel_permitido($papel, $ehAdmin) || (!$ehAdmin && $papel === 'admin')) {
        flash('erro', 'Moderadores só podem usar os perfis de vigia e moderador.');
        voltar_equipe();
    }
    if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('erro', 'Informe um nome e e-mail válido.');
        voltar_equipe();
    }
    if ($senha !== '' && mb_strlen($senha) < 6) {
        flash('erro', 'A nova senha deve ter pelo menos 6 caracteres.');
        voltar_equipe();
    }

    $existe = db()->prepare('SELECT id FROM usuarios WHERE email = ? AND id <> ?');
    $existe->execute([$email, $id]);
    if ($existe->fetch()) {
        flash('erro', 'Já existe outra conta com este e-mail.');
        voltar_equipe();
    }

    if ($senha !== '') {
        $atualizar = db()->prepare('UPDATE usuarios SET nome = ?, email = ?, senha_hash = ?, papel = ? WHERE id = ?');
        $atualizar->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), $papel, $id]);
    } else {
        $atualizar = db()->prepare('UPDATE usuarios SET nome = ?, email = ?, papel = ? WHERE id = ?');
        $atualizar->execute([$nome, $email, $papel, $id]);
    }

    if ($id === $eu) {
        $_SESSION['usuario']['nome'] = $nome;
        $_SESSION['usuario']['email'] = $email;
    }
    flash('ok', 'Dados de ' . $nome . ' atualizados.');
    voltar_equipe();
}

// Compatibilidade com formulários antigos: somente o administrador pode alternar papéis desta forma.
if ($acao === 'papel') {
    if (!$ehAdmin) {
        flash('erro', 'Moderadores devem usar a edição de perfil, sem acesso a administradores.');
        voltar_equipe();
    }
    $id = (int) ($_POST['id'] ?? 0);
    if ($id === $eu) {
        flash('erro', 'Você não pode alterar o próprio papel.');
        voltar_equipe();
    }
    $alvo = obter_usuario_conta($id);
    if (!$alvo) {
        flash('erro', 'Usuário não encontrado.');
        voltar_equipe();
    }
    $novo = $alvo['papel'] === 'admin' ? 'vigia' : 'admin';
    db()->prepare('UPDATE usuarios SET papel = ? WHERE id = ?')->execute([$novo, $id]);
    flash('ok', 'Papel atualizado.');
    voltar_equipe();
}

if ($acao === 'redefinir_senha') {
    $id = (int) ($_POST['id'] ?? 0);
    $alvo = obter_usuario_conta($id);
    if (!moderador_pode_alterar($alvo)) {
        flash('erro', 'Moderadores não podem alterar contas administrativas.');
        voltar_equipe();
    }
    $novaSenha = (string) ($_POST['senha'] ?? '');
    if (mb_strlen($novaSenha) < 6) {
        flash('erro', 'A nova senha deve ter pelo menos 6 caracteres.');
        voltar_equipe();
    }
    db()->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?')->execute([password_hash($novaSenha, PASSWORD_DEFAULT), $id]);
    flash('ok', 'Senha alterada com sucesso.');
    voltar_equipe();
}

redirecionar('../index.php');
