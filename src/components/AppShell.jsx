import { Link, useLocation } from 'react-router-dom';
import { Moon, History, MapPin, LogOut, Users } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useAuth } from '@/lib/AuthContext';

const ABAS = [
  { path: '/', label: 'Ronda', Icon: Moon },
  { path: '/historico', label: 'Histórico', Icon: History },
  { path: '/locais', label: 'Locais', Icon: MapPin },
];

const ABA_EQUIPE = { path: '/equipe', label: 'Equipe', Icon: Users };

export default function AppShell({ children, titulo, subtitulo }) {
  const { pathname } = useLocation();
  const { logout, user } = useAuth();
  const abas = user?.role === 'admin' ? [...ABAS, ABA_EQUIPE] : ABAS;

  return (
    <div className="relative min-h-screen bg-background text-foreground">
      <div className="pointer-events-none fixed inset-x-0 top-0 h-72 bg-[radial-gradient(ellipse_at_top,rgba(245,184,65,0.13),transparent_68%)]" />
      <div className="relative mx-auto flex min-h-screen w-full max-w-lg flex-col">
        <header className="flex items-end justify-between px-5 pb-5 pt-9">
          <div>
            <p className="text-[10px] font-semibold uppercase tracking-[0.3em] text-primary/80">Ronda Noturna</p>
            <h1 className="mt-1 text-[28px] font-semibold uppercase leading-none tracking-tight">{titulo}</h1>
          </div>
          <div className="flex items-center gap-2 pb-1">
            {subtitulo ? (
              <span className="text-[11px] font-medium uppercase tracking-[0.14em] text-muted-foreground">{subtitulo}</span>
            ) : null}
            <button
              type="button"
              onClick={() => logout()}
              aria-label="Sair da conta"
              className="flex h-9 w-9 items-center justify-center rounded-full border border-border/80 text-muted-foreground transition-colors hover:text-foreground"
            >
              <LogOut className="h-4 w-4" />
            </button>
          </div>
        </header>

        <main className="flex-1 px-5 pb-32">{children}</main>

        <nav className="fixed inset-x-0 bottom-0 z-40 border-t border-border/70 bg-background/85 backdrop-blur-xl">
          <div className="mx-auto flex w-full max-w-lg items-stretch justify-around px-3 pb-[max(10px,env(safe-area-inset-bottom))] pt-2">
            {abas.map(({ path, label, Icon }) => {
              const ativo = pathname === path;
              return (
                <Link
                  key={path}
                  to={path}
                  className={cn(
                    'flex flex-1 flex-col items-center gap-1 rounded-2xl py-2 text-[10px] font-semibold uppercase tracking-[0.14em] transition-colors',
                    ativo ? 'text-primary' : 'text-muted-foreground hover:text-foreground'
                  )}
                >
                  <Icon className={cn('h-5 w-5 transition-transform', ativo && 'scale-110')} strokeWidth={ativo ? 2.4 : 1.8} />
                  {label}
                </Link>
              );
            })}
          </div>
        </nav>
      </div>
    </div>
  );
}
