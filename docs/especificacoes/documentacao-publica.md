# Documentação pública

## Objetivo

Explicar o template a quem o encontra e registrar as regras para quem o usa como base.

## Regras

- `docs/` guarda guias e especificações lógicas; as especificações de trabalho ficam fora do Git.
- Toda funcionalidade entregue tem um documento em `docs/especificacoes/`.
- Os documentos não têm dados pessoais, segredos, tarefas internas, caminhos locais ou clientes.
- Uma funcionalidade só é concluída com a documentação pública atualizada.

## Comportamentos

- `DocsTest` falha com link interno quebrado, variável que não existe ou dado que não deveria estar ali.

## Configuração

Não há variáveis.

## Decisões

- **READMEs guardam o uso; `docs/` guarda regras e motivos:** evita repetir o mesmo texto em dois lugares.
- **Verificação nos testes:** roda no CI que já existe, sem ferramenta nova.
- **Só em português, além do README bilíngue:** o inglês fica como evolução possível.
