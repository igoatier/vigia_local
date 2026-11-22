import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { MapPin, ShieldAlert } from 'lucide-react';
import { base44 } from '@/api/base44Client';
import { useAuth } from '@/lib/AuthContext';
import AppShell from '@/components/AppShell';
import ProgressoRonda from '@/components/ProgressoRonda';
import PostoCard from '@/components/PostoCard';
import RegistroDialogo from '@/components/RegistroDialogo';
import RondaTabs from '@/components/RondaTabs';
import { Skeleton } from '@/components/ui/skeleton';
import { RONDAS, dadosDaRonda, distanciaMetros, formatarHora, inicioTurno, obterPosicao, rondaAtual, rondaDoRegistro } from '@/lib/geo';

export default function Home() {
  const { user } = useAuth();
  const [locais, setLocais] = useState([]);
  const [registros, setRegistros] = useState([]);
  const [posicao, setPosicao] = useState(null);
  const [carregando, setCarregando] = useState(true);
  const [postoAtivo, setPostoAtivo] = useState(null);
  const [rondaSelecionada, setRondaSelecionada] = useState(() => rondaAtual());

  const rondaAgora = rondaAtual();

  const carregar = useCallback(async () => {
    if (!user?.id) return;
    try {
      const [resLocais, resRegistros] = await Promise.all([
        base44.entities.Local.filter({ ativo: true }, { sort: 'ordem', limit: 100 }),
        base44.entities.Registro.filter(
          { created_by_id: user.id, created_date: { $gte: inicioTurno().toISOString() } },
          { sort: '-created_date', limit: 100 }
        ),
      ]);
      setLocais(resLocais.items || []);
      setRegistros(resRegistros.items || []);
    } finally {
      setCarregando(false);
    }
  }, [user?.id]);

  useEffect(() => {
    carregar();
  }, [carregar]);

  useEffect(() => {
    obterPosicao().then(setPosicao).catch(() => {});
  }, []);

  const registroPorLocal = registros
    .filter((registro) => rondaDoRegistro(registro) === rondaSelecionada)
    .reduce((acc, registro) => {
      if (registro.local_id && !acc[registro.local_id]) acc[registro.local_id] = registro;
      return acc;
    }, {});

  const porRonda = RONDAS.reduce((acc, ronda) => {
    acc[ronda.numero] = new Set(
      registros.filter((registro) => rondaDoRegistro(registro) === ronda.numero).map((registro) => registro.local_id)
    ).size;
    return acc;
  }, {});

  const feitos = RONDAS.reduce((soma, ronda) => soma + (porRonda[ronda.numero] || 0), 0);
  const total = locais.length * RONDAS.length;
  const dataAtual = new Date().toLocaleDateString('pt-BR', { weekday: 'short', day: '2-digit', month: 'short' });
  const horarioSelecionado = dadosDaRonda(rondaSelecionada).horario;

  const finalizar = () => {
    setPostoAtivo(null);
    carregar();
  };

  return (
    <AppShell titulo="Ronda de hoje" subtitulo={dataAtual}>
      <ProgressoRonda feitos={feitos} total={total} />

      <div className="mb-2 mt-6 flex items-baseline justify-between">
        <h2 className="text-[11px] font-semibold uppercase tracking-[0.24em] text-muted-foreground">Rondas da noite</h2>
        <span className="text-[10px] uppercase tracking-[0.14em] text-muted-foreground">00h · 02h · 04h</span>
      </div>

      <RondaTabs
        selecionada={rondaSelecionada}
        porRonda={porRonda}
        rondaAgora={rondaAgora}
        onSelecionar={setRondaSelecionada}
      />

      <div className="mb-3 mt-7 flex items-center justify-between">
        <h2 className="text-[11px] font-semibold uppercase tracking-[0.24em] text-muted-foreground">
          Postos · ronda {horarioSelecionado}
        </h2>
        <span className="text-[11px] uppercase tracking-[0.14em] text-muted-foreground">em ordem</span>
      </div>

      {carregando ? (
        <div className="space-y-3">
          {[0, 1, 2].map((i) => (
            <Skeleton key={i} className="h-24 w-full rounded-2xl bg-secondary/60" />
          ))}
        </div>
      ) : locais.length === 0 ? (
        <div className="rounded-3xl border border-border/80 bg-card p-7 text-center">
          <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-secondary text-muted-foreground">
            <ShieldAlert className="h-5 w-5" />
          </div>
          <h3 className="mt-3 font-display text-lg font-semibold">Nenhum posto cadastrado</h3>
          <p className="mt-1 text-sm text-muted-foreground">
            {user?.role === 'admin'
              ? 'Cadastre os postos da ronda com as coordenadas de cada local.'
              : 'Peça ao administrador para cadastrar os postos da ronda.'}
          </p>
          {user?.role === 'admin' ? (
            <Link
              to="/locais"
              className="mt-4 inline-flex items-center gap-1.5 rounded-full bg-primary px-4 py-2 text-[10px] font-bold uppercase tracking-[0.18em] text-primary-foreground"
            >
              <MapPin className="h-3.5 w-3.5" /> Cadastrar postos
            </Link>
          ) : null}
        </div>
      ) : (
        <div className="space-y-3">
          {locais.map((local, i) => {
            const registro = registroPorLocal[local.id];
            return (
              <PostoCard
                key={local.id}
                posto={local}
                indice={i + 1}
                horario={registro ? formatarHora(registro.created_date) : null}
                distancia={posicao ? distanciaMetros(posicao, local) : undefined}
                onRegistrar={setPostoAtivo}
              />
            );
          })}
        </div>
      )}

      <RegistroDialogo
        posto={postoAtivo}
        ronda={rondaSelecionada}
        aberto={Boolean(postoAtivo)}
        onFechar={() => setPostoAtivo(null)}
        onRegistrado={finalizar}
      />
    </AppShell>
  );
}
