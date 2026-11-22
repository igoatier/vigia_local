import { useEffect, useState } from 'react';
import { Loader2, LocateFixed } from 'lucide-react';
import { base44 } from '@/api/base44Client';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToast } from '@/components/ui/use-toast';
import { obterPosicao } from '@/lib/geo';

const VAZIO = { nome: '', endereco: '', latitude: '', longitude: '', raio_metros: '100', ordem: '1' };

export default function DialogoLocal({ local, aberto, onFechar, onSalvo }) {
  const { toast } = useToast();
  const [form, setForm] = useState(VAZIO);
  const [salvando, setSalvando] = useState(false);
  const [buscando, setBuscando] = useState(false);
  const [confirmarExclusao, setConfirmarExclusao] = useState(false);

  useEffect(() => {
    if (!aberto) return;
    setConfirmarExclusao(false);
    setForm(
      local
        ? {
            nome: local.nome || '',
            endereco: local.endereco || '',
            latitude: String(local.latitude ?? ''),
            longitude: String(local.longitude ?? ''),
            raio_metros: String(local.raio_metros ?? 100),
            ordem: String(local.ordem ?? 1),
          }
        : VAZIO
    );
  }, [aberto, local?.id]);

  const definir = (campo, valor) => setForm((atual) => ({ ...atual, [campo]: valor }));

  const usarMinhaLocalizacao = async () => {
    setBuscando(true);
    try {
      const posicao = await obterPosicao();
      setForm((atual) => ({
        ...atual,
        latitude: posicao.latitude.toFixed(6),
        longitude: posicao.longitude.toFixed(6),
      }));
    } catch (erro) {
      toast({ variant: 'destructive', title: 'Localização indisponível', description: erro.message });
    } finally {
      setBuscando(false);
    }
  };

  const salvar = async () => {
    const latitude = Number(form.latitude);
    const longitude = Number(form.longitude);
    if (!form.nome.trim() || Number.isNaN(latitude) || Number.isNaN(longitude) || !form.latitude || !form.longitude) {
      toast({ variant: 'destructive', title: 'Preencha nome, latitude e longitude' });
      return;
    }
    setSalvando(true);
    try {
      const dados = {
        nome: form.nome.trim(),
        endereco: form.endereco.trim(),
        latitude,
        longitude,
        raio_metros: Number(form.raio_metros) || 100,
        ordem: Number(form.ordem) || 1,
        ativo: local ? local.ativo !== false : true,
      };
      if (local) {
        await base44.entities.Local.update(local.id, dados);
      } else {
        await base44.entities.Local.create(dados);
      }
      toast({ title: local ? 'Posto atualizado' : 'Posto cadastrado' });
      onSalvo?.();
    } catch (erro) {
      toast({ variant: 'destructive', title: 'Não foi possível salvar', description: erro?.message });
    } finally {
      setSalvando(false);
    }
  };

  const excluir = async () => {
    if (!confirmarExclusao) {
      setConfirmarExclusao(true);
      return;
    }
    setSalvando(true);
    try {
      await base44.entities.Local.delete(local.id);
      toast({ title: 'Posto removido' });
      onSalvo?.();
    } catch (erro) {
      toast({ variant: 'destructive', title: 'Não foi possível remover', description: erro?.message });
      setSalvando(false);
    }
  };

  return (
    <Dialog open={aberto} onOpenChange={(valor) => !valor && !salvando && onFechar()}>
      <DialogContent className="max-w-md border-border/80 bg-card p-0">
        <DialogHeader className="border-b border-border/70 px-5 pb-4 pt-5 text-left">
          <DialogTitle className="font-display text-xl font-semibold uppercase tracking-tight">
            {local ? 'Editar posto' : 'Novo posto'}
          </DialogTitle>
        </DialogHeader>

        <div className="max-h-[65vh] space-y-3.5 overflow-y-auto px-5 py-4">
          <div className="space-y-1.5">
            <Label className="text-[10px] uppercase tracking-[0.16em] text-muted-foreground">Nome do posto</Label>
            <Input value={form.nome} onChange={(e) => definir('nome', e.target.value)} placeholder="Portaria principal" className="bg-secondary/25" />
          </div>
          <div className="space-y-1.5">
            <Label className="text-[10px] uppercase tracking-[0.16em] text-muted-foreground">Endereço / referência</Label>
            <Input value={form.endereco} onChange={(e) => definir('endereco', e.target.value)} placeholder="Bloco B — fundos" className="bg-secondary/25" />
          </div>

          <button
            type="button"
            onClick={usarMinhaLocalizacao}
            className="flex w-full items-center justify-center gap-2 rounded-xl border border-primary/30 bg-primary/10 py-2.5 text-[10px] font-bold uppercase tracking-[0.16em] text-primary"
          >
            {buscando ? <Loader2 className="h-3.5 w-3.5 animate-spin" /> : <LocateFixed className="h-3.5 w-3.5" />}
            Usar minha localização atual
          </button>

          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <Label className="text-[10px] uppercase tracking-[0.16em] text-muted-foreground">Latitude</Label>
              <Input value={form.latitude} onChange={(e) => definir('latitude', e.target.value)} inputMode="decimal" className="bg-secondary/25 font-mono text-xs" />
            </div>
            <div className="space-y-1.5">
              <Label className="text-[10px] uppercase tracking-[0.16em] text-muted-foreground">Longitude</Label>
              <Input value={form.longitude} onChange={(e) => definir('longitude', e.target.value)} inputMode="decimal" className="bg-secondary/25 font-mono text-xs" />
            </div>
            <div className="space-y-1.5">
              <Label className="text-[10px] uppercase tracking-[0.16em] text-muted-foreground">Raio (metros)</Label>
              <Input value={form.raio_metros} onChange={(e) => definir('raio_metros', e.target.value)} inputMode="numeric" className="bg-secondary/25" />
            </div>
            <div className="space-y-1.5">
              <Label className="text-[10px] uppercase tracking-[0.16em] text-muted-foreground">Ordem</Label>
              <Input value={form.ordem} onChange={(e) => definir('ordem', e.target.value)} inputMode="numeric" className="bg-secondary/25" />
            </div>
          </div>
        </div>

        <div className="space-y-2 border-t border-border/70 p-4">
          <Button onClick={salvar} disabled={salvando} className="h-12 w-full rounded-xl text-[11px] font-bold uppercase tracking-[0.2em]">
            {salvando ? <Loader2 className="h-4 w-4 animate-spin" /> : 'Salvar posto'}
          </Button>
          {local ? (
            <button
              type="button"
              onClick={excluir}
              disabled={salvando}
              className="w-full py-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-muted-foreground transition-colors hover:text-destructive"
            >
              {confirmarExclusao ? 'Confirmar exclusão' : 'Excluir posto'}
            </button>
          ) : null}
        </div>
      </DialogContent>
    </Dialog>
  );
}