<?php declare(strict_types=1);

namespace StPiGh\ShopwareAntispy;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use StPiGh\ShopwareAntispy\DependencyInjection\CompilerPass\DisableShopwareSpyFeaturesPass;
use StPiGh\ShopwareAntispy\Service\PrivacyStateInstaller;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class StPiGhShopwareAntispy extends Plugin
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new DisableShopwareSpyFeaturesPass());
    }

    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);

        $this->applyPrivacyState();
    }

    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);

        $this->applyPrivacyState();
    }

    private function applyPrivacyState(): void
    {
        if (!$this->container?->has(PrivacyStateInstaller::class)) {
            return;
        }

        $this->container->get(PrivacyStateInstaller::class)->apply();
    }
}
