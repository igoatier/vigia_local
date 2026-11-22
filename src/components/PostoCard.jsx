import { CheckCircle2, MapPin, Navigation } from 'lucide-react';
import { cn } from '@/lib/utils';

export default function PostoCard({ posto, indice, horario, distancia, onRegistrar }) {
  const concluido = Boolean(horario);

  return (
    <article
      className={cn(
        'group relative overflow-hidden rounded-2xl border bg-card/70 transition-colors',
        concluido ? 'border-primary/25' : 'border-border/80'
      )}
    >
      <div className="flex items-start gap-3.5 p-4">
        <div
          className={cn(
            'mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border font-display text-base font-semibold',
            concluido ? 'border-primary/40 bg-primary/15 text-primary' : 'border-border bg-secondary text-muted-foreground'
          )}
        >
          {concluido ? <CheckCircle2 className="h-4.5 w-4.5" /> : indice}
        </div>

        <div className="min-w-0 flex-1">
          <h3 className="truncate font-display text-lg font-semibold leading-tight">{posto.nome}</h3>
          {posto.endereco ? (
            <p className="mt-0.5 flex items-center gap-1 truncate text-xs text-muted-foreground">
              <MapPin className="h-3 w-3 shrink-0" />
              {posto.endereco}
            </p>
          ) : null}

          <div className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] uppercase tracking-[0.12em]">
            <span className={cn('font-semibold', concluido ? 'text-primary' : 'text-muted-foreground')}>
              {concluido ? `Registrado ${horario}` : 'Pendente'}
            </span>
            {typeof distancia === 'number' ? (
              <span className="flex items-center gap-1 text-muted-foreground">
                <Navigation className="h-3 w-3" />
                {distancia < 1000 ? `${Math.round(distancia)} m` : `${(distancia / 1000).toFixed(1)} km`}
              </span>
            ) : null}
          </div>
        </div>
      </div>

      {!concluido ? (
        <button
          type="button"
          onClick={() => onRegistrar?.(posto)}
          className="w-full border-t border-border/70 bg-secondary/40 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-foreground transition-colors hover:bg-primary hover:text-primary-foreground"
        >
          Registrar ponto
        </button>
      ) : null}
    </article>
  );
}
