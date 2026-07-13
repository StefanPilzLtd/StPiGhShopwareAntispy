<?php declare(strict_types=1);

namespace StPiGh\ShopwareAntispy\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;

class AntispyConfig
{
    public const ALLOW_STORE_COMMUNICATION = 'StPiGhShopwareAntispy.config.allowStoreCommunication';
    public const ALLOW_APP_SERVER_COMMUNICATION = 'StPiGhShopwareAntispy.config.allowAppServerCommunication';

    public const STORE_API_URI_KEY = 'core.store.apiUri';
    public const STORE_API_URI_DEFAULT = 'https://api.shopware.com';
    public const STORE_API_URI_BLOCKED = 'http://127.0.0.1';

    public function __construct(private readonly SystemConfigService $systemConfigService)
    {
    }

    /**
     * Fail-closed: whenever the configuration cannot be read (missing table
     * during install, broken DB connection, ...) the channel stays blocked.
     */
    public function isAllowed(string $configKey): bool
    {
        try {
            return $this->systemConfigService->getBool($configKey);
        } catch (\Throwable) {
            return false;
        }
    }
}
