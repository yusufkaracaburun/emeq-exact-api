<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Contracts;

use Emeq\ExactApi\Data\ExactCredentials;

/**
 * Strategy die de ExactCredentials voor de *huidige* request/job/command levert.
 * De host-app implementeert dit en bindt het in de container.
 *
 * Belangrijk bij Exact: clientId/clientSecret/redirectUri horen bij de gedeelde
 * geregistreerde app — álle tenants koppelen via dezelfde app. De per-connection
 * discriminator (`connectionRef`) onderscheidt tenants en stuurt de fingerprint.
 */
interface ExactCredentialResolver
{
    public function resolve(): ExactCredentials;
}
