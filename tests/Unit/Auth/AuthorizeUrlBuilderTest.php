<?php

declare(strict_types=1);

use Emeq\ExactApi\Auth\AuthorizeUrlBuilder;
use Emeq\ExactApi\Data\AuthorizeUrlParameters;

it('builds the authorize URL with the required params', function (): void {
    $url = (new AuthorizeUrlBuilder('https://start.exactonline.nl'))
        ->build(new AuthorizeUrlParameters(clientId: 'cid', redirectUri: 'https://cb'));

    expect($url)->toStartWith('https://start.exactonline.nl/api/oauth2/auth?')
        ->and($url)->toContain('client_id=cid')
        ->and($url)->toContain('response_type=code')
        ->and($url)->toContain('redirect_uri=' . urlencode('https://cb'))
        ->and($url)->not->toContain('state=')
        ->and($url)->not->toContain('scope=');
});

it('includes state and scope when provided', function (): void {
    $url = (new AuthorizeUrlBuilder('https://start.exactonline.nl'))
        ->build(new AuthorizeUrlParameters(clientId: 'cid', redirectUri: 'https://cb', state: 'xyz', scope: 'read'));

    expect($url)->toContain('state=xyz')
        ->and($url)->toContain('scope=read');
});
