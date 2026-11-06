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

## Nova listagem

Use `resources/js/Components/DataTable` com uma definição em `app/Support/Tables/Definitions`. Veja o
[painel de administrador](../especificacoes/painel-administrador.md).

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
