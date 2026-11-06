# Permissões por módulo

## Objetivo

Organizar as permissões pelas áreas do sistema, para que a tela mostre só o que a pessoa pode fazer.

## Regras

- Toda permissão pertence a um módulo: Painel, Perfis, Permissões, Usuários ou Ambiente, ou a outro criado pelo projeto.
- **Todo módulo novo traz suas permissões.** Módulo com registros tem Visualizar, Criar, Editar e Excluir, além das específicas.
- Módulo sem registros só tem as permissões que liberam algo. O Ambiente tem Visualizar e Editar.
- Visualizar exibe o menu e a listagem, e nada mais. As demais permissões liberam botões e ações.
- Sem Visualizar, as demais permissões do módulo ficam guardadas, mas não têm efeito, na tela e no servidor.
- O Painel abre para qualquer pessoa logada. Ele só agrupa indicadores com permissão própria.
- Botão sem permissão não aparece. Não basta ficar desativado.
- Administradores têm tudo, sem verificação.

## Comportamentos

- Toda lista de permissões aparece por módulo, com título que expande e recolhe e a contagem de marcadas.
- Permissão antiga sem módulo aparece em "Outras".
- O modal do usuário abre pela tela de usuários e pelo módulo Permissões. Marcado é concedida, e desmarcado é revogada.
- Os badges Do perfil, Individual e Revogada mostram a origem. Só a diferença em relação ao perfil é guardada.
- Quem gerencia precisa de Visualizar de Permissões. Pela tela de usuários, também de Visualizar de Usuários.
- Ninguém concede o que não possui, ninguém ajusta a si mesmo, e só administradores agem sobre administradores.

## Configuração

Os módulos ficam em `template.modules`, com `label`, `view`, `basic`, `specific`, `without_records` e `enabled_by`. O
passo a passo está em [estender o template](../guias/estender-template.md#novo-módulo).

## Decisões

- **Regra no cálculo das permissões efetivas:** rotas, políticas, menu e botões já leem esse cálculo, inclusive os futuros.
- **Conceder Visualizar conta como conceder o resto:** ele ativa o que estava sem efeito, por isso a pessoa precisa possuir também.
- **Módulos em configuração:** não há tela de módulos. Um projeto acrescenta uma entrada, sem alterar as telas.
- **Código de permissão não muda:** `configura_env` continua, agora com o título Editar Ambiente.
- **`exportar_dados` virou uma permissão por módulo:** ela só recebeu exclusão lógica, e os vínculos ficam guardados.
- **Reorganização repetível:** cada passo roda uma vez, para não desfazer o que veio depois.
- **Limite conhecido:** ao editar um perfil, a verificação olha o perfil, e não os ajustes individuais de quem o usa.
