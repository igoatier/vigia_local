import { useCallback, useEffect, useState } from 'react';
import { Loader2, ShieldCheck, ShieldOff, UserPlus, Users } from 'lucide-react';
import { base44 } from '@/api/base44Client';
import { useAuth } from '@/lib/AuthContext';
import AppShell from '@/components/AppShell';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { useToast } from '@/components/ui/use-toast';

export default function Equipe() {
  const { user } = useAuth();
  const { toast } = useToast();
  const ehAdmin = user?.role === 'admin';
  const [usuarios, setUsuarios] = useState([]);
  const [carregando, setCarregando] = useState(true);
  const [email, setEmail] = useState('');
  const [enviando, setEnviando] = useState(false);
  const [alterando, setAlterando] = useState(null);

  const carregar = useCallback(async () => {
    try {
      const lista = await base44.entities.User.list();
      const itens = Array.isArray(lista) ? lista : lista.items || [];
      setUsuarios(
        [...itens].sort((a, b) => (a.role === b.role ? 0 : a.role === 'admin' ? -1 : 1))
      );
    } finally {
      setCarregando(false);
    }
  }, []);

  useEffect(() => {
    if (!ehAdmin) {
      setCarregando(false);
      return;
    }
    carregar();
  }, [ehAdmin, carregar]);

  const convidar = async () => {
    const alvo = email.trim();
    if (!alvo) return;
    setEnviando(true);
    try {
      await base44.users.inviteUser(alvo, 'user');
      toast({ title: 'Convite enviado', description: `${alvo} entra como vigia.` });
      setEmail('');
      carregar();
    } catch (erro) {
      toast({ variant: 'destructive', title: 'Não foi possível convidar', description: erro?.message });
    } finally {
      setEnviando(false);
    }
  };

  const alternarPapel = async (pessoa) => {
    const novoPapel = pessoa.role === 'admin' ? 'user' : 'admin';
    setAlterando(pessoa.id);
    try {
      await base44.entities.User.update(pessoa.id, { role: novoPapel });
      toast({
        title: novoPapel === 'admin' ? 'Agora é administrador' : 'Agora é vigia',
        description: pessoa.full_name || pessoa.email,
      });
      carregar();
    } catch (erro) {
      toast({ variant: 'destructive', title: 'Não foi possível alterar', description: erro?.message });
    } finally {
      setAlterando(null);
    }
  };

  return (
    <AppShell titulo="Equipe" subtitulo={`${usuarios.length} ${usuarios.length === 1 ? 'pessoa' : 'pessoas'}`}>
      {!ehAdmin ? (
        <div className="rounded-3xl border border-border/80 bg-card p-7 text-center">
          <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-secondary text-muted-foreground">
            <Users className="h-5 w-5" />
          </div>
          <h3 className="mt-3 font-display text-lg font-semibold">Acesso restrito</h3>
          <p className="mt-1 text-sm text-muted-foreground">
            Somente administradores gerenciam os vigias e os postos.
          </p>
        </div>
      ) : (
        <>
          <div className="rounded-3xl border border-border/80 bg-card p-4">
            <h2 className="text-[10px] font-semibold uppercase tracking-[0.2em] text-primary">Convidar vigia</h2>
            <div className="mt-2 flex gap-2">
              <Input
                type="email"
                value={email}
                onChange={(evento) => setEmail(evento.target.value)}
                placeholder="email do vigia"
                className="h-11 bg-secondary/25 text-sm"
              />
              <Button
                onClick={convidar}
                disabled={enviando || !email.trim()}
                className="h-11 shrink-0 rounded-xl px-4 text-[10px] font-bold uppercase tracking-[0.14em]"
              >
                {enviando ? <Loader2 className="h-4 w-4 animate-spin" /> : <UserPlus className="h-4 w-4" />}
              </Button>
            </div>
            <p className="mt-2 text-[11px] leading-snug text-muted-foreground">
              O convite chega por e-mail. Ao entrar, a pessoa já acessa as rondas como vigia.
            </p>
          </div>

          <div className="mb-2 mt-6 flex items-center justify-between">
            <h2 className="text-[11px] font-semibold uppercase tracking-[0.24em] text-muted-foreground">Pessoas</h2>
            <span className="text-[10px] uppercase tracking-[0.14em] text-muted-foreground">papel</span>
          </div>

          {carregando ? (
            <div className="space-y-3">
              {[0, 1, 2].map((i) => (
                <Skeleton key={i} className="h-16 w-full rounded-2xl bg-secondary/60" />
              ))}
            </div>
          ) : (
            <div className="space-y-2.5">
              {usuarios.map((pessoa) => {
                const admin = pessoa.role === 'admin';
                const ehVoce = pessoa.id === user?.id;
                return (
                  <div
                    key={pessoa.id}
                    className="flex items-center gap-3 rounded-2xl border border-border/80 bg-card/70 p-3"
                  >
                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-secondary font-display text-sm font-semibold uppercase text-muted-foreground">
                      {(pessoa.full_name || pessoa.email || '?').charAt(0)}
                    </span>
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-sm font-semibold leading-tight">
                        {pessoa.full_name || pessoa.email}
                        {ehVoce ? <span className="ml-1.5 text-[9px] uppercase tracking-[0.14em] text-muted-foreground">você</span> : null}
                      </p>
                      <p className="mt-0.5 truncate text-[11px] text-muted-foreground">{pessoa.email}</p>
                    </div>
                    <span
                      className={`shrink-0 rounded-full px-2 py-0.5 text-[9px] font-semibold uppercase tracking-[0.12em] ${
                        admin ? 'bg-primary/15 text-primary' : 'bg-secondary text-muted-foreground'
                      }`}
                    >
                      {admin ? 'Admin' : 'Vigia'}
                    </span>
                    {!ehVoce ? (
                      <button
                        type="button"
                        onClick={() => alternarPapel(pessoa)}
                        disabled={alterando === pessoa.id}
                        aria-label={admin ? 'Tornar vigia' : 'Tornar administrador'}
                        className="shrink-0 text-muted-foreground transition-colors hover:text-primary disabled:opacity-40"
                      >
                        {alterando === pessoa.id ? (
                          <Loader2 className="h-4 w-4 animate-spin" />
                        ) : admin ? (
                          <ShieldOff className="h-4 w-4" />
                        ) : (
                          <ShieldCheck className="h-4 w-4" />
                        )}
                      </button>
                    ) : null}
                  </div>
                );
              })}
            </div>
          )}
        </>
      )}
    </AppShell>
  );
}
