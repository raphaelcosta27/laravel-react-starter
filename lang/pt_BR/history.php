<?php

/*
| Nomes legíveis do histórico, usados em "Sua atividade recente". Tabelas e campos
| que não estão aqui não aparecem para a pessoa.
*/
return [
    'actions' => [
        'created' => 'Criado',
        'updated' => 'Alterado',
        'deleted' => 'Excluído',
        'restored' => 'Restaurado',
    ],

    'tables' => [
        'users' => 'Conta',
        'user_dados' => 'Dados pessoais',
        'user_contatos' => 'Contato',
        'user_enderecos' => 'Endereço',
    ],

    'fields' => [
        'users' => [
            'name' => 'Nome',
            'email' => 'E-mail',
            'password' => 'Senha',
        ],
        'user_dados' => [
            'preferred_name' => 'Como quer ser chamado',
            'birth_date' => 'Data de nascimento',
            'gender' => 'Gênero',
            'cpf' => 'CPF',
            'avatar_path' => 'Imagem de perfil',
        ],
        'user_contatos' => [
            'type' => 'Tipo',
            'value' => 'Telefone ou e-mail',
            'is_whatsapp' => 'WhatsApp',
            'is_emergency' => 'Contato de emergência',
            'relationship' => 'Parentesco',
            'contact_name' => 'Nome do contato',
            'deleted_at' => 'Exclusão',
        ],
        'user_enderecos' => [
            'postal_code' => 'CEP',
            'street' => 'Rua',
            'number' => 'Número',
            'complement' => 'Complemento',
            'reference' => 'Ponto de referência',
            'neighborhood' => 'Bairro',
            'city' => 'Cidade',
            'state' => 'Estado',
            'country' => 'País',
            'is_main' => 'Endereço principal',
            'is_billing' => 'Endereço de cobrança',
            'is_delivery' => 'Endereço de entrega',
            'recipient_name' => 'Dono do endereço',
            'deleted_at' => 'Exclusão',
        ],
    ],
];
