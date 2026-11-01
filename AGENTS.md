# Devsbook

Este repositório é uma API de rede social usada como **laboratório prático de System Design** e também como projeto de portfólio.

O objetivo principal não é adicionar tecnologias indiscriminadamente, mas evoluir o sistema a partir de problemas reais de performance, concorrência, cache, processamento assíncrono, escalabilidade, observabilidade e resiliência.

## Fonte de contexto

Antes de propor mudanças relevantes, consulte:

- `docs/system-design-roadmap.md` — visão geral e roadmap completo.
- `docs/current-phase.md` — fase atual, objetivo e limites de escopo.
- `docs/learning-log.md` — aprendizados e gaps já registrados.
- `docs/experiments/` — experimentos técnicos, quando existirem.
- `docs/performance/` — benchmarks e análises de performance, quando existirem.
- `docs/adr/` — decisões arquiteturais, quando existirem.

## Forma de trabalho

- Trabalhar **uma fase do roadmap por vez**.
- Não antecipar soluções de fases futuras sem necessidade concreta.
- Antes de otimizar, medir.
- Antes de adicionar infraestrutura, justificar o problema que ela resolve.
- Preferir a solução mais simples que satisfaça o problema atual.
- Não introduzir microservices apenas para fins de portfólio.
- Preservar o projeto como laboratório de aprendizado.
- Evitar refatorações amplas sem relação com o objetivo da fase atual.
- Alterações relevantes devem ser pequenas o suficiente para serem revisadas e compreendidas.

## Para mudanças arquiteturais ou de performance

Seguir esta sequência:

1. identificar o problema;
2. medir o comportamento atual;
3. levantar hipóteses;
4. explicar os conceitos envolvidos;
5. apresentar alternativas;
6. discutir trade-offs;
7. implementar somente a solução escolhida;
8. validar com testes e/ou benchmark;
9. documentar o resultado.

## Regra de aprendizado

Antes de implementar uma solução relevante de System Design:

- explique qual problema está sendo resolvido;
- liste os conceitos que precisam ser entendidos;
- apresente as principais decisões;
- não pule diretamente para a implementação quando houver uma decisão arquitetural relevante ainda não discutida.

Quando solicitado explicitamente, faça perguntas antes da implementação para que o autor do projeto proponha a solução primeiro.

## Benchmarks

Mudanças de performance devem, sempre que possível, registrar:

- cenário testado;
- volume de dados;
- ferramenta utilizada;
- p50;
- p95;
- p99;
- throughput;
- error rate;
- quantidade de queries por request;
- métricas relevantes de CPU, memória e banco.

Não inventar resultados. Se não houver medição, declarar que ainda não há benchmark.

## PostgreSQL

Não adicionar índices apenas por intuição.

Para alterações de performance no banco:

1. observar a query;
2. registrar `EXPLAIN ANALYZE` quando aplicável;
3. formular hipótese;
4. aplicar a mudança;
5. comparar plano e tempo;
6. registrar trade-offs.

## Novos componentes

Redis, filas, S3, mecanismos de busca, serviços externos ou qualquer outro componente devem ser adicionados apenas quando houver uma justificativa concreta.

Pergunta obrigatória:

> Qual problema este componente resolve neste projeto?

## Testes

- Não remover testes existentes para fazer uma mudança passar.
- Adicionar cobertura para comportamento alterado.
- Para bugs de concorrência ou consistência, criar um cenário que reproduza o problema quando viável.
- Para upgrades de framework, preservar primeiro o comportamento atual.

## Documentação

Documentar mudanças relevantes em uma das áreas:

```text
docs/
├── architecture/
├── performance/
├── experiments/
├── adr/
├── current-phase.md
├── learning-log.md
└── system-design-roadmap.md
```

### ADR

Criar ADR quando houver uma decisão arquitetural relevante com alternativas reais, por exemplo:

- usar Redis para cache;
- mover mídia para S3;
- escolher estratégia de sessão;
- escolher mecanismo de fila;
- alterar estratégia de geração do feed.

## Escopo

A fase atual está definida em `docs/current-phase.md`.

Itens explicitamente marcados como fora de escopo não devem ser implementados sem atualização prévia daquele documento.
