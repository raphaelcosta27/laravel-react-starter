# Interface em Material Design

## Objetivo

Dar a todas as telas o mesmo visual, com ações discretas e formulários em modal.

## Regras

- As telas usam Material UI, em sua versão gratuita.
- Ações de tabela são ícones com título e descrição, por mouse, teclado ou toque longo.
- Criação e edição abrem em modal.
- As telas cumprem a WCAG 2.2 AA.

## Comportamentos

- O modal ocupa a tela inteira abaixo de 600 px e pede confirmação antes de descartar alterações.
- Formulários têm endereço próprio; salvar ou fechar mantém filtros, ordem e página.

## Configuração

`APP_PRIMARY_COLOR` e `APP_SECONDARY_COLOR`. Uma cor inválida volta ao padrão do tema.

## Decisões

- **Seletor nativo:** mantém o teclado e o toque do navegador.
- **Endereço por formulário:** o link pode ser compartilhado e o botão voltar funciona.
