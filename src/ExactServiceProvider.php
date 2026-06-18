<?php

declare(strict_types=1);

namespace Emeq\ExactApi;

use Emeq\ExactApi\Auth\AuthConnector;
use Emeq\ExactApi\Auth\AuthorizeUrlBuilder;
use Emeq\ExactApi\Auth\OAuthAuthenticator;
use Emeq\ExactApi\Contracts\ExactCredentialResolver;
use Emeq\ExactApi\Contracts\TokenStore;
use Emeq\ExactApi\Data\ExactCredentials;
use Emeq\ExactApi\Exceptions\MissingCredentialResolverException;
use Emeq\ExactApi\Exceptions\MissingTokenStoreException;
use Emeq\ExactApi\Http\ExactConnector;
use Emeq\ExactApi\Http\Middleware\VerifyExactSignature;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Routing\Router;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ExactServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('exact-api')
            ->hasConfigFile('exact');
    }

    public function packageBooted(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('verify.exact.signature', VerifyExactSignature::class);
    }

    public function packageRegistered(): void
    {
        // Singleton AuthConnector zodat MockClient-fixtures in tests deze raken.
        $this->app->singleton(AuthConnector::class, function ($app): AuthConnector {
            /** @var \Illuminate\Contracts\Config\Repository $config */
            $config = $app->make('config');

            return new AuthConnector(
                baseUrl: (string) $config->get('exact.auth_base_url', 'https://start.exactonline.nl'),
                timeoutSeconds: (int) $config->get('exact.http.timeout', 30),
            );
        });

        $this->app->singleton(AuthorizeUrlBuilder::class, function ($app): AuthorizeUrlBuilder {
            /** @var \Illuminate\Contracts\Config\Repository $config */
            $config = $app->make('config');

            return new AuthorizeUrlBuilder(
                authBaseUrl: (string) $config->get('exact.auth_base_url', 'https://start.exactonline.nl'),
            );
        });

        // Factory: per-connection authenticator (zelfde singleton AuthConnector +
        // gebonden TokenStore; lock/cache gedeeld, fingerprint-geïsoleerd).
        $this->app->bind('exact.authenticator-factory', function ($app) {
            return function (ExactCredentials $credentials) use ($app): OAuthAuthenticator {
                /** @var \Illuminate\Contracts\Config\Repository $config */
                $config = $app->make('config');

                return new OAuthAuthenticator(
                    credentials: $credentials,
                    tokenStore: $app->make(TokenStore::class),
                    authConnector: $app->make(AuthConnector::class),
                    cacheFactory: $app->make(CacheFactory::class),
                    lockStore: $config->get('exact.cache.lock_store'),
                    lockPrefix: (string) $config->get('exact.cache.lock_prefix', 'exact_refresh_'),
                    safetyMarginSeconds: (int) $config->get('exact.cache.ttl_safety_margin', 0),
                    lockWaitSeconds: (int) $config->get('exact.cache.lock_wait', 8),
                    lockTtlSeconds: (int) $config->get('exact.cache.lock_ttl', 10),
                );
            };
        });

        // Factory: per-division connector.
        $this->app->bind('exact.connector-factory', function ($app) {
            return function (OAuthAuthenticator $authenticator, string $division) use ($app): ExactConnector {
                /** @var \Illuminate\Contracts\Config\Repository $config */
                $config = $app->make('config');

                /** @var array{times: int, sleep: int, on: list<int>} $retry */
                $retry = (array) $config->get('exact.http.retry', ['times' => 3, 'sleep' => 1000, 'on' => [429, 500, 502, 503, 504]]);

                return new ExactConnector(
                    apiBaseUrl: (string) $config->get('exact.api_base_url', 'https://start.exactonline.nl'),
                    division: $division,
                    authenticator: $authenticator,
                    timeoutSeconds: (int) $config->get('exact.http.timeout', 30),
                    tries: (int) $retry['times'],
                    retryInterval: (int) $retry['sleep'],
                    retryOnStatuses: array_map('intval', $retry['on']),
                );
            };
        });

        // Resolver én TokenStore zijn host-verantwoordelijkheid (geen stille
        // defaults). Ontbreken → duidelijke exception i.p.v. generieke binding-fout.
        $this->app->singleton(Exact::class, function ($app): Exact {
            if ( ! $app->bound(ExactCredentialResolver::class)) {
                throw MissingCredentialResolverException::notBound();
            }

            if ( ! $app->bound(TokenStore::class)) {
                throw MissingTokenStoreException::notBound();
            }

            return new Exact(
                resolver: $app->make(ExactCredentialResolver::class),
                tokenStore: $app->make(TokenStore::class),
                authenticatorFactory: $app->make('exact.authenticator-factory'),
                connectorFactory: $app->make('exact.connector-factory'),
                authorizeUrlBuilder: $app->make(AuthorizeUrlBuilder::class),
                authConnector: $app->make(AuthConnector::class),
            );
        });
    }
}
