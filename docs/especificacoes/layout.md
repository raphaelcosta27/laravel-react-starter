# Layout

## Objetivo

Dar às telas a aparência e a fluidez de um aplicativo, no computador e no celular, com uma identidade própria e reutilizável.

## Regras

- O `AppShell` envolve as telas internas e não desmonta ao trocar de tela. `Auth/*`, `Install/*`, `Welcome` e `Error` ficam fora dele.
- Todo módulo tem ícone antes do nome. Os itens ficam em `resources/js/navigation.js`.
- O Painel e os módulos do projeto ficam no topo do menu. Usuários, Perfis, Permissões e Ambiente ficam em Config, com `group: CONFIG_GROUP`.
- Config fica no rodapé, logo acima da conta, e cresce para cima ao abrir. Aparece para quem tem ao menos um dos seus módulos, só com esses módulos.
- Todo botão tem ícone. Quando o sentido é evidente, fica só o ícone, com título e descrição. Ação destrutiva mantém o texto.
- Campos usam `kind` em `fieldProps`, que define ícone, tipo e teclado: `email`, `password`, `phone`, `person`, `pin`, `key`, `label`, `document` e `description`.
- As telas usam só tokens do tema. Cores fixas, como `grey.*` e hexadecimais, ficam fora.
- A cor da marca é livre: o tema calcula, em cada esquema, um tom com contraste de 4,5:1 sobre as superfícies.

## Comportamentos

| Largura | Menu |
|---|---|
| Abaixo de 600 px | Oculto. O ícone de três riscos o abre pela lateral esquerda |
| De 600 a 1199 px | Recolhido, só com ícones |
| A partir de 1200 px | Aberto, e recolhe pelo botão da barra ou por Ctrl+B |

- **Tema:** Claro, Escuro ou Sistema, no menu da conta. A escolha vale na hora e fica na conta. Sem login, vale a do aparelho.
- **Sem clarão:** um script no `<head>` aplica o esquema antes da primeira pintura.
- **Voltar do aparelho:** com o menu ou um formulário aberto, fecha o que está aberto e respeita a confirmação de alterações.
- **Avisos:** flutuam, sem deslocar o conteúdo. Erro fica até ser fechado, e a região anunciada a leitores de tela continua na página.
- **Atalhos:** Ctrl+K leva à busca da tabela, e Ctrl+B recolhe o menu.
- **Movimento reduzido:** as animações diminuem, conforme a preferência do sistema.
- **Impressão:** sai sem menus, sem barra e sem fundo escuro.
- **Instalação:** `/manifest.webmanifest` permite adicionar à tela inicial, com abertura em tela cheia. Não há uso offline.

## Configuração

`APP_LOGO`, `APP_LOGO_DARK`, `APP_TAGLINE`, `APP_ICON`, `APP_PRIMARY_COLOR` e `APP_SECONDARY_COLOR`. O novo módulo segue
[estender o template](../guias/estender-template.md#novo-módulo).

## Decisões

- **Menu oculto no celular:** a tela inteira fica para o conteúdo, e o menu entra pela esquerda, com o mesmo conteúdo do computador.
- **Layout persistente do Inertia:** evita desmontar o menu sem reescrever cada tela.
- **Variáveis de CSS nos temas:** trocar o tema não redesenha a árvore do React.
- **Entrada extra no histórico:** sem ela, o voltar sairia do sistema na primeira tela aberta.
- **Sem service worker:** só faria sentido com uso offline.
