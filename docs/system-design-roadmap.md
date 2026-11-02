# Devsbook — Laboratório de System Design

## 1. Contexto

O objetivo deste projeto não é apenas modernizar uma API antiga ou adicionar tecnologias ao currículo.

A proposta é transformar o **`api-devsbook`** em um **laboratório prático de System Design**, usando o próprio código para criar problemas reais, identificar lacunas de conhecimento, estudar os conceitos necessários e então voltar ao sistema para aplicar, medir e documentar as decisões.

Repositório:

- GitHub: https://github.com/lucianovianna/api-devsbook

O projeto atual é uma API de rede social em Laravel, com funcionalidades como:

- autenticação;
- usuários e perfis;
- feed;
- posts;
- upload de imagens;
- likes;
- comentários;
- seguidores;
- busca de usuários.

A base atual é pequena, relativamente simples de entender por completo e possui um domínio que permite aumentar gradualmente a complexidade até chegar a problemas reais de escalabilidade e sistemas distribuídos.

---

## 2. Por que usar o Devsbook como laboratório

O Devsbook tem uma característica importante: ele já contém vários problemas que se conectam diretamente aos conceitos que precisam ser estudados.

Não será necessário criar exercícios artificiais apenas para estudar teoria.

### Exemplos já existentes

#### Feed

O feed atual segue aproximadamente este fluxo:

```text
pega usuários que eu sigo
        ↓
busca os posts
        ↓
para cada post:
    busca usuário
    conta likes
    verifica meu like
    busca comentários
        ↓
para cada comentário:
    busca usuário
```

Isso cria uma oportunidade natural para estudar:

- N+1 queries;
- índices;
- `EXPLAIN ANALYZE`;
- eager loading;
- JOINs;
- paginação;
- caching;
- métricas de performance.

---

### Likes e follows

A lógica atual verifica se o registro existe e depois decide se cria ou remove.

Em cenários concorrentes, duas requisições podem executar a mesma verificação praticamente ao mesmo tempo.

Isso abre espaço para estudar:

- race conditions;
- constraints;
- transações;
- MVCC;
- locks;
- isolation levels;
- idempotência;
- optimistic locking;
- pessimistic locking.

---

### Upload de imagens

Atualmente o processamento de imagem acontece dentro da própria requisição.

Isso permite estudar:

- CPU-bound vs I/O-bound;
- processamento síncrono vs assíncrono;
- filas;
- workers;
- retry;
- idempotência;
- backpressure;
- armazenamento externo;
- S3.

---

### Armazenamento local de mídia

As imagens ficam dentro da própria aplicação.

Isso começa a gerar problemas quando a API passa a rodar em múltiplas instâncias:

```text
           Load Balancer
         /      |       \
      API 1   API 2   API 3
```

Se uma imagem for salva fisicamente na API 1, ela não necessariamente existirá nas APIs 2 e 3.

Esse problema introduz:

- stateful vs stateless;
- horizontal scaling;
- shared storage;
- object storage;
- S3;
- CDN.

---

## 3. Objetivo do projeto

O objetivo final não é produzir apenas isto:

```text
Laravel
PostgreSQL
Redis
Vue
Docker
```

Isso seria apenas mais um CRUD com várias tecnologias.

O objetivo é construir uma evolução documentada:

```text
problema
   ↓
medição
   ↓
hipótese
   ↓
estudo
   ↓
mudança
   ↓
benchmark
   ↓
trade-offs
```

Cada tecnologia ou padrão deve existir por um motivo claro.

A pergunta principal sempre será:

> **Qual problema este componente resolve?**

---

# 4. Método de estudo

O método principal será:

```text
Problema
   ↓
Tentar resolver
   ↓
Descobrir lacuna
   ↓
Estudar conceito
   ↓
Voltar ao problema
   ↓
Implementar
   ↓
Medir
   ↓
Documentar
```

A ideia é evitar um estudo puramente linear como:

```text
livro
↓
curso
↓
outro curso
↓
mais teoria
↓
talvez aplicar um dia
```

O Devsbook será utilizado para dar contexto aos conceitos.

Exemplo:

Em vez de estudar genericamente:

> "MVCC"

o problema será:

> "O que acontece se duas requisições tentarem dar like no mesmo post simultaneamente?"

A investigação leva naturalmente para:

```text
race condition
↓
transaction
↓
MVCC
↓
row locks
↓
isolation levels
↓
constraints
↓
idempotência
```

---

# 5. Princípios do laboratório

## 5.1 Medir antes de otimizar

Evitar:

> "Essa query parece lenta, vou colocar Redis."

Preferir:

```text
problema percebido
↓
benchmark
↓
métricas
↓
EXPLAIN ANALYZE
↓
hipótese
↓
mudança
↓
novo benchmark
```

---

## 5.2 Não adicionar tecnologia por currículo

Evitar decisões como:

> "Vou adicionar Kafka porque Kafka fica bonito no README."

A arquitetura deve crescer porque um problema apareceu.

Exemplo:

```text
requisição demora demais
↓
processamento pode ser assíncrono
↓
fila passa a fazer sentido
```

---

## 5.3 Não começar com microservices

A estratégia inicial será manter um **monólito bem estruturado**.

Arquitetura inicial pretendida:

```text
                 Nginx
                   ↓
              Laravel API
                   ↓
        ┌──────────┼──────────┐
        ↓          ↓          ↓
   PostgreSQL     Redis      Queue
                              ↓
                            Workers
                              ↓
                              S3
```

Microservices só serão considerados quando houver um problema concreto que justifique separação.

---

## 5.4 Documentar decisões, não apenas implementação

Para portfólio, o importante não é apenas dizer:

> "Usei Redis."

É demonstrar:

```text
problema
↓
por que Redis foi considerado
↓
alternativas
↓
implementação
↓
resultado
↓
trade-offs
```

---

# 6. Roadmap

---

# Fase 0 — Modernização da fundação

## Objetivo

Atualizar o projeto para uma stack moderna antes de começar os experimentos de System Design.

A base atual é Laravel 8 e foi construída originalmente em uma stack antiga.

A intenção é atualizar para as versões estáveis modernas de:

- PHP;
- Laravel;
- PostgreSQL.

Como referência para esta fase:

```text
Laravel 8
↓
Laravel 13

PHP antigo
↓
PHP 8.5

PostgreSQL
↓
PostgreSQL 18
```

As versões exatas devem ser confirmadas novamente no momento da implementação.

---

## Estratégia de upgrade

Como o projeto é relativamente pequeno, a abordagem preferida é:

1. criar uma base Laravel moderna limpa;
2. migrar gradualmente domínio, modelos, rotas e funcionalidades;
3. preservar o histórico do repositório;
4. adicionar testes durante a migração.

Não há grande benefício em transformar o upgrade:

```text
Laravel 8 → 9 → 10 → 11 → 12 → 13
```

em um projeto separado, salvo se o objetivo específico for estudar manutenção de sistemas legados.

O foco principal continua sendo System Design.

---

## Adicionar infraestrutura local

Criar um ambiente reproduzível com Docker Compose:

```text
docker compose up
       ↓
┌────────────────┐
│ Laravel / PHP  │
├────────────────┤
│ PostgreSQL     │
├────────────────┤
│ Redis          │
└────────────────┘
```

---

## Incluir

- Dockerfile;
- Docker Compose;
- PostgreSQL;
- Redis;
- `.env.example`;
- migrations reproduzíveis;
- seeders;
- testes;
- lint;
- static analysis;
- CI simples no GitHub Actions.

---

## Resultado esperado

Um novo desenvolvedor deve conseguir:

```bash
git clone ...
docker compose up
php artisan migrate --seed
php artisan test
```

e ter o sistema funcionando.

---

# Fase 1 — Baseline e observação do sistema atual

## Objetivo

Criar uma linha de base antes de qualquer otimização.

A pergunta principal passa a ser:

> Como provar que determinado ponto é realmente um gargalo?

---

## Gerar volume de dados

Criar factories e seeders capazes de gerar, progressivamente:

```text
100.000 usuários
1.000.000 posts
5.000.000 likes
2.000.000 comentários
milhões de relações entre usuários
```

Os valores não precisam ser atingidos imediatamente.

O volume deve crescer conforme necessário para tornar os gargalos observáveis.

---

## Endpoints iniciais para teste

Exemplos:

```text
GET  /feed
GET  /user/:id
GET  /search
POST /post/:id/like
POST /user/:id/follow
```

---

## Load testing

Introduzir uma ferramenta como **k6**.

Medir pelo menos:

- requests por segundo;
- p50;
- p95;
- p99;
- error rate;
- queries por request;
- CPU;
- memória;
- tempo de banco;
- throughput.

---

## Resultado esperado

Criar um documento de baseline:

```text
docs/baseline.md
```

contendo métricas iniciais da aplicação.

Esse será o ponto de comparação das próximas fases.

---

# Fase 2 — PostgreSQL e performance

## Objetivo

Transformar conhecimento intuitivo sobre SQL e índices em uma compreensão sistemática de performance do PostgreSQL.

---

## 2.1 Encontrar problemas reais

O principal candidato inicial é o feed.

Investigar:

- N+1 queries;
- queries repetidas;
- queries lentas;
- ausência de índices;
- paginação com offset;
- agregações;
- contagens repetidas.

---

## 2.2 Índices

Estudar:

1. B-tree;
2. índice simples;
3. índice composto;
4. seletividade;
5. cardinalidade;
6. ordem das colunas;
7. leftmost prefix;
8. custo de índices em `INSERT`;
9. custo em `UPDATE`;
10. custo em `DELETE`;
11. covering indexes;
12. `INCLUDE`.

---

## 2.3 EXPLAIN ANALYZE

Aprender a interpretar:

```text
Seq Scan
Index Scan
Index Only Scan
Bitmap Index Scan
Bitmap Heap Scan
```

E observar:

```text
cost
actual time
estimated rows
actual rows
loops
Rows Removed by Filter
```

Perguntas principais:

```text
Qual operação está mais cara?

O PostgreSQL está fazendo Seq Scan?

Existe índice?

O índice está sendo usado?

Quantas linhas o planner esperava?

Quantas linhas realmente foram encontradas?

Onde está sendo gasto o tempo?
```

---

## 2.4 Índices candidatos

Exemplos para investigação:

```text
posts(user_id, created_at)

post_likes(post_id, user_id)

user_relations(user_from, user_to)
```

Esses índices não devem ser adicionados automaticamente.

Devem ser avaliados através de:

```text
EXPLAIN ANALYZE
+
benchmark
```

---

## 2.5 Paginação

Comparar:

```text
OFFSET pagination
vs
cursor pagination
```

Investigar o comportamento quando o usuário acessa páginas muito profundas.

---

## 2.6 Agregações

Posteriormente estudar:

- `GROUP BY`;
- `COUNT`;
- `SUM`;
- pré-agregação;
- materialized views;
- tabelas de resumo.

---

## Entregável

Criar documentação como:

```text
docs/performance/feed-v1.md
```

Estrutura:

```text
Problema
↓
Benchmark inicial
↓
EXPLAIN ANALYZE
↓
Hipótese
↓
Mudança
↓
EXPLAIN depois
↓
Benchmark depois
↓
Trade-offs
```

---

# Fase 3 — Concorrência e consistência no PostgreSQL

## Objetivo

Entender o que realmente acontece quando múltiplas requisições alteram os mesmos dados simultaneamente.

---

## Laboratórios principais

Usar:

```text
POST /post/:id/like
POST /user/:id/follow
```

Enviar múltiplas requisições simultâneas.

Observar se aparecem:

- likes duplicados;
- follows duplicados;
- estados inconsistentes;
- comportamento inesperado.

---

## Conceitos

Estudar nesta ordem:

1. transaction;
2. concorrência;
3. race condition;
4. lost update;
5. MVCC;
6. isolation levels;
7. Read Committed;
8. Repeatable Read;
9. Serializable;
10. row locks;
11. `SELECT ... FOR UPDATE`;
12. pessimistic locking;
13. optimistic locking;
14. deadlocks.

---

## Constraints

Investigar também o papel do banco na integridade.

Exemplo:

```sql
UNIQUE(post_id, user_id)
```

para impedir dois likes idênticos.

Princípio:

> A consistência não deve depender exclusivamente de um `if` na aplicação.

---

## Resultado esperado

Ser capaz de responder:

> Se duas requisições tentarem modificar o mesmo dado ao mesmo tempo, o que o PostgreSQL fará e quais opções existem para controlar o comportamento?

---

# Fase 4 — Runtime, CPU, I/O e processamento

## Objetivo

Entender como a aplicação realmente executa trabalho.

---

## Conceitos

Estudar:

1. CPU-bound;
2. I/O-bound;
3. processos;
4. threads;
5. concorrência;
6. paralelismo;
7. blocking I/O;
8. non-blocking I/O;
9. processamento síncrono;
10. processamento assíncrono.

---

## Laboratório

Usar o processamento de imagens.

Hoje:

```text
POST imagem
   ↓
Laravel recebe
   ↓
resize
   ↓
salva arquivo
   ↓
response
```

Investigar:

- tempo da requisição;
- CPU durante resize;
- consumo de memória;
- throughput;
- efeito de múltiplos uploads simultâneos.

---

## Evolução

Transformar em:

```text
POST imagem
   ↓
salva original
   ↓
enfileira job
   ↓
202 Accepted

Worker
   ↓
resize
   ↓
thumbnail
```

Essa evolução prepara o terreno para a fase de filas.

---

# Fase 5 — Cache e Redis

## Objetivo

Introduzir cache somente onde existir benefício observável.

---

## Candidatos

Exemplos:

```text
perfil do usuário
contagem de seguidores
contagem de likes
partes do feed
```

---

## Conceitos básicos

1. cache hit;
2. cache miss;
3. TTL;
4. cache invalidation;
5. cache-aside;
6. write-through;
7. write-behind;
8. stale data.

---

## Problemas avançados

Depois estudar:

1. cache stampede;
2. TTL jitter;
3. cache locking;
4. distributed locking;
5. stale-while-revalidate;
6. cache warming;
7. eviction;
8. LRU/LFU.

---

## Experimentos

Exemplo:

```text
1.000 requests
↓
mesma chave
↓
cache expira
↓
1.000 cache misses
↓
PostgreSQL recebe pico
```

Depois testar:

- TTL jitter;
- lock;
- stale-while-revalidate.

Medir o efeito sobre o banco.

---

## Pergunta obrigatória

Antes de colocar Redis:

> O PostgreSQL realmente precisa ser protegido por cache neste caso?

---

# Fase 6 — Filas, workers e processamento assíncrono

## Objetivo

Transformar o processamento de mídia em um laboratório de mensageria.

---

## Arquitetura

```text
API
 ↓
Queue
 ↓
Workers
```

---

## Conceitos

Estudar:

1. queue;
2. producer;
3. consumer;
4. worker;
5. job;
6. ACK;
7. retry;
8. exponential backoff;
9. failed jobs;
10. Dead Letter Queue;
11. idempotência;
12. mensagens duplicadas;
13. at-most-once;
14. at-least-once;
15. exactly-once;
16. ordering;
17. worker concurrency;
18. backpressure.

---

## Cenários de falha

Exemplo:

```text
worker recebe job
↓
processa imagem
↓
salva resultado
↓
worker morre antes do ACK
↓
job é entregue novamente
```

Pergunta:

> O que impede o processamento de gerar efeitos duplicados?

---

## Backpressure

Evitar:

```text
fila com 100.000 jobs
↓
criar 1.000 workers
↓
1.000 queries simultâneas
↓
PostgreSQL saturado
```

Investigar limites de concorrência e capacidade do sistema.

---

# Fase 7 — Stateless e escalabilidade horizontal

## Objetivo

Executar múltiplas instâncias da aplicação sem depender de estado local.

---

## Arquitetura desejada

```text
                Load Balancer
              /      |      \
          Laravel Laravel Laravel
              \      |      /
                  Redis
                    |
               PostgreSQL
```

---

## Conceitos

Estudar:

1. vertical scaling;
2. horizontal scaling;
3. stateful application;
4. stateless application;
5. HTTP stateless;
6. session state;
7. shared session store;
8. Redis Session Store;
9. sticky session;
10. session affinity;
11. health checks;
12. connection pooling.

---

## Mídia

Problema atual:

```text
Laravel 1
└── public/media/foto.jpg
```

Laravel 2 não necessariamente possui o arquivo.

Solução a investigar:

```text
Laravel 1 ─┐
Laravel 2 ─┼──→ S3 / Object Storage
Laravel 3 ─┘
```

---

## Conceitos relacionados

- object storage;
- S3;
- presigned URLs;
- CDN;
- shared storage.

---

# Fase 8 — Observabilidade e resiliência

## Objetivo

Aprender a observar o comportamento do sistema e diagnosticar incidentes antes de alterar a arquitetura.

---

## Os três pilares

### Logs

Exemplo:

```text
pedido 123 falhou
```

### Metrics

Exemplo:

```text
2.3% das requests estão falhando
```

### Traces

Exemplo:

```text
Request
 ↓
API            30ms
 ↓
Redis          5ms
 ↓
PostgreSQL     240ms
```

---

## Métricas principais

Estudar:

```text
p50
p95
p99
throughput
error rate
saturation
```

---

## Adicionar

- structured logs;
- request/correlation ID;
- métricas da API;
- métricas de fila;
- cache hit ratio;
- métricas PostgreSQL;
- slow queries;
- tracing distribuído;
- OpenTelemetry;
- dashboards;
- alertas.

---

## Laboratório de incidentes

Criar falhas artificialmente:

```text
PostgreSQL lento
Redis indisponível
Worker parado
S3 lento
API externa em timeout
```

E tentar responder:

```text
Onde está o problema?

Como sei?

Qual métrica mostrou?

Qual componente foi afetado?

Qual foi o impacto?
```

---

# Fase 9 — Frontend e apresentação

## Status

**Opcional para System Design.**

**Recomendado para portfólio.**

---

## Objetivo

Criar uma vitrine funcional para o sistema sem transformar o frontend no foco principal do projeto.

Como já existe experiência prévia com Vue, uma escolha natural é:

```text
Vue 3
+
Vite
```

---

## Escopo mínimo

Implementar apenas:

- login;
- feed;
- criar post;
- perfil;
- seguir/deixar de seguir;
- like;
- comentários;
- upload;
- busca.

---

## Objetivo de portfólio

Permitir:

```text
GitHub
↓
Demo
↓
Login
↓
Rede social funcional
```

---

## O frontend também pode gerar problemas úteis

### Infinite scroll

```text
infinite scroll
↓
cursor pagination
```

### Optimistic UI

```text
usuário clica like
↓
UI atualiza imediatamente
↓
request falha
↓
rollback
```

---

## Limite

Não gastar tempo excessivo com:

- animações;
- design system sofisticado;
- CSS altamente refinado;
- dezenas de telas.

O frontend é a **vitrine**.

O backend e as decisões arquiteturais são o **conteúdo técnico**.

---

# Fase 10 — System Design intermediário/avançado

## Objetivo

Depois que o sistema estiver funcional e observável, parar de pensar apenas no volume atual e trabalhar com cenários hipotéticos de grande escala.

---

# 10.1 Feed em escala

Arquitetura inicial:

```text
request
↓
busca quem sigo
↓
busca posts
↓
monta feed
```

Pergunta:

> O que acontece se o usuário seguir 500.000 contas?

Estudar:

- fan-out on read;
- fan-out on write;
- timeline materialization;
- cache;
- queues;
- eventual consistency.

---

## Celebrity problem

Pergunta:

> O que acontece se um usuário com 200 milhões de seguidores publicar um post?

Investigar:

- hot users;
- hot partitions;
- fan-out;
- estratégias híbridas.

---

# 10.2 Busca

Hoje:

```sql
LIKE '%luciano%'
```

Perguntas:

```text
Quando PostgreSQL ainda é suficiente?

Quando usar Full Text Search?

Quando Elasticsearch/OpenSearch faria sentido?
```

Não adicionar mecanismo externo antes de justificar.

---

# 10.3 Contadores

Hoje:

```sql
SELECT COUNT(*)
FROM post_likes
WHERE post_id = ?
```

Pergunta:

> Como isso se comporta com centenas de milhões de likes?

Investigar:

- counter cache;
- Redis counters;
- eventual consistency;
- event-driven updates;
- reconciliation.

---

# 10.4 Sistemas distribuídos

Somente depois das fases anteriores aprofundar:

- CAP theorem;
- strong consistency;
- eventual consistency;
- distributed locks;
- leader election;
- consensus;
- quorum;
- Saga;
- Outbox Pattern;
- distributed transactions;
- CQRS;
- Event Sourcing.

Esses conceitos passarão a ter contexto concreto.

---

# 7. Arquitetura de evolução

Uma possível evolução do projeto:

```text
                    ┌──────────────┐
                    │     Vue      │
                    └──────┬───────┘
                           │
                           ↓
                    ┌──────────────┐
                    │ Load Balancer│
                    └──────┬───────┘
                           │
               ┌───────────┼───────────┐
               ↓           ↓           ↓
           Laravel      Laravel      Laravel
               │           │           │
               └───────────┼───────────┘
                           │
         ┌─────────────────┼─────────────────┐
         ↓                 ↓                 ↓
    PostgreSQL           Redis             Queue
                           │                 │
                        Cache             Workers
                        Session              │
                                            ↓
                                            S3
```

Não significa que todos esses componentes devem existir desde o início.

A arquitetura deve crescer fase por fase.

---

# 8. Relação com os gaps identificados

O roadmap foi construído para atacar diretamente os gaps encontrados durante o assessment inicial.

---

## PostgreSQL / concorrência

- transactions;
- race conditions;
- lost update;
- MVCC;
- isolation levels;
- row locks;
- `SELECT ... FOR UPDATE`;
- optimistic locking;
- pessimistic locking;
- deadlocks.

---

## PostgreSQL / performance

- B-tree;
- índice composto;
- seletividade;
- `EXPLAIN ANALYZE`;
- Seq Scan;
- Index Scan;
- Index Only Scan;
- planner;
- pré-agregação;
- materialized views;
- tabelas de resumo.

---

## Runtime

- CPU-bound;
- I/O-bound;
- processos;
- threads;
- concorrência;
- paralelismo;
- blocking/non-blocking;
- async.

---

## Session / escalabilidade

- stateless;
- stateful;
- session storage;
- Redis Session Store;
- sticky session;
- horizontal scaling.

---

## Cache

- TTL;
- cache invalidation;
- cache-aside;
- cache stampede;
- TTL jitter;
- distributed locking;
- stale-while-revalidate.

---

## Filas

- queues;
- workers;
- ACK;
- retry;
- backoff;
- idempotência;
- duplicação;
- DLQ;
- backpressure.

---

# 9. Estratégia de portfólio

O projeto deve contar uma história de engenharia.

Evitar um README que diga apenas:

```text
Tecnologias:

- Laravel
- Redis
- Docker
- PostgreSQL
- Vue
- AWS
```

Preferir demonstrar resultados.

Exemplo:

```text
Feed Optimization

Cenário:
- 500 contas seguidas
- 5 milhões de posts

Antes:
- 143 queries/request
- p95: 1.8s

Problemas:
- N+1
- ausência de índice
- offset pagination

Mudanças:
- eager loading
- índice composto
- cursor pagination

Depois:
- 7 queries/request
- p95: 180ms
```

Os números acima são apenas um exemplo de como documentar; os valores reais deverão ser medidos no laboratório.

---

## Estrutura de documentação sugerida

```text
docs/
├── architecture/
│   ├── v1.md
│   ├── v2-cache.md
│   ├── v3-async-processing.md
│   └── v4-horizontal-scaling.md
│
├── performance/
│   ├── baseline.md
│   ├── feed-v1.md
│   └── pagination.md
│
├── experiments/
│   ├── concurrent-likes.md
│   ├── cache-stampede.md
│   ├── queue-retry.md
│   └── worker-backpressure.md
│
└── adr/
    ├── 001-postgresql.md
    ├── 002-redis-cache.md
    ├── 003-s3-media.md
    └── 004-queue.md
```

---

# 10. Definition of Done por fase

Uma fase não termina apenas quando o código funciona.

Ela deve responder:

```text
[ ] Qual problema eu estava tentando resolver?

[ ] Como medi o problema?

[ ] Qual conceito precisei estudar?

[ ] Quais soluções considerei?

[ ] Qual escolhi?

[ ] Por que escolhi?

[ ] Como implementei?

[ ] Qual foi o resultado?

[ ] Quais trade-offs foram introduzidos?

[ ] O que ainda pode melhorar?
```

---

# 11. Ordem recomendada

Resumo:

```text
FASE 0
Modernização
PHP + Laravel + PostgreSQL + Docker

        ↓

FASE 1
Baseline
Seed + Load Test + Métricas

        ↓

FASE 2
PostgreSQL Performance
Índices + EXPLAIN + N+1 + Paginação

        ↓

FASE 3
Concorrência
Transactions + MVCC + Locks

        ↓

FASE 4
Runtime
CPU/I/O + Sync/Async

        ↓

FASE 5
Cache
Redis + TTL + Stampede

        ↓

FASE 6
Queues
Workers + Retry + Idempotência

        ↓

FASE 7
Escalabilidade
Stateless + Load Balancer + S3

        ↓

FASE 8
Observabilidade
Logs + Metrics + Traces

        ↓

FASE 9
Frontend
Vue + Demo

        ↓

FASE 10
System Design avançado
Feed em escala + Distribuição
```

---

# 12. Prioridades

## Laboratório principal

As fases **0 a 8** constituem o núcleo do laboratório.

Elas devem produzir experiência prática em:

- banco;
- concorrência;
- cache;
- filas;
- escalabilidade;
- observabilidade;
- resiliência.

---

## Portfólio

A fase **9** aumenta significativamente a capacidade de apresentar o sistema para outras pessoas.

Ela não é obrigatória para o aprendizado de System Design.

---

## System Design avançado

A fase **10** deve acontecer quando os fundamentos anteriores já estiverem consolidados.

Ela conecta o projeto com problemas maiores de sistemas distribuídos.

---

# 13. Resultado esperado

Ao final, o objetivo não é poder dizer apenas:

> "Sei Laravel, Redis, PostgreSQL e Docker."

O objetivo é conseguir explicar:

> "Eu tinha este problema."

> "Medi desta forma."

> "Identifiquei este gargalo."

> "Considerei estas alternativas."

> "Escolhi esta solução por estes motivos."

> "O sistema passou a se comportar desta forma."

> "Estes são os trade-offs que introduzi."

Essa capacidade de identificar problemas, medir, escolher soluções e justificar trade-offs é o principal objetivo do laboratório de System Design.
