<?php
declare(strict_types=1);

require __DIR__ . '/layout.php';

$usuario = usuario_atual();
$rondaSolicitada = isset($_GET['ronda']) ? max(1, min(3, (int) $_GET['ronda'])) : null;
$turno = turno_data();

$postos = db()->query('SELECT * FROM postos WHERE ativo = 1 ORDER BY ordem, nome')->fetchAll();

$ehAdmin = eh_admin();
if ($ehAdmin) {
    $stmt = db()->prepare('SELECT r.posto_id, r.ronda, r.criado_em, u.nome AS vigia_nome FROM registros r JOIN usuarios u ON u.id = r.usuario_id WHERE r.turno_data = ?');
    $stmt->execute([$turno]);
} else {
    $stmt = db()->prepare('SELECT posto_id, ronda, criado_em, NULL AS vigia_nome FROM registros WHERE usuario_id = ? AND turno_data = ?');
    $stmt->execute([$usuario['id'], $turno]);
}

$registrados = [];
$registradosPor = [];
foreach ($stmt->fetchAll() as $linha) {
    $chave = $linha['ronda'] . ':' . $linha['posto_id'];
    $registrados[$chave] = $linha['criado_em'];
    if (!empty($linha['vigia_nome'])) {
        $registradosPor[$chave] = $linha['vigia_nome'];
    }
}

$porRonda = [1 => 0, 2 => 0, 3 => 0];
foreach (array_keys($registrados) as $chave) {
    $partes = explode(':', (string) $chave);
    $numRonda = (int) $partes[0];
    if (isset($porRonda[$numRonda])) {
        $porRonda[$numRonda]++;
    }
}

// Sem uma ronda informada pela URL, segue automaticamente para a primeira ronda incompleta.
// Assim, ao concluir 00:00, a tela abre 02:00; depois, 04:00.
if ($rondaSolicitada !== null) {
    $rondaSelecionada = $rondaSolicitada;
} elseif (!$postos) {
    $rondaSelecionada = ronda_atual();
} else {
    $rondaSelecionada = ronda_atual();
    foreach (RONDAS as $numero => $horario) {
        if ($porRonda[$numero] < count($postos)) {
            $rondaSelecionada = $numero;
            break;
        }
    }
}

$feitos = array_sum($porRonda);
$total = count($postos) * 3;
$pct = $total > 0 ? (int) round($feitos / $total * 100) : 0;

if ($total === 0) {
    $mensagem = 'Nenhum posto cadastrado ainda.';
} elseif ($feitos >= $total) {
    $mensagem = 'Todas as rondas da noite foram concluídas.';
} else {
    $faltam = $total - $feitos;
    $mensagem = 'Faltam ' . $faltam . ' ' . ($faltam === 1 ? 'ponto' : 'pontos') . ' para fechar as rondas da noite.';
}

layout_inicio('Ronda de hoje', date('d/m'));
?>
<section class="progresso">
  <div class="progresso-circulo" aria-label="<?= $feitos ?> de <?= $total ?> pontos concluídos">
    <svg viewBox="0 0 112 112" aria-hidden="true">
      <circle class="progresso-trilha" cx="56" cy="56" r="46"></circle>
      <circle class="progresso-valor" cx="56" cy="56" r="46" style="stroke-dashoffset: <?= 2 * M_PI * 46 * (1 - ($total > 0 ? $feitos / $total : 0)) ?>"></circle>
    </svg>
    <div class="progresso-centro">
      <span class="big"><?= $feitos ?></span>
      <span>de <?= $total ?></span>
    </div>
  </div>
  <div class="progresso-copy">
    <p class="progresso-kicker">Progresso do turno</p>
    <h2><?= $feitos === 0 ? 'Ronda não iniciada' : ($feitos >= $total && $total > 0 ? 'Ronda completa' : 'Ronda em andamento') ?></h2>
    <p class="muted"><?= e($mensagem) ?></p>
  </div>
</section>

<div class="secao-topo">
  <h2>Rondas da noite</h2>
  <span class="muted">avança automaticamente</span>
</div>

<div class="rondas">
  <?php foreach (RONDAS as $numero => $horario): ?>
    <a class="ronda <?= $numero === $rondaSelecionada ? 'ativa' : '' ?>" href="index.php?page=ronda&ronda=<?= $numero ?>">
      <strong><?= $horario ?></strong>
      <span><?= $porRonda[$numero] ?> feitos</span>
      <em><?= $numero === ronda_atual() ? 'agora' : '' ?></em>
    </a>
  <?php endforeach; ?>
</div>

<div class="secao-topo">
  <h2>Postos · ronda <?= RONDAS[$rondaSelecionada] ?></h2>
  <span class="muted">em ordem</span>
</div>

<?php if (!$postos): ?>
  <div class="vazio">
    <h3>Nenhum posto cadastrado</h3>
    <p class="muted">
      <?= eh_admin() ? 'Cadastre os postos na aba Postos.' : 'Peça ao administrador para cadastrar os postos da ronda.' ?>
    </p>
    <?php if (eh_admin()): ?>
      <a class="btn btn-primario" href="index.php?page=postos">Cadastrar postos</a>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="lista">
    <?php foreach ($postos as $indice => $posto): ?>
      <?php $concluidoEm = $registrados[$rondaSelecionada . ':' . $posto['id']] ?? null; ?>
      <article class="posto <?= $concluidoEm ? 'concluido' : '' ?>">
        <div class="posto-cabecalho">
          <span class="numero"><?= $concluidoEm ? '✓' : (int) ($indice + 1) ?></span>
          <div class="posto-info">
            <h3><?= e($posto['nome']) ?></h3>
            <?php if (!empty($posto['endereco'])): ?>
              <p class="muted pequeno"><?= e($posto['endereco']) ?></p>
            <?php endif; ?>
            <p class="status <?= $concluidoEm ? 'ok' : '' ?>">
              <?= $concluidoEm ? 'Registrado ' . formatar_hora($concluidoEm) . (!empty($registradosPor[$rondaSelecionada . ':' . $posto['id']]) ? ' (' . e($registradosPor[$rondaSelecionada . ':' . $posto['id']]) . ')' : '') : 'Pendente' ?>
              <span class="muted pequeno">· raio <?= (int) $posto['raio_metros'] ?> m</span>
            </p>
          </div>
        </div>
        <?php if (!$concluidoEm): ?>
          <a class="posto-acao" href="index.php?page=registrar&posto=<?= (int) $posto['id'] ?>&ronda=<?= $rondaSelecionada ?>&camera=1">
            Registrar ponto
          </a>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php layout_fim('ronda'); ?>
