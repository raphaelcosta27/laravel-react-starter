# Painel de administrador

## Objetivo

Gerenciar perfis, permissões e usuários, com listagens prontas para reaproveitar em qualquer entidade.

## Regras

- Cada usuário tem um perfil; as permissões vêm do perfil, com ajustes individuais de conceder e revogar.
- Administradores passam por toda verificação.
- Sempre resta ao menos um administrador ativo.
- Quem não é administrador só concede o que possui.
- Somente administradores alteram o e-mail de outra conta.
- Nada é apagado de fato: toda entidade usa exclusão lógica, inclusive vínculos.

## Comportamentos

- As listagens ordenam, buscam, filtram, paginam e exportam no servidor, e só colunas declaradas chegam à consulta.
- Registros excluídos têm aba própria e podem ser restaurados.
- Reenviar e-mails tem limite por usuário de destino.

## Configuração

`ADMIN_PROFILE_NAME`, `DEFAULT_PROFILE_NAME`, `ADMIN_EMAIL_THROTTLE`, `ADMIN_EMAIL_THROTTLE_MINUTES`, `EXPORT_CSV_DELIMITER`.

## Decisões

- **Permissão por código:** a mesma verificação vale em rota, código e tela, e a tela só exibe ou oculta.
- **E-mail só por administrador:** com o e-mail, a recuperação de senha daria acesso à conta.
- **Regras que valem para administradores ficam nas Actions:** `Gate::before` ignora as policies para eles.
