<?php

use Laravel\Sanctum\Sanctum;

return [
    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Domaines autorisés pour l'authentification stateful (cookies).
    | L'app mobile utilise des tokens Bearer, mais l'interface web
    | utilise l'authentification par session/cookies.
    |
    */

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,127.0.0.1,::1,mie.digitalforges.org')),

    'guard' => ['web'],

    'expiration' => null,

    /*
    |--------------------------------------------------------------------------
    | Token Expiration (pour l'app mobile)
    |--------------------------------------------------------------------------
    |
    | Durée de validité des tokens API en minutes.
    | null = jamais expire (recommandé pour les apps mobiles)
    | 43200 = 30 jours
    |
    */
    'token_expiration' => null,

    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    ],
];
