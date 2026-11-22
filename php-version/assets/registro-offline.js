(() => {
  'use strict';

  const DB_NAME = 'vigia_check_offline';
  const DB_VERSION = 1;
  const STORE = 'registros_pendentes';
  const API_URL = 'actions/registro_sync.php';

  function abrirBanco() {
    return new Promise((resolve, reject) => {
      if (!('indexedDB' in window)) {
        reject(new Error('Armazenamento offline não disponível neste navegador.'));
        return;
      }

      const request = indexedDB.open(DB_NAME, DB_VERSION);
      request.onupgradeneeded = () => {
        const banco = request.result;
        if (!banco.objectStoreNames.contains(STORE)) {
          const store = banco.createObjectStore(STORE, { keyPath: 'sync_id' });
          store.createIndex('status', 'status', { unique: false });
          store.createIndex('criado_em', 'criado_em', { unique: false });
        }
      };
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error || new Error('Não foi possível abrir a fila offline.'));
    });
  }

  async function executar(transacao) {
    const banco = await abrirBanco();
    return new Promise((resolve, reject) => {
      const tx = banco.transaction(STORE, 'readwrite');
      const resultado = transacao(tx.objectStore(STORE));
      tx.oncomplete = () => resolve(resultado);
      tx.onerror = () => reject(tx.error || new Error('Falha no armazenamento local.'));
      tx.onabort = () => reject(tx.error || new Error('Operação local cancelada.'));
    });
  }

  function todos() {
    return abrirBanco().then((banco) => new Promise((resolve, reject) => {
      const tx = banco.transaction(STORE, 'readonly');
      const request = tx.objectStore(STORE).getAll();
      request.onsuccess = () => resolve(request.result || []);
      request.onerror = () => reject(request.error);
    }));
  }

  function salvar(registro) {
    return executar((store) => store.put(registro));
  }

  function remover(syncId) {
    return executar((store) => store.delete(syncId));
  }

  function gerarId() {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
      return window.crypto.randomUUID();
    }
    return 'offline-' + Date.now() + '-' + Math.random().toString(16).slice(2);
  }

  function carregarImagem(arquivo) {
    return new Promise((resolve, reject) => {
      const imagem = new Image();
      const url = URL.createObjectURL(arquivo);
      imagem.onload = () => {
        URL.revokeObjectURL(url);
        resolve(imagem);
      };
      imagem.onerror = () => {
        URL.revokeObjectURL(url);
        reject(new Error('Não foi possível processar a foto.'));
      };
      imagem.src = url;
    });
  }

  async function reduzirImagem(arquivo) {
    const limite = 1600;
    const imagem = await carregarImagem(arquivo);
    const escala = Math.min(1, limite / Math.max(imagem.naturalWidth, imagem.naturalHeight));
    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(imagem.naturalWidth * escala));
    canvas.height = Math.max(1, Math.round(imagem.naturalHeight * escala));
    const contexto = canvas.getContext('2d', { alpha: false });
    contexto.drawImage(imagem, 0, 0, canvas.width, canvas.height);

    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.86));
    return blob || arquivo;
  }

  function atualizarIndicador() {
    const indicador = document.getElementById('sync-pendentes');
    if (!indicador) return;
    todos().then((lista) => {
      const quantidade = lista.length;
      indicador.hidden = quantidade === 0;
      indicador.textContent = quantidade === 1
        ? '1 registro aguardando sincronização'
        : quantidade + ' registros aguardando sincronização';
    }).catch(() => {
      indicador.hidden = true;
    });
  }

  function solicitarSincronizacaoEmSegundoPlano() {
    if (!('serviceWorker' in navigator)) return;
    navigator.serviceWorker.ready.then((registro) => {
      if (registro.sync) return registro.sync.register('vigia-registros');
      return null;
    }).catch(() => {});
  }

  async function enviar(registro) {
    const dados = new FormData();
    dados.append('sync_id', registro.sync_id);
    dados.append('posto_id', registro.posto_id);
    dados.append('ronda', registro.ronda);
    dados.append('latitude', registro.latitude);
    dados.append('longitude', registro.longitude);
    dados.append('precisao', registro.precisao);
    dados.append('observacao', registro.observacao || '');
    dados.append('csrf', registro.csrf);
    dados.append('foto', registro.foto, 'registro.jpg');

    const resposta = await fetch(API_URL, {
      method: 'POST',
      body: dados,
      credentials: 'same-origin',
      headers: { Accept: 'application/json' }
    });

    let resultado;
    try {
      resultado = await resposta.json();
    } catch (erro) {
      throw new Error('O servidor não respondeu em formato válido.');
    }

    if (!resposta.ok || !resultado.ok) {
      throw new Error(resultado.mensagem || 'Não foi possível sincronizar o registro.');
    }
    return resultado;
  }

  async function sincronizarRegistro(registro) {
    try {
      registro.status = 'enviando';
      registro.tentativas = (registro.tentativas || 0) + 1;
      await salvar(registro);
      await enviar(registro);
      await remover(registro.sync_id);
      atualizarIndicador();
      return true;
    } catch (erro) {
      registro.status = 'erro';
      registro.ultimo_erro = erro.message;
      await salvar(registro).catch(() => {});
      atualizarIndicador();
      return false;
    }
  }

  async function sincronizarPendentes() {
    if (!navigator.onLine) {
      atualizarIndicador();
      return;
    }
    let lista;
    try {
      lista = await todos();
    } catch (erro) {
      return;
    }
    for (const registro of lista.sort((a, b) => String(a.criado_em).localeCompare(String(b.criado_em)))) {
      await sincronizarRegistro(registro);
    }
  }

  function envioTradicional(formulario) {
    HTMLFormElement.prototype.submit.call(formulario);
  }

  function vincularFormulario(formulario) {
    if (!formulario || formulario.dataset.offlineVinculado === '1') return;
    formulario.dataset.offlineVinculado = '1';

    formulario.addEventListener('submit', async (evento) => {
      evento.preventDefault();
      const arquivo = formulario.elements.foto && formulario.elements.foto.files[0];
      const botao = document.getElementById('btn-enviar');

      if (!arquivo || !window.indexedDB) {
        envioTradicional(formulario);
        return;
      }

      if (botao) {
        botao.disabled = true;
        botao.textContent = 'Preparando registro…';
      }

      try {
        const foto = await reduzirImagem(arquivo);
        const registro = {
          sync_id: gerarId(),
          posto_id: formulario.elements.posto_id.value,
          ronda: formulario.elements.ronda.value,
          latitude: formulario.elements.latitude.value,
          longitude: formulario.elements.longitude.value,
          precisao: formulario.elements.precisao.value,
          observacao: formulario.elements.observacao.value,
          csrf: formulario.elements.csrf.value,
          foto,
          criado_em: new Date().toISOString(),
          status: 'pendente',
          tentativas: 0,
          ultimo_erro: null
        };

        await salvar(registro);
        atualizarIndicador();
        solicitarSincronizacaoEmSegundoPlano();
        if (botao) botao.textContent = navigator.onLine ? 'Enviando…' : 'Salvo no aparelho';
        const sincronizado = await sincronizarRegistro(registro);

        if (sincronizado) {
          window.location.href = 'index.php?page=ronda';
          return;
        }

        if (botao) {
          botao.disabled = false;
          botao.textContent = 'Ponto salvo — aguardando conexão';
        }
        window.alert('Ponto salvo neste aparelho. Ele será enviado automaticamente quando a conexão voltar.');
      } catch (erro) {
        if (botao) {
          botao.disabled = false;
          botao.textContent = 'Confirmar registro';
        }
        window.alert('Não foi possível salvar o ponto offline: ' + erro.message);
      }
    });
  }

  window.VigiaOffline = {
    vincularFormulario,
    sincronizarPendentes,
    atualizarIndicador
  };

  window.addEventListener('online', sincronizarPendentes);
  window.addEventListener('online', atualizarIndicador);
  navigator.serviceWorker?.addEventListener('message', (evento) => {
    if (evento.data && evento.data.type === 'vigia-sync') sincronizarPendentes();
  });
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') sincronizarPendentes();
  });
  window.addEventListener('load', () => {
    atualizarIndicador();
    sincronizarPendentes();
  });
})();
