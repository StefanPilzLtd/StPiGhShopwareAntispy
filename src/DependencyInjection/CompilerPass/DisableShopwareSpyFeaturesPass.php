<?php declare(strict_types=1);

namespace StPiGh\ShopwareAntispy\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use StPiGh\ShopwareAntispy\Http\BlockingHttpClient;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class DisableShopwareSpyFeaturesPass implements CompilerPassInterface
{
    private const UPDATE_API_CLIENT = 'Shopware\Core\Framework\Update\Services\ApiClient';

    /**
     * Third-party checks that open their own HTTP connection (bypassing every
     * injectable client) and can therefore only be switched off entirely.
     * FroshTools SystemTimeChecker: `new GuzzleHttp\Client()` to cloudflare.com.
     */
    private const UNINTERCEPTABLE_SERVICES = [
        'Frosh\Tools\Components\Health\Checker\HealthChecker\SystemTimeChecker' => 'frosh_tools.health_checker',
    ];

    private const SERVICE_MATCHERS = [
        'Shopware\Core\System\UsageData\Subscriber\EntityDeleteSubscriber',
        'Shopware\Core\System\UsageData\Subscriber\ConsentStateChangedSubscriber',
        'Shopware\Core\System\UsageData\Consent\ConsentReporter',
        'Shopware\Core\System\UsageData\EntitySync\CollectEntityDataMessageHandler',
        'Shopware\Core\System\UsageData\EntitySync\DispatchEntityMessageHandler',
        'Shopware\Core\System\UsageData\EntitySync\IterateEntityMessageHandler',
        'Shopware\Core\System\UsageData\ScheduledTask\CollectEntityDataTask',
        'Shopware\Core\System\UsageData\ScheduledTask\CollectEntityDataTaskHandler',
        'Shopware\Core\Framework\Store\InAppPurchase\InAppPurchaseUpdateTask',
        'Shopware\Core\Framework\Store\InAppPurchase\Handler\InAppPurchaseUpdateHandler',
        'Shopware\Core\Service\ScheduledTask\InstallServicesTask',
        'Shopware\Core\Service\ScheduledTask\InstallServicesTaskHandler',
        'Shopware\Core\Service\MessageHandler\InstallServicesHandler',
        'Shopware\Core\Service\MessageHandler\UpdateServiceHandler',
        'Shopware\Core\Service\MessageHandler\LogConsentToRegistryHandler',
        'Shopware\Core\Service\Subscriber\ExtensionCompatibilitiesResolvedSubscriber',
        'Shopware\Core\Service\Subscriber\LicenseSyncSubscriber',
        'Shopware\Core\Service\Subscriber\ServiceLifecycleSubscriber',
        'Shopware\Core\Service\Subscriber\PermissionsSubscriber',
        'Shopware\Core\Service\Subscriber\ShopwareAccountSubscriber',
        'Shopware\Core\Framework\Plugin\Telemetry\PluginTelemetrySubscriber',
        'Shopware\Core\Framework\App\Telemetry\AppTelemetrySubscriber',
        'Shopware\Core\Framework\DataAbstractionLayer\Telemetry\EntityTelemetrySubscriber',
        'Shopware\Core\Framework\Adapter\Cache\Telemetry\CacheTelemetrySubscriber',
        'Shopware\Core\Framework\MessageQueue\Telemetry\MessageQueueTelemetrySubscriber',
        'Shopware\Core\Framework\Store\InAppPurchase',
        'Shopware\Core\Service',
        'Shopware\Core\System\UsageData',
        'Shopware\Core\Framework\Plugin\Telemetry',
        'Shopware\Core\Framework\App\Telemetry',
        'Shopware\Core\Framework\DataAbstractionLayer\Telemetry',
        'Shopware\Core\Framework\Adapter\Cache\Telemetry',
        'Shopware\Core\Framework\MessageQueue\Telemetry',
    ];

    public function process(ContainerBuilder $container): void
    {
        $container->setParameter('shopware.usage_data.collection_enabled', false);
        $container->setParameter('shopware.usage_data.gateway.dispatch_enabled', false);
        $container->setParameter('shopware.usage_data.gateway.base_uri', 'http://127.0.0.1');
        $container->setParameter('shopware.usage_data.gateway.batch_size', 1);

        // Daily translation download from GitHub (translation.update). A manual
        // `translation:install` stays possible because the shop owner triggers it.
        $container->setParameter('shopware.translation.scheduled_task.enabled', false);

        // Admin update check contacts releases.shopware.com on every login.
        if ($container->hasDefinition(self::UPDATE_API_CLIENT)) {
            $container->getDefinition(self::UPDATE_API_CLIENT)->replaceArgument(0, new Definition(BlockingHttpClient::class));
        }

        foreach ($container->getDefinitions() as $serviceId => $definition) {
            $class = $definition->getClass();

            if (!$this->matchesBlockedService($serviceId) && ($class === null || !$this->matchesBlockedService($class))) {
                continue;
            }

            $definition->clearTag('kernel.event_subscriber');
            $definition->clearTag('kernel.event_listener');
            $definition->clearTag('messenger.message_handler');
            $definition->clearTag('shopware.scheduled.task');
        }

        foreach ($container->getDefinitions() as $serviceId => $definition) {
            $tag = self::UNINTERCEPTABLE_SERVICES[$definition->getClass() ?? $serviceId] ?? null;
            if ($tag !== null) {
                $definition->clearTag($tag);
            }
        }

        foreach (array_keys($container->findTaggedServiceIds('shopware.metric_transport_factory')) as $serviceId) {
            $container->getDefinition($serviceId)->clearTag('shopware.metric_transport_factory');
        }
    }

    private function matchesBlockedService(string $service): bool
    {
        foreach (self::SERVICE_MATCHERS as $matcher) {
            if (str_contains($service, $matcher)) {
                return true;
            }
        }

        return false;
    }
}
