# VigiaCheck V2 — Sistema de controle de rondas em PHP

Aplicação web independente para controle de rondas de vigilância, desenvolvida em **PHP 8+, MySQL/MariaDB, HTML, CSS e JavaScript nativo**. A versão atual não depende de React, Base44, Node.js ou de um backend externo.

O sistema foi projetado para funcionar localmente no Apache/WAMP e pode ser acessado em:

```text
http://localhost/vigia_local/php-version/index.php
```

> Este README documenta a versão PHP V2 atualmente instalada em `php-version/`.

---

## 1. Visão geral da V2

O VigiaCheck permite que vigias registrem a passagem por postos de vigilância durante três rondas noturnas. Cada registro pode conter fotografia, localização GPS, precisão, distância até o posto e observação.

A V2 moderniza a aplicação para uso em celular e inclui:

- Interface responsiva com aparência de aplicativo mobile;
- Progressive Web App (PWA) instalável;
- Funcionamento offline com fila local no navegador;
- Sincronização automática quando a conexão retorna;
- Captura automática de câmera ao registrar um posto;
- Captura de localização com alta precisão e tentativas graduais;
- Compressão da fotografia no aparelho antes do envio;
- Avanço automático entre as rondas de `00:00`, `02:00` e `04:00`;
- Controle de postos concluídos e pendentes;
- Histórico administrativo;
- Exclusão de registros restrita ao administrador;
- Perfis `admin`, `moderador` e `vigia`;
- Cadastro de escala semanal para definir o responsável pelo turno;
- Alertas dentro do sistema e por e-mail para rondas atrasadas;
- Justificativa pelo vigia responsável;
- Resolução dos alertas por administrador ou moderador;
- Proteção CSRF nas ações POST;
- Validação de sessão e permissões no servidor;
- Tratamento seguro dos arquivos de foto;
- Migrações para instalações existentes.

---

## 2. Tecnologias utilizadas

A aplicação é exclusivamente PHP no backend e não utiliza React.

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8 ou superior |
| Banco de dados | MySQL ou MariaDB |
| Servidor local | Apache via WAMP |
| Frontend | HTML5, CSS3 e JavaScript nativo |
| PWA | Manifest Web App + Service Worker |
| Armazenamento offline | IndexedDB |
| Fotos | Câmera do dispositivo, Canvas e JPEG/PNG |
| Comunicação | Formulários POST e endpoint PHP JSON |
| Sessão | Sessão nativa do PHP |
| Segurança | PDO, prepared statements, CSRF e controle de perfil |

Não são necessários:

- React;
- Vite;
- Node.js para executar a aplicação;
- Base44 CLI;
- API externa;
- Servidor cloud;
- Aplicativo nativo separado.

---

## 3. Requisitos do ambiente

### Servidor local

- Windows com WAMP instalado;
- Apache ativo;
- MySQL ou MariaDB ativo;
- PHP 8.0 ou superior;
- Extensões PHP `PDO`, `pdo_mysql`, `mbstring`, `fileinfo` e `json` habilitadas;
- Navegador moderno no computador ou celular.

### Navegador do celular

Para câmera, GPS e instalação do PWA, recomenda-se:

- Google Chrome ou navegador baseado em Chromium;
- Microsoft Edge;
- Safari atualizado no iPhone.

A câmera e o GPS normalmente exigem HTTPS em produção. Em ambiente local, `http://localhost` é aceito pelos navegadores como contexto confiável. Para acessar pelo IP do computador em um celular, configure HTTPS ou use uma rede e configuração compatíveis com o navegador.

---

## 4. Estrutura do projeto

A aplicação PHP fica em `php-version/`:

```text
php-version/
├── actions/
│   ├── alerta.php              # Justificativa, resolução e salvamento da escala
│   ├── conta.php               # Login, criação e alteração de contas
│   ├── posto.php               # Cadastro e alteração de postos
│   ├── registro.php            # Recebimento de registros online
│   ├── registro_excluir.php    # Exclusão protegida de registros
│   └── registro_sync.php       # Recebimento de registros offline em JSON
├── assets/
│   ├── app.css                 # Estilos responsivos e mobile
│   ├── icon.svg                # Ícone do PWA
│   └── registro-offline.js     # Fila IndexedDB e sincronização
├── migrations/
│   ├── 001_add_moderador.sql
│   ├── 002_add_sync_id.sql
│   ├── 003_alertas_ronda.sql
│   └── 004_escala_semanal.sql
├── lib/
│   ├── alertas.php             # Alertas, e-mail e escala semanal
│   ├── auth.php                # Sessão, autenticação e permissões
│   ├── db.php                  # Conexão PDO com o banco
│   ├── foto.php                # Validação e armazenamento de fotos
│   └── helpers.php              # CSRF, rondas, datas e utilitários
├── scripts/
│   ├── cron-alertas.txt        # Exemplos de agendamento
│   └── monitorar_rondas.php    # Verificação automática das rondas
├── views/
│   ├── alertas.php             # Alertas e configuração da escala
│   ├── equipe.php              # Usuários e perfis
│   ├── historico.php           # Histórico administrativo
│   ├── layout.php              # Layout, menu e recursos PWA
│   ├── login.php               # Login
│   ├── postos.php              # Cadastro de postos
│   ├── registrar.php           # Câmera, GPS e envio do registro
│   └── ronda.php               # Lista e progresso da ronda
├── uploads/                    # Fotos dos registros
├── config.php                  # Banco, e-mail, fuso horário e limites
├── index.php                   # Roteador principal
├── install.php                 # Criação do primeiro administrador
├── manifest.webmanifest        # Configuração PWA
├── schema.sql                  # Schema completo
└── sw.js                       # Service Worker
```

---

## 5. Configuração do banco de dados

A configuração local atual está em `php-version/config.php`:

```php
const DB_HOST = 'localhost';
const DB_PORT = 3306;
const DB_NAME = 'vigia_check';
const DB_USER = 'root';
const DB_PASS = 'root';
```

Altere esses valores caso o MySQL/MariaDB do ambiente utilize outras credenciais. O arquivo contém informações específicas do ambiente local e não deve ser publicado com senhas reais.

O fuso horário configurado é:

```php
date_default_timezone_set('America/Sao_Paulo');
```

O banco é acessado por PDO com prepared statements.

---

## 6. Instalação no WAMP

### 6.1 Copiar o projeto

O projeto deve estar em:

```text
C:\wamp64\www\vigia_local
```

A aplicação PHP deve ficar em:

```text
C:\wamp64\www\vigia_local\php-version
```

### 6.2 Iniciar o WAMP

1. Abra o WampServer;
2. Inicie Apache;
3. Inicie MySQL/MariaDB;
4. Confirme que os serviços estão verdes;
5. Verifique se a porta do MySQL é `3306`.

### 6.3 Criar o banco

No phpMyAdmin, importe:

```text
php-version/schema.sql
```

O schema cria o banco `vigia_check` e as tabelas principais.

Se o banco já existir, aplique as migrações em ordem:

```text
migrations/001_add_moderador.sql
migrations/002_add_sync_id.sql
migrations/003_alertas_ronda.sql
migrations/004_escala_semanal.sql
```

A biblioteca de alertas também garante automaticamente as tabelas de alertas e escala quando a área é acessada, mas a execução das migrações é recomendada para manter o banco documentado e controlado.

### 6.4 Criar o primeiro administrador

Abra uma única vez:

```text
http://localhost/vigia_local/php-version/install.php
```

Informe nome, e-mail e senha com pelo menos seis caracteres. Depois de criar o administrador:

1. Entre em `http://localhost/vigia_local/php-version/index.php?page=login`;
2. Confirme o acesso;
3. Remova ou bloqueie o arquivo `install.php` em ambiente publicado.

### 6.5 Abrir o sistema

Use sempre:

```text
http://localhost/vigia_local/php-version/index.php
```

O endereço `http://localhost/vigia_local` não deve ser usado como substituto do painel do WAMP. A aplicação foi organizada para iniciar explicitamente pelo arquivo `php-version/index.php`, sem redirecionar o endereço raiz do WAMP.

---

## 7. Perfis e permissões

### Administrador (`admin`)

Pode:

- Acessar o painel completo;
- Cadastrar, alterar e desativar usuários;
- Criar administradores, moderadores e vigias;
- Cadastrar e alterar postos;
- Visualizar todo o histórico;
- Excluir registros de ronda;
- Configurar a escala semanal;
- Justificar alertas;
- Resolver alertas;
- Consultar registros de todos os vigias.

### Moderador (`moderador`)

Pode:

- Acessar a equipe;
- Cadastrar e alterar moderadores e vigias;
- Alterar nome, e-mail, senha e dados permitidos;
- Configurar a escala semanal;
- Visualizar e resolver alertas;
- Justificar alertas;
- Acompanhar o histórico conforme as regras administrativas.

Não pode:

- Criar ou alterar um administrador;
- Transformar outro usuário em administrador;
- Excluir registros, função reservada ao administrador.

### Vigia (`vigia`)

Pode:

- Acessar suas rondas;
- Registrar postos com foto e localização;
- Trabalhar temporariamente offline;
- Sincronizar registros quando a conexão voltar;
- Visualizar alertas relacionados ao seu usuário;
- Justificar o alerta da ronda atribuída a ele.

---

## 8. Rondas e progresso

A configuração atual possui três rondas:

| Código | Horário |
|---:|---:|
| 1 | 00:00 |
| 2 | 02:00 |
| 3 | 04:00 |

Uma ronda só é considerada concluída quando todos os postos ativos possuem registro para a data e o número da ronda.

A tela da ronda apresenta:

- Cartão circular de progresso;
- Quantidade de postos concluídos;
- Quantidade de postos pendentes;
- Indicação visual de posto concluído;
- Botão de registro;
- Seleção automática da primeira ronda incompleta.

Depois que todos os postos da ronda atual são registrados, o sistema avança automaticamente para a próxima ronda, sem exigir que o usuário selecione manualmente `02:00` ou `04:00`.

A data de trabalho noturna é calculada pela função `turno_data()`. Registros feitos depois da meia-noite ainda podem pertencer à noite iniciada no dia anterior, mantendo as três rondas no mesmo turno operacional.

---

## 9. Registro de posto: câmera e GPS

Ao tocar em **Registrar ponto**:

1. A tela de registro é aberta;
2. O navegador tenta abrir a câmera automaticamente;
3. O usuário captura ou confirma a fotografia;
4. O navegador coleta a localização;
5. O sistema calcula a distância até o posto;
6. O registro é enviado ou colocado na fila offline.

### GPS em aparelhos com sinal fraco

A captura utiliza `watchPosition()` com alta precisão, espera por uma posição melhor e possui tentativas graduais. Caso a precisão ideal não seja obtida, o sistema utiliza a melhor posição disponível, informando a precisão quando possível.

Para melhorar o resultado:

- Ative a localização do celular;
- Permita localização precisa para o navegador;
- Evite economizador extremo de bateria;
- Aguarde alguns segundos em áreas abertas;
- Confirme que o posto possui latitude, longitude e raio configurados corretamente.

---

## 10. Funcionamento offline e sincronização

A V2 possui uma fila offline no navegador com IndexedDB.

Quando não há internet:

- A fotografia é reduzida no navegador;
- O registro recebe um `sync_id` único;
- Os dados são armazenados localmente;
- A tela mostra a quantidade de registros pendentes;
- O vigia pode continuar a ronda;
- O Service Worker mantém os recursos essenciais disponíveis.

Quando a conexão retorna:

- O JavaScript tenta sincronizar automaticamente;
- O Service Worker pode solicitar sincronização em segundo plano;
- Cada item é enviado ao endpoint `actions/registro_sync.php`;
- O PHP valida sessão, CSRF, posto, foto e coordenadas;
- A transação grava o registro no MySQL;
- O `sync_id` impede duplicidade caso o envio seja repetido;
- O item é removido da fila somente após confirmação do servidor.

A compressão da imagem ocorre no aparelho por Canvas, reduzindo o tamanho sem alterar significativamente a qualidade visual necessária para conferência.

---

## 11. Escala semanal

A V2 possui a tabela `escala_semanal`. O administrador ou moderador configura o responsável por dia da semana na tela:

```text
http://localhost/vigia_local/php-version/index.php?page=alertas
```

A escala pode ser configurada assim:

| Dia | Responsável |
|---|---|
| Segunda-feira | Vigia 1 |
| Terça-feira | Vigia 1 |
| Quarta-feira | Vigia 1 |
| Quinta-feira | Vigia 1 |
| Sexta-feira | Vigia 1 |
| Sábado | Vigia 2 |
| Domingo | Vigia 2 |

Os nomes reais das contas são selecionados na tela. A escala vale para as três rondas do turno daquele dia.

A rotina de alertas segue esta prioridade:

1. Responsável ativo definido na escala semanal;
2. Primeiro vigia que registrou um ponto, apenas como fallback se não houver escala;
3. `Nenhum vigia identificado`, caso não exista escala nem registro.

O e-mail informa se o responsável foi encontrado pela escala semanal ou pelo primeiro registro.

> A data considerada é a `turno_data` do sistema. Isso mantém as rondas `00:00`, `02:00` e `04:00` da mesma noite associadas ao mesmo dia operacional.

---

## 12. Alertas de rondas não concluídas

O sistema verifica cada ronda após uma tolerância de 35 minutos:

- Ronda de `00:00`: verificação a partir de `00:35`;
- Ronda de `02:00`: verificação a partir de `02:35`;
- Ronda de `04:00`: verificação a partir de `04:35`.

Se houver postos ativos sem registro, é criado um alerta na tabela `alertas_ronda` contendo:

- Data do turno;
- Número da ronda;
- Responsável;
- Lista de postos pendentes;
- Quantidade de pendências;
- Mensagem;
- Status;
- Justificativa;
- Usuário que justificou;
- Usuário que resolveu;
- Horários de criação, justificativa, resolução e envio de e-mail.

Cada combinação de data e ronda possui alerta único. Assim, o sistema não gera vários alertas idênticos para a mesma ronda.

### Status

- `aberto`: alerta aguardando justificativa ou resolução;
- `justificado`: o responsável informou o motivo;
- `resolvido`: administrador ou moderador encerrou o alerta.

### Destinatários

O e-mail é enviado aos usuários ativos com perfil:

- `admin`;
- `moderador`.

O envio usa a função nativa `mail()`. Em Windows/WAMP, é necessário configurar um MTA ou SMTP no PHP para que o e-mail realmente seja entregue. O alerta continua disponível dentro do sistema mesmo quando o e-mail não estiver configurado.

---

## 13. Agendamento do monitoramento

O PHP não verifica rondas continuamente sozinho. A rotina precisa ser executada por um agendador.

### Linux/produção

O arquivo `scripts/cron-alertas.txt` contém exemplos de cron. O modelo recomendado é executar:

```bash
35 0 * * * /usr/bin/php /caminho/vigia_local/php-version/scripts/monitorar_rondas.php --ronda=1
35 2 * * * /usr/bin/php /caminho/vigia_local/php-version/scripts/monitorar_rondas.php --ronda=2
35 4 * * * /usr/bin/php /caminho/vigia_local/php-version/scripts/monitorar_rondas.php --ronda=3
```

Ou executar o script sem `--ronda` para verificar as rondas cujo horário limite já foi atingido:

```bash
/usr/bin/php /caminho/php-version/scripts/monitorar_rondas.php
```

### Windows/WAMP

Use o **Agendador de Tarefas do Windows** para executar o PHP CLI nos horários correspondentes. O executável normalmente fica em uma pasta semelhante a:

```text
C:\wamp64\bin\php\php8.x.x\php.exe
```

Configure como argumentos:

```text
C:\wamp64\www\vigia_local\php-version\scripts\monitorar_rondas.php --ronda=1
```

Crie tarefas equivalentes para as rondas 2 e 3. O monitor exige `PHP_SAPI === 'cli'` e não deve ser aberto pelo navegador.

---

## 14. Banco de dados

As principais tabelas são:

- `usuarios`: contas, perfis, status e autenticação;
- `postos`: postos, coordenadas, raio e ordem;
- `registros`: registros de ronda, foto, GPS, precisão e sincronização;
- `alertas_ronda`: alertas de rondas incompletas;
- `escala_semanal`: responsável por dia da semana.

Campos importantes de `registros`:

- `sync_id`: identificador único para sincronização offline;
- `posto_id`;
- `usuario_id`;
- `ronda`;
- `ronda_horario`;
- `latitude` e `longitude`;
- `precisao_metros`;
- `distancia_metros`;
- `dentro_raio`;
- `foto`;
- `observacao`;
- `turno_data`;
- `criado_em`.

Restrições de unicidade impedem registros duplicados para o mesmo posto, usuário, turno e ronda, além de impedir o reprocessamento do mesmo `sync_id`.

---

## 15. Segurança

A V2 aplica as seguintes medidas:

- Senhas armazenadas com `password_hash()`;
- Login baseado em sessão PHP;
- Regeneração de sessão após login;
- Tokens CSRF para ações POST;
- Prepared statements PDO;
- Validação de permissões no servidor, não apenas na interface;
- Exclusão de registro somente para administrador;
- Bloqueio de moderador para criação/alteração de administrador;
- Validação de extensão e tamanho de fotos;
- Proteção do diretório de uploads com `.htaccess`;
- Duplicidade offline controlada por `sync_id`;
- Transações no recebimento dos registros pendentes;
- Ações de alerta protegidas por login e CSRF.

Em produção:

- Troque a senha padrão do banco;
- Use HTTPS;
- Configure SMTP autenticado;
- Remova `install.php`;
- Não publique `config.php` com credenciais reais;
- Faça backup do banco e da pasta `uploads/`;
- Restrinja permissões de escrita ao diretório de uploads;
- Mantenha PHP e MySQL atualizados.

---

## 16. Correções e melhorias aplicadas na V2

A versão V2 corrige e moderniza os seguintes pontos:

1. A aplicação foi consolidada em PHP, sem dependência de React ou Base44.
2. A conexão foi ajustada para o ambiente local `localhost`, banco `vigia_check`, usuário `root`, porta `3306`.
3. O acesso oficial foi definido como `/vigia_local/php-version/index.php`.
4. O roteamento foi separado por páginas e ações PHP.
5. O layout foi adaptado para telas pequenas e uso com toque.
6. Foi criado o manifest e o Service Worker do PWA.
7. Foi adicionada instalação do sistema na tela inicial do celular.
8. Foi incluído indicador de conexão offline.
9. A tela da ronda passou a exibir progresso circular.
10. A próxima ronda é selecionada automaticamente após a conclusão.
11. A câmera é acionada automaticamente no registro.
12. O GPS passou a utilizar alta precisão, observação de posição e fallback.
13. As fotografias são comprimidas antes do armazenamento/envio.
14. Registros sem conexão passaram a ser armazenados no IndexedDB.
15. A sincronização automática foi integrada ao retorno da conexão.
16. O endpoint JSON offline foi criado com transação e proteção contra duplicidade.
17. O perfil moderador foi criado com limites administrativos.
18. A exclusão de registros ficou restrita ao administrador.
19. O monitor de rondas atrasadas foi criado.
20. A tolerância de alerta foi fixada em 35 minutos.
21. Os postos pendentes passaram a aparecer no alerta.
22. Os alertas passaram a ter status, justificativa e resolução.
23. Administradores e moderadores recebem os alertas por e-mail, quando o SMTP está configurado.
24. Foi criada a escala semanal para os responsáveis de segunda a domingo.
25. A escala semanal passou a ter prioridade sobre a identificação pelo primeiro registro.
26. Foram adicionadas migrações para o moderador, sincronização, alertas e escala.
27. Todos os arquivos PHP alterados foram validados com `php -l`.

---

## 17. Testes rápidos

### Sintaxe PHP

No PowerShell, dentro de `php-version`:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

O resultado esperado é `No syntax errors detected` para todos os arquivos.

### Acesso local

```text
http://localhost/vigia_local/php-version/index.php
http://localhost/vigia_local/php-version/index.php?page=login
http://localhost/vigia_local/php-version/index.php?page=ronda
http://localhost/vigia_local/php-version/index.php?page=alertas
```

### Teste de offline

1. Entre como vigia;
2. Abra uma ronda;
3. Desative temporariamente a rede do celular;
4. Registre um posto;
5. Confirme o indicador de pendências;
6. Reative a rede;
7. Aguarde a sincronização;
8. Confirme o registro no histórico.

### Teste de permissões

- Teste a exclusão com administrador;
- Confirme que moderador não consegue excluir;
- Confirme que moderador não consegue promover usuário a administrador;
- Configure a escala como administrador ou moderador;
- Confirme que o vigia visualiza apenas os alertas relacionados a ele.

### Teste de alerta

Para testar manualmente a rotina no terminal, use o PHP CLI:

```powershell
php C:\wamp64\www\vigia_local\php-version\scripts\monitorar_rondas.php --ronda=1
```

A rotina exige postos ativos e verifica os registros da `turno_data` atual. Em ambiente de testes, valide o banco antes de executar o comando para não criar alertas reais.

---

## 18. Solução de problemas

### “Banco de dados indisponível”

Confira:

1. MySQL/MariaDB está iniciado no WAMP;
2. A porta está correta;
3. O banco `vigia_check` existe;
4. `DB_USER` e `DB_PASS` em `config.php` estão corretos;
5. O schema foi importado;
6. O PHP possui `pdo_mysql` habilitado.

### Erro de acesso ao banco

O erro `Access denied for user` indica credencial incorreta ou usuário sem permissão. Ajuste o `config.php` ou crie/conceda permissões ao usuário no MySQL.

### Câmera não abre

Verifique:

- Permissão de câmera no navegador;
- Uso de `localhost` ou HTTPS;
- Câmera não está sendo usada por outro aplicativo;
- Acesso à página pelo endereço correto;
- Compatibilidade do navegador com `getUserMedia()`.

### GPS impreciso

Verifique a permissão de localização precisa, o GPS do aparelho, a visibilidade do céu e as coordenadas do posto. O sistema salva a precisão disponível para facilitar a análise.

### E-mail não chega

A função `mail()` depende de MTA/SMTP configurado no servidor. No WAMP, configure o mecanismo de envio ou use um SMTP da hospedagem. O alerta interno não depende do envio do e-mail.

### Registros offline não sincronizam

Confirme:

- O navegador voltou a ficar online;
- A sessão do vigia ainda está válida;
- O Service Worker foi registrado;
- A fotografia está dentro do limite de 6 MB;
- O endpoint `actions/registro_sync.php` está acessível;
- O banco está disponível;
- Não há erro no console do navegador.

### O alerta mostra “nenhum vigia identificado”

Configure a escala semanal em:

```text
index.php?page=alertas
```

Sem escala e sem qualquer registro na ronda, o sistema não consegue identificar um responsável.

---

## 19. Operação recomendada

Antes de iniciar os turnos:

1. Cadastre os postos com coordenadas e raio;
2. Cadastre os dois vigias como usuários ativos;
3. Configure a escala de segunda a sexta e de sábado a domingo;
4. Teste câmera e GPS em cada aparelho;
5. Instale o PWA no celular;
6. Confirme o funcionamento offline;
7. Configure o agendador do monitor de alertas;
8. Configure SMTP se os e-mails forem necessários;
9. Faça backup periódico do banco e das fotos.

Durante a operação:

- O vigia deve manter a localização ativa;
- O vigia deve aguardar a confirmação do registro;
- Registros pendentes devem ser sincronizados quando a rede retornar;
- Administrador e moderador devem acompanhar a área de alertas;
- A escala deve ser atualizada antes de trocas de plantão.

---

## 20. Licença e manutenção

Este diretório contém o código da aplicação VigiaCheck V2 em PHP. Preserve a estrutura atual, mantenha as migrações versionadas e faça backup antes de alterar o schema ou as regras de ronda.

Para novas alterações:

1. Edite os arquivos dentro de `php-version/`;
2. Atualize o schema ou crie uma nova migração;
3. Execute a validação de sintaxe PHP;
4. Teste login, permissões, ronda, offline e alertas;
5. Atualize este README se o comportamento do sistema mudar.
