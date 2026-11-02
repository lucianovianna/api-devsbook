# Problemas preexistentes do legado

Registro iniciado em 2026-11-01, durante a ampliação dos testes da Fase 0.
As falhas abaixo foram reproduzidas no Laravel 8.64.0 / PHP 8.0.30 /
PostgreSQL 14.24. Não foram corrigidas nesta entrega de testes.

## LEGACY-001 — Atualização rejeita email próprio inalterado

Status: aberto, reproduzido automaticamente.

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

A correção futura deve aceitar o email do usuário atual e continuar rejeitando
emails de terceiros. Esse último comportamento já é coberto por
`ProfileUpdateTest`.

## LEGACY-002 — Lista de seguidos retorna a identidade de origem

Status: aberto, reproduzido automaticamente.

- Local: `UserController::followers`, preenchimento de `following`.
- Cenário: A segue B e consulta `GET /api/user/{A}/followers`.
- Esperado: `following` contém a identidade pública de B.
- Observado: `following` contém A, embora a relação persistida seja A → B.
- Impacto: o consumidor recebe pessoas incorretas na lista de seguidos.
- Causa identificada: a query seleciona corretamente as relações de saída,
  mas o loop busca `User::find(user_from)` em vez de `user_to`.
- Teste: `KnownIssuesTest::test_following_list_should_contain_the_destination_user`.

A correção futura deve preservar a direção da lista `followers`, as listas
vazias e a resposta para usuário inexistente, cobertas por `RelationListTest`.

## Como os testes sinalizam essas pendências

`KnownIssuesTest` executa as requisições reais. Se encontra a manifestação
específica da falha conhecida, chama `markTestIncomplete` com o identificador
acima. Uma resposta diferente é validada contra o comportamento esperado e
pode falhar normalmente. Não há captura genérica de erros nem teste aprovado
por exigir um resultado incorreto.

Quando a aplicação for corrigida, o ramo de pendência deixa de ser executado e
as assertions do comportamento esperado precisam passar.

```bash
./bin/legacy exec app php artisan test --filter=KnownIssuesTest
```

Para tratar pendências como saída não zero:

```bash
./bin/legacy exec app php vendor/bin/phpunit --filter=KnownIssuesTest --fail-on-incomplete
```

Os dois casos incompletos devem continuar explícitos nos relatórios. Sua
existência não representa cobertura aprovada desses comportamentos e não
encerra as correções do legado.
