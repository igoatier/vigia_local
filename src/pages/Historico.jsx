import { useCallback, useEffect, useState } from 'react';
import { History } from 'lucide-react';
import { base44 } from '@/api/base44Client';
import { useAuth } from '@/lib/AuthContext';
import AppShell from '@/components/AppShell';
import HistoricoItem from '@/components/HistoricoItem';
import HistoricoFiltros from '@/components/HistoricoFiltros';
import { Skeleton } from '@/components/ui/skeleton';
import { formatarData, rotuloDia } from '@/lib/geo';

export default function Historico() {
  const { user } = useAuth();
  const ehAdmin = user?.role === 'admin';
  const [registros, setRegistros] = useState([]);
  const [carregando, setCarregando] = useState(true);
  const [vigia, setVigia] = useState('todos');
  const [local, setLocal] = useState('todos');
  const [vigias, setVigias] = useState([]);
  const [locais, setLocais] = useState([]);

  useEffect(() => {
    if (!ehAdmin) return;
    Promise.all([
      base44.entities.Registro.list({ limit: 200 }),
      base44.entities.Local.list({ sort: 'ordem', limit: 100 }),
    ]).then(([resRegistros, resLocais]) => {
      const itens = resRegistros.items || [];
      const nomes = [...new Set(itens.map((r) => (typeof r === 'string' ? r : r?.vigia_nome)).filter(Boolean))];
      setVigias(nomes);
      setLocais((resLocais.items || []).map((item) => ({ id: item.id, nome: item.nome })));
    }).catch(() => {});
  }, [ehAdmin]);

  const carregar = useCallback(async () => {
    if (!user?.id) return;
    const consulta = ehAdmin ? {} : { created_by_id: user.id };
    if (ehAdmin && vigia !== 'todos') consulta.vigia_nome = vigia;
    if (local !== 'todos') consulta.local_id = local;
    try {
      const res = await base44.entities.Registro.filter(consulta, { sort: '-created_date', limit: 100 });
      setRegistros(res.items || []);
    } finally {
      setCarregando(false);
    }
  }, [user?.id, ehAdmin, vigia, local]);

  useEffect(() => {
    setCarregando(true);
    carregar();
  }, [carregar]);

  const grupos = registros.reduce((acc, registro) => {
    const chave = rotuloDia(registro.created_date);
    if (!acc[chave]) acc[chave] = [];
    acc[chave].push(registro);
    return acc;
  }, {});

  return (
    <AppShell
      titulo="Histórico"
      subtitulo={`${registros.length} ${registros.length === 1 ? 'registro' : 'registros'}`}
    >
      {ehAdmin ? (
        <HistoricoFiltros
          vigias={vigias}
          locais={locais}
          vigia={vigia}
          local={local}
          onVigia={setVigia}
          onLocal={setLocal}
        />
      ) : null}

      {carregando ? (
        <div className="space-y-3">
          {[0, 1, 2, 3].map((i) => (
            <Skeleton key={i} className="h-24 w-full rounded-2xl bg-secondary/60" />
          ))}
        </div>
      ) : registros.length === 0 ? (
        <div className="rounded-3xl border border-border/80 bg-card p-8 text-center">
          <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-secondary text-muted-foreground">
            <History className="h-5 w-5" />
          </div>
          <h3 className="mt-3 font-display text-lg font-semibold">Nenhum ponto registrado</h3>
          <p className="mt-1 text-sm text-muted-foreground">Os registros aparecem aqui com foto, hora e coordenadas.</p>
        </div>
      ) : (
        <div className="space-y-7">
          {Object.entries(grupos).map(([dia, lista]) => (
            <section key={dia}>
              <div className="mb-2.5 flex items-center justify-between">
                <h2 className="text-[11px] font-semibold uppercase tracking-[0.24em] text-primary">{dia}</h2>
                <span className="text-[10px] uppercase tracking-[0.14em] text-muted-foreground">
                  {formatarData(lista[0].created_date)}
                </span>
              </div>
              <div className="space-y-3">
                {lista.map((registro) => (
                  <HistoricoItem key={registro.id} registro={registro} />
                ))}
              </div>
            </section>
          ))}
        </div>
      )}
    </AppShell>
  );
}
