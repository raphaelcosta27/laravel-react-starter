# Laravel + React Starter

[Read in English](README.md)

Base para novos projetos com Laravel 13, React 19, Inertia 2 e MySQL. Já inclui autenticação, confirmação
da conta por e-mail, troca obrigatória de senha no primeiro acesso e testes automatizados.

## Pré-requisitos

- PHP 8.3 ou superior, com `pdo_mysql` e `pdo_sqlite`
- Composer 2
- Node 22.12 ou superior
- MySQL 8 em execução (no WAMP, basta iniciar os serviços)

## Instalação

```powershell
composer setup
composer dev
```

O `composer setup` instala as dependências, cria o `.env`, compila o frontend, cria o banco e o usuário
inicial. Ele pode ser repetido sem perder dados. O `composer dev` sobe a aplicação em
<http://127.0.0.1:8000>, com recarga automática do frontend.

## Primeiro acesso

Por padrão, os e-mails não são enviados: eles ficam em `storage/logs/laravel.log`.

1. Abra o log e copie a senha temporária e o link do e-mail de boas-vindas de `admin@example.com`.
2. Abra o link e entre com a senha temporária.
3. Defina uma nova senha.

A senha temporária e o link valem por 1 hora. Depois disso, use **Solicitar nova autenticação** na tela de
login para receber uma nova senha e um novo link.

## Cadastrar usuários pelo terminal

```powershell
php artisan app:create-user
```

O comando pede nome, e-mail e senha (duas vezes), com as mesmas regras do cadastro pela web. A conta já nasce
confirmada e nenhum e-mail é enviado. Como todo usuário novo, ele define uma nova senha no primeiro acesso.

## Testes

```powershell
composer test   # backend
npm test        # frontend e acessibilidade
```

Os testes do backend usam SQLite em memória e nunca tocam o banco de desenvolvimento. Por isso, mudanças em
migrações também precisam ser conferidas no MySQL com `php artisan app:setup`.

## Configuração

Tudo o que varia por projeto fica no `.env`. As principais opções:

| Variável | Padrão | Uso |
|----------|--------|-----|
| `APP_NAME` | `Template` | Nome exibido nas telas e nos e-mails |
| `APP_URL` | `http://127.0.0.1:8000` | Endereço usado nos links dos e-mails |
| `SERVER_PORT` | `8000` | Porta local; deve acompanhar `APP_URL` |
| `APP_LOGO` | `/images/logo.svg` | Logo da aplicação |
| `APP_FAVICON` | `/favicon.ico` | Ícone do navegador |
| `DB_DATABASE` | `template` | Nome do banco |
| `DB_ENGINE` | `InnoDB` | Motor do MySQL; o padrão MyISAM do WAMP não funciona |
| `MAIL_MAILER` | `log` | Forma de envio de e-mail |
| `REGISTRATION_ENABLED` | `false` | Ativa a tela pública de registro |
| `TEMPORARY_PASSWORD_EXPIRE` | `60` | Validade da senha temporária e do link, em minutos |
| `INITIAL_USER_EMAIL` | `admin@example.com` | E-mail do usuário inicial; vazio não cria o usuário |
| `INITIAL_USER_PASSWORD` | vazio | Senha temporária; vazia gera uma automaticamente |

Os textos ficam em `lang/pt_BR.json` e `lang/pt_BR/`. A lista completa de opções está em `.env.example`.

## Regras de contas

- Toda criação de usuário passa por `App\Actions\CreateUser`.
- O campo `must_change_password` obriga a troca de senha. Para solicitar uma nova troca a um usuário, use
  `$user->requirePasswordChange()`.
- Novas rotas autenticadas devem usar o grupo de middleware `account.ready`. Um teste falha se alguma rota
  ficar de fora.

## Iniciar um novo projeto

1. Clique em **Use this template** no GitHub para criar o seu repositório e clone-o.
2. Copie `.env.example` para `.env` e altere `APP_NAME`, `APP_LOGO`, `DB_DATABASE` e `INITIAL_USER_EMAIL`.
3. Execute `composer setup` e `composer dev`.

Nenhum arquivo de código precisa ser alterado.

## Licença

Distribuído sob a [licença MIT](LICENSE).
