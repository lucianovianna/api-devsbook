# Learning Log

Este arquivo registra o aprendizado produzido durante a evolução do Devsbook como laboratório de System Design.

O objetivo não é registrar cada alteração de código.

Registrar principalmente situações em que:

- uma hipótese estava errada ou incompleta;
- apareceu um conceito novo;
- um bug revelou um problema de arquitetura;
- houve uma decisão com trade-offs relevantes;
- um experimento mudou o entendimento do sistema;
- um benchmark revelou comportamento inesperado.

## Template

```md
## YYYY-MM-DD — Título

### Contexto
Qual problema estava sendo investigado?

### O que eu achava inicialmente
Descrever o entendimento ou hipótese antes do estudo.

### O que aconteceu
Descrever o comportamento observado.

### Gap identificado
O que eu percebi que não sabia ou não entendia bem?

### Conceitos estudados
- conceito A
- conceito B

### O que aprendi
Explicar com palavras próprias.

### Solução escolhida
Qual decisão foi tomada?

### Por que
Quais alternativas foram consideradas e por que essa solução foi escolhida?

### Evidência
Benchmark, teste, EXPLAIN, log ou experimento que sustentou a conclusão.

### Trade-offs
O que a solução melhora e o que ela piora ou adiciona de complexidade?

### O que ainda não domino
- assunto A
- assunto B

### Próximo passo
Qual experimento ou estudo deveria acontecer depois?
```

# Entradas

## 2026-11 — Início do laboratório de System Design

### Contexto

O Devsbook foi escolhido como projeto principal para estudar System Design de forma prática.

A aplicação já possui um domínio de rede social com usuários, feed, posts, likes, comentários, seguidores, busca e upload de mídia.

### O que eu achava inicialmente

O estudo de System Design estava organizado principalmente como uma lista de conceitos.

Havia dificuldade em decidir por onde começar, quais conceitos já estavam dominados, quais eram os gaps reais e como evitar estudo puramente teórico.

### O que mudou

Foi adotado o método:

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

### Gaps já identificados

#### PostgreSQL / concorrência

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

#### PostgreSQL / performance

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

#### Runtime

- CPU-bound;
- I/O-bound;
- processos;
- threads;
- concorrência;
- paralelismo;
- blocking/non-blocking I/O;
- async.

#### Session / escalabilidade

- stateless;
- stateful;
- session storage;
- Redis Session Store;
- sticky session;
- horizontal scaling.

#### Cache

- TTL;
- cache invalidation;
- cache-aside;
- cache stampede;
- TTL jitter;
- distributed locking;
- stale-while-revalidate.

#### Filas

- queues;
- workers;
- ACK;
- retry;
- exponential backoff;
- idempotência;
- mensagens duplicadas;
- DLQ;
- backpressure.

### Decisão

Usar o Devsbook como laboratório principal e evoluí-lo gradualmente conforme `docs/system-design-roadmap.md`.

### Princípio principal

Não adicionar tecnologia pelo nome ou pelo valor de portfólio.

Cada componente precisa responder:

> Qual problema real deste projeto ele está resolvendo?

### Próximo passo

Executar a **Fase 0 — Modernização da fundação**.
