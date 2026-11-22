<?php
declare(strict_types=1);

require __DIR__ . '/layout.php';

$ehAdmin = eh_admin();
$editarId = $ehAdmin ? (int) ($_GET['editar'] ?? 0) : 0;
$postos = db()->query('SELECT * FROM postos WHERE ativo = 1 ORDER BY ordem, nome')->fetchAll();

$editando = null;
if ($editarId > 0) {
    foreach ($postos as $posto) {
        if ((int) $posto['id'] === $editarId) {
            $editando = $posto;
            break;
        }
    }
}

layout_inicio('Postos', count($postos) . (count($postos) === 1 ? ' local' : ' locais'));
?>
<p class="muted pequeno">Cada posto guarda as coordenadas exigidas para o registro do ponto.</p>

<?php if ($ehAdmin): ?>
<section class="card">
  <h2 class="card-titulo"><?= $editando ? 'Editar posto' : 'Novo posto' ?></h2>

  <form method="post" action="actions/posto.php" class="form" id="form-posto">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="acao" value="salvar">
    <input type="hidden" name="id" value="<?= $editando ? (int) $editando['id'] : 0 ?>">

    <label class="campo">
      <span>Nome do posto</span>
      <input type="text" name="nome" maxlength="120" required value="<?= e($editando['nome'] ?? '') ?>" placeholder="Portaria principal">
    </label>

    <label class="campo">
      <span>Endereço / referência</span>
      <input type="text" name="endereco" maxlength="180" value="<?= e($editando['endereco'] ?? '') ?>" placeholder="Bloco B — fundos">
    </label>

    <button class="btn btn-secundario" type="button" id="usar-localizacao">Usar minha localização atual</button>

    <div class="dupla">
      <label class="campo">
        <span>Latitude</span>
        <input type="text" name="latitude" id="campo-lat" inputmode="decimal" required value="<?= e($editando['latitude'] ?? '') ?>">
      </label>
      <label class="campo">
        <span>Longitude</span>
        <input type="text" name="longitude" id="campo-lon" inputmode="decimal" required value="<?= e($editando['longitude'] ?? '') ?>">
      </label>
      <label class="campo">
        <span>Raio (m)</span>
        <input type="number" name="raio_metros" min="1" max="5000" value="<?= (int) ($editando['raio_metros'] ?? 1) ?>">
      </label>
      <label class="campo">
        <span>Ordem</span>
        <input type="number" name="ordem" min="1" max="999" value="<?= (int) ($editando['ordem'] ?? (count($postos) + 1)) ?>">
      </label>
    </div>

    <button class="btn btn-primario" type="submit"><?= $editando ? 'Salvar alterações' : 'Cadastrar posto' ?></button>
    <?php if ($editando): ?>
      <a class="link-discreto" href="index.php?page=postos">Cancelar edição</a>
    <?php endif; ?>
  </form>
</section>
<?php endif; ?>

<div class="secao-topo">
  <h2>Postos cadastrados</h2>
  <span class="muted"><?= count($postos) ?></span>
</div>

<?php if (!$postos): ?>
  <div class="vazio">
    <h3>Nenhum posto ainda</h3>
    <p class="muted"><?= $ehAdmin ? 'Preencha o formulário acima para começar.' : 'Aguarde o cadastro feito pelo administrador.' ?></p>
  </div>
<?php else: ?>
  <div class="lista">
    <?php foreach ($postos as $posto): ?>
      <article class="posto">
        <div class="posto-cabecalho">
          <span class="numero"><?= (int) $posto['ordem'] ?></span>
          <div class="posto-info">
            <h3><?= e($posto['nome']) ?></h3>
            <p class="muted pequeno mono"><?= e((string) $posto['latitude']) ?>, <?= e((string) $posto['longitude']) ?> · raio <?= (int) $posto['raio_metros'] ?> m</p>
          </div>
        </div>
        <?php if ($ehAdmin): ?>
          <div class="posto-acoes">
            <a class="posto-acao" href="index.php?page=postos&editar=<?= (int) $posto['id'] ?>">Editar</a>
            <form method="post" action="actions/posto.php" onsubmit="return confirm('Desativar este posto? Os registros anteriores continuarão salvos no histórico.');">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="acao" value="excluir">
              <input type="hidden" name="id" value="<?= (int) $posto['id'] ?>">
              <button class="posto-acao perigo" type="submit">Desativar</button>
            </form>
          </div>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<script>
const botaoLocalizacao = document.getElementById('usar-localizacao');
if (botaoLocalizacao) botaoLocalizacao.addEventListener('click', function () {
  const botao = this;
  if (!navigator.geolocation) {
    alert('Este dispositivo não permite capturar a localização.');
    return;
  }
  botao.disabled = true;
  botao.textContent = 'Obtendo localização…';
  navigator.geolocation.getCurrentPosition(function (pos) {
    document.getElementById('campo-lat').value = pos.coords.latitude.toFixed(7);
    document.getElementById('campo-lon').value = pos.coords.longitude.toFixed(7);
    botao.disabled = false;
    botao.textContent = 'Coordenadas preenchidas';
  }, function () {
    botao.disabled = false;
    botao.textContent = 'Usar minha localização atual';
    alert('Não foi possível obter a localização. Autorize o GPS e tente novamente.');
  }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 });
});
</script>

<?php layout_fim('postos'); ?>