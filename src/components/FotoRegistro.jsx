import { useEffect, useState } from 'react';
import { ImageOff } from 'lucide-react';
import { base44 } from '@/api/base44Client';
import { cn } from '@/lib/utils';

export default function FotoRegistro({ fileUri, alt, className }) {
  const [url, setUrl] = useState(null);
  const [falhou, setFalhou] = useState(false);

  useEffect(() => {
    if (!fileUri) return;
    let ativo = true;
    setUrl(null);
    setFalhou(false);
    base44.integrations.Core.CreateFileSignedUrl({ file_uri: fileUri })
      .then((res) => {
        if (ativo) setUrl(res.signed_url);
      })
      .catch(() => {
        if (ativo) setFalhou(true);
      });
    return () => {
      ativo = false;
    };
  }, [fileUri]);

  if (falhou || !fileUri) {
    return (
      <div className={cn('flex items-center justify-center bg-secondary text-muted-foreground', className)}>
        <ImageOff className="h-5 w-5" />
      </div>
    );
  }

  if (!url) {
    return <div className={cn('animate-pulse bg-secondary', className)} />;
  }

  return <img src={url} alt={alt} className={cn('object-cover', className)} loading="lazy" />;
}