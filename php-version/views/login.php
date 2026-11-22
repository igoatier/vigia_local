<?php
declare(strict_types=1);

$flash = flash_pegar();
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#0b0e14">
  <title>Entrar · VigiaCheck</title>
  <link rel="stylesheet" href="assets/app.css">
</head>
<body>
<div class="app app-login">
  <header class="topo topo-login">
    <div>
      <p class="eyebrow">Ronda noturna</p>
      <h1>VigiaCheck</h1>
    </div>
  </header>

  <?php if ($flash): ?>
    <div class="flash flash-<?= e($flash['tipo']) ?>"><?= e($flash['mensagem']) ?></div>
  <?php endif; ?>

  <main>
    <section class="card">
      <h2 class="card-titulo">Acesso do vigia</h2>
      <p class="muted">Entre com o e-mail e a senha cadastrados pelo administrador.</p>

      <form method="post" action="actions/conta.php" class="form">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="acao" value="login">

        <label class="campo">
          <span>E-mail</span>
          <input type="email" name="email" autocomplete="username" required>
        </label>

        <label class="campo">
          <span>Senha</span>
          <input type="password" name="senha" autocomplete="current-password" required>
        </label>

        <button class="btn btn-primario" type="submit">Entrar</button>
      </form>
    </section>

    <p class="rodape-nota muted">
      Esqueceu a senha? Peça ao administrador para redefinir o seu acesso.
    </p>
  </main>
</div>
</body>
</html>