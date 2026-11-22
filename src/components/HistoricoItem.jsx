import { Check, TriangleAlert, MapPin } from 'lucide-react';
import FotoRegistro from '@/components/FotoRegistro';
import { formatarCoordenadas, formatarDistancia, formatarHora, dadosDaRonda, rondaAtual } from '@/lib/geo';

export default function HistoricoItem({ registro }) {
  const rondaHorario =
    registro.ronda_horario ||
    (registro.created_date ? dadosDaRonda(rondaAtual(new Date(registro.created_date))).horario : null);
  const mapaUrl =
    registro.latitude != null && registro.longitude != null
      ? `https://www.google.com/maps?q=${registro.latitude},${registro.longitude}`
      : null;

  return (
    <article className="flex gap-3 rounded-2xl border border-border/80 bg-card/70 p-3">
      <FotoRegistro fileUri={registro.foto_uri} alt={registro.local_nome} className="h-16 w-16 shrink-0 rounded-xl" />

      <div className="min-w-0 flex-1">
        <div className="flex items-baseline justify-between gap-2">
          <h3 className="truncate font-display text-base font-semibold leading-tight">{registro.local_nome}</h3>
          <span className="shrink-0 font-mono text-[11px] text-primary">{formatarHora(registro.created_date)}</span>
        </div>

        <p className="mt-1 truncate font-mono text-[10px] tracking-tight text-muted-foreground">
          {mapaUrl ? (
            <a
              href={mapaUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="hover:text-primary hover:underline"
              title="Ver coordenadas no Google Maps"
            >
              {formatarCoordenadas(registro.latitude, registro.longitude)}
            </a>
          ) : (
            formatarCoordenadas(registro.latitude, registro.longitude)
          )}
        </p>

        <div className="mt-1.5 flex flex-wrap items-center gap-x-2.5 gap-y-1">
          <span
            className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[9px] font-semibold uppercase tracking-[0.12em] ${
              registro.dentro_raio ? 'bg-primary/15 text-primary' : 'bg-destructive/15 text-destructive'
            }`}
          >
            {registro.dentro_raio ? <Check className="h-2.5 w-2.5" /> : <TriangleAlert className="h-2.5 w-2.5" />}
            {registro.dentro_raio ? 'No local' : 'Fora do raio'}
          </span>
          {rondaHorario ? (
            <span className="rounded-full bg-secondary px-2 py-0.5 text-[9px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">
              Ronda {rondaHorario}
            </span>
          ) : null}
          {registro.vigia_nome ? (
            <span className="truncate text-[10px] uppercase tracking-[0.1em] text-muted-foreground">{registro.vigia_nome}</span>
          ) : null}
          {registro.distancia_metros != null && mapaUrl ? (
            <a
              href={mapaUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-1 text-[10px] text-muted-foreground transition-colors hover:text-primary hover:underline"
              title="Ver localização no Google Maps"
            >
              <MapPin className="h-2.5 w-2.5" />
              {formatarDistancia(registro.distancia_metros)} do posto
            </a>
          ) : null}
        </div>

        {registro.observacao ? (
          <p className="mt-1.5 line-clamp-2 text-xs text-muted-foreground">{registro.observacao}</p>
        ) : null}
      </div>
    </article>
  );
}