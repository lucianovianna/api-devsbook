# Validação com PostgreSQL 18

Data: 2026-11-01. Escopo: quarta etapa da Fase 0.

## Objetivo e limite

Verificar se a aplicação modernizada funciona com a versão alvo do banco,
preservando contratos e schema. A referência anterior era PostgreSQL 14.24,
com Laravel 13.39.0/PHP 8.5.12: 100 testes e 585 assertions aprovados.
Essa referência foi reexecutada imediatamente antes da troca do banco.

Não há benchmark nem afirmação de ganho de performance. Esta etapa não inclui
tuning, novos índices, constraints, Redis, seed de desenvolvimento ou CI.
Também não valida transferência de um banco com dados reais.

## Ambiente e escolha

| Componente | Valor verificado |
| --- | --- |
| Aplicação | Laravel 13.39.0 / PHP 8.5.12, sem mudança de dependências |
| Referência anterior | PostgreSQL 14.24, todas as cinco tabelas de domínio vazias |
| Banco atual | PostgreSQL 18.6, imagem oficial Debian Bookworm |
| Imagem | `postgres:18-bookworm@sha256:5c4783a8cc3f0074912164f94dfa1be4e043438f0f707cf968d7af9a0d31a2c8` |
| Volume atual | `devsbook_postgres18`, montado em `/var/lib/postgresql` |
| Diretório efetivo | `/var/lib/postgresql/18/docker` |
| Desenvolvimento / testes | `devsbook` / `devsbook_test` |
| Porta local do banco | `127.0.0.1:5434`, configurável por `DB_PORT_FORWARD` |

Conceitos relevantes: versão de formato do cluster, volume persistente,
migrations de schema versus migração de dados, introspecção de catálogo e
isolamento do banco de testes.

O PostgreSQL 18 não abre diretamente o cluster 14. As alternativas para
preservar dados em um upgrade incluem dump/restore e `pg_upgrade`. Nesta
validação foi escolhido um cluster vazio, reconstruído pelas migrations,
pois o banco de referência não tinha dados de domínio. Isso isola a
compatibilidade da aplicação sem introduzir uma transferência de dados.
O custo é que o novo ambiente começa vazio; isto não substitui um ensaio
com dados reais quando houver necessidade de migrá-los.

A imagem oficial 18 usa um diretório com a versão dentro de
`/var/lib/postgresql`. Por isso, `compose.yaml` passou a montar um volume
novo nesse diretório, sem reutilizar `/var/lib/postgresql/data` do 14.
O script existente de inicialização criou `devsbook_test` no volume novo.
Nenhum `PGDATA` antigo foi reaproveitado.

Os volumes `devsbook_php85-postgres` e `devsbook-legacy_legacy-postgres`
permanecem disponíveis. O primeiro foi inspecionado com `pg_controldata`
montado somente para leitura: cluster 14 em estado `shut down`. Não houve
remoção dos volumes nem importação de seus dados. A referência Compose do 14
permanece no commit `ccd453d`, caso seja necessário consultá-la.

## Verificações realizadas

### Conexão, migrations e seeders

- Conexão efetiva da aplicação com PostgreSQL 18.6.
- `php artisan migrate --seed` em `devsbook`, inicialmente vazio.
- Em `devsbook_test`, com ambiente `testing` e nome do banco conferido antes
  de executar: migrate/seed, rollback de uma migration e reaplicação/seed.
- Após o rollback, restava somente a tabela de controle `migrations`.
  Após reaplicar, voltaram as cinco tabelas de domínio.
- O `DatabaseSeeder` executa, mas continua vazio. As factories usadas pelos
  testes funcionam; ainda falta a base de desenvolvimento da entrega de seeders.

### Schema e catálogo

Foram comparados metadados obtidos de `information_schema.columns`,
`pg_constraint` e `pg_indexes` nos dois servidores, incluindo `migrations`:

| Verificação | PostgreSQL 14.24 | PostgreSQL 18.6 |
| --- | --- | --- |
| Tabelas | 6 | 6 |
| Colunas: tipos, tamanhos, defaults e nulabilidade | 30 | mesmas 30 |
| Primary keys | 6 | mesmas 6 |
| Índices | 6 | mesmos 6 |
| Colunas NOT NULL | 27 | mesmas 27 |
| Entradas NOT NULL em `pg_constraint` | 0 | 27 |

A diferença no total de entradas de `pg_constraint` (6 → 33) é uma mudança
esperada do catálogo do PostgreSQL 18: `NOT NULL` passa a ter registros do
tipo `n`. A comparação verificou separadamente as mesmas 27 colunas não
nulas, em vez de interpretar os novos registros como alteração do domínio.
As demais constraints têm as mesmas definições. Não foram adicionadas
foreign keys, constraints únicas ou índices; as limitações conhecidas de
consistência do legado permanecem para as fases correspondentes.

Uma consulta para distinguir os tipos de constraint:

```sql
SELECT con.contype, count(*)
FROM pg_constraint con
JOIN pg_class rel ON rel.oid = con.conrelid
JOIN pg_namespace n ON n.oid = rel.relnamespace
WHERE n.nspname = 'public'
GROUP BY con.contype
ORDER BY con.contype;
```

### Suíte, isolamento e HTTP

- `phpunit --fail-on-incomplete --fail-on-warning --fail-on-risky`:
  **100 testes, 585 assertions**, sem falhas, avisos ou incompletos.
- Ordem aleatória, seed `20261101`: mesmos 100 testes e 585 assertions.
- Os testes existentes não foram modificados para passar no banco novo.
- Um registro sentinela em `devsbook` permaneceu presente durante a suíte;
  todas as cinco tabelas de domínio de `devsbook_test` ficaram vazias ao final.
- HTTP real: cadastro, login, perfil, email próprio inalterado, follow e lista
  de seguidos, criação/leitura de feed, like, comentário, busca, avatar, capa,
  post com foto e listagem de fotos, refresh e logout com revogação.
- Registros e arquivos criados nessa verificação foram removidos. Ao final,
  ambos os bancos ficaram sem dados de domínio; a mídia versionada permaneceu
  intacta.
- `up -d --force-recreate --wait postgres` preservou o registro sentinela,
  comprovando que o diretório de dados correto estava no volume persistente.
  O sentinela foi removido após a conferência.

## Reproduzir

Use os comandos de configuração inicial do [README](../../README.md).
Em um checkout já configurado, com o PostgreSQL 18 disponível:

```bash
./bin/dev up -d postgres
./bin/dev run --rm app php artisan migrate --seed
./bin/dev up -d
./bin/dev exec -T postgres psql -X -U devsbook -d devsbook -c 'SELECT version();'
./bin/dev exec -T app php vendor/bin/phpunit --fail-on-incomplete --fail-on-warning --fail-on-risky
./bin/dev exec -T app php vendor/bin/phpunit --order-by=random --random-order-seed=20261101 --fail-on-incomplete --fail-on-warning --fail-on-risky
```

`devsbook_test` é reservado à suíte e pode ser limpo por `RefreshDatabase`.
O helper verifica ambiente e banco efetivo antes da limpeza. `devsbook`
permanece separado. O volume PostgreSQL 14 é preservado, mas não é usado
pelo Compose atual. Não executar rollback/fresh no banco de desenvolvimento
para reproduzir os testes de migration descritos acima.

## Resultado e próximos passos

PostgreSQL 18.6 validado para os cenários cobertos. A única alteração de
execução está no Compose: imagem/digest, nome do volume e ponto de montagem.
Não foi necessária adaptação no código de domínio, migrations ou testes.

A Fase 0 continua em andamento: disponibilizar Redis, criar seed de
desenvolvimento e adicionar CI. Performance e concorrência continuam nas
fases próprias. A suíte não cobre todas as combinações de entrada nem prova
compatibilidade com um conjunto de dados de produção.

## Referências

- [Imagem oficial PostgreSQL — PGDATA](https://hub.docker.com/_/postgres).
- [PostgreSQL 18 — migração e diferenças](https://www.postgresql.org/docs/18/release-18.html).
- [Catálogo pg_constraint no PostgreSQL 18](https://www.postgresql.org/docs/18/catalog-pg-constraint.html).
