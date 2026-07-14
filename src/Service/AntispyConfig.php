<?php declare(strict_types=1);

namespace StPiGh\ShopwareAntispy\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;

class AntispyConfig
{
    public const ALLOW_STORE_COMMUNICATION = 'StPiGhShopwareAntispy.config.allowStoreCommunication';
    public const ALLOW_APP_SERVER_COMMUNICATION = 'StPiGhShopwareAntispy.config.allowAppServerCommunication';
    public const ALLOWED_APP_SERVER_ORIGINS = 'StPiGhShopwareAntispy.config.allowedAppServerOrigins';

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

    public function isAppServerUriAllowed(string $uri): bool
    {
        if ($this->isAllowed(self::ALLOW_APP_SERVER_COMMUNICATION)) {
            return true;
        }

        $origin = $this->normalizeOrigin($uri);

        return $origin !== null && in_array($origin, $this->allowedAppServerOrigins(), true);
    }

    /**
     * @return list<string>
     */
    public function allowedAppServerOrigins(): array
    {
        try {
            $configured = $this->systemConfigService->getString(self::ALLOWED_APP_SERVER_ORIGINS);
        } catch (\Throwable) {
            return [];
        }

        $origins = [];
        foreach (preg_split('/[\s,;]+/', $configured, -1, \PREG_SPLIT_NO_EMPTY) ?: [] as $value) {
            $origin = $this->normalizeOrigin($value);
            if ($origin !== null) {
                $origins[$origin] = true;
            }
        }

        return array_keys($origins);
    }

    private function normalizeOrigin(string $value): ?string
    {
        $parts = parse_url(trim($value));
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            return null;
        }
        if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true) || isset($parts['user'], $parts['pass'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return $scheme . '://' . $host . $port;
    }
}
