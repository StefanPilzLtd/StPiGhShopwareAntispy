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
    ) {
    }

    public function apply(): void
    {
        $this->disableStoreAndUsageConfig();
        $this->disableScheduledTasks();
        $this->disableShopwareServiceApps();
    }

    private function disableStoreAndUsageConfig(): void
    {
        $this->systemConfigService->set('core.store.apiUri', 'http://127.0.0.1', null, false);
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
