# Endereços de usuários

## Objetivo

Oferecer um componente de endereço reutilizável e o cadastro de endereços no perfil.

## Regras

- São obrigatórios CEP, rua, número, bairro, cidade, estado e país.
- Cada pessoa tem no máximo um endereço principal, garantido por índice no banco.
- O primeiro endereço vira o principal, e o atual só deixa de ser quando outro é marcado.
- Excluir o principal com outros cadastrados pede a escolha do próximo.
- A opção de entrega exige o dono do endereço.
- Endereço de outra pessoa responde 404.

## Comportamentos

- O CEP completo preenche rua, bairro, cidade e estado, e o foco vai ao número.
- Falha na busca avisa e deixa preencher à mão, sem bloquear o envio.
- O que a pessoa muda durante a busca não é sobrescrito, e uma busca antiga não substitui a nova.
- Com país diferente de Brasil, busca, máscara e lista de estados ficam desligadas.
- "Sem número" preenche S/N.

## Configuração

`ADDRESS_DEFAULT_COUNTRY`, `ADDRESS_POSTAL_CODE_URLS`.

## Decisões

- **Busca pelo servidor:** o endereço do serviço fica configurável, só o CEP sai e há limite de uso.
- **Dois serviços em ordem:** ViaCEP e BrasilAPI. "Não existe" encerra; falha passa ao próximo.
- **Componente sem regras de usuário:** `AddressFields` recebe um `useForm`, e `ValidatesAddress` entrega as regras a qualquer formulário.
