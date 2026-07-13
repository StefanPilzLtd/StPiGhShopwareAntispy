<?php declare(strict_types=1);

namespace StPiGh\ShopwareAntispy\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use StPiGh\ShopwareAntispy\Service\AntispyConfig;

class BlockingGuzzleClient extends Client
{
    /**
     * Without inner client and config key the client blocks unconditionally.
     * With both, requests are passed through to the original client as long
     * as the shop owner opted in via plugin configuration.
     */
    public function __construct(
        private readonly ?Client $inner = null,
        private readonly ?AntispyConfig $config = null,
        private readonly ?string $allowConfigKey = null,
    ) {
        parent::__construct();
    }

    public function request(string $method, $uri = '', array $options = []): ResponseInterface
    {
        if ($this->passThrough()) {
            return $this->inner->request($method, $uri, $options);
        }

        return $this->responseFor((string) $uri);
    }

    public function requestAsync(string $method, $uri = '', array $options = []): PromiseInterface
    {
        if ($this->passThrough()) {
            return $this->inner->requestAsync($method, $uri, $options);
        }

        return new FulfilledPromise($this->responseFor((string) $uri));
    }

    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        if ($this->passThrough()) {
            return $this->inner->send($request, $options);
        }

        return $this->responseFor((string) $request->getUri());
    }

    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        if ($this->passThrough()) {
            return $this->inner->sendAsync($request, $options);
        }

        return new FulfilledPromise($this->responseFor((string) $request->getUri()));
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        if ($this->passThrough()) {
            return $this->inner->sendRequest($request);
        }

        return $this->responseFor((string) $request->getUri());
    }

    private function passThrough(): bool
    {
        return $this->inner !== null
            && $this->config !== null
            && $this->allowConfigKey !== null
            && $this->config->isAllowed($this->allowConfigKey);
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
