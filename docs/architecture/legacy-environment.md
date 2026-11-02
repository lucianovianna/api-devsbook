# Ambiente reproduzível do legado

Data: 2026-11-01. Escopo: primeira etapa da Fase 0.

Este documento registra a referência anterior ao upgrade. Para repetir os
comandos abaixo, use o commit `672c10f` em um checkout separado, com seu
próprio `.env` e `vendor`. O checkout atual exige PHP 8.5 e usa `bin/dev`;
consulte [laravel13-migration.md](laravel13-migration.md). O runtime legado
não instala nem executa as dependências modernas.

## Problema

O checkout não tinha ambiente de execução documentado, dependências instaladas
ou Docker Compose. O PHP 8.3 local não satisfazia o lock e não tinha todas as
extensões necessárias. Precisávamos executar o legado antes de escrever os
testes de regressão e iniciar o upgrade.

## Decisão

Usar `compose.legacy.yaml`, com PHP e PostgreSQL em containers locais. O wrapper
`bin/legacy` seleciona esse Compose e, no Linux, preserva o UID/GID do usuário
nos arquivos criados no checkout.

Alternativas consideradas:

- Instalar PHP antigo e extensões na máquina: permitiria executar o legado,
  mas dependeria da configuração de cada desenvolvedor.
- Atualizar dependências para usar PHP moderno imediatamente: misturaria a
  preparação da referência de comportamento com o upgrade.
- Isolar o runtime compatível em Docker: escolhido por permitir reproduzir
  o ambiente mantendo `composer.json` e `composer.lock` intactos.

| Componente | Versão |
| --- | --- |
| PHP | 8.0.30, imagem CLI Bullseye |
| Laravel | 8.64.0, conforme o lock existente |
| Composer | 2.8.12 |
| PostgreSQL | 14.24 |
| PHPUnit | 9.5.10, conforme o lock existente |

As imagens base estão fixadas por digest. Os pacotes APT vêm do snapshot Debian
de 2023-11-20, correspondente à imagem PHP. O mirror corrente devolveu 404 para
pacotes Bullseye durante a construção. O snapshot fixa os índices e pacotes;
`check-valid-until=no` permite ler metadados arquivados, mantendo a verificação
das assinaturas. Referência: [Debian Snapshot](https://snapshot.debian.org/).

PHP 8.1 foi experimentado: instalação, migrations e os dois testes de exemplo
passaram, mas o cadastro falhou ao emitir JWT. A versão de `tymon/jwt-auth`
travada no lock tem `Claim::jsonSerialize()` sem a assinatura exigida no PHP 8.1,
e o Laravel transforma a depreciação em erro. PHP 8.0 permite executar esse código
sem atualizar ou editar a biblioteca.

A versão original do PostgreSQL não está registrada no projeto. PostgreSQL 14
é uma referência escolhida para executar o legado, não uma afirmação sobre o
banco original. PostgreSQL 18 será validado na etapa de modernização.

## Configuração

- A API está publicada em `127.0.0.1:8000`.
- PostgreSQL está publicado em `127.0.0.1:5433`.
- `devsbook` é o banco de desenvolvimento; `devsbook_test` é o banco de testes.
- As credenciais locais estão explícitas no Compose.
- `phpunit.xml` fixa `DB_DATABASE=devsbook_test` tanto nas variáveis de ambiente
  quanto em `$_SERVER`, sobrepondo a conexão de desenvolvimento do container.
- O script de inicialização cria o banco de testes somente na primeira
  inicialização de um volume vazio. Ele não recria bancos existentes.
- O volume `legacy-postgres` mantém os dados entre recriações dos containers.
- O checkout é montado no container; `public/media` permanece no host.
- `.env` é local e ignorado pelo Git. As chaves da aplicação e JWT são geradas
  na configuração inicial, sem incluí-las em arquivos versionados.
- `DB_CONNECTION` no exemplo foi corrigido de `postgres` para `pgsql`.
- JWT foi incluído no exemplo como `JWT_SECRET=` para tornar sua configuração
  explícita.

O `artisan serve` desta versão remove variáveis de ambiente do processo filho
quando o reload de `.env` está habilitado. Isso fazia migrations funcionarem
via CLI enquanto os endpoints tentavam conectar em `127.0.0.1:5432`. O comando
do serviço usa `--no-reload` para preservar a conexão fornecida pelo Compose.
Após mudar `.env`, execute `./bin/legacy restart app`. Mudanças nas variáveis do
Compose exigem `./bin/legacy up -d` para recriar o serviço.

## Operação

Os comandos de configuração, testes, consulta com `psql` e encerramento estão
no [README](../../README.md#referência-do-legado).

Não remover o volume para reiniciar a aplicação: `./bin/legacy down` seguido
de `./bin/legacy up -d` preserva o banco. `down -v` apaga os dados deste ambiente.

## Limites

Este runtime antigo é temporário e destinado à referência local do legado.
Ele não é a stack alvo nem uma configuração de produção. As vulnerabilidades
das dependências antigas ainda precisam ser tratadas na modernização.

Na preparação do ambiente não houve alteração em controllers, models, migrations
ou dependências. O seed atual continua vazio. A factory e o trait `HasFactory`
do model foram posteriormente ajustados na etapa dos
[testes de regressão](legacy-regression-tests.md). Redis não é necessário para executar o comportamento
atual e será disponibilizado na entrega do ambiente moderno.

Não há benchmark nesta entrega. Na preparação inicial do ambiente havia apenas
testes de exemplo, que não cobriam autenticação, feed ou interações.

## Validação realizada

- Construção da imagem com PHP 8.0.30 e instalação com `composer install`,
  sem alteração de `composer.json` ou `composer.lock`.
- `composer check-platform-reqs`: todos os requisitos passaram.
- `php artisan test --without-tty`: os dois testes existentes passaram.
- Migrations e seed executados em `devsbook`; o seeder atual não insere dados.
- Migration, rollback e reaplicação executados em `devsbook_test`.
- Verificação temporária via PHPUnit: conexão efetiva em `devsbook_test`,
  ambiente `testing` e geração de JPEG pelo GD; seis assertions passaram.
- HTTP: `/` retornou 200 e `/api/ping` retornou `{"pong":true}`.
- Verificação HTTP com usuário temporário: cadastro e login emitiram tokens;
  perfil autenticado e feed vazio responderam 200. O usuário foi removido.
- Configuração repetida em cópia sem `.env`, `vendor` ou caches gerados,
  usando projeto Compose e volume independentes. Build, instalação pelo lock,
  geração de chaves, migrations, testes e ping passaram. O ambiente de
  verificação foi removido; o ambiente principal permaneceu disponível.

As verificações temporárias dessa entrega não foram adicionadas à suíte.
Na etapa seguinte foram criados testes persistentes dos endpoints e ajustada a
factory de usuário; ver [testes de regressão](legacy-regression-tests.md).
