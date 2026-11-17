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
        // Update check (Shopware\Core\Framework\Update\Services\ApiClient): an empty version index
        // plus 404 on the changelog makes core report "no update available" without errors.
        // The recovery tool download is answered with 404 so a web update fails visibly.
        if (str_contains($url, 'releases.shopware.com/changelog/index.json')) {
            return $this->jsonResponse([], 200);
        }
        if (str_contains($url, 'releases.shopware.com') || str_contains($url, 'shopware/web-installer')) {
            return $this->jsonResponse(['blocked' => true], 404);
        }

        $payload = match (true) {
            str_contains($url, '/killswitch') => ['killswitch' => true],
            str_contains($url, '/v1/consent') || str_contains($url, '/api/consent') => ['blocked' => true],
            str_contains($url, '/api/service') => ['services' => [], 'pagination' => ['pages' => 1]],
            default => ['blocked' => true],
        };

        return $this->jsonResponse($payload, str_contains($url, '/api/consent') ? 202 : 200);
    }

    /**
     * @param array<mixed> $payload
     */
    private function jsonResponse(array $payload, int $status): ResponseInterface
    {
        return new MockResponse(json_encode($payload, \JSON_THROW_ON_ERROR), [
            'http_code' => $status,
            'response_headers' => [
                'Content-Type: application/json',
                'X-StPiGh-Shopware-Antispy: blocked',
            ],
        ]);
    }
}
