# Adaptar o template a um projeto novo

Nenhum arquivo de código precisa mudar para começar.

## Passos

1. Crie seu repositório com **Use this template** no GitHub e clone-o.
2. Suba o sistema, com o Docker ou com `composer setup` e `composer dev`.
3. Abra o sistema e siga o [assistente de instalação](../especificacoes/primeira-inicializacao.md).
4. Em **Ambiente**, no painel, ajuste o que mudar. A lista completa está em `.env.example`.

## O que costuma mudar

| Assunto | Variáveis |
| --- | --- |
| Identidade | `APP_NAME`, `APP_LOGO`, `APP_FAVICON`, `APP_PRIMARY_COLOR`, `APP_SECONDARY_COLOR` |
| E-mail | `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_FROM_ADDRESS` |
| Contas | `REGISTRATION_ENABLED`, `PASSWORD_MIN_LENGTH` |
| Login | `LOGIN_TWO_FACTOR_ENABLED`, `TURNSTILE_ENABLED` |
| Perfil | `PROFILE_GENDERS`, `PROFILE_CPF_REQUIRED`, `PROFILE_RELATIONSHIPS` |
| Endereços | `ADDRESS_DEFAULT_COUNTRY` |

Os textos ficam em `lang/pt_BR.json` e `lang/pt_BR/`.

## Antes de publicar

- Use `APP_ENV=production` e `APP_DEBUG=false`.
- Configure um envio real de e-mail: com `MAIL_MAILER=log`, o código de login só aparece no log.
- Deixe o agendador ativo, para a limpeza de logs.
- Considere `ENVIRONMENT_EDITOR_ENABLED=false`.
- No Docker, troque `DOCKER_DB_PASSWORD` e ligue o HTTPS.
