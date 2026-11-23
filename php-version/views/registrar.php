<?php
declare(strict_types=1);

require __DIR__ . '/layout.php';

$postoId = (int) ($_GET['posto'] ?? 0);
$ronda = isset($_GET['ronda']) ? (int) $_GET['ronda'] : ronda_atual();
$rondaConfiguracao = ronda_configuracao($ronda);
if (!$rondaConfiguracao) {
    flash('erro', 'Horário de ronda inválido ou desativado.');
    redirecionar('index.php?page=ronda');
}

$stmt = db()->prepare('SELECT * FROM postos WHERE id = ? AND ativo = 1');
$stmt->execute([$postoId]);
$posto = $stmt->fetch();

if (!$posto) {
    flash('erro', 'Posto não encontrado.');
    redirecionar('index.php');
}

$abrirCamera = isset($_GET['camera']) && $_GET['camera'] === '1';
layout_inicio('Registrar ponto', 'Ronda ' . $rondaConfiguracao['horario']);
?>
<section class="card">
  <h2 class="card-titulo"><?= e($posto['nome']) ?></h2>
  <p class="muted pequeno"><?= e((string) $posto['endereco']) ?></p>

  <form method="post" action="actions/registro.php" enctype="multipart/form-data" class="form" id="form-registro" data-offline-registro="1">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="posto_id" value="<?= (int) $posto['id'] ?>">
    <input type="hidden" name="ronda" value="<?= $ronda ?>">
    <input type="hidden" name="latitude" id="campo-latitude">
    <input type="hidden" name="longitude" id="campo-longitude">
    <input type="hidden" name="precisao" id="campo-precisao">

    <label class="foto" id="foto-area">
      <input type="file" name="foto" id="campo-foto" accept="image/*" capture="environment" required>
      <span class="foto-texto" id="foto-texto">Abrir câmera</span>
      <img id="foto-preview" alt="Prévia da foto" hidden>
    </label>

    <div class="geo" id="geo-status">
      <span class="muted">Obtendo coordenadas…</span>
    </div>

    <label class="campo">
      <span>Observação (opcional)</span>
      <textarea name="observacao" rows="2" maxlength="500"></textarea>
    </label>

    <button class="btn btn-primario" type="submit" id="btn-enviar" disabled>Confirmar registro</button>
  </form>
</section>

<script>
(function () {
  const posto = { lat: <?= (float) $posto['latitude'] ?>, lon: <?= (float) $posto['longitude'] ?>, raio: <?= (int) $posto['raio_metros'] ?> };
  const abrirCamera = <?= $abrirCamera ? 'true' : 'false' ?>;
  const campoLat = document.getElementById('campo-latitude');
  const campoLon = document.getElementById('campo-longitude');
  const campoPrecisao = document.getElementById('campo-precisao');
  const status = document.getElementById('geo-status');
  const botao = document.getElementById('btn-enviar');
  const campoFoto = document.getElementById('campo-foto');
  const preview = document.getElementById('foto-preview');
  const textoFoto = document.getElementById('foto-texto');

  let temFoto = false;
  let temGeo = false;
  let watchId = null;
  let timerGeo = null;

  function atualizarBotao() {
    botao.disabled = !(temFoto && temGeo);
  }

  function distanciaMetros(lat1, lon1, lat2, lon2) {
    const R = 6371000;
    const rad = (g) => (g * Math.PI) / 180;
    const dLat = rad(lat2 - lat1);
    const dLon = rad(lon2 - lon1);
    const h = Math.sin(dLat / 2) ** 2 + Math.cos(rad(lat1)) * Math.cos(rad(lat2)) * Math.sin(dLon / 2) ** 2;
    return 2 * R * Math.asin(Math.sqrt(h));
  }

  function encerrarBusca() {
    if (watchId !== null) navigator.geolocation.clearWatch(watchId);
    watchId = null;
    if (timerGeo !== null) window.clearTimeout(timerGeo);
    timerGeo = null;
  }

  function mostrarPosicao(pos, fonte) {
    const latitude = Number(pos.coords.latitude);
    const longitude = Number(pos.coords.longitude);
    const precisao = Math.max(0, Number(pos.coords.accuracy) || 0);
    campoLat.value = latitude.toFixed(7);
    campoLon.value = longitude.toFixed(7);
    campoPrecisao.value = Math.round(precisao);

    const distancia = distanciaMetros(latitude, longitude, posto.lat, posto.lon);
    const dentro = distancia <= posto.raio;
    const distanciaTexto = distancia < 1000 ? Math.round(distancia) + ' m' : (distancia / 1000).toFixed(1).replace('.', ',') + ' km';
    const fonteTexto = fonte === 'aproximada' ? 'posição aproximada' : 'GPS de alta precisão';

    status.innerHTML =
      '<p class="mono">' + latitude.toFixed(5) + ', ' + longitude.toFixed(5) + '</p>' +
      '<p class="muted pequeno">Precisão ±' + Math.round(precisao) + ' m · ' + fonteTexto + ' · Posto a ' + distanciaTexto + '</p>' +
      '<p class="selo ' + (dentro ? 'selo-ok' : 'selo-alerta') + '">' +
      (dentro ? 'Dentro do raio do posto' : 'Fora do raio de ' + posto.raio + ' m') +
      '</p>';
    temGeo = true;
    atualizarBotao();
  }

  campoFoto.addEventListener('change', function () {
    const arquivo = campoFoto.files && campoFoto.files[0];
    if (!arquivo) return;
    temFoto = true;
    textoFoto.hidden = true;
    preview.src = URL.createObjectURL(arquivo);
    preview.hidden = false;
    atualizarBotao();
  });

  function abrirCameraAutomaticamente() {
    if (!abrirCamera || (campoFoto.files && campoFoto.files.length)) return;
    textoFoto.textContent = 'Abrindo câmera…';
    window.setTimeout(function () {
      try { campoFoto.click(); } catch (erro) { textoFoto.textContent = 'Toque aqui para abrir a câmera'; }
    }, 120);
  }

  function erroLocalizacao() {
    encerrarBusca();
    status.innerHTML =
      '<div style="display:flex;flex-direction:column;gap:6px;">' +
      '<span class="erro">Não foi possível obter a localização.</span>' +
      '<span class="muted pequeno">Ative o GPS e permita a localização precisa para este site.</span>' +
      '<button type="button" class="link-discreto" id="btn-retry-geo" style="align-self:flex-start;cursor:pointer;">Tentar novamente</button>' +
      '</div>';
    const btnRetry = document.getElementById('btn-retry-geo');
    if (btnRetry) btnRetry.addEventListener('click', obterLocalizacao);
  }

  function obterLocalizacao() {
    temGeo = false;
    atualizarBotao();
    encerrarBusca();
    status.innerHTML = '<span class="muted">Buscando a melhor posição do GPS…</span>';

    if (!navigator.geolocation) {
      status.innerHTML = '<span class="erro">Este dispositivo não permite capturar a localização.</span>';
      return;
    }

    let melhorPosicao = null;
    const receber = function (pos) {
      if (!melhorPosicao || (pos.coords.accuracy || Infinity) < (melhorPosicao.coords.accuracy || Infinity)) {
        melhorPosicao = pos;
        mostrarPosicao(pos, 'precisa');
      }
      if ((pos.coords.accuracy || Infinity) <= 60) encerrarBusca();
    };

    const fallback = function () {
      if (melhorPosicao) {
        mostrarPosicao(melhorPosicao, 'precisa');
        encerrarBusca();
        return;
      }
      navigator.geolocation.getCurrentPosition(function (pos) {
        mostrarPosicao(pos, 'aproximada');
        encerrarBusca();
      }, erroLocalizacao, { enableHighAccuracy: false, timeout: 12000, maximumAge: 180000 });
    };

    watchId = navigator.geolocation.watchPosition(receber, function () {
      window.setTimeout(fallback, 500);
    }, { enableHighAccuracy: true, timeout: 30000, maximumAge: 0 });
    timerGeo = window.setTimeout(fallback, 25000);
  }

  obterLocalizacao();
  window.addEventListener('load', abrirCameraAutomaticamente, { once: true });
  window.addEventListener('load', function () {
    window.VigiaOffline?.vincularFormulario(document.getElementById('form-registro'));
  }, { once: true });
})();
</script>

<?php layout_fim('ronda'); ?>
