export default function HistoricoFiltros({ vigias, locais, vigia, local, onVigia, onLocal }) {
    const temVigias = vigias.length > 0;
  
    return (
      <div className={`mb-4 grid gap-2 ${temVigias ? 'grid-cols-2' : 'grid-cols-1'}`}>
        {temVigias ? (
          <label className="space-y-1">
            <span className="block text-[9px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">Vigia</span>
            <select
              value={vigia}
              onChange={(evento) => onVigia(evento.target.value)}
              className="w-full rounded-xl border border-border/80 bg-card px-2.5 py-2 text-xs text-foreground"
            >
              <option value="todos">Todos</option>
              {vigias.map((nome) => (
                <option key={nome} value={nome}>
                  {nome}
                </option>
              ))}
            </select>
          </label>
        ) : null}
  
        <label className="space-y-1">
          <span className="block text-[9px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">Posto</span>
          <select
            value={local}
            onChange={(evento) => onLocal(evento.target.value)}
            className="w-full rounded-xl border border-border/80 bg-card px-2.5 py-2 text-xs text-foreground"
          >
            <option value="todos">Todos</option>
            {locais.map((item) => (
              <option key={item.id} value={item.id}>
                {item.nome}
              </option>
            ))}
          </select>
        </label>
      </div>
    );
  }