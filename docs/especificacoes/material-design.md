# Interface em Material Design

## Objetivo

Dar a todas as telas o mesmo visual, com ações discretas e formulários em modal.

## Regras

- As telas usam Material UI, em sua versão gratuita.
- Todo botão de tabela é um ícone com título e descrição, por mouse, teclado ou toque longo: barra, paginação, cadastro, ações em massa e ações da linha.
- Abaixo de 600 px, cada registro vira um cartão com os campos essenciais, sem rolagem horizontal da página.
- Em ponteiro de toque, os botões de ícone têm ao menos 44 px.
- Criação e edição abrem em modal.
- Estrutura, temas, ícones de campos e botões seguem a [especificação de layout](layout.md).
- As telas cumprem a WCAG 2.2 AA.

## Comportamentos

- O modal ocupa a tela inteira abaixo de 600 px e pede confirmação antes de descartar alterações.
- Formulários têm endereço próprio; salvar ou fechar mantém filtros, ordem e página.
- No cartão, seleção, interruptor, detalhes e ações continuam funcionando. Um seletor substitui a ordenação pelo cabeçalho.
- O gerenciador de colunas e o rodapé de totais só aparecem no computador.

## Configuração

`APP_PRIMARY_COLOR` e `APP_SECONDARY_COLOR`. Uma cor inválida volta ao padrão do tema.

## Decisões

- **Seletor nativo:** mantém o teclado e o toque do navegador.
- **Endereço por formulário:** o link pode ser compartilhado e o botão voltar funciona.
- **Campos na variante standard:** só a linha abaixo do campo, definida uma vez no tema.
- **Cartões dentro do `DataTable`:** a troca vale para qualquer tabela, sem código em cada tela.
- **Edição só pelo modal:** a tabela não tem campos; alterar um dado exige o botão Editar. O interruptor de situação continua na tabela, com confirmação e permissão próprias.
