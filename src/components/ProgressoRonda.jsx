export default function ProgressoRonda({ feitos, total }) {
    const pct = total > 0 ? feitos / total : 0;
    const raio = 46;
    const circunferencia = 2 * Math.PI * raio;
  
    return (
      <section className="relative overflow-hidden rounded-3xl border border-border/80 bg-card p-5">
        <div className="pointer-events-none absolute -right-16 -top-16 h-44 w-44 rounded-full bg-primary/10 blur-2xl" />
        <div className="relative flex items-center gap-5">
          <div className="relative h-28 w-28 shrink-0">
            <svg viewBox="0 0 112 112" className="h-full w-full -rotate-90">
              <circle cx="56" cy="56" r={raio} fill="none" stroke="hsl(var(--secondary))" strokeWidth="7" />
              <circle
                cx="56"
                cy="56"
                r={raio}
                fill="none"
                stroke="hsl(var(--primary))"
                strokeWidth="7"
                strokeLinecap="round"
                strokeDasharray={circunferencia}
                strokeDashoffset={circunferencia * (1 - pct)}
                className="transition-[stroke-dashoffset] duration-700 ease-out"
              />
            </svg>
            <div className="absolute inset-0 flex flex-col items-center justify-center">
              <span className="font-display text-3xl font-semibold leading-none">{feitos}</span>
              <span className="text-[10px] uppercase tracking-[0.2em] text-muted-foreground">de {total}</span>
            </div>
          </div>
  
          <div className="min-w-0">
            <p className="text-[10px] font-semibold uppercase tracking-[0.24em] text-primary">Progresso do turno</p>
            <h2 className="mt-1 font-display text-2xl font-semibold leading-tight">
              {feitos === 0 ? 'Ronda não iniciada' : feitos === total ? 'Ronda completa' : 'Ronda em andamento'}
            </h2>
            <p className="mt-1.5 text-sm leading-snug text-muted-foreground">
              {total === 0
                ? 'Nenhum posto cadastrado ainda.'
                : total - feitos === 0
                  ? 'Todas as rondas da noite foram concluídas.'
                  : `Faltam ${total - feitos} ${total - feitos === 1 ? 'ponto' : 'pontos'} para fechar as rondas da noite.`}
            </p>
          </div>
        </div>
      </section>
    );
  }
