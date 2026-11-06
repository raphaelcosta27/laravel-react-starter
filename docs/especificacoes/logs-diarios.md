# Logs diários

## Objetivo

Separar o log por dia e apagar o que passou do prazo.

## Regras

- Cada dia tem um arquivo `laravel-AAAA-MM-DD.log`.
- `php artisan app:clean-logs` apaga só arquivos com esse nome exato e mais antigos que o prazo.

## Comportamentos

- O comando roda todo dia pelo agendador, que precisa estar ativo no servidor.
- Um `laravel.log` anterior não é movido nem apagado.

## Configuração

`LOG_STACK`, `LOG_DAILY_DAYS`, `LOG_LEVEL`.

## Decisões

- **Nome exato:** evita apagar arquivo que não é log diário.
- **Dia pelo fuso da aplicação:** o nome do arquivo acompanha `config/app.php`.
