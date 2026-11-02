# Devsbook API

## Ambiente local — Laravel 13 / PHP 8.5

Pré-requisitos: Docker Engine e Docker Compose. PHP, Composer e `psql` locais
não são necessários. O wrapper `bin/dev` preserva o UID/GID do usuário Linux.

```bash
test -f .env || cp .env.example .env
./bin/dev build
./bin/dev run --rm app composer install --no-interaction --prefer-dist
```

Somente na configuração inicial, gere as chaves:

```bash
./bin/dev run --rm app php artisan key:generate
./bin/dev run --rm app php artisan jwt:secret --force
```

Preserve as chaves de um ambiente já configurado. Depois:

```bash
./bin/dev run --rm app php artisan migrate --seed
./bin/dev up -d
curl http://localhost:8000/api/ping
./bin/dev exec -T app php artisan test --without-tty
./bin/dev exec -T app composer check-platform-reqs
```

A API fica em `http://localhost:8000` e usa PostgreSQL 18.6, validado com
Laravel 13/PHP 8.5. Redis e CI continuam como próximas entregas da Fase 0.
O seeder atual não insere dados; a suíte usa factories pequenas por cenário.

O Compose seleciona `devsbook` para desenvolvimento e cria `devsbook_test`
na primeira inicialização do volume. Os testes conferem o banco efetivo antes
de limpar **somente `devsbook_test`** e revertem as transações de cada cenário.
Os defeitos LEGACY-001/002 foram corrigidos antes do upgrade e seus testes
permanecem na suíte. Consulte a [migração](docs/architecture/laravel13-migration.md)
e os [testes de regressão](docs/architecture/legacy-regression-tests.md).

O banco local usa usuário `devsbook` e senha `devsbook-local`, exclusivos de
desenvolvimento, publicado em `127.0.0.1:5434`. Para consultar:

```bash
./bin/dev exec postgres psql -U devsbook -d devsbook
./bin/dev exec postgres psql -U devsbook -d devsbook_test
```

Se as portas estiverem ocupadas:

```bash
APP_PORT=8001 DB_PORT_FORWARD=5435 ./bin/dev up -d
```

`./bin/dev down` preserva o volume. Não use `down -v` para reiniciar: ele apaga
os bancos desse ambiente. A mídia continua em `public/media` no checkout.
O volume atual é `devsbook_postgres18`, montado em `/var/lib/postgresql`.
Os volumes anteriores `devsbook_php85-postgres` (PostgreSQL 14 da migração
Laravel/PHP) e `devsbook-legacy_legacy-postgres` (legado) são preservados;
dados antigos não são copiados automaticamente. Ao atualizar um checkout
existente, execute as migrations no cluster novo antes de usar a API:

```bash
./bin/dev up -d postgres
./bin/dev run --rm app php artisan migrate --seed
./bin/dev up -d
```

A validação reconstruiu o schema em um cluster vazio; não é um procedimento
de migração de dados existentes. Consulte
[postgresql18-validation.md](docs/architecture/postgresql18-validation.md)
para versões, isolamento e resultados. O servidor Artisan é destinado ao
desenvolvimento local.

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







