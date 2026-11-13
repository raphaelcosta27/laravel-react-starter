# Estender o template

## Nova entidade

1. Crie o modelo com `use RecordsChanges, SoftDeletes;`. Testes falham sem o histórico ou com exclusão física.
2. Coloque as regras em uma Action, como `App\Actions\SaveContact`.
3. Busque registros da pessoa pela relação, como `$request->user()->contacts()`: o de outra pessoa responde 404.

## Nova função restrita

1. Inclua o código em `lang/pt_BR/permissions.php`.
2. Proteja a rota com `can:codigo` dentro do grupo `account.ready`.
3. Na tela, use `useCan()` só para exibir ou ocultar.

`PermissionCoverageTest` falha se a rota ficar sem permissão e fora da lista de exceções.

## Novo módulo

Todo módulo novo traz suas permissões, e os testes apontam o que faltar. Veja
[permissões por módulo](../especificacoes/permissoes-por-modulo.md).

1. Acrescente o módulo em `template.modules`, com Visualizar, Criar, Editar, Excluir e as específicas.
2. Escreva título e descrição de cada código em `lang/pt_BR/permissions.php`.
3. Rode o comando abaixo, que cria as permissões sem alterar as existentes.
4. Proteja as rotas com `can:` e declare o item em `resources/js/navigation.js`, com a permissão Visualizar e um ícone.
   Os dois tamanhos do menu leem desse arquivo. Módulo de administração leva `group: CONFIG_GROUP` e vai para Config.

```bash
php artisan db:seed --class=AdminPanelSeeder
```

## Módulo em pacote Composer

Um pacote traz o módulo inteiro sem editar arquivos do projeto. Veja
[módulos de pacotes](../especificacoes/modulos-de-pacotes.md).

1. No `boot()` do ServiceProvider do pacote, registre o módulo:

   ```php
   use App\Support\Permissions\PackageModules;

   PackageModules::register('settings', [
       'label' => 'Settings',
       'view' => 'gerenciar_configuracoes_sistema',
       'without_records' => true,
       'basic' => ['gerenciar_configuracoes_sistema'],
       'specific' => [],
       'enabled_by' => null,
       'permissions' => [
           'gerenciar_configuracoes_sistema' => [
               'title' => 'Gerenciar Configurações de Sistema',
               'description' => 'Permissão permite consultar e alterar as configurações do sistema.',
           ],
       ],
       'menu' => [
           'label' => 'Settings',
           'route' => 'admin.settings',
           'index' => 'admin.settings.index',
           'icon' => 'settings',
           'group' => 'config',
       ],
   ]);
   ```

   `label`, `view`, `basic` e o texto de cada código são obrigatórios. `menu` é opcional e, quando vem, precisa de
   `label`, `route`, `index` e `icon`. `group` aceita `'config'` ou nada.
2. Registre as rotas com `loadRoutesFrom()`, no padrão de `routes/admin.php`: middleware `web`, `auth` e
   `account.ready`, prefixo `admin`, nome `admin.` e `can:<código>` em cada rota.
3. Renderize as telas com o nome do pacote: `Inertia::render('laravel-settings::Admin/Index')` abre
   `vendor/raphaelcosta27/laravel-settings/resources/js/Pages/Admin/Index.jsx`, dentro do layout.
   As telas podem importar `@/…`, `@mui/*` e `@inertiajs/react`. O pacote não deve ter `node_modules` próprio.
4. Escolha o ícone entre `settings`, `article`, `tune`, `palette`, `campaign`, `category`, `description` e
   `extension`. Nome desconhecido usa `extension`. Para oferecer outro, acrescente-o em
   `resources/js/navigationIcons.js`.
5. Se o rótulo precisar de tradução, use `loadJsonTranslationsFrom()` no pacote.
6. Instale o pacote e rode o comando abaixo, que cria as permissões dele sem alterar as existentes.

```bash
php artisan db:seed --class=AdminPanelSeeder
```

Nos testes do pacote, confira a tela com `->component('laravel-settings::Admin/Index', false)`: o segundo argumento
evita procurar o arquivo em `resources/js/Pages`. Se os pacotes forem de outro fornecedor, troque `PACKAGE_VENDOR` e o
glob em `resources/js/pages.js`.

## Nova listagem

Use `resources/js/Components/DataTable` com uma definição em `app/Support/Tables/Definitions`. Veja o
[painel de administrador](../especificacoes/painel-administrador.md).

Marque as colunas do cartão de celular com `meta: { essential: true }`. A primeira é o título. Sem nenhuma
marcada, valem as três primeiras.

O botão de cadastro vai na propriedade `toolbar`, e cada ação em massa declara `icon` e `description`:

```jsx
<ActionIconButton
    emphasis
    icon={PersonAddIcon}
    label={__('New user')}
    description={__('Opens the form to register a user.')}
    href={route('admin.users.create')}
/>
```

## Formulário com endereço

Renderize `Components/AddressFields.jsx` com um `useForm` que tenha os campos de endereço, e aplique
`App\Http\Requests\Concerns\ValidatesAddress` na Request. Veja [endereços](../especificacoes/enderecos.md).

## Avisar uma pessoa

```php
ScreenNotifications::send($user, 'Título', 'Texto', 'success', '/dashboard');
```

## Novo bloco na tela inicial

1. Crie uma classe em `app/Support/Dashboard/Blocks` que implemente `App\Support\Dashboard\Block`. O método `permission()` devolve o código exigido, ou `null` para todas as pessoas.
2. Adicione a classe em `template.dashboard.blocks`, no arquivo `config/template.php`. A ordem da lista é a ordem na tela.
3. Crie `resources/js/Components/Dashboard/Blocks/<Chave>.jsx`, com o mesmo nome devolvido por `key()`. O componente recebe `{ data }`, e título e falha são tratados pela tela. Para escolher o lugar, exporte `layout`, com `section` (`overview`, `details` ou `account`) e `size` (`full`, `half` ou `third`). Sem ele, o bloco ocupa um terço da seção Detalhes.

A tela inicial não precisa de alteração. Para desligar um bloco, coloque a chave em `DASHBOARD_DISABLED_BLOCKS`.

## Nova variável de ambiente

Inclua-a em `.env.example` e a descrição em `lang/pt_BR/environment.php`. Sem isso, um teste falha e ela não aparece
agrupada na tela de ambiente.
