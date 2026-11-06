# Cobertura de permissões

## Objetivo

Garantir que toda ação restrita tenha permissão cadastrada e verificada.

## Regras

- O seeder cria as permissões de `lang/pt_BR/permissions.php`, sem duplicar.
- Toda rota exige permissão ou consta na lista de exceções, com o motivo.
- Toda permissão usada no código existe no seeder.

## Comportamentos

- `PermissionCoverageTest` falha se uma rota ou comando novo ficar de fora.
- Administradores não precisam de verificação.

## Configuração

Não há variáveis. Novas permissões entram em `lang/pt_BR/permissions.php`.

## Decisões

- **Lista de exceções com motivo:** rota sem permissão precisa ser escolha, não esquecimento.
