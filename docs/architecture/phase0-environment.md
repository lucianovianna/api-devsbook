# Ambiente completo da Fase 0

Data: 2026-11-01. Escopo: última entrega local da Fase 0.

## Inicialização manual

Os três serviços têm `restart: on-failure` explícito no `compose.yaml`.
O Docker pode iniciar com o WSL, mas essa política não inicia os containers
do Devsbook junto com o daemon. Se o processo encerrar com código de erro,
o Docker tenta reiniciar o container, sem limite de tentativas configurado.
Essa política não reinicia processos travados nem containers apenas `unhealthy`.
Para ligar o ambiente, execute
`./bin/dev up -d`; para parar, `./bin/dev stop`.
Fechar o terminal mantém os containers em execução quando iniciados com `-d`.
Parar os containers preserva o volume do PostgreSQL.

## Problema e decisões

Após modernizar Laravel/PHP e validar PostgreSQL 18, faltavam um Redis
acessível pela aplicação, dados pequenos para exercitar o domínio, verificações
de código reproduzíveis. Esta entrega completa essas
peças e mantém os contratos validados pelas etapas anteriores.

Redis resolve, nesta fase, a preparação e verificação de conectividade do
ambiente que será usado nos estudos posteriores. Não é uma solução de cache
de domínio ainda. Sessão continua em arquivo, cache em arquivo e fila síncrona.
O serviço é acessível na rede do Compose e pelo host em `127.0.0.1:16379`,
sem dados persistentes. Não há worker ou processamento assíncrono.

Foi escolhido Predis como cliente PHP para executar a conexão no mesmo runtime
sem acrescentar uma extensão nativa. A alternativa era compilar PhpRedis na
imagem. A escolha simplifica a instalação atual; diferenças de desempenho
entre clientes não foram medidas e não motivaram a decisão. O driver pode ser
reavaliado se um problema concreto exigir isso.

## Versões e arquivos

| Componente | Versão/decisão |
| --- | --- |
| PHP / Laravel / PostgreSQL | 8.5.12 / 13.39.0 / 18.6, referências anteriores |
| Redis | 8.10.3, imagem oficial `redis:8-alpine` fixada por digest no Compose |
| Predis | 3.7.0, dependência de execução no lock |
| Larastan / PHPStan | 3.13.2 / 2.3.4, dependências de desenvolvimento |
| Pint | 1.32.1, preset PSR-12 |
| Fluxo de validação | `composer check` local antes de integrar na `main` |

- `compose.yaml`: app, PostgreSQL e Redis; healthchecks nos serviços de dados;
  app depende da disponibilidade dos dois.
- `bin/dev`: preserva UID/GID e encaminha comandos ao Compose.
- `.env.example`: configuração pública do ambiente; segredos permanecem vazios.
- `composer.json`: `lint`, `format`, `analyse`, `test` e `check`.
- `README.md`: instalação, dados de demonstração, verificações e operação.

O volume PostgreSQL 18 e os dois volumes anteriores permanecem preservados.
PostgreSQL é publicado em `127.0.0.1:15432` e Redis em `127.0.0.1:16379`.
As portas podem ser alteradas com `DB_PORT_FORWARD` e `REDIS_PORT_FORWARD`.
Na rede interna, continuam `postgres:5432` e `redis:6379`. Redis usa
`--save "" --appendonly no` porque não armazena estado da aplicação nesta fase.

## Seed pequeno

`DevelopmentSeeder`, chamado pelo `DatabaseSeeder`, cria:

- Ana, Bruno e Carla (`@example.test`), com senha inicial `devsbook-demo`;
- quatro posts de texto, permitindo verificar paginação;
- três follows, dois likes e dois comentários.

São dados de demonstração, não uma carga de benchmark. Não são geradas imagens
nem arquivos; os perfis usam as mídias padrão. O seed só aceita `local` ou
`testing`. Não há chamadas a truncate/fresh nem exclusão de dados existentes.

A execução é transacional e pode ser repetida sequencialmente: usuários são
identificados por email, posts por autor/tipo/texto e interações por seus
participantes/post/texto. Alterações de perfil e senha de usuários existentes
são preservadas. Editar o texto de um post de demonstração permite que uma
execução posterior recrie o original. Não se afirma idempotência concorrente;
nenhuma constraint nova foi introduzida nesta entrega.

`DevelopmentSeedTest` verifica contagens após duas execuções, preservação de
outro usuário, nome/senha editados, login/feed/relações e bloqueio em produção.
Usa o mesmo banco de testes isolado dos demais cenários.

## Lint e análise estática

`composer lint` verifica estilo com Pint/PSR-12; `composer format` aplica as
correções. A adoção inicial normalizou 15 arquivos, principalmente espaços,
imports não usados e chaves de estruturas de controle. Não houve substituição
do domínio ou alteração de contratos para atender ao formatter.

`composer analyse` executa Larastan/PHPStan no nível 5 sobre `app`, `database`,
`routes` e `bootstrap/app.php`. Não há baseline nem lista de erros ignorados.
O nível e o escopo são explícitos; isso não representa prova de ausência de
bugs ou análise dos testes/configurações pelo PHPStan.

A primeira análise encontrou cinco erros. Os ajustes foram:

- explicitar o guard JWT no refresh para tipar a chamada à biblioteca;
- remover estado de usuário nunca lido em `SearchController`, mantendo `auth:api`;
- corrigir o PHPDoc da classe de model na factory;
- fornecer `stubs/jwt-claims.stub` para a propriedade/setter de TTL nullable.

O stub só fornece metadados à análise, não é carregado pela aplicação. Ele
reflete o valor `null` aceito pelo adaptador já validado na migração; a
dependência anota TTL como inteiro, mas sua payload factory tem um ramo para
omitir `exp` quando o TTL é nulo. O comportamento continua coberto pelos testes
de TTL nulo e de expiração finita. Não houve alteração em `vendor`.

`composer check` executa lint, análise e testes. A suíte falha também em
incompletos, warnings e risky. O helper continua conferindo o banco efetivo
antes de `RefreshDatabase` limpar `devsbook_test`.

## Fluxo de validação local

O autor optou por executar as verificações localmente antes de levar alterações
à `main`, pois mantém o projeto sozinho. O workflow de GitHub Actions foi
removido para simplificar esse fluxo. Não há validação remota pendente.

Com o ambiente em execução:

```bash
./bin/dev exec -T app composer validate --strict
./bin/dev exec -T app composer check-platform-reqs
./bin/dev exec -T app composer check
```

O último comando reúne Pint, Larastan/PHPStan e testes. As mesmas verificações
foram reproduzidas em instalação limpa. O ambiente Docker e os comandos locais
continuam disponíveis.

Inicialmente foi implementado um workflow em pushes e pull requests, validado
com actionlint e com seus comandos reproduzidos localmente. Depois de observar
as execuções no GitHub, o autor decidiu adotar somente a validação local.

## Evidência de validação

- `composer validate --strict` e `composer check-platform-reqs`: aprovados.
- Pint: 60 arquivos verificados, sem pendências após a adoção de estilo.
- Larastan/PHPStan nível 5: sem erros.
- Suíte atual: **102 testes e 602 assertions**, sem falhas/incompletos/warnings/risky.
  Os 100 cenários anteriores continuam; dois testes novos cobrem o seed.
- Ordem aleatória, seed `20261101`: mesmos 102 testes e 602 assertions.
- Redis 8.10.3: PONG via `redis-cli` e via conexão Laravel/Predis.
- Seed executado duas vezes: 3 usuários, 4 posts, 3 relações, 2 likes e
  2 comentários. O teste também preservou um usuário e edições existentes.
- Cópia limpa sem `.env`, `vendor` ou caches, com volume PostgreSQL novo e
  projeto Compose exclusivo: build, instalação pelo lock, chaves, migrations,
  seed, `up --wait`, lint/análise/testes, Composer, HTTP e Redis aprovados.
- Nessa cópia, login de Ana e feed com 4 posts/2 por página passaram via HTTP.
  Após a suíte, as contagens de desenvolvimento permaneceram 3/4/3/2/2 e
  todas as tabelas de domínio do banco de testes ficaram vazias.
- Na entrega inicial, o workflow passou no actionlint; posteriormente foi removido por decisão do autor.
- Containers e volume temporários removidos após o ensaio. O ambiente principal
  continua disponível, com os dados pequenos do seed. Mídia versionada intacta.
- `git diff --check`: aprovado.

Não há benchmark nesta entrega. O seeder é pequeno e os testes não exercitam
carga, concorrência, falhas distribuídas ou todas as combinações de entrada.

## Próxima etapa do laboratório

A Fase 0 está concluída com verificações locais. A Fase 1 introduz volumes e
medições de baseline, mediante a próxima solicitação do autor. Nenhum
experimento da Fase 1 foi iniciado.

## Referências

- [Larastan — instalação e níveis](https://github.com/larastan/larastan).
- [PHPStan — stub files](https://phpstan.org/user-guide/stub-files).
- [Predis — cliente PHP](https://github.com/predis/predis).
