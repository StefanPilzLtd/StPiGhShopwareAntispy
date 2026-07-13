<?php declare(strict_types=1);

namespace StPiGh\ShopwareAntispy\Subscriber;

use Shopware\Core\System\SystemConfig\Event\SystemConfigChangedEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use StPiGh\ShopwareAntispy\Service\AntispyConfig;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The store guzzle client resolves its base URI from core.store.apiUri at
 * runtime. The installer parks that key on 127.0.0.1, so the toggle has to
 * restore it — otherwise allowing store communication would still dead-end.
 */
class ConfigToggleSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly SystemConfigService $systemConfigService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SystemConfigChangedEvent::class => 'onSystemConfigChanged',
        ];
    }

    public function onSystemConfigChanged(SystemConfigChangedEvent $event): void
    {
        if ($event->getKey() !== AntispyConfig::ALLOW_STORE_COMMUNICATION) {
            return;
        }

        $this->systemConfigService->set(
            AntispyConfig::STORE_API_URI_KEY,
            $event->getValue() ? AntispyConfig::STORE_API_URI_DEFAULT : AntispyConfig::STORE_API_URI_BLOCKED,
            null,
            false
        );
    }
}
