# Fase atual — 0: Modernização da fundação

## Objetivo

Modernizar a base técnica do Devsbook antes de iniciar os experimentos principais de System Design.

A intenção desta fase é criar uma aplicação moderna, reproduzível, testável e estável, **sem antecipar otimizações ou mudanças arquiteturais das fases seguintes**.

## Estado atual

Fase 0 concluída, com ambiente reproduzido em instalação limpa.
Por decisão do autor, lint, análise e testes são executados localmente antes
de levar alterações à `main`. O workflow automático foi removido.
Detalhes em `docs/architecture/phase0-environment.md`.

O projeto foi criado originalmente em 2021 e atualmente possui uma base semelhante a:

- Laravel 13, migrado a partir do Laravel 8;
- PHP 8.5 em runtime Docker;
- PostgreSQL 18.6 no ambiente local, validado com a suíte de regressão;
- Redis 8.10.3 disponível, com cliente Predis; cache/sessão/fila mantêm os drivers anteriores;
- `QUEUE_CONNECTION=sync`;
- `SESSION_DRIVER=file`;
- armazenamento local de mídia em `public/media`;
- suíte de regressão no PostgreSQL cobrindo as rotas da API, além dos testes padrão do Laravel;
- autenticação via JWT;
- endpoints de autenticação, usuário, feed, posts, likes, comentários, follow/unfollow, busca e upload de mídia.

## Meta técnica

Modernizar o projeto para uma stack estável atual.

Referência planejada:

- PHP 8.5;
- Laravel 13;
- PostgreSQL 18;
- Redis;
- Docker / Docker Compose.

As versões exatas devem ser confirmadas antes da execução do upgrade.

## Estratégia preferida

1. preservar o repositório e seu histórico;
2. garantir comportamento mínimo com testes;
3. modernizar a base Laravel/PHP;
4. ajustar dependências incompatíveis;
5. validar autenticação e endpoints existentes;
6. modernizar PostgreSQL e ambiente local;
7. adicionar Docker Compose;
8. disponibilizar verificações locais de código e testes;
9. encerrar a fase com aplicação funcional e reproduzível.

Não é objetivo desta fase estudar upgrades sequenciais Laravel 8 → 9 → 10 → 11 → 12 → 13, salvo se surgir uma necessidade técnica concreta.

## Entregáveis

### 1. Auditoria inicial

Antes de modificar código:

- identificar dependências atuais;
- identificar dependências incompatíveis com a stack alvo;
- identificar recursos depreciados;
- identificar pontos de risco;
- identificar endpoints críticos;
- definir testes mínimos necessários antes do upgrade.

Gerar, se útil:

`docs/architecture/legacy-baseline.md`

### 2. Testes mínimos de regressão

Prioridade:

1. autenticação;
2. criação de usuário;
3. leitura de usuário;
4. feed;
5. like;
6. follow/unfollow.

Não é necessário atingir alta cobertura nesta fase.

Status: suíte mínima implementada e validada no legado. A cobertura e os
limites estão em `docs/architecture/legacy-regression-tests.md`. Reexecutar
essa suíte a cada etapa da migração.

A cobertura foi ampliada para uploads, atualização de perfil/senha,
comentários, busca, fotos e listagem de relações. Dois problemas preexistentes
foram registrados e depois corrigidos no legado,
antes do upgrade. Seus cenários continuam na suíte como testes normais.
Ver `docs/architecture/legacy-known-issues.md`.

### 3. Upgrade Laravel/PHP

Base limpa Laravel 13 integrada no mesmo repositório, com runtime PHP 8.5
e dependências atualizadas. Detalhes, versões resolvidas e validação em
`docs/architecture/laravel13-migration.md`. A migração Laravel/PHP foi
validada primeiro no PostgreSQL 14. O Compose agora usa PostgreSQL 18;
Redis foi disponibilizado na última entrega do ambiente.

Validar:

- bootstrap;
- providers;
- middleware;
- autenticação;
- rotas;
- validações;
- models;
- migrations;
- filesystem;
- JWT;
- testes.

Evitar refatorações arquiteturais não necessárias durante o upgrade.

### 4. PostgreSQL

PostgreSQL 18.6 validado no Laravel 13.39.0/PHP 8.5.12: migrations,
rollback/reaplicação isolados, seeder atual, schema, suíte completa e HTTP.
Resultado: 100 testes/585 assertions, também em ordem aleatória. Não foi
necessário alterar código de domínio ou migrations.

O volume anterior PostgreSQL 14 foi preservado. O novo cluster não importa
dados automaticamente. O seeder pequeno foi entregue posteriormente, conforme a seção 6.
Ver `docs/architecture/postgresql18-validation.md`.

Execução com PostgreSQL moderno validada.

Validar:

- conexão;
- migrations;
- tipos utilizados;
- constraints existentes;
- seeders;
- comportamento dos endpoints.

Não iniciar ainda tuning sistemático ou criação de índices de performance.

### 5. Docker

Ambiente local reproduzível entregue com:

```text
app
postgres
redis
```

### 6. Seeders e factories

Base inicial entregue: três usuários, quatro posts, três follows,
dois likes e dois comentários. O seed é repetível e preserva dados existentes;
usuários de demonstração têm a senha inicial `devsbook-demo`.
Executa apenas em `local`/`testing`. Há testes de repetição, preservação e
bloqueio em produção. A factory de usuário permanece disponível à suíte.

Grandes volumes pertencem à Fase 1.

### 7. Verificações locais

Lint, análise estática e testes estão disponíveis em um único comando:

```bash
./bin/dev exec -T app composer check
```

O autor executa esse comando antes de levar alterações à `main`.
O workflow de GitHub Actions foi removido por preferência pelo fluxo local
no projeto individual. Não há execução remota pendente para encerrar a fase.
As verificações e a instalação limpa já foram validadas.

## Fora do escopo

Não implementar nesta fase:

- otimização do feed;
- investigação de N+1;
- criação sistemática de índices para performance;
- `EXPLAIN ANALYZE` como trabalho de tuning;
- cache de domínio;
- cache stampede;
- Redis Session Store;
- filas assíncronas reais;
- workers;
- processamento assíncrono de imagem;
- S3;
- CDN;
- múltiplas instâncias da API;
- load balancer;
- microservices;
- frontend;
- OpenTelemetry;
- tracing distribuído;
- Elasticsearch/OpenSearch;
- redesign do feed.

## Definition of Done

- [x] versões confirmadas: PHP 8.5.12, Laravel 13.39.0, PostgreSQL 18.6 e Redis 8.10.3;
- [x] aplicação executando na stack atualizada;
- [x] testes de regressão revalidados: 102 testes/602 assertions;
- [x] PostgreSQL 18 funcionando com migrations e seed pequeno;
- [x] Redis disponível e conexão Laravel verificada;
- [x] `docker compose up` sobe o ambiente após a configuração inicial;
- [x] instalação limpa configurada e verificada conforme o README;
- [x] comando local de lint, análise e testes disponível e validado;
- [x] dependências críticas atualizadas;
- [x] soluções de fases futuras não foram antecipadas;
- [x] decisões, riscos e limites documentados.

A Fase 0 está concluída. O início da Fase 1 será combinado com o autor;
não foram iniciados experimentos de baseline.

## Primeira tarefa recomendada

```text
Leia AGENTS.md, docs/system-design-roadmap.md e docs/current-phase.md.

Estamos iniciando a Fase 0.

Analise o estado atual do projeto e proponha um plano para modernizá-lo para
a stack alvo definida em docs/current-phase.md.

Não altere nenhum arquivo ainda.

Quero que você:

1. identifique incompatibilidades da aplicação atual;
2. liste dependências que precisam ser atualizadas ou substituídas;
3. identifique pontos de risco;
4. proponha uma sequência pequena de migração;
5. separe mudanças obrigatórias de melhorias opcionais;
6. indique quais testes devemos ter antes de começar.

Não implemente fases posteriores do roadmap.
```

## Próxima fase

**Fase 1 — Baseline e observação do sistema atual**

Nela serão introduzidos grandes volumes de dados, load testing, p50/p95/p99, throughput, contagem de queries e baseline de CPU, memória e PostgreSQL.
