# Devsbook API

## Ambiente local do legado — Fase 0

O ambiente está em [compose.legacy.yaml](compose.legacy.yaml). Ele mantém as
dependências do `composer.lock` e usa PHP 8.0 e PostgreSQL 14 para preparar os
testes de regressão antes da migração para a stack moderna.

Pré-requisitos: Docker Engine e Docker Compose. PHP, Composer e `psql` locais
não são necessários. No Linux, o wrapper `bin/legacy` usa o UID/GID do usuário
para que os arquivos gerados no repositório tenham o proprietário correto.

Na primeira execução, copie o exemplo somente se ainda não houver `.env`:

```bash
test -f .env || cp .env.example .env
./bin/legacy build
./bin/legacy run --rm app composer install --no-interaction --prefer-dist
./bin/legacy run --rm app php artisan key:generate
./bin/legacy run --rm app php artisan jwt:secret --force
./bin/legacy run --rm app php artisan migrate --seed
./bin/legacy up -d
```

Os comandos de geração de chaves são para a configuração inicial. Preserve as
chaves de um ambiente já configurado. A instalação usa o lock existente;
não execute `composer update` nesta etapa.

A API fica em `http://localhost:8000`. Verifique com:

```bash
curl http://localhost:8000/api/ping
./bin/legacy exec app php artisan test
./bin/legacy exec app composer check-platform-reqs
```

A suíte mínima de regressão usa JWT real e o banco `devsbook_test`. Ela cobre
autenticação, cadastro, leitura/edição de perfil e senha, feed, uploads, fotos,
comentários, busca, likes e relações. Dois problemas preexistentes estão
sinalizados como testes incompletos e registrados em
[legacy-known-issues.md](docs/architecture/legacy-known-issues.md). O banco de testes
é recriado na primeira execução de um teste de API e cada cenário usa uma
transação revertida ao terminar. Detalhes e limites estão em
[docs/architecture/legacy-regression-tests.md](docs/architecture/legacy-regression-tests.md).

O Compose fixa a conexão da aplicação no banco local `devsbook`, com usuário
`devsbook` e senha `devsbook-local`. O PostgreSQL é publicado somente em
`127.0.0.1:5433`. Essas credenciais são exclusivas deste ambiente de desenvolvimento.
O banco separado `devsbook_test` é criado na primeira inicialização do volume;
`phpunit.xml` seleciona esse banco durante os testes.

Para consultar os bancos usando o cliente incluído no container:

```bash
./bin/legacy exec postgres psql -U devsbook -d devsbook
./bin/legacy exec postgres psql -U devsbook -d devsbook_test
```

Se as portas estiverem ocupadas, use:

```bash
LEGACY_APP_PORT=8001 LEGACY_DB_PORT=5434 ./bin/legacy up -d
```

Para encerrar, execute `./bin/legacy down`; os dados
permanecem no volume. A mídia permanece no diretório `public/media` do checkout.

Este ambiente é temporário e destinado à execução local do legado. Redis e o
upgrade da stack entram nas próximas entregas.
Veja as decisões e limitações em
[docs/architecture/legacy-environment.md](docs/architecture/legacy-environment.md).

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







