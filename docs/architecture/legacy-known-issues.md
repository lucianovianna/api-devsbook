# Problemas preexistentes do legado

Registro iniciado em 2026-11-01, durante a ampliação dos testes da Fase 0.
As falhas abaixo foram reproduzidas no Laravel 8.64.0 / PHP 8.0.30 /
PostgreSQL 14.24. Foram inicialmente registradas sem correção na entrega de testes.
Em 2026-11-01, ambas foram corrigidas e validadas no legado antes do upgrade.

## LEGACY-001 — Atualização rejeita email próprio inalterado

Status: corrigido em 2026-11-01, antes do upgrade Laravel/PHP.

- Local: `UserController::update`, regra `unique:users,email`.
- Cenário: um usuário autenticado envia `PUT /api/user` com seu email atual e
  um novo nome.
- Esperado: HTTP 200, manter o email e salvar o novo nome.
- Observado: HTTP 400 com erro de unicidade em `message.email`; o nome não é
  atualizado.
- Impacto: formulários que reenviam o email atual impedem edições legítimas
  de outros campos.
- Causa identificada: a validação não exclui o próprio usuário da consulta de
  unicidade. Existe um `if` posterior para email alterado, mas a requisição já
  foi rejeitada pelo validator antes de alcançá-lo.
- Teste: `KnownIssuesTest::test_profile_update_should_accept_the_users_unchanged_email`.

Correção aplicada: `Rule::unique` ignora o ID do usuário autenticado. Aceita
o email do usuário atual e continua rejeitando
emails de terceiros. Esse último comportamento já é coberto por
`ProfileUpdateTest`.

## LEGACY-002 — Lista de seguidos retorna a identidade de origem

Status: corrigido em 2026-11-01, antes do upgrade Laravel/PHP.

- Local: `UserController::followers`, preenchimento de `following`.
- Cenário: A segue B e consulta `GET /api/user/{A}/followers`.
- Esperado: `following` contém a identidade pública de B.
- Observado: `following` contém A, embora a relação persistida seja A → B.
- Impacto: o consumidor recebe pessoas incorretas na lista de seguidos.
- Causa identificada: a query seleciona corretamente as relações de saída,
  mas o loop busca `User::find(user_from)` em vez de `user_to`.
- Teste: `KnownIssuesTest::test_following_list_should_contain_the_destination_user`.

Correção aplicada: a lista `following` usa `user_to`. Foi preservada a
direção da lista `followers`, as listas
vazias e a resposta para usuário inexistente, cobertas por `RelationListTest`.

## Validação das correções antes do upgrade

`KnownIssuesTest` mantém os dois cenários como testes de regressão normais,
com as assertions do comportamento esperado. Os ramos `markTestIncomplete`
foram removidos após as correções; os cenários não foram removidos nem
relaxados.

```bash
./bin/legacy exec -T app php vendor/bin/phpunit --fail-on-incomplete
```

Resultado no Laravel 8.64.0 / PHP 8.0.30 / PostgreSQL 14.24: **97 testes,
514 assertions, sem falhas ou incompletos**. Inclui rejeição de email de
terceiros, seguidores de entrada, listas vazias e usuário inexistente.

Esses resultados antecedem a modernização. A suíte deve continuar passando
na stack nova. Este registro não representa uma auditoria exaustiva de bugs.
