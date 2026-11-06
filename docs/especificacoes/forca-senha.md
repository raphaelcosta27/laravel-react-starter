# Força da senha

## Objetivo

Não aceitar senha fraca e mostrar o progresso enquanto a pessoa digita.

## Regras

- Ao menos 8 caracteres, com maiúscula, minúscula, número e caractere especial, até 72.
- Vale para cadastro, troca e recuperação de senha.
- Espaço conta como caractere especial, e letras acentuadas contam como letras.

## Comportamentos

- A barra mostra cada critério por texto, além da cor.
- Quem tem senha antiga fora dos critérios continua entrando; a regra vale na próxima troca.

## Configuração

`PASSWORD_MIN_LENGTH`, de 8 a 72.

## Decisões

- **Uma única fonte de critérios:** servidor e tela leem `PasswordCriteria`, e um arquivo de exemplos testa os dois.
- **Limite de 72:** é onde o bcrypt deixa de considerar a senha.
