<?php
declare(strict_types=1);

function abas(): array
{
    $abas = [
        ['ronda', 'Ronda', 'index.php?page=ronda'],
        ['historico', 'Histórico', 'index.php?page=historico'],
        ['postos', 'Postos', 'index.php?page=postos'],
    ];
    if (esta_logado()) {
        $abas[] = ['alertas', 'Alertas', 'index.php?page=alertas'];
    }
    if (pode_gerenciar_equipe()) {
        $abas[] = ['equipe', 'Equipe', 'index.php?page=equipe'];
    }
    return $abas;
}

function layout_inicio(string $titulo, string $subtitulo = ''): void
{
    $flash = flash_pegar();
    ?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#0b0e14">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="VigiaCheck">
  <link rel="manifest" href="manifest.webmanifest">
  <link rel="icon" href="assets/icon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="assets/icon.svg">
  <title><?= e($titulo) ?> · VigiaCheck</title>
  <link rel="stylesheet" href="assets/app.css">
  <script src="assets/registro-offline.js" defer></script>
</head>
<body>
<div class="app">
  <header class="topo">
    <div>
      <p class="eyebrow">Ronda noturna</p>
      <h1><?= e($titulo) ?></h1>
    </div>
    <div class="topo-dir">
      <?php if ($subtitulo !== ''): ?>
        <span class="subtitulo"><?= e($subtitulo) ?></span>
      <?php endif; ?>
      <a class="btn-sair" href="index.php?page=sair">Sair</a>
    </div>
  </header>

  <?php if ($flash): ?>
    <div class="flash flash-<?= e($flash['tipo']) ?>"><?= e($flash['mensagem']) ?></div>
  <?php endif; ?>

  <div class="pwa-offline" id="pwa-offline" role="status" hidden>Você está offline. As telas serão atualizadas quando a conexão voltar.</div>
  <div class="sync-pendentes" id="sync-pendentes" role="status" hidden></div>
  <div class="pwa-install" id="pwa-install" hidden>
    <div><strong>Instale o VigiaCheck</strong><span>Acesse as rondas como um app no celular.</span></div>
    <button type="button" class="pwa-install-btn" id="pwa-install-btn">Instalar</button>
    <button type="button" class="pwa-close" id="pwa-close" aria-label="Fechar">×</button>
  </div>

  <main>
<?php
}

function layout_fim(string $ativo): void
{
    ?>
  </main>
</div>
<nav class="nav">
  <div class="nav-inner">
    <?php foreach (abas() as [$chave, $rotulo, $url]): ?>
      <a class="nav-item <?= $chave === $ativo ? 'ativo' : '' ?>" href="<?= e($url) ?>">
        <span class="dot"></span><?= e($rotulo) ?>
      </a>
    <?php endforeach; ?>
  </div>
</nav>
<script>
(() => {
  const offline = document.getElementById('pwa-offline');
  const install = document.getElementById('pwa-install');
  const installButton = document.getElementById('pwa-install-btn');
  const closeButton = document.getElementById('pwa-close');
  const chaveDispensado = 'vigia-pwa-install-dismissed';
  const chaveInstalado = 'vigia-pwa-installed';
  const chaveExibidoSessao = 'vigia-pwa-install-session-shown';
  let deferredPrompt = null;

  const estaInstalado = () => {
    const modoStandalone = window.matchMedia?.('(display-mode: standalone)').matches;
    const modoIos = window.navigator.standalone === true;
    return Boolean(modoStandalone || modoIos || localStorage.getItem(chaveInstalado) === '1');
  };

  const ocultarInstalacao = () => {
    if (install) install.hidden = true;
  };

  if (estaInstalado() || localStorage.getItem(chaveDispensado) === '1' || sessionStorage.getItem(chaveExibidoSessao) === '1') {
    ocultarInstalacao();
  }

  const atualizarConexao = () => { if (offline) offline.hidden = navigator.onLine; };
  window.addEventListener('online', atualizarConexao);
  window.addEventListener('offline', atualizarConexao);
  atualizarConexao();

  window.addEventListener('beforeinstallprompt', (event) => {
    if (estaInstalado() || localStorage.getItem(chaveDispensado) === '1' || sessionStorage.getItem(chaveExibidoSessao) === '1') {
      deferredPrompt = null;
      ocultarInstalacao();
      return;
    }
    event.preventDefault();
    deferredPrompt = event;
    sessionStorage.setItem(chaveExibidoSessao, '1');
    if (install) install.hidden = false;
  });

  installButton?.addEventListener('click', async () => {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    const escolha = await deferredPrompt.userChoice;
    deferredPrompt = null;
    if (escolha?.outcome === 'accepted') {
      localStorage.setItem(chaveInstalado, '1');
    }
    ocultarInstalacao();
  });

  closeButton?.addEventListener('click', () => {
    localStorage.setItem(chaveDispensado, '1');
    deferredPrompt = null;
    ocultarInstalacao();
  });

  window.addEventListener('appinstalled', () => {
    localStorage.setItem(chaveInstalado, '1');
    localStorage.removeItem(chaveDispensado);
    deferredPrompt = null;
    ocultarInstalacao();
  });

  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('./sw.js', { scope: './' }).catch(() => {}));
  }
})();
</script>
</body>
</html>
<?php
}
