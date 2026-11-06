# Contribuir

## Fluxo

1. Cada funcionalidade tem uma especificação e um branch próprio, criado a partir do `main`.
2. Escreva os testes antes do código.
3. Só publique e mescle no `main` o que estiver resolvido e testado.
4. O próximo branch sempre parte do `main` atualizado.

## Antes de concluir

Uma funcionalidade só está pronta quando:

- todos os testes automatizados passam, e `vendor/bin/pint --dirty` não aponta nada;
- as verificações de acessibilidade passam;
- as novas opções estão em `.env.example` e em `lang/pt_BR/environment.php`;
- nenhum valor específico de projeto foi fixado no código;
- nenhuma entidade é excluída fisicamente;
- a documentação pública foi atualizada: o README e o documento em `docs/especificacoes/`.

## Testes

```bash
php artisan test
```

```bash
npx vitest run
```

```bash
php vendor/bin/phpunit -c phpunit.mysql.xml
```

O último roda em um banco MySQL descartável, para os testes de bloqueio real de linhas.

## Documentos

Sem dados pessoais, segredos, tarefas internas, caminhos locais ou referências a clientes. `DocsTest` confere isso,
junto com links internos e variáveis citadas. As especificações de trabalho (`specs/` e `.specify/`) ficam fora do Git.
