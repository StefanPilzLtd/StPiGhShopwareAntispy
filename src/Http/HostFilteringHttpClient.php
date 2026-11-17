<?php declare(strict_types=1);

namespace StPiGh\ShopwareAntispy\Http;

use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Decorates Symfony's shared `http_client`. Requests to blocked hosts (and
 * their subdomains) never leave the shop; everything else is passed through.
 *
 * Covers callers that use the generic client instead of a dedicated Shopware
 * client, e.g. third-party plugins that check against api.shopware.com.
 * Blocked requests are answered with 403, so callers treat them like a
 * refused remote service instead of receiving fabricated data.
 */
class HostFilteringHttpClient implements HttpClientInterface
{
    use DecoratorTrait;

    public const BLOCKED_HOSTS = [
        'shopware.com',
        'shopware.io',
        'shopware.de',
        'shopware.store',
        'fos.gg',
        'cloudflare.com',
    ];

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $target = $url;
        if (isset($options['base_uri']) && \is_string($options['base_uri']) && parse_url($url, \PHP_URL_HOST) === null) {
            $target = $options['base_uri'];
        }

        if (!self::isBlockedHost((string) parse_url($target, \PHP_URL_HOST))) {
            return $this->client->request($method, $url, $options);
        }

        $response = new MockResponse(json_encode(['blocked' => true], \JSON_THROW_ON_ERROR), [
            'http_code' => 403,
            'response_headers' => [
                'Content-Type: application/json',
                'X-StPiGh-Shopware-Antispy: blocked',
            ],
        ]);

        return (new MockHttpClient($response))->request($method, $url, $options);
    }

    public static function isBlockedHost(string $host): bool
    {
        $host = rtrim(strtolower($host), '.');
        if ($host === '') {
            return false;
        }

        foreach (self::BLOCKED_HOSTS as $blocked) {
            if ($host === $blocked || str_ends_with($host, '.' . $blocked)) {
                return true;
            }
        }

        return false;
    }
}
