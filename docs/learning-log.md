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

## 2026-11-01 — Compatibilidade observada na modernização Laravel/PHP

### Contexto

Depois de corrigir LEGACY-001/002 no legado (97 testes/514 assertions), a
base foi transportada para Laravel 13/PHP 8.5 com dependências atuais.

### Hipótese inicial

Preservar o arquivo de configuração JWT seria suficiente para manter o
comportamento dos tokens.

### O que aconteceu

A suíte encontrou tokens novos expirados. `jwt-auth 2.3.1` converte o TTL
`null` em zero em `Claims\Factory::setTTL`, embora a payload factory tenha
um ramo que omite `exp` quando TTL é nulo. Também foi necessário limpar o
middleware calculado da rota ao descartar controllers entre requests de teste;
a closure anterior permanecia ligada à instância antiga.

### Conceitos envolvidos e aprendizado

- Compatibilidade inclui o significado de valores de configuração, não apenas
  a existência da opção ou a satisfação das constraints do Composer.
- O controller pode ser criado antes da execução do middleware de autenticação.
  A identidade deve ser obtida depois de `auth:api`.
- Testes HTTP no mesmo processo reutilizam objetos que requests reais normalmente
  recriam. Descartar controller e manter sua closure calculada não reproduz
  corretamente esse ciclo.
- Nomes de arquivo sem extensão exigem escolher explicitamente o encoding na
  nova biblioteca de imagens. O MIME real preserva JPEG/PNG dos posts.

### Solução e trade-offs

Um adaptador pequeno preserva TTL nulo e delega TTL finito à biblioteca. Isso
mantém a política atual, ao custo de uma binding que precisa ser revista em
futuros upgrades do JWT. Adotar TTL finito exigiria uma decisão de produto.
O helper usa a API `flushController`; os testes não desabilitam autenticação.

### Evidência

Laravel 13.39.0/PHP 8.5.12: 100 testes e 585 assertions, também em ordem
aleatória. A instalação limpa pelo lock reproduziu o resultado. Detalhes em
[laravel13-migration.md](architecture/laravel13-migration.md).

### Próximo passo

Validar PostgreSQL 18 e completar o ambiente da Fase 0. Não há benchmark
nesta etapa; performance e concorrência continuam nas fases correspondentes.

## 2026-11-01 — Validar schema entre PostgreSQL 14 e 18

### Contexto

A aplicação Laravel 13/PHP 8.5 passou a executar em um cluster PostgreSQL 18
novo, mantendo os volumes do 14 e do legado. A suíte passou sem mudar código
de domínio, migrations ou testes.

### Hipótese e observação

Comparar a quantidade de registros de `pg_constraint` parecia uma forma
simples de verificar se as migrations criaram as mesmas constraints.
O catálogo apresentou 6 entradas no 14 e 33 no 18, mesmo com a mesma migration.

### Conceitos e aprendizado

O PostgreSQL 18 passou a representar `NOT NULL` em `pg_constraint` como tipo
`n`. Os 27 registros novos correspondem às mesmas 27 colunas não nulas do
schema anterior. Uma diferença de representação no catálogo não implica
uma mudança nas regras do domínio. A comparação precisa considerar tipos,
defaults, nulabilidade, constraints e índices, além de contagens.

Também há diferença entre migrations de schema e upgrade de um cluster.
A imagem Docker 18 usa `/var/lib/postgresql/18/docker`; o volume foi montado
em `/var/lib/postgresql`. Trocar a imagem não converte os arquivos do 14.

### Decisão e trade-off

Usar volume 18 novo e migrations, porque a referência estava sem dados de
domínio. Os volumes antigos foram preservados. Isso valida a aplicação com
o banco novo; não comprova uma transferência de dados com dump/restore ou
`pg_upgrade`, que exigiria um ensaio específico se houvesse dados a migrar.

### Evidência

100 testes/585 assertions, também em ordem aleatória; comparação do schema,
rollback/reaplicação isolados, HTTP e persistência após recriar o container.
Ver [postgresql18-validation.md](architecture/postgresql18-validation.md).
Não houve benchmark ou avaliação de ganho de performance.

### Próximo passo

Completar Redis, seed de desenvolvimento e CI na Fase 0.

## 2026-11-01 — Ambiente reproduzível e verificações comuns da Fase 0

### Contexto

Faltavam Redis, dados pequenos e verificações automáticas após os upgrades.
A instalação limpa precisava reproduzir os comandos que a CI executaria.

### Conceitos e decisões

Disponibilidade de um componente não exige mudar os drivers do domínio.
Redis foi disponibilizado com Predis, enquanto cache/sessão continuam em arquivo
e fila síncrona. Não houve hipótese de ganho de performance ou benchmark.

O seed usa identificadores estáveis e uma transação, preservando dados já
existentes. Repetibilidade sequencial não garante segurança concorrente;
constraints e concorrência continuam nos estudos correspondentes.

A análise estática encontrou diferenças entre metadados e comportamento:
a biblioteca JWT anota TTL inteiro, mas o adaptador precisa preservar `null`.
Um stub específico corrige essa informação para PHPStan sem executar código
nem editar a biblioteca. Também foi explicitado o guard JWT no refresh.

### Evidência e limites

Pint, Larastan/PHPStan nível 5 e 102 testes/602 assertions passaram em cópia
sem `.env`, `vendor` ou volumes anteriores. A ordem aleatória manteve o
resultado. O seed foi repetido, login/feed e Redis foram verificados, e os dados
de desenvolvimento permaneceram após a suíte. O workflow passou no actionlint.

Reproduzir os passos da CI localmente não equivale a observar um run hospedado.
A primeira execução no GitHub fica pendente da publicação dos arquivos.
Ver [phase0-environment.md](architecture/phase0-environment.md).

### Próximo passo

Conferir a CI após publicação e combinar o início da Fase 1, com medições
antes de qualquer otimização.
