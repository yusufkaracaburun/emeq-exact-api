<?php

declare(strict_types=1);

/**
 * Configuratie voor emeq/exact-api.
 */
return [

    /*
     * Base URL van de Exact Online REST API (geen trailing slash). Region-
     * specifiek: NL = start.exactonline.nl, BE = .be, DE = .de, UK = .co.uk.
     * Resource-calls hangen `/api/v1/{division}/...` hierachter.
     */
    'api_base_url' => env('EXACT_API_BASE_URL', 'https://start.exactonline.nl'),

    /*
     * Base URL van de OAuth2-endpoints. `/api/oauth2/auth` + `/api/oauth2/token`
     * worden hierachter gehangen. Zelfde host als de API op NL.
     */
    'auth_base_url' => env('EXACT_AUTH_BASE_URL', 'https://start.exactonline.nl'),

    'cache' => [
        /*
         * Cache-store voor de refresh-lock (Cache::lock). MOET een atomic-lock-
         * driver zijn (redis/database/memcached/dynamodb). Null = default store.
         */
        'lock_store' => env('EXACT_LOCK_STORE'),

        'lock_prefix' => 'exact_refresh_',

        /*
         * Seconden wachten om de per-connection refresh-lock te verkrijgen voor
         * we opgeven (een parallel request kan al aan het refreshen zijn).
         */
        'lock_wait' => 8,

        /*
         * Seconden dat de lock vastgehouden wordt (auto-release vangnet als een
         * worker sterft tijdens de refresh).
         */
        'lock_ttl' => 10,

        /*
         * Safety-margin in seconden. Exact WEIGERT een refresh zolang de access-
         * token nog geldig is ("Rate limit exceeded: access_token not expired"),
         * dus we refreshen pas ná expiry → margin = 0. Anders dan Snelstart/Mollie
         * (die proactief vóór expiry mogen refreshen).
         */
        'ttl_safety_margin' => 0,
    ],

    'token' => [
        /*
         * Key-prefix voor de default CacheTokenStore (standalone/tests). De Hub
         * bindt z'n eigen TokenStore tegen het encrypted Connection-model.
         */
        'store_prefix' => 'exact_token_',

        /*
         * TTL voor de CacheTokenStore-entry. NIET de 600s access-TTL — de bundle
         * bevat ook het langlevende refresh-token (Exact ~30 dagen), dus de cache
         * moet de bundle veel langer bewaren dan de access-token leeft.
         */
        'store_ttl' => env('EXACT_TOKEN_STORE_TTL', 2592000),
    ],

    'http' => [
        'timeout' => 30,

        'retry' => [
            'times' => 3,
            'sleep' => 1000,
            'on' => [429, 500, 502, 503, 504],
        ],
    ],

];
