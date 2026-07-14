<?php declare(strict_types=1);

namespace StPiGh\ShopwareAntispy\Subscriber;

use StPiGh\ShopwareAntispy\Service\AntispyConfig;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AdminPrivacySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly string $adminPathName,
        private readonly AntispyConfig $antispyConfig,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -512],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $isAdminRequest = str_starts_with($request->getPathInfo(), '/' . trim($this->adminPathName, '/'));

        if ($request->attributes->get('_route') === 'administration.index') {
            $this->sanitizeAdministrationIndex($response);
        }

        if ($isAdminRequest) {
            $this->hardenHeaders($response);
        }
    }

    private function sanitizeAdministrationIndex(Response $response): void
    {
        $content = $response->getContent();
        if (!\is_string($content) || $content === '') {
            return;
        }

        $content = preg_replace("/serviceRegistryUrl:\\s*'[^']*'/", "serviceRegistryUrl: ''", $content) ?? $content;
        $content = preg_replace("/analyticsGatewayUrl:\\s*'[^']*'/", "analyticsGatewayUrl: ''", $content) ?? $content;

        $response->setContent($content);
        $this->hardenHeaders($response);
    }

    private function hardenHeaders(Response $response): void
    {
        $response->headers->set('X-StPiGh-Shopware-Antispy', 'active');
        $response->headers->set('Permissions-Policy', 'browsing-topics=(), interest-cohort=()');
        $response->headers->set('Referrer-Policy', 'same-origin');

        // App admin modules are embedded as iframes. Keep the default closed,
        // but allow explicitly configured app origins without opening every app server.
        $frameSources = ["'self'"];
        if ($this->antispyConfig->isAllowed(AntispyConfig::ALLOW_APP_SERVER_COMMUNICATION)) {
            $frameSources[] = 'https:';
        }
        array_push($frameSources, ...$this->antispyConfig->allowedAppServerOrigins());

        $response->headers->set(
            'Content-Security-Policy',
            \sprintf("connect-src 'self'; frame-src %s; object-src 'none'; base-uri 'self'", implode(' ', array_unique($frameSources)))
        );
    }
}
