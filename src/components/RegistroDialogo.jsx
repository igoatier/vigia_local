import { useEffect, useRef, useState } from 'react';
import { Camera, Check, Loader2, MapPin, RotateCcw, TriangleAlert } from 'lucide-react';
import { base44 } from '@/api/base44Client';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/components/ui/use-toast';
import { useAuth } from '@/lib/AuthContext';
import { dadosDaRonda, distanciaMetros, formatarCoordenadas, formatarDistancia, obterPosicao } from '@/lib/geo';

export default function RegistroDialogo({ posto, ronda, aberto, onFechar, onRegistrado }) {
  const { user } = useAuth();
  const { toast } = useToast();
  const dadosRonda = dadosDaRonda(ronda);
  const [posicao, setPosicao] = useState(null);
  const [erroGeo, setErroGeo] = useState('');
  const [buscandoGeo, setBuscandoGeo] = useState(false);
  const [foto, setFoto] = useState(null);
  const [preview, setPreview] = useState(null);
  const [observacao, setObservacao] = useState('');
  const [enviando, setEnviando] = useState(false);
  const inputCamera = useRef(null);

  const buscarPosicao = async () => {
    setBuscandoGeo(true);
    setErroGeo('');
    try {
      setPosicao(await obterPosicao());
    } catch (erro) {
      setErroGeo(erro.message);
    } finally {
      setBuscandoGeo(false);
    }
  };

  useEffect(() => {
    if (!aberto) return;
    setFoto(null);
    setPreview(null);
    setObservacao('');
    setPosicao(null);
    setErroGeo('');
    buscarPosicao();
  }, [aberto, posto?.id]);

  const escolherFoto = (evento) => {
    const arquivo = evento.target.files?.[0];
    if (!arquivo) return;
    setFoto(arquivo);
    setPreview((anterior) => {
      if (anterior) URL.revokeObjectURL(anterior);
      return URL.createObjectURL(arquivo);
    });
  };

  const distancia = posicao && posto ? distanciaMetros(posicao, posto) : null;
  const raio = posto?.raio_metros ?? 100;
  const dentroDoRaio = distancia != null && distancia <= raio;

  const enviar = async () => {
    if (!foto || !posicao || !posto) return;
    setEnviando(true);
    try {
      const { file_uri } = await base44.integrations.Core.UploadPrivateFile({ file: foto });
      await base44.entities.Registro.create({
        local_id: posto.id,
        local_nome: posto.nome,
        ronda: dadosRonda.numero,
        ronda_horario: dadosRonda.horario,
        latitude: posicao.latitude,
        longitude: posicao.longitude,
        precisao_metros: Math.round(posicao.precisao || 0),
        distancia_metros: Math.round(distancia || 0),
        dentro_raio: dentroDoRaio,
        foto_uri: file_uri,
        observacao: observacao.trim(),
        vigia_nome: user?.full_name || user?.email || '',
      });
      toast({ title: 'Ponto registrado', description: posto.nome });
      onRegistrado?.();
    } catch (erro) {
      toast({
        variant: 'destructive',
        title: 'Não foi possível registrar',
        description: erro?.message || 'Tente novamente.',
      });
    } finally {
      setEnviando(false);
    }
  };

  return (
    <Dialog open={aberto} onOpenChange={(valor) => !valor && !enviando && onFechar()}>
      <DialogContent className="max-w-md border-border/80 bg-card p-0">
        <DialogHeader className="border-b border-border/70 px-5 pb-4 pt-5 text-left">
          <DialogTitle className="font-display text-xl font-semibold uppercase tracking-tight">
            Registrar ponto
          </DialogTitle>
          <DialogDescription className="text-xs uppercase tracking-[0.16em] text-muted-foreground">
            {posto?.nome} · Ronda {dadosRonda.horario}
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-3 px-5 py-4">
          <input
            ref={inputCamera}
            type="file"
            accept="image/*"
            capture="environment"
            className="hidden"
            onChange={escolherFoto}
          />

          {preview ? (
            <div className="relative overflow-hidden rounded-2xl border border-border/80">
              <img src={preview} alt="Foto do registro" className="h-44 w-full object-cover" />
              <button
                type="button"
                onClick={() => inputCamera.current?.click()}
                className="absolute bottom-2 right-2 flex items-center gap-1.5 rounded-full bg-background/85 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.14em] backdrop-blur"
              >
                <RotateCcw className="h-3 w-3" /> Trocar foto
              </button>
            </div>
          ) : (
            <button
              type="button"
              onClick={() => inputCamera.current?.click()}
              className="flex h-44 w-full flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-border bg-secondary/30 text-muted-foreground transition-colors hover:border-primary/60 hover:text-primary"
            >
              <Camera className="h-7 w-7" strokeWidth={1.6} />
              <span className="text-[11px] font-semibold uppercase tracking-[0.18em]">Abrir câmera</span>
            </button>
          )}

          <div className="rounded-2xl border border-border/80 bg-secondary/25 p-3.5">
            <div className="flex items-start gap-2.5">
              <MapPin className="mt-0.5 h-4 w-4 shrink-0 text-primary" />
              <div className="min-w-0 flex-1 text-xs">
                {buscandoGeo ? (
                  <span className="flex items-center gap-2 text-muted-foreground">
                    <Loader2 className="h-3.5 w-3.5 animate-spin" /> Obtendo coordenadas…
                  </span>
                ) : erroGeo ? (
                  <div className="space-y-2">
                    <p className="flex items-start gap-1.5 text-destructive">
                      <TriangleAlert className="mt-px h-3.5 w-3.5 shrink-0" /> {erroGeo}
                    </p>
                    <button
                      type="button"
                      onClick={buscarPosicao}
                      className="text-[10px] font-semibold uppercase tracking-[0.16em] text-primary"
                    >
                      Tentar novamente
                    </button>
                  </div>
                ) : posicao ? (
                  <>
                    <p className="font-mono text-[11px] tracking-tight text-foreground">
                      {formatarCoordenadas(posicao.latitude, posicao.longitude)}
                    </p>
                    <p className="mt-1 text-[10px] uppercase tracking-[0.14em] text-muted-foreground">
                      Precisão ±{Math.round(posicao.precisao || 0)} m · Posto a {formatarDistancia(distancia)}
                    </p>
                    <p
                      className={`mt-2 inline-flex items-center gap-1 rounded-full px-2 py-1 text-[9px] font-semibold uppercase tracking-[0.14em] ${
                        dentroDoRaio ? 'bg-primary/15 text-primary' : 'bg-destructive/15 text-destructive'
                      }`}
                    >
                      {dentroDoRaio ? <Check className="h-3 w-3" /> : <TriangleAlert className="h-3 w-3" />}
                      {dentroDoRaio ? 'Dentro do raio do posto' : `Fora do raio de ${raio} m`}
                    </p>
                  </>
                ) : (
                  <span className="text-muted-foreground">Aguardando localização.</span>
                )}
              </div>
            </div>
          </div>

          <Textarea
            value={observacao}
            onChange={(e) => setObservacao(e.target.value)}
            placeholder="Observação (opcional)"
            rows={2}
            className="resize-none border-border/80 bg-secondary/25 text-sm"
          />
        </div>

        <div className="border-t border-border/70 p-4">
          <Button
            onClick={enviar}
            disabled={!foto || !posicao || enviando}
            className="h-12 w-full rounded-xl text-[11px] font-bold uppercase tracking-[0.2em]"
          >
            {enviando ? <Loader2 className="h-4 w-4 animate-spin" /> : 'Confirmar registro'}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}