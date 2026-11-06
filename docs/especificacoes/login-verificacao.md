# Verificação do login

## Objetivo

Exigir um segundo passo no login e, se desejado, uma prova de que há uma pessoa.

## Regras

- Depois da senha, a pessoa informa um código de 6 dígitos recebido por e-mail.
- O código vale por tempo limitado, com tentativas e envios limitados.
- O captcha é o Cloudflare Turnstile. Ligado sem as duas chaves, recusa todo login.

## Comportamentos

- Com `MAIL_MAILER=log`, o código fica no log do dia.
- A resposta não revela se o e-mail existe.

## Configuração

`LOGIN_TWO_FACTOR_ENABLED`, `LOGIN_CODE_EXPIRE`, `LOGIN_CODE_ATTEMPTS`, `LOGIN_CODE_SEND_LIMIT`, `TURNSTILE_ENABLED`,
`TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY`.

## Decisões

- **Recusar sem as chaves:** falhar aberto deixaria o captcha decorativo.
- **Turnstile:** não exige que a pessoa resolva desafios e tem versão gratuita.
