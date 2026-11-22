<?php
declare(strict_types=1);

require __DIR__ . '/layout.php';

$ehAdmin = eh_admin();
$ehModerador = eh_moderador();
$eu = (int) usuario_atual()['id'];
$usuarios = db()->query("SELECT id, nome, email, papel FROM usuarios ORDER BY FIELD(papel, 'admin', 'moderador', 'vigia'), nome")->fetchAll();

$rotulosPapel = [
    'admin' => 'Administrador',
    'moderador' => 'Moderador',
    'vigia' => 'Vigia',
];

layout_inicio('Equipe', count($usuarios) . (count($usuarios) === 1 ? ' pessoa' : ' pessoas'));
?>
<section class="card">
  <h2 class="card-titulo">Novo acesso</h2>
  <p class="muted pequeno">
    <?= $ehAdmin ? 'Cadastre vigias, moderadores ou administradores.' : 'Cadastre vigias e moderadores. Administradores não podem ser criados por moderadores.' ?>
  </p>

  <form method="post" action="actions/conta.php" class="form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="acao" value="criar_usuario">

    <label class="campo">
      <span>Nome</span>
      <input type="text" name="nome" maxlength="120" required>
    </label>

    <label class="campo">
      <span>E-mail</span>
      <input type="email" name="email" maxlength="160" required>
    </label>

    <label class="campo">
      <span>Senha inicial (mínimo 6 caracteres)</span>
      <input type="password" name="senha" minlength="6" required>
    </label>

    <label class="campo">
      <span>Perfil</span>
      <select name="papel">
        <option value="vigia">Vigia</option>
        <option value="moderador">Moderador</option>
        <?php if ($ehAdmin): ?><option value="admin">Administrador</option><?php endif; ?>
      </select>
    </label>

    <button class="btn btn-primario" type="submit">Criar acesso</button>
  </form>
</section>

<div class="secao-topo">
  <h2>Pessoas</h2>
  <span class="muted">perfil</span>
</div>

<div class="lista">
  <?php foreach ($usuarios as $usuario): ?>
    <?php
      $id = (int) $usuario['id'];
      $ehAlvoAdmin = $usuario['papel'] === 'admin';
      $podeEditar = $ehAdmin || (!$ehAlvoAdmin && $ehModerador);
      $classePapel = $usuario['papel'] === 'admin' ? 'selo-ok' : ($usuario['papel'] === 'moderador' ? 'selo-moderador' : 'selo-neutro');
    ?>
    <article class="pessoa pessoa-com-edicao">
      <div class="pessoa-resumo">
        <span class="avatar"><?= e(mb_strtoupper(mb_substr($usuario['nome'], 0, 1))) ?></span>
        <div class="pessoa-info">
          <h3>
            <?= e($usuario['nome']) ?>
            <?php if ($id === $eu): ?><em class="muted pequeno">você</em><?php endif; ?>
          </h3>
          <p class="muted pequeno"><?= e($usuario['email']) ?></p>
        </div>
        <span class="selo <?= $classePapel ?>"><?= e($rotulosPapel[$usuario['papel']] ?? $usuario['papel']) ?></span>
      </div>

      <?php if ($podeEditar): ?>
        <details class="pessoa-edicao">
          <summary>Editar dados</summary>
          <form method="post" action="actions/conta.php" class="form">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="acao" value="editar_usuario">
            <input type="hidden" name="id" value="<?= $id ?>">

            <label class="campo">
              <span>Nome</span>
              <input type="text" name="nome" maxlength="120" value="<?= e($usuario['nome']) ?>" required>
            </label>
            <label class="campo">
              <span>E-mail</span>
              <input type="email" name="email" maxlength="160" value="<?= e($usuario['email']) ?>" required>
            </label>
            <label class="campo">
              <span>Nova senha <small>(deixe em branco para manter)</small></span>
              <input type="password" name="senha" minlength="6" autocomplete="new-password">
            </label>
            <label class="campo">
              <span>Perfil</span>
              <select name="papel">
                <option value="vigia" <?= $usuario['papel'] === 'vigia' ? 'selected' : '' ?>>Vigia</option>
                <option value="moderador" <?= $usuario['papel'] === 'moderador' ? 'selected' : '' ?>>Moderador</option>
                <?php if ($ehAdmin): ?><option value="admin" <?= $usuario['papel'] === 'admin' ? 'selected' : '' ?>>Administrador</option><?php endif; ?>
              </select>
            </label>
            <button class="btn btn-secundario" type="submit">Salvar alterações</button>
          </form>
        </details>
      <?php elseif ($ehAlvoAdmin && $ehModerador): ?>
        <p class="pessoa-restricao">Conta administrativa — edição restrita ao administrador.</p>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
</div>

<?php layout_fim('equipe'); ?>
