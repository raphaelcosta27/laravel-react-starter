# Laravel + React Starter

[Read in English](README.md)

Base para novos projetos com Laravel 13, React 19, Inertia 2 e MySQL. Já inclui autenticação, confirmação
da conta por e-mail, troca obrigatória de senha no primeiro acesso e testes automatizados.

## Pré-requisitos

- PHP 8.3 ou superior, com `pdo_mysql`, `pdo_sqlite` e `zip`
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

Por padrão, os e-mails não são enviados: eles ficam no arquivo de log do dia, como `storage/logs/laravel-2026-11-05.log`.

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
| `APP_PRIMARY_COLOR` | `#3949ab` | Cor principal da interface, em hexadecimal e entre aspas |
| `APP_SECONDARY_COLOR` | `#00695c` | Cor secundária da interface, em hexadecimal e entre aspas |
| `DB_DATABASE` | `template` | Nome do banco |
| `DB_ENGINE` | `InnoDB` | Motor do MySQL; o padrão MyISAM do WAMP não funciona |
| `MAIL_MAILER` | `log` | Forma de envio de e-mail |
| `REGISTRATION_ENABLED` | `false` | Ativa a tela pública de registro |
| `TEMPORARY_PASSWORD_EXPIRE` | `60` | Validade da senha temporária e do link, em minutos |
| `INITIAL_USER_EMAIL` | `admin@example.com` | E-mail do usuário inicial; vazio não cria o usuário |
| `INITIAL_USER_PASSWORD` | vazio | Senha temporária; vazia gera uma automaticamente |
| `ADMIN_PROFILE_NAME` | `Administrador` | Nome do perfil master, usado somente na criação |
| `DEFAULT_PROFILE_NAME` | `Usuário` | Nome do perfil padrão, usado somente na criação |
| `ADMIN_EMAIL_THROTTLE` | `3` | E-mails do painel por usuário de destino, por janela |
| `ADMIN_EMAIL_THROTTLE_MINUTES` | `10` | Duração dessa janela, em minutos |
| `EXPORT_CSV_DELIMITER` | `;` | Separador de colunas do CSV exportado |
| `LOG_DAILY_DAYS` | `14` | Dias de guarda dos arquivos de log |

Os textos ficam em `lang/pt_BR.json` e `lang/pt_BR/`. A lista completa de opções está em `.env.example`.

## Logs

Cada dia tem um arquivo próprio em `storage/logs`, com o nome `laravel-AAAA-MM-DD.log`. O dia segue o fuso de
`config/app.php`, que é UTC por padrão. O comando `php artisan app:clean-logs` apaga os arquivos com mais de
`LOG_DAILY_DAYS` dias. Somente arquivos com esse nome exato são apagados.

O comando roda todo dia pelo agendador do Laravel, que precisa estar ativo no servidor. Use o mesmo usuário do
servidor web: caso contrário, o site pode perder a permissão de escrita no arquivo do dia.

No Linux, adicione esta linha ao crontab desse usuário:

```bash
* * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
```

No Windows, crie uma tarefa no Agendador de Tarefas que rode `php artisan schedule:run` a cada minuto, na pasta
do projeto.

Instalações existentes precisam trocar `LOG_STACK=single` por `LOG_STACK=daily` no próprio `.env`. Um
`laravel.log` anterior não é movido nem apagado.

## Histórico de alterações

A tabela `change_history` guarda um item por campo alterado, com a operação, a tabela, o registro, o campo, os
valores original e novo, a data e o usuário responsável. Sem usuário logado, a origem é `system`. Não há tela: a
consulta é feita pelo modelo `App\Models\ChangeHistory`. A aplicação não pode alterar nem excluir os itens.

```php
ChangeHistory::of($user)->where('field', 'email')->latest('id')->first()->actor;
```

Uma entidade nova adota o histórico com uma declaração, e um teste falha se alguma ficar de fora:

```php
use RecordsChanges, SoftDeletes;
```

- `HISTORY_MASKED_FIELDS` lista os campos exibidos como `********`, somados aos `$hidden` de cada modelo.
- `HISTORY_IGNORED_FIELDS` lista os campos fora do histórico. Por padrão, tudo é registrado.
- `update()`, `delete()` e `restore()` em massa salvam cada registro e ficam no histórico.
- `insert()`, `upsert()`, `increment()`, `decrement()`, `truncate()`, `DB::table()`, `withoutEvents()` e os métodos
  `Quietly` não passam pelo histórico. Um teste falha se a aplicação ou os seeders os usarem.

## Regras de contas

- Toda criação de usuário passa por `App\Actions\CreateUser`.
- O campo `must_change_password` obriga a troca de senha. Para solicitar uma nova troca a um usuário, use
  `$user->requirePasswordChange()`.
- Novas rotas autenticadas devem usar o grupo de middleware `account.ready`. Um teste falha se alguma rota
  ficar de fora.

## Painel de administrador

O painel gerencia perfis, permissões e usuários. A preparação cria o perfil master, o perfil padrão e as 20
permissões do painel, e o primeiro usuário criado no sistema vira administrador.

**Verificar uma permissão.** A permissão é identificada pelo código, e a mesma verificação vale em todo lugar:

```php
$user->can('cadastrar_usuario');

Route::get('reports', ReportController::class)->middleware(['account.ready', 'can:gerar_relatorio']);
```

```jsx
const can = useCan();

{can('gerar_relatorio') && <Link href="/reports">Relatórios</Link>}
```

`useCan` só exibe ou oculta elementos: a proteção sempre acontece no servidor. Administradores passam por toda
verificação, e um código inexistente nega acesso aos demais. Regras que valem também para administradores ficam
nas Actions, pois `Gate::before` ignora as policies para eles.

**Conceder permissões.** Quem não é administrador só concede o que possui, seja por ajuste individual, pelas
permissões de um perfil, pelo cadastro de um usuário ou pela troca de perfil. Conta o que passaria a valer: manter
o que já existe e revogar continuam liberados. As telas deixam indisponível o que não pode ser concedido.

**Adicionar uma função restrita.** Inclua o código em `lang/pt_BR/permissions.php`, que alimenta o seeder, e
proteja a rota com `can:`. O seeder cria somente as permissões faltantes. `PermissionCoverageTest` falha se uma
rota ficar sem permissão e fora da lista de exceções, ou se um código usado não constar no seeder.

**Exclusão de dados.** Nada é apagado de fato. Toda entidade usa exclusão lógica, inclusive vínculos, e um teste
falha se `forceDelete`, `detach`, `sync` ou `truncate` aparecer em `app/` ou `database/`.

**Testes de bloqueio.** Precisam de bloqueio real de linhas, então rodam em MySQL, em um banco descartável que é
apagado:

```bash
php vendor/bin/phpunit -c phpunit.mysql.xml
```

### Interface

As telas usam o [Material UI](https://mui.com/material-ui/), em sua versão gratuita. O tema fica em `resources/js/theme.js`;
uma cor inválida volta ao padrão.

- `ActionIconButton`: ação em ícone com dica de título e breve descrição, por mouse, teclado ou toque longo.
- `FormDialog`: formulário de criação e edição em diálogo. Ocupa a tela inteira abaixo de 600 px e pede confirmação
  antes de descartar alterações.

Os formulários têm endereço próprio, como `/admin/users/5/edit?search=ana`, que abre o diálogo sobre a listagem.
Salvar ou fechar mantém filtros, ordem e página.

### DataTable

Toda listagem usa `resources/js/Components/DataTable`. Ordenação, busca, filtros, paginação e exportação rodam no
servidor, por uma definição em `app/Support/Tables/Definitions`, e só as colunas declaradas chegam à consulta. As
escolhas do usuário ficam no navegador.

```jsx
<DataTable
    tableKey="reports"
    caption={__('Reports')}
    columns={columns}
    rows={rows}
    query={query}
    tabs={tabs}
    rowActions={rowActions}
    exportRoute="admin.reports.export"
    canExport={abilities.export}
    emptyMessage={__('No reports found.')}
/>
```

`rowActions` recebe o registro e devolve os botões, cada um com sua função:

```jsx
const rowActions = () => [
    {
        key: 'send',
        label: __('Send contract'),
        onClick: (row) => router.post(route('reports.send', row.id)),
        visible: (row) => row.can.send,
        confirm: { title: __('Send contract'), message: __('Confirm the sending?') },
        menu: true,
    },
];
```

O servidor envia `can` em cada registro, combinando autorização e estado do registro. O contrato completo do
componente está nas props dele e em `TableDefinition`.

## Iniciar um novo projeto

1. Clique em **Use this template** no GitHub para criar o seu repositório e clone-o.
2. Copie `.env.example` para `.env` e altere `APP_NAME`, `APP_LOGO`, `DB_DATABASE` e `INITIAL_USER_EMAIL`.
3. Execute `composer setup` e `composer dev`.

Nenhum arquivo de código precisa ser alterado.

## Licença

Distribuído sob a [licença MIT](LICENSE).
