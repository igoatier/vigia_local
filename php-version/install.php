<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/helpers.php';

$erro = '';
$concluido = false;

try {
    $total = (int) db()->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
} catch (Throwable $excecao) {
    $total = null;
    $erro = 'Tabelas não encontradas. Importe o arquivo schema.sql no MySQL antes de continuar.';
}

if ($total === 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $senha = (string) ($_POST['senha'] ?? '');

    if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($senha) < 6) {
        $erro = 'Informe nome, e-mail válido e senha com pelo menos 6 caracteres.';
    } else {
        $inserir = db()->prepare('INSERT INTO usuarios (nome, email, senha_hash, papel) VALUES (?, ?, ?, ?)');
        $inserir->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), 'admin']);
        $concluido = true;
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Instalação · VigiaCheck</title>
  <link rel="stylesheet" href="assets/app.css">
</head>
<body>
<div class="app app-login">
  <header class="topo topo-login">
    <div>
      <p class="eyebrow">Instalação</p>
      <h1>VigiaCheck</h1>
    </div>
  </header>

  <main>
    <?php if ($erro): ?>
      <div class="flash flash-erro"><?= e($erro) ?></div>
    <?php endif; ?>

    <?php if ($concluido || ($total !== null && $total > 0)): ?>
      <section class="card">
        <h2 class="card-titulo">Instalação concluída</h2>
        <p class="muted pequeno">O administrador já existe. Apague o arquivo <strong>install.php</strong> do servidor e entre pelo login.</p>
        <a class="btn btn-primario" href="index.php?page=login">Ir para o login</a>
      </section>
    <?php elseif ($total === 0): ?>
      <section class="card">
        <h2 class="card-titulo">Criar administrador</h2>
        <p class="muted pequeno">Esta conta gerencia postos, vigias e vê todos os registros.</p>

        <form method="post" action="" class="form">
          <label class="campo">
            <span>Nome</span>
            <input type="text" name="nome" required maxlength="120">
          </label>
          <label class="campo">
            <span>E-mail</span>
            <input type="email" name="email" required maxlength="160">
          </label>
          <label class="campo">
            <span>Senha (mínimo 6 caracteres)</span>
            <input type="password" name="senha" required minlength="6">
          </label>
          <button class="btn btn-primario" type="submit">Criar administrador</button>
        </form>
      </section>
    <?php endif; ?>
  </main>
</div>
</body>
</html>