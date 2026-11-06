# Primeira inicialização

## Objetivo

Instalar pelo navegador e ajustar o ambiente pelo painel, sem editar arquivos.

## Regras

- Enquanto não há usuários, todo acesso vai ao assistente, e o cadastro público fica fechado.
- O assistente exige um código de uso único, disponível só a quem acessa o servidor.
- O primeiro usuário nasce administrador, com e-mail confirmado e sem troca de senha obrigatória.
- Existindo um usuário, mesmo excluído, o assistente responde 404.
- A tela de ambiente é de administradores e de quem tem `visualizar_ambiente`. Salvar exige também `configura_env`.
- Segredos nunca chegam ao navegador, ao log ou ao histórico. Em branco, o valor atual é mantido.
- A chave da aplicação não pode ser alterada pela tela.

## Comportamentos

- Sem `.env` ou sem chave, o sistema os cria no primeiro acesso.
- Dados de banco inválidos mostram o erro sem expor a senha.
- Cada passo pode ser repetido sem duplicar nada.
- Salvar troca só as linhas alteradas, mantém uma cópia anterior e limpa config, cache e rotas.
- Dois administradores salvando ao mesmo tempo: o segundo é avisado.
- Mudança em `DB_*` só é aceita se a nova conexão responder.
- Valor definido pelo servidor tem prioridade, e a tela indica o que está em vigor.

## Configuração

`INSTALL_ENABLED`, `ENVIRONMENT_EDITOR_ENABLED`.

## Decisões

- **Código de instalação:** sem ele, quem chegasse primeiro a um sistema publicado tomaria o controle.
- **Banco fora do ar não reabre o assistente:** um marcador em disco separa queda de instalação nova.
- **Login final por token de uso único:** a sessão do assistente é em arquivo, e a do sistema pode ser outra.
- **Variável fora do exemplo é tratada como segredo:** não dá para saber o que ela guarda.
- **Mudar o nome fixa o cookie de sessão:** ele deriva do nome, e mudar um sem o outro deslogaria todos.
