<?php declare(strict_types=1);

namespace StPiGh\ShopwareAntispy\Service;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class PrivacyStateInstaller
{
    private const BLOCKED_TASKS = [
        'usage_data.entity_data.collect',
        'in-app-purchase.update',
        'services.install',
    ];

    public function __construct(
        private readonly Connection $connection,
        private readonly SystemConfigService $systemConfigService,
        private readonly AntispyConfig $antispyConfig,
    ) {
    }

    public function apply(): void
    {
        $this->disableStoreAndUsageConfig();
        $this->disableScheduledTasks();
        $this->disableShopwareServiceApps();
    }

    /**
     * Restores the shop to a working default state so that deactivating or
     * uninstalling the plugin does not leave the store connection broken.
     * Deactivated service apps are intentionally NOT re-enabled.
     */
    public function revert(): void
    {
        $this->systemConfigService->set(AntispyConfig::STORE_API_URI_KEY, AntispyConfig::STORE_API_URI_DEFAULT, null, false);
        $this->systemConfigService->delete('core.usageData.lastEntitySyncRunDate');

        $this->connection->executeStatement(
            'UPDATE scheduled_task SET status = :status, next_execution_time = NOW(3), updated_at = NOW(3) WHERE name IN (:names)',
            [
                'status' => 'scheduled',
                'names' => self::BLOCKED_TASKS,
            ],
            [
                'names' => ArrayParameterType::STRING,
            ]
        );
    }

    private function disableStoreAndUsageConfig(): void
    {
        if (!$this->antispyConfig->isAllowed(AntispyConfig::ALLOW_STORE_COMMUNICATION)) {
            $this->systemConfigService->set(AntispyConfig::STORE_API_URI_KEY, AntispyConfig::STORE_API_URI_BLOCKED, null, false);
        }

        $this->systemConfigService->set('core.usageData.lastEntitySyncRunDate', '2999-12-31 23:59:59.000', null, true);
    }

    private function disableScheduledTasks(): void
    {
        $this->connection->executeStatement(
            'UPDATE scheduled_task SET status = :status, next_execution_time = :nextExecutionTime, updated_at = NOW(3) WHERE name IN (:names)',
            [
                'status' => 'inactive',
                'nextExecutionTime' => '2999-12-31 23:59:59.000',
                'names' => self::BLOCKED_TASKS,
            ],
            [
                'names' => ArrayParameterType::STRING,
            ]
        );
    }

    private function disableShopwareServiceApps(): void
    {
        $this->connection->executeStatement(
            "
            UPDATE app
            LEFT JOIN app_translation ON app_translation.app_id = app.id
            SET app.active = 0, app.updated_at = NOW(3)
            WHERE app.active = 1
              AND (
                  LOWER(app.name) LIKE '%analytics%'
                  OR LOWER(app.name) LIKE '%gmv%'
                  OR LOWER(app.name) LIKE '%shopware services%'
                  OR LOWER(app.name) LIKE '%shopware-services%'
                  OR LOWER(app.path) LIKE '%services.shopware%'
                  OR LOWER(app.path) LIKE '%registry.services.shopware%'
                  OR LOWER(app_translation.label) LIKE '%analytics%'
                  OR LOWER(app_translation.label) LIKE '%gmv%'
                  OR LOWER(app_translation.label) LIKE '%shopware services%'
              )
            "
        );
    }
}
