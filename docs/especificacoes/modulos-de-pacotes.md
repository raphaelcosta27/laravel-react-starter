# Módulos de pacotes

## Objetivo

Permitir que um pacote Composer traga um módulo completo, com permissões, item de menu, rotas e telas, sem que ninguém
edite arquivos do projeto.

## Regras

- O pacote declara o módulo no `boot()` do próprio ServiceProvider, com `PackageModules::register()`.
- Um módulo de pacote segue as mesmas regras dos módulos do Template, descritas em
  [permissões por módulo](permissoes-por-modulo.md). Sem Visualizar, as demais permissões não têm efeito.
- A chave do módulo e cada código de permissão são únicos entre o Template e todos os pacotes.
- Todo código declarado em `basic` e `specific` traz título e descrição em `permissions`.
- Declaração errada falha na inicialização, com a mensagem `Módulo de pacote [<chave>]: <problema>.`
- O menu só exibe ou oculta o item. As rotas do pacote ficam protegidas no servidor com `can:<código>`.
- Os módulos existentes do Template continuam em `template.modules` e em `resources/js/navigation.js`.

## Comportamentos

- Os módulos de pacotes aparecem depois dos do Template, na ordem em que se registraram: na tela de Permissões, nos
  perfis, no modal do usuário e no menu.
- A preparação do painel cria as permissões do pacote marcadas como do sistema. Rodar de novo não muda nada: título
  editado continua, e permissão excluída não volta.
- O item de menu vai para Config com `group: 'config'`, ou para o menu principal sem grupo. Quem não tem Visualizar do
  módulo não o vê, e Config some se ficar vazio. Administradores veem todos.
- Uma página `'<pacote>::<Caminho>'` abre dentro do layout. As regras de telas fora do layout, como `Auth/`, valem para
  o caminho depois de `::`.
- Pacote removido: as permissões ficam no banco, pois nada é excluído de fato, e aparecem em "Outras permissões".

## Configuração

O passo a passo está em [estender o template](../guias/estender-template.md#módulo-em-pacote-composer). Em resumo:

| Parte | Onde fica |
|---|---|
| Módulo, permissões e menu | `PackageModules::register()` no ServiceProvider do pacote |
| Rotas | `loadRoutesFrom()`, com `web`, `auth`, `account.ready`, prefixo `admin`, nome `admin.` e `can:` |
| Telas | `vendor/raphaelcosta27/<pacote>/resources/js/Pages/**/*.jsx` |
| Ícone do menu | Um nome de `resources/js/navigationIcons.js` |
| Fornecedor dos pacotes | `resources/js/pages.js` (`PACKAGE_VENDOR` e o glob logo abaixo) |

Depois de instalar o pacote, rode `php artisan db:seed --class=AdminPanelSeeder`.

## Decisões

- **Registro no container, e não em `config()`:** vale com `config:cache` e em qualquer ordem de providers, e cada
  teste começa vazio.
- **Textos das permissões só na criação:** depois disso, valem os do banco, como no catálogo do Template.
- **Rótulo como chave de tradução:** o front traduz, como faz com os itens do Template. O pacote pode trazer as próprias
  traduções com `loadJsonTranslationsFrom()`.
- **Mapa fechado de ícones:** o build leva só os ícones importados. Nome desconhecido usa o de extensão.
- **Fornecedor fixo no front:** o Vite só aceita texto literal em `import.meta.glob`. O valor fica num único arquivo.
- **`preserveSymlinks` no Vite:** com o Composer em modo `path`, o pacote fica em `vendor/` como link para fora do
  projeto. Mantendo o caminho do link, as telas dele usam o `node_modules` do projeto. Sem isso, o build falha.
- **Pacote sem `node_modules` próprio:** as telas usam as bibliotecas do projeto. Uma cópia própria do React, por
  exemplo, quebraria a tela.
- **Testes do projeto conhecem os pacotes:** as regras de módulos e a cobertura de permissões juntam o catálogo do
  Template ao dos pacotes, para um projeto com pacote instalado continuar passando.
