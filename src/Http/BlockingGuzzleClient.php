<?php declare(strict_types=1);

namespace StPiGh\ShopwareAntispy\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class BlockingGuzzleClient extends Client
{
    public function request(string $method, $uri = '', array $options = []): ResponseInterface
    {
        return $this->responseFor((string) $uri);
    }

    public function requestAsync(string $method, $uri = '', array $options = []): PromiseInterface
    {
        return new FulfilledPromise($this->responseFor((string) $uri));
    }

    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        return $this->responseFor((string) $request->getUri());
    }

    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        return new FulfilledPromise($this->responseFor((string) $request->getUri()));
    }

    private function responseFor(string $uri): ResponseInterface
    {
        $payload = match (true) {
            str_contains($uri, 'environmentinformation') => ['notices' => []],
            str_contains($uri, 'pluginupdates') => ['data' => []],
            str_contains($uri, 'autoupdate/permission') => ['updateAllowed' => false],
            str_contains($uri, 'generatesignature') => ['signature' => ''],
            str_contains($uri, 'userinfo') => [],
            str_contains($uri, 'licenseenvironment') => [],
            str_contains($uri, 'tracking/events') => ['blocked' => true],
            str_contains($uri, 'inappfeatures') => [],
            default => ['blocked' => true],
        };

        return new Response(
            200,
            [
                'Content-Type' => 'application/json',
                'X-StPiGh-Shopware-Antispy' => 'blocked',
            ],
            json_encode($payload, \JSON_THROW_ON_ERROR)
        );
    }
}
