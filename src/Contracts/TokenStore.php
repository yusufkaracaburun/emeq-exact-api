<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Contracts;

use Emeq\ExactApi\Data\AccessToken;
use Emeq\ExactApi\Data\ExactCredentials;

/**
 * Persistence-seam voor Exact's OAuth2-tokenbundle (access + roterend refresh).
 *
 * De SDK bezit géén token-opslag — de host (Hub) implementeert dit contract en
 * persisteert tegen z'n encrypted Connection-model. Omdat Exact bij élke refresh
 * een NIEUW refresh-token uitgeeft en het oude direct invalideert, MOET put() de
 * geroteerde bundle atomair opslaan vóór de volgende API-call doorgaat — anders
 * is de Connection na de eerste refresh dood.
 *
 * Implementaties sleutelen op `$credentials->fingerprint()` (per Connection),
 * niet op clientId (= gedeelde app over alle tenants).
 */
interface TokenStore
{
    public function get(ExactCredentials $credentials): ?AccessToken;

    public function put(ExactCredentials $credentials, AccessToken $token): void;
}
