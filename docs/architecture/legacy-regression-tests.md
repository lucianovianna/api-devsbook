# Testes mínimos de regressão do legado

Data: 2026-11-01. Escopo: segunda etapa da Fase 0.

As seções abaixo registram a entrega inicial no legado. Depois, LEGACY-001/002
foram corrigidos antes do upgrade e a suíte foi migrada para Laravel 13/PHP 8.5.
O estado atual e os comandos modernos estão ao final deste documento.

## Objetivo

Registrar o comportamento HTTP e a persistência dos fluxos prioritários antes
de migrar Laravel/PHP. Estes testes são uma referência de compatibilidade,
não uma medição de performance ou uma cobertura completa da aplicação.

## Executar

Com o [ambiente do legado](legacy-environment.md) configurado:

```bash
./bin/legacy exec app php artisan test
./bin/legacy exec app php artisan test --filter=AuthenticationTest
```

Em execução sem TTY, como um script, use `exec -T` e acrescente `--without-tty`
ao comando `php artisan test`.

## Isolamento e fixtures

- `Tests\ApiTestCase` utiliza `RefreshDatabase`: aplica migrations em banco
  limpo uma vez por processo e reverte a transação de cada cenário.
- Antes da limpeza, exige ambiente `testing`, conexão PostgreSQL e consulta
  `current_database()`. A execução é interrompida se o banco efetivo não for
  `devsbook_test`, inclusive quando uma URL de conexão ou configuração em cache
  apontar para outro banco. Esse banco é reservado à suíte e será limpo.
- A factory de usuário fornece `birthdate`, usa o hash bcrypt existente para
  a senha `password` e não envia colunas ausentes no schema. `User` passou a
  usar `HasFactory`. As demais fixtures são pequenas e criadas por cenário.
- O PHPUnit fornece um segredo JWT exclusivo para testes e mantém a blacklist
  habilitada. A expiração é testada com TTL explícito de um minuto e avanço
  do relógio, sem mudar o TTL da aplicação.
- Tokens são assinados pela biblioteca real; autenticação e middleware não
  são simulados nem desabilitados.
- O helper `apiJson` descarta guards, token JWT em memória e instâncias de
  controllers entre requisições. Isso reproduz o ciclo de requisições HTTP
  independentes, pois o Laravel de testes reutiliza a aplicação no mesmo
  processo. A blacklist e os dados persistidos continuam disponíveis.
- Cada teste usa cache e sessão em memória, conforme o `phpunit.xml` existente.
- Uploads usam imagens reais criadas pelo GD e requests com arquivos. O helper
  `apiUpload` reinicia o estado HTTP como `apiJson`. `path.public` aponta para
  um diretório temporário exclusivo por teste, removido mesmo quando há falha.
  Não se usa `Storage::fake()` para interceptar gravações diretas em `public_path`.

## Cobertura

| Arquivo | Comportamentos |
| --- | --- |
| `AuthenticationTest` | Login com token utilizável, senha/email incorretos, validação, proteção das 18 rotas privadas, token inválido/expirado, logout com revogação, refresh com substituição e revogação, redirect para cliente sem Accept JSON. |
| `RegistrationTest` | Cadastro com hash e defaults persistidos, token utilizável, ausência de cada campo obrigatório, formatos inválidos e email duplicado sem criar outro usuário. |
| `UserReadTest` | Perfil próprio e de terceiro, URLs públicas, campos ocultos, relações, contagem de fotos e usuário inexistente. |
| `FeedTest` | Feed vazio, posts próprios/de seguidos, exclusão de terceiros, ordenação, páginas zero/um/além do total, autores, likes e comentários, feed por usuário e criação/validação de post de texto. |
| `LikeTest` | Toggle sequencial, contagem, preservação de likes de outros usuários, identidade entre tokens distintos e post inexistente. |
| `FollowTest` | Toggle sequencial, contagem, preservação de outros seguidores, rejeição de autofollow e usuário inexistente. |
| `ProfileUpdateTest` | Atualização completa/parcial, isolamento de identidade, campos omitidos, email de terceiro, formatos/tamanhos inválidos, troca de senha com login novo e rejeição da senha antiga, confirmação ausente/divergente sem persistência parcial. |
| `UploadTest` | Post com foto, avatar e capa em JPEG/PNG; dimensões, encoding real, arquivo persistido, URL consultada em perfil/fotos, identidade do proprietário, arquivo ausente/inválido sem gravações. |
| `CommentTest` | Criação por autor autenticado e leitura no feed, texto ausente/em branco/não string, post inexistente e ausência de persistência nos erros. |
| `SearchTest` | Busca por fragmento do nome, exclusão de não correspondentes, campos públicos, resultado vazio e termo ausente/em branco/não string. |
| `PhotosTest` | Rotas própria/de terceiro, filtro por autor e tipo, URLs, ordenação, paginação e lista vazia. |
| `RelationListTest` | Seguidores de entrada com campos públicos, listas vazias e usuário inexistente. A direção de `following` é uma pendência conhecida abaixo. |
| `KnownIssuesTest` | Reproduções de LEGACY-001 e LEGACY-002, sinalizadas como incompletas enquanto a falha específica existir. |

Os dois testes de exemplo originais foram preservados e o ping público ganhou
um teste. Todas as 22 rotas declaradas em `routes/api.php` são exercitadas;
isso não equivale a cobertura completa de código ou de combinações de entrada.

## Contratos preservados e pendências

- Validações e recursos inexistentes continuam retornando 400.
- Cadastro continua retornando 200 e JWT.
- Feed, perfil e toggles mantêm o envelope externo em array.
- Paginação começa em zero e retorna dois posts por página.
- O campo `likeCout` mantém a grafia atual. Corrigi-lo exige uma mudança
  explícita de contrato.
- Requests JSON não autenticados retornam 401 com `message`; requests sem
  Accept JSON redirecionam para `/api/401`, que retorna o erro legado.
- Dois problemas preexistentes foram reproduzidos e registrados em
  [legacy-known-issues.md](legacy-known-issues.md): email próprio inalterado
  rejeitado e direção incorreta da lista `following`. Os casos são incompletos,
  não testes aprovados de comportamento esperado.
- Posts com imagem mantêm filename sem extensão e o formato de entrada;
  avatares/capas convertem PNG e JPEG para JPEG. A migração da biblioteca deve
  preservar esses resultados ou documentar uma mudança explícita.
- Arquivos corrompidos, limites de tamanho de upload e todas as combinações
  possíveis de entrada ainda não estão cobertos. Não há alegação de cobertura
  total nem medição percentual de linhas/branches.
- Duplicatas concorrentes de likes/follows e ausência de constraints continuam
  pendentes das fases próprias. Os testes de toggle são sequenciais.

Controllers, rotas, migrations e dependências não foram alterados para fazer
os testes passarem. A mudança no model limita-se à habilitação de factories.

## Resultado da validação

Ambiente: Laravel 8.64.0, PHP 8.0.30 e PostgreSQL 14.24.

- A suíte inicial tinha 40 testes e 209 assertions aprovados, incluindo os dois
  testes originais. A ampliação cobre os fluxos comuns dos módulos restantes.
- Após a ampliação, `php artisan test --without-tty`: 95 testes passaram e dois
  ficaram incompletos por LEGACY-001/002, sem falhas inesperadas.
- PHPUnit com `--order-by=random --random-order-seed=20261101`: 97 casos,
  511 assertions e os mesmos dois incompletos. Não houve testes skipped/risky.
- Verificação de sintaxe PHP e `git diff --check` passaram.
- Os diretórios temporários de upload foram removidos após as execuções;
  não houve alteração na mídia versionada de `public/media`.
- Após a suíte, as fixtures não permaneceram no banco de testes. O banco de
  desenvolvimento continuou sem usuários, como antes da execução.

Esses resultados validam a execução no legado. A mesma suíte deverá passar
na stack moderna antes de concluir o upgrade.

## Após as correções e migração Laravel 13/PHP 8.5

Antes do upgrade, as duas correções passaram no Laravel 8/PHP 8.0:
97 testes e 514 assertions, sem incompletos. `KnownIssuesTest` mantém os
cenários e agora exige o comportamento correto, sem `markTestIncomplete`.

No Laravel 13.39.0/PHP 8.5.12, os mesmos cenários continuam passando.
Foram adicionados testes para TTL nulo do JWT, CORS e throttle: **100 testes,
585 assertions**, também em ordem aleatória com seed `20261101`.
Sem falhas, avisos ou incompletos.

```bash
./bin/dev exec -T app php artisan test --without-tty
./bin/dev exec -T app php vendor/bin/phpunit --fail-on-incomplete --fail-on-warning --fail-on-risky
```

A migração adaptou providers para atributos estáticos do PHPUnit 12,
`usePublicPath` para uploads e `flushController` para limpar middleware
calculado entre requests. As assertions anteriores foram preservadas.
Isolamento continua no PostgreSQL `devsbook_test`, com verificação da conexão
antes de limpar e transação revertida por cenário. Os limites de cobertura
e concorrência descritos acima permanecem. Detalhes em
[laravel13-migration.md](laravel13-migration.md).

## Validação com PostgreSQL 18

A mesma suíte passou no PostgreSQL 18.6, com Laravel 13.39.0/PHP 8.5.12:
100 testes e 585 assertions, sem falhas, avisos ou incompletos. A ordem
aleatória com seed `20261101` apresentou o mesmo resultado. Nenhum teste
ou assertion precisou ser alterado para trocar a versão do banco.

O banco de desenvolvimento manteve um registro sentinela durante os testes,
e as cinco tabelas de domínio de `devsbook_test` ficaram vazias ao final.
Rollback e reaplicação também foram verificados exclusivamente nesse banco.
O ambiente atual de `bin/dev` usa o volume PostgreSQL 18, separado dos anteriores.
Detalhes em [postgresql18-validation.md](postgresql18-validation.md).

## Encerramento local da Fase 0

A suíte passou a ter **102 testes e 602 assertions** após adicionar dois
cenários de seed: repetição/preservação e bloqueio fora de desenvolvimento/testes.
Os 100 casos anteriores continuam presentes. Pint/PSR-12 e análise nível 5
passaram; a ordem aleatória `20261101` manteve o resultado. A instalação limpa
reproduziu lint, análise, suíte, seed, HTTP e conexão Redis. O seed de `devsbook`
permaneceu intacto, com `devsbook_test` vazio após os testes.

`composer test` agora falha também em incompletos, warnings e risky. A CI
executa `composer check`, que inclui lint, análise e testes. Detalhes e limite
da validação remota em [phase0-environment.md](phase0-environment.md).
