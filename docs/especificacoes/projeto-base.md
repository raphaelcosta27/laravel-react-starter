# Projeto base

## Objetivo

Oferecer um ponto de partida com Laravel, React e Inertia que serve a qualquer projeto sem mudar código.

## Regras

- Tudo que muda entre projetos vem de configuração: nome, logo, cores, idioma e textos.
- Toda criação de usuário passa por `App\Actions\CreateUser`.
- Toda rota autenticada usa o grupo `account.ready`.
- O primeiro usuário do sistema vira administrador.

## Comportamentos

- `php artisan app:setup` prepara chave, banco e usuário inicial, e pode ser repetido.
- A senha inicial é temporária e precisa ser trocada no primeiro acesso.
- Conta inativa ou excluída não entra.

## Configuração

`APP_NAME`, `APP_LOGO`, `APP_FAVICON`, `APP_PRIMARY_COLOR`, `APP_SECONDARY_COLOR`, `APP_LOCALE`, `REGISTRATION_ENABLED`,
`TEMPORARY_PASSWORD_EXPIRE`, `INITIAL_USER_NAME`, `INITIAL_USER_EMAIL`, `INITIAL_USER_PASSWORD`.

## Decisões

- **Registro público desligado por padrão:** a maioria dos sistemas internos não o usa, e ligá-lo é uma variável.
- **Sessão, cache e fila em arquivo:** a página inicial funciona antes de existir banco.
- **Testes antes do código:** é regra do projeto, e toda regra aqui tem teste que falha se for quebrada.
