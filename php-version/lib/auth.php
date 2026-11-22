<?php
declare(strict_types=1);

function usuario_atual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function esta_logado(): bool
{
    return !empty($_SESSION['usuario']);
}

function eh_admin(): bool
{
    return (usuario_atual()['papel'] ?? '') === 'admin';
}

function eh_moderador(): bool
{
    return (usuario_atual()['papel'] ?? '') === 'moderador';
}

function pode_gerenciar_equipe(): bool
{
    return eh_admin() || eh_moderador();
}

function exigir_login(): void
{
    if (!esta_logado()) {
        redirecionar('index.php?page=login');
    }
}

function exigir_admin(): void
{
    exigir_login();
    if (!eh_admin()) {
        flash('erro', 'Apenas administradores acessam esta área.');
        redirecionar('index.php');
    }
}

function entrar(string $email, string $senha): bool
{
    $stmt = db()->prepare('SELECT * FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1');
    $stmt->execute([mb_strtolower(trim($email))]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id' => (int) $usuario['id'],
        'nome' => $usuario['nome'],
        'email' => $usuario['email'],
        'papel' => $usuario['papel'],
    ];

    return true;
}

function sair(): void
{
    $_SESSION = [];
    session_destroy();
}

function exigir_gestor_equipe(): void
{
    exigir_login();
    if (!pode_gerenciar_equipe()) {
        flash('erro', 'Apenas administradores e moderadores acessam esta área.');
        redirecionar('index.php');
    }
}
