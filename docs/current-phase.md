# Fase atual — 0: Modernização da fundação

## Objetivo

Modernizar a base técnica do Devsbook antes de iniciar os experimentos principais de System Design.

A intenção desta fase é criar uma aplicação moderna, reproduzível, testável e estável, **sem antecipar otimizações ou mudanças arquiteturais das fases seguintes**.

## Estado atual

O projeto foi criado originalmente em 2021 e atualmente possui uma base semelhante a:

- Laravel 8;
- compatibilidade antiga de PHP;
- PostgreSQL configurado como banco;
- Redis configurável, mas ainda não utilizado como parte central da arquitetura;
- `QUEUE_CONNECTION=sync`;
- `SESSION_DRIVER=file`;
- armazenamento local de mídia em `public/media`;
- testes padrão/minimais do Laravel;
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
8. adicionar CI;
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

### 3. Upgrade Laravel/PHP

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

Garantir execução com PostgreSQL moderno.

Validar:

- conexão;
- migrations;
- tipos utilizados;
- constraints existentes;
- seeders;
- comportamento dos endpoints.

Não iniciar ainda tuning sistemático ou criação de índices de performance.

### 5. Docker

Criar ambiente local reproduzível com, no mínimo:

```text
app
postgres
redis
```

### 6. Seeders e factories

Criar base inicial para desenvolvimento e testes.

Grandes volumes pertencem à Fase 1.

### 7. CI

Adicionar pipeline simples no GitHub Actions com:

- instalação de dependências;
- preparação do ambiente;
- migrations;
- execução dos testes;
- lint/static analysis, caso configurados.

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

- [ ] versões alvo confirmadas;
- [ ] aplicação executando na stack atualizada;
- [ ] testes mínimos de regressão passando;
- [ ] PostgreSQL funcionando com migrations e seeders;
- [ ] Redis disponível no ambiente;
- [ ] `docker compose up` sobe o ambiente;
- [ ] novo clone consegue ser configurado pelo README;
- [ ] CI executa testes automaticamente;
- [ ] dependências críticas atualizadas;
- [ ] soluções de fases futuras não foram antecipadas;
- [ ] principais decisões e riscos documentados.

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
