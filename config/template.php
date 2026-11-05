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
    | Identidade visual da aplicação.
    */
    'branding' => [
        'logo' => env('APP_LOGO', '/images/logo.svg'),
        'favicon' => env('APP_FAVICON', '/favicon.ico'),
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
