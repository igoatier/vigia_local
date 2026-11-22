# VigiaCheck · versão PHP 8 + MySQL

Sistema de rondas noturnas para vigias: registro de pontos com foto e GPS, três rondas por noite (00:00, 02:00 e 04:00), histórico completo para o administrador e gestão de postos e vigias.

> Esta pasta (`php-version/`) é um projeto independente. Ela **não** roda dentro do Base44 — copie os arquivos para um servidor com PHP e MySQL (Hostinger, KingHost, Locaweb, cPanel, VPS etc.).

## Requisitos

- PHP 8.1 ou superior com as extensões `pdo_mysql`, `fileinfo` e `mbstring` (já vêm na maioria das hospedagens)
- MySQL 5.7+ ou MariaDB 10.4+
- HTTPS recomendado: sem HTTPS o navegador do celular bloqueia o GPS

## Instalação (5 passos)

1. **Envie os arquivos** desta pasta para o servidor (ex.: `public_html/vigia/`).
2. **Crie o banco**: no phpMyAdmin, aba *Importar*, selecione `schema.sql` e execute. Isso cria o banco `vigia_check` com as tabelas `usuarios`, `postos` e `registros`.
3. **Configure a conexão** em `config.php`: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
4. **Crie o administrador**: abra `https://SEU-DOMINIO/vigia-check/install.php`, preencha nome, e-mail e senha. Depois **apague o `install.php`** do servidor.
5. **Entre** em `index.php?page=login`. No primeiro acesso, cadastre os postos (aba *Postos*) usando o botão “Usar minha localização atual”.

Permissões da pasta `uploads/`: `chmod 775` (ou `755` se o PHP rodar como o mesmo usuário dos arquivos). Ela precisa aceitar escrita.

## Como funciona

- **Turno**: começa às 18h e vai até a manhã seguinte. Registros feitos entre 00:00 e 11:59 são contabilizados na noite anterior.
- **Rondas**: cada posto precisa ser registrado três vezes — 00:00, 02:00 e 04:00. A aba *Ronda* mostra as três, e cada ponto concluído fica marcado com a hora.
- **Registro**: no celular, abre a câmera (`capture="environment"`), pega a latitude/longitude pelo GPS e calcula a distância até o posto. Fora do raio do posto o registro é aceito, mas fica marcado como “Fora do raio”.
- **Histórico**: o vigia vê os próprios registros; o administrador vê os de todos e filtra por vigia e por posto.
- **Equipe**: o administrador cria o login de cada vigia (nome, e-mail e senha inicial) e alterna entre *vigia* e *admin*.

## Papéis

| Papel | Pode |
| --- | --- |
| `vigia` | Registrar pontos das três rondas e ver o próprio histórico |
| `moderador` | Cadastrar e editar vigias e moderadores, incluindo nome, e-mail, senha e perfil; não pode criar, editar ou promover administradores |
| `admin` | Tudo do vigia + cadastrar/editar/excluir postos, ver todos os registros e gerenciar todos os perfis da equipe |

Em instalações existentes, execute uma vez o arquivo `migrations/001_add_moderador.sql` no banco antes de criar o primeiro moderador. Instalações novas já recebem o perfil pelo `schema.sql`.

## Estrutura de arquivos

```
php-version/
├─ index.php              roteador (?page=ronda|historico|postos|equipe|registrar|foto|login|sair)
├─ install.php            cria o primeiro administrador (apague após usar)
├─ config.php             conexão MySQL, fuso horário, upload
├─ schema.sql             tabelas do banco
├─ lib/
│  ├─ db.php              conexão PDO
│  ├─ auth.php            login, sessão, papéis
│  ├─ helpers.php         distância (Haversine), rondas, CSRF, flashes, formatação
│  └─ foto.php            entrega a foto somente ao dono ou ao admin
├─ views/                 telas (layout, login, ronda, registrar, historico, postos, equipe)
├─ actions/               POST: conta.php, posto.php, registro.php
├─ assets/app.css         tema escuro mobile-first
└─ uploads/               fotos (acesso direto bloqueado por .htaccess)
```

## Segurança aplicada

- Senhas com `password_hash` / `password_verify` (bcrypt)
- Sessões com `session_regenerate_id` no login
- Token CSRF em todos os formulários
- Todas as consultas com prepared statements (PDO)
- Fotos fora do acesso público, servidas por `index.php?page=foto&id=…` com verificação de dono/admin
- Upload validado por MIME real (`finfo`), extensão permitida e limite de 6 MB
- Postos e equipe só acessíveis ao papel `admin`

## Ajustes comuns

- **Raio padrão de cada posto**: campo “Raio (m)” ao cadastrar o posto.
- **Fuso horário**: em `config.php`, `date_default_timezone_set(...)`.
- **Limite de fotos**: `MAX_UPLOAD_BYTES` em `config.php` (e `upload_max_filesize` no PHP).
- **Redefinir senha de um vigia**: no phpMyAdmin, rode
  `UPDATE usuarios SET senha_hash = '<hash>' WHERE email = '...';`
  gerando o hash com `php -r "echo password_hash('NOVA_SENHA', PASSWORD_DEFAULT);"`.
