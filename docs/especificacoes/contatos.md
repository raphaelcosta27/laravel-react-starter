# Contatos de usuários

## Objetivo

Permitir que cada pessoa mantenha telefones e e-mails de contato no próprio perfil.

## Regras

- Tabela `user_contatos`; o tipo é telefone ou e-mail.
- Telefone brasileiro, fixo ou celular, com DDD existente, guardado só com dígitos.
- O valor não se repete entre os contatos ativos da pessoa.
- WhatsApp vale só para telefone.
- Contato de emergência exige parentesco e nome; sem a opção, eles não são guardados.
- Contato de outra pessoa responde 404, mesmo para administradores.

## Comportamentos

- Um contato de e-mail não muda o e-mail de login.
- Mudar o tipo no formulário limpa o valor e desmarca o WhatsApp.
- A exclusão é lógica, e o valor excluído pode ser cadastrado de novo.

## Configuração

`PROFILE_RELATIONSHIPS`, traduzidos por `lang/pt_BR.json`.

## Decisões

- **Número estrangeiro fora do escopo:** a validação segue o plano de numeração brasileiro.
- **Contatos ficam com a conta:** excluir a conta não os altera, e eles voltam com ela.
- **404 em vez de 403:** a resposta não revela que o contato existe.
