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

## Nova variável de ambiente

Inclua-a em `.env.example` e a descrição em `lang/pt_BR/environment.php`. Sem isso, um teste falha e ela não aparece
agrupada na tela de ambiente.
