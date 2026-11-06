# Dashboard gerencial

## Objetivo

Fazer da tela inicial um resumo útil: a situação da própria conta para todas as pessoas e os indicadores de gestão para quem tem permissão.

## Regras

- A tela é formada por blocos independentes, na ordem da configuração.
- Bloco sem permissão, desligado ou sem o recurso ativo não aparece, e seus dados nem são calculados.
- Administradores veem todos os blocos ligados.
- A falha de um bloco aparece só nele e fica registrada no log.
- Os blocos de usuários exigem `visualizar_usuario`. O movimento do sistema exige `visualizar_historico`.
- "Sua atividade recente" nunca mostra valores, só o que mudou e quando.
- Contas excluídas só entram no total de excluídos.

## Comportamentos

| Bloco | Chave | Mostra |
|---|---|---|
| Seu perfil | `ProfileCompletion` | Percentual preenchido e link para o que falta |
| Notificações | `Notifications` | Não lidas e as 5 mais recentes, sem marcá-las como vistas |
| Sua atividade recente | `MyActivity` | As 5 ações mais recentes da própria pessoa |
| Usuários | `UsersOverview` | Ativos, inativos, confirmação pendente e excluídos |
| Contas com pendência | `PendingAccounts` | Troca de senha pendente e senha temporária expirada |
| Usuários por perfil | `ProfileDistribution` | Quantidade em cada perfil, inclusive os vazios |
| Acessos | `Accesses` | Ativos no período e contas sem acesso registrado |
| Novos usuários | `UserGrowth` | Cadastros por dia |
| Alterações no sistema | `SystemMovement` | Ações por dia |

Usuários aparece como quatro cartões de indicador. Cada quantidade de Usuários, Contas com pendência e Usuários por perfil abre a lista já filtrada, com o mesmo total. Quem não pode ver os excluídos vê o número sem link.

O último acesso é gravado quando o login termina, depois do código por e-mail. O retorno pelo "lembrar de mim" também conta.

## Configuração

| Variável | Padrão | Efeito |
|---|---|---|
| `DASHBOARD_DISABLED_BLOCKS` | vazio | Chaves dos blocos desligados, separadas por vírgula |
| `DASHBOARD_GROWTH_DAYS` | 30 | Dias do gráfico de novos usuários |
| `DASHBOARD_MOVEMENT_DAYS` | 7 | Dias do gráfico de alterações |
| `DASHBOARD_ACCESS_DAYS` | 30 | Dias do indicador de acessos |
| `DASHBOARD_PROFILE_ITEMS` | os cinco itens | Itens que contam no percentual do perfil |

Os nomes exibidos em "Sua atividade recente" ficam em `lang/pt_BR/history.php`. Campo fora desse arquivo não aparece.

## Decisões

- **Uma tela só:** blocos pessoais e de gestão ficam juntos, para não haver duas telas iniciais.
- **Gráficos com `@mui/x-charts`:** eixos, tooltip e área com gradiente seguem o template de dashboard do Material UI. A biblioteca só é baixada por quem vê um gráfico, e a tabela com os mesmos valores continua disponível.
- **Seções e tamanhos:** cartões com borda fina, em Visão geral, Detalhes e Sua conta. Quem sobra na linha cresce, então desligar um bloco não deixa vão.
- **Mesmas regras da lista:** cartões e lista usam os mesmos escopos do modelo, para o total nunca divergir.
- **"Sem acesso registrado":** contas anteriores ao recurso não têm esse dado, então dizer "nunca entrou" seria falso.
- **Último acesso fora do histórico:** entrar no sistema não é alteração de dados.
- **Ações, não campos:** salvar um formulário com três mudanças conta como uma alteração.
