<?php

declare(strict_types=1);

namespace App\Bridge\Mercure;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\RemoteHubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Reports the hub's public URL on the origin of the current request instead of a
 * statically configured one. The hub is embedded in the app's own web server, so it
 * always answers on the origin serving the dashboard — whatever scheme, host and port
 * the browser sees (plain HTTP on a published port, TLS terminated by a reverse proxy,
 * or native HTTPS).
 *
 * Symfony derives the mercureAuthorization cookie from this URL, including its Secure
 * flag: a static https:// URL sets it even over plain HTTP, and the browser then drops
 * the cookie, leaving the subscription anonymous.
 */
#[AsDecorator('mercure.hub.default')]
readonly class SameOriginHub implements RemoteHubInterface
{
    /** Path the `mercure` directive is mounted on, in every Caddyfile of the project. */
    public const string PATH = '/.well-known/mercure';

    public function __construct(
        #[AutowireDecorated]
        private RemoteHubInterface $hub,
        private RequestStack $requestStack,
    ) {}

    public function getPublicUrl(): string
    {
        $publicUrl = $this->hub->getPublicUrl();

        // No hub configured: callers read the empty value as "skip publishing".
        if ($publicUrl === '') {
            return '';
        }

        $request = $this->requestStack->getMainRequest();

        return $request === null ? $publicUrl : $request->getSchemeAndHttpHost() . self::PATH;
    }

    public function getUrl(): string
    {
        return $this->hub->getUrl();
    }

    public function getFactory(): ?TokenFactoryInterface
    {
        return $this->hub->getFactory();
    }

    public function publish(Update $update): string
    {
        return $this->hub->publish($update);
    }
}
