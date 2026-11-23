<?php
declare(strict_types=1);

require __DIR__ . '/layout.php';
exigir_admin();
garantir_estrutura_rondas();
$rondas = rondas_todas();
$editarId = (int) ($_GET['editar'] ?? 0);
$editando = $editarId > 0 ? ($rondas[$editarId] ?? null) : null;

layout_inicio('Horários das rondas', 'Somente administrador');
?>
<section class="card">
  <h2 class="card-titulo"><?= $editando ? 'Editar horário' : 'Novo horário' ?></h2>
  <p class="muted pequeno">Cadastre quantas rondas forem necessárias. A tolerância padrão é de 35 minutos e pode ser ajustada por horário.</p>
  <form method="post" action="actions/horario.php" class="form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="acao" value="salvar">
    <input type="hidden" name="id" value="<?= (int) ($editando['id'] ?? 0) ?>">
    <label class="campo">
      <span>Nome da ronda</span>
      <input type="text" name="nome" maxlength="100" required value="<?= e($editando['nome'] ?? '') ?>" placeholder="Ronda da madrugada">
    </label>
    <div class="dupla">
      <label class="campo">
        <span>Horário</span>
        <input type="time" name="horario" required value="<?= e($editando['horario'] ?? '') ?>">
      </label>
      <label class="campo">
        <span>Ordem</span>
        <input type="number" name="ordem" min="1" max="999" required value="<?= (int) ($editando['ordem'] ?? (count($rondas) + 1)) ?>">
      </label>
    </div>
    <label class="campo">
      <span>Tolerância após o horário (minutos)</span>
      <input type="number" name="tolerancia_minutos" min="0" max="240" required value="<?= (int) ($editando['tolerancia_minutos'] ?? 35) ?>">
    </label>
    <?php if ($editando): ?>
      <label class="campo">
        <span>Status</span>
        <select name="ativo">
          <option value="1" <?= (int) $editando['ativo'] === 1 ? 'selected' : '' ?>>Ativa</option>
          <option value="0" <?= (int) $editando['ativo'] === 0 ? 'selected' : '' ?>>Desativada</option>
        </select>
      </label>
    <?php endif; ?>
    <button class="btn btn-primario" type="submit"><?= $editando ? 'Salvar alterações' : 'Criar horário' ?></button>
    <?php if ($editando): ?><a class="link-discreto" href="index.php?page=horarios">Cancelar edição</a><?php endif; ?>
  </form>
</section>

<div class="secao-topo">
  <h2>Horários cadastrados</h2>
  <span class="muted"><?= count($rondas) ?></span>
</div>
<div class="lista">
  <?php foreach ($rondas as $ronda): ?>
    <article class="card horario-item <?= (int) $ronda['ativo'] === 0 ? 'horario-inativo' : '' ?>">
      <div class="horario-resumo">
        <div>
          <p class="progresso-kicker">Ordem <?= (int) $ronda['ordem'] ?></p>
          <h3><?= e($ronda['nome']) ?></h3>
          <p class="muted pequeno">Horário <?= e($ronda['horario']) ?> · tolerância <?= (int) $ronda['tolerancia_minutos'] ?> min</p>
        </div>
        <span class="selo <?= (int) $ronda['ativo'] === 1 ? 'selo-ok' : 'selo-neutro' ?>">
          <?= (int) $ronda['ativo'] === 1 ? 'Ativa' : 'Desativada' ?>
        </span>
      </div>
      <a class="posto-acao" href="index.php?page=horarios&editar=<?= (int) $ronda['id'] ?>">Editar horário</a>
    </article>
  <?php endforeach; ?>
</div>
<?php layout_fim('horarios'); ?>
