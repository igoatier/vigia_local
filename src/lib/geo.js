export function distanciaMetros(a, b) {
    if (!a || !b) return null;
    const R = 6371000;
    const rad = (d) => (d * Math.PI) / 180;
    const dLat = rad(b.latitude - a.latitude);
    const dLon = rad(b.longitude - a.longitude);
    const h =
      Math.sin(dLat / 2) ** 2 +
      Math.cos(rad(a.latitude)) * Math.cos(rad(b.latitude)) * Math.sin(dLon / 2) ** 2;
    return 2 * R * Math.asin(Math.sqrt(h));
  }
  
  export function formatarDistancia(m) {
    if (typeof m !== 'number' || Number.isNaN(m)) return '—';
    if (m < 1000) return `${Math.round(m)} m`;
    return `${(m / 1000).toFixed(1).replace('.', ',')} km`;
  }
  
  function mensagemGeo(erro) {
    if (!erro) return 'Não foi possível obter a localização.';
    if (erro.code === 1) return 'Permissão de localização negada. Libere o acesso nas configurações do navegador.';
    if (erro.code === 2) return 'Sinal de GPS indisponível neste momento.';
    if (erro.code === 3) return 'O GPS demorou demais para responder. Tente novamente.';
    return 'Não foi possível obter a localização.';
  }
  
  export function obterPosicao() {
    return new Promise((resolve, reject) => {
      if (!navigator.geolocation) {
        reject(new Error('Este dispositivo não permite capturar a localização.'));
        return;
      }
      navigator.geolocation.getCurrentPosition(
        (pos) =>
          resolve({
            latitude: pos.coords.latitude,
            longitude: pos.coords.longitude,
            precisao: pos.coords.accuracy,
          }),
        (erro) => reject(new Error(mensagemGeo(erro))),
        { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 }
      );
    });
  }
  
  // Turno noturno: começa às 18h e termina na manhã seguinte.
  export function inicioTurno() {
    const agora = new Date();
    const inicio = new Date(agora);
    inicio.setHours(18, 0, 0, 0);
    if (agora < inicio) inicio.setDate(inicio.getDate() - 1);
    return inicio;
  }
  
  export function formatarHora(data) {
    if (!data) return '';
    return new Date(data).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
  }
  
  export function formatarData(data) {
    if (!data) return '';
    return new Date(data).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
  }
  
  export function formatarCoordenadas(lat, lon) {
    if (typeof lat !== 'number' || typeof lon !== 'number') return '';
    return `${lat.toFixed(5)}, ${lon.toFixed(5)}`;
  }
  
  export function rotuloDia(data) {
    const d = new Date(data);
    const hoje = new Date();
    const ontem = new Date();
    ontem.setDate(hoje.getDate() - 1);
    const mesmoDia = (a, b) => a.toDateString() === b.toDateString();
    if (mesmoDia(d, hoje)) return 'Hoje';
    if (mesmoDia(d, ontem)) return 'Ontem';
    return d.toLocaleDateString('pt-BR', { weekday: 'short', day: '2-digit', month: 'short' });
  }
  
  export const RONDAS = [
    { numero: 1, horario: '00:00' },
    { numero: 2, horario: '02:00' },
    { numero: 3, horario: '04:00' },
  ];
  
  export function rondaAtual(data = new Date()) {
    const hora = data.getHours() + data.getMinutes() / 60;
    if (hora >= 23 || hora < 1) return 1;
    if (hora < 3) return 2;
    if (hora < 5) return 3;
    return 1;
  }
  
  export function rondaDoRegistro(registro) {
    if (!registro) return null;
    if (typeof registro.ronda === 'number') return registro.ronda;
    if (registro.created_date) return rondaAtual(new Date(registro.created_date));
    return null;
  }
  
  export function dadosDaRonda(numero) {
    return RONDAS.find((ronda) => ronda.numero === numero) || RONDAS[0];
  }