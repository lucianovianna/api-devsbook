# ADR 0001 — Base limpa Laravel 13 no repositório existente

- Data: 2026-11-01.
- Status: aceita pelo autor na solicitação desta etapa da Fase 0.

## Contexto

O projeto Laravel 8 tem um runtime de referência reproduzível e testes da API.
A próxima entrega é modernizar Laravel/PHP preservando comportamento, histórico
e o propósito de laboratório. Não é objetivo estudar cada upgrade sequencial.

## Alternativas

1. Adaptar gradualmente a estrutura Laravel 8 ao framework atual, mantendo
   kernels e providers antigos: menos substituições iniciais, mas carregaria
   configuração obsoleta e exigiria revisar cada integração de qualquer forma.
2. Integrar o esqueleto oficial Laravel 13 no mesmo repositório e transportar
   o domínio existente: adota a configuração atual, com custo de revisar
   bootstrap, middleware, autenticação, imagens e testes.

## Decisão

Adotar a segunda alternativa, conforme o roadmap e a solicitação do autor.
Corrigir LEGACY-001/002 antes e medir a compatibilidade pelos mesmos cenários.
Preservar schema, dados e contratos HTTP. Não adicionar migrations padrão do
esqueleto para users/cache/jobs, Sanctum ou frontend.

JWT permanece com a biblioteca atualizada da mesma família, GD permanece local
e síncrono. O pequeno adaptador de TTL mantém a política anterior; a decisão
sobre prazo de expiração não é parte deste upgrade.

## Consequências

- Kernels e providers de configuração antigos deixam de existir; as integrações
  necessárias ficam explícitas em `bootstrap/app.php` e `AppServiceProvider`.
- As dependências modernas exigem PHP 8.5 neste projeto e um novo lock.
- Reproduzir a referência Laravel 8 requer checkout e `vendor` separados.
- PostgreSQL 14 permanece temporariamente em volume independente para validar
  a troca Laravel/PHP sem misturar uma atualização do banco.
- PostgreSQL 18, Redis, CI e seed de desenvolvimento continuam pendentes da
  Fase 0; não há antecipação das fases seguintes.

## Evidência

[Relato da migração, versões e validações](../architecture/laravel13-migration.md).
