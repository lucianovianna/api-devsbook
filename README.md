# Devsbook API

O Devsbook é uma API de rede social com cadastro e autenticação, perfis de
usuário, feed, posts de texto e fotos, likes, comentários, seguidores e busca
de usuários.

Criei este projeto em 2021, quando era desenvolvedor júnior e estava tendo meu
primeiro contato com Laravel. Ele foi uma oportunidade de praticar a construção
de uma API e os fluxos de uma rede social.

Hoje estou retomando a aplicação como um laboratório prático de System Design
e como parte do meu portfólio. A ideia é evoluir a partir de problemas observados
no próprio sistema: medir o comportamento, estudar as alternativas, implementar
uma solução e registrar o resultado. O [roadmap](docs/system-design-roadmap.md)
organiza os estudos de performance, concorrência, cache, processamento assíncrono,
escalabilidade e observabilidade. O andamento e os limites do trabalho atual
ficam em [docs/current-phase.md](docs/current-phase.md).

## Stack

| Tecnologia | Uso no projeto |
| --- | --- |
| PHP 8.5 e Laravel 13 | Implementação da API |
| PostgreSQL 18 | Persistência dos dados |
| Redis 8 e Predis | Serviço e cliente disponíveis para os estudos com Redis |
| JWT (`tymon/jwt-auth`) | Autenticação da API |
| Intervention Image com GD | Processamento de fotos, avatares e capas |
| Docker e Docker Compose | Ambiente local de execução |
| PHPUnit | Testes de regressão |
| Pint e PHPStan/Larastan | Estilo de código e análise estática |
| GitHub Actions | Verificações em pushes e pull requests |

As versões exatas das dependências estão no `composer.lock`; as imagens do
ambiente estão fixadas por digest em `compose.yaml` e no Dockerfile.
Atualmente, o cache e as sessões usam arquivos, a fila é síncrona e as mídias
ficam em `public/media`. Redis ainda não participa dos fluxos da aplicação.

## Ambiente local

### Pré-requisitos

- Git;
- Docker Engine;
- Docker Compose com suporte a `--wait`.

PHP, Composer, Redis e `psql` são executados em containers. Os comandos abaixo
partem da raiz do repositório. No Linux, o wrapper `bin/dev` preserva o UID/GID
do usuário nos arquivos gerados.

### Configuração inicial

Copie o exemplo de configuração, construa o runtime e instale as dependências:

```bash
test -f .env || cp .env.example .env
./bin/dev build app
./bin/dev run --rm app composer install --no-interaction --prefer-dist
```

Em uma instalação nova, gere as chaves da aplicação e do JWT:

```bash
./bin/dev run --rm app php artisan key:generate
./bin/dev run --rm app php artisan jwt:secret --force
```

Se o `.env` já estiver configurado, preserve as chaves existentes. Execute esses
comandos apenas para as chaves que ainda estiverem vazias.

Aplique as migrations, carregue os dados de demonstração e suba os serviços:

```bash
./bin/dev run --rm app php artisan migrate --seed
./bin/dev up -d --wait
```

Verifique a API e o Redis:

```bash
curl http://localhost:8000/api/ping
./bin/dev exec -T redis redis-cli ping
```

As respostas esperadas são `{"pong":true}` e `PONG`.

### Serviços e configuração

| Serviço | Acesso |
| --- | --- |
| API | `http://localhost:8000` |
| PostgreSQL | `127.0.0.1:5434`, banco `devsbook` |
| Redis | `redis:6379`, acessível apenas na rede do Compose |

O PostgreSQL local usa usuário `devsbook` e senha `devsbook-local`, exclusivos
desse ambiente de desenvolvimento. O Compose fornece a conexão aos containers.
Redis usa Predis e não tem persistência configurada.

Se as portas estiverem ocupadas:

```bash
APP_PORT=8001 DB_PORT_FORWARD=5435 ./bin/dev up -d --wait
```

Depois da configuração inicial, `./bin/dev up -d --wait` sobe o ambiente.
Também é possível usar `docker compose up -d --wait`; nos comandos diretos,
o UID/GID padrão é 1000. O servidor Artisan é destinado ao desenvolvimento local.

### Dados de demonstração

O seed cria três usuários, quatro posts de texto, três relações, dois likes e
dois comentários. Ana segue Bruno e Carla, permitindo consultar um feed com
paginação e interações.

| Email | Senha inicial |
| --- | --- |
| `ana@example.test` | `devsbook-demo` |
| `bruno@example.test` | `devsbook-demo` |
| `carla@example.test` | `devsbook-demo` |

Para executar novamente:

```bash
./bin/dev exec -T app php artisan db:seed
```

O seed aceita apenas os ambientes `local` e `testing`. Pode ser repetido e
preserva usuários existentes, incluindo alterações de perfil e senha. Não limpa
tabelas nem gera arquivos de upload. Os posts são identificados por autor,
tipo e texto; editar o texto permite que uma execução posterior recrie o original.

### Encerrar e consultar o banco

Para consultar o banco com o ambiente em execução:

```bash
./bin/dev exec postgres psql -U devsbook -d devsbook
```

Para encerrar os serviços:

```bash
./bin/dev down
```

`down` preserva o volume do banco; `down -v` apaga os dados desse ambiente.
A mídia permanece em `public/media` no checkout.

## Testes e qualidade de código

Execute as verificações com o ambiente em execução:

```bash
./bin/dev exec -T app composer validate --strict
./bin/dev exec -T app composer check-platform-reqs
./bin/dev exec -T app composer check
```

`composer check` executa Pint (PSR-12), PHPStan/Larastan nível 5 e a suíte de
testes. Incompletos, warnings e casos risky também fazem a execução falhar.
Os comandos podem ser usados separadamente:

```bash
./bin/dev exec -T app composer lint
./bin/dev exec -T app composer analyse
./bin/dev exec -T app composer test
./bin/dev exec -T app composer format
```

`lint` apenas verifica o estilo; `format` aplica as correções. A análise cobre
`app`, `database`, `routes` e `bootstrap/app.php`.

Os testes usam o banco separado **`devsbook_test`**, criado na primeira
inicialização de um volume vazio. O helper confere o banco efetivo antes de
limpar o schema, e cada cenário reverte sua transação. Os dados de demonstração
permanecem em `devsbook`.

O [workflow de CI](.github/workflows/ci.yml) usa o mesmo Compose e os comandos
do README para instalar dependências, configurar chaves temporárias, aplicar
migrations com seed e verificar código, testes, HTTP e conexão Redis.

## Endpoints

As rotas usam o prefixo `/api`. Cadastro, login, ping e `/api/401` são públicos.
Nas demais, envie o token no header `Authorization: Bearer <token>`.

| Método | Rota | Entrada |
| --- | --- | --- |
| GET | `/api/ping` | — |
| GET | `/api/401` | — |
| POST | `/api/auth/login` | `email`, `password` |
| POST | `/api/auth/logout` | — |
| POST | `/api/auth/refresh` | — |
| POST | `/api/user` | `name`, `email`, `password`, `birthdate` |
| PUT | `/api/user` | `name`, `email`, `birthdate`, `city`, `work`, `password`, `password_confirm` |
| GET | `/api/user` | — |
| GET | `/api/user/{id}` | — |
| POST | `/api/user/avatar` | Arquivo `avatar` |
| POST | `/api/user/cover` | Arquivo `cover` |
| GET | `/api/feed` | Query `page` |
| POST | `/api/feed` | `type=text/photo`, `body`, arquivo `photo` |
| GET | `/api/user/feed` | Query `page` |
| GET | `/api/user/{id}/feed` | Query `page` |
| GET | `/api/user/photos` | Query `page` |
| GET | `/api/user/{id}/photos` | Query `page` |
| POST | `/api/user/{id}/follow` | — |
| GET | `/api/user/{id}/followers` | — |
| POST | `/api/post/{id}/like` | — |
| POST | `/api/post/{id}/comment` | `txt` |
| GET | `/api/search` | Query `txt` |

Envie `Accept: application/json`. Uploads usam `multipart/form-data`.
A paginação começa em zero. Like e follow alternam o estado a cada chamada.

## Documentação do projeto

- [Roadmap de System Design](docs/system-design-roadmap.md): objetivos e sequência dos estudos.
- [Trabalho atual](docs/current-phase.md): andamento, entregas e limites de escopo.
- [Learning log](docs/learning-log.md): aprendizados e lacunas identificadas.
- [Ambiente e verificações](docs/architecture/phase0-environment.md): decisões, validações e limites da CI.
- [Testes de regressão](docs/architecture/legacy-regression-tests.md): cobertura e isolamento.
- [Migração Laravel/PHP](docs/architecture/laravel13-migration.md) e [validação PostgreSQL 18](docs/architecture/postgresql18-validation.md): histórico da modernização.
- [Ambiente legado](docs/architecture/legacy-environment.md): reprodução do Laravel 8 em checkout separado, com seu próprio lock e dependências.
