# Notificações em tela

## Objetivo

Avisar a pessoa por um sino com a quantidade de novidades e uma barra lateral.

## Regras

- Cada pessoa vê só as próprias notificações.
- As exibidas na barra são marcadas como vistas e deixam de contar.
- O X oculta com exclusão lógica; nenhuma notificação é apagada.
- Tipos: `info`, `success`, `warning` e `error`, sempre com ícone e texto.
- O link aceita caminho interno ou endereço http(s).
- O conteúdo é tratado como texto.

## Comportamentos

- A contagem atualiza ao navegar e periodicamente, e acima de 99 aparece 99+.
- A barra carrega aos poucos.
- Sessão vencida ao usar o X leva ao login.

## Configuração

`NOTIFICATIONS_ENABLED`, `NOTIFICATIONS_POLL_SECONDS`, `NOTIFICATIONS_PER_PAGE`.

## Decisões

- **Consulta periódica:** conexão permanente exigiria mais infraestrutura e não foi pedida.
- **Paginação pelo último id:** ocultar um item não desloca o que falta carregar.
- **Nome `ScreenNotification`:** `notifications` já pertence ao recurso de notificações do Laravel.
- **O template entrega o mecanismo:** quais eventos notificam fica a cargo de cada projeto.
