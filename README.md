# Devsbook API

## Ambiente local — Fase 0

Stack validada: PHP 8.5.12, Laravel 13.39.0, PostgreSQL 18.6 e Redis 8.10.3.
Pré-requisitos: Git, Docker Engine e Docker Compose com suporte a `--wait`.
PHP, Composer, Redis e `psql` locais não são necessários. O wrapper `bin/dev`
preserva o UID/GID no Linux; nos comandos diretos do Compose, o padrão é 1000.

Na primeira configuração:

```bash
test -f .env || cp .env.example .env
./bin/dev build app
./bin/dev run --rm app composer install --no-interaction --prefer-dist
```

Somente se as respectivas chaves ainda estiverem vazias no `.env`, gere-as:

```bash
./bin/dev run --rm app php artisan key:generate
./bin/dev run --rm app php artisan jwt:secret --force
```

Preserve as chaves de um ambiente já configurado. Depois:

```bash
./bin/dev run --rm app php artisan migrate --seed
./bin/dev up -d --wait
curl http://localhost:8000/api/ping
./bin/dev exec -T redis redis-cli ping
```

Após instalar dependências, configurar chaves e aplicar migrations, tanto
`./bin/dev up -d --wait` quanto `docker compose up -d --wait` sobem app,
PostgreSQL e Redis. A API fica em `http://localhost:8000`; o banco é publicado
somente em `127.0.0.1:5434`. Redis é acessível na rede do Compose, sem porta
publicada no host. O servidor Artisan é destinado ao desenvolvimento local.

Redis usa Predis na aplicação e está disponível para os próximos estudos.
Cache permanece em arquivo (`CACHE_STORE=file`), sessão em arquivo e fila
síncrona. Nesta fase, Redis não armazena dados de domínio e não tem persistência
configurada. Para conferir a conexão pelo Laravel:

```bash
./bin/dev exec -T app php artisan tinker --execute="echo Illuminate\Support\Facades\Redis::connection()->ping();"
```

### Dados de demonstração

`migrate --seed` cria três usuários, quatro posts de texto, três relações,
dois likes e dois comentários. Execute novamente com:

```bash
./bin/dev exec -T app php artisan db:seed
```

O seed pode ser repetido e preserva usuários existentes, incluindo alterações
de perfil/senha. Não limpa tabelas. Identifica os posts de demonstração pelo
autor/tipo/texto; editar o texto permite que uma execução posterior recrie o
post original. A execução é permitida apenas em `local` ou `testing`.

| Email de demonstração | Senha inicial |
| --- | --- |
| `ana@example.test` | `devsbook-demo` |
| `bruno@example.test` | `devsbook-demo` |
| `carla@example.test` | `devsbook-demo` |

Ana segue Bruno e Carla, permitindo consultar um feed com paginação e interações.
As mídias padrão existentes são utilizadas; o seed não gera arquivos de upload.

### Verificações de código e testes

```bash
./bin/dev exec -T app composer validate --strict
./bin/dev exec -T app composer check-platform-reqs
./bin/dev exec -T app composer check
```

`composer check` executa Pint (PSR-12), PHPStan/Larastan nível 5 e toda a suíte.
Os testes retornam erro também para incompletos, warnings e casos risky.
Comandos separados:

```bash
./bin/dev exec -T app composer lint
./bin/dev exec -T app composer analyse
./bin/dev exec -T app composer test
./bin/dev exec -T app composer format
```

`format` aplica correções de estilo; `lint` apenas verifica. A análise cobre
`app`, `database`, `routes` e `bootstrap/app.php`, sem baseline de erros.
O stub de TTL JWT corrige metadados da dependência para o adaptador existente;
seu propósito está documentado em [phase0-environment.md](docs/architecture/phase0-environment.md).

A suíte usa **somente `devsbook_test`**, criado na primeira inicialização de
um volume vazio. O helper confere ambiente, conexão e nome efetivo do banco
antes de limpar o schema; cada cenário reverte sua transação. Dados do seed de
desenvolvimento ficam em `devsbook`. Consulte os
[testes de regressão](docs/architecture/legacy-regression-tests.md).

### Operação e dados persistentes

Credenciais locais do PostgreSQL: usuário `devsbook`, senha `devsbook-local`,
exclusivos de desenvolvimento. Para consultar os bancos:

```bash
./bin/dev exec postgres psql -U devsbook -d devsbook
./bin/dev exec postgres psql -U devsbook -d devsbook_test
```

Se as portas estiverem ocupadas:

```bash
APP_PORT=8001 DB_PORT_FORWARD=5435 ./bin/dev up -d --wait
```

`./bin/dev down` preserva o volume. `down -v` apaga os bancos desse ambiente.
A mídia permanece em `public/media` no checkout. O volume atual é
`devsbook_postgres18`, montado em `/var/lib/postgresql`.
Os anteriores `devsbook_php85-postgres` e `devsbook-legacy_legacy-postgres`
continuam preservados; dados antigos não são copiados automaticamente.
A [validação PostgreSQL 18](docs/architecture/postgresql18-validation.md)
reconstruiu o schema em um cluster vazio e não é uma migração de dados reais.

### CI

[.github/workflows/ci.yml](.github/workflows/ci.yml) roda em pushes e pull requests.
Usa o mesmo runtime Compose e lock, gera chaves temporárias, aplica migrations
com seed, executa verificações do Composer, `composer check`, ping HTTP e
conexão Redis pelo Laravel. O banco `devsbook_test` permanece isolado do seed.
O job remove apenas seus próprios containers/volumes ao terminar.

A sequência do job foi reproduzida localmente em uma cópia sem `.env`, `vendor`
ou volumes anteriores. A primeira execução hospedada no GitHub depende de
publicar estes arquivos; não houve push nesta etapa.

## Referência do legado

O runtime antigo (`compose.legacy.yaml`, `bin/legacy`) está documentado em
[legacy-environment.md](docs/architecture/legacy-environment.md). Ele exige
os arquivos Laravel 8 e seu lock, disponíveis no commit `672c10f`, em um
checkout separado. Não execute PHP 8.0 com o `vendor`/lock modernos.
Se o container antigo estiver usando a porta 8000, pare apenas o app com
`./bin/legacy stop app` antes de subir o ambiente novo. Preserve seu volume.

## Endpoints


- '^' acessivel sem login
<br>
 


POST ^api/auth/login (email, password)

POST api/auth/logout

POST api/auth/refresh

<hr>

POST ^api/user (name, email, password, birthdate)

PUT api/user (name, email, birthdate, city, work, password, password_confirm)

<hr>

POST api/user/avatar (avatar)

POST api/user/cover (cover)

<hr>

GET api/feed (page)

GET api/user/feed (page)

GET api/user/:id/feed (page)

GET api/user/photos (page)

GET api/user/:id/photos (page)

POST api/user/:id/follow

GET api/user/:id/followers

<hr>

GET api/user

GET api/user/:id

<hr>

POST api/feed (type=text/photo, body, photo)

<hr>

POST api/post/:id/like

POST api/post/:id/comment (txt)

<hr>

GET api/search (txt)







