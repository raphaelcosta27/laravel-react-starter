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
- Sem imagem, ou se ela falhar, aparecem as iniciais.
- A tabela de usuários mostra a foto ao lado do nome, inclusive na aba de excluídos.
- Excluir a conta libera o CPF; restaurá-la é recusado se outra conta já o usa.

## Configuração

`PROFILE_GENDERS`, `PROFILE_CPF_REQUIRED`, `PROFILE_AVATAR_MAX_KB`, `PROFILE_AVATAR_DISK`.

## Decisões

- **Imagem em disco privado:** servida à própria pessoa e a quem tem `visualizar_usuario`, sem depender de `storage:link`.
- **Miniatura com o arquivo original:** gerar versões menores exigiria a extensão GD, ausente no Docker. A imagem só é baixada quando aparece na tela.
- **Trocar a imagem não apaga a anterior:** segue a regra de preservar dados. Projetos sujeitos a pedidos de eliminação precisam tratar esses arquivos.
- **Um formulário para os nomes:** dois campos de nome em telas diferentes ficariam fora de sincronia.
- **CPF validado ao digitar:** o CPF concluído e errado fica vermelho na hora e bloqueia o salvar. Pela metade, bloqueia e só avisa ao sair do campo. O servidor segue validando.
