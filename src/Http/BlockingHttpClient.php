<?php declare(strict_types=1);

namespace StPiGh\ShopwareAntispy\Http;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

class BlockingHttpClient extends MockHttpClient
{
    public function __construct()
    {
        parent::__construct($this->createResponse(...), 'http://127.0.0.1');
    }

    public function withOptions(array $options): static
    {
        return new static();
    }

    private function createResponse(string $method, string $url, array $options): ResponseInterface
    {
        $payload = match (true) {
            str_contains($url, '/killswitch') => ['killswitch' => true],
            str_contains($url, '/v1/consent') || str_contains($url, '/api/consent') => ['blocked' => true],
            str_contains($url, '/api/service') => ['services' => [], 'pagination' => ['pages' => 1]],
            default => ['blocked' => true],
        };

        return new MockResponse(json_encode($payload, \JSON_THROW_ON_ERROR), [
            'http_code' => str_contains($url, '/api/consent') ? 202 : 200,
            'response_headers' => [
                'Content-Type: application/json',
                'X-StPiGh-Shopware-Antispy: blocked',
            ],
        ]);
    }
}
