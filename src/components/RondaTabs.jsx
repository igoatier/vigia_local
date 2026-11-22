import { RONDAS } from '@/lib/geo';
import { cn } from '@/lib/utils';

export default function RondaTabs({ selecionada, porRonda, rondaAgora, onSelecionar }) {
  return (
    <div className="grid grid-cols-3 gap-2">
      {RONDAS.map((ronda) => {
        const ativa = ronda.numero === selecionada;
        return (
          <button
            key={ronda.numero}
            type="button"
            onClick={() => onSelecionar(ronda.numero)}
            className={cn(
              'rounded-2xl border px-2 py-2.5 text-center transition-colors',
              ativa
                ? 'border-primary/70 bg-primary/10 text-primary'
                : 'border-border/80 bg-card/60 text-muted-foreground hover:text-foreground'
            )}
          >
            <span className="block font-display text-lg font-semibold leading-none">{ronda.horario}</span>
            <span className="mt-1.5 block text-[9px] font-semibold uppercase tracking-[0.12em]">
              {porRonda[ronda.numero] || 0} feitos
            </span>
            <span
              className={cn(
                'mt-1 block text-[8px] font-bold uppercase tracking-[0.2em]',
                ronda.numero === rondaAgora ? 'text-primary' : 'text-transparent'
              )}
            >
              agora
            </span>
          </button>
        );
      })}
    </div>
  );
}
