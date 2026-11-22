import { useCallback, useEffect, useState } from 'react';
import { MapPin, Plus, Settings2 } from 'lucide-react';
import { base44 } from '@/api/base44Client';
import { useAuth } from '@/lib/AuthContext';
import AppShell from '@/components/AppShell';
import DialogoLocal from '@/components/DialogoLocal';
import { Skeleton } from '@/components/ui/skeleton';
import { formatarCoordenadas } from '@/lib/geo';

export default function Locais() {
  const { user } = useAuth();
  const ehAdmin = user?.role === 'admin';
  const [locais, setLocais] = useState([]);
  const [carregando, setCarregando] = useState(true);
  const [editando, setEditando] = useState(null);
  const [novoAberto, setNovoAberto] = useState(false);

  const carregar = useCallback(async () => {
    try {
      const res = await base44.entities.Local.list({ sort: 'ordem', limit: 100 });
      setLocais(res.items || []);
    } finally {
      setCarregando(false);
    }
  }, []);

  useEffect(() => {
    carregar();
  }, [carregar]);

  const finalizar = () => {
    setNovoAberto(false);
    setEditando(null);
    carregar();
  };

  return (
    <AppShell titulo="Postos" subtitulo={`${locais.length} ${locais.length === 1 ? 'local' : 'locais'}`}>
      <p className="mb-4 text-sm leading-relaxed text-muted-foreground">
        {ehAdmin
          ? 'Cada posto guarda as coordenadas exigidas para o registro do ponto.'
          : 'Locais da ronda definidos pelo administrador.'}
      </p>

      {ehAdmin ? (
        <button
          type="button"
          onClick={() => setNovoAberto(true)}
          className="mb-4 flex w-full items-center justify-center gap-2 rounded-2xl border border-primary/35 bg-primary/10 py-3 text-[11px] font-bold uppercase tracking-[0.18em] text-primary transition-colors hover:bg-primary hover:text-primary-foreground"
        >
          <Plus className="h-4 w-4" /> Novo posto
        </button>
      ) : null}

      {carregando ? (
        <div className="space-y-3">
          {[0, 1, 2].map((i) => (
            <Skeleton key={i} className="h-20 w-full rounded-2xl bg-secondary/60" />
          ))}
        </div>
      ) : locais.length === 0 ? (
        <div className="rounded-3xl border border-border/80 bg-card p-8 text-center">
          <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-secondary text-muted-foreground">
            <MapPin className="h-5 w-5" />
          </div>
          <h3 className="mt-3 font-display text-lg font-semibold">Nenhum posto cadastrado</h3>
          <p className="mt-1 text-sm text-muted-foreground">
            {ehAdmin ? 'Toque em “Novo posto” para começar.' : 'Aguarde o cadastro feito pelo administrador.'}
          </p>
        </div>
      ) : (
        <div className="space-y-2.5">
          {locais.map((local, i) => (
            <button
              key={local.id}
              type="button"
              disabled={!ehAdmin}
              onClick={() => setEditando(local)}
              className="flex w-full items-center gap-3 rounded-2xl border border-border/80 bg-card/70 p-4 text-left transition-colors enabled:hover:border-primary/40 disabled:cursor-default"
            >
              <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-border bg-secondary font-display text-base font-semibold text-muted-foreground">
                {local.ordem ?? i + 1}
              </span>
              <span className="min-w-0 flex-1">
                <span className="block truncate font-display text-base font-semibold leading-tight">{local.nome}</span>
                <span className="mt-0.5 block truncate font-mono text-[10px] text-muted-foreground">
                  {formatarCoordenadas(local.latitude, local.longitude)} · raio {local.raio_metros ?? 100} m
                </span>
              </span>
              {ehAdmin ? <Settings2 className="h-4 w-4 shrink-0 text-muted-foreground" /> : null}
            </button>
          ))}
        </div>
      )}

      <DialogoLocal local={editando} aberto={Boolean(editando)} onFechar={() => setEditando(null)} onSalvo={finalizar} />
      <DialogoLocal local={null} aberto={novoAberto} onFechar={() => setNovoAberto(false)} onSalvo={finalizar} />
    </AppShell>
  );
}
