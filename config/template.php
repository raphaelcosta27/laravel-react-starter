<?php

return [

    /*
    | Ativa a tela pública de registro de usuários.
    */
    'registration_enabled' => (bool) env('REGISTRATION_ENABLED', false),

    /*
    | Validade da senha temporária e do link de confirmação, em minutos.
    */
    'temporary_password_expire' => (int) env('TEMPORARY_PASSWORD_EXPIRE', 60),

    /*
    | Verificação em duas etapas do login. Validade em minutos. O limite de
    | códigos conta login e reenvio, por usuário, a cada período de validade.
    */
    'login' => [
        'two_factor_enabled' => (bool) env('LOGIN_TWO_FACTOR_ENABLED', true),
        'code_expire' => (int) env('LOGIN_CODE_EXPIRE', 15),
        'code_attempts' => (int) env('LOGIN_CODE_ATTEMPTS', 5),
        'code_send_limit' => (int) env('LOGIN_CODE_SEND_LIMIT', 5),
    ],

    /*
    | Identidade visual da aplicação.
    */
    'branding' => [
        'logo' => env('APP_LOGO', '/images/logo.svg'),
        'favicon' => env('APP_FAVICON', '/favicon.ico'),
        'primary_color' => env('APP_PRIMARY_COLOR'),
        'secondary_color' => env('APP_SECONDARY_COLOR'),
    ],

    /*
    | Dias de guarda dos arquivos de log. Vazio ou inválido usa 14 dias.
    */
    'logs' => [
        'retention_days' => env('LOG_DAILY_DAYS'),
    ],

    /*
    | Histórico de alterações. Os campos mascarados somam-se aos $hidden de cada
    | modelo. Nomes de campo separados por vírgula.
    */
    'history' => [
        'masked_fields' => array_filter(array_map('trim', explode(',', (string) env('HISTORY_MASKED_FIELDS', 'password,remember_token')))),
        'ignored_fields' => array_filter(array_map('trim', explode(',', (string) env('HISTORY_IGNORED_FIELDS', '')))),
    ],

    /*
    | Senhas. O tamanho mínimo pode subir, mas nunca fica abaixo de 8.
    */
    'password' => [
        'min_length' => (int) env('PASSWORD_MIN_LENGTH', 8),
    ],

    /*
    | Dados pessoais da página de perfil. Os gêneros ficam em inglês e são
    | traduzidos pelos arquivos de idioma. Tamanho da imagem em kilobytes.
    */
    'profile' => [
        'genders' => array_filter(array_map('trim', explode(',', (string) env('PROFILE_GENDERS', 'Female,Male,Non-binary,Prefer not to say,Other')))),
        'cpf_required' => (bool) env('PROFILE_CPF_REQUIRED', false),
        'avatar_max_kb' => (int) env('PROFILE_AVATAR_MAX_KB', 2048),
        'avatar_disk' => env('PROFILE_AVATAR_DISK', 'local'),
        'relationships' => array_filter(array_map('trim', explode(',', (string) env('PROFILE_RELATIONSHIPS', 'Mother,Father,Spouse or partner,Child,Sibling,Grandparent,Uncle or aunt,Cousin,Friend,Other relationship')))),
    ],

    /*
    | Notificações em tela. O sino consulta o servidor a cada poll_seconds.
    */
    'notifications' => [
        'enabled' => (bool) env('NOTIFICATIONS_ENABLED', true),
        'poll_seconds' => (int) env('NOTIFICATIONS_POLL_SECONDS', 60),
        'per_page' => (int) env('NOTIFICATIONS_PER_PAGE', 20),
    ],

    /*
    | Endereços. {cep} é trocado pelos dígitos; os serviços são tentados em ordem.
    */
    'address' => [
        'default_country' => env('ADDRESS_DEFAULT_COUNTRY', 'Brasil'),
        'postal_code_urls' => array_filter(array_map('trim', explode(',', (string) env('ADDRESS_POSTAL_CODE_URLS', 'https://viacep.com.br/ws/{cep}/json/,https://brasilapi.com.br/api/cep/v1/{cep}')))),
    ],

    /*
    | Usuário criado por "php artisan app:setup".
    */
    'initial_user' => [
        'name' => env('INITIAL_USER_NAME', 'Administrador'),
        'email' => env('INITIAL_USER_EMAIL', 'admin@example.com'),
        'password' => env('INITIAL_USER_PASSWORD'),
    ],

    /*
    | Painel de administrador. Os nomes valem somente na criação dos perfis:
    | depois disso, o sistema os identifica por "system_key".
    */
    'admin' => [
        'admin_profile_name' => env('ADMIN_PROFILE_NAME', 'Administrador'),
        'default_profile_name' => env('DEFAULT_PROFILE_NAME', 'Usuário'),
        'email_throttle' => (int) env('ADMIN_EMAIL_THROTTLE', 3),
        'email_throttle_minutes' => (int) env('ADMIN_EMAIL_THROTTLE_MINUTES', 10),
        'csv_delimiter' => env('EXPORT_CSV_DELIMITER', ';'),
    ],

];
