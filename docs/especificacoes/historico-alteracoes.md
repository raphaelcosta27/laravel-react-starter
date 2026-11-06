# Histórico de alterações

## Objetivo

Registrar toda mudança em dados: tabela, campo, valor original, valor novo, data e responsável.

## Regras

- Cada campo alterado é um item na tabela `change_history`.
- Os itens nunca mudam e nunca são excluídos.
- Sem usuário logado, a origem é `system`.
- Campos `$hidden` e os de `HISTORY_MASKED_FIELDS` aparecem como `********`.
- Uma falha ao registrar desfaz a alteração.

## Comportamentos

- Entidades adotam com a trait `RecordsChanges`; um teste falha se alguma ficar de fora.
- `update()`, `delete()` e `restore()` em massa registram cada linha.
- Um teste falha se a aplicação usar escrita que não passa pelo histórico.

## Configuração

`HISTORY_MASKED_FIELDS`, `HISTORY_IGNORED_FIELDS`.

## Decisões

- **Sem tela:** a consulta é pelo modelo `ChangeHistory`, e cada projeto decide como exibir.
- **Tudo por padrão:** esquecer de registrar é pior do que registrar a mais.
