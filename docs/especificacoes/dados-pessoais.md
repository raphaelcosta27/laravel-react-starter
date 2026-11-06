# Dados pessoais

## Objetivo

Guardar nome completo, como a pessoa quer ser chamada, nascimento, gênero, CPF e imagem de perfil.

## Regras

- Tabela `user_dados`, um registro por conta, criado no primeiro salvamento.
- O nome completo acompanha `users.name` nos dois sentidos.
- O CPF é validado, guardado sem máscara e único entre contas não excluídas.
- O CPF fica oculto na serialização, mascarado no histórico e fora da sessão.
- A imagem aceita JPG, PNG e WebP, conferidos pelo conteúdo do arquivo.

## Comportamentos

- A saudação usa o nome preferido, com o nome da conta como alternativa.
- Sem imagem, aparecem as iniciais.
- Excluir a conta libera o CPF; restaurá-la é recusado se outra conta já o usa.

## Configuração

`PROFILE_GENDERS`, `PROFILE_CPF_REQUIRED`, `PROFILE_AVATAR_MAX_KB`, `PROFILE_AVATAR_DISK`.

## Decisões

- **Imagem em disco privado:** servida só à própria pessoa, sem depender de `storage:link`.
- **Trocar a imagem não apaga a anterior:** segue a regra de preservar dados. Projetos sujeitos a pedidos de eliminação precisam tratar esses arquivos.
- **Um formulário para os nomes:** dois campos de nome em telas diferentes ficariam fora de sincronia.
