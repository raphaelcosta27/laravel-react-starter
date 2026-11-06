# Instalação Docker

## Objetivo

Subir o sistema sem instalar PHP, Apache ou Node na máquina.

## Regras

- Os arquivos ficam em `docker/`, e o compose monta a raiz do projeto com `../`.
- O servidor é o Apache, com a versão estável mais recente do PHP, indicada em um único lugar.
- Só imagens oficiais, com versão fixada.
- A imagem não leva código nem segredos.

## Comportamentos

- Sem `.env`, ele nasce apontando para o banco do ambiente, e o assistente de instalação assume.
- Dependências e interface são preparadas na primeira subida.
- O MySQL é opcional e não expõe porta na máquina.
- Agendador e fila rodam em serviços próprios.
- HTTPS liga por opção e, sem certificado, a subida falha dizendo o que falta.

## Configuração

`DOCKER_HTTP_PORT`, `DOCKER_HTTPS_PORT`, `DOCKER_SSL_ENABLED`, `DOCKER_DB_HOST`, `DOCKER_DB_DATABASE`, `DOCKER_DB_USERNAME`,
`DOCKER_DB_PASSWORD`, em `docker/.env`.

## Decisões

- **Redis e Horizon opcionais:** sessão e cache usam arquivo, e o Horizon não está instalado.
- **`node_modules` em volume:** binários do Linux não se misturam com os da máquina.
- **`www-data` assume o dono da pasta:** em Linux, grava sem ajuste manual de permissões.
- **Validação em CI:** um workflow sobe o ambiente, roda a suíte e confere persistência e HTTPS.
