<?php
declare(strict_types=1);

require __DIR__ . '/layout.php';

$ehAdmin = eh_admin();
$vigiaFiltro = $ehAdmin ? (int) ($_GET['vigia'] ?? 0) : (int) usuario_atual()['id'];
$postoFiltro = (int) ($_GET['posto'] ?? 0);

$sql = 'SELECT r.*, u.nome AS vigia, p.nome AS posto_nome
        FROM registros r
        JOIN usuarios u ON u.id = r.usuario_id
        JOIN postos p ON p.id = r.posto_id
        WHERE 1 = 1';
$params = [];

if (!$ehAdmin || $vigiaFiltro > 0) {
    $sql .= ' AND r.usuario_id = ?';
    $params[] = $vigiaFiltro;
}
if ($postoFiltro > 0) {
    $sql .= ' AND r.posto_id = ?';
    $params[] = $postoFiltro;
}
$sql .= ' ORDER BY r.criado_em DESC LIMIT 100';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll();

$vigias = $ehAdmin ? db()->query('SELECT id, nome FROM usuarios ORDER BY nome')->fetchAll() : [];
$postos = db()->query('SELECT id, nome FROM postos ORDER BY ordem, nome')->fetchAll();

$grupos = [];
foreach ($registros as $registro) {
    $grupos[rotulo_dia($registro['criado_em'])][] = $registro;
}

layout_inicio('Histórico', count($registros) . (count($registros) === 1 ? ' registro' : ' registros'));
?>
<?php if ($ehAdmin): ?>
  <form method="get" action="index.php" class="filtros">
    <input type="hidden" name="page" value="historico">
    <label class="campo">
      <span>Vigia</span>
      <select name="vigia" onchange="this.form.submit()">
        <option value="0">Todos os vigias</option>
        <?php foreach ($vigias as $vigia): ?>
          <option value="<?= (int) $vigia['id'] ?>" <?= $vigiaFiltro === (int) $vigia['id'] ? 'selected' : '' ?>>
            <?= e($vigia['nome']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="campo">
      <span>Posto</span>
      <select name="posto" onchange="this.form.submit()">
        <option value="0">Todos os postos</option>
        <?php foreach ($postos as $posto): ?>
          <option value="<?= (int) $posto['id'] ?>" <?= $postoFiltro === (int) $posto['id'] ? 'selected' : '' ?>>
            <?= e($posto['nome']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
  </form>
<?php endif; ?>

<?php if (!$registros): ?>
  <div class="vazio">
    <h3>Nenhum ponto registrado</h3>
    <p class="muted">Os registros aparecem aqui com foto, hora e coordenadas.</p>
  </div>
<?php else: ?>
  <?php foreach ($grupos as $dia => $lista): ?>
    <section class="grupo">
      <div class="secao-topo">
        <h2><?= e($dia) ?></h2>
        <span class="muted"><?= data_br($lista[0]['criado_em']) ?></span>
      </div>

      <div class="lista">
        <?php foreach ($lista as $registro): ?>
          <?php
            $rondaHorario = !empty($registro['ronda_horario'])
                ? $registro['ronda_horario']
                : (RONDAS[(int) ($registro['ronda'] ?? 0)] ?? RONDAS[ronda_atual($registro['criado_em'])]);
          ?>
          <article class="historico-item">
            <a href="index.php?page=foto&id=<?= (int) $registro['id'] ?>" target="_blank" rel="noopener noreferrer" title="Ver foto original">
              <img class="thumb" src="index.php?page=foto&id=<?= (int) $registro['id'] ?>" alt="<?= e($registro['posto_nome']) ?>" loading="lazy">
            </a>
            <div class="historico-corpo">
              <div class="historico-linha">
                <h3><?= e($registro['posto_nome']) ?></h3>
                <span class="mono hora"><?= formatar_hora($registro['criado_em']) ?></span>
              </div>
              <p class="muted pequeno mono">
                <a class="link-coordenadas" href="https://www.google.com/maps?q=<?= e((string)$registro['latitude']) ?>,<?= e((string)$registro['longitude']) ?>" target="_blank" rel="noopener noreferrer" title="Ver coordenadas no Google Maps">
                  <?= e((string) $registro['latitude']) ?>, <?= e((string) $registro['longitude']) ?>
                </a>
              </p>
              <div class="selos">
                <span class="selo <?= (int) $registro['dentro_raio'] === 1 ? 'selo-ok' : 'selo-alerta' ?>">
                  <?= (int) $registro['dentro_raio'] === 1 ? '✓ NO LOCAL' : '⚠ FORA DO RAIO' ?>
                </span>
                <span class="selo selo-neutro">RONDA <?= e($rondaHorario) ?></span>
                <?php if (!empty($registro['vigia'])): ?>
                  <span class="vigia-tag"><?= e(mb_strtoupper($registro['vigia'])) ?></span>
                <?php endif; ?>
                <?php if ($registro['distancia_metros'] !== null && $registro['distancia_metros'] !== ''): ?>
                  <a class="link-mapa" href="https://www.google.com/maps?q=<?= e((string)$registro['latitude']) ?>,<?= e((string)$registro['longitude']) ?>" target="_blank" rel="noopener noreferrer" title="Ver localização no Google Maps">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <?= formatar_distancia((float) $registro['distancia_metros']) ?> do posto
                  </a>
                <?php endif; ?>
              </div>
              <?php if (!empty($registro['observacao'])): ?>
                <p class="pequeno observacao"><?= e($registro['observacao']) ?></p>
              <?php endif; ?>
            </div>
            <?php if ($ehAdmin): ?>
              <form class="historico-excluir" method="post" action="actions/registro_excluir.php" onsubmit="return confirm('Excluir este registro permanentemente? A foto também será apagada.');">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="registro_id" value="<?= (int) $registro['id'] ?>">
                <button type="submit" title="Excluir registro" aria-label="Excluir registro">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v5"></path><path d="M14 11v5"></path></svg>
                </button>
              </form>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>
<?php endif; ?>

<?php layout_fim('historico'); ?>
