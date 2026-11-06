# Ambiente Docker

Sobe o sistema sem PHP, Apache ou Node na máquina. Só o Docker é necessário.

```bash
cd docker
docker compose --profile mysql up -d --build
```

Abra `http://localhost:8080`. Sem usuários, o assistente de instalação abre. O `.env` nasce apontando para o MySQL do
ambiente, e o código de instalação pode ser lido assim:

```bash
docker compose exec app cat storage/app/private/system/install-code.txt
```

A primeira subida instala as dependências e compila a interface, e leva alguns minutos.

## Serviços

| Serviço | Papel |
| --- | --- |
| `app` | PHP e Apache. Monta a raiz do projeto (`../`) |
| `assets` | Compila a interface se `public/build` não existir, e termina |
| `scheduler` | Roda `schedule:work` |
| `queue` | Roda `queue:work` |
| `mysql` | Banco, no perfil `mysql`. Não expõe porta na máquina |
| `redis` | Perfil `redis`. O sistema não usa por padrão |
| `horizon` | Perfil `horizon`. Exige `composer require laravel/horizon` e `QUEUE_CONNECTION=redis` |

Redis e Horizon não são necessários hoje: sessão e cache usam arquivo, e a fila é `sync`. Com `QUEUE_CONNECTION=sync`, o
serviço `queue` fica ocioso até o projeto usar uma fila real.

## Opções

Copie `docker/.env.example` para `docker/.env` para mudar portas, banco e perfis. Com `COMPOSE_PROFILES=mysql` nesse
arquivo, basta `docker compose up -d`.

**Banco externo:** defina `DOCKER_DB_HOST` e suba sem o perfil `mysql`. Se o `.env` da raiz já existe, ajuste `DB_*` nele.

**HTTPS:** coloque `server.crt` e `server.key` em `docker/certs`, defina `DOCKER_SSL_ENABLED=true` e suba de novo. O HTTP
passa a redirecionar para o HTTPS, na porta `DOCKER_HTTPS_PORT`. Sem os arquivos, o `app` termina com a mensagem do que
falta. Os certificados não vão para o repositório.

**Versão do PHP:** fica em `ARG PHP_VERSION`, em `docker/php/Dockerfile`.

## Comandos

```bash
docker compose exec -u www-data app php artisan test
```

```bash
docker compose run --rm assets npx vitest run
```

```bash
docker compose run --rm assets sh -c "npm ci && npm run build"
```

```bash
docker compose down
```

`down` mantém banco e arquivos. `down -v` apaga os volumes, inclusive o banco.

## Problemas comuns

- **Porta ocupada:** troque `DOCKER_HTTP_PORT` ou `DOCKER_HTTPS_PORT` em `docker/.env`.
- **Interface desatualizada:** rode o comando de compilação acima.
- **O `app` não fica saudável:** veja `docker compose logs app`.
- **Erro de permissão em Linux:** o `www-data` assume o dono da pasta do projeto. Se ela for do `root`, troque o dono.
- **Mudou `docker/.env` e nada aconteceu:** o `.env` da raiz só é gerado na primeira subida. Ajuste `DB_*` nele.
- **Windows:** a pasta montada é mais lenta. Clonar dentro do WSL resolve.
