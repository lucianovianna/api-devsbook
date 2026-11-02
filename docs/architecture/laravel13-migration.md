# Laravel 13 / PHP 8.5 — base limpa no mesmo repositório

Data: 2026-11-01. Escopo: terceira etapa da Fase 0.

## Problema e sequência

O Laravel 8 e seu lock dependiam de PHP 8.0 para executar os contratos existentes.
As correções LEGACY-001/002 foram aplicadas primeiro e validadas no runtime
antigo: **97 testes e 514 assertions**, sem incompletos. Depois foi integrada a
estrutura de uma aplicação Laravel 13 limpa no mesmo repositório. Nenhum
histórico foi reescrito e nenhum banco legado foi removido.

Conceitos envolvidos: bootstrap da aplicação, registro de providers, ciclo de
middleware e autenticação, resolução de dependências pelo Composer, contrato
HTTP versus implementação do framework e encoding de imagens.

A alternativa era adaptar incrementalmente a estrutura do Laravel 8. A base
limpa foi escolhida pelo autor e pelo roadmap para adotar o bootstrap atual
sem estudar cada upgrade intermediário. O custo é revisar explicitamente os
pontos de integração que antes eram configurados nos kernels e providers.

## Origem e dependências

Esqueleto oficial: ramo `13.x` de `laravel/laravel`, commit
`def7ce14811161f3c689a71ca8abc1369da44bd6`. O código foi obtido em diretório
temporário; não houve `create-project` sobre o checkout nem substituição do
schema pelas migrations padrão do esqueleto.

| Dependência | Decisão |
| --- | --- |
| PHP | `~8.5.0`, imagem CLI Bookworm fixada por digest; plataforma Composer 8.5.0 |
| Laravel | `^13.0`, estrutura limpa de bootstrap/configuração |
| JWT | `tymon/jwt-auth ^2.3`, mantendo guard, namespace e política atuais |
| Imagens | `intervention/image ^4.4`, GD direto via container da aplicação |
| Tinker | `^3.0`, compatível com o esqueleto atual |
| PHPUnit | `^12.5.12`, mesma família usada pelo esqueleto consultado |
| Collision / Faker / Mockery / Pint | versões compatíveis do esqueleto |
| Fruitcake Laravel CORS | removido; middleware de CORS nativo do Laravel |
| Sanctum | removido: não era utilizado; autenticação continua JWT |
| Ignition / Sail | removidos; configuração atual de erros e runtime Docker próprio |
| Guzzle | sem requisito direto: não é usado pelo domínio e integra o framework |

`composer.lock` registra as versões exatas resolvidas. Somente releases
estáveis são aceitos. Não foram instalados SDKs de IA, starter kits, pacotes
de frontend, serviços adicionais ou funcionalidades novas do framework.

Versões resolvidas e executadas: **PHP 8.5.12, Laravel 13.39.0,
JWT 2.3.1, Image 4.4.1, Tinker 3.0.2, PHPUnit 12.5.42,
Collision 8.9.5 e Pint 1.32.1**.

## Pontos de compatibilidade

- `bootstrap/app.php` registra rotas web/API/console, health check `/up`,
  redirect de autenticação e throttle. Providers ficam em `bootstrap/providers.php`.
  Kernels, providers vazios e middleware de passagem do Laravel 8 foram retirados.
- O controller base mantém `Illuminate\Routing\Controller` porque os controllers
  existentes declaram middleware no construtor. A identidade passa a ser obtida
  em um middleware de instância após `auth:api`, e não ao construir o controller.
- O guard padrão continua `api`/JWT. A configuração JWT foi preservada, incluindo
  blacklist, TTL e claims; não houve troca de estratégia de autenticação.
  `jwt-auth 2.3.1` converte TTL `null` em zero em `Claims\Factory::setTTL`,
  criando tokens já expirados. `App\Auth\JwtClaimFactory` preserva `null` e
  delega os valores finitos à implementação original. A binding mantém o
  rebinding da request. Um teste novo verifica ausência de `exp` e uso após
  um dia; o cenário de TTL de um minuto continua verificando rejeição após
  dois minutos. A alternativa de adotar TTL finito mudaria a política do
  produto e foi deixada para uma decisão explícita fora deste upgrade.
- O limite permanece em 60 requests/minuto por usuário autenticado ou IP.
  CORS mantém origem/métodos/headers públicos como no legado. Testes específicos
  verificam preflight e o limite efetivo depois da troca de bootstrap.
- Respostas de validação continuam 400, cadastro 200, envelope externo em array,
  paginação zero e campo `likeCout` continuam como antes. Requests sem Accept
  JSON continuam redirecionando para `/api/401`.
- Models e migrations de domínio foram preservados. Não foram adicionadas
  constraints, índices, cache de domínio, filas ou otimizações.
- Image v4 usa `decodePath`, `scale` e `cover`. Mantém largura 800 dos posts,
  avatar 200×200 e capa 850×310. Posts preservam encoding de entrada e nome sem
  extensão; o encoder de posts é escolhido pelo MIME real, pois o método
  `save()` v4 não determina formato pelo nome sem extensão. Avatar/capa
  continuam JPEG. Qualidade JPEG 90 e orientação automática
  desabilitada preservam opções do processamento antigo. Não se afirma igualdade
  binária entre versões da biblioteca.
- Traduções existentes foram movidas de `resources/lang` para `lang`.
- PHPUnit usa atributos `DataProvider` e métodos estáticos. As assertions dos
  cenários anteriores foram preservadas. O diretório público temporário de
  uploads usa `usePublicPath`, e o banco efetivo continua verificado antes
  de `RefreshDatabase` executar sua limpeza. O helper usa `flushController()`
  para limpar tanto o controller quanto seu middleware calculado entre
  requests, evitando closures ligadas à instância anterior no mesmo teste.
- Cache padrão continua em arquivo, sessão em arquivo e fila síncrona. Nomes
  atuais `CACHE_STORE` e `FILESYSTEM_DISK` aceitam fallback das variáveis antigas.

## Runtime e dados

`compose.yaml` e `bin/dev` executam PHP 8.5 com um PostgreSQL **14.24 separado**.
Manter o banco na versão de referência permite atribuir regressões à migração
Laravel/PHP. PostgreSQL 18 e Redis são a próxima entrega, não foram antecipados.

O volume `devsbook_php85-postgres` é exclusivo deste ambiente. O volume
`devsbook-legacy_legacy-postgres` permanece intacto. Nenhum dado foi transferido
entre eles. O container de app antigo foi parado para liberar a porta 8000,
pois seu checkout compartilhado agora usa as dependências modernas.

Para repetir o legado, use um checkout separado do commit `672c10f`, com `.env`,
`vendor` e projeto Compose próprios. PHP 8.0 não executa o lock moderno.
O Compose também fornece diretórios XDG graváveis em `/tmp` para que Tinker
funcione com o UID do host, sem tentar gravar em `/.config/psysh`.
O [README](../../README.md) contém instalação, geração inicial de chaves,
migrations, execução, testes e portas. O servidor Artisan é apenas local.

## Validação

- Build da imagem PHP 8.5.12 e resolução do lock sem ignorar requisitos.
- `composer validate --strict` e `composer check-platform-reqs`: aprovados.
- `composer audit`: nenhum advisory de vulnerabilidade encontrado na consulta.
- PHPUnit com `--fail-on-incomplete --fail-on-warning --fail-on-risky`:
  **100 testes e 585 assertions**, sem falhas, avisos ou incompletos.
- Ordem aleatória, seed `20261101`: mesmos 100 testes e 585 assertions.
- Os 97 cenários anteriores foram preservados, incluindo LEGACY-001/002;
  três cenários novos verificam TTL nulo, CORS e throttle.
- Migrations e seeder executados no banco novo de desenvolvimento.
- HTTP real: cadastro, login, perfil, email próprio inalterado, lista de
  seguidos, refresh com revogação do token anterior e logout com revogação
  aprovados. Usuários e relações temporários removidos; desenvolvimento e
  testes ficaram sem usuários ao final.
- Tinker executado com o UID local e diretórios XDG do Compose.
- `route:list --path=api --except-vendor`: 22 rotas da API.
- `config:cache` e `route:cache` gerados; ping HTTP 200 e health `/up` 200;
  caches removidos após a verificação para manter o desenvolvimento local.
- Instalação em cópia sem `.env`, `vendor` ou caches locais, com projeto
  Compose, portas e volume exclusivos: build, `composer install` pelo lock,
  geração inicial de chaves, migrations/seed, **100 testes/585 assertions**
  e ping HTTP aprovados. Containers e volume temporários removidos.
- `php artisan test --without-tty`: 100 testes aprovados (585 assertions).
- Sintaxe PHP dos arquivos de app/bootstrap/config/database/routes/tests e
  `git diff --check`: aprovados.

Não há benchmark nesta entrega. Os testes verificam comportamento, não
performance nem todas as combinações de entrada. Limites anteriores de
concorrência, uploads corrompidos e cobertura permanecem explícitos em
[legacy-regression-tests.md](legacy-regression-tests.md).

## Referências consultadas

- [Releases Laravel 13](https://laravel.com/framework/docs/releases).
- [Esqueleto oficial](https://github.com/laravel/laravel/tree/def7ce14811161f3c689a71ca8abc1369da44bd6).
- [JWT 2.3.1 e constraints](https://github.com/tymondesigns/jwt-auth/blob/2.3.1/composer.json).
- [Image v2 em fim de vida](https://image.intervention.io/v2/getting-started/installation).
- [Código Image 4.4.1](https://github.com/Intervention/image/tree/3a47838f2e0f4ec6c8c9b43a5d73ac985b975830).
