# Laravel + React Starter

[Read in English](README.md)

Base para novos projetos com Laravel 13, React 19, Inertia 2 e MySQL. Já inclui autenticação, confirmação
da conta por e-mail, troca obrigatória de senha no primeiro acesso e testes automatizados.

A [documentação](docs/README.md) reúne os guias para adaptar e estender o template e as especificações
lógicas de cada recurso, com as decisões tomadas. Para colaborar, veja o [guia de contribuição](CONTRIBUTING.md).

## Docker

Com o Docker instalado, não é preciso PHP, Apache ou Node na máquina:

```bash
cd docker
docker compose --profile mysql up -d --build
```

O sistema abre em `http://localhost:8080`. Opções, HTTPS e problemas comuns estão em [docker/README.md](docker/README.md).

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
3. Informe o código de verificação, que também fica no log.
4. Defina uma nova senha.

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
| `APP_LOGO_DARK` | vazio | Logo no tema escuro. Vazio usa `APP_LOGO` |
| `APP_TAGLINE` | vazio | Frase da área de marca, ao lado do login |
| `APP_ICON` | `/images/icon` | Prefixo dos ícones de instalação, que terminam em `-192.png` e `-512.png` |
| `APP_FAVICON` | `/favicon.ico` | Ícone do navegador |
| `APP_PRIMARY_COLOR` | `#3949ab` | Cor principal da interface, em hexadecimal e entre aspas |
| `APP_SECONDARY_COLOR` | `#00695c` | Cor secundária da interface, em hexadecimal e entre aspas |
| `DB_DATABASE` | `template` | Nome do banco |
| `DB_ENGINE` | `InnoDB` | Motor do MySQL; o padrão MyISAM do WAMP não funciona |
| `MAIL_MAILER` | `log` | Forma de envio de e-mail |
| `REGISTRATION_ENABLED` | `false` | Ativa a tela pública de registro |
| `TEMPORARY_PASSWORD_EXPIRE` | `60` | Validade da senha temporária e do link, em minutos |
| `INITIAL_USER_EMAIL` | `admin@example.com` | E-mail do usuário inicial; vazio não cria o usuário |
| `INITIAL_USER_PASSWORD` | vazio | Senha temporária; vazia gera uma automaticamente. Precisa cumprir os critérios de senha |
| `PASSWORD_MIN_LENGTH` | `8` | Tamanho mínimo das senhas, de 8 a 72 |
| `PROFILE_GENDERS` | `Female,Male,Non-binary,Prefer not to say,Other` | Opções de gênero, traduzidas por `lang/pt_BR.json` |
| `PROFILE_CPF_REQUIRED` | `false` | Torna o CPF obrigatório no perfil |
| `PROFILE_AVATAR_MAX_KB` | `2048` | Tamanho máximo da imagem de perfil, em KB. Respeite também o `upload_max_filesize` do PHP |
| `PROFILE_AVATAR_DISK` | `local` | Disco das imagens de perfil |
| `PROFILE_RELATIONSHIPS` | `Mother,Father,...,Other relationship` | Parentescos dos contatos de emergência, traduzidos por `lang/pt_BR.json` |
| `NOTIFICATIONS_ENABLED` | `true` | Liga o sino e as rotas de notificação |
| `NOTIFICATIONS_POLL_SECONDS` | `60` | Intervalo de atualização do sino |
| `NOTIFICATIONS_PER_PAGE` | `20` | Notificações por página da barra |
| `INSTALL_ENABLED` | `true` | Liga o assistente de instalação enquanto não há usuários |
| `ENVIRONMENT_EDITOR_ENABLED` | `true` | Permite salvar a tela de ambiente do painel |
| `ADDRESS_DEFAULT_COUNTRY` | `Brasil` | País já preenchido nos endereços |
| `ADDRESS_POSTAL_CODE_URLS` | ViaCEP e BrasilAPI | Serviços de CEP, tentados em ordem. `{cep}` é trocado pelos dígitos |
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

## Senhas

Toda senha definida precisa ter ao menos 8 caracteres, uma letra maiúscula, uma minúscula, um número e um
caractere especial, com limite de 72 caracteres. Espaços contam como caractere especial, e letras acentuadas
contam como letras. Quem já tem uma senha fora dos critérios continua entrando, e a regra vale na próxima troca.

Os critérios ficam em `App\Support\Auth\PasswordCriteria`. O servidor valida com `Password::defaults()`, e a
barra `Components/PasswordStrength.jsx` recebe os mesmos padrões pela prop compartilhada `passwordCriteria`:

```jsx
<PasswordStrength value={form.data.password} />
```

## Dados pessoais

A página de perfil guarda nome completo, como a pessoa quer ser chamada, data de nascimento, gênero, CPF e imagem
na tabela `user_dados` (modelo `App\Models\UserData`, relação `$user->personalData`). O registro nasce no primeiro
salvamento, e o nome completo acompanha `users.name` nos dois sentidos.

O CPF é guardado sem máscara e é único entre contas não excluídas. Ele fica oculto na serialização, aparece mascarado
no histórico e não vai para a sessão. Excluir a conta libera o CPF; restaurá-la é recusado se outra conta já o usa.

As imagens ficam em disco privado e são servidas por `GET /profile/avatar` somente à própria pessoa. Trocar ou remover
não apaga o arquivo anterior, cujo caminho fica no histórico. Projetos sujeitos a pedidos de eliminação de dados precisam
tratar esses arquivos.

## Contatos

Na página de perfil, cada pessoa cuida dos próprios telefones e e-mails na tabela `user_contatos` (modelo
`App\Models\UserContact`, relação `$user->contacts`). O telefone é guardado só com dígitos, nos formatos brasileiros de
fixo e celular, e o e-mail, em minúsculas. O mesmo valor não se repete entre os contatos ativos da pessoa.

Telefones podem ser marcados como WhatsApp. Qualquer contato pode ser de emergência, e então exige parentesco e nome.
O contato de e-mail não muda o e-mail de login. Contato de outra pessoa responde 404, mesmo para administradores:
não há consulta de contatos pelo painel. A exclusão é lógica, e os contatos continuam ligados à conta quando ela é
excluída e restaurada.

## Endereços

Na página de perfil, cada pessoa cuida dos próprios endereços em `user_enderecos` (modelo `App\Models\UserAddress`,
relação `$user->addresses`). O primeiro vira o principal, e o banco garante um só por pessoa. Excluir o principal com
outros cadastrados pede a escolha do próximo. A opção de entrega exige o nome de quem recebe.

Para usar em outro formulário, renderize `Components/AddressFields.jsx` com um `useForm` que tenha `postal_code`,
`street`, `number`, `complement`, `reference`, `neighborhood`, `city`, `state` e `country`, e aplique
`App\Http\Requests\Concerns\ValidatesAddress` na Request. O componente não conhece usuários.

A busca de CEP passa pelo servidor, em `GET /postal-code/{cep}`, envia só o CEP e é limitada a 30 consultas por minuto.
Se ela falhar, a pessoa é avisada e preenche à mão. Com país diferente de Brasil, busca, máscara e lista de estados
ficam desligadas.

## Notificações

Na barra superior, o sino mostra as notificações novas da pessoa. Abrir a barra lateral marca as exibidas como vistas, e o X
oculta com exclusão lógica. Para enviar, use uma chamada, a um usuário ou a uma coleção:

```php
ScreenNotifications::send($profile->users, 'Título', 'Texto', 'success', '/dashboard');
```

Os tipos são `info`, `success`, `warning` e `error`. O link é opcional e aceita caminho interno ou endereço http(s).
O conteúdo é sempre exibido como texto. A contagem é atualizada ao navegar e a cada `NOTIFICATIONS_POLL_SECONDS`.

## Tela inicial

Depois do login, a tela inicial mostra blocos conforme as permissões de cada pessoa: perfil, notificações e atividade recente
para todos, e indicadores de usuários, acessos e alterações para quem gerencia. Os cartões levam à lista já filtrada. Use
`DASHBOARD_DISABLED_BLOCKS` para desligar blocos. Veja o [guia para adicionar um bloco](docs/guias/estender-template.md).

## Instalação pelo navegador

Enquanto não há usuários, todo acesso vai para `/install`, que funciona mesmo sem `.env`, chave ou banco. O assistente pede
o código de instalação, em `storage/app/private/system/install-code.txt` e no log. Se ele se perder, `php artisan
app:install-code` gera outro. Depois vêm os parâmetros, com teste de conexão, a preparação do banco e o primeiro usuário,
que nasce administrador e entra já autenticado. Concluído, o endereço responde 404. O cadastro público fica fechado até
ali, e `php artisan app:setup` continua como alternativa.

## Ambiente pelo painel

Em `/admin/environment`, administradores e perfis com `configura_env` e `visualizar_ambiente` editam o `.env` por grupos. Segredos nunca chegam
ao navegador: em branco, o valor atual é mantido. Variáveis fora de `.env.example` são tratadas como segredo. `APP_KEY`
não é alterada por ali. Ao salvar, a cópia anterior fica em `storage/app/private/system/.env.backup`, config, cache e
rotas são limpos, e o histórico guarda quem mudou o quê, com segredos mascarados. Mudança em `DB_*` só é aceita se a nova
conexão responder. Em produção, use `ENVIRONMENT_EDITOR_ENABLED=false` para deixar a tela só para leitura.

Em uma instalação já existente, rode o comando abaixo para criar as permissões de ambiente:

```bash
php artisan db:seed --class=AdminPanelSeeder
```

## Login

Depois da senha, a pessoa recebe um código de 6 dígitos no e-mail da conta. Com `MAIL_MAILER=log`, o código
fica no log do dia. Se o envio de e-mail estiver fora do ar, use `LOGIN_TWO_FACTOR_ENABLED=false`.

| Variável | Padrão | Uso |
|----------|--------|-----|
| `LOGIN_TWO_FACTOR_ENABLED` | `true` | Exige o código depois da senha |
| `LOGIN_CODE_EXPIRE` | `15` | Validade do código, em minutos |
| `LOGIN_CODE_ATTEMPTS` | `5` | Tentativas erradas por código |
| `LOGIN_CODE_SEND_LIMIT` | `5` | Códigos por usuário a cada período de validade, somando login e reenvio |
| `TURNSTILE_ENABLED` | `false` | Exige o captcha do Cloudflare Turnstile na tela de login |
| `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` | vazias | Chaves do Turnstile |

- O código fica no cache, protegido por hash. Limpar o cache encerra os logins pendentes, e a pessoa recomeça.
- Use um cache compartilhado se a aplicação rodar em mais de um servidor.
- Ligado sem as duas chaves, o captcha recusa todo login.
- O "Lembrar de mim" evita novos logins enquanto o cookie valer, e portanto também o código.
- O cadastro pela tela pública entra direto, sem código: ele confirma o e-mail depois, pelo link enviado.

## Regras de contas

- Toda criação de usuário passa por `App\Actions\CreateUser`.
- O campo `must_change_password` obriga a troca de senha. Para solicitar uma nova troca a um usuário, use
  `$user->requirePasswordChange()`.
- Novas rotas autenticadas devem usar o grupo de middleware `account.ready`. Um teste falha se alguma rota
  ficar de fora.

## Painel de administrador

O painel gerencia perfis, permissões e usuários. A preparação cria o perfil master, o perfil padrão e as 22
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

**Alterar e-mail.** No painel, somente administradores alteram o e-mail de uma conta, mesmo com `editar_usuario`:
com o e-mail, a recuperação de senha daria acesso à conta. Cada pessoa altera o próprio e-mail em seu perfil.

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

O menu fica na lateral: oculto no celular, aberto pelo ícone de três riscos, e recolhível no computador. Cada pessoa escolhe
o tema Claro, Escuro ou Sistema no menu da conta, e a escolha fica guardada nela. No celular, o sistema pode ser adicionado à
tela inicial e abre em tela cheia. As regras estão em [Layout](docs/especificacoes/layout.md).

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

Para configurar pela tela em vez de editar o `.env`, use a [instalação pelo navegador](#instalação-pelo-navegador).
O [guia de adaptação](docs/guias/adaptar-template.md) lista o que ajustar em um projeto novo.

Nenhum arquivo de código precisa ser alterado.

## Licença

Distribuído sob a [licença MIT](LICENSE).
